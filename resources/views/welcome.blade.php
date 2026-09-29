<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- ========== SÉCURITÉ ========== --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- ========== SEO ========== --}}
    <title>{{ $settings->site_name }} – {{ $settings->site_slogan }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($settings->site_description, 155) }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="{{ $settings->site_name }}">
    <meta property="og:title"       content="{{ $settings->site_name }} – {{ $settings->site_slogan }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($settings->site_description, 155) }}">
    <meta property="og:url"         content="{{ url()->current() }}">
    @if(!empty($settings->logo_url))
        <meta property="og:image" content="{{ $settings->logo_url }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="{{ $settings->site_name }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($settings->site_description, 155) }}">

    {{-- ========== PERF : PRELOAD / PRECONNECT ========== --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap">

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
            $ctaUrl   = url('/external/dashboard');
            $ctaLabel = 'Mon espace';
            $ctaIcon  = 'fa-user-circle';
        } else {
            $ctaUrl   = url('/login');
            $ctaLabel = 'Commencer';
            $ctaIcon  = 'fa-rocket';
        }

        // ✅ Interrupteur : affichage des liens publics (élèves, paiements, proclamation, enregistrement)
        $showPublicLinks = (bool) ($settings->show_public_links ?? true);

        // Pré-calculs règlement (évite les @if multiples dans le DOM)
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
            color: var(--gray-800);
            background-color: #fff;
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        body.no-scroll { overflow: hidden; }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            line-height: 1.15;
        }

        a { text-decoration: none; color: inherit; transition: color var(--t-base), background var(--t-base), border-color var(--t-base), transform var(--t-base), box-shadow var(--t-base); }
        img { max-width: 100%; height: auto; display: block; }
        ul, ol { list-style: none; }

        :focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* ============================================================
           DESIGN TOKENS
        ============================================================ */
        :root {
            /* Brand */
            --primary:       #6C63FF;
            --primary-dark:  #5A52D5;
            --primary-light: #8B83FF;
            --secondary:     #4CAF50;
            --accent:        #FFB74D;
            --accent-light:  #FFCC80;

            /* Neutres */
            --gray-50:  #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;

            /* Gradients réutilisables */
            --grad-brand: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            --grad-brand-dark: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);

            /* Ombres */
            --shadow-xs: 0 1px 2px rgba(0,0,0,.04);
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,.08);
            --shadow-lg: 0 10px 25px rgba(0,0,0,.1);
            --shadow-xl: 0 20px 40px rgba(0,0,0,.12);
            --shadow-brand: 0 8px 16px rgba(108,99,255,.25);
            --shadow-brand-lg: 0 12px 24px rgba(108,99,255,.35);

            /* Rayons */
            --radius-sm:   8px;
            --radius-md:   12px;
            --radius-lg:   16px;
            --radius-xl:   24px;
            --radius-full: 9999px;

            /* Transitions */
            --t-fast: 150ms cubic-bezier(.4,0,.2,1);
            --t-base: 300ms cubic-bezier(.4,0,.2,1);

            /* Layout */
            --nav-h: 70px;
        }

        /* ============================================================
           UTILITAIRES
        ============================================================ */
        .container {
            max-width: 1280px;
            margin-inline: auto;
            padding-inline: 1.25rem;
        }

        .section-padding { padding-block: 5rem; }
        .section-alt     { background-color: var(--gray-50); }
        .text-center     { text-align: center; }

        .grid { display: grid; gap: 2rem; grid-template-columns: 1fr; }
        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-cols-4 { grid-template-columns: repeat(4, 1fr); }

        .section-actions {
            margin-top: 3rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Sections basses : rendu différé */
        .cv-auto { content-visibility: auto; contain-intrinsic-size: 1px 800px; }

        /* ============================================================
           NAVIGATION
        ============================================================ */
        .navbar {
            position: fixed;
            inset: 0 0 auto 0;
            z-index: 1000;
            background-color: rgba(255,255,255,.98);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-xs);
            transition: box-shadow var(--t-base);
        }

        .navbar.scrolled { box-shadow: var(--shadow-md); }

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
            border-radius: var(--radius-lg);
            background: var(--grad-brand);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
            box-shadow: var(--shadow-sm);
            flex-shrink: 0;
        }

        .logo-text {
            font-size: 1.375rem;
            font-weight: 700;
            background: var(--grad-brand);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-divider {
            width: 1px;
            height: 30px;
            background-color: var(--gray-200);
            margin: 0 1.5rem 0 .5rem;
            flex-shrink: 0;
        }

        .nav-right { display: flex; align-items: center; gap: 1.5rem; }
        .nav-links { display: flex; align-items: center; gap: 1.75rem; }

        .nav-link {
            position: relative;
            font-weight: 500;
            color: var(--gray-700);
            font-size: .95rem;
            white-space: nowrap;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--grad-brand);
            transition: width var(--t-base);
            border-radius: 2px;
        }

        .nav-link:hover { color: var(--primary); }
        .nav-link:hover::after { width: 100%; }

        .nav-buttons { display: flex; align-items: center; gap: .5rem; }

        .btn-nav {
            padding: .6rem 1.25rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: .9rem;
            transition: all var(--t-base);
            white-space: nowrap;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .btn-nav-outline {
            border: 1.5px solid var(--gray-200);
            color: var(--gray-700);
            background: transparent;
        }
        .btn-nav-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--gray-50);
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

        /* ✅ Bouton "Connexion" mis en avant */
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
        .btn-nav-login:active {
            transform: translateY(0);
        }
        /* Point d'attention pulsant (optionnel, discret) */
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
            color: var(--gray-700);
            font-size: 1.5rem;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            transition: all var(--t-base);
            flex-shrink: 0;
        }
        .mobile-toggle:hover { background: var(--gray-100); color: var(--primary); }

        /* Menu mobile */
        .mobile-menu {
            position: fixed;
            top: var(--nav-h);
            inset-inline: 0;
            height: calc(100vh - var(--nav-h));
            background: rgba(255,255,255,.98);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
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
            transition: opacity .3s ease, transform .3s ease, visibility .3s;
        }

        .mobile-menu.open { opacity: 1; transform: translateY(0); visibility: visible; }

        .mobile-menu a {
            padding: .875rem 1rem;
            border-radius: var(--radius-md);
            color: var(--gray-700);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .75rem;
            transition: all var(--t-base);
            font-size: 1.05rem;
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
            transition: opacity .8s cubic-bezier(.4,0,.2,1);
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
            background: linear-gradient(135deg, rgba(0,0,0,.4) 0%, rgba(0,0,0,.6) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }

        .carousel-content {
            text-align: center;
            max-width: 700px;
            animation: slideUp .8s cubic-bezier(.4,0,.2,1) .2s both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .carousel-content h1 {
            font-size: clamp(1.8rem, 5vw, 3.5rem);
            line-height: 1.1;
            margin-bottom: 1rem;
            color: #fff;
            text-shadow: 0 4px 12px rgba(0,0,0,.4);
        }

        .carousel-content p {
            font-size: clamp(.95rem, 2.2vw, 1.25rem);
            margin-bottom: 1.5rem;
            color: var(--gray-200);
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
            padding: .875rem 2rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all var(--t-base);
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .carousel-btn-primary {
            background: var(--grad-brand);
            color: #fff;
            box-shadow: var(--shadow-brand-lg);
        }
        .carousel-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 16px 32px rgba(108,99,255,.45); }

        .carousel-btn-secondary {
            background: rgba(255,255,255,.15);
            color: #fff;
            border: 1.5px solid rgba(255,255,255,.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .carousel-btn-secondary:hover { background: rgba(255,255,255,.25); transform: translateY(-2px); }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,.5);
            border: 1.5px solid rgba(255,255,255,.25);
            color: #fff;
            width: 48px;
            height: 48px;
            border-radius: var(--radius-full);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--t-base);
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
            transition: all var(--t-base);
        }
        .indicator.active { background: #fff; width: 28px; border-radius: 6px; }

        /* ============================================================
           SECTIONS — HEADER GÉNÉRIQUE
        ============================================================ */
        .section-title {
            font-size: clamp(1.55rem, 4vw, 2.75rem);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: .5rem;
            letter-spacing: -.02em;
            line-height: 1.15;
        }

        .section-subtitle { font-size: clamp(.9rem, 1.5vw, 1.1rem); color: var(--gray-600); }
        .section-header   { text-align: center; margin-bottom: 3.5rem; }

        /* Ancres : espace au-dessus pour ne pas être masqué par la navbar */
        section[id]                  { scroll-margin-top: 80px; }
        .reglement-group             { scroll-margin-top: 95px; }

        /* ============================================================
           CARTES ANNONCES / ORGANISATION
        ============================================================ */
        .annonce-card, .org-card {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            transition: all var(--t-base);
            border: 1px solid var(--gray-200);
        }
        .annonce-card:hover, .org-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-8px);
            border-color: var(--primary-light);
        }

        .annonce-card {
            padding: 1.5rem;
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
        .annonce-card-title { font-size: 1.25rem; font-weight: 700; }
        .annonce-card-text  { color: var(--gray-600); font-size: .9rem; flex: 1; }

        .org-card { padding: 2.5rem 1.5rem; text-align: center; }
        .org-card ul {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: .5rem;
            color: var(--gray-600);
        }
        .org-card ul li {
            background: var(--gray-50);
            padding: .25rem .75rem;
            border-radius: var(--radius-full);
            display: inline-block;
            font-size: .9rem;
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
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            font-size: 1.25rem;
        }
        .org-icon {
            width: 64px; height: 64px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary) 10%, var(--secondary) 90%);
            margin: 0 auto 1.5rem;
            font-size: 1.75rem;
        }

        /* ============================================================
           RÈGLEMENT INTÉRIEUR — SECTION SOIGNÉE
        ============================================================ */

        /* Navigation rapide (pills) */
        .reglement-nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .65rem;
            margin-bottom: 2.75rem;
        }
        .reglement-nav-pill {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .65rem 1.15rem;
            border-radius: var(--radius-full);
            background: #fff;
            border: 1.5px solid var(--gray-200);
            color: var(--gray-700);
            font-size: .9rem;
            font-weight: 600;
            transition: all var(--t-base);
            box-shadow: var(--shadow-xs);
            white-space: nowrap;
        }
        .reglement-nav-pill:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
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
            color: var(--gray-600);
            font-size: .72rem;
            font-weight: 700;
        }
        .reglement-nav-pill.is-blue  .reglement-nav-count { background: #DBEAFE; color: #1D4ED8; }
        .reglement-nav-pill.is-amber .reglement-nav-count { background: #FEF3C7; color: #92400E; }
        .reglement-nav-pill.is-red   .reglement-nav-count { background: #FEE2E2; color: #B91C1C; }

        /* Groupe par catégorie */
        .reglement-group {
            margin-bottom: 2.75rem;
        }
        .reglement-group:last-child { margin-bottom: 0; }

        .reglement-group-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .reglement-group-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #fff;
            flex-shrink: 0;
        }
        .reglement-group-icon.is-blue  { background: linear-gradient(135deg, #3B82F6, #2563EB); }
        .reglement-group-icon.is-amber { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .reglement-group-icon.is-red   { background: linear-gradient(135deg, #EF4444, #DC2626); }

        .reglement-group-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: .6rem;
            flex-wrap: wrap;
            line-height: 1.25;
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
            color: var(--gray-600);
            font-size: .75rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* Liste de cartes */
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
            padding: 1.35rem 1.6rem;
            transition: box-shadow var(--t-base), border-color var(--t-base), transform var(--t-base);
            min-width: 0;
        }
        .reglement-card.is-blue  { border-left-color: #3B82F6; }
        .reglement-card.is-amber { border-left-color: #F59E0B; }
        .reglement-card.is-red   { border-left-color: #EF4444; }

        @media (hover: hover) {
            .reglement-card:hover {
                box-shadow: var(--shadow-md);
                transform: translateX(2px);
            }
        }

        .reglement-card-title {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: .6rem;
            line-height: 1.4;
            word-break: break-word;
        }
        .reglement-card-title i {
            font-size: .95rem;
            margin-top: .3rem;
            flex-shrink: 0;
        }
        .reglement-card-title span { flex: 1; min-width: 0; }

        .reglement-card.is-blue  .reglement-card-title i { color: #10B981; }
        .reglement-card.is-amber .reglement-card-title i { color: #D97706; }
        .reglement-card.is-red   .reglement-card-title i { color: #DC2626; }

        /* Corps HTML (issu de CKEditor) */
        .reglement-card-body {
            color: var(--gray-600);
            font-size: .95rem;
            line-height: 1.7;
            word-wrap: break-word;
            overflow-wrap: break-word;
            min-width: 0;
        }
        .reglement-card-body p { margin: 0 0 .75rem; }
        .reglement-card-body p:last-child { margin-bottom: 0; }
        .reglement-card-body ul,
        .reglement-card-body ol { padding-left: 1.25rem; margin: .5rem 0 .75rem; }
        .reglement-card-body ul { list-style: disc; }
        .reglement-card-body ol { list-style: decimal; }
        .reglement-card-body li { margin-bottom: .25rem; }
        .reglement-card-body strong { color: var(--gray-800); font-weight: 700; }
        .reglement-card-body em { font-style: italic; }
        .reglement-card-body u { text-decoration: underline; }
        .reglement-card-body s { text-decoration: line-through; }
        .reglement-card-body a { color: var(--primary); text-decoration: underline; }
        .reglement-card-body a:hover { color: var(--primary-dark); }
        .reglement-card-body blockquote {
            border-left: 3px solid var(--gray-200);
            padding: .5rem 0 .5rem 1rem;
            margin: .75rem 0;
            color: var(--gray-500);
            font-style: italic;
        }
        .reglement-card-body img {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius-md);
            margin: .5rem 0;
        }
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
        .reglement-card-body h1,
        .reglement-card-body h2,
        .reglement-card-body h3,
        .reglement-card-body h4 {
            color: var(--gray-900);
            margin: .75rem 0 .5rem;
            line-height: 1.3;
        }
        .reglement-card-body h1:first-child,
        .reglement-card-body h2:first-child,
        .reglement-card-body h3:first-child,
        .reglement-card-body h4:first-child { margin-top: 0; }

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
            color: var(--gray-500);
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
            color: var(--gray-500);
            font-size: .85rem;
            text-align: center;
        }
        .tarif-table thead {
            background: var(--grad-brand);
            color: #fff;
        }
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
            font-size: .95rem;
        }
        .tarif-table tbody tr:hover { background: var(--gray-50); }

        .tarif-section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 2rem 0 1rem;
            color: var(--gray-800);
        }

        .taux-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: #fff;
            border: 1px solid var(--gray-200);
            padding: .6rem 1.25rem;
            border-radius: var(--radius-full);
            font-size: .9rem;
            color: var(--gray-600);
            box-shadow: var(--shadow-xs);
            flex-wrap: wrap;
            justify-content: center;
            text-align: center;
        }
        .taux-badge i { color: var(--primary); }
        .taux-badge strong { color: var(--gray-800); }

        .tarif-montant-usd { font-weight: 600; }
        .tarif-montant-fc  { font-size: .8rem; color: var(--gray-500); margin-top: .25rem; }

        /* ============================================================
           FOOTER
        ============================================================ */
        footer {
            background: linear-gradient(135deg, var(--gray-900) 0%, #0f172a 100%);
            color: var(--gray-400);
            padding: 5rem 0 2rem;
        }
        footer h3 { color: #fff; font-size: 1.25rem; margin-bottom: 1.5rem; }
        footer a  { color: var(--gray-400); }
        footer a:hover { color: var(--primary); }
        footer ul { display: flex; flex-direction: column; gap: 1.25rem; }
        footer li { display: flex; align-items: flex-start; gap: .75rem; }
        footer li i { margin-top: .25rem; flex-shrink: 0; }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: .875rem;
            margin-bottom: 1.5rem;
        }
        .footer-brand-text {
            font-size: 1.375rem;
            font-weight: 700;
            color: #fff;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .footer-meta { font-size: .875rem; color: var(--gray-500); margin-bottom: .5rem; }
        .footer-meta i { margin-right: .5rem; }

        .social-icons { display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap; }
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            background: rgba(255,255,255,.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,.2);
            transition: all var(--t-base);
            flex-shrink: 0;
        }
        .social-icon:hover {
            background: var(--grad-brand);
            transform: translateY(-4px);
        }

        .footer-bottom {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-800);
            text-align: center;
            color: var(--gray-500);
        }

        /* ============================================================
           ANIMATIONS
        ============================================================ */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity .6s cubic-bezier(.4,0,.2,1),
                        transform .6s cubic-bezier(.4,0,.2,1);
        }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
                scroll-behavior: auto !important;
            }
            .btn-nav-login::before { animation: none; }
        }

        /* ============================================================
           RESPONSIVE — TABLETTE LARGE (≤ 1024px)
        ============================================================ */
        @media (max-width: 1024px) {
            .nav-links, .nav-buttons { display: none; }
            .mobile-toggle { display: flex; }
            .grid-cols-4, .grid-cols-3 { grid-template-columns: repeat(2, 1fr); }

            .reglement-card { padding: 1.25rem 1.4rem; }
        }

        /* ============================================================
           RESPONSIVE — TABLETTE (≤ 768px)
        ============================================================ */
        @media (max-width: 768px) {
            .container { padding-inline: 1rem; }
            .section-padding { padding-block: 3.5rem; }

            /* Carrousel : ratio vertical plus adapté */
            .carousel-container { aspect-ratio: 4/5; }
            .carousel-content h1 { font-size: 1.6rem; }
            .carousel-content p  { font-size: .9rem; }
            .carousel-buttons    { flex-direction: column; }
            .carousel-btn {
                width: 100%;
                justify-content: center;
                font-size: .9rem;
                padding: .75rem 1.5rem;
            }
            .carousel-nav, .carousel-indicators { display: none !important; }

            /* Grilles → 1 colonne */
            .grid-cols-2, .grid-cols-3, .grid-cols-4,
            footer .grid-cols-3 { grid-template-columns: 1fr; }

            .mobile-menu { padding: 1rem 1rem 2rem; }
            .mobile-menu a { font-size: .95rem; padding: .7rem 1rem; }

            .section-title    { font-size: 1.5rem; }
            .section-subtitle { font-size: .9rem; }
            .section-header   { margin-bottom: 2.5rem; }

            .annonce-card-title { font-size: 1.1rem; }
            .annonce-card-text  { font-size: .85rem; }
            .org-card h3        { font-size: 1.1rem; }
            .org-card ul li     { font-size: .8rem; }

            .tarif-table { min-width: 500px; }
            .tarif-table th, .tarif-table td { padding: .75rem; font-size: .8rem; }
            .tarif-section-title { font-size: 1.2rem; }

            .btn-nav { font-size: .8rem; padding: .5rem 1rem; }
            .section-actions { margin-top: 2rem; }
            .tarif-table-wrapper::after { display: block; }

            /* Règlement : nav en grille 2 colonnes */
            .reglement-nav {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: .55rem;
                margin-bottom: 2rem;
            }
            .reglement-nav-pill {
                justify-content: center;
                padding: .6rem .8rem;
                font-size: .82rem;
                gap: .4rem;
            }
            .reglement-nav-pill i { display: none; } /* On gagne de la place */

            /* Groupe header compact */
            .reglement-group { margin-bottom: 2rem; }
            .reglement-group-header { gap: .75rem; margin-bottom: 1.25rem; }
            .reglement-group-icon { width: 42px; height: 42px; font-size: 1.1rem; }
            .reglement-group-title { font-size: 1.15rem; }
            .reglement-group-count { font-size: .7rem; min-width: 22px; height: 22px; }

            /* Cartes règlement compactes */
            .reglement-card { padding: 1.1rem 1.15rem; border-radius: var(--radius-md); }
            .reglement-card-title { font-size: .98rem; gap: .5rem; }
            .reglement-card-body { font-size: .9rem; line-height: 1.65; }
        }

        /* ============================================================
           RESPONSIVE — MOBILE INTERMÉDIAIRE (≤ 560px)
        ============================================================ */
        @media (max-width: 560px) {
            .reglement-nav { grid-template-columns: 1fr; }
            .reglement-nav-pill i { display: inline-block; }
            .reglement-nav-pill {
                padding: .65rem 1rem;
                font-size: .85rem;
            }
        }

        /* ============================================================
           RESPONSIVE — PETIT MOBILE (≤ 480px)
        ============================================================ */
        @media (max-width: 480px) {
            :root { --nav-h: 56px; }
            .container { padding-inline: .85rem; }

            .logo-text { font-size: 1rem; }
            .logo-icon { width: 36px; height: 36px; font-size: 1rem; }
            .mobile-menu { padding: .75rem .75rem 1.5rem; }
            .mobile-menu a { font-size: .9rem; padding: .65rem .85rem; }

            .section-padding { padding-block: 2.75rem; }
            .section-header { margin-bottom: 2rem; }
            .section-title { font-size: 1.35rem; }
            .section-subtitle { font-size: .85rem; }

            .carousel-content h1 { font-size: 1.35rem; }
            .carousel-overlay { padding: 1.5rem 1rem; }

            .tarif-table { min-width: 400px; font-size: .75rem; }
            .tarif-table th, .tarif-table td { padding: .5rem; font-size: .7rem; }
            .tarif-table th { font-size: .65rem; }
            .tarif-section-title { font-size: 1rem; }
            .tarif-table-wrapper::after { font-size: .7rem; }

            /* Règlement : très compact */
            .reglement-group-header { gap: .65rem; margin-bottom: 1rem; }
            .reglement-group-icon { width: 38px; height: 38px; font-size: 1rem; border-radius: 10px; }
            .reglement-group-title { font-size: 1.05rem; gap: .45rem; }
            .reglement-group-count { font-size: .68rem; min-width: 20px; height: 20px; padding: 0 6px; }

            .reglement-card {
                padding: 1rem 1.05rem;
                border-radius: 12px;
                border-left-width: 3px;
            }
            .reglement-card-title { font-size: .93rem; margin-bottom: .5rem; }
            .reglement-card-title i { font-size: .85rem; margin-top: .25rem; }
            .reglement-card-body { font-size: .875rem; line-height: 1.6; }
            .reglement-card-body ul,
            .reglement-card-body ol { padding-left: 1.1rem; }
            .reglement-card-body blockquote { padding-left: .85rem; }

            .annonce-card { padding: 1.15rem; }
            .annonce-card-title { font-size: 1rem; }
            .org-card { padding: 2rem 1.15rem; }

            footer { padding: 3.5rem 0 1.75rem; }
            footer h3 { font-size: 1.1rem; margin-bottom: 1.15rem; }
            footer ul { gap: 1rem; }
            .footer-brand-text { font-size: 1.15rem; }
            .social-icon { width: 36px; height: 36px; }
        }

        /* ============================================================
           RESPONSIVE — TRÈS PETIT MOBILE (≤ 360px)
        ============================================================ */
        @media (max-width: 360px) {
            .container { padding-inline: .7rem; }
            .reglement-card { padding: .9rem .9rem; }
            .reglement-card-title { font-size: .88rem; }
            .reglement-card-body { font-size: .83rem; }
            .reglement-group-title { font-size: 1rem; }
            .section-title { font-size: 1.2rem; }
            .carousel-content h1 { font-size: 1.15rem; }
            .carousel-content p { font-size: .82rem; }
        }

        /* ============================================================
           TRÈS LARGE ÉCRAN — Cartes règlement en 2 colonnes
        ============================================================ */
        @media (min-width: 1440px) {
            .container { max-width: 1400px; }
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
                        <a href="#accueil"      class="nav-link">Accueil</a>
                        <a href="#organisation" class="nav-link">Organisation</a>
                        <a href="#reglement"    class="nav-link">Règlement</a>
                        <a href="#tarifs"       class="nav-link">Tarifs</a>
                        <a href="#annonces"     class="nav-link">Annonces</a>
                    </div>

                    <div class="nav-buttons">
                        {{-- ✅ Enregistrer : conditionnel --}}
                        @if($showPublicLinks)
                            <a href="{{ route('public.enregistrement.create') }}" class="btn-nav btn-nav-outline">
                                <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Enregistrer
                            </a>
                        @endif

                        <a href="{{ route('external.login') }}" class="btn-nav btn-nav-outline">
                            <i class="fa-solid fa-user" aria-hidden="true"></i> Abonnés
                        </a>

                        {{-- ✅ Connexion : bouton mis en avant --}}
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
            <a href="#organisation"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Organisation</a>
            <a href="#reglement"><i class="fa-solid fa-gavel" aria-hidden="true"></i> Règlement</a>
            <a href="#tarifs"><i class="fa-solid fa-tag" aria-hidden="true"></i> Tarifs</a>
            <a href="#annonces"><i class="fa-solid fa-bell" aria-hidden="true"></i> Annonces</a>
            <hr>

            {{-- ✅ Enregistrer : conditionnel --}}
            @if($showPublicLinks)
                <a href="{{ route('public.enregistrement.create') }}">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Enregistrer
                </a>
            @endif

            <a href="{{ route('external.login') }}">
                <i class="fa-solid fa-user" aria-hidden="true"></i> Espace Abonné
            </a>

            {{-- ✅ Connexion : bouton mis en avant --}}
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
                         fetchpriority="high"
                         decoding="sync"
                     @else
                         loading="lazy"
                         decoding="async"
                     @endif
                     sizes="100vw"
                     width="1600" height="900" />
                <div class="carousel-overlay">
                    <div class="carousel-content">
                        <h1>{{ $settings->site_slogan }}</h1>
                        <p>{{ $settings->site_description }}</p>
                        <p class="tagline">Rejoignez une communauté d'apprenants motivés et encadrés par des enseignants passionnés.</p>
                        <div class="carousel-buttons">
                            <a href="{{ $ctaUrl }}" class="carousel-btn carousel-btn-primary">
                                <i class="fa-solid {{ $ctaIcon }}" aria-hidden="true"></i> {{ $ctaLabel }}
                            </a>

                            {{-- ✅ Enregistrer : conditionnel --}}
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

    {{-- ====== ANNONCES ====== --}}
    @if(isset($annonces) && $annonces->isNotEmpty())
        <section id="annonces" class="section-padding section-alt cv-auto">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title reveal">Actualités &amp; Annonces</h2>
                    <p class="section-subtitle reveal">Restez informé des dernières informations importantes</p>
                </div>
                <div class="grid grid-cols-3">
                    @foreach($annonces as $annonce)
                        <article class="annonce-card reveal">
                            <div class="annonce-card-header">
                                <div class="annonce-icon" aria-hidden="true">
                                    <i class="fa-solid fa-lightbulb"></i>
                                </div>
                                <h3 class="annonce-card-title">{{ $annonce->titre }}</h3>
                            </div>
                            <p class="annonce-card-text">{{ $annonce->contenu }}</p>
                            @if(!empty($annonce->image_url))
                                <img src="{{ $annonce->image_url }}"
                                     alt="{{ $annonce->titre }}"
                                     loading="lazy" decoding="async"
                                     sizes="(max-width: 768px) 100vw, 33vw">
                            @endif
                            <div class="card-footer">
                                @if(!empty($annonce->date_fin) && $annonce->date_fin instanceof \Carbon\CarbonInterface)
                                    <div style="font-size:.875rem; color:var(--gray-500);">
                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                        Expire le {{ $annonce->date_fin->format('d M Y') }}
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="section-actions">
                    <a href="{{ route('annonces') }}" class="btn-nav btn-nav-primary">
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
                <h2 class="section-title reveal">Notre Structure</h2>
                <p class="section-subtitle reveal">Explorez nos différents niveaux et sections pédagogiques</p>
            </div>
            <div class="grid grid-cols-4">
                @if(isset($anneesScolaires) && $anneesScolaires->isNotEmpty())
                    <div class="org-card reveal">
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
                    <div class="org-card reveal">
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
                    <div class="org-card reveal">
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
                    <div class="org-card reveal">
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
                <a href="{{ route('public.inscriptions') }}" class="btn-nav btn-nav-primary">
                    <i class="fa-solid fa-book" aria-hidden="true"></i> Voir les classes disponibles
                </a>
            </div>
        </div>
    </section>

    {{-- ============================================================
         RÈGLEMENT INTÉRIEUR — Section soignée
         ============================================================ --}}
    @if($hasAnyReglement)
        <section id="reglement" class="section-padding section-alt cv-auto">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title reveal">Règlement intérieur</h2>
                    <p class="section-subtitle reveal">
                        Les règles de vie à connaître et à respecter au sein de l'établissement
                    </p>
                </div>

                {{-- Navigation rapide entre les 3 catégories --}}
                <nav class="reglement-nav reveal" aria-label="Navigation rapide entre les catégories">
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

                {{-- Groupe 1 : Règles à suivre --}}
                @if($hasRegles)
                    <div class="reglement-group" id="reglement-regles">
                        <header class="reglement-group-header reveal">
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
                                <article class="reglement-card is-blue reveal">
                                    <h4 class="reglement-card-title">
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                        <span>{{ $regle->titre }}</span>
                                    </h4>
                                    <div class="reglement-card-body">
                                        {!! $regle->contenu !!}
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Groupe 2 : Obligations --}}
                @if($hasObligations)
                    <div class="reglement-group" id="reglement-obligations">
                        <header class="reglement-group-header reveal">
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
                                <article class="reglement-card is-amber reveal">
                                    <h4 class="reglement-card-title">
                                        <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                                        <span>{{ $obligation->titre }}</span>
                                    </h4>
                                    <div class="reglement-card-body">
                                        {!! $obligation->contenu !!}
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Groupe 3 : Interdictions --}}
                @if($hasInterdictions)
                    <div class="reglement-group" id="reglement-interdictions">
                        <header class="reglement-group-header reveal">
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
                                <article class="reglement-card is-red reveal">
                                    <h4 class="reglement-card-title">
                                        <i class="fa-solid fa-times-circle" aria-hidden="true"></i>
                                        <span>{{ $interdiction->titre }}</span>
                                    </h4>
                                    <div class="reglement-card-body">
                                        {!! $interdiction->contenu !!}
                                    </div>
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
                    <h2 class="section-title reveal">Tarification</h2>
                    <p class="section-subtitle reveal">Consultez nos frais d'inscription et tarifs annuels</p>
                </div>

                <div class="reveal" style="text-align:center; margin-bottom:2rem;">
                    <span class="taux-badge">
                        <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                        Taux de change appliqué&nbsp;:
                        <strong>1 USD = {{ number_format($tauxChange, 0, ',', ' ') }} FC</strong>
                    </span>
                </div>

                @foreach($sallesParSection as $sectionNom => $salles)
                    <div class="reveal">
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

    {{-- ====== FOOTER ====== --}}
    <footer class="cv-auto">
        <div class="container">
            <div class="grid grid-cols-3" style="margin-bottom:3rem;">
                <div>
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
                        <strong>Directeur :</strong> {{ $settings->responsable_name }}
                    </p>
                </div>
                <div>
                    <h3>Nous Contacter</h3>
                    <ul>
                        <li><i class="fa-solid fa-envelope" aria-hidden="true"></i> <a href="mailto:{{ $settings->site_email }}">{{ $settings->site_email }}</a></li>
                        <li><i class="fa-solid fa-phone" aria-hidden="true"></i> <a href="tel:{{ str_replace(' ', '', $settings->site_phone) }}">{{ $settings->site_phone }}</a></li>
                        <li><i class="fa-solid fa-map-marker-alt" aria-hidden="true"></i> {{ $settings->site_address }}</li>
                    </ul>
                </div>
                <div>
                    <h3>Accès Rapide</h3>
                    <ul>
                        @if($showPublicLinks)
                            <li><a href="{{ route('public.inscriptions') }}">📚 Voir les classes</a></li>
                            <li><a href="{{ route('public.paiements') }}">💰 Suivi des paiements</a></li>
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
                <p>&copy; {{ date('Y') }} <strong>{{ $settings->site_name }}</strong>. Tous droits réservés.</p>
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

                goTo(index) {
                    this.current = ((index % this.total) + this.total) % this.total;
                    this.render();
                }

                next() { this.goTo(this.current + 1); }
                prev() { this.goTo(this.current - 1); }

                start() {
                    this.stop();
                    if (this.total <= 1 || this.isPaused || document.hidden) return;
                    this.timer = setInterval(() => this.next(), this.INTERVAL);
                }

                stop() {
                    if (this.timer) { clearInterval(this.timer); this.timer = null; }
                }

                reset() { this.stop(); this.start(); }

                bindEvents() {
                    const prevBtn = $('[data-carousel-prev]', this.root);
                    const nextBtn = $('[data-carousel-next]', this.root);

                    if (prevBtn) prevBtn.addEventListener('click', () => { this.prev(); this.reset(); });
                    if (nextBtn) nextBtn.addEventListener('click', () => { this.next(); this.reset(); });

                    this.indicators.forEach(ind => {
                        ind.addEventListener('click', () => {
                            this.goTo(parseInt(ind.dataset.carouselIndex, 10));
                            this.reset();
                        });
                    });

                    this.root.addEventListener('mouseenter', () => { this.isPaused = true;  this.stop(); });
                    this.root.addEventListener('mouseleave', () => { this.isPaused = false; this.start(); });
                    this.root.addEventListener('focusin',    () => { this.isPaused = true;  this.stop(); });
                    this.root.addEventListener('focusout',   () => { this.isPaused = false; this.start(); });

                    this.root.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowLeft')  { this.prev(); this.reset(); }
                        if (e.key === 'ArrowRight') { this.next(); this.reset(); }
                    });

                    document.addEventListener('visibilitychange', () => {
                        document.hidden ? this.stop() : this.start();
                    });

                    this.bindSwipe();
                }

                bindSwipe() {
                    let startX = 0, startY = 0, swiping = false;

                    this.root.addEventListener('touchstart', (e) => {
                        const t = e.changedTouches[0];
                        startX = t.clientX;
                        startY = t.clientY;
                        swiping = true;
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
               REVEAL ANIMATIONS
            ============================================================ */
            const revealEls = $$('.reveal');

            if (!prefersReducedMotion && 'IntersectionObserver' in window && revealEls.length) {
                const io = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

                revealEls.forEach((el, i) => {
                    el.style.transitionDelay = `${Math.min(i * 0.05, 0.5)}s`;
                    io.observe(el);
                });
            } else {
                revealEls.forEach(el => el.classList.add('visible'));
            }

            /* ============================================================
               MENU MOBILE — avec focus trap
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
                    } else if (lastFocused) {
                        lastFocused.focus();
                    }
                };

                mobileToggle.addEventListener('click', () => toggleMenu());

                mobileMenu.addEventListener('click', (e) => {
                    if (e.target.closest('a')) toggleMenu(false);
                });

                document.addEventListener('click', (e) => {
                    if (isOpen && !mobileMenu.contains(e.target) && !mobileToggle.contains(e.target)) {
                        toggleMenu(false);
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (!isOpen) return;

                    if (e.key === 'Escape') {
                        e.preventDefault();
                        toggleMenu(false);
                        return;
                    }

                    if (e.key === 'Tab') {
                        const focusable = getFocusable();
                        if (focusable.length === 0) return;

                        const first = focusable[0];
                        const last = focusable[focusable.length - 1];

                        if (e.shiftKey && document.activeElement === first) {
                            e.preventDefault();
                            last.focus();
                        } else if (!e.shiftKey && document.activeElement === last) {
                            e.preventDefault();
                            first.focus();
                        }
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