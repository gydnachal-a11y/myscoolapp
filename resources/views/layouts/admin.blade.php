<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        use Illuminate\Support\Facades\Log;
        use Illuminate\Support\Facades\Route;

        // ============================================================
        // DÉTECTION DU CONTEXTE
        // ============================================================
        $settings     = \App\Models\SiteSetting::getSettings();
        $webUser      = auth('web')->user();
        $contactUser  = auth('contact')->user();
        $currentUser  = $webUser ?? $contactUser;
        $currentGuard = $webUser ? 'web' : ($contactUser ? 'contact' : null);
        $isContact    = $currentGuard === 'contact';

        $userName    = $currentUser->name  ?? $currentUser->nom  ?? 'Utilisateur';
        $userRole    = $currentUser->role_label ?? $currentUser->role ?? ($isContact ? 'Abonné' : 'Invité');
        $logoutRoute = $isContact ? 'external.logout' : 'logout';

        // ============================================================
        // ✅ PRÉ-CHARGEMENT DES PERMISSIONS (avant $isAdminUser !)
        // ============================================================
        $userPermissions = [];

        if ($currentUser && $currentGuard === 'web') {
            try {
                $userPermissions = $currentUser->getAllPermissionNames();
            } catch (\Throwable $e) {
                Log::warning('Échec getAllPermissionNames() — menu réduit à vide', [
                    'user_id' => $currentUser->id ?? null,
                    'error'   => $e->getMessage(),
                ]);
                $userPermissions = [];
            }
        }

        // ✅ Index inversé pour lookup O(1) dans $canAccessMenu
        $userPermissionsMap = array_flip($userPermissions);

        // ============================================================
        // ✅ SUPER ADMIN (calculé une fois)
        // ============================================================
        $isSuperAdmin = $currentGuard === 'web'
            && $currentUser
            && (
                $currentUser->hasRole('super_admin')
                || (method_exists($currentUser, 'isSuperAdmin') && $currentUser->isSuperAdmin())
            );

        // ============================================================
        // ✅ MENU ADMIN — permissions SANS préfixe (format DB)
        //    (défini AVANT $isAdminUser car on s'en sert pour le détecter)
        // ============================================================
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
            'Abonnés & Communication' => [
                ['route' => 'admin.contacts.index',  'label' => 'Abonnés',                 'icon' => 'fa-users',              'permission' => 'contacts.index'],
                ['route' => 'admin.messages.index',  'label' => 'Messages reçus',          'icon' => 'fa-envelope-open-text', 'permission' => 'messages.index', 'badge' => $unreadMessages ?? 0],
                ['route' => 'admin.emails.compose',  'label' => 'Envoyer un email groupé', 'icon' => 'fa-paper-plane',        'permission' => 'emails.compose'],
            ],
            'Règlement intérieur' => [
                ['route' => 'admin.reglements.index', 'label' => 'Règles & disciplines', 'icon' => 'fa-gavel', 'permission' => 'reglements.index'],
            ],
            'Administration' => [
                ['route' => 'admin.users.index',          'label' => 'Personnel',          'icon' => 'fa-users-cog',   'permission' => 'users.index'],
                ['route' => 'admin.annonces.index',       'label' => 'Annonces',           'icon' => 'fa-bullhorn',    'permission' => 'annonces.index'],
                ['route' => 'admin.site-settings.edit',   'label' => 'Paramètres du site', 'icon' => 'fa-globe',       'permission' => 'site-settings.edit'],
                ['route' => 'admin.settings.canvas.edit', 'label' => 'Éditeur d\'en-tête', 'icon' => 'fa-paint-brush', 'permission' => 'settings.canvas.edit'],
            ],
        ];

        // ============================================================
        // ✅ DÉTECTION ADMIN PAR PERMISSIONS (plus par nom de rôle)
        // ============================================================
        // On extrait toutes les permissions du menu admin, et si l'utilisateur
        // en possède AU MOINS UNE → on le considère comme admin.
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
                || $hasAdminPermission      // ✅ LA CORRECTION CLÉ
            );

        // ============================================================
        // COMPTEURS (dépend maintenant de $isAdminUser corrigé)
        // ============================================================
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

        // ============================================================
        // ✅ HELPER : Vérifie l'accès à un item de menu (O(1))
        // ============================================================
        $canAccessMenu = function (array $link) use ($currentUser, $isSuperAdmin, $userPermissionsMap): bool {
            // Pas de permission requise → toujours visible
            if (empty($link['permission'])) {
                return true;
            }

            // Pas d'utilisateur → invisible
            if (!$currentUser) {
                return false;
            }

            // Super admin → accès total
            if ($isSuperAdmin) {
                return true;
            }

            // ✅ Lookup O(1) grâce à array_flip()
            return isset($userPermissionsMap[$link['permission']]);
        };

        // ============================================================
        // ✅ MENU MEMBRE — permissions SANS préfixe
        // ============================================================
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

        // ============================================================
        // ✅ FILTRAGE FINAL : route existe + permission
        // ============================================================
        $menuSections     = $isAdminUser ? $adminMenuSections : $memberMenuSections;
        $filteredSections = [];

        foreach ($menuSections as $sectionName => $links) {
            $filteredSections[$sectionName] = [];

            foreach ($links as $link) {
                if (!Route::has($link['route'])) {
                    continue;
                }

                if (!$canAccessMenu($link)) {
                    continue;
                }

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }

        @keyframes pulse-red {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            50%      { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        }

        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.15); }
        }

        .badge-urgent { animation: pulse-badge 1.5s ease-in-out infinite; }

        .menu-item-urgent { position: relative; }

        .menu-item-urgent::after {
            content: '';
            position: absolute;
            top: 8px; right: 8px;
            width: 8px; height: 8px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse-red 2s infinite;
        }

        .menu-item-urgent-active {
            border-left: 3px solid #ef4444;
            padding-left: calc(0.75rem - 3px);
        }

        .section-urgent {
            background: rgba(239, 68, 68, 0.1);
            border-left: 2px solid #ef4444;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-100 min-h-screen font-sans antialiased" x-data="appLayout()" x-init="init()">

    <div class="flex h-screen overflow-hidden" :class="{ 'sidebar-collapsed': sidebarCollapsed }">

        {{-- Overlay mobile --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-40 lg:hidden" x-cloak></div>

        {{-- SIDEBAR --}}
        <aside :class="[
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                    sidebarCollapsed ? 'w-20' : 'w-72'
               ]"
               class="fixed inset-y-0 left-0 z-50 bg-gradient-to-b from-slate-900 to-slate-800 text-white transform transition-all duration-300 ease-in-out lg:relative lg:translate-x-0 lg:z-auto flex flex-col shadow-2xl">

            {{-- Logo --}}
            <div class="p-4 border-b border-white/10 flex items-center justify-between"
                 :class="sidebarCollapsed ? 'justify-center' : ''">
                <div x-show="!sidebarCollapsed" class="flex items-center gap-3">
                    @if($settings->site_logo ?? null)
                        <img src="{{ $settings->logo_url }}" alt="{{ $settings->site_name }}"
                             class="w-10 h-10 rounded-xl object-cover">
                    @else
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-indigo-500/30">
                            {{ strtoupper(substr($settings->site_name ?? 'A', 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h1 class="text-xl font-extrabold bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent tracking-tight">
                            {{ $settings->site_name ?? 'Application' }}
                        </h1>
                        <p class="text-slate-400 text-[10px] font-medium uppercase tracking-wider">
                            {{ $isAdminUser ? 'Administration' : 'Espace membre' }}
                        </p>
                    </div>
                </div>
                <div x-show="sidebarCollapsed"
                     class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-indigo-500/30">
                    {{ strtoupper(substr($settings->site_name ?? 'A', 0, 1)) }}
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
                <button @click="toggleSidebar()"
                        class="hidden lg:flex text-slate-400 hover:text-white transition p-1.5 rounded-lg hover:bg-white/5">
                    <i class="fa-solid" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
                </button>
            </div>

            {{-- Profil --}}
            @if($currentUser)
            <div class="p-3 mx-3 mt-3 bg-white/5 rounded-2xl border border-white/10"
                 :class="sidebarCollapsed ? 'px-2' : ''">
                <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center' : 'space-x-3'">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-lg shadow-indigo-500/20 flex-shrink-0">
                        {{ strtoupper(substr($userName, 0, 1)) }}
                    </div>
                    <div x-show="!sidebarCollapsed" class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate">{{ $userName }}</p>
                        <p class="text-xs text-slate-400 truncate capitalize flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
                            {{ $userRole }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Recherche --}}
            <div x-show="!sidebarCollapsed" class="px-4 mt-4">
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                    <input type="text"
                           x-model="searchQuery"
                           @input="filterMenu()"
                           placeholder="Rechercher dans le menu..."
                           class="w-full pl-9 pr-3 py-2 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition">
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto p-3 space-y-2 scrollbar-thin scrollbar-thumb-white/10 scrollbar-track-transparent">
                <template x-for="(links, sectionName) in filteredMenu" :key="sectionName">
                    <div :data-section="sectionName">
                        <button @click="toggleSection(sectionName)"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-colors"
                                :class="sidebarCollapsed ? 'justify-center' : ''">
                            <span x-show="!sidebarCollapsed"
                                  class="uppercase tracking-wider text-[10px] font-semibold"
                                  x-text="sectionName"></span>
                            <span x-show="sidebarCollapsed" class="w-6 h-px bg-slate-600"></span>
                            <i x-show="!sidebarCollapsed"
                               class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                               :class="openSections[sectionName] ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="openSections[sectionName] || sidebarCollapsed"
                             x-transition:enter="transition ease-out duration-200 transform origin-top"
                             x-transition:enter-start="opacity-0 scale-y-95"
                             x-transition:enter-end="opacity-100 scale-y-100"
                             x-transition:leave="transition ease-in duration-150 transform origin-top"
                             x-transition:leave-start="opacity-100 scale-y-100"
                             x-transition:leave-end="opacity-0 scale-y-95"
                             class="mt-1 space-y-0.5">
                            <template x-for="link in links" :key="link.route + JSON.stringify(link.params || {})">
                                <a :href="link.url"
                                   class="flex items-center gap-3 py-2.5 px-3 rounded-xl text-sm font-medium transition-all duration-200 relative"
                                   :class="{
                                       'bg-indigo-600 text-white shadow-lg shadow-indigo-500/25 sidebar-link-active': link.active,
                                       'text-slate-300 hover:bg-white/5 hover:text-white': !link.active,
                                       'menu-item-urgent': link.urgent && !link.active
                                   }"
                                   :title="sidebarCollapsed ? link.label : ''">
                                    <i :class="['fa-solid', 'text-base', 'w-5', 'text-center', link.icon]"></i>
                                    <span x-show="!sidebarCollapsed" x-text="link.label"></span>
                                    <span x-show="sidebarCollapsed" class="sr-only" x-text="link.label"></span>

                                    <template x-if="link.badge && link.badge > 0">
                                        <span x-show="!sidebarCollapsed"
                                              class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"
                                              :class="{ 'badge-urgent': link.urgent }"
                                              x-text="link.badge"></span>
                                    </template>

                                    <template x-if="link.badge && link.badge > 0 && sidebarCollapsed">
                                        <span class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full"
                                              :class="{ 'badge-urgent': link.urgent }"
                                              x-text="link.badge"></span>
                                    </template>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Aucun résultat --}}
                <div x-show="filteredMenu && Object.keys(filteredMenu).length === 0 && searchQuery.length > 0"
                     class="text-center text-slate-500 text-sm py-8">
                    <i class="fa-solid fa-search text-2xl block mb-2 text-slate-600"></i>
                    Aucun résultat pour "<span x-text="searchQuery"></span>"
                </div>

                {{-- Aucun menu disponible --}}
                <div x-show="filteredMenu && Object.keys(filteredMenu).length === 0 && searchQuery.length === 0"
                     class="text-center text-slate-500 text-sm py-8">
                    <i class="fa-solid fa-lock text-2xl block mb-2 text-slate-600"></i>
                    Aucun menu disponible
                    <p class="text-xs mt-1 text-slate-600">Contactez un administrateur.</p>
                </div>
            </nav>

            {{-- Pied --}}
            <div class="p-3 border-t border-white/10">
                @if($currentUser && Route::has($logoutRoute))
                    <form action="{{ route($logoutRoute) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 text-sm text-slate-400 hover:text-white transition py-2.5 px-3 rounded-xl hover:bg-white/5"
                                :class="sidebarCollapsed ? 'justify-center' : ''">
                            <i class="fa-solid fa-right-from-bracket text-base w-5 text-center"></i>
                            <span x-show="!sidebarCollapsed">Déconnexion</span>
                        </button>
                    </form>
                @endif
                <p x-show="!sidebarCollapsed" class="text-[10px] text-slate-500 text-center mt-2">
                    &copy; {{ date('Y') }} {{ $settings->site_name ?? 'Application' }}
                </p>
            </div>
        </aside>

        {{-- CONTENU PRINCIPAL --}}
        <main class="flex-1 overflow-y-auto">

            <div class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200/70 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="lg:hidden text-slate-600 hover:text-slate-900 relative">
                        <i class="fa-solid fa-bars text-xl"></i>
                        @if($aDesDemandesEnAttente)
                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>
                        @endif
                    </button>
                    <div>
                        <h1 class="text-lg font-semibold text-slate-800">
                            @yield('page_title', $isAdminUser ? 'Administration' : 'Espace membre')
                        </h1>
                        <p class="text-xs text-slate-400 mt-0.5">@yield('page_subtitle', '')</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if($isAdminUser && $aDesDemandesEnAttente)
                        <a href="{{ route('admin.demandes-avance.index') }}"
                           class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 border border-red-200 text-red-700 rounded-full text-xs font-bold hover:bg-red-100 transition"
                           title="Demandes d'avance à traiter">
                            <span class="w-2 h-2 rounded-full bg-red-500 badge-urgent"></span>
                            {{ $demandesAvanceEnAttente }} demande{{ $demandesAvanceEnAttente > 1 ? 's' : '' }} en attente
                        </a>
                    @endif

                    @if(Route::has('home'))
                        <a href="{{ route('home') }}" target="_blank"
                           class="hidden sm:inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 transition">
                            <i class="fa-solid fa-globe"></i> Voir le site
                        </a>
                    @endif

                    @if($currentUser)
                        <span class="text-sm text-slate-500 hidden sm:inline">{{ $userName }}</span>
                        @if(Route::has($logoutRoute))
                            <form action="{{ route($logoutRoute) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-gray-500 hover:text-red-600 transition">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <div class="p-4 lg:p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-start gap-2">
                        <i class="fa-regular fa-circle-check mt-0.5"></i>
                        <div class="flex-1">{{ session('success') }}</div>
                        <button @click="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
                        <i class="fa-regular fa-circle-exclamation mt-0.5"></i>
                        <div class="flex-1">{{ session('error') }}</div>
                        <button @click="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg flex items-start gap-2">
                        <i class="fa-regular fa-triangle-exclamation mt-0.5"></i>
                        <div class="flex-1">{{ session('warning') }}</div>
                        <button @click="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif
                @if(session('info'))
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg flex items-start gap-2">
                        <i class="fa-regular fa-circle-info mt-0.5"></i>
                        <div class="flex-1">{{ session('info') }}</div>
                        <button @click="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

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
                // ✅ Une seule sérialisation JSON (economie de payload)
                originalMenu: {!! json_encode($filteredSections, JSON_HEX_APOS | JSON_HEX_QUOT) !!},
                filteredMenu: {},
                openSections: {},

                init() {
                    // Copie initiale — on ne mute jamais originalMenu
                    this.filteredMenu = { ...this.originalMenu };

                    const saved = localStorage.getItem('sidebarCollapsed');
                    if (saved !== null) {
                        this.sidebarCollapsed = saved === 'true';
                    }
                    this.openActiveSection();
                    this.openUrgentSection();
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
                }
            }
        }
    </script>
</body>
</html>