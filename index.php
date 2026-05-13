<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WarungKu — Aplikasi Catat Pesanan Cafetaria Kampus | Cepat & Akurat</title>
  <meta name="description" content="WarungKu adalah aplikasi pencatatan pesanan cafetaria kampus yang cepat dan akurat. Catat pesanan mahasiswa hanya dalam hitungan detik langsung dari handphone.">
  <meta name="keywords" content="aplikasi kasir cafetaria, catat pesanan kampus, aplikasi warung, sistem pemesanan makanan, kasir digital, POS cafetaria, aplikasi kantin kampus">
  <meta name="author" content="WarungKu">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="https://catat-pesan.web-portofolio.com/">
  <link rel="icon" type="image/png" href="logo.png">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="WarungKu — Aplikasi Catat Pesanan Cafetaria Kampus">
  <meta property="og:description" content="Catat pesanan mahasiswa hanya dalam hitungan detik. Cepat, akurat, dan mudah digunakan langsung dari handphone.">
  <meta property="og:url" content="https://catat-pesan.web-portofolio.com/">
  <meta property="og:image" content="https://catat-pesan.web-portofolio.com/logo.png">
  <meta property="og:site_name" content="WarungKu">
  <meta property="og:locale" content="id_ID">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="WarungKu — Aplikasi Catat Pesanan Cafetaria Kampus">
  <meta name="twitter:description" content="Catat pesanan mahasiswa hanya dalam hitungan detik. Cepat, akurat, dan mudah digunakan langsung dari handphone.">
  <meta name="twitter:image" content="https://catat-pesan.web-portofolio.com/logo.png">

  <!-- JSON-LD Structured Data -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "WarungKu",
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "description": "Aplikasi pencatatan pesanan cafetaria kampus yang cepat dan akurat. Didesain khusus untuk penjual di kantin kampus.",
    "url": "https://catat-pesan.web-portofolio.com/",
    "image": "https://catat-pesan.web-portofolio.com/logo.png",
    "offers": {
      "@type": "Offer",
      "price": "0",
      "priceCurrency": "IDR"
    },
    "author": {
      "@type": "Organization",
      "name": "WarungKu"
    }
  }
  </script>

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script>
    const savedTheme = localStorage.getItem('warungku_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
  </script>
  <style>
    :root{
      --bg:#0f0e0b;--surface:#1a1814;--surface2:#231f1a;--surface3:#2d2820;
      --gold:#d4a853;--gold-light:#e8c47a;--gold-dim:rgba(212,168,83,.12);
      --cream:#f5edd8;--cream-dim:rgba(245,237,216,.6);
      --green:#4caf7d;--text:#f0e8d5;--text-dim:#8a7f6e;
      --border:rgba(212,168,83,.15);--radius:16px;
    }

    [data-theme="light"] {
      --bg: #fdfaf6;
      --surface: #ffffff;
      --surface2: #f4efeb;
      --surface3: #e8e1d7;
      --gold: #b38222;
      --gold-light: #c2902f;
      --gold-dim: rgba(179, 130, 34, 0.15);
      --cream: #1a1814;
      --cream-dim: rgba(26, 24, 20, 0.7);
      --red: #d33c3c;
      --green: #2c8558;
      --text: #3c3730;
      --text-dim: #7a7265;
      --border: rgba(179, 130, 34, 0.25);
    }
    *{margin:0;padding:0;box-sizing:border-box;}
    html{scroll-behavior:smooth;}
    body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;overflow-x:hidden;}

    /* ── NAV ── */
    .nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;transition:background .3s,box-shadow .3s;}
    .nav.scrolled{background:var(--surface);backdrop-filter:blur(18px);box-shadow:0 2px 20px rgba(0,0,0,.1);border-bottom:1px solid var(--border);}
    [data-theme="dark"] .nav.scrolled{background:rgba(15,14,11,.95);box-shadow:0 2px 20px rgba(0,0,0,.4);}
    .brand{font-family:'Playfair Display',serif;font-size:22px;font-weight:900;color:var(--gold);text-decoration:none;}
    .brand b{color:var(--cream);}
    .nav-links{display:flex;gap:24px;align-items:center;}
    .nav-links a{color:var(--text-dim);text-decoration:none;font-size:13px;font-weight:500;transition:color .2s;}
    .nav-links a:hover{color:var(--gold);}
    .nav-cta{background:var(--gold)!important;color:var(--bg)!important;padding:8px 20px;border-radius:100px;font-weight:700!important;font-size:13px!important;transition:transform .2s,box-shadow .2s;}
    .nav-cta:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(212,168,83,.4);}
    .theme-toggle{background:transparent;border:1px solid var(--border);border-radius:8px;color:var(--text-dim);cursor:pointer;padding:6px 10px;font-size:14px;transition:all 0.2s;display:flex;align-items:center;justify-content:center;}
    .theme-toggle:hover{border-color:var(--gold);color:var(--gold);}
    .hamburger{display:none;background:none;border:none;color:var(--gold);font-size:24px;cursor:pointer;}

    /* ── HERO ── */
    .hero{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:100px 24px 60px;position:relative;overflow:hidden;}
    .hero::before{content:'';position:absolute;top:-30%;left:50%;transform:translateX(-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(212,168,83,.08) 0%,transparent 70%);pointer-events:none;}
    .hero-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:var(--gold-dim);border:1px solid var(--border);border-radius:100px;font-size:12px;font-weight:600;color:var(--gold);margin-bottom:28px;animation:fadeUp .8s ease both;}
    .hero-badge .dot{width:6px;height:6px;background:var(--green);border-radius:50%;animation:pulse 2s infinite;}
    @keyframes pulse{0%,100%{opacity:1;}50%{opacity:.4;}}
    .hero h1{font-family:'Playfair Display',serif;font-size:clamp(36px,8vw,64px);font-weight:900;color:var(--cream);line-height:1.1;margin-bottom:20px;animation:fadeUp .8s .1s ease both;}
    .hero h1 em{font-style:normal;color:var(--gold);position:relative;}
    .hero h1 em::after{content:'';position:absolute;bottom:2px;left:0;right:0;height:3px;background:var(--gold);border-radius:2px;opacity:.4;}
    .hero-sub{font-size:clamp(15px,3.5vw,18px);color:var(--text-dim);max-width:520px;line-height:1.7;margin-bottom:36px;animation:fadeUp .8s .2s ease both;}
    .hero-actions{display:flex;gap:14px;flex-wrap:wrap;justify-content:center;animation:fadeUp .8s .3s ease both;}
    .btn-primary{display:inline-flex;align-items:center;gap:10px;padding:16px 36px;background:linear-gradient(135deg,var(--gold),var(--gold-light));color:var(--bg);border:none;border-radius:14px;font-family:'DM Sans',sans-serif;font-size:16px;font-weight:700;cursor:pointer;text-decoration:none;transition:transform .2s,box-shadow .2s;}
    .btn-primary:hover{transform:translateY(-3px);box-shadow:0 12px 40px rgba(212,168,83,.4);}
    .btn-secondary{display:inline-flex;align-items:center;gap:10px;padding:16px 36px;background:transparent;color:var(--cream);border:1.5px solid var(--border);border-radius:14px;font-family:'DM Sans',sans-serif;font-size:16px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;}
    .btn-secondary:hover{border-color:var(--gold);color:var(--gold);}
    .hero-stats{display:flex;gap:40px;margin-top:56px;animation:fadeUp .8s .4s ease both;}
    .stat{text-align:center;}
    .stat-num{font-family:'Playfair Display',serif;font-size:32px;font-weight:900;color:var(--gold);}
    .stat-label{font-size:12px;color:var(--text-dim);margin-top:4px;}

    @keyframes fadeUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}

    /* ── SECTION SHARED ── */
    section{padding:80px 24px;}
    .section-label{display:inline-flex;align-items:center;gap:8px;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin-bottom:14px;}
    .section-title{font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,40px);font-weight:700;color:var(--cream);margin-bottom:14px;line-height:1.2;}
    .section-desc{font-size:15px;color:var(--text-dim);max-width:560px;line-height:1.7;}
    .container{max-width:1100px;margin:0 auto;}

    /* ── FEATURES ── */
    .features{background:var(--surface);}
    .features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-top:48px;}
    .feat-card{background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:28px 24px;transition:transform .3s,border-color .3s,box-shadow .3s;opacity:0;transform:translateY(30px);}
    .feat-card.visible{opacity:1;transform:translateY(0);transition:opacity .6s ease,transform .6s ease;}
    .feat-card:hover{transform:translateY(-6px);border-color:var(--gold);box-shadow:0 20px 60px rgba(0,0,0,.4);}
    .feat-icon{width:52px;height:52px;border-radius:14px;background:var(--gold-dim);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:18px;}
    .feat-card h3{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:var(--cream);margin-bottom:8px;}
    .feat-card p{font-size:13px;color:var(--text-dim);line-height:1.6;}

    /* ── HOW IT WORKS ── */
    .how-it-works .steps{display:flex;gap:24px;margin-top:48px;flex-wrap:wrap;justify-content:center;}
    .step{flex:1;min-width:220px;max-width:320px;text-align:center;position:relative;padding:32px 20px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);opacity:0;transform:translateY(30px);}
    .step.visible{opacity:1;transform:translateY(0);transition:opacity .6s ease,transform .6s ease;}
    .step-num{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-light));color:var(--bg);font-weight:800;font-size:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;}
    .step h3{font-family:'Playfair Display',serif;font-size:17px;color:var(--cream);margin-bottom:8px;}
    .step p{font-size:13px;color:var(--text-dim);line-height:1.6;}
    .step-icon{font-size:36px;margin-bottom:14px;}

    /* ── TESTIMONIALS ── */
    .testimonials{background:var(--surface);}
    .testi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:48px;}
    .testi-card{background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius);padding:28px;opacity:0;transform:translateY(30px);}
    .testi-card.visible{opacity:1;transform:translateY(0);transition:opacity .6s ease,transform .6s ease;}
    .testi-stars{color:var(--gold);font-size:14px;letter-spacing:2px;margin-bottom:14px;}
    .testi-text{font-size:14px;color:var(--cream-dim);line-height:1.7;margin-bottom:18px;font-style:italic;}
    .testi-author{display:flex;align-items:center;gap:12px;}
    .testi-avatar{width:40px;height:40px;border-radius:50%;background:var(--gold-dim);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:18px;}
    .testi-name{font-size:13px;font-weight:600;color:var(--cream);}
    .testi-role{font-size:11px;color:var(--text-dim);}

    /* ── CTA FINAL ── */
    .cta-section{text-align:center;position:relative;overflow:hidden;}
    .cta-section::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:500px;height:500px;background:radial-gradient(circle,rgba(212,168,83,.06) 0%,transparent 70%);pointer-events:none;}
    .cta-section .btn-primary{font-size:18px;padding:18px 44px;margin-top:28px;}

    /* ── FOOTER ── */
    footer{padding:40px 24px;border-top:1px solid var(--border);text-align:center;}
    .footer-brand{font-family:'Playfair Display',serif;font-size:20px;font-weight:900;color:var(--gold);margin-bottom:8px;}
    .footer-brand b{color:var(--cream);}
    .footer-text{font-size:12px;color:var(--text-dim);line-height:1.8;}
    .footer-links{display:flex;justify-content:center;gap:20px;margin-top:16px;flex-wrap:wrap;}
    .footer-links a{font-size:12px;color:var(--text-dim);text-decoration:none;transition:color .2s;}
    .footer-links a:hover{color:var(--gold);}

    /* ── MOBILE NAV MENU ── */
    .mobile-menu{display:none;position:fixed;top:64px;left:0;right:0;background:var(--surface);backdrop-filter:blur(18px);border-bottom:1px solid var(--border);padding:20px 24px;flex-direction:column;gap:16px;z-index:99;}
    [data-theme="dark"] .mobile-menu{background:rgba(15,14,11,.98);}
    .mobile-menu.open{display:flex;}
    .mobile-menu a{color:var(--text-dim);text-decoration:none;font-size:15px;font-weight:500;padding:8px 0;border-bottom:1px solid var(--border);transition:color .2s;}
    .mobile-menu a:last-child{border-bottom:none;}
    .mobile-menu a:hover{color:var(--gold);}

    /* ── RESPONSIVE ── */
    @media(max-width:768px){
      .nav-links{display:none;}
      .hamburger{display:block;}
      .hero{padding:90px 20px 50px;}
      .hero-stats{gap:24px;}
      .stat-num{font-size:24px;}
      section{padding:60px 20px;}
      .features-grid{grid-template-columns:1fr;}
      .how-it-works .steps{flex-direction:column;align-items:center;}
      .step{max-width:100%;}
      .testi-grid{grid-template-columns:1fr;}
    }
  </style>
</head>
<body>

<!-- ═══ NAV ═══ -->
<nav class="nav" id="mainNav">
  <a href="#" class="brand">Warung<b>Ku</b></a>
  <div class="nav-links">
    <a href="#fitur">Fitur</a>
    <a href="#cara-kerja">Cara Kerja</a>
    <a href="#testimoni">Testimoni</a>
    <a href="#harga">Harga</a>
    <button class="theme-toggle" onclick="toggleTheme()" id="themeToggleBtn">☀️</button>
    <a href="app.php" class="nav-cta">Masuk Aplikasi →</a>
  </div>
  <button class="hamburger" onclick="toggleMenu()" aria-label="Menu navigasi">☰</button>
</nav>
<div class="mobile-menu" id="mobileMenu">
  <a href="#fitur" onclick="closeMenu()">Fitur</a>
  <a href="#cara-kerja" onclick="closeMenu()">Cara Kerja</a>
  <a href="#testimoni" onclick="closeMenu()">Testimoni</a>
  <a href="#harga" onclick="closeMenu()">Harga</a>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
    <span style="font-size:14px;color:var(--text-dim);font-weight:500;">Mode Tampilan</span>
    <button class="theme-toggle" onclick="toggleTheme()" id="themeToggleBtnMobile">☀️</button>
  </div>
  <a href="app.php">🚀 Masuk Aplikasi</a>
</div>

<!-- ═══ HERO ═══ -->
<header class="hero" id="hero">
  <h1>Catat Pesanan<br><em>Secepat Kilat</em></h1>
  <p class="hero-sub">Tidak perlu buku catatan lagi. WarungKu membantu penjual cafetaria kampus mencatat pesanan mahasiswa dengan cepat dan akurat — langsung dari handphone.</p>
  <div class="hero-actions">
    <a href="app.php" class="btn-primary">🚀 Mulai Catat Pesanan</a>
    <a href="#cara-kerja" class="btn-secondary">📖 Lihat Cara Kerja</a>
  </div>
  <div class="hero-stats">
    <div class="stat"><div class="stat-num">3×</div><div class="stat-label">Lebih Cepat</div></div>
    <div class="stat"><div class="stat-num">0</div><div class="stat-label">Kesalahan Catat</div></div>
    <div class="stat"><div class="stat-num">100%</div><div class="stat-label">Gratis</div></div>
  </div>
</header>

<!-- ═══ FITUR ═══ -->
<section class="features" id="fitur">
  <div class="container">
    <div class="section-label">✦ KENAPA WARUNGKU?</div>
    <h2 class="section-title">Dibuat Khusus untuk<br>Penjual Cafetaria Kampus</h2>
    <p class="section-desc">Saat jam makan tiba dan mahasiswa berdatangan, kamu butuh alat yang cepat dan tidak ribet.</p>
    <div class="features-grid">
      <article class="feat-card" data-reveal>
        <div class="feat-icon">⚡</div>
        <h3>Pencatatan Super Cepat</h3>
        <p>Tap menu, atur jumlah, checkout. Hanya 3 langkah untuk mencatat satu pesanan. Tidak ada halaman yang perlu dimuat ulang.</p>
      </article>
      <article class="feat-card" data-reveal>
        <div class="feat-icon">📱</div>
        <h3>Optimal di Handphone</h3>
        <p>Didesain khusus untuk layar handphone. Tombol besar, mudah dijangkau ibu jari. Tidak perlu laptop atau tablet.</p>
      </article>
      <article class="feat-card" data-reveal>
        <div class="feat-icon">📊</div>
        <h3>Riwayat Pesanan Lengkap</h3>
        <p>Semua pesanan tersimpan otomatis. Cek riwayat penjualan kapan saja untuk mengelola stok dan laporan harian.</p>
      </article>
      <article class="feat-card" data-reveal>
        <div class="feat-icon">🎯</div>
        <h3>Akurat & Anti Salah</h3>
        <p>Tidak ada lagi pesanan tertukar atau lupa dicatat. Sistem otomatis menghitung subtotal, pajak, dan total.</p>
      </article>
      </article>
    </div>
  </div>
</section>

<!-- ═══ HARGA LANGGANAN ═══ -->
<section class="pricing" id="harga" style="background:var(--surface2);">
  <div class="container" style="text-align:center;">
    <div class="section-label">💎 HARGA LANGGANAN</div>
    <h2 class="section-title">Investasi Terjangkau<br>untuk Kelancaran Usaha</h2>
    <p class="section-desc" style="margin:0 auto 40px;">Pilih paket yang paling sesuai dengan kebutuhan kantin Anda. Nikmati bulan pertama secara gratis!</p>
    
    <div style="display:flex; flex-wrap:wrap; gap:20px; justify-content:center;">
      <!-- Paket 1 -->
      <div class="pricing-card" data-reveal style="flex:1; min-width:260px; max-width:320px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:32px 24px; opacity:0; transform:translateY(30px);">
        <h3 style="font-family:'Playfair Display',serif; font-size:22px; color:var(--cream); margin-bottom:8px;">Bulanan</h3>
        <div style="font-size:32px; font-weight:700; color:var(--gold); margin-bottom:16px;">Rp 20.000<span style="font-size:14px; color:var(--text-dim); font-weight:400;">/bln</span></div>
        <ul style="list-style:none; text-align:left; color:var(--text-dim); font-size:14px; margin-bottom:28px; display:flex; flex-direction:column; gap:12px;">
          <li>✓ Akses penuh fitur pencatatan</li>
          <li>✓ Riwayat pesanan tak terbatas</li>
          <li>✓ <b>Bulan pertama GRATIS</b></li>
        </ul>
        <a href="app.php" class="btn-secondary" style="width:100%; justify-content:center; font-size:14px; padding:12px;">Pilih Paket</a>
      </div>

      <!-- Paket 2 -->
      <div class="pricing-card" data-reveal style="flex:1; min-width:260px; max-width:320px; background:var(--surface); border:1px solid var(--gold); border-radius:var(--radius); padding:32px 24px; position:relative; box-shadow:0 10px 40px rgba(212,168,83,0.15); opacity:0; transform:translateY(30px);">
        <div style="position:absolute; top:-12px; left:50%; transform:translateX(-50%); background:var(--gold); color:var(--bg); font-size:11px; font-weight:700; padding:4px 12px; border-radius:100px; letter-spacing:1px; text-transform:uppercase;">Terpopuler</div>
        <h3 style="font-family:'Playfair Display',serif; font-size:22px; color:var(--cream); margin-bottom:8px;">6 Bulan</h3>
        <div style="font-size:32px; font-weight:700; color:var(--gold); margin-bottom:16px;">Rp 110.000<span style="font-size:14px; color:var(--text-dim); font-weight:400;">/6 bln</span></div>
        <ul style="list-style:none; text-align:left; color:var(--text-dim); font-size:14px; margin-bottom:28px; display:flex; flex-direction:column; gap:12px;">
          <li>✓ Lebih hemat Rp 10.000</li>
          <li>✓ Akses penuh fitur pencatatan</li>
          <li>✓ Riwayat pesanan tak terbatas</li>
          <li>✓ <b>Bulan pertama GRATIS</b></li>
        </ul>
        <a href="app.php" class="btn-primary" style="width:100%; justify-content:center; font-size:14px; padding:12px;">Pilih Paket</a>
      </div>

      <!-- Paket 3 -->
      <div class="pricing-card" data-reveal style="flex:1; min-width:260px; max-width:320px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:32px 24px; opacity:0; transform:translateY(30px);">
        <h3 style="font-family:'Playfair Display',serif; font-size:22px; color:var(--cream); margin-bottom:8px;">Tahunan</h3>
        <div style="font-size:32px; font-weight:700; color:var(--gold); margin-bottom:16px;">Rp 200.000<span style="font-size:14px; color:var(--text-dim); font-weight:400;">/thn</span></div>
        <ul style="list-style:none; text-align:left; color:var(--text-dim); font-size:14px; margin-bottom:28px; display:flex; flex-direction:column; gap:12px;">
          <li>✓ Lebih hemat Rp 40.000</li>
          <li>✓ Akses penuh fitur pencatatan</li>
          <li>✓ Riwayat pesanan tak terbatas</li>
          <li>✓ <b>Bulan pertama GRATIS</b></li>
        </ul>
        <a href="app.php" class="btn-secondary" style="width:100%; justify-content:center; font-size:14px; padding:12px;">Pilih Paket</a>
      </div>
    </div>
  </div>
  <style>
    .pricing-card.visible { opacity:1!important; transform:translateY(0)!important; transition:opacity 0.6s ease, transform 0.6s ease; }
  </style>
</section>

<!-- ═══ CARA KERJA ═══ -->
<section class="how-it-works" id="cara-kerja">
  <div class="container" style="text-align:center;">
    <div class="section-label">📋 CARA KERJA</div>
    <h2 class="section-title">Semudah 1-2-3</h2>
    <p class="section-desc" style="margin:0 auto 0;">Tidak perlu training atau tutorial panjang. Siapa saja bisa langsung pakai.</p>
    <div class="steps">
      <div class="step" data-reveal>
        <div class="step-icon">🍽️</div>
        <div class="step-num">1</div>
        <h3>Pilih Menu</h3>
        <p>Tap tombol <strong>+</strong> pada menu yang dipesan mahasiswa. Semua menu tampil dalam grid yang mudah dilihat.</p>
      </div>
      <div class="step" data-reveal>
        <div class="step-icon">🔢</div>
        <div class="step-num">2</div>
        <h3>Atur Jumlah</h3>
        <p>Tambah atau kurangi jumlah langsung di kartu menu. Total otomatis terhitung di keranjang pesanan.</p>
      </div>
      <div class="step" data-reveal>
        <div class="step-icon">✅</div>
        <div class="step-num">3</div>
        <h3>Checkout & Simpan</h3>
        <p>Tap "Checkout" dan konfirmasi. Pesanan tersimpan otomatis ke riwayat. Siap terima pesanan berikutnya!</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══ TESTIMONI ═══ -->
<section class="testimonials" id="testimoni">
  <div class="container">
    <div class="section-label">💬 TESTIMONI</div>
    <h2 class="section-title">Dipercaya Penjual Cafetaria</h2>
    <p class="section-desc">Dengarkan cerita dari mereka yang sudah merasakan kemudahan WarungKu.</p>
    <div class="testi-grid">
      <div class="testi-card" data-reveal>
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Dulu saya pakai buku catatan, sering salah hitung dan tulisan susah dibaca. Sejak pakai WarungKu, pesanan tidak pernah tertukar lagi!"</p>
        <div class="testi-author">
          <div class="testi-avatar">👩</div>
          <div><div class="testi-name">Bu Siti</div><div class="testi-role">Penjual di Kantin FT</div></div>
        </div>
      </div>
      <div class="testi-card" data-reveal>
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Jam 12 siang itu gila-gilaan ramai. WarungKu bikin saya bisa catat pesanan sambil masak. Tinggal tap-tap, selesai!"</p>
        <div class="testi-author">
          <div class="testi-avatar">👨</div>
          <div><div class="testi-name">Pak Budi</div><div class="testi-role">Penjual di Kantin FISIP</div></div>
        </div>
      </div>
      <div class="testi-card" data-reveal>
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Fitur riwayat pesanan sangat membantu. Saya jadi tahu menu apa yang paling laris setiap harinya. Recommended!"</p>
        <div class="testi-author">
          <div class="testi-avatar">👩</div>
          <div><div class="testi-name">Mbak Rina</div><div class="testi-role">Penjual di Kantin FK</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ CTA FINAL ═══ -->
<section class="cta-section">
  <div class="container">
    <div class="section-label">🚀 MULAI SEKARANG</div>
    <h2 class="section-title">Siap Melayani Lebih Cepat?</h2>
    <p class="section-desc" style="margin:0 auto;">Langsung buka halaman pesanan dan mulai catat. Silakan pilih paket langganan Anda.</p>
    <a href="app.php" class="btn-primary">🚀 Masuk Aplikasi</a>
  </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer>
  <div class="footer-brand">Warung<b>Ku</b></div>
  <p class="footer-text">Aplikasi pencatatan pesanan cafetaria kampus.<br>Dibuat dengan ❤️ untuk para penjual cafetaria.</p>
  <div class="footer-links">
    <a href="app.php">Halaman Pesanan</a>
    <a href="riwayat.php">Riwayat</a>
    <a href="#fitur">Fitur</a>
    <a href="#cara-kerja">Cara Kerja</a>
  </div>
  <p class="footer-text" style="margin-top:20px;">© 2026 WarungKu. All rights reserved.</p>
</footer>

<script>
// ── Nav scroll effect ──
const nav=document.getElementById('mainNav');
window.addEventListener('scroll',()=>{nav.classList.toggle('scrolled',window.scrollY>40);});

// ── Theme Toggle ──
function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('warungku_theme', next);
  updateThemeIcons(next);
}

function updateThemeIcons(theme) {
  const icon = theme === 'dark' ? '☀️' : '🌙';
  const btn = document.getElementById('themeToggleBtn');
  const btnMobile = document.getElementById('themeToggleBtnMobile');
  if(btn) btn.textContent = icon;
  if(btnMobile) btnMobile.textContent = icon;
}

// Set initial icon
updateThemeIcons(document.documentElement.getAttribute('data-theme'));

// ── Mobile menu ──
function toggleMenu(){document.getElementById('mobileMenu').classList.toggle('open');}
function closeMenu(){document.getElementById('mobileMenu').classList.remove('open');}

// ── Scroll reveal ──
const observer=new IntersectionObserver((entries)=>{
  entries.forEach((e,i)=>{
    if(e.isIntersecting){
      setTimeout(()=>e.target.classList.add('visible'),i*100);
      observer.unobserve(e.target);
    }
  });
},{threshold:0.15});
document.querySelectorAll('[data-reveal]').forEach(el=>observer.observe(el));
</script>
</body>
</html>
