#!/usr/bin/env python3
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
    "cleanup.php",
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
            print(f"⚠️  Versuch {attempt}: {e}")
            if attempt < retries:
                time.sleep(5)
    sys.exit(1)

client, sftp = connect()
errors = []
for filename in FILES:
    local = os.path.join(os.path.dirname(__file__), "..", filename)
    remote = REMOTE.rstrip("/") + "/" + filename
    if not os.path.exists(local):
        print(f"⏭️  SKIP {filename}")
        continue
    try:
        sftp.put(local, remote)
        print(f"✅ OK   {filename} → {remote}")
    except Exception as e:
        print(f"❌ FAIL {filename}: {e}")
        errors.append(filename)

sftp.close()
client.close()
sys.exit(1 if errors else 0)
