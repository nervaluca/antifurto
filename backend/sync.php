<?php
// API di sincronizzazione multi-dispositivo per Manutenzioni Antifurto (Ten Solutions)
// Un solo endpoint POST: riceve le modifiche locali del dispositivo, le salva (con
// risoluzione conflitti "vince la modifica più recente"), e restituisce tutte le
// modifiche più recenti di "since" in poi (comprese quelle di altri dispositivi).

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

$TABLES = [
    'clienti'    => ['table' => 'hager_clienti',    'fields' => ['id','nome','indirizzo','telefono','note']],
    'centrali'   => ['table' => 'hager_centrali',   'fields' => ['id','clienteId','modello','modelloCustom','matricola','dataInstallazione','zone','note']],
    'sensori'    => ['table' => 'hager_sensori',    'fields' => ['id','centraleId','categoria','etichetta','modello','zona','batteria','autonomiaAnni','dataInstallazione','dataSostituzione','note']],
    'interventi' => ['table' => 'hager_interventi', 'fields' => ['id','centraleId','data','tecnico','tipo','batterie','note']],
];

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) fail('JSON non valido');

if (!isset($body['apiKey']) || $body['apiKey'] !== API_KEY) fail('Chiave API non valida', 401);

$since = isset($body['since']) ? (int)$body['since'] : 0;
$incoming = isset($body['changes']) && is_array($body['changes']) ? $body['changes'] : [];

try {
    $pdo = getPDO();
} catch (Exception $e) {
    fail('Errore di connessione al database: ' . $e->getMessage(), 500);
}

$pdo->beginTransaction();
try {
    // 1) Applica le modifiche in arrivo dal dispositivo (upsert manuale, "vince il più recente")
    foreach ($TABLES as $key => $def) {
        if (empty($incoming[$key]) || !is_array($incoming[$key])) continue;
        $table = $def['table'];
        $fields = $def['fields'];

        $selectStmt = $pdo->prepare("SELECT updated_at FROM $table WHERE id = ?");
        $updateCols = implode(', ', array_map(fn($f) => "$f = :$f", array_diff($fields, ['id'])));
        $updateStmt = $pdo->prepare("UPDATE $table SET $updateCols, updated_at = :updated_at, deleted = :deleted WHERE id = :id");
        $insertCols = implode(', ', array_merge($fields, ['updated_at', 'deleted']));
        $insertPlaceholders = implode(', ', array_map(fn($f) => ":$f", array_merge($fields, ['updated_at', 'deleted'])));
        $insertStmt = $pdo->prepare("INSERT INTO $table ($insertCols) VALUES ($insertPlaceholders)");

        foreach ($incoming[$key] as $rec) {
            if (empty($rec['id'])) continue;
            $updatedAt = isset($rec['updated_at']) ? (int)$rec['updated_at'] : 0;
            $deleted = !empty($rec['deleted']) ? 1 : 0;

            $selectStmt->execute([$rec['id']]);
            $existing = $selectStmt->fetch();

            $params = [];
            foreach ($fields as $f) {
                $params[$f] = isset($rec[$f]) ? $rec[$f] : null;
            }
            $params['updated_at'] = $updatedAt;
            $params['deleted'] = $deleted;

            if ($existing) {
                // Aggiorna solo se la modifica in arrivo è più recente (o uguale) di quella salvata
                if ($updatedAt >= (int)$existing['updated_at']) {
                    $params['id'] = $rec['id'];
                    $updateStmt->execute($params);
                }
            } else {
                $insertStmt->execute($params);
            }
        }
    }

    // 2) Raccoglie tutte le modifiche (di qualunque dispositivo) più recenti di "since"
    $changes = [];
    foreach ($TABLES as $key => $def) {
        $table = $def['table'];
        $fields = $def['fields'];
        $cols = implode(', ', array_merge($fields, ['updated_at', 'deleted']));
        $stmt = $pdo->prepare("SELECT $cols FROM $table WHERE updated_at > ?");
        $stmt->execute([$since]);
        $changes[$key] = $stmt->fetchAll();
    }

    $pdo->commit();

    echo json_encode([
        'serverTime' => (int) round(microtime(true) * 1000),
        'changes' => $changes,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    fail('Errore durante la sincronizzazione: ' . $e->getMessage(), 500);
}
