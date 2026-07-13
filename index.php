<?php
require __DIR__ . '/includes/common.php';
page_head('Familie Nellessen · Würselen');
site_nav();
?>

<!-- ════ HERO ════ -->
<header class="hero" style="position:relative;height:100svh;min-height:600px;display:flex;flex-direction:column;justify-content:flex-end;overflow:hidden;background:#04060a">
  <canvas id="bg" style="position:absolute;inset:0;width:100%;height:100%" aria-hidden="true"></canvas>
  <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(4,6,10,0) 20%,rgba(4,6,10,.6) 60%,rgba(4,6,10,.97) 100%)"></div>
  <div style="position:relative;z-index:2;padding:0 2.5rem 4rem;max-width:1100px">
    <p style="display:inline-flex;align-items:center;gap:.8rem;font-size:.68rem;font-weight:500;letter-spacing:.2em;text-transform:uppercase;color:var(--lime);margin-bottom:1.8rem;opacity:0;animation:rise .7s .3s forwards">
      <span style="display:block;width:28px;height:1px;background:var(--lime)"></span>
      Würselen · Aachen · NRW
      <span style="display:block;width:28px;height:1px;background:var(--lime)"></span>
    </p>
    <h1 style="font-size:clamp(4.5rem,13vw,12rem);font-weight:700;line-height:.9;letter-spacing:-.035em;color:#fff;opacity:0;animation:rise .9s .5s forwards">
      Fami<span style="color:var(--lime)">lie</span><br>Nelles<span style="color:var(--lime)">sen.</span>
    </h1>
    <p style="margin-top:2rem;font-family:var(--ffi);font-size:clamp(.9rem,1.5vw,1.1rem);font-weight:300;color:rgba(255,255,255,.4);max-width:460px;line-height:1.7;opacity:0;animation:rise .8s .75s forwards">
      Verwurzelt im Wurmtal. Zuhause zwischen Aachen und Herzogenrath. Eine Familie — eine Geschichte.
    </p>
    <div style="display:flex;gap:1rem;margin-top:2.5rem;flex-wrap:wrap;opacity:0;animation:rise .8s 1s forwards">
      <a href="#wuerselen" class="btn btn-lime">Mehr entdecken</a>
      <a href="familie/login.php" class="btn btn-outline">Familien-Bereich</a>
    </div>
  </div>
  <div style="position:absolute;bottom:2rem;right:2.5rem;z-index:3;display:flex;flex-direction:column;align-items:center;gap:.5rem;opacity:0;animation:rise .7s 1.3s forwards" aria-hidden="true">
    <div style="width:1px;height:50px;background:linear-gradient(to bottom,var(--lime),transparent)"></div>
    <span style="font-size:.6rem;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.3)">Scroll</span>
  </div>
</header>

<hr class="div">

<!-- ════ WÜRSELEN ════ -->
<section class="section" id="wuerselen">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:5rem;align-items:start">
      <div>
        <p class="eyebrow reveal">No. 01 — Unsere Stadt</p>
        <h2 class="h2 reveal">Würselen.<br><em>Mehr als</em><br>eine Adresse.</h2>
        <div class="body-text reveal">
          <p>Würselen liegt da, wo das Wurmtal auf die Stadtgrenzen Aachens trifft — eine Stadt mit eigenem Charakter, grünen Auen und einer Geschichte, die tief in die Bergbauära reicht.</p>
          <p>Keine Tourismusbroschüre, keine Werbung: einfach unsere Heimat. Laut genug für alles Wichtige, leise genug, um sich noch zu kennen.</p>
          <p>Für die Familie Nellessen ist Würselen seit Generationen nicht nur eine Postadresse — es ist ein Gefühl.</p>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr;gap:1px;background:var(--border)" class="reveal">
        <?php $stats = [
          ['37k','Einwohner','Groß genug für alles Wichtige, klein genug, dass man sich begegnet.'],
          ['~7km','bis Aachener Dom','Weltkulturerbe vor der Haustür — die Kaiserstadt beginnt quasi am Stadtrand.'],
          ['Wurm','Unser Fluss','Das Wurmtal durchzieht die Stadt und bringt Grün mitten ins Stadtleben.'],
          ['1969','Eingemeindung','Erst da wurde Würselen offiziell Teil des Kreises Aachen — Eigenständigkeit bis zuletzt.'],
        ]; foreach($stats as $s): ?>
        <div style="background:var(--surface);padding:2rem;transition:background .2s" onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background='var(--surface)'">
          <div style="font-size:2.5rem;font-weight:700;letter-spacing:-.03em;color:#fff;line-height:1"><?= $s[0] ?></div>
          <div style="font-size:.72rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--lime);margin:.4rem 0"><?= $s[1] ?></div>
          <div style="font-family:var(--ffi);font-size:.85rem;color:rgba(255,255,255,.35);line-height:1.6"><?= $s[2] ?></div>
        </div>
        <?php endforeach ?>
      </div>
    </div>
  </div>
</section>

<hr class="div">

<!-- ════ PHOTO FULL — WURMTAL ════ -->
<div style="position:relative;height:60vh;min-height:350px;overflow:hidden;background:#0d1a0d">
  <img src="https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=1920&q=80"
       alt="Das Wurmtal" style="width:100%;height:100%;object-fit:cover;opacity:.45;filter:saturate(.7)">
  <div style="position:absolute;inset:0;background:linear-gradient(120deg,rgba(4,6,10,.92) 0%,rgba(4,6,10,.4) 100%)"></div>
  <div style="position:absolute;top:50%;left:2.5rem;transform:translateY(-50%);max-width:520px">
    <p class="eyebrow reveal" style="margin-bottom:1.5rem">Das Wurmtal</p>
    <p style="font-size:clamp(1.4rem,3vw,2.2rem);font-weight:700;letter-spacing:-.02em;color:#fff;line-height:1.2" class="reveal">
      „Zwischen Kohle, Grün<br>und Kaiserstadt —<br><span style="color:var(--lime)">das ist unser Würselen.</span>"
    </p>
  </div>
</div>

<hr class="div">

<!-- ════ DER NAME ════ -->
<section class="section section--mid" id="name">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:5rem;align-items:center">
      <div style="border-left:2px solid var(--lime);padding-left:2.5rem" class="reveal">
        <p style="font-family:var(--ffi);font-size:.8rem;color:var(--muted);margin-bottom:.5rem">Etymologie</p>
        <div style="font-size:clamp(3rem,7vw,6rem);font-weight:700;letter-spacing:-.04em;color:#fff;line-height:.9">
          Nel<span style="color:var(--lime)">lis</span>
        </div>
        <p style="font-family:var(--ffi);font-size:.85rem;color:rgba(255,255,255,.35);margin-top:.75rem">Diminutiv von Cornelius · Mittellatein</p>
        <div style="margin-top:2rem;display:flex;flex-direction:column;gap:.5rem">
          <?php $trail = ['Cornelius','Cornelis','Nellis','Nellissen','Nellessen']; foreach($trail as $i=>$t): ?>
          <div style="display:flex;align-items:center;gap:.75rem">
            <span style="width:20px;height:1px;background:<?=$i===count($trail)-1?'var(--lime)':'rgba(255,255,255,.15)'?>"></span>
            <span style="font-size:<?=$i===count($trail)-1?'1.1rem':'.85rem'?>;font-weight:<?=$i===count($trail)-1?700:400?>;color:<?=$i===count($trail)-1?'var(--lime)':'rgba(255,255,255,.35)'?>"><?= $t ?></span>
          </div>
          <?php endforeach ?>
        </div>
      </div>
      <div>
        <p class="eyebrow reveal">No. 02 — Der Name</p>
        <h2 class="h2 reveal">Ein Name,<br><em>eine Region,</em><br>eine Geschichte.</h2>
        <div class="body-text reveal">
          <p>Nellessen ist ein typisch aachenburgischer Familienname. Er leitet sich vom mittelalterlichen Rufnamen <em style="color:rgba(255,255,255,.7)">Nellis</em> ab — einem Diminutiv des lateinischen <em style="color:rgba(255,255,255,.7)">Cornelius</em> — und verbreitete sich ab dem 15. Jahrhundert vor allem im Dreiländereck zwischen Aachen, Lüttich und Maastricht.</p>
          <p>Die Patronymbildung mit dem Suffix <em style="color:rgba(255,255,255,.7)">-sen</em> ist typisch für den niederrheinisch-maasländischen Sprachraum und bis heute im Aachener Land häufiger anzutreffen als anderswo in Deutschland.</p>
          <p>Kurz: wer Nellessen heißt, kommt (fast) immer von hier.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<hr class="div">

<!-- ════ AACHEN ════ -->
<section class="section" id="aachen">
  <div class="container">
    <p class="eyebrow reveal">No. 03 — Nebenan</p>
    <h2 class="h2 reveal" style="max-width:600px">Aachen.<br><em>Sieben Kilometer</em> Weltgeschichte.</h2>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);margin-top:3rem" class="reveal">
      <?php $cards = [
        ['🏛️','Aachener Dom','UNESCO-Welterbe seit 1978. Karl der Große ließ ihn erbauen — noch heute ist er das Herz der Stadt.'],
        ['🎓','RWTH Aachen','Eine der führenden Technischen Universitäten Europas. Bringt 45.000 Studierende und globale Forschung in die Region.'],
        ['🌍','Dreiländereck','Deutschland, Belgien, Niederlande — drei Länder in einer Stunde. Internationalität ist hier keine Leistung, sondern Normalzustand.'],
      ]; foreach($cards as $c): ?>
      <div class="card">
        <div class="card-icon"><?= $c[0] ?></div>
        <div class="card-title"><?= $c[1] ?></div>
        <div class="card-desc"><?= $c[2] ?></div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
</section>

<hr class="div">

<!-- ════ BLOG ════ -->
<section class="section section--mid" id="blog">
  <div class="container">
    <p class="eyebrow reveal">No. 04 — Notizen</p>
    <h2 class="h2 reveal">Was uns<br><em>gerade</em> bewegt.</h2>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);margin-top:3rem">
      <?php $posts = [
        ['Herbst im Wurmtal','Wenn die Blätter fallen und der Nebel über den Wiesen liegt, ist das Wurmtal am schönsten. Eine Beobachtung.',  'Natur', 'Oktober 2025'],
        ['Der Name im Mittelalter','Wie Cornelius zu Nellis wurde — und warum Würselen dabei eine besondere Rolle spielte.', 'Geschichte', 'September 2025'],
        ['Aachen aus Würselener Sicht','Sieben Kilometer Distanz. Ein völlig anderer Blick auf eine Stadt, die man zu kennen glaubt.', 'Stadt', 'August 2025'],
      ]; foreach($posts as $p): ?>
      <div class="card" style="cursor:pointer">
        <div class="card-meta"><?= $p[2] ?> · <?= $p[3] ?></div>
        <div class="card-title" style="margin-top:.75rem;font-size:1.05rem;line-height:1.3"><?= $p[0] ?></div>
        <div class="card-desc" style="margin-top:.6rem"><?= $p[1] ?></div>
        <div style="margin-top:1.5rem;font-size:.7rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.25)">Platzhalter — Bald verfügbar →</div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
</section>

<hr class="div">

<!-- ════ FAMILIE TEASER ════ -->
<section class="section" style="background:#080c08">
  <div class="container" style="text-align:center">
    <p class="eyebrow reveal" style="justify-content:center">Für die Familie</p>
    <h2 class="h2 reveal" style="margin:0 auto 1.5rem">Der <em>geschützte</em><br>Familien-Bereich.</h2>
    <p class="body-text reveal" style="max-width:520px;margin:0 auto 2.5rem">Fotos, Erinnerungen, Geburtstage, Rezepte — alles was nur für die Familie ist, findest du hier. Mit deinem persönlichen Login.</p>
    <a href="familie/login.php" class="btn btn-lime reveal" style="display:inline-flex">Zum Familien-Bereich →</a>
  </div>
</section>

<?php site_footer(); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
(function(){
  const canvas=document.getElementById('bg');
  if(!canvas||!window.THREE)return;
  const renderer=new THREE.WebGLRenderer({canvas,antialias:true,alpha:true});
  renderer.setPixelRatio(Math.min(devicePixelRatio,2));
  renderer.setClearColor(0x04060a,1);
  const scene=new THREE.Scene();
  const camera=new THREE.PerspectiveCamera(60,1,.1,100);
  camera.position.z=14;
  function resize(){const W=canvas.parentElement.offsetWidth,H=canvas.parentElement.offsetHeight;renderer.setSize(W,H);camera.aspect=W/H;camera.updateProjectionMatrix()}
  resize(); window.addEventListener('resize',resize);

  const ico=new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.IcosahedronGeometry(5,2)),new THREE.LineBasicMaterial({color:0xc2f56a,transparent:true,opacity:.12}));
  const ico2=new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.IcosahedronGeometry(2.8,1)),new THREE.LineBasicMaterial({color:0x38bdf8,transparent:true,opacity:.1}));
  const torus=new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.TorusKnotGeometry(3.2,.06,120,8,2,3)),new THREE.LineBasicMaterial({color:0xc2f56a,transparent:true,opacity:.05}));
  scene.add(ico,ico2,torus);

  const N=2500,pos=new Float32Array(N*3),col=new Float32Array(N*3);
  for(let i=0;i<N;i++){const r=8+Math.random()*28,t=Math.random()*Math.PI*2,p=Math.acos(2*Math.random()-1);pos[i*3]=r*Math.sin(p)*Math.cos(t);pos[i*3+1]=r*Math.sin(p)*Math.sin(t);pos[i*3+2]=r*Math.cos(p);if(Math.random()>.45){col[i*3]=.76;col[i*3+1]=.96;col[i*3+2]=.42}else{col[i*3]=.22;col[i*3+1]=.74;col[i*3+2]=.98}}
  const pg=new THREE.BufferGeometry();pg.setAttribute('position',new THREE.BufferAttribute(pos,3));pg.setAttribute('color',new THREE.BufferAttribute(col,3));
  const pts=new THREE.Points(pg,new THREE.PointsMaterial({size:.05,vertexColors:true,transparent:true,opacity:.7}));
  scene.add(pts);

  let mx=0,my=0,tx=0,ty=0;
  document.addEventListener('mousemove',e=>{mx=(e.clientX/window.innerWidth-.5)*2;my=(e.clientY/window.innerHeight-.5)*2},{passive:true});

  const clock=new THREE.Clock();
  (function animate(){requestAnimationFrame(animate);const t=clock.getElapsedTime();
    ico.rotation.x=t*.06+my*.08;ico.rotation.y=t*.09+mx*.08;
    ico2.rotation.x=-t*.13;ico2.rotation.y=-t*.08;
    torus.rotation.x=t*.04;torus.rotation.z=t*.05;
    pts.rotation.y=t*.015;
    tx+=(mx*2-tx)*.025;ty+=(-my*2-ty)*.025;
    camera.position.x=tx;camera.position.y=ty;camera.lookAt(scene.position);
    renderer.render(scene,camera);
  })();
})();
</script>
