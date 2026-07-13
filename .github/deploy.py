#!/usr/bin/env python3
import os, sys, time, paramiko

HOST   = os.environ["SFTP_HOST"]
PORT   = int(os.environ.get("SFTP_PORT", "22"))
USER   = os.environ["SFTP_USER"]
PASS   = os.environ["SFTP_PASSWORD"]
REMOTE = os.environ.get("SFTP_TARGET", "/dsc06424530/")

FILES = [
    # Public
    ("index.php",                         "index.php"),
    ("send.php",                          "send.php"),
    ("robots.txt",                        "robots.txt"),
    # PHPMailer
    ("phpmailer/Exception.php",           "phpmailer/Exception.php"),
    ("phpmailer/PHPMailer.php",           "phpmailer/PHPMailer.php"),
    ("phpmailer/SMTP.php",                "phpmailer/SMTP.php"),
    # Includes
    ("includes/common.php",               "includes/common.php"),
    ("includes/style.php",                "includes/style.php"),
    # Familie
    ("familie/auth.php",                  "familie/auth.php"),
    ("familie/login.php",                 "familie/login.php"),
    ("familie/logout.php",                "familie/logout.php"),
    ("familie/index.php",                 "familie/index.php"),
    ("familie/admin.php",                 "familie/admin.php"),
    ("familie/fotos.php",                 "familie/fotos.php"),
    ("familie/kalender.php",              "familie/kalender.php"),
    ("familie/rezepte.php",               "familie/rezepte.php"),
    ("familie/erinnerungen.php",          "familie/erinnerungen.php"),
    ("familie/data/.htaccess",            "familie/data/.htaccess"),
]

def connect(retries=3):
    for attempt in range(1, retries + 1):
        try:
            client = paramiko.SSHClient()
            client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            client.connect(HOST, port=PORT, username=USER, password=PASS, timeout=20)
            print(f"✅ Connected (attempt {attempt})")
            return client, client.open_sftp()
        except Exception as e:
            print(f"⚠️  Attempt {attempt}: {e}")
            if attempt < retries: time.sleep(5)
    sys.exit(1)

def ensure_dir(sftp, path):
    try: sftp.stat(path)
    except FileNotFoundError:
        sftp.mkdir(path); print(f"📁 {path}")

client, sftp = connect()
base = REMOTE.rstrip("/")

# Ensure dirs
for d in ["phpmailer", "includes", "familie", "familie/data"]:
    ensure_dir(sftp, f"{base}/{d}")

errors = []
for local_rel, remote_rel in FILES:
    local  = os.path.join(os.path.dirname(__file__), "..", local_rel)
    remote = f"{base}/{remote_rel}"
    if not os.path.exists(local):
        print(f"⏭️  SKIP {local_rel}"); continue
    try:
        sftp.put(local, remote)
        print(f"✅ {local_rel} ({os.path.getsize(local):,}B)")
    except Exception as e:
        print(f"❌ {local_rel}: {e}"); errors.append(local_rel)

sftp.close(); client.close()
print(f"\n{'✅ Done' if not errors else '❌ Errors: ' + str(errors)}")
sys.exit(1 if errors else 0)
