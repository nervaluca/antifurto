# Da PWA ad APK — Manutenzioni Antifurto

Stesso schema usato per Rapportino e Verifiche: Capacitor + build automatica su GitHub Actions.

## 1. Crea il repo
Crea un nuovo repo (es. `nervaluca/manutenzioni-antifurto`) e carica tutti i file di questa cartella
mantenendo la struttura (compreso `.github/workflows/build.yml`).

## 2. Nessuna cartella android/ da committare
A differenza di un setup "classico", qui — come in Rapportino e Verifiche — la cartella `android/`
**non va committata**: il workflow la rigenera da zero ad ogni run con `npx cap add android`.
Non devi fare nulla in locale prima del primo push.

## 3. Build automatica
Ad ogni push su `main` (o `master`), GitHub Actions:
1. installa le dipendenze Capacitor
2. copia `index.html`, `manifest.json`, `sw.js`, le icone dentro `www/`
3. genera la piattaforma Android da zero e sincronizza
4. compila `app-debug.apk` e lo pubblica come artifact
5. compila un APK release, lo firma con `r0adkll/sign-android-release` (vedi punto 6) e lo pubblica come artifact separato
6. tutto scaricabile dalla tab **Actions** del repo

## 4. Differenze rispetto a Rapportino/Verifiche
- **Nessun backend/sync**: qui non serve `CapacitorHttp: {enabled: true}` né `SERVER_BASE`,
  perché tutti i dati (clienti, centrali, sensori, interventi) restano sul dispositivo.
- **Backup**: il pulsante "Esporta backup" in APK usa i plugin nativi `@capacitor/filesystem`
  e `@capacitor/share` per salvare il JSON e aprire il foglio di condivisione (WhatsApp, email,
  Drive...). Nel browser/PWA usa invece il download classico. Il codice gestisce già entrambi i casi.
- **Import**: usa l'`<input type="file">` standard, che funziona sia in PWA che dentro la WebView
  Capacitor senza bisogno di plugin aggiuntivi.

## 5. Icona e splash screen (opzionale)
Per un'icona/splash più curati (invece del placeholder generato):
```bash
npm install -D @capacitor/assets
npx capacitor-assets generate --iconBackgroundColor "#14171c" --splashBackgroundColor "#14171c"
```
Servono un `icon.png` (1024x1024) e uno `splash.png` (2732x2732) sorgente da mettere in `assets/`.

## 6. Firma per release (stesso sistema di Rapportino e Verifiche)

Il workflow (`.github/workflows/build.yml`) costruisce sempre un APK **debug**, e in più compila un APK release e lo firma con l'action `r0adkll/sign-android-release`, esattamente come fanno già `nervaluca/rapportino` e `nervaluca/verifiche`. **Non serve toccare `build.gradle` né avere il progetto Android in locale** — la firma avviene esternamente sull'APK già compilato.

Servono gli stessi 4 secrets del repo (Settings → Secrets and variables → Actions → Repository secrets):
- `KEYSTORE_BASE64` — il tuo file `.keystore`/`.jks` codificato in base64
- `KEYSTORE_PASSWORD`
- `KEY_ALIAS`
- `KEY_PASSWORD`

Per generare `KEYSTORE_BASE64` da un keystore esistente:
```bash
base64 -i tuo-file.keystore | tr -d '\n' > keystore_base64.txt
```
poi incolla il contenuto come valore del secret.

Se hai già questi 4 secrets configurati su Rapportino o Verifiche, puoi copiare/incollare gli stessi identici valori qui — è lo stesso keystore che puoi riusare su più app, oppure uno diverso per ognuna, a seconda di cosa preferisci.

Ad ogni push su `main` (o `master`) otterrai due artifact: `manutenzioni-antifurto-debug` e `manutenzioni-antifurto-release` (quest'ultimo firmato, pronto per l'installazione diretta).

Nota: se i 4 secrets non sono ancora presenti nel repo, lo step "Sign APK" fallirà (esattamente come succederebbe su Rapportino/Verifiche senza quei secrets) — il job risulterà in errore ma l'APK debug sarà comunque stato caricato prima di quel punto. Aggiungi i secrets per far passare anche la parte di firma.

