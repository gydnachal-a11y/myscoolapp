@extends('layouts.admin')

@section('page_title', 'Info Paiements')
@section('page_subtitle', 'Journal des paiements scolaires et frais supplémentaires')

@section('content')
@php
    /* ---------- Formules ---------- */
    $fmtUsd = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $fmtFc  = fn ($v) => number_format((float) $v, 0, ',', ' ');

    /* ---------- Filtres actifs ? ---------- */
    $hasFilters = collect(['session', 'section', 'salle', 'option', 'periode', 'statut', 'recherche'])
        ->contains(fn ($k) => request()->filled($k));

    /* ---------- Groupes de périodes (sans salle choisie) ---------- */
    $moisParType     = collect($periodes)->where('type_periode', 'mensuel');
    $tranchesParType = collect($periodes)->where('type_periode', 'tranche');

    /* ---------- Valeurs courantes (null-safe) ---------- */
    $currentSession = request('session');
    $currentSection = request('section');
    $currentSalle   = request('salle');
    $currentOption  = request('option');
    $currentPeriode = request('periode');
    $currentStatut  = request('statut');

    /* ---------- URLs d'export (précalculées pour les 2 types) ---------- */
    $baseQuery = request()->query();

    $urlExport = function (string $routeName, string $type) use ($baseQuery): string {
        return route($routeName, array_merge($baseQuery, ['type' => $type]));
    };

    $exportUrls = [
        'principaux' => [
            'pdf'   => $urlExport('admin.info-paiements.export.pdf',  'principaux'),
            'csv'   => $urlExport('admin.info-paiements.export.csv',  'principaux'),
            'xml'   => $urlExport('admin.info-paiements.export.xml',  'principaux'),
            'word'  => $urlExport('admin.info-paiements.export.word', 'principaux'),
            'print' => $urlExport('admin.info-paiements.imprimer',    'principaux'),
        ],
        'frais' => [
            'pdf'   => $urlExport('admin.info-paiements.export.pdf',  'frais'),
            'csv'   => $urlExport('admin.info-paiements.export.csv',  'frais'),
            'xml'   => $urlExport('admin.info-paiements.export.xml',  'frais'),
            'word'  => $urlExport('admin.info-paiements.export.word', 'frais'),
            'print' => $urlExport('admin.info-paiements.imprimer',    'frais'),
        ],
    ];
@endphp

{{-- ═══════════════════════════════════════════════════════════════
     ROOT — x-data unique pour partager activeTab partout
═══════════════════════════════════════════════════════════════ --}}
<div class="page" x-data="{ activeTab: 'principaux' }">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    {{-- ═══════════════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════════════ --}}
    <header class="page-header" data-reveal="auto">
        <div class="page-header-main">
            <h1 class="page-title">
                <i class="fa-solid fa-file-invoice-dollar title-icon" aria-hidden="true"></i>
                <span class="page-title-text">Info Paiements</span>
            </h1>
            <p class="page-subtitle">Journal des paiements scolaires et frais supplémentaires</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.paiements.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Paiements</span>
            </a>
            <a href="{{ route('admin.paiement-frais-supplementaires.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                <span>Frais supp.</span>
            </a>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════
         STATS
    ═══════════════════════════════════════════════════════════ --}}
    <section class="stats-grid">
        <article class="stat-card stat-indigo" data-reveal="auto" data-delay="1">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Paiements principaux</p>
                <p class="stat-value">{{ number_format($totalPaiements, 0, ',', ' ') }}</p>
            </div>
        </article>

        <article class="stat-card stat-violet" data-reveal="auto" data-delay="2">
            <div class="stat-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Frais supplémentaires</p>
                <p class="stat-value">{{ number_format($totalFraisSupp, 0, ',', ' ') }}</p>
            </div>
        </article>

        <article class="stat-card stat-success" data-reveal="auto" data-delay="3">
            <div class="stat-icon"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Total payé (principal)</p>
                <p class="stat-value">${{ $fmtUsd($totalPayePrincipal) }}</p>
                <p class="stat-unit">{{ $fmtFc($totalPayePrincipalFC) }} FC</p>
            </div>
        </article>

        <article class="stat-card stat-cyan" data-reveal="auto" data-delay="4">
            <div class="stat-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Total payé (frais supp.)</p>
                <p class="stat-value">${{ $fmtUsd($totalPayeFraisSupp) }}</p>
                <p class="stat-unit">{{ $fmtFc($totalPayeFraisSuppFC) }} FC</p>
            </div>
        </article>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         TOOLBAR EXPORTS — dynamique selon l'onglet
         ✅ Mobile : PDF/CSV/XML/Word en 2 cols + Imprimer pleine largeur
    ═══════════════════════════════════════════════════════════ --}}
    <section class="toolbar no-print" data-reveal="auto" data-delay="1">
        <div class="toolbar-info">
            <i class="fa-solid fa-download" aria-hidden="true"></i>
            <span class="toolbar-label">
                Exporter
                <span class="toolbar-context"
                      x-text="activeTab === 'frais' ? 'les frais supplémentaires' : 'les paiements principaux'"></span>
                :
            </span>
        </div>

        <div class="toolbar-group export-buttons">
            <a :href="activeTab === 'frais'
                    ? '{{ $exportUrls['frais']['pdf'] }}'
                    : '{{ $exportUrls['principaux']['pdf'] }}'"
               class="export-btn pdf">
                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> PDF
            </a>

            <a :href="activeTab === 'frais'
                    ? '{{ $exportUrls['frais']['csv'] }}'
                    : '{{ $exportUrls['principaux']['csv'] }}'"
               class="export-btn csv">
                <i class="fa-solid fa-file-csv" aria-hidden="true"></i> CSV
            </a>

            <a :href="activeTab === 'frais'
                    ? '{{ $exportUrls['frais']['xml'] }}'
                    : '{{ $exportUrls['principaux']['xml'] }}'"
               class="export-btn xml">
                <i class="fa-solid fa-file-code" aria-hidden="true"></i> XML
            </a>

            <a :href="activeTab === 'frais'
                    ? '{{ $exportUrls['frais']['word'] }}'
                    : '{{ $exportUrls['principaux']['word'] }}'"
               class="export-btn doc">
                <i class="fa-solid fa-file-word" aria-hidden="true"></i> Word
            </a>

            <a :href="activeTab === 'frais'
                    ? '{{ $exportUrls['frais']['print'] }}'
                    : '{{ $exportUrls['principaux']['print'] }}'"
               target="_blank"
               class="export-btn print">
                <i class="fa-solid fa-print" aria-hidden="true"></i> Imprimer
            </a>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         FILTRES — Cascade Session → Section → Salle → Option → Période → Statut
    ═══════════════════════════════════════════════════════════ --}}
    <section class="filter-card no-print" data-reveal="auto" data-delay="2">
        <div class="filter-header">
            <div class="filter-header-left">
                <i class="fa-solid fa-sliders filter-icon" aria-hidden="true"></i>
                <span class="filter-title">Filtres</span>
                @if($hasFilters)
                    <span class="filter-count">actifs</span>
                @endif
            </div>
        </div>

        <div class="filter-body">
            <form method="GET" action="{{ route('admin.info-paiements.index') }}"
                  class="filter-form" data-filter-form>
                <div class="filter-grid">

                    {{-- 1. SESSION --}}
                    <div class="filter-field">
                        <label for="f-session" class="filter-label">
                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                            Session
                        </label>
                        <select name="session" id="f-session" class="filter-select">
                            <option value="">Toutes les sessions</option>
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}" @selected($currentSession == $s->id)>
                                    {{ $s->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 2. SECTION --}}
                    <div class="filter-field">
                        <label for="f-section" class="filter-label">
                            <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                            Section
                        </label>
                        <select name="section" id="f-section" class="filter-select">
                            <option value="">Toutes les sections</option>
                            @foreach($sections as $s)
                                <option value="{{ $s->id }}"
                                        data-session-id="{{ $s->session_id ?? '' }}"
                                        @selected($currentSection == $s->id)>
                                    {{ $s->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. SALLE --}}
                    <div class="filter-field">
                        <label for="f-salle" class="filter-label">
                            <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i>
                            Salle
                            @if($salle && $typePeriode)
                                <span class="badge-mode badge-mode-{{ $typePeriode }}">
                                    <i class="fa-solid {{ $typePeriode === 'tranche' ? 'fa-layer-group' : 'fa-calendar-check' }}" aria-hidden="true"></i>
                                    {{ $typePeriode === 'tranche' ? 'Tranches' : 'Mensuel' }}
                                </span>
                            @endif
                        </label>
                        <select name="salle" id="f-salle" class="filter-select">
                            <option value="">Toutes les salles</option>
                            @foreach($salles as $s)
                                <option value="{{ $s->id }}"
                                        data-section-id="{{ $s->section_id ?? '' }}"
                                        data-option-id="{{ $s->option_id ?? '' }}"
                                        data-mode-paiement="{{ $s->mode_paiement ?? '' }}"
                                        @selected($currentSalle == $s->id)>
                                    {{ $s->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 4. OPTION --}}
                    <div class="filter-field">
                        <label for="f-option" class="filter-label">
                            <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                            Option
                            @if($salle && empty($salle->option_id))
                                <span class="badge-mode badge-mode-neutral">
                                    <i class="fa-solid fa-minus" aria-hidden="true"></i> Salle sans option
                                </span>
                            @endif
                        </label>
                        <select name="option" id="f-option" class="filter-select"
                                @if($salle && empty($salle->option_id)) disabled @endif>
                            <option value="">
                                @if($salle && empty($salle->option_id))
                                    — Aucune option associée —
                                @else
                                    Toutes les options
                                @endif
                            </option>
                            @foreach($options as $o)
                                <option value="{{ $o->id }}"
                                        data-section-id="{{ $o->section_id ?? '' }}"
                                        @selected($currentOption == $o->id)>
                                    {{ $o->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 5. PÉRIODE --}}
                    <div class="filter-field">
                        <label for="f-periode" class="filter-label">
                            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                            Période
                            @if($salle && $periodesIdsDisponibles->isNotEmpty())
                                <span class="filter-hint">
                                    {{ $periodesIdsDisponibles->count() }} encaissée(s)
                                </span>
                            @endif
                        </label>
                        <select name="periode" id="f-periode" class="filter-select"
                                @if($salle && $periodesIdsDisponibles->isEmpty()) disabled @endif>
                            <option value="">
                                @if($salle && $periodesIdsDisponibles->isEmpty())
                                    — Aucun paiement —
                                @else
                                    Toutes les périodes
                                @endif
                            </option>

                            @if(!$salle)
                                @if($moisParType->isNotEmpty())
                                    <optgroup label="📅 Mois">
                                        @foreach($moisParType as $p)
                                            <option value="{{ $p->id }}" @selected($currentPeriode == $p->id)>
                                                {{ $p->nom }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if($tranchesParType->isNotEmpty())
                                    <optgroup label="📚 Tranches">
                                        @foreach($tranchesParType as $p)
                                            <option value="{{ $p->id }}" @selected($currentPeriode == $p->id)>
                                                {{ $p->nom }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @else
                                @foreach($periodes as $p)
                                    <option value="{{ $p->id }}" @selected($currentPeriode == $p->id)>
                                        {{ $p->nom }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- 6. STATUT --}}
                    <div class="filter-field">
                        <label for="f-statut" class="filter-label">
                            <i class="fa-solid fa-flag" aria-hidden="true"></i>
                            Statut
                        </label>
                        <select name="statut" id="f-statut" class="filter-select">
                            <option value="">Tous les statuts</option>
                            <option value="paye"    @selected($currentStatut === 'paye')>✅ Payé (complet)</option>
                            <option value="partiel" @selected($currentStatut === 'partiel')>⚠️ Payé en partie</option>
                            <option value="impaye"  @selected($currentStatut === 'impaye')>❌ Impayé</option>
                            <option value="surpaye" @selected($currentStatut === 'surpaye')>🔵 Surpayé</option>
                        </select>
                    </div>

                    {{-- 7. RECHERCHE --}}
                    <div class="filter-field filter-field-wide">
                        <label for="f-recherche" class="filter-label">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            Recherche
                        </label>
                        <input type="search" name="recherche" id="f-recherche"
                               value="{{ request('recherche') }}"
                               placeholder="Nom ou prénom de l'élève…"
                               class="filter-input">
                    </div>
                </div>

                <div class="filter-actions">
                    @if($hasFilters)
                        <a href="{{ route('admin.info-paiements.index') }}" class="btn btn-ghost">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Réinitialiser
                        </a>
                    @endif
                    <button type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Appliquer
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         TABS
    ═══════════════════════════════════════════════════════════ --}}
    <nav class="tabs no-print" role="tablist" data-reveal="auto" data-delay="3">
        <button type="button" role="tab"
                class="tab"
                :class="{ 'is-active': activeTab === 'principaux' }"
                :aria-selected="activeTab === 'principaux'"
                @click="activeTab = 'principaux'">
            <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
            <span>Paiements principaux</span>
            <span class="tab-count">{{ $paiementsPrincipaux->total() }}</span>
        </button>
        <button type="button" role="tab"
                class="tab"
                :class="{ 'is-active': activeTab === 'frais' }"
                :aria-selected="activeTab === 'frais'"
                @click="activeTab = 'frais'">
            <i class="fa-solid fa-receipt" aria-hidden="true"></i>
            <span>Frais supplémentaires</span>
            <span class="tab-count">{{ $fraisSupplementaires->total() }}</span>
        </button>
    </nav>

    {{-- ═══════════════════════════════════════════════════════════
         TAB 1 : Paiements principaux
    ═══════════════════════════════════════════════════════════ --}}
    <section x-show="activeTab === 'principaux'" x-cloak class="content-card" data-reveal="auto" data-delay="4">
        <div class="content-header">
            <h2 class="content-title">
                <i class="fa-solid fa-list title-icon-sm" aria-hidden="true"></i>
                Paiements principaux
                <span class="count-badge">{{ $paiementsPrincipaux->total() }}</span>
            </h2>
        </div>

        @if($paiementsPrincipaux->count())
            <div class="table-wrapper">
                <table class="data-table table-principaux">
                    <thead>
                        <tr>
                            <th scope="col">Élève</th>
                            <th scope="col" class="text-right">Payé</th>
                            <th scope="col" class="text-right">Restant</th>
                            <th scope="col" class="text-center">Statut</th>
                            <th scope="col" class="text-center">Date</th>
                            <th scope="col" class="text-right no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paiementsPrincipaux as $p)
                            @php
                                $initiale = strtoupper(mb_substr($p->eleve?->nom ?? '?', 0, 1));
                                $meta = match($p->statut) {
                                    'paye'    => ['label' => 'Payé',    'icon' => 'fa-circle-check',       'class' => 'success'],
                                    'partiel' => ['label' => 'Partiel', 'icon' => 'fa-circle-half-stroke', 'class' => 'warning'],
                                    'impaye'  => ['label' => 'Impayé',  'icon' => 'fa-circle-xmark',       'class' => 'danger'],
                                    'surpaye' => ['label' => 'Surpayé', 'icon' => 'fa-circle-up',          'class' => 'info'],
                                    default   => ['label' => $p->statut ?? '—', 'icon' => 'fa-circle', 'class' => 'neutral'],
                                };
                            @endphp
                            <tr>
                                <td data-label="Élève" class="cell-employe">
                                    <div class="eleve-cell">
                                        <div class="eleve-avatar">{{ $initiale }}</div>
                                        <div class="eleve-meta">
                                            <span class="cell-primary">{{ $p->eleve?->nom_complet ?? '—' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Payé" class="cell-amount text-right">
                                    <span class="amount-label">Payé</span>
                                    <span class="amount-usd amount-success">${{ $fmtUsd($p->montant_paye_usd) }}</span>
                                    <span class="amount-fc">{{ $fmtFc($p->montant_paye_fc) }} FC</span>
                                </td>
                                <td data-label="Restant" class="cell-amount text-right">
                                    <span class="amount-label">Restant</span>
                                    @if(($p->montant_restant_usd ?? 0) > 0)
                                        <span class="amount-usd amount-danger">${{ $fmtUsd($p->montant_restant_usd) }}</span>
                                        <span class="amount-fc">{{ $fmtFc($p->montant_restant_fc) }} FC</span>
                                    @else
                                        <span class="amount-usd text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Statut" class="cell-statut text-center">
                                    <span class="pill pill-{{ $meta['class'] }}">
                                        <i class="fa-solid {{ $meta['icon'] }}" aria-hidden="true"></i>
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td data-label="Date" class="cell-date text-center">
                                    <span class="date-value">{{ $p->date_paiement?->format('d/m/Y') ?? '—' }}</span>
                                </td>
                                <td data-label="Action" class="cell-actions text-right no-print">
                                    <a href="{{ route('admin.info-paiements.recu.paiement', $p->id) }}"
                                       class="action-btn" title="Reçu">
                                        <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                                        <span class="action-label">Reçu</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($paiementsPrincipaux->hasPages())
                <div class="pagination-wrapper no-print">{{ $paiementsPrincipaux->links() }}</div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-icon-wrapper"><i class="fa-regular fa-folder-open" aria-hidden="true"></i></div>
                <h3 class="empty-title">Aucun paiement trouvé</h3>
                <p class="empty-text">Aucun paiement principal ne correspond à vos filtres.</p>
            </div>
        @endif
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         TAB 2 : Frais supplémentaires
    ═══════════════════════════════════════════════════════════ --}}
    <section x-show="activeTab === 'frais'" x-cloak class="content-card">
        <div class="content-header">
            <h2 class="content-title">
                <i class="fa-solid fa-list title-icon-sm" aria-hidden="true"></i>
                Frais supplémentaires
                <span class="count-badge">{{ $fraisSupplementaires->total() }}</span>
            </h2>
        </div>

        @if($fraisSupplementaires->count())
            <div class="table-wrapper">
                <table class="data-table table-frais">
                    <thead>
                        <tr>
                            <th scope="col">Élève</th>
                            <th scope="col">Frais</th>
                            <th scope="col" class="text-right">Payé</th>
                            <th scope="col" class="text-center">Date</th>
                            <th scope="col" class="text-right no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fraisSupplementaires as $f)
                            @php $initiale = strtoupper(mb_substr($f->eleve?->nom ?? '?', 0, 1)); @endphp
                            <tr>
                                <td data-label="Élève" class="cell-employe">
                                    <div class="eleve-cell">
                                        <div class="eleve-avatar">{{ $initiale }}</div>
                                        <div class="eleve-meta">
                                            <span class="cell-primary">{{ $f->eleve?->nom_complet ?? '—' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Frais" class="cell-frais">
                                    <span class="pill pill-indigo">
                                        <i class="fa-solid fa-tag" aria-hidden="true"></i>
                                        {{ $f->fraisSupplementaire?->libelle ?? '—' }}
                                    </span>
                                </td>
                                <td data-label="Payé" class="cell-amount text-right">
                                    <span class="amount-label">Payé</span>
                                    <span class="amount-usd amount-success">${{ $fmtUsd($f->montant_paye_usd) }}</span>
                                    <span class="amount-fc">{{ $fmtFc($f->montant_paye_fc) }} FC</span>
                                </td>
                                <td data-label="Date" class="cell-date text-center">
                                    <span class="date-value">{{ $f->date_paiement?->format('d/m/Y') ?? '—' }}</span>
                                </td>
                                <td data-label="Action" class="cell-actions text-right no-print">
                                    <a href="{{ route('admin.info-paiements.recu.frais', $f->id) }}"
                                       class="action-btn" title="Reçu">
                                        <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                                        <span class="action-label">Reçu</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($fraisSupplementaires->hasPages())
                <div class="pagination-wrapper no-print">{{ $fraisSupplementaires->links() }}</div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-icon-wrapper"><i class="fa-regular fa-folder-open" aria-hidden="true"></i></div>
                <h3 class="empty-title">Aucun frais supplémentaire</h3>
                <p class="empty-text">Aucun frais supplémentaire ne correspond à vos filtres.</p>
            </div>
        @endif
    </section>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════════ --}}
<style>
    /* ============================================================
       TOKENS
    ============================================================ */
    .page {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        --c-primary: #6366f1;
        --c-primary-dark: #4f46e5;
        --c-ink-900: #0f172a;
        --c-ink-700: #334155;
        --c-ink-600: #475569;
        --c-ink-500: #64748b;
        --c-ink-400: #94a3b8;
        --c-gray-100: #f1f5f9;
        --c-gray-200: #e2e8f0;
        --c-gray-50: #f8fafc;
        --c-white: #ffffff;

        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-xl: 20px;
        --radius-full: 9999px;

        --shadow-sm: 0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04);
        --shadow-md: 0 8px 20px rgba(15,23,42,.06), 0 2px 4px rgba(15,23,42,.04);
        --shadow-lg: 0 16px 32px rgba(15,23,42,.08), 0 4px 8px rgba(15,23,42,.04);

        --ease-out-expo: cubic-bezier(.16, 1, .3, 1);
        --ease-soft: cubic-bezier(.4, 0, .2, 1);

        max-width: 1320px;
        margin: 0 auto;
        padding: clamp(1rem, 2.5vw, 2rem) clamp(.75rem, 2vw, 1.25rem);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .page *,
    .page *::before,
    .page *::after { box-sizing: border-box; }

    .page h1,
    .page h2,
    .page h3 {
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        letter-spacing: -0.02em;
    }

    .cell-amount .amount-label { display: none; }

    /* ============================================================
       HEADER
    ============================================================ */
    .page-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1.25rem;
        margin-bottom: clamp(1.5rem, 3vw, 2.25rem);
    }
    @media (min-width: 768px) {
        .page-header {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }
    .page-header-main { min-width: 0; width: 100%; }
    @media (min-width: 768px) { .page-header-main { width: auto; } }

    .page-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.6rem;
        font-size: clamp(1.4rem, 3vw, 1.85rem);
        font-weight: 800;
        color: var(--c-ink-900);
        margin: 0 0 0.25rem;
        line-height: 1.15;
    }
    .page-title-text { overflow: hidden; text-overflow: ellipsis; }
    .title-icon    { color: var(--c-primary); }
    .title-icon-sm { color: var(--c-primary); font-size: 1rem; }
    .page-subtitle { color: var(--c-ink-500); font-size: clamp(.85rem, 1.4vw, .95rem); margin: 0; line-height: 1.5; }

    .header-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        width: 100%;
    }
    @media (min-width: 768px) { .header-actions { width: auto; } }

    /* ============================================================
       BOUTONS
    ============================================================ */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.35rem;
        min-height: 46px;
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 400ms var(--ease-out-expo);
        white-space: nowrap;
        text-align: center;
    }
    @media (max-width: 767px) { .btn { flex: 1 1 calc(50% - 0.3rem); min-width: 0; } }
    @media (max-width: 420px) { .btn { flex: 1 1 100%; } }

    .btn-secondary {
        background: var(--c-ink-900);
        color: #fff;
        box-shadow: var(--shadow-sm);
    }
    .btn-secondary:hover {
        background: var(--c-primary);
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(99,102,241,.35);
    }
    .btn-ghost {
        background: #fff;
        color: var(--c-ink-500);
        border: 1.5px solid var(--c-gray-200);
    }
    .btn-ghost:hover {
        border-color: var(--c-primary);
        color: var(--c-primary-dark);
        background: var(--c-gray-50);
        transform: translateY(-1px);
    }

    /* ============================================================
       STATS
    ============================================================ */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: clamp(.75rem, 1.5vw, 1rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 1.75rem);
    }
    @media (min-width: 640px)  { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }

    .stat-card {
        display: flex;
        align-items: center;
        gap: clamp(.75rem, 1.5vw, 1rem);
        padding: clamp(1rem, 2vw, 1.2rem) clamp(1rem, 2vw, 1.25rem);
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-gray-100);
        box-shadow: var(--shadow-sm);
        transition: transform 400ms var(--ease-out-expo), box-shadow 400ms var(--ease-out-expo);
        min-width: 0;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon {
        width: clamp(42px, 5vw, 48px);
        height: clamp(42px, 5vw, 48px);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: clamp(1rem, 2vw, 1.2rem);
        flex-shrink: 0;
        transition: transform 400ms var(--ease-out-expo);
    }
    .stat-card:hover .stat-icon { transform: scale(1.08) rotate(-5deg); }

    .stat-indigo  .stat-icon { background: #eef2ff; color: #4f46e5; }
    .stat-violet  .stat-icon { background: #f5f3ff; color: #7c3aed; }
    .stat-success .stat-icon { background: #ecfdf5; color: #059669; }
    .stat-cyan    .stat-icon { background: #ecfeff; color: #0891b2; }

    .stat-content { min-width: 0; flex: 1; }
    .stat-label {
        font-size: clamp(.68rem, 1.2vw, .72rem);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
        color: var(--c-ink-400);
        margin: 0 0 0.15rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .stat-value {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: clamp(1.1rem, 2.5vw, 1.4rem);
        font-weight: 800;
        color: var(--c-ink-900);
        line-height: 1.15;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: -0.02em;
    }
    .stat-unit {
        display: block;
        font-size: clamp(.68rem, 1.2vw, .72rem);
        color: var(--c-ink-400);
        font-weight: 500;
        margin-top: 0.15rem;
    }

    /* ============================================================
       TOOLBAR EXPORTS
       ✅ Mobile : 2 cols + Imprimer pleine largeur
       ✅ Desktop : flex-row
    ============================================================ */
    .toolbar {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
        padding: 1rem 1.25rem;
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-gray-100);
        box-shadow: var(--shadow-sm);
        margin-bottom: clamp(1rem, 2vw, 1.5rem);
    }
    @media (min-width: 768px) {
        .toolbar {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }
    .toolbar-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--c-ink-500);
    }
    .toolbar-info i { color: var(--c-primary); }
    .toolbar-label { font-weight: 600; }
    .toolbar-context { color: var(--c-primary-dark); font-weight: 700; }

    /* ✅ Grille export adaptée à chaque breakpoint */
    .toolbar-group.export-buttons {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
        width: 100%;
    }
    @media (min-width: 768px) {
        .toolbar-group.export-buttons {
            display: flex;
            flex-wrap: wrap;
            width: auto;
            gap: 0.4rem;
        }
    }
    @media (max-width: 360px) {
        .toolbar-group.export-buttons {
            grid-template-columns: 1fr;
        }
    }

    .export-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.7rem 0.9rem;
        min-height: 44px;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 700;
        color: #fff;
        text-decoration: none;
        transition: all 300ms var(--ease-out-expo);
        border: none;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    @media (min-width: 768px) {
        .export-btn {
            width: auto;
            padding: 0.6rem 0.95rem;
            min-height: 40px;
            min-width: 84px;
        }
    }
    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,.18);
        filter: brightness(1.08);
    }
    .export-btn.pdf   { background: #dc2626; }
    .export-btn.csv   { background: #16a34a; }
    .export-btn.xml   { background: #2563eb; }
    .export-btn.doc   { background: #0891b2; }
    .export-btn.print { background: #475569; }

    /* ✅ Bouton Imprimer pleine largeur sur mobile */
    @media (max-width: 767px) {
        .export-btn.print {
            grid-column: 1 / -1;
            min-height: 46px;
            font-size: 0.82rem;
        }
    }
    @media (max-width: 360px) {
        .export-btn.print { grid-column: auto; }
    }

    /* ============================================================
       FILTRES
    ============================================================ */
    .filter-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-gray-100);
        box-shadow: var(--shadow-sm);
        margin-bottom: clamp(1rem, 2vw, 1.5rem);
        overflow: hidden;
    }
    .filter-header { padding: 1rem 1.25rem 0; }
    .filter-header-left {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }
    .filter-icon { color: var(--c-primary); }
    .filter-title { font-weight: 700; color: var(--c-ink-900); font-size: 0.95rem; }
    .filter-count {
        background: #eef2ff;
        color: #4f46e5;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: var(--radius-full);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .filter-body { padding: 1rem 1.25rem 1.25rem; }
    .filter-form { display: flex; flex-direction: column; gap: 1rem; }
    .filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 640px)  { .filter-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .filter-grid { grid-template-columns: repeat(3, 1fr); } }

    .filter-field { display: flex; flex-direction: column; gap: 0.35rem; }
    .filter-field-wide { grid-column: span 2; }
    @media (max-width: 1023px) { .filter-field-wide { grid-column: span 1; } }

    .filter-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--c-ink-600);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .filter-label > i { color: var(--c-primary); font-size: 0.75rem; }

    .filter-hint {
        font-size: 0.65rem;
        font-weight: 700;
        background: #ecfdf5;
        color: #059669;
        padding: 0.1rem 0.45rem;
        border-radius: var(--radius-full);
        text-transform: none;
        letter-spacing: 0;
    }

    .badge-mode {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.62rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: var(--radius-full);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        line-height: 1;
    }
    .badge-mode i { font-size: 0.7rem; }
    .badge-mode-mensuel { background: #eef2ff; color: #4338ca; }
    .badge-mode-tranche { background: #f5f3ff; color: #6d28d9; }
    .badge-mode-neutral { background: #f1f5f9; color: #64748b; }

    .filter-select,
    .filter-input {
        width: 100%;
        padding: 0.8rem 1rem;
        min-height: 46px;
        border: 1.5px solid var(--c-gray-200);
        border-radius: var(--radius-md);
        background: var(--c-gray-50);
        font-family: inherit;
        font-size: 0.9rem;
        color: var(--c-ink-900);
        transition: all 300ms var(--ease-soft);
        outline: none;
        appearance: none;
        -webkit-appearance: none;
    }
    .filter-select {
        padding-right: 2.5rem;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.7rem center;
        background-size: 1.1rem;
    }
    .filter-select:disabled,
    .filter-input:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        background-color: var(--c-gray-100);
    }
    .filter-select:focus,
    .filter-input:focus {
        border-color: var(--c-primary);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12);
    }

    .filter-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        padding-top: 0.75rem;
        border-top: 1px dashed var(--c-gray-200);
        flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .filter-actions { flex-direction: column-reverse; }
        .filter-actions .btn { width: 100%; flex: 1 1 100%; }
    }

    /* ============================================================
       TABS
    ============================================================ */
    .tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid var(--c-gray-100);
        padding-bottom: 0.5rem;
        flex-wrap: wrap;
    }
    .tab {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1rem;
        border-radius: 10px;
        font-family: inherit;
        font-weight: 600;
        font-size: 0.9rem;
        background: transparent;
        border: none;
        cursor: pointer;
        color: var(--c-ink-500);
        transition: all 300ms var(--ease-soft);
    }
    .tab:hover { background: var(--c-gray-50); color: var(--c-ink-900); }
    .tab.is-active { background: #eef2ff; color: #4f46e5; }
    .tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 20px;
        padding: 0 0.4rem;
        background: var(--c-gray-100);
        color: var(--c-ink-500);
        border-radius: var(--radius-full);
        font-size: 0.72rem;
        font-weight: 700;
    }
    .tab.is-active .tab-count { background: #c7d2fe; color: #4338ca; }
    @media (max-width: 640px) {
        .tabs { border-bottom: none; }
        .tab { flex: 1 1 100%; justify-content: space-between; }
    }

    /* ============================================================
       CONTENT CARD
    ============================================================ */
    .content-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-gray-100);
        box-shadow: var(--shadow-sm);
        margin-bottom: clamp(1rem, 2vw, 1.5rem);
        overflow: hidden;
        width: 100%;
    }
    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--c-gray-100);
        flex-wrap: wrap;
    }
    .content-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: var(--c-ink-900);
        margin: 0;
        min-width: 0;
    }
    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 26px;
        padding: 0 0.6rem;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: var(--radius-full);
        font-size: 0.78rem;
        font-weight: 700;
    }

    /* ============================================================
       TABLEAU — Fix desktop
    ============================================================ */
    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        color: var(--c-ink-600);
    }
    .data-table thead th {
        text-align: left;
        padding: 1rem 1.25rem;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--c-ink-400);
        background: var(--c-gray-50);
        border-bottom: 1.5px solid var(--c-gray-200);
        white-space: nowrap;
    }
    .data-table tbody td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--c-gray-100);
        vertical-align: middle;
    }
    .data-table tbody tr { transition: background 250ms ease; }
    .data-table tbody tr:hover { background: var(--c-gray-50); }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .text-right  { text-align: right !important; }
    .text-center { text-align: center !important; }
    .text-muted  { color: #cbd5e1; }
    .cell-primary { font-weight: 600; color: var(--c-ink-900); }

    /* ✅ DESKTOP : distribution explicite des colonnes */
    @media (min-width: 1024px) {
        .table-wrapper { overflow-x: visible; }

        .data-table {
            table-layout: fixed;
            min-width: 0;
        }

        /* Table principaux : 6 colonnes = 100% */
        .table-principaux thead th:nth-child(1) { width: 22%; } /* Élève */
        .table-principaux thead th:nth-child(2) { width: 18%; } /* Payé */
        .table-principaux thead th:nth-child(3) { width: 18%; } /* Restant */
        .table-principaux thead th:nth-child(4) { width: 14%; } /* Statut */
        .table-principaux thead th:nth-child(5) { width: 12%; } /* Date */
        .table-principaux thead th:nth-child(6) { width: 16%; } /* Action */

        /* Table frais : 5 colonnes = 100% */
        .table-frais thead th:nth-child(1) { width: 30%; } /* Élève */
        .table-frais thead th:nth-child(2) { width: 25%; } /* Frais */
        .table-frais thead th:nth-child(3) { width: 20%; } /* Payé */
        .table-frais thead th:nth-child(4) { width: 13%; } /* Date */
        .table-frais thead th:nth-child(5) { width: 12%; } /* Action */

        /* Éviter le débordement */
        .eleve-cell { overflow: hidden; }
        .cell-primary {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }

    /* ✅ TABLETTE : scroll horizontal si nécessaire */
    @media (min-width: 768px) and (max-width: 1023px) {
        .data-table { min-width: 900px; }
    }

    /* Cellules spécifiques */
    .eleve-cell { display: flex; align-items: center; gap: 0.6rem; min-width: 0; }
    .eleve-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    .eleve-meta { min-width: 0; }

    .amount-usd {
        display: block;
        font-weight: 700;
        color: var(--c-ink-900);
        font-variant-numeric: tabular-nums;
    }
    .amount-success { color: #059669; }
    .amount-danger  { color: #dc2626; }
    .amount-fc {
        display: block;
        font-size: 0.72rem;
        color: var(--c-ink-400);
        font-variant-numeric: tabular-nums;
    }
    .date-value { font-size: 0.85rem; color: var(--c-ink-600); }

    /* PILLS */
    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.7rem;
        border-radius: var(--radius-full);
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .pill i { font-size: 0.7rem; }
    .pill-success { background: #ecfdf5; color: #047857; }
    .pill-warning { background: #fffbeb; color: #b45309; }
    .pill-danger  { background: #fef2f2; color: #be123c; }
    .pill-info    { background: #eff6ff; color: #1d4ed8; }
    .pill-indigo  { background: #eef2ff; color: #4338ca; }
    .pill-neutral { background: #f1f5f9; color: #64748b; }

    /* ACTION */
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-width: 38px;
        height: 38px;
        padding: 0 0.6rem;
        border-radius: 10px;
        color: var(--c-ink-500);
        background: transparent;
        border: 1.5px solid transparent;
        cursor: pointer;
        transition: all 300ms var(--ease-soft);
        font-size: 0.9rem;
        text-decoration: none;
        font-weight: 600;
    }
    .action-btn:hover {
        background: var(--c-gray-100);
        color: var(--c-ink-900);
        border-color: var(--c-gray-200);
        transform: translateY(-1px);
    }
    .action-label { display: none; }

    .pagination-wrapper {
        padding: 0.9rem 1.25rem;
        border-top: 1px solid var(--c-gray-100);
        display: flex;
        justify-content: flex-start;
        overflow-x: auto;
    }

    /* EMPTY STATE */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: clamp(2.5rem, 6vw, 4rem) 1.5rem;
        text-align: center;
        gap: 0.5rem;
    }
    .empty-icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--c-gray-50);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #cbd5e1;
        margin-bottom: 0.75rem;
    }
    .empty-title { font-size: 1.1rem; font-weight: 700; color: var(--c-ink-700); margin: 0; }
    .empty-text  { font-size: 0.9rem; color: var(--c-ink-400); max-width: 420px; margin: 0; line-height: 1.5; }

    /* ============================================================
       ✅ RESPONSIVE MOBILE — Cartes (grid-areas conservées)
    ============================================================ */
    @media (max-width: 767px) {
        .table-wrapper { overflow-x: visible; }
        .data-table,
        .data-table tbody { display: block; width: 100%; }
        .data-table thead { display: none; }

        .data-table tbody tr {
            display: grid;
            gap: 0.75rem 0.5rem;
            padding: 1rem;
            background: #fff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--c-gray-100);
            box-shadow: var(--shadow-sm);
            margin: 1rem;
            transition: border-color 300ms var(--ease-soft), box-shadow 300ms var(--ease-soft), transform 300ms var(--ease-soft);
        }
        .data-table tbody tr:hover {
            background: #fff;
            border-color: #c7d2fe;
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .data-table tbody td {
            display: block;
            padding: 0 !important;
            border: none;
            font-size: 0.85rem;
        }
        .data-table tbody td::before { display: none !important; }

        .cell-employe { min-width: 0; overflow: hidden; }
        .eleve-cell { gap: 0.65rem; align-items: center; }
        .eleve-avatar { width: 40px; height: 40px; font-size: 0.9rem; border-radius: 10px; }
        .cell-primary {
            font-size: 0.95rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
        }

        .cell-statut {
            display: flex !important;
            justify-content: flex-end;
            align-items: center;
            align-self: center;
        }

        .cell-amount {
            padding: 0.25rem 0 !important;
            text-align: left !important;
            display: flex !important;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }
        .cell-amount.text-right > .amount-usd,
        .cell-amount.text-right > .amount-fc { text-align: left; }

        .amount-label {
            display: block;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--c-ink-400);
            margin-bottom: 0.15rem;
        }
        .amount-usd { font-size: 0.95rem; font-weight: 700; line-height: 1.2; }
        .amount-fc  { font-size: 0.7rem; line-height: 1.2; }

        .cell-date {
            display: flex !important;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            color: var(--c-ink-400);
            font-weight: 500;
            text-align: left !important;
        }
        .cell-date::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #cbd5e1;
            flex-shrink: 0;
        }
        .date-value { color: var(--c-ink-400); }

        .cell-actions {
            padding-top: 0.75rem !important;
            border-top: 1px dashed var(--c-gray-200) !important;
            display: flex !important;
            justify-content: flex-end;
        }
        .action-btn {
            min-width: 40px;
            height: 40px;
            padding: 0 0.8rem;
            background: var(--c-gray-50);
            border: 1.5px solid var(--c-gray-200);
            border-radius: 10px;
            font-size: 0.8rem;
            gap: 0.4rem;
        }
        .action-btn:hover {
            background: var(--c-gray-100);
            border-color: #cbd5e1;
        }
        .action-label { display: inline; font-size: 0.78rem; font-weight: 600; }

        .pagination-wrapper {
            padding: 1rem;
            justify-content: flex-start;
        }

        /* Table principale : layout en grille */
        .table-principaux tbody tr {
            grid-template-columns: 1fr 1fr;
            grid-template-areas:
                "employe statut"
                "paye restant"
                "date actions";
        }
        .table-principaux .cell-employe    { grid-area: employe; }
        .table-principaux .cell-statut     { grid-area: statut; }
        .table-principaux td[data-label="Payé"]    { grid-area: paye; }
        .table-principaux td[data-label="Restant"] { grid-area: restant; }
        .table-principaux .cell-date       { grid-area: date; }
        .table-principaux .cell-actions    { grid-area: actions; }

        /* Table frais : layout en grille */
        .table-frais tbody tr {
            grid-template-columns: 1fr 1fr;
            grid-template-areas:
                "employe employe"
                "frais frais"
                "paye date"
                "actions actions";
        }
        .table-frais .cell-employe { grid-area: employe; }
        .table-frais .cell-frais   { grid-area: frais; }
        .table-frais td[data-label="Payé"] { grid-area: paye; }
        .table-frais .cell-date    { grid-area: date; justify-content: flex-end; }
        .table-frais .cell-actions { grid-area: actions; }
        .table-frais .cell-frais { text-align: left; }
        .table-frais .cell-frais .pill {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }

    /* ✅ Très petit mobile */
    @media (max-width: 400px) {
        .page { padding: 1.25rem 0.75rem; }
        .page-title { font-size: 1.35rem; }
        .page-subtitle { font-size: 0.8rem; }
        .content-header { padding: 0.85rem 1rem; }
        .content-title  { font-size: 0.9rem; }
        .data-table tbody tr { padding: 0.85rem; margin: 0.75rem; gap: 0.6rem 0.4rem; }
        .eleve-avatar { width: 36px; height: 36px; font-size: 0.8rem; }
        .cell-primary { font-size: 0.9rem; }
        .amount-label { font-size: 0.6rem; }
        .amount-usd   { font-size: 0.88rem; }
        .amount-fc    { font-size: 0.65rem; }
        .action-btn { padding: 0 0.6rem; height: 36px; font-size: 0.75rem; }
        .action-label { font-size: 0.72rem; }
        .empty-icon-wrapper { width: 64px; height: 64px; font-size: 1.6rem; }
        .empty-title { font-size: 1rem; }
        .empty-text  { font-size: 0.85rem; }
    }

    /* ============================================================
       ✅ ANIMATIONS BIDIRECTIONNELLES
    ============================================================ */
    .page [data-reveal] {
        opacity: 0;
        transform: translateY(32px) scale(.985);
        filter: blur(6px);
        transition:
            opacity 700ms var(--ease-out-expo),
            transform 700ms var(--ease-out-expo),
            filter 700ms var(--ease-out-expo);
        will-change: opacity, transform, filter;
    }
    .page [data-reveal="left"]  { transform: translateX(-40px); }
    .page [data-reveal="right"] { transform: translateX(40px); }
    .page [data-reveal="scale"] { transform: scale(.94); }
    .page [data-reveal="auto"][data-scroll-dir="up"]   { transform: translateY(-32px); }
    .page [data-reveal="auto"][data-scroll-dir="down"] { transform: translateY(32px); }

    .page [data-reveal].is-visible {
        opacity: 1;
        transform: translate(0, 0) scale(1);
        filter: blur(0);
    }
    .page [data-delay="1"] { transition-delay: 60ms; }
    .page [data-delay="2"] { transition-delay: 120ms; }
    .page [data-delay="3"] { transition-delay: 180ms; }
    .page [data-delay="4"] { transition-delay: 240ms; }
    .page [data-delay="5"] { transition-delay: 300ms; }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .page [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
        .stat-card:hover, .btn-secondary:hover, .btn-ghost:hover,
        .export-btn:hover, .action-btn:hover, .data-table tbody tr:hover {
            transform: none;
        }
        *, *::before, *::after {
            animation-duration: .01ms !important;
            transition-duration: .01ms !important;
        }
    }

    /* ============================================================
       IMPRESSION
    ============================================================ */
    @media print {
        .no-print { display: none !important; }
        .page { padding: 0; max-width: 100%; }
        .content-card { box-shadow: none; border: 1px solid #ddd; }
        .page [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
    }
</style>

{{-- ═══════════════════════════════════════════════════════════════
     JS — CASCADE + AUTO-SUBMIT (logique inchangée)
═══════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        const form = document.querySelector('[data-filter-form]');
        if (!form) return;

        /* ---------- Références ---------- */
        const sessionSel = document.getElementById('f-session');
        const sectionSel = document.getElementById('f-section');
        const salleSel   = document.getElementById('f-salle');
        const optionSel  = document.getElementById('f-option');
        const periodeSel = document.getElementById('f-periode');
        const statutSel  = document.getElementById('f-statut');

        /* ---------- Auto-submit debouncé ---------- */
        let timer = null;
        const submitSoon = function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 150);
        };

        /* =========================================================
           FILTRES EN CASCADE (avec reset des enfants)
        ========================================================= */

        function filtrerSections(reset) {
            if (!sessionSel || !sectionSel) return;
            const v = sessionSel.value;
            let selectedHidden = false;

            sectionSel.querySelectorAll('option').forEach(function (opt) {
                if (!opt.value) return;
                const match = v === '' || opt.dataset.sessionId === v;
                opt.hidden = !match;
                if (!match && opt.selected) selectedHidden = true;
            });

            if (reset && selectedHidden) sectionSel.value = '';
        }

        function filtrerSalles(reset) {
            if (!sectionSel || !salleSel) return;
            const v = sectionSel.value;
            let selectedHidden = false;

            salleSel.querySelectorAll('option').forEach(function (opt) {
                if (!opt.value) return;
                const match = v === '' || opt.dataset.sectionId === v;
                opt.hidden = !match;
                if (!match && opt.selected) selectedHidden = true;
            });

            if (reset && selectedHidden) salleSel.value = '';
        }

        function filtrerOptions(reset) {
            if (!salleSel || !optionSel) return;
            const vSalle = salleSel.value;

            if (!vSalle) {
                optionSel.disabled = false;
                optionSel.querySelectorAll('option').forEach(function (opt) { opt.hidden = false; });
                return;
            }

            const selectedOption = salleSel.querySelector('option[value="' + vSalle + '"]');
            const optionId = selectedOption ? (selectedOption.dataset.optionId || '') : '';

            if (!optionId) {
                optionSel.disabled = true;
                optionSel.value = '';
                return;
            }

            optionSel.disabled = false;
            optionSel.querySelectorAll('option').forEach(function (opt) {
                if (!opt.value) return;
                opt.hidden = opt.value !== optionId;
            });

            if (reset) optionSel.value = optionId;
        }

        /* =========================================================
           LISTENERS
        ========================================================= */

        if (sessionSel) {
            sessionSel.addEventListener('change', function () {
                if (sectionSel) sectionSel.value = '';
                if (salleSel)   salleSel.value   = '';
                if (optionSel)  optionSel.value  = '';
                if (periodeSel) periodeSel.value = '';
                filtrerSections(true);
                filtrerSalles(true);
                filtrerOptions(true);
                submitSoon();
            });
        }

        if (sectionSel) {
            sectionSel.addEventListener('change', function () {
                if (salleSel)   salleSel.value   = '';
                if (optionSel)  optionSel.value  = '';
                if (periodeSel) periodeSel.value = '';
                filtrerSalles(true);
                filtrerOptions(true);
                submitSoon();
            });
        }

        if (salleSel) {
            salleSel.addEventListener('change', function () {
                if (optionSel)  optionSel.value  = '';
                if (periodeSel) periodeSel.value = '';
                filtrerOptions(true);
                submitSoon();
            });
        }

        if (optionSel)  optionSel.addEventListener('change', submitSoon);
        if (periodeSel) periodeSel.addEventListener('change', submitSoon);
        if (statutSel)  statutSel.addEventListener('change', submitSoon);

        /* =========================================================
           INITIALISATION
        ========================================================= */
        filtrerSections(false);
        filtrerSalles(false);
        filtrerOptions(false);
    });

    /* ============================================================
       ✅ ANIMATIONS BIDIRECTIONNELLES
    ============================================================ */
    (function () {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        let lastY = window.scrollY;
        let scrollDir = 'down';

        window.addEventListener('scroll', () => {
            const y = window.scrollY;
            if (Math.abs(y - lastY) > 4) {
                scrollDir = y > lastY ? 'down' : 'up';
                lastY = y;
            }
        }, { passive: true });

        function initReveal() {
            const els = document.querySelectorAll('.page [data-reveal]');
            if (!els.length) return;

            if (prefersReducedMotion || !('IntersectionObserver' in window)) {
                els.forEach(el => el.classList.add('is-visible'));
                return;
            }

            const io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const el = entry.target;
                    const isAuto = el.dataset.reveal === 'auto';

                    if (entry.isIntersecting) {
                        if (isAuto) el.dataset.scrollDir = scrollDir;
                        el.classList.add('is-visible');
                    } else if (entry.intersectionRatio === 0) {
                        el.classList.remove('is-visible');
                        if (isAuto) delete el.dataset.scrollDir;
                    }
                });
            }, {
                threshold: [0, 0.1],
                rootMargin: '0px 0px -30px 0px'
            });

            els.forEach(el => io.observe(el));
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initReveal);
        } else {
            initReveal();
        }
    })();
})();
</script>
@endsection