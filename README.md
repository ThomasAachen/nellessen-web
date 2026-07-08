# nellessen.de

> Stay Tuned — Landingpage während der Überarbeitung von nellessen.de

Dark, hypermodern "Stay Tuned" Seite mit Three.js 3D-Animation, Kontaktformular und allen gesetzlich vorgeschriebenen Seiten (§ 5 TMG).

---

## Stack

| | |
|---|---|
| Frontend | HTML5 · CSS3 · Vanilla JS |
| 3D | Three.js r128 |
| Backend | PHP 8+ (Kontaktformular) |
| Hosting | IONOS Shared Hosting (SFTP) |
| CI/CD | GitHub Actions |

---

## Projektstruktur

```
nellessen/
├── index.html              # Hauptseite (Stay Tuned + alle Modals)
├── send.php                # Kontaktformular-Handler → info@nellessen.de
├── README.md
├── .gitignore
└── .github/
    └── workflows/
        └── deploy.yml      # Auto-Deploy bei Push auf main
```

---

## GitHub Actions Setup (einmalig)

Öffne das GitHub Repo → **Settings → Secrets and variables → Actions → New repository secret**

| Secret | Wert |
|--------|------|
| `SFTP_HOST` | `home13471641.1and1-data.host` |
| `SFTP_USER` | `p370731` |
| `SFTP_PASSWORD` | *(dein IONOS SFTP Passwort)* |
| `SFTP_TARGET` | `/` *(oder Webroot-Pfad, s.u.)* |

Nach dem Einrichten: jeder `git push` auf `main` deployed automatisch auf nellessen.de.

---

## Manuelles Deployment

```bash
sftp -P 22 p370731@home13471641.1and1-data.host
sftp> put index.html
sftp> put send.php
sftp> bye
```

---

## Lokale Entwicklung

Da `send.php` PHP benötigt, für lokale Tests einen einfachen PHP-Server starten:

```bash
php -S localhost:8080
# → http://localhost:8080
```

---

## Enthaltene Seiten / Modals

- ✅ **Stay Tuned** — Hauptseite mit Three.js 3D-Scene
- ✅ **Impressum** — § 5 TMG (T. Nellessen, Johnens Gäßchen 12, 52146 Würselen)
- ✅ **Datenschutzerklärung** — DSGVO-konform, inkl. Google Fonts + Cloudflare CDN + IONOS Hosting
- ✅ **Haftungsausschluss** — Inhalte, Links, Urheberrecht
- ✅ **Kontaktformular** → sendet an info@nellessen.de per PHP mail()

---

*Würselen · Aachen · NRW*
