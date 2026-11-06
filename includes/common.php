<?php
/**
 * Gemeinsames Seitengerüst: Kopf, Navigation, Footer mit Rechtstexten und Kontaktformular.
 */
function site_base() {
    return strpos($_SERVER['SCRIPT_NAME'], '/familie/') !== false ? '../' : '';
}

function de_month_year() {
    $m = array('', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');
    return $m[(int)date('n')] . ' ' . date('Y');
}

function page_head($title = 'Familie Nellessen · Würselen', $desc = 'Familie Nellessen aus Würselen, zwischen Wurmtal und Kaiserstadt.', $extra = '') {
    echo '<!DOCTYPE html><html lang="de"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<meta name="description" content="' . htmlspecialchars($desc) . '">';
    if (strpos($_SERVER['SCRIPT_NAME'], '/familie/') !== false) {
        echo '<meta name="robots" content="noindex,nofollow">';
    }
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">';
    require __DIR__ . '/style.php';
    echo $extra;
    echo '</head><body>';
}

function site_nav($active = '', $solid = false) {
    $base = site_base();
    ?>
    <nav class="nav<?= $solid ? ' solid' : '' ?>" id="mainNav">
      <a href="<?= $base ?>index.php" class="nav-brand" style="opacity:1">Nellessen</a>
      <ul class="nav-links">
        <li><a href="<?= $base ?>index.php#wuerselen" <?= $active === 'wuerselen' ? 'class="active"' : '' ?>>Würselen</a></li>
        <li><a href="<?= $base ?>index.php#name" <?= $active === 'name' ? 'class="active"' : '' ?>>Der Name</a></li>
        <li><a href="<?= $base ?>index.php#blog" <?= $active === 'blog' ? 'class="active"' : '' ?>>Blog</a></li>
        <li><a href="<?= $base ?>familie/index.php" class="<?= $active === 'familie' ? 'active' : '' ?>">Familie</a></li>
        <li><button onclick="openM('kontakt')">Kontakt</button></li>
      </ul>
    </nav>
    <script>
    (function(){var n=document.getElementById('mainNav');if(n.classList.contains('solid'))return;
    addEventListener('scroll',function(){n.classList.toggle('solid',scrollY>50)},{passive:true});})();
    </script>
    <?php
}

function site_footer($extra = '') {
    $base = site_base();
    ?>
    <hr class="div">
    <footer class="site-footer">
      <div class="footer-inner">
        <span class="footer-brand">Nellessen · Würselen</span>
        <nav class="footer-nav">
          <button onclick="openM('impressum')">Impressum</button>
          <button onclick="openM('datenschutz')">Datenschutzerklärung</button>
          <button onclick="openM('disclaimer')">Haftungsausschluss</button>
        </nav>
        <span class="footer-copy">© <?= date('Y') ?> Familie Nellessen</span>
      </div>
    </footer>

    <div class="overlay" id="o-impressum" role="dialog" aria-modal="true">
      <div class="modal">
        <button class="modal-close" onclick="closeM('impressum')" aria-label="Schließen">×</button>
        <h2>Impressum</h2>
        <h3>Angaben gemäß § 5 TMG</h3>
        <p><strong>T. Nellessen</strong><br>Johnens Gäßchen 12<br>52146 Würselen<br>Deutschland</p>
        <h3>Kontakt</h3>
        <p>E-Mail: <a href="mailto:info@nellessen.de">info@nellessen.de</a></p>
        <h3>Verantwortlich nach § 55 Abs. 2 RStV</h3>
        <p>T. Nellessen, Johnens Gäßchen 12, 52146 Würselen</p>
        <h3>Hinweis</h3>
        <p>Dies ist eine private, nicht-kommerzielle Familienwebsite. Es werden keine gewerblichen Angebote gemacht.</p>
      </div>
    </div>

    <div class="overlay" id="o-datenschutz" role="dialog" aria-modal="true">
      <div class="modal">
        <button class="modal-close" onclick="closeM('datenschutz')" aria-label="Schließen">×</button>
        <h2>Datenschutzerklärung</h2>
        <h3>1. Verantwortlicher</h3>
        <p><strong>T. Nellessen</strong>, Johnens Gäßchen 12, 52146 Würselen, <a href="mailto:info@nellessen.de">info@nellessen.de</a></p>
        <h3>2. Server-Logfiles</h3>
        <p>Beim Aufruf dieser Website speichert der Webserver vorübergehend die IP-Adresse, den Zeitpunkt, die aufgerufene Adresse sowie Browser und Betriebssystem. Das dient dem sicheren und stabilen Betrieb. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO.</p>
        <h3>3. Kontaktformular</h3>
        <p>Name, E-Mail-Adresse und Nachricht aus dem Kontaktformular werden per E-Mail an info@nellessen.de übermittelt und nach Bearbeitung der Anfrage gelöscht. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO.</p>
        <h3>4. Geschützter Familienbereich</h3>
        <p>Der Familienbereich ist nur für eingeladene Personen zugänglich. Für das Konto speichern wir Name, Benutzername, E-Mail-Adresse und das Passwort ausschließlich als Hash. Mitglieder können dort Fotos, Rezepte, Erinnerungen, Termine und Stammbaumdaten (auch zu Verstorbenen und lebenden Verwandten) einstellen. Diese Inhalte sind nur für angemeldete Familienmitglieder sichtbar und werden nicht an Dritte weitergegeben. Wer Inhalte einstellt, versichert, dass er dazu berechtigt ist und dass abgebildete oder genannte Personen einverstanden sind. Rechtsgrundlagen sind Art. 6 Abs. 1 lit. b und f sowie lit. a DSGVO.</p>
        <p>Auf Wunsch senden wir E-Mail-Erinnerungen an Geburtstage und Familientermine. Die Erinnerungen lassen sich im Profil jederzeit abschalten. Inhalte und Konten werden auf Anfrage gelöscht.</p>
        <h3>5. Cookies</h3>
        <p>Im Familienbereich wird ein technisch notwendiges Sitzungs-Cookie gesetzt, das nach dem Abmelden oder spätestens nach acht Stunden ungültig wird. Es gibt kein Tracking und keine Analysewerkzeuge.</p>
        <h3>6. Schriftarten und Bilder von Drittanbietern</h3>
        <p>Die Seite lädt Schriftarten von Google Fonts (Google LLC, USA), die 3D-Grafik der Startseite über das Cloudflare-CDN und Fotos von Unsplash. Dabei wird Ihre IP-Adresse an diese Anbieter übermittelt. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO. Weitere Informationen: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Google</a>, <a href="https://www.cloudflare.com/privacypolicy/" target="_blank" rel="noopener">Cloudflare</a>, <a href="https://unsplash.com/privacy" target="_blank" rel="noopener">Unsplash</a>.</p>
        <h3>7. Hosting</h3>
        <p>Die Website wird bei IONOS SE, Elgendorfer Str. 57, 56410 Montabaur gehostet. IONOS verarbeitet die Daten als Auftragsverarbeiter nach Art. 28 DSGVO.</p>
        <h3>8. Ihre Rechte</h3>
        <p>Sie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch (Art. 15 bis 21 DSGVO). Beschwerden richten Sie an die Landesbeauftragte für Datenschutz und Informationsfreiheit NRW.</p>
        <p style="margin-top:1rem;font-size:.8rem">Stand: <?= de_month_year() ?></p>
      </div>
    </div>

    <div class="overlay" id="o-disclaimer" role="dialog" aria-modal="true">
      <div class="modal">
        <button class="modal-close" onclick="closeM('disclaimer')" aria-label="Schließen">×</button>
        <h2>Haftungsausschluss</h2>
        <h3>Haftung für Inhalte</h3>
        <p>Die Inhalte wurden mit Sorgfalt erstellt. Für Richtigkeit und Vollständigkeit kann keine Gewähr übernommen werden (§ 7 Abs. 1 TMG). Bei Kenntnis von Rechtsverletzungen werden Inhalte umgehend entfernt.</p>
        <h3>Haftung für Links</h3>
        <p>Für externe Links übernehmen wir keine Haftung. Zum Zeitpunkt der Verlinkung waren keine rechtswidrigen Inhalte erkennbar.</p>
        <h3>Urheberrecht</h3>
        <p>Eigene Inhalte unterliegen dem deutschen Urheberrecht. Eine Vervielfältigung bedarf der schriftlichen Zustimmung. Inhalte von Familienmitgliedern bleiben deren Eigentum.</p>
      </div>
    </div>

    <div class="overlay" id="o-kontakt" role="dialog" aria-modal="true">
      <div class="modal">
        <button class="modal-close" onclick="closeM('kontakt')" aria-label="Schließen">×</button>
        <h2>Kontakt</h2>
        <h3>Schreib uns</h3>
        <p>Ob Familie, alter Bekannter oder Neugieriger, wir freuen uns über jede Nachricht.</p>
        <form id="contactForm" novalidate style="margin-top:1.5rem">
          <div class="form-group"><label>Name</label><input type="text" name="name" placeholder="Dein Name" required></div>
          <div class="form-group"><label>E-Mail</label><input type="email" name="email" placeholder="deine@email.de" required></div>
          <div class="form-group"><label>Nachricht</label><textarea name="message" rows="5" placeholder="Was liegt dir auf dem Herzen?" required></textarea></div>
          <p style="font-size:.75rem;color:var(--muted);margin-bottom:.75rem">Mit dem Absenden stimmst du unserer <button type="button" onclick="closeM('kontakt');openM('datenschutz')" style="font-size:.75rem;color:var(--sky);text-decoration:underline;background:none;border:none;cursor:pointer;padding:0">Datenschutzerklärung</button> zu.</p>
          <button type="submit" class="btn btn-lime" style="width:100%;justify-content:center">Nachricht absenden</button>
          <div id="formStatus" style="margin-top:1rem;display:none"></div>
        </form>
      </div>
    </div>

    <script>
    var SITE_BASE = <?= json_encode($base) ?>;
    function openM(id){document.getElementById('o-'+id).classList.add('open');document.body.style.overflow='hidden'}
    function closeM(id){document.getElementById('o-'+id).classList.remove('open');document.body.style.overflow=''}
    document.querySelectorAll('.overlay').forEach(function(el){
      el.addEventListener('mousedown',function(e){if(e.target===el&&el.id.indexOf('o-')===0&&!el.hasAttribute('data-sticky')){el.classList.remove('open');document.body.style.overflow=''}})
    });
    document.addEventListener('keydown',function(e){
      if(e.key==='Escape')document.querySelectorAll('.overlay.open').forEach(function(el){el.classList.remove('open');document.body.style.overflow=''})
    });
    (function(){
      var cf=document.getElementById('contactForm'); if(!cf)return;
      cf.addEventListener('submit',function(e){
        e.preventDefault();
        var btn=cf.querySelector('button[type=submit]'),st=document.getElementById('formStatus');
        btn.disabled=true;btn.textContent='Wird gesendet …';st.style.display='none';
        fetch(SITE_BASE+'send.php',{method:'POST',body:new FormData(cf)})
          .then(function(r){return r.json()})
          .then(function(d){
            st.style.display='block';st.className=d.success?'alert alert-ok':'alert alert-err';
            st.textContent=d.success?'Nachricht gesendet. Wir melden uns!':(d.error||'Fehler beim Senden.');
            if(d.success)cf.reset();
          })
          .catch(function(){st.style.display='block';st.className='alert alert-err';st.textContent='Fehler beim Senden. Bitte schreib direkt an info@nellessen.de.'})
          .then(function(){btn.disabled=false;btn.textContent='Nachricht absenden'});
      });
    })();
    (function(){
      var io=new IntersectionObserver(function(entries){entries.forEach(function(e,i){if(e.isIntersecting){setTimeout(function(){e.target.classList.add('on')},i*80);io.unobserve(e.target)}})},{threshold:.1});
      document.querySelectorAll('.reveal').forEach(function(el){io.observe(el)});
    })();
    </script>
    <?= $extra ?>
    </body></html>
    <?php
}
