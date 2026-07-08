#!/usr/bin/env python3
import os, sys, time, paramiko

HOST   = os.environ["SFTP_HOST"]
PORT   = int(os.environ.get("SFTP_PORT", "22"))
USER   = os.environ["SFTP_USER"]
PASS   = os.environ["SFTP_PASSWORD"]
REMOTE = os.environ.get("SFTP_TARGET", "/dsc06424530/")

FILES = [
    ("index.html",                "index.html"),
    ("send.php",                  "send.php"),
    ("robots.txt",                "robots.txt"),
    ("phpmailer/Exception.php",   "phpmailer/Exception.php"),
    ("phpmailer/PHPMailer.php",   "phpmailer/PHPMailer.php"),
    ("phpmailer/SMTP.php",        "phpmailer/SMTP.php"),
]

def connect(retries=3):
    for attempt in range(1, retries + 1):
        try:
            client = paramiko.SSHClient()
            client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            client.connect(HOST, port=PORT, username=USER, password=PASS, timeout=20)
            sftp = client.open_sftp()
            print(f"✅ Verbunden (Versuch {attempt})")
            return client, sftp
        except Exception as e:
            print(f"⚠️  Versuch {attempt}: {e}")
            if attempt < retries:
                time.sleep(5)
    sys.exit(1)

def ensure_dir(sftp, remote_dir):
    try:
        sftp.stat(remote_dir)
    except FileNotFoundError:
        sftp.mkdir(remote_dir)
        print(f"📁 Erstellt: {remote_dir}")

client, sftp = connect()
remote_base = REMOTE.rstrip("/")

# Ensure phpmailer/ subdir exists
ensure_dir(sftp, f"{remote_base}/phpmailer")

errors = []
for local_rel, remote_rel in FILES:
    local  = os.path.join(os.path.dirname(__file__), "..", local_rel)
    remote = f"{remote_base}/{remote_rel}"
    if not os.path.exists(local):
        print(f"⏭️  SKIP {local_rel}")
        continue
    try:
        sftp.put(local, remote)
        size = os.path.getsize(local)
        print(f"✅ {local_rel} ({size:,} B)")
    except Exception as e:
        print(f"❌ {local_rel}: {e}")
        errors.append(local_rel)

sftp.close()
client.close()
sys.exit(1 if errors else 0)
