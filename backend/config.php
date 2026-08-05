<?php
// ============================================================
// CONFIGURAZIONE — compilata con i dati di iw1fzr.it (Aruba)
// ============================================================

// Credenziali del database MySQL (stesso database di WordPress; le tabelle
// create da questa app hanno il prefisso "hager_" e non toccano quelle "wp_*").
define('DB_HOST', '89.46.111.27');
define('DB_NAME', 'Sql1012292_1');
define('DB_USER', 'Sql1012292');
define('DB_PASS', '41b0n2210w');

// Chiave segreta condivisa tra app e server (deve combaciare ESATTAMENTE con
// SYNC_API_KEY dentro index.html — vedi sotto).
define('API_KEY', 'OOmheFTfX3iEbvldVUpQbfwxUCsZfuyv8TpGiL7ytamOhb0F');

function getPDO() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
