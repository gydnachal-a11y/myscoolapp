<!DOCTYPE html>
<html lang="fr" class="no-alpine">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- ANTI-FLICKER + FALLBACK --}}
    <script>
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed-preload');
        }

        setTimeout(function () {
            if (document.documentElement.classList.contains('no-alpine')) {
                document.documentElement.classList.remove('no-alpine');
                document.documentElement.classList.add('alpine-ready');
            }
        }, 2000);
    </script>

    <style>
        /* ════════════════════════════════════════════════════════
           ANTI-FLICKER
           ════════════════════════════════════════════════════════ */
        html { background-color: #f1f5f9; }
        html.no-alpine body { visibility: hidden; }
        html.alpine-ready body {
            visibility: visible;
            animation: fadeInPage 0.15s ease-out;
        }
        @keyframes fadeInPage {
            from { opacity: 0.6; }
            to   { opacity: 1; }
        }
        html.sidebar-collapsed-preload .sidebar-aside { width: 5rem !important; }
        [x-cloak] { display: none !important; }

        /* ════════════════════════════════════════════════════════
           RESET GLOBAL
           ════════════════════════════════════════════════════════ */
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
            width: 100%;
        }

        .flex, .inline-flex { min-width: 0; }

        /* ════════════════════════════════════════════════════════
           ✅ FIX CRITIQUE — Les tables restent des <table> sur desktop
           ════════════════════════════════════════════════════════
           La règle `table { display: block; }` cassait TOUTES les tables
           de l'application sur desktop (les colonnes ne respectaient plus
           leurs largeurs, table-layout: fixed ne s'appliquait plus, etc.).
           On la limite donc au mobile uniquement.
        */
        @media (max-width: 767px) {
            /* Mobile : scroll horizontal fluide pour tables NON stylées.
               Les .data-table ont leur propre système de cartes,
               cette règle ne les affecte donc pas. */
            table:not(.data-table) {
                display: block;
                overflow-x: auto;
                max-width: 100%;
                -webkit-overflow-scrolling: touch;
            }
        }

        @media (min-width: 768px) {
            /* Desktop : on ne touche pas au comportement natif des tables */
            table {
                display: table;
                max-width: 100%;
            }
        }

        img, video, iframe {
            max-width: 100%;
            height: auto;
        }

        /* ════════════════════════════════════════════════════════
           SCROLLBARS
           ════════════════════════════════════════════════════════ */
        * {
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
        }
        *:hover { scrollbar-color: rgba(148, 163, 184, 0.3) transparent; }
        ::-webkit-scrollbar { width: 0; height: 0; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: transparent; }

        .sidebar-nav {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .sidebar-nav::-webkit-scrollbar { display: none; width: 0; }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @php
        use Illuminate\Support\Facades\Log;
        use Illuminate\Support\Facades\Route;

        $settings     = \App\Models\SiteSetting::getSettings();
        $webUser      = auth('web')->user();
        $contactUser  = auth('contact')->user();
        $currentUser  = $webUser ?? $contactUser;
        $currentGuard = $webUser ? 'web' : ($contactUser ? 'contact' : null);
        $isContact    = $currentGuard === 'contact';

        $userName    = $currentUser->name  ?? $currentUser->nom  ?? 'Utilisateur';
        $userRole    = $currentUser->role_label ?? $currentUser->role ?? ($isContact ? 'Abonné' : 'Invité');
        $logoutRoute = $isContact ? 'external.logout' : 'logout';

        $userPermissions = [];

        if ($currentUser && $currentGuard === 'web') {
            try {
                $userPermissions = $currentUser->getAllPermissionNames();
            } catch (\Throwable $e) {
                Log::warning('Échec getAllPermissionNames()', [
                    'user_id' => $currentUser->id ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $userPermissionsMap = array_flip($userPermissions);

        $isSuperAdmin = $currentGuard === 'web'
            && $currentUser
            && (
                $currentUser->hasRole('super_admin')
                || (method_exists($currentUser, 'isSuperAdmin') && $currentUser->isSuperAdmin())
            );

        $adminMenuSections = [
            'Tableaux de bord' => [
                ['route' => 'admin.statistiques.index',       'label' => 'Statistiques générales',    'icon' => 'fa-chart-pie',  'permission' => 'statistiques.index'],
                ['route' => 'admin.paiement-dashboard.index', 'label' => 'Tableau de bord paiements', 'icon' => 'fa-chart-line', 'permission' => 'paiement-dashboard.index'],
            ],
            'Configuration' => [
                ['route' => 'admin.annees-scolaires.index',   'label' => 'Années scolaires',   'icon' => 'fa-calendar-alt',  'permission' => 'annees-scolaires.index'],
                ['route' => 'admin.sessions.index',           'label' => 'Sessions',           'icon' => 'fa-layer-group',   'permission' => 'sessions.index'],
                ['route' => 'admin.sections.index',           'label' => 'Sections',           'icon' => 'fa-th-large',      'permission' => 'sections.index'],
                ['route' => 'admin.options.index',            'label' => 'Options',            'icon' => 'fa-sliders',       'permission' => 'options.index'],
                ['route' => 'admin.salles-de-classe.index',   'label' => 'Salles de classe',   'icon' => 'fa-door-open',     'permission' => 'salles-de-classe.index'],
                ['route' => 'admin.fonctions.index',          'label' => 'Fonctions',          'icon' => 'fa-briefcase',     'permission' => 'fonctions.index'],
                ['route' => 'admin.mois-scolaires.index',     'label' => 'Mois scolaires',     'icon' => 'fa-calendar-day',  'permission' => 'mois-scolaires.index'],
                ['route' => 'admin.tranches-scolaires.index', 'label' => 'Tranches scolaires', 'icon' => 'fa-calendar-week', 'permission' => 'tranches-scolaires.index'],
                ['route' => 'admin.devises.index',            'label' => 'Devises',            'icon' => 'fa-coins',         'permission' => 'devises.index'],
                ['route' => 'admin.site-settings.edit',       'label' => 'Paramètres du site', 'icon' => 'fa-globe',         'permission' => 'site-settings.edit'],
                ['route' => 'admin.settings.canvas.edit',     'label' => 'Éditeur d\'en-tête', 'icon' => 'fa-paint-brush',   'permission' => 'settings.canvas.edit'],
            ],
            'Inscriptions & Élèves' => [
                ['route' => 'admin.eleves.index',         'label' => 'Élèves',                'icon' => 'fa-user-graduate',       'permission' => 'eleves.index'],
                ['route' => 'admin.inscriptions.index',   'label' => 'Inscriptions',          'icon' => 'fa-clipboard-list',      'permission' => 'inscriptions.index'],
                ['route' => 'admin.info-eleves.index',    'label' => 'Info élèves',           'icon' => 'fa-users-viewfinder',    'permission' => 'info-eleves.index'],
                ['route' => 'admin.info-paiements.index', 'label' => 'Info paiements élèves', 'icon' => 'fa-file-invoice-dollar', 'permission' => 'info-paiements.index'],
            ],
            'Pédagogie' => [
                ['route' => 'admin.cours.index',         'label' => 'Cours',             'icon' => 'fa-book-open',          'permission' => 'cours.index'],
                ['route' => 'admin.categories.index',    'label' => 'Catégories',        'icon' => 'fa-tags',               'permission' => 'categories.index'],
                ['route' => 'admin.references.index',    'label' => 'Libellés',          'icon' => 'fa-tag',                'permission' => 'references.index', 'params' => ['type' => 'libelles']],
                ['route' => 'admin.references.index',    'label' => 'Pondérations',      'icon' => 'fa-scale-balanced',     'permission' => 'references.index', 'params' => ['type' => 'ponderations']],
                ['route' => 'admin.references.index',    'label' => 'Nb heures',         'icon' => 'fa-clock',              'permission' => 'references.index', 'params' => ['type' => 'nombre-heures']],
                ['route' => 'admin.references.index',    'label' => 'Créneaux horaires', 'icon' => 'fa-clock-rotate-left',  'permission' => 'references.index', 'params' => ['type' => 'creneaux-horaires']],
                ['route' => 'admin.periode-notes.index', 'label' => 'Périodes de notes', 'icon' => 'fa-calendar-check',     'permission' => 'periode-notes.index'],
                ['route' => 'admin.notes.index',         'label' => 'Notes des élèves',  'icon' => 'fa-pencil',             'permission' => 'notes.index'],
                ['route' => 'admin.notes.classement',    'label' => 'Classements',       'icon' => 'fa-trophy',             'permission' => 'notes.classement'],
            ],
            'Finances' => [
                ['route' => 'admin.paiements.index',                    'label' => 'Paiements élèves',          'icon' => 'fa-money-bill-wave',         'permission' => 'paiements.index'],
                ['route' => 'admin.frais-supplementaires.index',        'label' => 'Frais supplémentaires',     'icon' => 'fa-wallet',                  'permission' => 'frais-supplementaires.index'],
                ['route' => 'admin.paiement-frais-supplementaires.index','label' => 'Paiements frais supp.',    'icon' => 'fa-receipt',                 'permission' => 'paiement-frais-supplementaires.index'],
                ['route' => 'admin.echeances.index',                    'label' => 'Échéances',                 'icon' => 'fa-hourglass-half',          'permission' => 'echeances.index'],
                ['route' => 'admin.salaires.index',                     'label' => 'Salaires',                  'icon' => 'fa-hand-holding-dollar',     'permission' => 'salaires.index'],
                ['route' => 'admin.paiement-salaires.index',            'label' => 'Paiements salaires',        'icon' => 'fa-money-check-dollar',      'permission' => 'paiement-salaires.index'],
                ['route' => 'admin.avances.index',                      'label' => 'Avances sur salaire',       'icon' => 'fa-hand-holding-usd',        'permission' => 'avances.index'],
                ['route' => 'admin.salaire-horaires.index',             'label' => 'Taux horaire',              'icon' => 'fa-business-time',           'permission' => 'salaire-horaires.index'],
                ['route' => 'admin.taux.edit',                          'label' => 'Taux de change',            'icon' => 'fa-arrow-right-arrow-left',  'permission' => 'taux.edit'],
                ['route' => 'admin.planification-paiements.index',      'label' => 'Planification paiements',   'icon' => 'fa-calendar-plus',           'permission' => 'planification-paiements.index'],
            ],
            'Demandes d\'avance' => [
                ['route' => 'admin.session-avances.index', 'label' => 'Sessions d\'ouverture', 'icon' => 'fa-calendar-check',       'permission' => 'session-avances.index'],
                [
                    'route'      => 'admin.demandes-avance.index',
                    'label'      => 'Demandes en attente',
                    'icon'       => 'fa-hand-holding-dollar',
                    'permission' => 'demandes-avance.index',
                    'badge'      => $demandesAvanceEnAttente ?? 0,
                    'urgent'     => ($demandesAvanceEnAttente ?? 0) > 0,
                ],
            ],
            'Sécurité & Permissions' => [
                ['route' => 'admin.roles.index',        'label' => 'Rôles',                 'icon' => 'fa-shield-halved', 'permission' => 'roles.index'],
                ['route' => 'admin.permissions.manage', 'label' => 'Permissions (avancé)',  'icon' => 'fa-lock',          'permission' => 'permissions.manage'],
                ['route' => 'admin.permissions.index',  'label' => 'Liste des permissions', 'icon' => 'fa-list',          'permission' => 'permissions.index'],
            ],
            'Communication' => [
                ['route' => 'admin.contacts.index',  'label' => 'Abonnés',                 'icon' => 'fa-users',              'permission' => 'contacts.index'],
                ['route' => 'admin.annonces.index',  'label' => 'Annonces',                'icon' => 'fa-bullhorn',           'permission' => 'annonces.index'],
                ['route' => 'admin.messages.index',  'label' => 'Messages reçus',          'icon' => 'fa-envelope-open-text', 'permission' => 'messages.index', 'badge' => $unreadMessages ?? 0],
                ['route' => 'admin.emails.compose',  'label' => 'Envoyer un email groupé', 'icon' => 'fa-paper-plane',        'permission' => 'emails.compose'],
            ],
            'Règlement intérieur' => [
                ['route' => 'admin.reglements.index', 'label' => 'Règles & disciplines', 'icon' => 'fa-gavel', 'permission' => 'reglements.index'],
            ],
            'Administration' => [
                ['route' => 'admin.users.index', 'label' => 'Personnel', 'icon' => 'fa-users-cog', 'permission' => 'users.index'],
            ],
        ];

        $adminMenuPermissionSet = [];
        foreach ($adminMenuSections as $links) {
            foreach ($links as $link) {
                if (!empty($link['permission'])) {
                    $adminMenuPermissionSet[$link['permission']] = true;
                }
            }
        }

        $hasAdminPermission = !empty(array_intersect_key(
            $adminMenuPermissionSet,
            $userPermissionsMap
        ));

        $isAdminUser = $currentGuard === 'web'
            && $currentUser
            && (
                $isSuperAdmin
                || $currentUser->hasRole('admin')
                || $hasAdminPermission
            );

        $unreadMessages = $unreadMessages ?? 0;

        if (!isset($demandesAvanceEnAttente)) {
            try {
                $demandesAvanceEnAttente = $isAdminUser
                    ? \App\Models\DemandeAvance::enAttente()->count()
                    : 0;
            } catch (\Throwable $e) {
                $demandesAvanceEnAttente = 0;
            }
        }
        $aDesDemandesEnAttente = $demandesAvanceEnAttente > 0;

        $canAccessMenu = function (array $link) use ($currentUser, $isSuperAdmin, $userPermissionsMap): bool {
            if (empty($link['permission'])) return true;
            if (!$currentUser) return false;
            if ($isSuperAdmin) return true;
            return isset($userPermissionsMap[$link['permission']]);
        };

        $memberMenuSections = [
            'Tableau de bord' => [
                ['route' => 'dashboard.index', 'label' => 'Accueil', 'icon' => 'fa-home'],
            ],
            'Mes cours' => [
                ['route' => 'member.cours.index',        'label' => 'Mes cours',    'icon' => 'fa-book-open', 'permission' => 'cours.index'],
                ['route' => 'member.cours.assignements', 'label' => 'Assignations', 'icon' => 'fa-tasks',     'permission' => 'cours.assign'],
            ],
            'Notes' => [
                ['route' => 'member.notes.saisie',     'label' => 'Saisir notes', 'icon' => 'fa-pencil',     'permission' => 'notes.saisie'],
                ['route' => 'member.notes.bulletin',   'label' => 'Bulletins',    'icon' => 'fa-file-lines', 'permission' => 'notes.bulletin'],
                ['route' => 'member.notes.classement', 'label' => 'Classements',  'icon' => 'fa-trophy',     'permission' => 'notes.classement'],
            ],
            'Finances personnelles' => [
                ['route' => 'member.salaire',               'label' => 'Mon salaire',            'icon' => 'fa-money-bill',          'permission' => 'salaires.index'],
                ['route' => 'member.demandes-avance.index', 'label' => 'Mes demandes d\'avance', 'icon' => 'fa-hand-holding-dollar'],
            ],
            'Informations' => [
                ['route' => 'member.profil', 'label' => 'Mon profil', 'icon' => 'fa-user'],
            ],
        ];

        $menuSections     = $isAdminUser ? $adminMenuSections : $memberMenuSections;
        $filteredSections = [];

        foreach ($menuSections as $sectionName => $links) {
            $filteredSections[$sectionName] = [];

            foreach ($links as $link) {
                if (!Route::has($link['route'])) continue;
                if (!$canAccessMenu($link)) continue;

                $link['url']    = route($link['route'], $link['params'] ?? []);
                $link['active'] = request()->routeIs($link['route']);
                $filteredSections[$sectionName][] = $link;
            }

            if (empty($filteredSections[$sectionName])) {
                unset($filteredSections[$sectionName]);
            }
        }
    @endphp

    <title>@yield('page_title', 'Administration') - {{ $settings->site_name ?? 'Application' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --premium-bg:          #0a0e1a;
            --premium-bg-2:        #0f1420;
            --premium-text:        #e2e8f0;
            --premium-text-muted:  #94a3b8;
            --premium-text-dim:    #64748b;
            --premium-accent:      #6366f1;
            --premium-accent-2:    #8b5cf6;
            --premium-accent-glow: rgba(99, 102, 241, 0.4);
            --premium-success:     #10b981;
            --premium-danger:      #ef4444;

            --font-display: 'Plus Jakarta Sans', system-ui, sans-serif;
            --font-body:    'Inter', system-ui, sans-serif;

            --sidebar-section-size: 14px;
            --sidebar-link-size:    13px;
        }

        body {
            font-family: var(--font-body);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(99, 102, 241, 0.08), transparent),
                radial-gradient(ellipse 80% 50% at 50% 120%, rgba(139, 92, 246, 0.05), transparent),
                #f1f5f9;
        }

        h1, h2, h3, h4, h5 { font-family: var(--font-display); letter-spacing: -0.02em; }

        @keyframes pulse-red {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            50%      { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        }
        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.15); }
        }
        @keyframes shimmer {
            0%   { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        @keyframes spin-slow { to { transform: rotate(360deg); } }
        @keyframes fadeInLink {
            from { opacity: 0; transform: translateX(-6px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideDownFade {
            from { opacity: 0; transform: translateY(-4px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .badge-urgent { animation: pulse-badge 1.5s ease-in-out infinite; }
        .menu-item-urgent { position: relative; }
        .menu-item-urgent::after {
            content: '';
            position: absolute;
            top: 8px; right: 8px;
            width: 8px; height: 8px;
            background: var(--premium-danger);
            border-radius: 50%;
            animation: pulse-red 2s infinite;
        }

        .link-item {
            animation: fadeInLink 0.3s cubic-bezier(0.4, 0, 0.2, 1) both;
        }
        .link-item:nth-child(1)  { animation-delay: 0.02s; }
        .link-item:nth-child(2)  { animation-delay: 0.04s; }
        .link-item:nth-child(3)  { animation-delay: 0.06s; }
        .link-item:nth-child(4)  { animation-delay: 0.08s; }
        .link-item:nth-child(5)  { animation-delay: 0.10s; }
        .link-item:nth-child(6)  { animation-delay: 0.12s; }
        .link-item:nth-child(7)  { animation-delay: 0.14s; }
        .link-item:nth-child(8)  { animation-delay: 0.16s; }
        .link-item:nth-child(n+9){ animation-delay: 0.18s; }

        @media (max-width: 1023.98px) {
            .sidebar-aside { position: fixed !important; }
        }
        @media (min-width: 1024px) {
            .sidebar-aside { position: relative !important; }
        }

        .sidebar-aside {
            background: linear-gradient(180deg, #0a0e1a 0%, #0f1420 50%, #0a0e1a 100%);
            isolation: isolate;
            box-shadow:
                0 0 0 1px rgba(148, 163, 184, 0.06) inset,
                0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .sidebar-aside::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 40% at 20% 0%, rgba(99, 102, 241, 0.15), transparent 50%),
                radial-gradient(ellipse 40% 30% at 80% 100%, rgba(139, 92, 246, 0.1), transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .sidebar-aside::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: -1;
            opacity: 0.6;
        }

        .sidebar-logo-mark {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            box-shadow:
                0 8px 24px rgba(99, 102, 241, 0.35),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-logo-mark:hover { transform: scale(1.05); }
        .sidebar-logo-mark::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        .sidebar-brand-text {
            background: linear-gradient(135deg, #f8fafc 0%, #c7d2fe 50%, #f8fafc 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-family: var(--font-display);
            font-weight: 800;
        }

        .sidebar-profile {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.05) 100%);
            border: 1px solid rgba(148, 163, 184, 0.12);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        .sidebar-profile::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: conic-gradient(from 0deg at 50% 50%, transparent 0deg, rgba(99, 102, 241, 0.1) 60deg, transparent 120deg);
            animation: spin-slow 20s linear infinite;
            pointer-events: none;
        }
        .profile-avatar {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            box-shadow:
                0 4px 12px rgba(99, 102, 241, 0.35),
                0 0 0 2px rgba(255, 255, 255, 0.05);
            position: relative;
            z-index: 1;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .profile-avatar:hover { transform: scale(1.08) rotate(-3deg); }
        .profile-avatar::after {
            content: '';
            position: absolute;
            bottom: -2px; right: -2px;
            width: 10px; height: 10px;
            background: var(--premium-success);
            border-radius: 50%;
            border: 2px solid var(--premium-bg-2);
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
        }

        .sidebar-search {
            background: rgba(148, 163, 184, 0.06);
            border: 1px solid rgba(148, 163, 184, 0.1);
            transition: all 0.25s;
        }
        .sidebar-search:focus-within {
            background: rgba(99, 102, 241, 0.08);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .section-header {
            color: var(--premium-text-dim);
            font-family: var(--font-display);
            font-size: var(--sidebar-section-size);
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            line-height: 1.3;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .section-header:hover {
            color: var(--premium-text-muted);
            background: rgba(148, 163, 184, 0.06);
        }
        .section-header .section-name {
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: left;
            padding-right: 0.5rem;
        }
        .section-header .chevron {
            flex-shrink: 0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), color 0.25s;
        }
        .section-header:hover .chevron { color: var(--premium-accent); }
        .section-header.is-open { color: #c7d2fe; }
        .section-header.is-open .chevron {
            color: var(--premium-accent);
            filter: drop-shadow(0 0 4px var(--premium-accent-glow));
        }

        .sidebar-link {
            color: var(--premium-text-muted);
            font-size: var(--sidebar-link-size);
            font-weight: 500;
            line-height: 1.4;
            position: relative;
            overflow: hidden;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%) scaleY(0);
            width: 3px;
            height: 60%;
            background: linear-gradient(180deg, var(--premium-accent) 0%, var(--premium-accent-2) 100%);
            border-radius: 0 3px 3px 0;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 12px var(--premium-accent-glow);
        }
        .sidebar-link:hover {
            color: var(--premium-text);
            background: rgba(148, 163, 184, 0.05);
            transform: translateX(2px);
        }
        .sidebar-link:hover::before { transform: translateY(-50%) scaleY(1); }
        .sidebar-link:hover i { transform: scale(1.15); }
        .sidebar-link i {
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-link-active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.18) 0%, rgba(139, 92, 246, 0.12) 100%);
            color: #fff !important;
            box-shadow:
                0 4px 16px rgba(99, 102, 241, 0.25),
                0 0 0 1px rgba(99, 102, 241, 0.3) inset;
            position: relative;
        }
        .sidebar-link-active::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 70%;
            background: linear-gradient(180deg, #818cf8 0%, #a78bfa 100%);
            border-radius: 0 3px 3px 0;
            box-shadow: 0 0 16px rgba(129, 140, 248, 0.8);
        }
        .sidebar-link-active::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.06) 50%, transparent 100%);
            background-size: 200% 100%;
            animation: shimmer 4s ease-in-out infinite;
            pointer-events: none;
        }
        .sidebar-link-active i {
            filter: drop-shadow(0 0 6px rgba(129, 140, 248, 0.7));
        }

        /* ═══════════════════════════════════════════════════════════
           BOUTON DÉCONNEXION
           ═══════════════════════════════════════════════════════════ */
        .sidebar-logout {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(220, 38, 38, 0.04) 100%);
            border: 1px solid rgba(239, 68, 68, 0.15);
            color: #fca5a5 !important;
            font-weight: 600;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .sidebar-logout::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, #f87171 0%, #dc2626 100%);
            border-radius: 0 3px 3px 0;
            opacity: 0.6;
            transition: opacity 0.25s;
        }
        .sidebar-logout:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.22) 0%, rgba(220, 38, 38, 0.12) 100%);
            border-color: rgba(239, 68, 68, 0.4);
            color: #fff !important;
            transform: translateX(2px);
            box-shadow:
                0 4px 12px rgba(239, 68, 68, 0.2),
                0 0 0 1px rgba(239, 68, 68, 0.1) inset;
        }
        .sidebar-logout:hover::before {
            opacity: 1;
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.6);
        }
        .sidebar-logout i {
            color: inherit !important;
            transition: transform 0.25s;
        }
        .sidebar-logout:hover i {
            transform: translateX(2px) scale(1.1);
            color: #fff !important;
        }
        .sidebar-logout.justify-center::before { display: none; }

        /* ═══════════════════════════════════════════════════════════
           BOUTON TOGGLE SIDEBAR
           ═══════════════════════════════════════════════════════════ */
        .sidebar-toggle-btn {
            color: #64748b;
            padding: 0.375rem;
            border-radius: 0.5rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .sidebar-toggle-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.25s;
        }
        .sidebar-toggle-btn:hover {
            color: #c7d2fe;
            background: rgba(99, 102, 241, 0.12);
            transform: scale(1.08);
        }
        .sidebar-toggle-btn:hover::before { opacity: 1; }
        .sidebar-toggle-btn i {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-toggle-btn:hover i {
            filter: drop-shadow(0 0 6px rgba(129, 140, 248, 0.7));
        }
        .sidebar-toggle-btn:active {
            transform: scale(0.95);
        }

        .topbar-premium {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.6) inset;
        }
        .topbar-action { transition: all 0.2s; }
        .topbar-action:hover {
            background: rgba(241, 245, 249, 0.8);
            transform: translateY(-1px);
        }

        .content-main {
            background:
                radial-gradient(ellipse 60% 40% at 50% 0%, rgba(99, 102, 241, 0.04), transparent 50%),
                transparent;
            min-width: 0;
            max-width: 100%;
            overflow-x: hidden;
        }

        .content-main > * {
            max-width: 100%;
            min-width: 0;
        }

        /* ✅ FIX : Le scroll horizontal des tables dans le contenu
           est limité au mobile. Sur desktop, on laisse les tables
           natives fonctionner (table-layout: fixed, etc.). */
        @media (max-width: 767px) {
            .content-main table:not(.data-table) {
                display: block;
                overflow-x: auto;
                max-width: 100%;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* ═══════════════════════════════════════════════════════════
           ANIMATIONS DE SCROLL POUR LE CONTENU
           ═══════════════════════════════════════════════════════════ */
        .content-anim {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1),
                        transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: opacity, transform;
        }
        .content-anim.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .content-anim.delay-1 { transition-delay: 0.08s; }
        .content-anim.delay-2 { transition-delay: 0.16s; }
        .content-anim.delay-3 { transition-delay: 0.24s; }
        .content-anim.delay-4 { transition-delay: 0.32s; }
        .content-anim.delay-5 { transition-delay: 0.40s; }

        .content-anim-fade {
            opacity: 0;
            transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .content-anim-fade.is-visible { opacity: 1; }

        .content-anim-left {
            opacity: 0;
            transform: translateX(-20px);
            transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1),
                        transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .content-anim-left.is-visible {
            opacity: 1;
            transform: translateX(0);
        }

        .content-anim-scale {
            opacity: 0;
            transform: scale(0.96);
            transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1),
                        transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .content-anim-scale.is-visible {
            opacity: 1;
            transform: scale(1);
        }

        @media (prefers-reduced-motion: reduce) {
            .content-anim,
            .content-anim-fade,
            .content-anim-left,
            .content-anim-scale {
                opacity: 1 !important;
                transform: none !important;
                transition: none !important;
            }
        }

        /* ═══════════════════════════════════════════════════════════
           BADGES & ALERTES
           ═══════════════════════════════════════════════════════════ */
        .badge-premium {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .alert-premium {
            border-radius: 14px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            animation: slideDownFade 0.35s ease both;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    @stack('styles')
</head>
<body x-cloak class="bg-slate-100 min-h-screen antialiased" x-data="appLayout()" x-init="init()">

    <div class="flex h-screen overflow-hidden" :class="{ 'sidebar-collapsed': sidebarCollapsed }">

        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden"></div>

        {{-- SIDEBAR --}}
        <aside class="sidebar-aside inset-y-0 left-0 z-50 text-white transform transition-all duration-300 ease-in-out lg:translate-x-0 lg:z-auto flex flex-col flex-shrink-0"
               :class="[
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                    sidebarCollapsed ? 'w-20' : 'w-72',
                    'lg:relative'
               ]">

            {{-- LOGO + TOGGLE --}}
            <div class="px-4 py-5 flex items-center"
                 :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">

                <div x-show="!sidebarCollapsed" class="flex items-center gap-3 flex-1 min-w-0">
                    @if($settings->site_logo ?? null)
                        <img src="{{ $settings->logo_url }}" alt="{{ $settings->site_name }}"
                             class="w-11 h-11 rounded-xl object-cover shadow-lg flex-shrink-0">
                    @else
                        <div class="sidebar-logo-mark w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                            {{ strtoupper(substr($settings->site_name ?? 'A', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h1 class="sidebar-brand-text text-lg truncate leading-tight">
                            {{ $settings->site_name ?? 'Application' }}
                        </h1>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-500 mt-0.5">
                            {{ $isAdminUser ? 'Administration' : 'Espace membre' }}
                        </p>
                    </div>
                </div>

                <div x-show="sidebarCollapsed" class="sidebar-logo-mark w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                    {{ strtoupper(substr($settings->site_name ?? 'A', 0, 1)) }}
                </div>

                <button @click="sidebarOpen = false"
                        class="lg:hidden text-slate-500 hover:text-white transition p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>

                <button type="button"
                        @click="toggleSidebar()"
                        x-show="!sidebarCollapsed"
                        class="sidebar-toggle-btn hidden lg:flex"
                        :aria-label="sidebarCollapsed ? 'Ouvrir le menu' : 'Réduire le menu'"
                        :title="sidebarCollapsed ? 'Ouvrir le menu' : 'Réduire le menu'">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
            </div>

            <div x-show="sidebarCollapsed" class="px-2 mb-2 flex justify-center">
                <button type="button"
                        @click="toggleSidebar()"
                        class="sidebar-toggle-btn hidden lg:flex"
                        aria-label="Ouvrir le menu"
                        title="Ouvrir le menu">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>

            {{-- PROFIL --}}
            @if($currentUser)
            <div class="mx-3 mb-4 sidebar-profile rounded-2xl p-3"
                 :class="sidebarCollapsed ? 'px-2' : ''">
                <div class="flex items-center relative z-10"
                     :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                    <div class="profile-avatar w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ strtoupper(substr($userName, 0, 1)) }}
                    </div>
                    <div x-show="!sidebarCollapsed" class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ $userName }}</p>
                        <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $userRole }}</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- RECHERCHE --}}
            <div x-show="!sidebarCollapsed" class="px-4 mb-4">
                <div class="sidebar-search relative rounded-xl">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs pointer-events-none"></i>
                    <input type="text"
                           x-model="searchQuery"
                           @input="filterMenu()"
                           placeholder="Rechercher..."
                           class="w-full pl-10 pr-3 py-2.5 bg-transparent border-0 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none">
                    <kbd x-show="!searchQuery"
                         class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-mono text-slate-500 bg-white/5 border border-white/10 px-1.5 py-0.5 rounded">
                        ⌘K
                    </kbd>
                </div>
            </div>

            {{-- NAVIGATION --}}
            <nav class="sidebar-nav flex-1 overflow-y-auto px-3 pb-3 space-y-1">
                <template x-for="(links, sectionName) in filteredMenu" :key="sectionName">
                    <div :data-section="sectionName">
                        <button @click="toggleSection(sectionName)"
                                class="section-header w-full flex items-center justify-between gap-2 px-3 py-3 rounded-lg"
                                :class="[
                                    sidebarCollapsed ? 'justify-center' : '',
                                    openSections[sectionName] ? 'is-open' : ''
                                ]">
                            <span x-show="!sidebarCollapsed"
                                  class="section-name"
                                  x-text="sectionName"></span>
                            <span x-show="sidebarCollapsed" class="w-6 h-px bg-slate-700"></span>
                            <i x-show="!sidebarCollapsed"
                               class="chevron fa-solid fa-chevron-down text-[10px]"
                               :class="openSections[sectionName] ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="openSections[sectionName] || sidebarCollapsed"
                             x-transition:enter="transition ease-out duration-250 transform origin-top"
                             x-transition:enter-start="opacity-0 scale-y-95"
                             x-transition:enter-end="opacity-100 scale-y-100"
                             x-transition:leave="transition ease-in duration-150 transform origin-top"
                             x-transition:leave-start="opacity-100 scale-y-100"
                             x-transition:leave-end="opacity-0 scale-y-95"
                             class="mt-1 space-y-0.5">
                            <template x-for="link in links" :key="link.route + JSON.stringify(link.params || {})">
                                <a :href="link.url"
                                   class="link-item sidebar-link flex items-center gap-3 py-2.5 px-3 rounded-xl"
                                   :class="{
                                       'sidebar-link-active': link.active,
                                       'menu-item-urgent': link.urgent && !link.active
                                   }"
                                   :title="sidebarCollapsed ? link.label : ''">
                                    <i :class="['fa-solid', 'text-[13px]', 'w-5', 'text-center', link.icon]"></i>
                                    <span x-show="!sidebarCollapsed" x-text="link.label" class="truncate"></span>
                                    <span x-show="sidebarCollapsed" class="sr-only" x-text="link.label"></span>

                                    <template x-if="link.badge && link.badge > 0">
                                        <span x-show="!sidebarCollapsed"
                                              class="ml-auto badge-premium text-white text-[10px] font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center flex-shrink-0"
                                              :class="{ 'badge-urgent': link.urgent }"
                                              x-text="link.badge"></span>
                                    </template>

                                    <template x-if="link.badge && link.badge > 0 && sidebarCollapsed">
                                        <span class="absolute top-0.5 right-0.5 badge-premium text-white text-[9px] font-bold w-4 h-4 flex items-center justify-center rounded-full"
                                              :class="{ 'badge-urgent': link.urgent }"
                                              x-text="link.badge"></span>
                                    </template>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                <div x-show="filteredMenu && Object.keys(filteredMenu).length === 0 && searchQuery.length > 0"
                     class="text-center text-slate-500 text-sm py-8 px-3">
                    <i class="fa-solid fa-magnifying-glass text-xl block mb-3 text-slate-700"></i>
                    <p class="text-xs">Aucun résultat pour</p>
                    <p class="text-xs text-slate-400 font-medium mt-1">"<span x-text="searchQuery"></span>"</p>
                </div>

                <div x-show="filteredMenu && Object.keys(filteredMenu).length === 0 && searchQuery.length === 0"
                     class="text-center text-slate-500 text-sm py-8 px-3">
                    <i class="fa-solid fa-lock text-xl block mb-3 text-slate-700"></i>
                    <p class="text-xs">Aucun menu disponible</p>
                    <p class="text-[10px] mt-2 text-slate-600">Contactez un administrateur</p>
                </div>
            </nav>

            {{-- PIED --}}
            <div class="px-3 py-4 border-t border-white/5">
                @if($currentUser && Route::has($logoutRoute))
                    <form action="{{ route($logoutRoute) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="sidebar-logout w-full flex items-center gap-3 text-sm py-2.5 px-3 rounded-xl"
                                :class="sidebarCollapsed ? 'justify-center' : ''"
                                title="Déconnexion">
                            <i class="fa-solid fa-arrow-right-from-bracket text-[15px] w-5 text-center"></i>
                            <span x-show="!sidebarCollapsed">Déconnexion</span>
                        </button>
                    </form>
                @endif
                <p x-show="!sidebarCollapsed" class="text-[10px] text-slate-600 text-center mt-3 font-medium">
                    © {{ date('Y') }} <span class="text-slate-500">{{ $settings->site_name ?? 'Application' }}</span>
                </p>
            </div>
        </aside>

        {{-- CONTENU PRINCIPAL --}}
        <main class="content-main flex-1 min-w-0 w-full overflow-y-auto overflow-x-hidden" id="contentMain">

            {{-- TOPBAR --}}
            <div class="topbar-premium sticky top-0 z-30 w-full">
                <div class="px-4 lg:px-6 py-3 w-full min-w-0">
                    <div class="flex items-center justify-between gap-4 w-full min-w-0">

                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <button @click="sidebarOpen = true" class="lg:hidden relative text-slate-600 hover:text-slate-900 p-1.5 rounded-lg hover:bg-slate-100 transition flex-shrink-0">
                                <i class="fa-solid fa-bars text-lg"></i>
                                @if($aDesDemandesEnAttente)
                                    <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                                @endif
                            </button>

                            <div class="min-w-0 flex-1">
                                <h1 class="text-base lg:text-lg font-semibold text-slate-800 truncate">
                                    @yield('page_title', $isAdminUser ? 'Administration' : 'Espace membre')
                                </h1>
                                @hasSection('page_subtitle')
                                    <p class="text-xs text-slate-400 mt-0.5 truncate">@yield('page_subtitle')</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if($isAdminUser && $aDesDemandesEnAttente)
                                <a href="{{ route('admin.demandes-avance.index') }}"
                                   class="hidden md:inline-flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200/60 text-red-700 rounded-full text-xs font-bold hover:shadow-lg hover:shadow-red-500/10 transition-all"
                                   title="Demandes d'avance à traiter">
                                    <span class="w-2 h-2 rounded-full bg-red-500 badge-urgent"></span>
                                    {{ $demandesAvanceEnAttente }} en attente
                                </a>
                            @endif

                            @if(Route::has('home'))
                                <a href="{{ route('home') }}" target="_blank"
                                   class="topbar-action hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-500 hover:text-indigo-600 rounded-lg">
                                    <i class="fa-solid fa-globe text-xs"></i>
                                    <span class="hidden lg:inline">Voir le site</span>
                                </a>
                            @endif

                            @if($currentUser)
                                <div class="hidden sm:flex items-center gap-2 pl-3 border-l border-slate-200">
                                    <div class="text-right">
                                        <p class="text-xs font-semibold text-slate-700 leading-tight">{{ $userName }}</p>
                                        <p class="text-[10px] text-slate-400 leading-tight">{{ $userRole }}</p>
                                    </div>
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition-transform hover:scale-110">
                                        {{ strtoupper(substr($userName, 0, 1)) }}
                                    </div>
                                </div>

                                @if(Route::has($logoutRoute))
                                    <form action="{{ route($logoutRoute) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Déconnexion"
                                                class="topbar-action p-2 text-slate-400 hover:text-red-600 rounded-lg">
                                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- MESSAGES FLASH --}}
            <div class="px-4 lg:px-6 pt-6 w-full min-w-0">
                @if(session('success'))
                    <div class="alert-premium mb-4 p-4 bg-emerald-50/80 border border-emerald-200/60 text-emerald-700 flex items-start gap-3 content-anim">
                        <i class="fa-solid fa-circle-check mt-0.5 text-emerald-500 flex-shrink-0"></i>
                        <div class="flex-1 text-sm font-medium min-w-0">{{ session('success') }}</div>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert-premium mb-4 p-4 bg-red-50/80 border border-red-200/60 text-red-700 flex items-start gap-3 content-anim">
                        <i class="fa-solid fa-circle-exclamation mt-0.5 text-red-500 flex-shrink-0"></i>
                        <div class="flex-1 text-sm font-medium min-w-0">{{ session('error') }}</div>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="alert-premium mb-4 p-4 bg-amber-50/80 border border-amber-200/60 text-amber-700 flex items-start gap-3 content-anim">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-500 flex-shrink-0"></i>
                        <div class="flex-1 text-sm font-medium min-w-0">{{ session('warning') }}</div>
                    </div>
                @endif
                @if(session('info'))
                    <div class="alert-premium mb-4 p-4 bg-blue-50/80 border border-blue-200/60 text-blue-700 flex items-start gap-3 content-anim">
                        <i class="fa-solid fa-circle-info mt-0.5 text-blue-500 flex-shrink-0"></i>
                        <div class="flex-1 text-sm font-medium min-w-0">{{ session('info') }}</div>
                    </div>
                @endif
            </div>

            {{-- CONTENU --}}
            <div class="px-4 lg:px-6 pb-8 w-full min-w-0" id="contentWrap">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')

    <script>
        function appLayout() {
            return {
                sidebarOpen: false,
                sidebarCollapsed: false,
                searchQuery: '',
                originalMenu: {!! json_encode($filteredSections, JSON_HEX_APOS | JSON_HEX_QUOT) !!},
                filteredMenu: {},
                openSections: {},
                contentObserver: null,

                init() {
                    document.documentElement.classList.remove('sidebar-collapsed-preload');

                    requestAnimationFrame(() => {
                        document.documentElement.classList.remove('no-alpine');
                        document.documentElement.classList.add('alpine-ready');
                    });

                    const saved = localStorage.getItem('sidebarCollapsed');
                    if (saved !== null) {
                        this.sidebarCollapsed = saved === 'true';
                    }

                    this.filteredMenu = { ...this.originalMenu };
                    this.openActiveSection();
                    this.openUrgentSection();

                    // Init animations du contenu
                    this.$nextTick(() => {
                        this.initContentAnimations();
                    });
                },

                openActiveSection() {
                    this.$nextTick(() => {
                        const activeLink = document.querySelector('a.sidebar-link-active');
                        if (activeLink) {
                            const section = activeLink.closest('[data-section]');
                            if (section) {
                                this.openSections[section.dataset.section] = true;
                            }
                        }
                    });
                },

                openUrgentSection() {
                    Object.entries(this.originalMenu).forEach(([section, links]) => {
                        if (links.some(link => link.urgent && link.badge > 0)) {
                            this.openSections[section] = true;
                        }
                    });
                },

                toggleSidebar() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
                },

                toggleSection(sectionName) {
                    this.openSections[sectionName] = !this.openSections[sectionName];
                },

                filterMenu() {
                    const query = this.searchQuery.toLowerCase().trim();
                    if (!query) {
                        this.filteredMenu = { ...this.originalMenu };
                        return;
                    }
                    const result = {};
                    Object.entries(this.originalMenu).forEach(([section, links]) => {
                        const filtered = links.filter(link =>
                            link.label.toLowerCase().includes(query)
                        );
                        if (filtered.length > 0) {
                            result[section] = filtered;
                            this.openSections[section] = true;
                        }
                    });
                    this.filteredMenu = result;
                },

                /* ═══════════════════════════════════════════════════
                   ANIMATIONS DE SCROLL POUR LE CONTENU
                   ═══════════════════════════════════════════════════ */
                initContentAnimations() {
                    const wrap = document.getElementById('contentWrap');
                    if (!wrap) return;

                    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    if (prefersReduced || !('IntersectionObserver' in window)) {
                        wrap.querySelectorAll('*').forEach(el => {
                            el.classList.add('is-visible');
                        });
                        return;
                    }

                    const selectors = [
                        ':scope > *',
                        ':scope section',
                        ':scope > section > *',
                        ':scope .card',
                        ':scope [class*="card"]:not([class*="card-"])',
                        ':scope [class*="stat"]',
                        ':scope [class*="grid"] > *',
                        ':scope .alert-premium'
                    ];

                    const candidates = new Set();
                    selectors.forEach(sel => {
                        try {
                            wrap.querySelectorAll(sel).forEach(el => {
                                if (el.classList.contains('content-anim')) return;
                                candidates.add(el);
                            });
                        } catch (e) { /* ignore */ }
                    });

                    const targets = Array.from(candidates).filter(el => {
                        if (el.offsetWidth < 40 || el.offsetHeight < 20) return false;
                        return true;
                    });

                    targets.forEach((el, i) => {
                        el.classList.add('content-anim');
                        const delay = (i % 5) + 1;
                        el.classList.add(`delay-${delay}`);
                    });

                    this.contentObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                this.contentObserver.unobserve(entry.target);
                            }
                        });
                    }, {
                        threshold: 0.08,
                        rootMargin: '0px 0px -40px 0px'
                    });

                    targets.forEach(el => this.contentObserver.observe(el));

                    setTimeout(() => {
                        targets.forEach(el => {
                            const rect = el.getBoundingClientRect();
                            if (rect.top < window.innerHeight * 0.9) {
                                el.classList.add('is-visible');
                            }
                        });
                    }, 300);
                }
            }
        }
    </script>
</body>
</html>