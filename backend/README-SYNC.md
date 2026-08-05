# Sincronizzazione multi-dispositivo — Guida al deploy

Con questa modifica l'app non è più solo locale: clienti, centrali, componenti e interventi
si sincronizzano automaticamente tra tutti i dispositivi che usano l'app, passando per un
piccolo database su iw1fzr.it. Ho testato l'intero flusso (creazione, sincronizzazione,
conflitti, cancellazioni propagate) con un server PHP/SQLite locale identico a quello che
userai su Aruba — funziona.

## 1. Trova (o crea) il database MySQL su Aruba

1. Vai su **admin.aruba.it** → Hosting → il pacchetto di iw1fzr.it → **Gestione Database / MySQL**
2. Se hai la possibilità di crearne uno nuovo dedicato, fallo (più pulito). Altrimenti va bene
   riusare quello di WordPress: le tabelle create da questa app hanno tutte il prefisso
   `hager_`, quindi non toccano le tabelle `wp_*` esistenti.
3. Segnati: **host** (di solito `localhost`), **nome database**, **utente**, **password**.
4. Apri **phpMyAdmin** dal pannello Aruba, seleziona il database, vai su **SQL** e incolla
   il contenuto di `backend/schema.sql` (crea le 4 tabelle: clienti, centrali, sensori,
   interventi).

## 2. Configura le credenziali

Apri `backend/config.php` e compila:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'il-tuo-nome-database');
define('DB_USER', 'il-tuo-utente');
define('DB_PASS', 'la-tua-password');
define('API_KEY', 'CAMBIA_QUESTA_CHIAVE_LUNGA_E_CASUALE_1234567890');
```

**Importante sulla `API_KEY`**: è una password condivisa tra l'app e il server, per evitare
che estranei scrivano nel tuo database. Cambiala con una stringa lunga e casuale a tua scelta
(es. genera una password robusta come faresti per un account). Poi apri `index.html`, cerca
la costante `SYNC_API_KEY` (vicino all'inizio dello script) e mettici **esattamente la stessa
stringa**. Se non combaciano, la sincronizzazione fallisce con "Chiave API non valida".

## 3. Carica i file via FTP (FileZilla)

Struttura da caricare dentro `iw1fzr.it/hager/`:
```
iw1fzr.it/hager/
├── index.html
├── manifest.json
├── sw.js
├── icon-192.png
├── icon-512.png
├── jspdf.umd.min.js
└── backend/
    ├── config.php   (con le tue credenziali vere, NON quello di esempio)
    └── sync.php
```
Non serve caricare `schema.sql` (serve solo una volta per creare le tabelle via phpMyAdmin).

## 4. Verifica che funzioni

Apri `https://iw1fzr.it/hager/` nel browser: dovresti vedere in alto nella lista clienti
la scritta "Sincronizzato alle HH:MM" con un pallino verde, invece di "In attesa di
sincronizzazione...". Se vedi un pallino rosso "Sync non riuscita", controlla che:
- `backend/config.php` abbia le credenziali giuste
- `API_KEY` combaci esattamente tra `config.php` e `index.html`
- Le tabelle `hager_*` esistano davvero nel database (rilancia `schema.sql` se in dubbio)

## 5. Come funziona (per capire cosa aspettarti)

- **Ogni modifica** (autosalvataggio) viene inviata al server dopo ~4 secondi di pausa.
- L'app sincronizza anche **all'avvio**, **ogni 45 secondi** mentre resta aperta, e **quando
  torna in primo piano** dopo essere stata in background.
- Puoi anche forzare una sincronizzazione manuale col pulsante "🔄 Sincronizza" in alto
  nella lista clienti.
- **Conflitti**: se due persone modificano lo stesso record mentre sono offline, vince la
  modifica più recente (basata sull'orario dell'ultima modifica).
- **Cancellazioni**: eliminare un cliente/centrale/componente lo segna come cancellato e
  la cancellazione si propaga agli altri dispositivi al prossimo sync — non sparisce
  bruscamente dal database (così, se due dispositivi erano offline, non si "resuscitano"
  a vicenda i dati per errore).
- **Offline**: l'app continua a funzionare normalmente anche senza connessione (tutto resta
  in locale come prima); appena torna la rete, la sincronizzazione riprende da sola.

## 6. Nell'APK

Nell'app installata (non nel browser), la sincronizzazione punta a un indirizzo fisso
(`https://iw1fzr.it/hager/backend/sync.php`, dentro la costante `SYNC_ENDPOINT` in
`index.html`) perché l'APK gira su un dominio interno (`https://localhost`) e non su
iw1fzr.it. Ho già abilitato `CapacitorHttp` in `capacitor.config.json`, che nell'APK
instrada le chiamate di rete a basso livello evitando i problemi di CORS — non serve
nessuna configurazione aggiuntiva, basta ricompilare l'APK dopo aver caricato i file.
