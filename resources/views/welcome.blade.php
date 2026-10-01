<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $settings->site_name }} – {{ $settings->site_slogan }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($settings->site_description, 155) }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="{{ $settings->site_name }}">
    <meta property="og:title"       content="{{ $settings->site_name }} – {{ $settings->site_slogan }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($settings->site_description, 155) }}">
    <meta property="og:url"         content="{{ url()->current() }}">
    @if(!empty($settings->logo_url))
        <meta property="og:image" content="{{ $settings->logo_url }}">
    @endif

    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="{{ $settings->site_name }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($settings->site_description, 155) }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

    {{-- ✅ Nouvelles polices : Plus Jakarta Sans (titres) + Inter (corps) --}}
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </noscript>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        $webUser     = auth('web')->user();
        $contactUser = auth('contact')->user();

        if ($webUser) {
            $ctaUrl   = url('/dashboard');
            $ctaLabel = 'Tableau de bord';
            $ctaIcon  = 'fa-gauge-high';
        } elseif ($contactUser) {
            $ctaUrl   = url('/espace-contact');
            $ctaLabel = 'Mon espace';
            $ctaIcon  = 'fa-user-circle';
        } else {
            $ctaUrl   = url('/login');
            $ctaLabel = 'Commencer';
            $ctaIcon  = 'fa-rocket';
        }

        $showPublicLinks = (bool) ($settings->show_public_links ?? true);

        $hasRegles        = isset($reglements['regles'])        && $reglements['regles']->isNotEmpty();
        $hasObligations   = isset($reglements['obligations'])   && $reglements['obligations']->isNotEmpty();
        $hasInterdictions = isset($reglements['interdictions']) && $reglements['interdictions']->isNotEmpty();
        $hasAnyReglement  = $hasRegles || $hasObligations || $hasInterdictions;
    @endphp

    <style>
        /* ============================================================
           RESET & BASE
        ============================================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 16px;
            color: #334155;
            background-color: #fff;
            line-height: 1.65;
            letter-spacing: 0.005em;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            font-feature-settings: "cv02", "cv03", "cv04", "cv11";
        }

        body.no-scroll { overflow: hidden; }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            color: #0f172a;
        }

        a { text-decoration: none; color: inherit; transition: all 300ms cubic-bezier(.4,0,.2,1); }
        img { max-width: 100%; height: auto; display: block; }
        ul, ol { list-style: none; }

        ::selection { background: rgba(108,99,255,.2); color: #1e1b4b; }

        :focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 3px;
            border-radius: 6px;
        }

        /* ============================================================
           DESIGN TOKENS
        ============================================================ */
        :root {
            --primary:       #6C63FF;
            --primary-dark:  #5147E0;
            --primary-light: #9B94FF;
            --secondary:     #10B981;
            --secondary-dark:#059669;
            --accent:        #F59E0B;
            --accent-light:  #FCD34D;

            --ink-900: #0f172a;
            --ink-800: #1e293b;
            --ink-700: #334155;
            --ink-600: #475569;
            --ink-500: #64748b;

            --gray-50:  #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-300: #CBD5E1;
            --gray-400: #94A3B8;
            --gray-500: #64748B;

            --grad-brand: linear-gradient(135deg, #6C63FF 0%, #10B981 100%);
            --grad-brand-dark: linear-gradient(135deg, #5147E0 0%, #059669 100%);
            --grad-indigo: linear-gradient(135deg, #6366f1, #8b5cf6);
            --grad-emerald: linear-gradient(135deg, #10b981, #059669);
            --grad-amber: linear-gradient(135deg, #f59e0b, #d97706);
            --grad-rose: linear-gradient(135deg, #f43f5e, #e11d48);

            --shadow-xs: 0 1px 2px rgba(15,23,42,.05);
            --shadow-sm: 0 1px 3px rgba(15,23,42,.08), 0 1px 2px rgba(15,23,42,.04);
            --shadow-md: 0 4px 12px rgba(15,23,42,.08), 0 2px 4px rgba(15,23,42,.04);
            --shadow-lg: 0 12px 24px rgba(15,23,42,.10), 0 4px 8px rgba(15,23,42,.05);
            --shadow-xl: 0 24px 48px rgba(15,23,42,.13), 0 8px 16px rgba(15,23,42,.06);
            --shadow-brand: 0 8px 20px rgba(108,99,255,.28);
            --shadow-brand-lg: 0 16px 32px rgba(108,99,255,.38);

            --radius-sm:   10px;
            --radius-md:   14px;
            --radius-lg:   18px;
            --radius-xl:   24px;
            --radius-2xl:  32px;
            --radius-full: 9999px;

            --ease-out-expo: cubic-bezier(.16, 1, .3, 1);
            --ease-soft:     cubic-bezier(.4, 0, .2, 1);
            --t-base: 300ms var(--ease-soft);
            --t-slow: 600ms var(--ease-out-expo);

            --nav-h: 72px;
        }

        /* ============================================================
           UTILITAIRES
        ============================================================ */
        .container {
            max-width: 1280px;
            margin-inline: auto;
            padding-inline: 1.25rem;
        }

        .section-padding { padding-block: 6rem; }
        .section-alt     { background-color: var(--gray-50); }
        .text-center     { text-align: center; }

        .grid { display: grid; gap: 1.75rem; grid-template-columns: 1fr; }
        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-cols-4 { grid-template-columns: repeat(4, 1fr); }

        .section-actions {
            margin-top: 3.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .cv-auto { content-visibility: auto; contain-intrinsic-size: 1px 800px; }

        /* ============================================================
           NAVIGATION
        ============================================================ */
        .navbar {
            position: fixed;
            inset: 0 0 auto 0;
            z-index: 1000;
            background-color: rgba(255,255,255,.85);
            backdrop-filter: saturate(180%) blur(16px);
            -webkit-backdrop-filter: saturate(180%) blur(16px);
            border-bottom: 1px solid transparent;
            transition: all 400ms var(--ease-soft);
        }

        .navbar.scrolled {
            background-color: rgba(255,255,255,.95);
            border-bottom-color: var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .navbar-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: var(--nav-h);
        }

        .logo { display: flex; align-items: center; gap: .875rem; flex-shrink: 0; min-width: 0; }

        .logo-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: var(--grad-brand);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 1.3rem;
            box-shadow: var(--shadow-brand);
            flex-shrink: 0;
            transition: transform 500ms var(--ease-out-expo);
        }
        .logo:hover .logo-icon { transform: rotate(-8deg) scale(1.08); }

        .logo-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--ink-900);
            letter-spacing: -0.025em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .logo-text span {
            background: var(--grad-brand);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-divider {
            width: 1px;
            height: 28px;
            background-color: var(--gray-200);
            margin: 0 1.5rem 0 .5rem;
            flex-shrink: 0;
        }

        .nav-right { display: flex; align-items: center; gap: 1.5rem; }
        .nav-links { display: flex; align-items: center; gap: 1.75rem; }

        .nav-link {
            position: relative;
            font-weight: 500;
            color: var(--ink-600);
            font-size: .92rem;
            white-space: nowrap;
            padding-block: .25rem;
            letter-spacing: 0;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--grad-brand);
            transition: width 350ms var(--ease-out-expo);
            border-radius: 2px;
        }

        .nav-link:hover { color: var(--primary); }
        .nav-link:hover::after { width: 100%; }

        .nav-link.active { color: var(--primary); font-weight: 600; }
        .nav-link.active::after { width: 100%; }

        .nav-buttons { display: flex; align-items: center; gap: .5rem; }

        .btn-nav {
            padding: .6rem 1.25rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: .88rem;
            transition: all 300ms var(--ease-soft);
            white-space: nowrap;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-family: inherit;
        }

        .btn-nav-outline {
            border: 1.5px solid var(--gray-200);
            color: var(--ink-700);
            background: #fff;
        }
        .btn-nav-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f5f4ff;
            transform: translateY(-1px);
        }

        .btn-nav-primary {
            background: var(--grad-brand);
            color: #fff;
            box-shadow: var(--shadow-brand);
        }
        .btn-nav-primary:hover {
            box-shadow: var(--shadow-brand-lg);
            transform: translateY(-2px);
        }

        .btn-nav-login {
            position: relative;
            background: var(--grad-brand);
            color: #fff !important;
            box-shadow: var(--shadow-brand);
            font-weight: 700;
            padding: .65rem 1.5rem;
            border: none;
        }
        .btn-nav-login i { color: #fff; }
        .btn-nav-login:hover {
            background: var(--grad-brand-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-brand-lg);
        }
        .btn-nav-login:active { transform: translateY(0); }

        .btn-nav-login::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: inherit;
            background: var(--grad-brand);
            opacity: 0;
            z-index: -1;
            animation: loginPulse 3s ease-in-out infinite;
        }
        @keyframes loginPulse {
            0%, 100% { opacity: 0;    transform: scale(1); }
            50%      { opacity: .25;  transform: scale(1.05); }
        }

        .mobile-toggle {
            display: none;
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--ink-700);
            font-size: 1.35rem;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 300ms var(--ease-soft);
            flex-shrink: 0;
        }
        .mobile-toggle:hover { background: var(--gray-100); color: var(--primary); }

        .mobile-menu {
            position: fixed;
            top: var(--nav-h);
            inset-inline: 0;
            height: calc(100vh - var(--nav-h));
            background: rgba(255,255,255,.98);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 999;
            overflow-y: auto;
            padding: 1.5rem 1rem 2.5rem;
            display: flex;
            flex-direction: column;
            gap: .25rem;
            border-top: 1px solid var(--gray-200);
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
            opacity: 0;
            transform: translateY(-10px);
            visibility: hidden;
            transition: opacity .35s ease, transform .35s ease, visibility .35s;
        }

        .mobile-menu.open { opacity: 1; transform: translateY(0); visibility: visible; }

        .mobile-menu a {
            padding: .875rem 1rem;
            border-radius: 12px;
            color: var(--ink-700);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .75rem;
            transition: all 300ms var(--ease-soft);
            font-size: 1rem;
        }

        .mobile-menu a:hover { background: var(--gray-100); color: var(--primary); }
        .mobile-menu a i { width: 20px; text-align: center; flex-shrink: 0; }

        .mobile-menu .mobile-cta {
            background: var(--grad-brand);
            color: #fff;
            justify-content: center;
            font-weight: 600;
            margin-top: .5rem;
        }
        .mobile-menu .mobile-cta:hover { background: var(--grad-brand-dark); color: #fff; }

        .mobile-menu hr { border: none; border-top: 1px solid var(--gray-200); margin: .5rem 0; }

        /* ============================================================
           CARROUSEL
        ============================================================ */
        .carousel-container {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            overflow: hidden;
            background: #000;
            margin-top: var(--nav-h);
            touch-action: pan-y;
        }

        .carousel-item {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity .9s var(--ease-soft);
            will-change: opacity;
        }

        .carousel-item.active { opacity: 1; }

        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .carousel-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(15,23,42,.55) 0%, rgba(15,23,42,.7) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }

        .carousel-content {
            text-align: center;
            max-width: 760px;
            animation: slideUp .9s var(--ease-out-expo) .2s both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(35px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .carousel-content h1 {
            font-size: clamp(1.9rem, 5vw, 3.6rem);
            line-height: 1.08;
            margin-bottom: 1rem;
            color: #fff;
            text-shadow: 0 4px 20px rgba(0,0,0,.4);
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .carousel-content p {
            font-size: clamp(1rem, 2.2vw, 1.2rem);
            margin-bottom: 1.5rem;
            color: #e2e8f0;
            line-height: 1.6;
            text-shadow: 0 2px 8px rgba(0,0,0,.3);
        }

        .carousel-content p.tagline { font-size: 1rem; opacity: .95; }

        .carousel-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .carousel-btn {
            padding: .9rem 2rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 300ms var(--ease-soft);
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-family: inherit;
        }

        .carousel-btn-primary {
            background: var(--grad-brand);
            color: #fff;
            box-shadow: var(--shadow-brand-lg);
        }
        .carousel-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(108,99,255,.5); }

        .carousel-btn-secondary {
            background: rgba(255,255,255,.15);
            color: #fff;
            border: 1.5px solid rgba(255,255,255,.35);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .carousel-btn-secondary:hover { background: rgba(255,255,255,.25); transform: translateY(-3px); }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,.45);
            border: 1.5px solid rgba(255,255,255,.25);
            color: #fff;
            width: 48px;
            height: 48px;
            border-radius: var(--radius-full);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 300ms var(--ease-soft);
            z-index: 10;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 16px rgba(0,0,0,.3);
        }
        .carousel-nav:hover { background: rgba(0,0,0,.7); transform: translateY(-50%) scale(1.08); }
        .carousel-nav.prev { left: 2rem; }
        .carousel-nav.next { right: 2rem; }

        .carousel-indicators {
            position: absolute;
            bottom: 3rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: .75rem;
            z-index: 10;
            padding: 0 1rem;
        }

        .indicator {
            width: 10px;
            height: 10px;
            border-radius: var(--radius-full);
            background: rgba(255,255,255,.4);
            cursor: pointer;
            border: 1.5px solid rgba(255,255,255,.5);
            transition: all 300ms var(--ease-soft);
            padding: 0;
        }
        .indicator.active { background: #fff; width: 32px; border-radius: 6px; }

        /* ============================================================
           SECTIONS — HEADERS
        ============================================================ */
        .section-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(1.75rem, 4vw, 2.9rem);
            font-weight: 800;
            color: var(--ink-900);
            margin-bottom: .75rem;
            letter-spacing: -0.03em;
            line-height: 1.12;
        }

        .section-title .highlight {
            background: var(--grad-brand);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-subtitle {
            font-size: clamp(1rem, 1.5vw, 1.15rem);
            color: var(--ink-500);
            line-height: 1.65;
            max-width: 640px;
            margin-inline: auto;
        }
        .section-header   { text-align: center; margin-bottom: 4rem; }

        section[id]      { scroll-margin-top: 90px; }
        .reglement-group { scroll-margin-top: 100px; }

        /* ============================================================
           ✅ SECTION DÉCOUVRIR — Cartes premium lisibles
        ============================================================ */
        .feature-card {
            background: #fff;
            border-radius: var(--radius-xl);
            padding: 2.25rem 2rem 2rem;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 500ms var(--ease-out-expo);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--grad-brand);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 500ms var(--ease-out-expo);
        }

        .feature-card::after {
            content: '';
            position: absolute;
            top: -50%; right: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(108,99,255,.06) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 500ms var(--ease-soft);
            pointer-events: none;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(108,99,255,.3);
        }
        .feature-card:hover::before { transform: scaleX(1); }
        .feature-card:hover::after  { opacity: 1; }

        .feature-icon {
            width: 68px;
            height: 68px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #fff;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 24px rgba(99,102,241,.3);
            transition: transform 500ms var(--ease-out-expo);
            position: relative;
            z-index: 1;
        }
        .feature-card:hover .feature-icon { transform: scale(1.1) rotate(-8deg); }

        .feature-icon.is-indigo  { background: var(--grad-indigo);  box-shadow: 0 10px 24px rgba(99,102,241,.35); }
        .feature-icon.is-emerald { background: var(--grad-emerald); box-shadow: 0 10px 24px rgba(16,185,129,.35); }
        .feature-icon.is-amber   { background: var(--grad-amber);   box-shadow: 0 10px 24px rgba(245,158,11,.35); }
        .feature-icon.is-rose    { background: var(--grad-rose);    box-shadow: 0 10px 24px rgba(244,63,94,.35); }

        .feature-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--ink-900);
            margin-bottom: .75rem;
            line-height: 1.3;
            letter-spacing: -0.02em;
            position: relative;
            z-index: 1;
        }

        /* ✅ TEXTE PLUS LISIBLE */
        .feature-text {
            color: var(--ink-600);
            font-size: 1rem;
            line-height: 1.75;
            letter-spacing: 0.005em;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        /* ============================================================
           ✅ SECTION "CE QUE VOUS POUVEZ FAIRE" — Refonte premium
        ============================================================ */
        .access-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            max-width: 1100px;
            margin: 0 auto;
        }

        .access-card {
            background: #fff;
            border-radius: var(--radius-2xl);
            padding: 2.5rem 2.25rem;
            border: 1.5px solid var(--gray-200);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: all 500ms var(--ease-out-expo);
        }

        .access-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            transition: transform 500ms var(--ease-out-expo);
        }
        .access-card.is-public::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
        .access-card.is-member::before { background: var(--grad-brand); }

        .access-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
        }
        .access-card.is-public:hover { border-color: rgba(99,102,241,.4); }
        .access-card.is-member:hover { border-color: rgba(108,99,255,.4); }

        .access-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem 1.15rem;
            border-radius: var(--radius-full);
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }
        .access-card.is-public .access-badge {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }
        .access-card.is-member .access-badge {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .access-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--ink-900);
            margin-bottom: .6rem;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }

        .access-desc {
            color: var(--ink-500);
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.7;
            letter-spacing: 0.005em;
        }

        .access-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* ✅ Items plus lisibles */
        .access-item {
            display: flex;
            align-items: flex-start;
            gap: .875rem;
            font-size: 1rem;
            color: var(--ink-700);
            line-height: 1.6;
            letter-spacing: 0.005em;
            padding: .65rem .85rem;
            border-radius: 12px;
            transition: all 300ms var(--ease-soft);
            background: transparent;
        }

        .access-item:hover {
            background: var(--gray-50);
            transform: translateX(4px);
        }

        .access-item i {
            flex-shrink: 0;
            margin-top: .28rem;
            font-size: 1rem;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: transform 300ms var(--ease-out-expo);
        }
        .access-item:hover i { transform: scale(1.2) rotate(8deg); }

        .access-card.is-public .access-item i {
            color: #6366f1;
            background: #eef2ff;
            padding: 3px;
        }
        .access-card.is-member .access-item i {
            color: #fff;
            background: var(--grad-brand);
            padding: 3px;
        }

        .access-item span { flex: 1; }

        .access-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            margin-top: 2rem;
            padding: .9rem 1.75rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: .95rem;
            transition: all 300ms var(--ease-soft);
            width: 100%;
            font-family: inherit;
            cursor: pointer;
            border: none;
        }
        .access-card.is-public .access-cta {
            background: #eef2ff;
            color: #4338ca;
        }
        .access-card.is-public .access-cta:hover {
            background: #e0e7ff;
            transform: translateX(6px);
        }
        .access-card.is-member .access-cta {
            background: var(--grad-brand);
            color: #fff;
            box-shadow: var(--shadow-brand);
        }
        .access-card.is-member .access-cta:hover {
            box-shadow: var(--shadow-brand-lg);
            transform: translateY(-3px);
        }

        /* ============================================================
           ✅ CTA FINAL
        ============================================================ */
        .cta-final {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: var(--radius-2xl);
            padding: 4rem 2.5rem;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .cta-final::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 80% at 20% 0%, rgba(108,99,255,.4), transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 100%, rgba(16,185,129,.3), transparent 60%);
            pointer-events: none;
            animation: ctaGlow 8s ease-in-out infinite alternate;
        }
        @keyframes ctaGlow {
            0%   { opacity: .7; transform: scale(1); }
            100% { opacity: 1;  transform: scale(1.05); }
        }
        .cta-final > * { position: relative; z-index: 1; }

        .cta-final h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(1.65rem, 3.5vw, 2.4rem);
            margin-bottom: 1rem;
            color: #fff;
            letter-spacing: -0.03em;
            line-height: 1.15;
            font-weight: 800;
        }
        .cta-final p {
            color: #cbd5e1;
            font-size: 1.08rem;
            margin-bottom: 2.25rem;
            max-width: 620px;
            margin-inline: auto;
            line-height: 1.7;
        }

        .cta-final-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-final-btn {
            padding: .9rem 2rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: .98rem;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            transition: all 300ms var(--ease-soft);
        }
        .cta-final-btn-primary {
            background: var(--grad-brand);
            color: #fff;
            box-shadow: var(--shadow-brand-lg);
        }
        .cta-final-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(108,99,255,.55); }
        .cta-final-btn-outline {
            background: rgba(255,255,255,.08);
            color: #fff;
            border: 1.5px solid rgba(255,255,255,.25);
            backdrop-filter: blur(10px);
        }
        .cta-final-btn-outline:hover { background: rgba(255,255,255,.15); transform: translateY(-3px); }

        /* ============================================================
           ANNONCES / ORGANISATION
        ============================================================ */
        .annonce-card, .org-card {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            transition: all 500ms var(--ease-out-expo);
            border: 1px solid var(--gray-200);
        }
        .annonce-card:hover, .org-card:hover {
            box-shadow: var(--shadow-xl);
            transform: translateY(-8px);
            border-color: rgba(108,99,255,.3);
        }

        .annonce-card {
            padding: 1.75rem;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .annonce-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--grad-brand);
        }
        .annonce-card .card-footer {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }
        .annonce-card img {
            aspect-ratio: 16 / 9;
            object-fit: cover;
            width: 100%;
            border-radius: var(--radius-md);
            margin: 1rem 0;
        }

        .annonce-card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .annonce-card-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            line-height: 1.3;
            color: var(--ink-900);
        }
        .annonce-card-text {
            color: var(--ink-600);
            font-size: .98rem;
            flex: 1;
            line-height: 1.7;
        }

        .org-card { padding: 2.5rem 1.5rem; text-align: center; }
        .org-card ul {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: .5rem;
            color: var(--ink-600);
        }
        .org-card ul li {
            background: var(--gray-50);
            padding: .35rem .85rem;
            border-radius: var(--radius-full);
            display: inline-block;
            font-size: .92rem;
            margin: .1rem 0;
        }

        .annonce-icon, .org-icon {
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .annonce-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            background: var(--grad-amber);
            font-size: 1.25rem;
            box-shadow: 0 6px 16px rgba(245,158,11,.3);
        }
        .org-icon {
            width: 68px; height: 68px;
            border-radius: 20px;
            background: var(--grad-brand);
            margin: 0 auto 1.5rem;
            font-size: 1.75rem;
            box-shadow: var(--shadow-brand);
            transition: transform 500ms var(--ease-out-expo);
        }
        .org-card:hover .org-icon { transform: scale(1.08) rotate(-6deg); }

        /* ============================================================
           RÈGLEMENT
        ============================================================ */
        .reglement-nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .65rem;
            margin-bottom: 3rem;
        }
        .reglement-nav-pill {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .7rem 1.25rem;
            border-radius: var(--radius-full);
            background: #fff;
            border: 1.5px solid var(--gray-200);
            color: var(--ink-700);
            font-size: .9rem;
            font-weight: 600;
            transition: all 300ms var(--ease-soft);
            box-shadow: var(--shadow-xs);
            white-space: nowrap;
        }
        .reglement-nav-pill:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .reglement-nav-pill.is-blue:hover  { border-color: #93C5FD; color: #1D4ED8; }
        .reglement-nav-pill.is-amber:hover { border-color: #FCD34D; color: #B45309; }
        .reglement-nav-pill.is-red:hover   { border-color: #FCA5A5; color: #B91C1C; }

        .reglement-nav-pill i { font-size: .9rem; }

        .reglement-nav-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            padding: 0 7px;
            border-radius: var(--radius-full);
            background: var(--gray-100);
            color: var(--ink-500);
            font-size: .72rem;
            font-weight: 700;
        }
        .reglement-nav-pill.is-blue  .reglement-nav-count { background: #DBEAFE; color: #1D4ED8; }
        .reglement-nav-pill.is-amber .reglement-nav-count { background: #FEF3C7; color: #92400E; }
        .reglement-nav-pill.is-red   .reglement-nav-count { background: #FEE2E2; color: #B91C1C; }

        .reglement-group { margin-bottom: 3rem; }
        .reglement-group:last-child { margin-bottom: 0; }

        .reglement-group-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.75rem;
        }
        .reglement-group-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #fff;
            flex-shrink: 0;
        }
        .reglement-group-icon.is-blue  { background: linear-gradient(135deg, #3B82F6, #2563EB); box-shadow: 0 8px 20px rgba(59,130,246,.3); }
        .reglement-group-icon.is-amber { background: linear-gradient(135deg, #F59E0B, #D97706); box-shadow: 0 8px 20px rgba(245,158,11,.3); }
        .reglement-group-icon.is-red   { background: linear-gradient(135deg, #EF4444, #DC2626); box-shadow: 0 8px 20px rgba(239,68,68,.3); }

        .reglement-group-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ink-900);
            display: flex;
            align-items: center;
            gap: .6rem;
            flex-wrap: wrap;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }
        .reglement-group-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 26px;
            padding: 0 8px;
            border-radius: var(--radius-full);
            background: var(--gray-100);
            color: var(--ink-500);
            font-size: .75rem;
            font-weight: 700;
        }

        .reglement-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .reglement-card {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            border-left: 4px solid var(--gray-300);
            padding: 1.5rem 1.75rem;
            transition: all 400ms var(--ease-soft);
            min-width: 0;
        }
        .reglement-card.is-blue  { border-left-color: #3B82F6; }
        .reglement-card.is-amber { border-left-color: #F59E0B; }
        .reglement-card.is-red   { border-left-color: #EF4444; }

        @media (hover: hover) {
            .reglement-card:hover {
                box-shadow: var(--shadow-md);
                transform: translateX(4px);
            }
        }

        .reglement-card-title {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ink-900);
            margin-bottom: .65rem;
            line-height: 1.4;
            word-break: break-word;
            letter-spacing: -0.015em;
        }
        .reglement-card-title i { font-size: .95rem; margin-top: .35rem; flex-shrink: 0; }
        .reglement-card-title span { flex: 1; min-width: 0; }

        .reglement-card.is-blue  .reglement-card-title i { color: #10B981; }
        .reglement-card.is-amber .reglement-card-title i { color: #D97706; }
        .reglement-card.is-red   .reglement-card-title i { color: #DC2626; }

        .reglement-card-body {
            color: var(--ink-600);
            font-size: 1rem;
            line-height: 1.75;
            word-wrap: break-word;
            overflow-wrap: break-word;
            min-width: 0;
            letter-spacing: 0.005em;
        }
        .reglement-card-body p { margin: 0 0 .75rem; }
        .reglement-card-body p:last-child { margin-bottom: 0; }
        .reglement-card-body ul,
        .reglement-card-body ol { padding-left: 1.25rem; margin: .5rem 0 .75rem; }
        .reglement-card-body ul { list-style: disc; }
        .reglement-card-body ol { list-style: decimal; }
        .reglement-card-body li { margin-bottom: .25rem; }
        .reglement-card-body strong { color: var(--ink-800); font-weight: 700; }
        .reglement-card-body a { color: var(--primary); text-decoration: underline; }
        .reglement-card-body a:hover { color: var(--primary-dark); }
        .reglement-card-body blockquote {
            border-left: 3px solid var(--gray-200);
            padding: .5rem 0 .5rem 1rem;
            margin: .75rem 0;
            color: var(--ink-500);
            font-style: italic;
        }
        .reglement-card-body img { max-width: 100%; height: auto; border-radius: var(--radius-md); margin: .5rem 0; }
        .reglement-card-body table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            margin: .75rem 0;
            display: block;
            overflow-x: auto;
        }
        .reglement-card-body th,
        .reglement-card-body td {
            padding: .5rem .75rem;
            border: 1px solid var(--gray-200);
            text-align: left;
        }
        .reglement-card-body th { background: var(--gray-50); font-weight: 600; }

        /* ============================================================
           TARIFS
        ============================================================ */
        .tarif-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 1.5rem 0;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: .5rem 0;
            position: relative;
        }
        .tarif-table-wrapper::after {
            content: '← Faites défiler →';
            display: none;
            text-align: center;
            font-size: .8rem;
            color: var(--ink-500);
            padding: .5rem 0 0;
        }

        .tarif-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--gray-200);
            min-width: 600px;
        }
        .tarif-table caption {
            caption-side: bottom;
            padding: .75rem;
            color: var(--ink-500);
            font-size: .85rem;
            text-align: center;
        }
        .tarif-table thead { background: var(--grad-brand); color: #fff; }
        .tarif-table th {
            padding: 1.25rem;
            text-align: left;
            font-weight: 700;
            font-size: .875rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .tarif-table td {
            padding: 1.25rem;
            border-top: 1px solid var(--gray-200);
            font-size: .98rem;
        }
        .tarif-table tbody tr { transition: background 300ms ease; }
        .tarif-table tbody tr:hover { background: var(--gray-50); }

        .tarif-section-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 2rem 0 1rem;
            color: var(--ink-800);
            letter-spacing: -0.02em;
        }

        .taux-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: #fff;
            border: 1px solid var(--gray-200);
            padding: .7rem 1.35rem;
            border-radius: var(--radius-full);
            font-size: .92rem;
            color: var(--ink-600);
            box-shadow: var(--shadow-xs);
            flex-wrap: wrap;
            justify-content: center;
            text-align: center;
        }
        .taux-badge i { color: var(--primary); }
        .taux-badge strong { color: var(--ink-800); }

        .tarif-montant-usd { font-weight: 600; color: var(--ink-800); }
        .tarif-montant-fc  { font-size: .82rem; color: var(--ink-500); margin-top: .25rem; }

        /* ============================================================
           FOOTER
        ============================================================ */
        footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #94a3b8;
            padding: 5rem 0 2rem;
        }
        footer h3 {
            color: #fff;
            font-size: 1.15rem;
            margin-bottom: 1.5rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.015em;
        }
        footer a  { color: #94a3b8; }
        footer a:hover { color: var(--primary-light); }
        footer ul { display: flex; flex-direction: column; gap: 1rem; }
        footer li { display: flex; align-items: flex-start; gap: .75rem; }
        footer li i { margin-top: .3rem; flex-shrink: 0; color: var(--primary-light); }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: .875rem;
            margin-bottom: 1.5rem;
        }
        .footer-brand-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -0.02em;
        }

        .footer-meta { font-size: .9rem; color: #64748b; margin-bottom: .5rem; }
        .footer-meta i { margin-right: .5rem; color: var(--primary-light); }

        .social-icons { display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap; }
        .social-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-full);
            background: rgba(255,255,255,.06);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,.12);
            transition: all 400ms var(--ease-out-expo);
            flex-shrink: 0;
            color: #cbd5e1;
        }
        .social-icon:hover {
            background: var(--grad-brand);
            transform: translateY(-5px) scale(1.08);
            color: #fff;
            border-color: transparent;
            box-shadow: var(--shadow-brand);
        }

        .footer-bottom {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,.08);
            text-align: center;
            color: #64748b;
            font-size: .92rem;
        }

        /* ============================================================
           ✅ SYSTÈME D'ANIMATIONS BIDIRECTIONNELLES
           → Rejouent à chaque entrée dans le viewport (scroll ↑ ET ↓)
           → Direction détectée automatiquement (top / bottom)
        ============================================================ */
        [data-reveal] {
            opacity: 0;
            transform: translateY(48px) scale(0.985);
            filter: blur(8px);
            transition:
                opacity 900ms var(--ease-out-expo),
                transform 900ms var(--ease-out-expo),
                filter 900ms var(--ease-out-expo);
            will-change: opacity, transform, filter;
        }

        /* Direction imposée : gauche */
        [data-reveal="left"]  { transform: translateX(-56px); }
        /* Direction imposée : droite */
        [data-reveal="right"] { transform: translateX(56px); }
        /* Direction imposée : zoom */
        [data-reveal="scale"] { transform: scale(0.9); }
        /* Direction imposée : haut */
        [data-reveal="up"]    { transform: translateY(-56px); }
        /* Direction imposée : bas */
        [data-reveal="down"]  { transform: translateY(56px); }

        /* Direction dynamique selon le sens de scroll (par défaut bas) */
        [data-reveal="auto"][data-scroll-dir="up"]   { transform: translateY(-56px); }
        [data-reveal="auto"][data-scroll-dir="down"] { transform: translateY(56px); }

        /* État visible */
        [data-reveal].is-visible {
            opacity: 1;
            transform: translate(0, 0) scale(1);
            filter: blur(0);
        }

        /* Délais en cascade */
        [data-delay="1"] { transition-delay: 80ms; }
        [data-delay="2"] { transition-delay: 160ms; }
        [data-delay="3"] { transition-delay: 240ms; }
        [data-delay="4"] { transition-delay: 320ms; }
        [data-delay="5"] { transition-delay: 400ms; }
        [data-delay="6"] { transition-delay: 480ms; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
                scroll-behavior: auto !important;
            }
            [data-reveal] {
                opacity: 1 !important;
                transform: none !important;
                filter: none !important;
            }
        }

        /* ============================================================
           RESPONSIVE
        ============================================================ */
        @media (max-width: 1024px) {
            .nav-links, .nav-buttons { display: none; }
            .mobile-toggle { display: flex; }
            .grid-cols-4, .grid-cols-3 { grid-template-columns: repeat(2, 1fr); }
            .reglement-card { padding: 1.35rem 1.5rem; }
            .access-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .container { padding-inline: 1rem; }
            .section-padding { padding-block: 4rem; }
            .section-header { margin-bottom: 2.75rem; }

            .carousel-container { aspect-ratio: 4/5; }
            .carousel-content h1 { font-size: 1.7rem; }
            .carousel-content p  { font-size: .95rem; }
            .carousel-buttons    { flex-direction: column; }
            .carousel-btn {
                width: 100%;
                justify-content: center;
                font-size: .95rem;
                padding: .8rem 1.5rem;
            }
            .carousel-nav, .carousel-indicators { display: none !important; }

            .grid-cols-2, .grid-cols-3, .grid-cols-4,
            footer .grid-cols-3 { grid-template-columns: 1fr; }

            .mobile-menu a { font-size: .95rem; padding: .75rem 1rem; }

            .section-title    { font-size: 1.7rem; }
            .section-subtitle { font-size: .98rem; }

            .feature-card { padding: 1.75rem 1.5rem; }
            .feature-icon { width: 56px; height: 56px; font-size: 1.5rem; border-radius: 16px; }
            .feature-title { font-size: 1.15rem; }
            .feature-text { font-size: .98rem; }

            .access-card { padding: 1.75rem 1.5rem; border-radius: var(--radius-xl); }
            .access-title { font-size: 1.35rem; }
            .access-desc { font-size: .95rem; margin-bottom: 1.5rem; }
            .access-item { font-size: .95rem; padding: .5rem .5rem; }
            .access-item:hover { transform: none; }
            .access-cta { padding: .8rem 1.25rem; font-size: .9rem; }

            .cta-final { padding: 2.75rem 1.5rem; border-radius: var(--radius-xl); }
            .cta-final h2 { font-size: 1.5rem; }
            .cta-final p { font-size: 1rem; }
            .cta-final-buttons { flex-direction: column; align-items: stretch; }
            .cta-final-btn { justify-content: center; }

            .annonce-card-title { font-size: 1.1rem; }
            .annonce-card-text  { font-size: .92rem; }

            .tarif-table { min-width: 500px; }
            .tarif-table th, .tarif-table td { padding: .75rem; font-size: .82rem; }
            .tarif-section-title { font-size: 1.2rem; }

            .section-actions { margin-top: 2.5rem; }
            .tarif-table-wrapper::after { display: block; }

            .reglement-nav {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: .55rem;
                margin-bottom: 2.25rem;
            }
            .reglement-nav-pill { justify-content: center; padding: .65rem .85rem; font-size: .82rem; gap: .4rem; }
            .reglement-nav-pill i { display: none; }

            .reglement-group-header { gap: .75rem; margin-bottom: 1.25rem; }
            .reglement-group-icon { width: 46px; height: 46px; font-size: 1.15rem; }
            .reglement-group-title { font-size: 1.2rem; }

            .reglement-card { padding: 1.2rem 1.25rem; border-radius: var(--radius-md); }
            .reglement-card-title { font-size: 1rem; }
            .reglement-card-body { font-size: .95rem; }
        }

        @media (max-width: 560px) {
            .reglement-nav { grid-template-columns: 1fr; }
            .reglement-nav-pill i { display: inline-block; }
        }

        @media (max-width: 480px) {
            :root { --nav-h: 60px; }
            .container { padding-inline: .9rem; }

            .logo-text { font-size: 1.05rem; }
            .logo-icon { width: 38px; height: 38px; font-size: 1.1rem; border-radius: 12px; }
            .mobile-menu a { font-size: .9rem; padding: .7rem .9rem; }

            .section-padding { padding-block: 3rem; }
            .section-header { margin-bottom: 2.25rem; }
            .section-title { font-size: 1.45rem; }
            .section-subtitle { font-size: .9rem; }

            .carousel-content h1 { font-size: 1.4rem; }
            .carousel-overlay { padding: 1.5rem 1rem; }

            .tarif-table { min-width: 400px; font-size: .75rem; }
            .tarif-table th, .tarif-table td { padding: .5rem; font-size: .72rem; }
            .tarif-section-title { font-size: 1.05rem; }

            .reglement-card { padding: 1rem 1.05rem; border-left-width: 3px; }
            .reglement-card-title { font-size: .95rem; }
            .reglement-card-body { font-size: .9rem; }

            .feature-card { padding: 1.4rem 1.2rem; }
            .feature-icon { width: 50px; height: 50px; font-size: 1.3rem; border-radius: 14px; }
            .feature-title { font-size: 1.05rem; }
            .feature-text { font-size: .93rem; }

            .access-card { padding: 1.35rem 1.15rem; }
            .access-title { font-size: 1.2rem; }
            .access-desc { font-size: .9rem; }
            .access-item { font-size: .9rem; gap: .6rem; }

            footer { padding: 3.5rem 0 1.75rem; }
            .footer-brand-text { font-size: 1.15rem; }
        }

        @media (min-width: 1440px) {
            .container { max-width: 1400px; }
            .access-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

    {{-- ====== NAVIGATION ====== --}}
    <nav class="navbar" id="navbar" aria-label="Navigation principale">
        <div class="container">
            <div class="navbar-inner">
                <a href="#accueil" class="logo" aria-label="Accueil — {{ $settings->site_name }}">
                    <div class="logo-icon" aria-hidden="true">{{ mb_substr($settings->site_name, 0, 1) }}</div>
                    <span class="logo-text">{{ $settings->site_name }}</span>
                </a>

                <span class="nav-divider" aria-hidden="true"></span>

                <div class="nav-right">
                    <div class="nav-links">
                        <a href="#accueil"      class="nav-link" data-spy="accueil">Accueil</a>
                        <a href="#decouvrir"    class="nav-link" data-spy="decouvrir">Découvrir</a>
                        <a href="#acces"        class="nav-link" data-spy="acces">Accès</a>
                        <a href="#organisation" class="nav-link" data-spy="organisation">Organisation</a>
                        <a href="#reglement"    class="nav-link" data-spy="reglement">Règlement</a>
                        <a href="#tarifs"       class="nav-link" data-spy="tarifs">Tarifs</a>
                        <a href="#annonces"     class="nav-link" data-spy="annonces">Annonces</a>
                    </div>

                    <div class="nav-buttons">
                        @if($showPublicLinks)
                            <a href="{{ route('public.enregistrement.create') }}" class="btn-nav btn-nav-outline">
                                <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Enregistrer
                            </a>
                        @endif
                        <a href="{{ route('external.login') }}" class="btn-nav btn-nav-outline">
                            <i class="fa-solid fa-user" aria-hidden="true"></i> Abonnés
                        </a>
                        <a href="{{ url('/login') }}" class="btn-nav btn-nav-login">
                            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Connexion
                        </a>
                    </div>
                </div>

                <button class="mobile-toggle" id="mobileToggle"
                        aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobileMenu">
                    <i class="fa-solid fa-bars" id="hamburgerIcon" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="mobile-menu" id="mobileMenu" role="navigation" aria-hidden="true">
            <a href="#accueil"><i class="fa-solid fa-home" aria-hidden="true"></i> Accueil</a>
            <a href="#decouvrir"><i class="fa-solid fa-gem" aria-hidden="true"></i> Découvrir</a>
            <a href="#acces"><i class="fa-solid fa-key" aria-hidden="true"></i> Accès</a>
            <a href="#organisation"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Organisation</a>
            <a href="#reglement"><i class="fa-solid fa-gavel" aria-hidden="true"></i> Règlement</a>
            <a href="#tarifs"><i class="fa-solid fa-tag" aria-hidden="true"></i> Tarifs</a>
            <a href="#annonces"><i class="fa-solid fa-bell" aria-hidden="true"></i> Annonces</a>
            <hr>
            @if($showPublicLinks)
                <a href="{{ route('public.enregistrement.create') }}">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Enregistrer
                </a>
            @endif
            <a href="{{ route('external.login') }}">
                <i class="fa-solid fa-user" aria-hidden="true"></i> Espace Abonné
            </a>
            <a href="{{ url('/login') }}" class="mobile-cta">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Connexion
            </a>
        </div>
    </nav>

    {{-- ====== CARROUSEL ====== --}}
    <section id="accueil" class="carousel-container" aria-roledescription="carrousel" aria-label="Présentation">
        @php $totalCarousel = $carouselImages->count(); @endphp

        @forelse($carouselImages as $index => $image)
            <div class="carousel-item {{ $loop->first ? 'active' : '' }}"
                 id="carousel-slide-{{ $index }}"
                 role="group"
                 aria-roledescription="diapositive"
                 aria-label="{{ $index + 1 }} sur {{ $totalCarousel }}"
                 aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                <img src="{{ $image->image_url }}"
                     alt="{{ $image->alt_text ?: $settings->site_slogan }}"
                     @if($loop->first)
                         fetchpriority="high" decoding="sync"
                     @else
                         loading="lazy" decoding="async"
                     @endif
                     sizes="100vw" width="1600" height="900" />
                <div class="carousel-overlay">
                    <div class="carousel-content">
                        <h1>{{ $settings->site_slogan }}</h1>
                        <p>{{ $settings->site_description }}</p>
                        <p class="tagline">Rejoignez une communauté d'apprenants motivés et encadrés par des enseignants passionnés.</p>
                        <div class="carousel-buttons">
                            <a href="{{ $ctaUrl }}" class="carousel-btn carousel-btn-primary">
                                <i class="fa-solid {{ $ctaIcon }}" aria-hidden="true"></i> {{ $ctaLabel }}
                            </a>
                            @if($showPublicLinks)
                                <a href="{{ route('public.enregistrement.create') }}" class="carousel-btn carousel-btn-secondary">
                                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Enregistrer
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="carousel-item active">
                <div style="width:100%;height:100%;background:var(--grad-brand);"></div>
                <div class="carousel-overlay">
                    <div class="carousel-content">
                        <h1>{{ $settings->site_slogan }}</h1>
                        <p>{{ $settings->site_description }}</p>
                        <div class="carousel-buttons">
                            <a href="{{ $ctaUrl }}" class="carousel-btn carousel-btn-primary">
                                <i class="fa-solid {{ $ctaIcon }}" aria-hidden="true"></i> {{ $ctaLabel }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforelse

        @if($totalCarousel > 1)
            <button type="button" data-carousel-prev class="carousel-nav prev" aria-label="Diapositive précédente">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" data-carousel-next class="carousel-nav next" aria-label="Diapositive suivante">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
            <div class="carousel-indicators" role="tablist" aria-label="Sélection de la diapositive">
                @foreach($carouselImages as $index => $image)
                    <button type="button"
                            class="indicator {{ $loop->first ? 'active' : '' }}"
                            role="tab"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            aria-controls="carousel-slide-{{ $index }}"
                            aria-label="Aller à la diapositive {{ $index + 1 }}"
                            data-carousel-index="{{ $index }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ============================================================
         SECTION — DÉCOUVRIR LA PLATEFORME
    ============================================================ --}}
    <section id="decouvrir" class="section-padding section-alt cv-auto">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title" data-reveal="auto">
                    Découvrez <span class="highlight">notre plateforme</span>
                </h2>
                <p class="section-subtitle" data-reveal="auto" data-delay="1">
                    Un espace numérique pensé pour les élèves, les parents et l'établissement
                </p>
            </div>

            <div class="grid grid-cols-4">
                <article class="feature-card" data-reveal="auto" data-delay="1">
                    <div class="feature-icon is-indigo" aria-hidden="true">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <h3 class="feature-title">Proclamations &amp; Résultats</h3>
                    <p class="feature-text">
                        Consultez les classements et proclamations publiés après chaque évaluation.
                        Suivez les performances des élèves par classe et par période, en toute transparence.
                    </p>
                </article>

                <article class="feature-card" data-reveal="auto" data-delay="2">
                    <div class="feature-icon is-emerald" aria-hidden="true">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <h3 class="feature-title">Annonces &amp; Communiqués</h3>
                    <p class="feature-text">
                        Restez informé en temps réel : communiqués officiels, alertes importantes,
                        actualités de l'établissement et informations pratiques pour toute la communauté.
                    </p>
                </article>

                <article class="feature-card" data-reveal="auto" data-delay="3">
                    <div class="feature-icon is-amber" aria-hidden="true">
                        <i class="fa-solid fa-users-viewfinder"></i>
                    </div>
                    <h3 class="feature-title">Liste des élèves inscrits</h3>
                    <p class="feature-text">
                        Les parents peuvent vérifier que leur enfant est bien inscrit dans la salle
                        de classe correspondante, avec les informations d'inscription et de frais.
                    </p>
                </article>

                <article class="feature-card" data-reveal="auto" data-delay="4">
                    <div class="feature-icon is-rose" aria-hidden="true">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="feature-title">Pré-inscription en ligne</h3>
                    <p class="feature-text">
                        Enregistrez votre enfant en ligne en quelques minutes. La finalisation
                        de l'inscription se fait ensuite sur place, à l'établissement, sans file d'attente.
                    </p>
                </article>
            </div>
        </div>
    </section>

    {{-- ============================================================
         SECTION — CE QUE VOUS POUVEZ FAIRE
    ============================================================ --}}
    <section id="acces" class="section-padding cv-auto">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title" data-reveal="auto">
                    Ce que <span class="highlight">vous pouvez faire</span>
                </h2>
                <p class="section-subtitle" data-reveal="auto" data-delay="1">
                    Un accès public pour découvrir, un espace abonné pour tout suivre en détail
                </p>
            </div>

            <div class="access-grid">
                {{-- PUBLIC --}}
                <div class="access-card is-public" data-reveal="left">
                    <span class="access-badge">
                        <i class="fa-solid fa-globe"></i> Accès public
                    </span>
                    <h3 class="access-title">Découvrir l'école</h3>
                    <p class="access-desc">
                        Accessible à tous, sans inscription. Idéal pour découvrir l'établissement
                        et ses activités avant de s'engager.
                    </p>
                    <div class="access-list">
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Consulter la présentation de l'école et son organisation</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Voir les annonces et communiqués publics</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Consulter les proclamations et classements publiés</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Vérifier la liste des élèves inscrits par salle</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Découvrir les tarifs d'inscription et frais annuels</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Pré-inscrire un élève en ligne (finalisation sur place)</span></div>
                    </div>
                </div>

                {{-- ABONNÉS --}}
                <div class="access-card is-member" data-reveal="right">
                    <span class="access-badge">
                        <i class="fa-solid fa-star"></i> Espace abonné
                    </span>
                    <h3 class="access-title">Suivre en détail</h3>
                    <p class="access-desc">
                        Réservé aux parents abonnés. Recevez toutes les informations en temps réel
                        et suivez la scolarité de votre enfant pas à pas.
                    </p>
                    <div class="access-list">
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Recevoir toutes les annonces et alertes en temps réel</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Accéder aux résultats détaillés de votre enfant</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Consulter les bulletins et classements personnalisés</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Suivre les paiements et l'état d'inscription</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Communiquer directement avec l'administration</span></div>
                        <div class="access-item"><i class="fa-solid fa-check"></i><span>Être notifié en priorité des événements importants</span></div>
                    </div>

                    @guest('contact')
                        <a href="{{ route('external.register') }}" class="access-cta">
                            <i class="fa-solid fa-user-plus"></i> S'abonner maintenant
                        </a>
                    @else
                        <a href="{{ route('external.dashboard') }}" class="access-cta">
                            <i class="fa-solid fa-arrow-right"></i> Accéder à mon espace
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    {{-- ====== ANNONCES ====== --}}
    @if(isset($annonces) && $annonces->isNotEmpty())
        <section id="annonces" class="section-padding section-alt cv-auto">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title" data-reveal="auto">Actualités &amp; Annonces</h2>
                    <p class="section-subtitle" data-reveal="auto" data-delay="1">Restez informé des dernières informations importantes</p>
                </div>
                <div class="grid grid-cols-3">
                    @foreach($annonces as $annonce)
                        <article class="annonce-card" data-reveal="auto" data-delay="{{ ($loop->iteration % 3) + 1 }}">
                            <div class="annonce-card-header">
                                <div class="annonce-icon" aria-hidden="true">
                                    <i class="fa-solid fa-lightbulb"></i>
                                </div>
                                <h3 class="annonce-card-title">{{ $annonce->titre }}</h3>
                            </div>
                            <p class="annonce-card-text">{{ $annonce->contenu }}</p>
                            @if(!empty($annonce->image_url))
                                <img src="{{ $annonce->image_url }}" alt="{{ $annonce->titre }}"
                                     loading="lazy" decoding="async"
                                     sizes="(max-width: 768px) 100vw, 33vw">
                            @endif
                            <div class="card-footer">
                                @if(!empty($annonce->date_fin) && $annonce->date_fin instanceof \Carbon\CarbonInterface)
                                    <div style="font-size:.9rem; color:var(--ink-500);">
                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                        Expire le {{ $annonce->date_fin->format('d M Y') }}
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="section-actions">
                    <a href="{{ route('annonces') }}" class="btn-nav btn-nav-primary" data-reveal="auto">
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Voir toutes les annonces
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- ====== ORGANISATION ====== --}}
    <section id="organisation" class="section-padding cv-auto">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title" data-reveal="auto">Notre <span class="highlight">Structure</span></h2>
                <p class="section-subtitle" data-reveal="auto" data-delay="1">Explorez nos différents niveaux et sections pédagogiques</p>
            </div>
            <div class="grid grid-cols-4">
                @if(isset($anneesScolaires) && $anneesScolaires->isNotEmpty())
                    <div class="org-card" data-reveal="auto" data-delay="1">
                        <div class="org-icon" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></div>
                        <h3>Années Scolaires</h3>
                        <ul>
                            @foreach($anneesScolaires as $annee)
                                <li>{{ $annee->libelle }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if(isset($sessions) && $sessions->isNotEmpty())
                    <div class="org-card" data-reveal="auto" data-delay="2">
                        <div class="org-icon" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></div>
                        <h3>Sessions</h3>
                        <ul>
                            @foreach($sessions as $session)
                                <li>{{ $session->nom }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if(isset($sections) && $sections->isNotEmpty())
                    <div class="org-card" data-reveal="auto" data-delay="3">
                        <div class="org-icon" aria-hidden="true"><i class="fa-solid fa-building"></i></div>
                        <h3>Sections</h3>
                        <ul>
                            @foreach($sections as $section)
                                <li>{{ $section->nom }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if(isset($options) && $options->isNotEmpty())
                    <div class="org-card" data-reveal="auto" data-delay="4">
                        <div class="org-icon" aria-hidden="true"><i class="fa-solid fa-sliders"></i></div>
                        <h3>Options</h3>
                        <ul>
                            @foreach($options as $option)
                                <li>{{ $option->nom }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div class="section-actions">
                <a href="{{ route('public.inscriptions') }}" class="btn-nav btn-nav-primary" data-reveal="auto">
                    <i class="fa-solid fa-book" aria-hidden="true"></i> Voir les classes disponibles
                </a>
            </div>
        </div>
    </section>

    {{-- ====== RÈGLEMENT ====== --}}
    @if($hasAnyReglement)
        <section id="reglement" class="section-padding section-alt cv-auto">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title" data-reveal="auto">Règlement intérieur</h2>
                    <p class="section-subtitle" data-reveal="auto" data-delay="1">
                        Les règles de vie à connaître et à respecter au sein de l'établissement
                    </p>
                </div>

                <nav class="reglement-nav" data-reveal="auto" aria-label="Navigation rapide entre les catégories">
                    @if($hasRegles)
                        <a href="#reglement-regles" class="reglement-nav-pill is-blue">
                            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                            <span>Règles à suivre</span>
                            <span class="reglement-nav-count">{{ $reglements['regles']->count() }}</span>
                        </a>
                    @endif
                    @if($hasObligations)
                        <a href="#reglement-obligations" class="reglement-nav-pill is-amber">
                            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                            <span>Obligations</span>
                            <span class="reglement-nav-count">{{ $reglements['obligations']->count() }}</span>
                        </a>
                    @endif
                    @if($hasInterdictions)
                        <a href="#reglement-interdictions" class="reglement-nav-pill is-red">
                            <i class="fa-solid fa-ban" aria-hidden="true"></i>
                            <span>Interdictions</span>
                            <span class="reglement-nav-count">{{ $reglements['interdictions']->count() }}</span>
                        </a>
                    @endif
                </nav>

                @if($hasRegles)
                    <div class="reglement-group" id="reglement-regles">
                        <header class="reglement-group-header" data-reveal="auto">
                            <div class="reglement-group-icon is-blue" aria-hidden="true">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <h3 class="reglement-group-title">
                                Règles à suivre
                                <span class="reglement-group-count">{{ $reglements['regles']->count() }}</span>
                            </h3>
                        </header>
                        <div class="reglement-list">
                            @foreach($reglements['regles'] as $regle)
                                <article class="reglement-card is-blue" data-reveal="auto" data-delay="{{ min($loop->iteration, 5) }}">
                                    <h4 class="reglement-card-title">
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                        <span>{{ $regle->titre }}</span>
                                    </h4>
                                    <div class="reglement-card-body">{!! $regle->contenu !!}</div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($hasObligations)
                    <div class="reglement-group" id="reglement-obligations">
                        <header class="reglement-group-header" data-reveal="auto">
                            <div class="reglement-group-icon is-amber" aria-hidden="true">
                                <i class="fa-solid fa-circle-exclamation"></i>
                            </div>
                            <h3 class="reglement-group-title">
                                Obligations
                                <span class="reglement-group-count">{{ $reglements['obligations']->count() }}</span>
                            </h3>
                        </header>
                        <div class="reglement-list">
                            @foreach($reglements['obligations'] as $obligation)
                                <article class="reglement-card is-amber" data-reveal="auto" data-delay="{{ min($loop->iteration, 5) }}">
                                    <h4 class="reglement-card-title">
                                        <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                                        <span>{{ $obligation->titre }}</span>
                                    </h4>
                                    <div class="reglement-card-body">{!! $obligation->contenu !!}</div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($hasInterdictions)
                    <div class="reglement-group" id="reglement-interdictions">
                        <header class="reglement-group-header" data-reveal="auto">
                            <div class="reglement-group-icon is-red" aria-hidden="true">
                                <i class="fa-solid fa-ban"></i>
                            </div>
                            <h3 class="reglement-group-title">
                                Interdictions
                                <span class="reglement-group-count">{{ $reglements['interdictions']->count() }}</span>
                            </h3>
                        </header>
                        <div class="reglement-list">
                            @foreach($reglements['interdictions'] as $interdiction)
                                <article class="reglement-card is-red" data-reveal="auto" data-delay="{{ min($loop->iteration, 5) }}">
                                    <h4 class="reglement-card-title">
                                        <i class="fa-solid fa-times-circle" aria-hidden="true"></i>
                                        <span>{{ $interdiction->titre }}</span>
                                    </h4>
                                    <div class="reglement-card-body">{!! $interdiction->contenu !!}</div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ====== TARIFS ====== --}}
    @if(isset($sallesParSection) && $sallesParSection->isNotEmpty())
        <section id="tarifs" class="section-padding cv-auto">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title" data-reveal="auto">Tarification</h2>
                    <p class="section-subtitle" data-reveal="auto" data-delay="1">Consultez nos frais d'inscription et tarifs annuels</p>
                </div>

                <div data-reveal="scale" style="text-align:center; margin-bottom:2rem;">
                    <span class="taux-badge">
                        <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                        Taux de change appliqué&nbsp;:
                        <strong>1 USD = {{ number_format($tauxChange, 0, ',', ' ') }} FC</strong>
                    </span>
                </div>

                @foreach($sallesParSection as $sectionNom => $salles)
                    <div data-reveal="auto">
                        <h3 class="tarif-section-title">{{ $sectionNom }}</h3>
                        <div class="tarif-table-wrapper">
                            <table class="tarif-table">
                                <caption>Frais d'inscription et annuels pour la section {{ $sectionNom }}</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Classe</th>
                                        <th scope="col">Inscription</th>
                                        <th scope="col">Annuel</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($salles as $salle)
                                        <tr>
                                            <td><strong>{{ $salle->nom }}</strong></td>
                                            <td>
                                                <div class="tarif-montant-usd">{{ number_format($salle->frais_inscription, 2, ',', ' ') }} $</div>
                                                <div class="tarif-montant-fc">≈ {{ number_format($salle->frais_inscription * $tauxChange, 0, ',', ' ') }} FC</div>
                                            </td>
                                            <td>
                                                <div class="tarif-montant-usd">{{ number_format($salle->frais_annuel, 2, ',', ' ') }} $</div>
                                                <div class="tarif-montant-fc">≈ {{ number_format($salle->frais_annuel * $tauxChange, 0, ',', ' ') }} FC</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ====== CTA FINAL ====== --}}
    <section class="section-padding cv-auto">
        <div class="container">
            <div class="cta-final" data-reveal="scale">
                <h2>Prêt à suivre la scolarité de votre enfant ?</h2>
                <p>
                    Abonnez-vous pour recevoir toutes les annonces en temps réel, consulter
                    les résultats détaillés et rester en contact direct avec l'établissement.
                </p>
                <div class="cta-final-buttons">
                    @guest('contact')
                        <a href="{{ route('external.register') }}" class="cta-final-btn cta-final-btn-primary">
                            <i class="fa-solid fa-user-plus"></i> S'abonner gratuitement
                        </a>
                        <a href="{{ route('external.login') }}" class="cta-final-btn cta-final-btn-outline">
                            <i class="fa-solid fa-right-to-bracket"></i> Se connecter
                        </a>
                    @else
                        <a href="{{ route('external.dashboard') }}" class="cta-final-btn cta-final-btn-primary">
                            <i class="fa-solid fa-user-circle"></i> Accéder à mon espace
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    {{-- ====== FOOTER ====== --}}
    <footer class="cv-auto">
        <div class="container">
            <div class="grid grid-cols-3" style="margin-bottom:3rem;">
                <div data-reveal="auto" data-delay="1">
                    <div class="footer-brand">
                        <div class="logo-icon" aria-hidden="true">{{ mb_substr($settings->site_name, 0, 1) }}</div>
                        <span class="footer-brand-text">{{ $settings->site_name }}</span>
                    </div>
                    <p style="margin-bottom:1rem;">{{ $settings->site_description }}</p>
                    <p class="footer-meta">
                        <i class="fa-solid fa-calendar" aria-hidden="true"></i>
                        Depuis {{ $settings->creation_date?->format('Y') ?? '2000' }}
                    </p>
                    <p class="footer-meta">
                        <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
                        <strong style="color:#e2e8f0;">Directeur :</strong> {{ $settings->responsable_name }}
                    </p>
                </div>
                <div data-reveal="auto" data-delay="2">
                    <h3>Nous Contacter</h3>
                    <ul>
                        <li><i class="fa-solid fa-envelope" aria-hidden="true"></i> <a href="mailto:{{ $settings->site_email }}">{{ $settings->site_email }}</a></li>
                        <li><i class="fa-solid fa-phone" aria-hidden="true"></i> <a href="tel:{{ str_replace(' ', '', $settings->site_phone) }}">{{ $settings->site_phone }}</a></li>
                        <li><i class="fa-solid fa-map-marker-alt" aria-hidden="true"></i> {{ $settings->site_address }}</li>
                    </ul>
                </div>
                <div data-reveal="auto" data-delay="3">
                    <h3>Accès Rapide</h3>
                    <ul>
                        @if($showPublicLinks)
                            <li><a href="{{ route('public.inscriptions') }}">📚 Voir les classes</a></li>
                            <li><a href="{{ route('public.classement') }}">🏆 Classements</a></li>
                            <li><a href="{{ route('public.enregistrement.create') }}">✍️ Enregistrer un élève</a></li>
                        @endif
                        <li><a href="{{ route('external.login') }}">👤 Espace Abonné</a></li>
                        <li><a href="{{ url('/login') }}">🔐 Connexion Admin</a></li>
                    </ul>
                    <div class="social-icons">
                        @foreach([
                            'facebook_url'  => ['fa-facebook-f', 'Facebook'],
                            'twitter_url'   => ['fa-twitter', 'Twitter'],
                            'instagram_url' => ['fa-instagram', 'Instagram'],
                            'youtube_url'   => ['fa-youtube', 'YouTube'],
                        ] as $field => [$icon, $label])
                            @if(!empty($settings->{$field}))
                                <a href="{{ $settings->{$field} }}" class="social-icon" aria-label="{{ $label }}" rel="noopener noreferrer" target="_blank">
                                    <i class="fa-brands {{ $icon }}" aria-hidden="true"></i>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} <strong style="color:#e2e8f0;">{{ $settings->site_name }}</strong>. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            'use strict';

            const $  = (sel, ctx = document) => ctx.querySelector(sel);
            const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            /* ============================================================
               ✅ DÉTECTION DE LA DIRECTION DE SCROLL
            ============================================================ */
            let lastScrollY = window.scrollY;
            let scrollDir = 'down';

            window.addEventListener('scroll', () => {
                const y = window.scrollY;
                if (Math.abs(y - lastScrollY) > 4) {
                    scrollDir = y > lastScrollY ? 'down' : 'up';
                    lastScrollY = y;
                }
            }, { passive: true });

            /* ============================================================
               ✅ ANIMATIONS BIDIRECTIONNELLES
               → Rejouent à chaque entrée (scroll ↑ ET ↓)
               → Direction dynamique selon le sens de scroll
            ============================================================ */
            function initReveal() {
                const els = $$('[data-reveal]');
                if (!els.length || prefersReducedMotion) {
                    els.forEach(el => el.classList.add('is-visible'));
                    return;
                }

                const io = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        const el = entry.target;
                        const isAuto = el.dataset.reveal === 'auto';

                        if (entry.isIntersecting) {
                            // Direction dynamique selon le scroll pour les "auto"
                            if (isAuto) {
                                el.dataset.scrollDir = scrollDir;
                            }
                            el.classList.add('is-visible');
                        } else if (entry.intersectionRatio === 0) {
                            // Complètement sorti → on reset pour rejouer
                            el.classList.remove('is-visible');
                            if (isAuto) delete el.dataset.scrollDir;
                        }
                    });
                }, {
                    threshold: [0, 0.12],
                    rootMargin: '0px 0px -40px 0px'
                });

                els.forEach(el => io.observe(el));
            }

            initReveal();

            /* ============================================================
               CARROUSEL
            ============================================================ */
            class Carousel {
                constructor(root) {
                    this.root = root;
                    this.slides = $$('.carousel-item', root);
                    this.indicators = $$('.indicator', root);
                    this.total = this.slides.length;
                    this.current = 0;
                    this.timer = null;
                    this.INTERVAL = 5000;
                    this.isPaused = false;
                    this.rafId = null;
                    if (this.total <= 1) return;
                    this.bindEvents();
                    this.start();
                }
                render() {
                    if (this.rafId) cancelAnimationFrame(this.rafId);
                    this.rafId = requestAnimationFrame(() => {
                        this.slides.forEach((s, i) => {
                            const active = i === this.current;
                            s.classList.toggle('active', active);
                            s.setAttribute('aria-hidden', active ? 'false' : 'true');
                        });
                        this.indicators.forEach((ind, i) => {
                            const active = i === this.current;
                            ind.classList.toggle('active', active);
                            ind.setAttribute('aria-selected', active ? 'true' : 'false');
                        });
                    });
                }
                goTo(index) { this.current = ((index % this.total) + this.total) % this.total; this.render(); }
                next() { this.goTo(this.current + 1); }
                prev() { this.goTo(this.current - 1); }
                start() {
                    this.stop();
                    if (this.total <= 1 || this.isPaused || document.hidden) return;
                    this.timer = setInterval(() => this.next(), this.INTERVAL);
                }
                stop() { if (this.timer) { clearInterval(this.timer); this.timer = null; } }
                reset() { this.stop(); this.start(); }
                bindEvents() {
                    const prevBtn = $('[data-carousel-prev]', this.root);
                    const nextBtn = $('[data-carousel-next]', this.root);
                    if (prevBtn) prevBtn.addEventListener('click', () => { this.prev(); this.reset(); });
                    if (nextBtn) nextBtn.addEventListener('click', () => { this.next(); this.reset(); });
                    this.indicators.forEach(ind => {
                        ind.addEventListener('click', () => { this.goTo(parseInt(ind.dataset.carouselIndex, 10)); this.reset(); });
                    });
                    this.root.addEventListener('mouseenter', () => { this.isPaused = true;  this.stop(); });
                    this.root.addEventListener('mouseleave', () => { this.isPaused = false; this.start(); });
                    this.root.addEventListener('focusin',    () => { this.isPaused = true;  this.stop(); });
                    this.root.addEventListener('focusout',   () => { this.isPaused = false; this.start(); });
                    this.root.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowLeft')  { this.prev(); this.reset(); }
                        if (e.key === 'ArrowRight') { this.next(); this.reset(); }
                    });
                    document.addEventListener('visibilitychange', () => { document.hidden ? this.stop() : this.start(); });
                    this.bindSwipe();
                }
                bindSwipe() {
                    let startX = 0, startY = 0, swiping = false;
                    this.root.addEventListener('touchstart', (e) => {
                        const t = e.changedTouches[0];
                        startX = t.clientX; startY = t.clientY; swiping = true;
                    }, { passive: true });
                    this.root.addEventListener('touchend', (e) => {
                        if (!swiping) return;
                        swiping = false;
                        const t = e.changedTouches[0];
                        const dx = t.clientX - startX;
                        const dy = t.clientY - startY;
                        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
                            dx < 0 ? this.next() : this.prev();
                            this.reset();
                        }
                    }, { passive: true });
                }
            }
            const carouselEl = $('.carousel-container');
            if (carouselEl) new Carousel(carouselEl);

            /* ============================================================
               NAVBAR SCROLL
            ============================================================ */
            const navbar = $('#navbar');
            if (navbar) {
                let ticking = false;
                window.addEventListener('scroll', () => {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(() => {
                        navbar.classList.toggle('scrolled', window.scrollY > 50);
                        ticking = false;
                    });
                }, { passive: true });
            }

            /* ============================================================
               SCROLL-SPY
            ============================================================ */
            const navLinks = $$('.nav-link[data-spy]');
            if (navLinks.length && 'IntersectionObserver' in window) {
                const sections = navLinks
                    .map(link => document.getElementById(link.dataset.spy))
                    .filter(Boolean);
                const spyObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const id = entry.target.id;
                            navLinks.forEach(link => {
                                link.classList.toggle('active', link.dataset.spy === id);
                            });
                        }
                    });
                }, { rootMargin: '-80px 0px -60% 0px', threshold: 0 });
                sections.forEach(section => spyObserver.observe(section));
            }

            /* ============================================================
               MENU MOBILE
            ============================================================ */
            const mobileToggle  = $('#mobileToggle');
            const mobileMenu    = $('#mobileMenu');
            const hamburgerIcon = $('#hamburgerIcon');

            if (mobileToggle && mobileMenu && hamburgerIcon) {
                let isOpen = false;
                let lastFocused = null;
                const focusableSelector = 'a[href], button:not([disabled])';
                const getFocusable = () => $$(focusableSelector, mobileMenu).filter(el => el.offsetParent !== null);

                const toggleMenu = (force) => {
                    isOpen = typeof force === 'boolean' ? force : !isOpen;
                    if (isOpen) lastFocused = document.activeElement;
                    mobileMenu.classList.toggle('open', isOpen);
                    mobileToggle.setAttribute('aria-expanded', String(isOpen));
                    mobileMenu.setAttribute('aria-hidden', String(!isOpen));
                    hamburgerIcon.className = isOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
                    document.body.classList.toggle('no-scroll', isOpen);
                    if (isOpen) {
                        requestAnimationFrame(() => {
                            const first = getFocusable()[0];
                            if (first) first.focus();
                        });
                    } else if (lastFocused) { lastFocused.focus(); }
                };

                mobileToggle.addEventListener('click', () => toggleMenu());
                mobileMenu.addEventListener('click', (e) => { if (e.target.closest('a')) toggleMenu(false); });
                document.addEventListener('click', (e) => {
                    if (isOpen && !mobileMenu.contains(e.target) && !mobileToggle.contains(e.target)) toggleMenu(false);
                });
                document.addEventListener('keydown', (e) => {
                    if (!isOpen) return;
                    if (e.key === 'Escape') { e.preventDefault(); toggleMenu(false); return; }
                    if (e.key === 'Tab') {
                        const focusable = getFocusable();
                        if (focusable.length === 0) return;
                        const first = focusable[0];
                        const last = focusable[focusable.length - 1];
                        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                    }
                });
                window.addEventListener('resize', () => {
                    if (window.innerWidth > 1024 && isOpen) toggleMenu(false);
                }, { passive: true });
            }
        })();
    </script>
</body>
</html>