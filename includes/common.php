<?php
function page_head(string $title = 'Familie Nellessen · Würselen', string $desc = 'Familie Nellessen aus Würselen — zwischen Wurmtal und Kaiserstadt.') {
    echo '<!DOCTYPE html><html lang="de"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<meta name="description" content="' . htmlspecialchars($desc) . '">';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">';
    // Style path relative
    $depth = substr_count($_SERVER['PHP_SELF'], '/', 1) - 1;
    $root  = str_repeat('../', $depth);
    echo '<link rel="stylesheet" href="' . $root . 'includes/style.css">';
    require __DIR__ . '/style.php';
    echo '</head><body>';
}

function site_nav(string $active = '') {
    $base = strpos($_SERVER['PHP_SELF'], '/familie/') !== false ? '../' : '';
    ?>
    <nav class="nav" id="mainNav">
      <a href="<?= $base ?>index.php" class="nav-brand">Nellessen</a>
      <ul class="nav-links">
        <li><a href="<?= $base ?>index.php#wuerselen" <?= $active==='wuerselen'?'class="active"':'' ?>>Würselen</a></li>
        <li><a href="<?= $base ?>index.php#name" <?= $active==='name'?'class="active"':'' ?>>Der Name</a></li>
        <li><a href="<?= $base ?>index.php#blog" <?= $active==='blog'?'class="active"':'' ?>>Blog</a></li>
        <li><a href="<?= $base ?>familie/<?= $active==='familie'?'index':'login' ?>.php" class="<?= $active==='familie'?'active':'' ?>">Familie</a></li>
        <li><button onclick="openM('kontakt')">Kontakt</button></li>
      </ul>
    </nav>
    <script>
    const nav=document.getElementById('mainNav');
    window.addEventListener('scroll',()=>nav.classList.toggle('solid',scrollY>50),{passive:true});
    </script>
    <?php
}

function site_footer() {
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

    <!-- IMPRESSUM -->
    <div class="overlay" id="o-impressum" role="dialog" aria-modal="true">
      <div class="modal">
        <button class="modal-close" onclick="closeM('impressum')">×</button>
        <h2>Impressum</h2>
        <h3>Angaben gemäß § 5 TMG</h3>
        <p><strong>T. Nellessen</strong><br>Johnens Gäßchen 12<br>52146 Würselen<br>Deutschland</p>
        <h3>Kontakt</h3>
        <p>E-Mail: <a href="mailto:info@nellessen.de">info@nellessen.de</a></p>
        <h3>Verantwortlich nach § 55 Abs. 2 RStV</h3>
        <p>T. Nellessen, Johnens Gäßchen 12, 52146 Würselen</p>
        <h3>Hinweis</h3>
        <p>Private, nicht-kommerzielle Familienwebsite. Keine gewerblichen Angebote.</p>
      </div>
    </div>

    <!-- DATENSCHUTZ -->
    <div class="overlay" id="o-datenschutz" role="dialog" aria-modal="true">
      <div class="modal">
        <button class="modal-close" onclick="closeM('datenschutz')">×</button>
        <h2>Datenschutzerklärung</h2>
        <h3>1. Verantwortlicher</h3>
        <p><strong>T. Nellessen</strong> · Johnens Gäßchen 12, 52146 Würselen · <a href="mailto:info@nellessen.de">info@nellessen.de</a></p>
        <h3>2. Server-Logfiles</h3>
        <p>Beim Seitenaufruf werden IP-Adresse, Zeitstempel, aufgerufene URL und Browser temporär im Serverlog gespeichert. Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO.</p>
        <h3>3. Familien-Login</h3>
        <p>Im geschützten Bereich werden Name und E-Mail-Adresse zur Authentifizierung gespeichert. Passwörter werden ausschließlich als Hash (bcrypt) gespeichert. Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO.</p>
        <h3>4. Kontaktformular</h3>
        <p>Daten aus dem Kontaktformular werden per SMTP an info@nellessen.de übertragen und nach Bearbeitung gelöscht. Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO.</p>
        <h3>5. Cookies &amp; Sessions</h3>
        <p>Im Familien-Bereich wird ein Session-Cookie gesetzt (technisch notwendig, kein Tracking). Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO.</p>
        <h3>6. Google Fonts &amp; Cloudflare CDN</h3>
        <p>Schriften von Google Fonts und Bibliotheken via Cloudflare CDN — dabei können IP-Adressen an Google LLC bzw. Cloudflare übermittelt werden.</p>
        <h3>7. Deine Rechte</h3>
        <p>Auskunft, Berichtigung, Löschung, Einschränkung, Widerspruch (Art. 15–21 DSGVO). Beschwerde bei der LDI NRW.</p>
        <p style="margin-top:1rem;font-size:.8rem">Stand: <?= date('F Y') ?></p>
      </div>
    </div>

    <!-- HAFTUNGSAUSSCHLUSS -->
    <div class="overlay" id="o-disclaimer" role="dialog" aria-modal="true">
      <div class="modal">
        <button class="modal-close" onclick="closeM('disclaimer')">×</button>
        <h2>Haftungsausschluss</h2>
        <h3>Haftung für Inhalte</h3>
        <p>Inhalte wurden sorgfältig erstellt. Für Richtigkeit und Vollständigkeit kann keine Gewähr übernommen werden (§ 7 Abs. 1 TMG). Bei Kenntnis von Rechtsverletzungen werden Inhalte umgehend entfernt.</p>
        <h3>Haftung für Links</h3>
        <p>Für externe Links übernehmen wir keine Haftung. Zum Zeitpunkt der Verlinkung waren keine rechtswidrigen Inhalte erkennbar.</p>
        <h3>Urheberrecht</h3>
        <p>Eigene Inhalte unterliegen dem deutschen Urheberrecht. Vervielfältigung bedarf der schriftlichen Zustimmung.</p>
      </div>
    </div>

    <!-- KONTAKT -->
    <div class="overlay" id="o-kontakt" role="dialog" aria-modal="true">
      <div class="modal">
        <button class="modal-close" onclick="closeM('kontakt')">×</button>
        <h2>Kontakt</h2>
        <h3>Schreib uns</h3>
        <p>Ob Familie, alter Bekannter oder Neugieriger — wir freuen uns über jede Nachricht.</p>
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
    function openM(id){document.getElementById('o-'+id).classList.add('open');document.body.style.overflow='hidden'}
    function closeM(id){document.getElementById('o-'+id).classList.remove('open');document.body.style.overflow=''}
    document.querySelectorAll('.overlay').forEach(el=>{
      el.addEventListener('click',e=>{if(e.target===el){el.classList.remove('open');document.body.style.overflow=''}})
    });
    document.addEventListener('keydown',e=>{
      if(e.key==='Escape')document.querySelectorAll('.overlay.open').forEach(el=>{el.classList.remove('open');document.body.style.overflow=''})
    });

    // Contact form
    const cf = document.getElementById('contactForm');
    if(cf) cf.addEventListener('submit', async function(e){
      e.preventDefault();
      const btn=this.querySelector('button[type=submit]'),st=document.getElementById('formStatus');
      btn.disabled=true; btn.textContent='Wird gesendet…';
      st.style.display='none';
      try{
        const depth = window.location.pathname.split('/').filter(Boolean).length - 1;
        const base  = depth > 0 ? '../'.repeat(depth) : '';
        const r=await fetch(base+'send.php',{method:'POST',body:new FormData(this)});
        const d=await r.json();
        st.style.display='block';
        st.className = d.success ? 'alert alert-ok' : 'alert alert-err';
        st.textContent = d.success ? '✓ Nachricht gesendet. Wir melden uns!' : '✗ ' + (d.error||'Fehler.');
        if(d.success) this.reset();
      }catch(err){
        st.style.display='block'; st.className='alert alert-err';
        st.textContent='✗ Fehler. Bitte direkt an info@nellessen.de schreiben.';
      }
      btn.disabled=false; btn.textContent='Nachricht absenden';
    });

    // Scroll reveal
    const io=new IntersectionObserver(entries=>{
      entries.forEach((e,i)=>{if(e.isIntersecting){setTimeout(()=>e.target.classList.add('on'),i*80);io.unobserve(e.target)}})
    },{threshold:.1});
    document.querySelectorAll('.reveal').forEach(el=>io.observe(el));
    </script>
    </body></html>
    <?php
}
