# Da PWA ad APK — Manutenzioni Antifurto

Stesso schema usato per Rapportino e Verifiche: Capacitor + build automatica su GitHub Actions.

## 1. Crea il repo
Crea un nuovo repo (es. `nervaluca/manutenzioni-antifurto`) e carica tutti i file di questa cartella
mantenendo la struttura (compreso `.github/workflows/build-apk.yml`).

## 2. Prima build locale (una tantum, per generare la cartella `android/`)
Se vuoi generare la piattaforma Android una volta in locale invece di lasciarlo fare alla Action:

```bash
npm install
mkdir www
cp index.html manifest.json sw.js icon-192.png icon-512.png www/
npx cap init "Manutenzioni Antifurto" "it.tensolutions.antifurto" --web-dir www
npx cap add android
git add android
git commit -m "Aggiunge piattaforma Android"
git push
```

Da quel momento il workflow farà solo `npx cap sync android` e la build ad ogni push su `main`.
In alternativa, lascia che sia la Action a fare `cap add android` automaticamente al primo run
(il workflow qui incluso già lo prevede con `npx cap add android || npx cap sync android`).

## 3. Build automatica
Ad ogni push su `main`, GitHub Actions:
1. installa le dipendenze Capacitor
2. copia `index.html`, `manifest.json`, `sw.js`, le icone dentro `www/`
3. sincronizza il progetto Android
4. compila `app-debug.apk`
5. lo pubblica come artifact scaricabile dalla tab **Actions** del repo

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

## 6. Firma per release (quando pronto per distribuzione)
Il workflow qui genera solo `assembleDebug` (installabile ma non firmato per il Play Store).
Per una release firmata vanno aggiunti keystore + credenziali come secrets del repo, come già fatto
eventualmente per gli altri progetti Android.
