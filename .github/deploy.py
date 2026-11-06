#!/usr/bin/env python3
"""Lädt alle Projektdateien per SFTP auf den Webspace. Nutzerdaten (Datenbank, Uploads) werden nie angefasst."""
import os, sys, time, paramiko

HOST   = os.environ["SFTP_HOST"]
PORT   = int(os.environ.get("SFTP_PORT", "22"))
USER   = os.environ["SFTP_USER"]
PASS   = os.environ["SFTP_PASSWORD"]
REMOTE = os.environ.get("SFTP_TARGET", "/dsc06424530/")

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
EXCLUDE_DIRS  = {".git", ".github", "node_modules", "__pycache__"}
EXCLUDE_FILES = {"README.md", ".gitignore", ".DS_Store", "Thumbs.db"}
ALLOWED_EXT   = {".php", ".html", ".css", ".js", ".txt", ".htaccess", ".json", ".xml", ".ico", ".svg", ".png", ".jpg", ".webp"}
# Alte Dateien, die auf dem Server nicht mehr liegen sollen
REMOVE = ["cleanup.php", "familie/kalender.php"]

def want(rel):
    parts = rel.split("/")
    if parts[0] == "familie" and len(parts) > 1 and parts[1] in ("data", "uploads"):
        return parts[-1] == ".htaccess"           # nur der Schutz, keine Nutzerdaten
    name = parts[-1]
    if name in EXCLUDE_FILES: return False
    ext = os.path.splitext(name)[1].lower() if name != ".htaccess" else ".htaccess"
    return ext in ALLOWED_EXT

def collect():
    out = []
    for dp, dns, fns in os.walk(ROOT):
        dns[:] = [d for d in dns if d not in EXCLUDE_DIRS]
        for fn in fns:
            rel = os.path.relpath(os.path.join(dp, fn), ROOT).replace(os.sep, "/")
            if want(rel): out.append(rel)
    return sorted(out)

def connect(retries=3):
    for attempt in range(1, retries + 1):
        try:
            client = paramiko.SSHClient()
            client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            client.connect(HOST, port=PORT, username=USER, password=PASS, timeout=20)
            print(f"Verbunden (Versuch {attempt})")
            return client, client.open_sftp()
        except Exception as e:
            print(f"Versuch {attempt} fehlgeschlagen: {e}")
            if attempt < retries: time.sleep(5)
    sys.exit(1)

def ensure_dir(sftp, path):
    try: sftp.stat(path)
    except IOError:
        sftp.mkdir(path); print(f"Ordner angelegt: {path}")

files = collect()
print(f"{len(files)} Dateien zu übertragen")
client, sftp = connect()
base = REMOTE.rstrip("/")
errors = []

dirs = sorted({os.path.dirname(f) for f in files if os.path.dirname(f)}, key=lambda d: d.count("/"))
for d in dirs:
    parts = d.split("/")
    for i in range(1, len(parts) + 1):
        ensure_dir(sftp, base + "/" + "/".join(parts[:i]))

for rel in files:
    try:
        sftp.put(os.path.join(ROOT, rel), f"{base}/{rel}")
        print(f"ok  {rel}")
    except Exception as e:
        print(f"FEHLER {rel}: {e}"); errors.append(rel)

for rel in REMOVE:
    try:
        sftp.remove(f"{base}/{rel}"); print(f"entfernt {rel}")
    except IOError:
        pass

sftp.close(); client.close()
print("Fertig" if not errors else f"Fehler bei: {errors}")
sys.exit(1 if errors else 0)
