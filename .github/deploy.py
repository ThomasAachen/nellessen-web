#!/usr/bin/env python3
"""
nellessen.de — SFTP Deploy Script
Läuft in GitHub Actions, uploaded Dateien direkt auf IONOS
"""
import os, sys, time, paramiko

HOST   = os.environ["SFTP_HOST"]
PORT   = int(os.environ.get("SFTP_PORT", "22"))
USER   = os.environ["SFTP_USER"]
PASS   = os.environ["SFTP_PASSWORD"]
REMOTE = os.environ.get("SFTP_TARGET", "/dsc06424530/")

FILES = [
    "index.html",
    "send.php",
    "robots.txt",
]

def connect(retries=3):
    for attempt in range(1, retries + 1):
        try:
            client = paramiko.SSHClient()
            client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            client.connect(HOST, port=PORT, username=USER, password=PASS, timeout=20)
            sftp = client.open_sftp()
            print(f"✅ Verbunden mit {HOST} (Versuch {attempt})")
            return client, sftp
        except Exception as e:
            print(f"⚠️  Versuch {attempt} fehlgeschlagen: {e}")
            if attempt < retries:
                time.sleep(5)
    print("❌ Verbindung fehlgeschlagen nach 3 Versuchen")
    sys.exit(1)

client, sftp = connect()

errors = []
for filename in FILES:
    local = os.path.join(os.path.dirname(__file__), "..", filename)
    remote = REMOTE.rstrip("/") + "/" + filename
    if not os.path.exists(local):
        print(f"⏭️  SKIP  {filename} (nicht vorhanden)")
        continue
    try:
        sftp.put(local, remote)
        size = os.path.getsize(local)
        print(f"✅ OK    {filename}  ({size:,} Bytes) → {remote}")
    except Exception as e:
        print(f"❌ FAIL  {filename}: {e}")
        errors.append(filename)

sftp.close()
client.close()

if errors:
    print(f"\n❌ Fehler bei: {', '.join(errors)}")
    sys.exit(1)
else:
    print(f"\n✅ Deploy abgeschlossen — {len(FILES)} Datei(en) übertragen")
