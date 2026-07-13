<style>
:root {
  --bg:      #0b0e0c;
  --surface: #121612;
  --surface2:#1a201b;
  --border:  rgba(255,255,255,0.07);
  --lime:    #c2f56a;
  --sky:     #38bdf8;
  --text:    #e8ecf0;
  --muted:   #4a5568;
  --ff:      'Space Grotesk', sans-serif;
  --ffi:     'Inter', sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--ff);background:var(--bg);color:var(--text);-webkit-font-smoothing:antialiased;overflow-x:hidden}
a{color:inherit;text-decoration:none}
img{display:block;max-width:100%}
button,input,textarea,select{font-family:inherit}
button{cursor:pointer;border:none;background:none;color:inherit}

/* Nav */
.nav{position:fixed;top:0;left:0;right:0;z-index:90;display:flex;align-items:center;justify-content:space-between;padding:0 2.5rem;height:60px;transition:background .3s,border-color .3s;border-bottom:1px solid transparent}
.nav.solid{background:rgba(11,14,12,.95);backdrop-filter:blur(12px);border-color:var(--border)}
.nav-brand{font-size:.75rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--white,#fff)}
.nav-links{display:flex;gap:2rem;list-style:none}
.nav-links a,.nav-links button{font-size:.68rem;font-weight:500;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.4);transition:color .2s}
.nav-links a:hover,.nav-links button:hover{color:var(--lime)}
.nav-links .active{color:var(--lime)}

/* Sections */
.section{padding:7rem 2.5rem}
.section--mid{background:var(--surface)}
.container{max-width:1100px;margin:0 auto}
.container--sm{max-width:760px;margin:0 auto}
hr.div{border:none;border-top:1px solid var(--border);margin:0}

.eyebrow{display:inline-flex;align-items:center;gap:.7rem;font-size:.67rem;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--lime);margin-bottom:2rem}
.eyebrow::after{content:'';display:block;width:28px;height:1px;background:var(--lime)}
.h2{font-size:clamp(2.2rem,5vw,4rem);font-weight:700;letter-spacing:-.025em;line-height:1;color:#fff;margin-bottom:2rem}
.h2 em{color:var(--lime);font-style:normal}
.body-text{font-family:var(--ffi);font-size:1rem;font-weight:300;line-height:1.85;color:rgba(255,255,255,.5)}
.body-text p+p{margin-top:1rem}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:.7rem;padding:.9rem 2rem;font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;transition:all .2s;cursor:pointer}
.btn-lime{background:var(--lime);color:#0b0e0c;border:1px solid var(--lime)}
.btn-lime:hover{opacity:.85}
.btn-outline{background:transparent;color:var(--lime);border:1px solid rgba(194,245,106,.35)}
.btn-outline:hover{border-color:var(--lime);background:rgba(194,245,106,.06)}

/* Forms */
.form-group{margin-bottom:1.25rem}
.form-group label{display:block;font-size:.67rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:.5rem}
.form-group input,.form-group textarea,.form-group select{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);padding:.85rem 1rem;font-size:.95rem;color:var(--text);outline:none;transition:border-color .2s}
.form-group input:focus,.form-group textarea:focus{border-color:rgba(194,245,106,.4)}
.form-group input::placeholder,.form-group textarea::placeholder{color:var(--muted)}

/* Modals */
.overlay{display:none;position:fixed;inset:0;z-index:200;background:rgba(4,6,10,.9);backdrop-filter:blur(8px);align-items:flex-start;justify-content:center;padding:4rem 1.5rem 2rem;overflow-y:auto}
.overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-top:2px solid var(--lime);width:100%;max-width:680px;padding:3rem;position:relative;animation:modalIn .3s ease}
@keyframes modalIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
.modal-close{position:absolute;top:1rem;right:1rem;width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--muted);transition:color .2s}
.modal-close:hover{color:var(--text)}
.modal h2{font-size:1.9rem;font-weight:700;letter-spacing:-.02em;color:#fff;margin-bottom:2rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border)}
.modal h3{font-size:.67rem;font-weight:600;letter-spacing:.17em;text-transform:uppercase;color:var(--lime);margin:2rem 0 .75rem}
.modal p,.modal li{font-family:var(--ffi);font-size:.9rem;font-weight:300;color:rgba(255,255,255,.5);line-height:1.8;margin-bottom:.5rem}
.modal ul{padding-left:1.2rem;margin-bottom:.5rem}
.modal a{color:var(--sky)}
.modal strong{color:rgba(255,255,255,.75);font-weight:500}

/* Reveal */
.reveal{opacity:0;transform:translateY(28px);transition:opacity .7s,transform .7s}
.reveal.on{opacity:1;transform:none}

/* Alerts */
.alert{padding:.85rem 1rem;font-family:var(--ffi);font-size:.9rem;margin-bottom:1rem}
.alert-ok{background:rgba(194,245,106,.08);border:1px solid rgba(194,245,106,.3);color:var(--lime)}
.alert-err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.3);color:#f87171}
.alert-info{background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.3);color:var(--sky)}

/* Footer */
footer.site-footer{background:#080a08;border-top:1px solid var(--border);padding:2.5rem}
.footer-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
.footer-brand{font-size:.72rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.2)}
.footer-nav{display:flex;gap:1.75rem;flex-wrap:wrap}
.footer-nav button,.footer-nav a{font-size:.67rem;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.25);transition:color .2s}
.footer-nav button:hover,.footer-nav a:hover{color:var(--lime)}
.footer-copy{font-size:.67rem;color:rgba(255,255,255,.15);letter-spacing:.06em}

/* Familie area */
.fam-nav{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:2rem}
.fam-nav a{padding:.5rem 1rem;font-size:.7rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.4);border:1px solid var(--border);transition:all .2s}
.fam-nav a:hover,.fam-nav a.active{color:var(--lime);border-color:rgba(194,245,106,.3)}

.card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1px;background:var(--border)}
.card{background:var(--surface);padding:2rem;transition:background .2s}
.card:hover{background:var(--surface2)}
.card-icon{font-size:1.75rem;margin-bottom:1rem}
.card-title{font-size:1rem;font-weight:700;color:#fff;margin-bottom:.5rem}
.card-desc{font-family:var(--ffi);font-size:.85rem;font-weight:300;color:rgba(255,255,255,.4);line-height:1.7}
.card-meta{margin-top:1rem;font-size:.68rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--lime)}

/* Table */
.tbl{width:100%;border-collapse:collapse}
.tbl th{font-size:.67rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);padding:.75rem 1rem;border-bottom:1px solid var(--border);text-align:left}
.tbl td{font-family:var(--ffi);font-size:.9rem;padding:.85rem 1rem;border-bottom:1px solid rgba(255,255,255,.04);color:rgba(255,255,255,.6)}
.tbl tr:hover td{background:var(--surface2)}
.badge{display:inline-block;padding:.2rem .6rem;font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
.badge-lime{background:rgba(194,245,106,.15);color:var(--lime)}
.badge-sky{background:rgba(56,189,248,.15);color:var(--sky)}
.badge-red{background:rgba(239,68,68,.15);color:#f87171}

@media(max-width:768px){
  .nav{padding:0 1.25rem}
  .section{padding:5rem 1.5rem}
  .nav-links{gap:1rem}
  footer.site-footer{padding:2rem 1.5rem}
  .footer-inner{flex-direction:column;gap:.75rem}
}
@keyframes rise{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:none}}
</style>
