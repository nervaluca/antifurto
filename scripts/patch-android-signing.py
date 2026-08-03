#!/usr/bin/env python3
"""
Inserisce automaticamente il blocco signingConfigs (release) dentro android/app/build.gradle,
generato al volo da Capacitor in CI (npx cap add android). Idempotente: se eseguito più volte
non duplica le modifiche. Non richiede un progetto Android locale: gira interamente in CI.
"""
import re
import sys
import pathlib

path = pathlib.Path("android/app/build.gradle")
if not path.exists():
    print(f"ATTENZIONE: {path} non trovato, salto la patch.")
    sys.exit(0)

content = path.read_text()

SIGNING_BLOCK = '''
    signingConfigs {
        release {
            if (System.getenv("KEYSTORE_PASSWORD")) {
                storeFile file(System.getenv("RELEASE_STORE_FILE") ?: "release.keystore")
                storePassword System.getenv("KEYSTORE_PASSWORD")
                keyAlias System.getenv("KEY_ALIAS")
                keyPassword System.getenv("KEY_PASSWORD")
            }
        }
    }
'''

changed = False

if "signingConfigs" not in content:
    new_content, n = re.subn(r'(android\s*\{\s*\n)', r'\1' + SIGNING_BLOCK, content, count=1)
    if n == 1:
        content = new_content
        changed = True
    else:
        print("ATTENZIONE: non ho trovato 'android {' nel file, blocco signingConfigs NON inserito.")

if "signingConfig signingConfigs.release" not in content:
    new_content, n = re.subn(
        r'(buildTypes\s*\{\s*release\s*\{)',
        r'\1\n            signingConfig signingConfigs.release',
        content, count=1
    )
    if n == 1:
        content = new_content
        changed = True
    else:
        print("ATTENZIONE: non ho trovato 'buildTypes { release {' nel file, riferimento signingConfig NON inserito.")

if changed:
    path.write_text(content)
    print("build.gradle aggiornato con la configurazione di firma release.")
else:
    print("build.gradle già configurato per la firma release, nessuna modifica necessaria.")
