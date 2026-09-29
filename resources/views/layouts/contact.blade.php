<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $settings   = \App\Models\SiteSetting::getSettings();
        $siteName   = $settings->site_name ?? 'MyscoolApp';
        $siteLogo   = $settings->site_logo ?? null;
        $siteSlogan = $settings->site_slogan ?? 'Espace abonné';
        $contact    = auth('contact')->user();

        // ✅ Compteur fourni par AppServiceProvider
        $unreadCount = (int) ($contactUnreadMessages ?? 0);

        // ✅ Interrupteur public
        $showPublicLinks = (bool) ($settings->show_public_links ?? true);

        $initialOf = static function (string $str): string {
            $str = trim($str);
            return $str === '' ? '?' : mb_strtoupper(mb_substr($str, 0, 1));
        };
    @endphp

    <title>@yield('title', $siteSlogan) - {{ $siteName }}</title>

    <meta name="description" content="@yield('meta_description', $settings->site_description ?? '')">
    <meta name="robots" content="noindex, nofollow">

    @if($siteLogo)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $siteLogo) }}">
    @else
        <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📚</text></svg>">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </noscript>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </noscript>

    <style>
        :root {
            --layout-bg:             #f0f2f5;
            --layout-surface:        #ffffff;
            --layout-border:         #e4e6eb;
            --layout-text:           #050505;
            --layout-text-muted:     #65676b;
            --layout-primary:        #0866ff;
            --layout-primary-dark:   #0045b5;
            --layout-primary-soft:   #e7f3ff;
            --layout-danger-soft:    #fee2e2;
            --layout-danger:         #dc2626;
            --layout-radius:         12px;
            --layout-shadow:         0 2px 8px rgba(0, 0, 0, 0.03);
            --layout-shadow-md:      0 4px 16px rgba(0, 0, 0, 0.06);
            --font-family:           'Inter', system-ui, -apple-system, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }

        body {
            font-family: var(--font-family);
            background-color: var(--layout-bg);
            color: var(--layout-text);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ============================================================
           NAVBAR
           ============================================================ */
        .modern-navbar {
            background-color: var(--layout-surface) !important;
            border-bottom: 1px solid var(--layout-border);
            padding: 0.55rem 0;
            box-shadow: var(--layout-shadow);
            z-index: 1030;
        }

        /* ----- Brand : jamais tronqué sur desktop ----- */
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: var(--layout-text) !important;
            font-size: 1.15rem;
            flex-shrink: 0;
            max-width: 260px;
            margin-right: 0.5rem;
            min-width: 0;
        }

        .navbar-brand > span:last-child {
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
        }

        .navbar-brand .logo-img,
        .navbar-brand .logo-placeholder {
            width: 40px;
            height: 40px;
            border-radius: var(--layout-radius);
            flex-shrink: 0;
            object-fit: cover;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .navbar-brand .logo-placeholder {
            background: linear-gradient(135deg, var(--layout-primary), var(--layout-primary-dark));
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.15rem;
            box-shadow: 0 2px 6px rgba(8, 102, 255, 0.3);
        }

        .navbar-toggler {
            border: none;
            box-shadow: none !important;
            padding: 0.35rem;
            border-radius: 10px;
            transition: background-color 0.2s;
        }
        .navbar-toggler:hover { background: var(--layout-primary-soft); }
        .navbar-toggler:focus { outline: none; }

        /* ----- Liens ----- */
        .navbar-nav {
            flex-wrap: nowrap;
            align-items: center;
        }

        .nav-link {
            color: var(--layout-text-muted) !important;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.5rem 0.85rem !important;
            border-radius: 10px;
            transition: color 0.2s ease, background-color 0.2s ease;
            position: relative;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .nav-link:hover,
        .nav-link.active,
        .nav-link.show {
            color: var(--layout-primary) !important;
            background-color: var(--layout-primary-soft);
        }

        /* Indicateur actif discret (liens normaux) */
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 18px;
            height: 2px;
            border-radius: 2px;
            background: var(--layout-primary);
            opacity: 0.9;
        }

        /* ============================================================
           ✅ LIEN MIS EN AVANT — "Fil d'actualité"
           ============================================================ */
        .nav-link-highlight {
            background: linear-gradient(135deg, var(--layout-primary) 0%, var(--layout-primary-dark) 100%);
            color: #fff !important;
            padding: 0.55rem 1.15rem !important;
            border-radius: 999px !important;
            font-weight: 700;
            font-size: 0.9rem;
            margin-right: 0.5rem;
            box-shadow: 0 3px 10px rgba(8, 102, 255, 0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            position: relative;
        }

        .nav-link-highlight i {
            color: #fff !important;
            transition: transform 0.2s ease;
        }

        .nav-link-highlight:hover,
        .nav-link-highlight:focus {
            background: linear-gradient(135deg, var(--layout-primary-dark) 0%, #003fa3 100%);
            color: #fff !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(8, 102, 255, 0.4);
        }

        .nav-link-highlight:hover i {
            transform: scale(1.1);
        }

        .nav-link-highlight.active {
            background: linear-gradient(135deg, var(--layout-primary-dark) 0%, #003fa3 100%);
            color: #fff !important;
            box-shadow:
                inset 0 2px 6px rgba(0, 0, 0, 0.15),
                0 3px 10px rgba(8, 102, 255, 0.35);
            transform: none;
        }

        /* Pas de petit trait sous ce lien (il est déjà bien mis en avant) */
        .nav-link-highlight.active::after {
            display: none;
        }

        /* ============================================================
           BADGE DE NOTIFICATION
           ============================================================ */
        .badge-notif {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            margin-left: 6px;
            background: #ef4444;
            color: #fff;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
            animation: badgePulse 0.4s ease-out;
            vertical-align: middle;
            position: relative;
        }

        .badge-notif::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 999px;
            border: 2px solid #ef4444;
            opacity: 0;
            animation: pulseRing 2s ease-out infinite;
            pointer-events: none;
        }

        @keyframes badgePulse {
            0%   { transform: scale(0.5); opacity: 0; }
            60%  { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes pulseRing {
            0%   { transform: scale(0.8); opacity: 0.6; }
            100% { transform: scale(1.3); opacity: 0; }
        }

        /* ============================================================
           DROPDOWN PROFIL + DROPDOWN ÉCOLE
           ============================================================ */
        .contact-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem !important;
            border-radius: 50rem !important;
            background: transparent;
            border: none;
        }
        .contact-dropdown-toggle:hover,
        .contact-dropdown-toggle.show {
            background: var(--layout-primary-soft);
        }

        .contact-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--layout-primary), var(--layout-primary-dark));
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .dropdown-menu {
            border: 1px solid var(--layout-border);
            border-radius: var(--layout-radius);
            box-shadow: var(--layout-shadow-md);
            padding: 0.5rem;
            min-width: 230px;
            margin-top: 0.5rem;
        }
        .dropdown-item {
            border-radius: 8px;
            padding: 0.55rem 0.75rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.15s ease;
        }
        .dropdown-item:hover,
        .dropdown-item:focus { background-color: var(--layout-primary-soft); }
        .dropdown-item.active {
            background-color: var(--layout-primary-soft);
            color: var(--layout-primary);
        }
        .dropdown-item.text-danger:hover { background-color: var(--layout-danger-soft); }

        .dropdown-item i { width: 18px; text-align: center; flex-shrink: 0; }

        .dropdown-toggle::after { margin-left: 0.35rem; vertical-align: middle; }

        /* ============================================================
           MAIN / ALERTS / FOOTER
           ============================================================ */
        main { flex: 1; padding: 1.5rem 0; }

        .layout-alert {
            border-radius: var(--layout-radius);
            border: none;
            box-shadow: var(--layout-shadow);
            font-size: 0.95rem;
        }

        footer {
            background-color: var(--layout-surface);
            border-top: 1px solid var(--layout-border);
            color: var(--layout-text-muted);
            padding: 1.25rem 0;
            margin-top: auto;
            font-size: 0.9rem;
            font-weight: 500;
        }

        [x-cloak] { display: none !important; }

        /* ============================================================
           RESPONSIVE — TABLETTE (≤ 1199px)
           ============================================================ */
        @media (max-width: 1199.98px) {
            .nav-link {
                font-size: 0.85rem;
                padding: 0.45rem 0.65rem !important;
            }
            .nav-link-highlight {
                padding: 0.5rem 1rem !important;
                font-size: 0.85rem;
            }
            .navbar-brand { font-size: 1.05rem; }
            .navbar-brand .logo-img,
            .navbar-brand .logo-placeholder {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }
        }

        /* ============================================================
           RESPONSIVE — MOBILE / TABLETTE PORTRAIT (≤ 991.98px)
           ============================================================ */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                padding-top: 1rem;
                border-top: 1px solid var(--layout-border);
                margin-top: 0.75rem;
                max-height: calc(100vh - 80px);
                overflow-y: auto;
            }
            .navbar-nav { flex-wrap: wrap; }
            .nav-item { width: 100%; }
            .nav-link {
                margin-bottom: 0.15rem;
                padding: 0.65rem 0.85rem !important;
                font-size: 0.95rem;
            }
            .nav-link.active::after { display: none; }

            /* ✅ Sur mobile, la pilule prend toute la largeur et reste mise en avant */
            .nav-link-highlight {
                width: 100%;
                justify-content: center;
                margin: 0 0 0.5rem 0;
                padding: 0.75rem 1rem !important;
                font-size: 0.95rem;
            }

            .contact-dropdown-toggle {
                width: 100%;
                justify-content: flex-start;
                padding: 0.65rem 0.85rem !important;
                border-radius: 10px !important;
            }
            .contact-dropdown-toggle .contact-avatar {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
            .dropdown-menu {
                box-shadow: none;
                border: none;
                padding-left: 1rem;
                background: transparent;
                margin-top: 0;
            }
        }

        /* ============================================================
           RESPONSIVE — PETIT MOBILE (≤ 576px)
           ============================================================ */
        @media (max-width: 576px) {
            main { padding: 1rem 0; }
            .navbar-brand { font-size: 1rem; max-width: 200px; }
            .navbar-brand .logo-img,
            .navbar-brand .logo-placeholder {
                width: 34px;
                height: 34px;
                font-size: 0.95rem;
            }
            .badge-notif { min-width: 18px; height: 18px; font-size: 0.65rem; }
        }

        /* ============================================================
           ACCESSIBILITÉ
           ============================================================ */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
            .nav-link-highlight:hover { transform: none; }
        }

        :focus-visible {
            outline: 2px solid var(--layout-primary);
            outline-offset: 2px;
            border-radius: 4px;
        }
    </style>

    @stack('styles')
</head>
<body x-data="notificationBadge()" x-init="init()">

    {{-- ==================== NAVBAR ==================== --}}
    <nav class="navbar navbar-expand-lg modern-navbar sticky-top">
        <div class="container">

            {{-- Brand --}}
            <a class="navbar-brand" href="{{ route('home') }}">
                @if($siteLogo)
                    <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="logo-img">
                @else
                    <span class="logo-placeholder">{{ $initialOf($siteName) }}</span>
                @endif
                <span>{{ $siteName }}</span>
            </a>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarContact"
                    aria-controls="navbarContact" aria-expanded="false"
                    aria-label="Ouvrir le menu">
                <i class="fa-solid fa-bars fs-5 text-dark" aria-hidden="true"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarContact">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

                    @auth('contact')
                        {{-- ✅ Fil d'actualité — lien mis en avant --}}
                        <li class="nav-item">
                            <a class="nav-link nav-link-highlight {{ request()->routeIs('external.dashboard') ? 'active' : '' }}"
                               href="{{ route('external.dashboard') }}">
                                <i class="fa-solid fa-house me-2" aria-hidden="true"></i>Fil d'actualité
                            </a>
                        </li>

                        {{-- Mes messages + badge --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('external.messages') ? 'active' : '' }}"
                               href="{{ route('external.messages') }}">
                                <i class="fa-solid fa-comments me-2" aria-hidden="true"></i>Mes messages
                                <span class="badge-notif"
                                      x-show="count > 0"
                                      x-cloak
                                      x-text="count > 99 ? '99+' : count"
                                      aria-live="polite"
                                      :aria-label="count + ' message(s) non lu(s)'"></span>
                            </a>
                        </li>

                        {{-- Annonces --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('annonces') ? 'active' : '' }}"
                               href="{{ route('annonces') }}">
                                <i class="fa-solid fa-bullhorn me-2" aria-hidden="true"></i>Annonces
                            </a>
                        </li>

                        {{-- Dropdown "École" : regroupe les 4 liens --}}
                        @if($showPublicLinks)
                            @php
                                $ecoleActive = request()->routeIs('public.inscriptions')
                                    || request()->routeIs('public.paiements')
                                    || request()->routeIs('public.classement')
                                    || request()->routeIs('external.reglement');
                            @endphp
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ $ecoleActive ? 'active' : '' }}"
                                   href="#" role="button"
                                   data-bs-toggle="dropdown"
                                   aria-expanded="false">
                                    <i class="fa-solid fa-school me-2" aria-hidden="true"></i>École
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('public.inscriptions') ? 'active' : '' }}"
                                           href="{{ route('public.inscriptions') }}">
                                            <i class="fa-solid fa-users text-primary" aria-hidden="true"></i>
                                            Liste des élèves
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('public.paiements') ? 'active' : '' }}"
                                           href="{{ route('public.paiements') }}">
                                            <i class="fa-solid fa-coins text-primary" aria-hidden="true"></i>
                                            Ayant Payé
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('public.classement') ? 'active' : '' }}"
                                           href="{{ route('public.classement') }}">
                                            <i class="fa-solid fa-trophy text-primary" aria-hidden="true"></i>
                                            Proclamation
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-2"></li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('external.reglement') ? 'active' : '' }}"
                                           href="{{ route('external.reglement') }}">
                                            <i class="fa-solid fa-book-bookmark text-primary" aria-hidden="true"></i>
                                            Règlement
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @else
                            {{-- Si les liens publics sont masqués, on garde uniquement Règlement --}}
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('external.reglement') ? 'active' : '' }}"
                                   href="{{ route('external.reglement') }}">
                                    <i class="fa-solid fa-book-bookmark me-2" aria-hidden="true"></i>Règlement
                                </a>
                            </li>
                        @endif

                        {{-- Profil --}}
                        <li class="nav-item dropdown ms-lg-2">
                            <a class="nav-link contact-dropdown-toggle" href="#" role="button"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="contact-avatar" aria-hidden="true">
                                    {{ $initialOf($contact->nom ?? 'U') }}
                                </span>
                                <span class="d-none d-lg-inline fw-semibold">
                                    {{ $contact->nom ?? 'Mon compte' }}
                                </span>
                                <i class="fa-solid fa-chevron-down d-none d-lg-inline"
                                   style="font-size: 0.7rem; opacity: 0.6;"
                                   aria-hidden="true"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li class="px-3 py-2 border-bottom mb-2">
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">
                                        {{ $contact->nom ?? 'Utilisateur' }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.78rem;">
                                        {{ $contact->email ?? '' }}
                                    </div>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('external.dashboard') }}">
                                        <i class="fa-solid fa-user text-primary" aria-hidden="true"></i> Mon espace
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('external.messages') }}">
                                        <i class="fa-solid fa-envelope text-primary" aria-hidden="true"></i>
                                        <span class="flex-grow-1">Mes messages</span>
                                        <span class="badge-notif"
                                              x-show="count > 0"
                                              x-cloak
                                              x-text="count > 99 ? '99+' : count"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('external.reglement') }}">
                                        <i class="fa-solid fa-book-bookmark text-primary" aria-hidden="true"></i> Règlement
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-2"></li>
                                <li>
                                    <form action="{{ route('external.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger w-100 text-start">
                                            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                                            Déconnexion
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        {{-- Annonces --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('annonces') ? 'active' : '' }}"
                               href="{{ route('annonces') }}">
                                <i class="fa-solid fa-bullhorn me-2" aria-hidden="true"></i>Annonces
                            </a>
                        </li>

                        {{-- Dropdown "École" (visiteurs) --}}
                        @if($showPublicLinks)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-school me-2" aria-hidden="true"></i>École
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('public.inscriptions') }}">
                                            <i class="fa-solid fa-users text-primary" aria-hidden="true"></i>
                                            Liste des élèves
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('public.paiements') }}">
                                            <i class="fa-solid fa-coins text-primary" aria-hidden="true"></i>
                                            Ayant Payé
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('public.classement') }}">
                                            <i class="fa-solid fa-trophy text-primary" aria-hidden="true"></i>
                                            Proclamation
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('external.login') }}">
                                <i class="fa-solid fa-right-to-bracket me-2" aria-hidden="true"></i>Connexion
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold"
                               href="{{ route('external.register') }}">
                                <i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i> Inscription
                            </a>
                        </li>
                    @endauth

                </ul>
            </div>
        </div>
    </nav>

    {{-- ==================== CONTENU PRINCIPAL ==================== --}}
    <main>
        <div class="container">

            @if(session('success'))
                <div class="alert layout-alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-circle-check fs-5 me-2" aria-hidden="true"></i>
                    <div class="flex-grow-1">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert layout-alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation fs-5 me-2" aria-hidden="true"></i>
                    <div class="flex-grow-1">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert layout-alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fa-solid fa-triangle-exclamation fs-5 me-2" aria-hidden="true"></i>
                        <span class="fw-bold">Erreur de validation</span>
                    </div>
                    <ul class="mb-0 ps-4">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- ==================== FOOTER ==================== --}}
    <footer>
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <p class="mb-0">
                    &copy; {{ date('Y') }} <strong>{{ $siteName }}</strong>. Tous droits réservés.
                </p>
                <a href="{{ route('home') }}" class="text-decoration-none text-muted small">
                    <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Retour au site public
                </a>
            </div>
        </div>
    </footer>

    {{-- ==================== SCRIPTS ==================== --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <script>
    document.addEventListener('alpine:init', () => {
        window.__csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        @auth('contact')
            Alpine.data('notificationBadge', () => ({
                count: {{ $unreadCount }},
                pollTimer: null,
                POLL_INTERVAL: 15000,
                UNREAD_URL: '{{ route('external.messages.unread-count') }}',

                init() {
                    setTimeout(() => this.poll(), 3000);
                    this.pollTimer = setInterval(() => this.poll(), this.POLL_INTERVAL);

                    window.addEventListener('notifications-reset', () => {
                        this.count = 0;
                    });

                    window.addEventListener('beforeunload', () => this.destroy());
                },

                destroy() {
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    }
                },

                async poll() {
                    if (document.hidden) return;

                    try {
                        const res = await fetch(this.UNREAD_URL, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });

                        if (!res.ok) return;

                        const data = await res.json();
                        if (typeof data.unread_messages === 'number') {
                            if (data.unread_messages > this.count) {
                                this.$nextTick(() => {
                                    document.querySelectorAll('.badge-notif').forEach(el => {
                                        el.style.animation = 'none';
                                        void el.offsetWidth;
                                        el.style.animation = 'badgePulse 0.4s ease-out';
                                    });
                                });
                            }
                            this.count = data.unread_messages;
                        }
                    } catch {
                        // Silencieux
                    }
                },
            }));
        @else
            Alpine.data('notificationBadge', () => ({
                count: 0,
                init() {},
                destroy() {},
            }));
        @endauth
    });
    </script>

    @stack('scripts')
</body>
</html>