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

    {{-- ═══════════════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════════════ --}}
    <header class="page-header">
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
        <article class="stat-card stat-indigo">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Paiements principaux</p>
                <p class="stat-value">{{ number_format($totalPaiements, 0, ',', ' ') }}</p>
            </div>
        </article>

        <article class="stat-card stat-violet">
            <div class="stat-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Frais supplémentaires</p>
                <p class="stat-value">{{ number_format($totalFraisSupp, 0, ',', ' ') }}</p>
            </div>
        </article>

        <article class="stat-card stat-success">
            <div class="stat-icon"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Total payé (principal)</p>
                <p class="stat-value">${{ $fmtUsd($totalPayePrincipal) }}</p>
                <p class="stat-unit">{{ $fmtFc($totalPayePrincipalFC) }} FC</p>
            </div>
        </article>

        <article class="stat-card stat-cyan">
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
    ═══════════════════════════════════════════════════════════ --}}
    <section class="toolbar no-print">
        <div class="toolbar-info">
            <i class="fa-solid fa-download" aria-hidden="true"></i>
            <span class="toolbar-label">
                Exporter
                <span class="toolbar-context"
                      x-text="activeTab === 'frais' ? 'les frais supplémentaires' : 'les paiements principaux'"></span>
                :
            </span>
        </div>

        <div class="toolbar-group">
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
    <section class="filter-card no-print">
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
    <nav class="tabs no-print" role="tablist">
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
    <section x-show="activeTab === 'principaux'" x-cloak class="content-card">
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
    .page { max-width: 1300px; margin: 0 auto; padding: 2rem 1rem; }
    .cell-amount .amount-label { display: none; }

    /* HEADER */
    .page-header { display: flex; flex-direction: column; align-items: flex-start; gap: 1.25rem; margin-bottom: 2rem; }
    .page-header-main { min-width: 0; width: 100%; }
    @media (min-width: 768px) {
        .page-header { flex-direction: row; justify-content: space-between; align-items: center; }
        .page-header-main { width: auto; }
    }
    .page-title { display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem; font-size: 1.75rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; margin: 0 0 0.25rem; line-height: 1.15; }
    .page-title-text { overflow: hidden; text-overflow: ellipsis; }
    .title-icon    { color: #6366f1; }
    .title-icon-sm { color: #6366f1; font-size: 1rem; }
    .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; line-height: 1.4; }
    .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; width: 100%; }
    @media (min-width: 768px) { .header-actions { width: auto; } }

    /* BOUTONS */
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.7rem 1.25rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s ease; white-space: nowrap; text-align: center; }
    @media (max-width: 767px) { .btn { flex: 1 1 calc(50% - 0.3rem); min-width: 0; padding: 0.65rem 0.9rem; font-size: 0.85rem; } }
    @media (max-width: 420px) { .btn { flex: 1 1 100%; } }
    .btn-secondary { background: #1e293b; color: #fff; }
    .btn-secondary:hover { background: #334155; transform: translateY(-1px); }
    .btn-ghost { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .btn-ghost:hover { border-color: #6366f1; color: #4f46e5; background: #f8fafc; }

    /* STATS */
    .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }
    .stat-card { display: flex; align-items: center; gap: 0.9rem; padding: 1.1rem; background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s; min-width: 0; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .stat-indigo .stat-icon { background: #eef2ff; color: #4f46e5; }
    .stat-violet .stat-icon { background: #f5f3ff; color: #7c3aed; }
    .stat-success .stat-icon { background: #ecfdf5; color: #059669; }
    .stat-cyan .stat-icon { background: #ecfeff; color: #0891b2; }
    .stat-content { min-width: 0; flex: 1; }
    .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 600; color: #94a3b8; margin: 0 0 0.15rem; }
    .stat-value { font-size: 1.4rem; font-weight: 700; color: #0f172a; line-height: 1.15; margin: 0; overflow: hidden; text-overflow: ellipsis; }
    .stat-unit  { display: block; font-size: 0.72rem; color: #94a3b8; font-weight: 500; margin-top: 0.15rem; }
    @media (max-width: 480px) {
        .stat-card { padding: 0.85rem; gap: 0.65rem; }
        .stat-icon { width: 36px; height: 36px; font-size: 0.95rem; }
        .stat-value { font-size: 1.1rem; }
        .stat-label { font-size: 0.65rem; }
        .stat-unit  { font-size: 0.68rem; }
    }

    /* TOOLBAR */
    .toolbar { display: flex; flex-direction: column; gap: 0.75rem; align-items: flex-start; padding: 1rem 1.25rem; background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 1.5rem; }
    @media (min-width: 768px) { .toolbar { flex-direction: row; justify-content: space-between; align-items: center; } }
    .toolbar-info { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #64748b; }
    .toolbar-info i { color: #6366f1; }
    .toolbar-label { font-weight: 600; }
    .toolbar-context { color: #4f46e5; font-weight: 700; }
    .toolbar-group { display: grid; gap: 0.4rem; width: 100%; grid-template-columns: 1fr; }
    @media (min-width: 480px) and (max-width: 767px) { .toolbar-group { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 768px) { .toolbar-group { display: flex; flex-wrap: wrap; width: auto; gap: 0.4rem; } }
    .export-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.6rem 0.9rem; border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: #fff; text-decoration: none; transition: all 0.2s; border: none; width: 100%; }
    @media (min-width: 768px) { .export-btn { width: auto; padding: 0.55rem 0.9rem; } }
    .export-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
    .export-btn.pdf   { background: #dc2626; }
    .export-btn.csv   { background: #16a34a; }
    .export-btn.xml   { background: #2563eb; }
    .export-btn.doc   { background: #0891b2; }
    .export-btn.print { background: #475569; }

    /* FILTRES */
    .filter-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 1.5rem; overflow: hidden; }
    .filter-header { padding: 1rem 1.25rem 0; }
    .filter-header-left { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
    .filter-icon { color: #6366f1; }
    .filter-title { font-weight: 700; color: #0f172a; font-size: 0.95rem; }
    .filter-count { background: #eef2ff; color: #4f46e5; font-size: 0.68rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.3px; }
    .filter-body { padding: 1rem 1.25rem 1.25rem; }
    .filter-form { display: flex; flex-direction: column; gap: 1rem; }
    .filter-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
    @media (min-width: 640px)  { .filter-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .filter-grid { grid-template-columns: repeat(3, 1fr); } }
    .filter-field { display: flex; flex-direction: column; gap: 0.35rem; }
    .filter-field-wide { grid-column: span 2; }
    @media (max-width: 1023px) { .filter-field-wide { grid-column: span 1; } }
    .filter-label { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: 0.7rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .filter-label > i { color: #6366f1; font-size: 0.75rem; }
    .filter-hint { font-size: 0.65rem; font-weight: 700; background: #ecfdf5; color: #059669; padding: 0.1rem 0.45rem; border-radius: 9999px; text-transform: none; letter-spacing: 0; }
    .badge-mode { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.62rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.3px; line-height: 1; }
    .badge-mode i { font-size: 0.7rem; }
    .badge-mode-mensuel  { background: #eef2ff; color: #4338ca; }
    .badge-mode-tranche  { background: #f5f3ff; color: #6d28d9; }
    .badge-mode-neutral  { background: #f1f5f9; color: #64748b; }
    .filter-select, .filter-input { width: 100%; padding: 0.65rem 0.9rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.9rem; color: #0f172a; transition: all 0.2s; outline: none; }
    .filter-select { padding-right: 2.5rem; cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 1.1rem; }
    .filter-select:disabled, .filter-input:disabled { opacity: 0.55; cursor: not-allowed; background-color: #f1f5f9; }
    .filter-select:focus, .filter-input:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .filter-actions { display: flex; justify-content: flex-end; gap: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0; flex-wrap: wrap; }
    @media (max-width: 640px) { .filter-actions { flex-direction: column-reverse; } .filter-actions .btn { width: 100%; } }

    /* TABS */
    .tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; flex-wrap: wrap; }
    .tab { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; background: transparent; border: none; cursor: pointer; color: #64748b; transition: all 0.2s; }
    .tab:hover { background: #f8fafc; color: #0f172a; }
    .tab.is-active { background: #eef2ff; color: #4f46e5; }
    .tab-count { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 20px; padding: 0 0.4rem; background: #f1f5f9; color: #64748b; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; }
    .tab.is-active .tab-count { background: #c7d2fe; color: #4338ca; }
    @media (max-width: 640px) { .tabs { border-bottom: none; } .tab { flex: 1 1 100%; justify-content: space-between; } }

    /* CONTENT CARD */
    .content-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 1.5rem; overflow: hidden; }
    .content-header { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; }
    .content-title { display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; min-width: 0; }
    .count-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 30px; height: 26px; padding: 0 0.6rem; background: #eef2ff; color: #4f46e5; border-radius: 9999px; font-size: 0.78rem; font-weight: 700; }

    /* TABLEAU */
    .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: #475569; }
    .data-table thead th { text-align: left; padding: 0.8rem 1.25rem; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .data-table tbody td { padding: 0.9rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .text-right  { text-align: right !important; }
    .text-center { text-align: center !important; }
    .text-muted  { color: #cbd5e1; }
    .cell-primary { font-weight: 600; color: #0f172a; }
    .eleve-cell { display: flex; align-items: center; gap: 0.6rem; }
    .eleve-avatar { width: 32px; height: 32px; border-radius: 8px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.78rem; flex-shrink: 0; }
    .eleve-meta { min-width: 0; }
    .amount-usd { display: block; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .amount-success { color: #059669; }
    .amount-danger  { color: #dc2626; }
    .amount-fc { display: block; font-size: 0.72rem; color: #94a3b8; font-variant-numeric: tabular-nums; }
    .date-value { font-size: 0.85rem; color: #475569; }

    /* PILLS */
    .pill { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; white-space: nowrap; }
    .pill i { font-size: 0.7rem; }
    .pill-success { background: #ecfdf5; color: #047857; }
    .pill-warning { background: #fffbeb; color: #b45309; }
    .pill-danger  { background: #fef2f2; color: #be123c; }
    .pill-info    { background: #eff6ff; color: #1d4ed8; }
    .pill-indigo  { background: #eef2ff; color: #4338ca; }
    .pill-neutral { background: #f1f5f9; color: #64748b; }

    /* ACTION */
    .action-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; min-width: 34px; height: 34px; padding: 0 0.5rem; border-radius: 9px; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.18s ease; font-size: 0.9rem; text-decoration: none; font-weight: 600; }
    .action-btn:hover { background: #f1f5f9; color: #0f172a; }
    .action-label { display: none; }
    .pagination-wrapper { padding: 0.9rem 1.25rem; border-top: 1px solid #f1f5f9; }

    /* EMPTY STATE */
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1.5rem; text-align: center; gap: 0.5rem; }
    .empty-icon-wrapper { width: 80px; height: 80px; border-radius: 50%; background: #f8fafc; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #cbd5e1; margin-bottom: 0.75rem; }
    .empty-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin: 0; }
    .empty-text  { font-size: 0.9rem; color: #94a3b8; max-width: 420px; margin: 0; line-height: 1.5; }

    /* RESPONSIVE MOBILE */
    @media (max-width: 767px) {
        .table-wrapper { overflow-x: visible; }
        .data-table, .data-table tbody { display: block; width: 100%; }
        .data-table thead { display: none; }
        .data-table tbody tr { display: grid; gap: 0.75rem 0.5rem; padding: 1rem; background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin: 1rem; transition: border-color 0.15s, box-shadow 0.15s; }
        .data-table tbody tr:hover { background: #fff; border-color: #c7d2fe; box-shadow: 0 4px 12px rgba(99,102,241,0.08); }
        .data-table tbody td { display: block; padding: 0 !important; border: none; font-size: 0.85rem; }
        .data-table tbody td::before { display: none !important; }
        .cell-employe { min-width: 0; overflow: hidden; }
        .eleve-cell { gap: 0.65rem; align-items: center; }
        .eleve-avatar { width: 40px; height: 40px; font-size: 0.9rem; border-radius: 10px; }
        .cell-primary { font-size: 0.95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }
        .cell-statut { display: flex !important; justify-content: flex-end; align-items: center; align-self: center; }
        .cell-amount { padding: 0.25rem 0 !important; text-align: left !important; display: flex !important; flex-direction: column; gap: 0.15rem; min-width: 0; }
        .cell-amount.text-right > .amount-usd, .cell-amount.text-right > .amount-fc { text-align: left; }
        .amount-label { display: block; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: #94a3b8; margin-bottom: 0.15rem; }
        .amount-usd { font-size: 0.95rem; font-weight: 700; line-height: 1.2; }
        .amount-fc  { font-size: 0.7rem; line-height: 1.2; }
        .cell-date { display: flex !important; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #94a3b8; font-weight: 500; text-align: left !important; }
        .cell-date::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #cbd5e1; flex-shrink: 0; }
        .date-value { color: #94a3b8; }
        .cell-actions { padding-top: 0.75rem !important; border-top: 1px dashed #e2e8f0 !important; display: flex !important; justify-content: flex-end; }
        .action-btn { min-width: 36px; height: 36px; padding: 0 0.7rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.8rem; gap: 0.4rem; }
        .action-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .action-label { display: inline; font-size: 0.78rem; font-weight: 600; }
        .pagination-wrapper { padding: 1rem; text-align: center; }

        .table-principaux tbody tr { grid-template-columns: 1fr 1fr; grid-template-areas: "employe statut" "paye restant" "date actions"; }
        .table-principaux .cell-employe    { grid-area: employe; }
        .table-principaux .cell-statut     { grid-area: statut; }
        .table-principaux td[data-label="Payé"]    { grid-area: paye; }
        .table-principaux td[data-label="Restant"] { grid-area: restant; }
        .table-principaux .cell-date       { grid-area: date; }
        .table-principaux .cell-actions    { grid-area: actions; }

        .table-frais tbody tr { grid-template-columns: 1fr 1fr; grid-template-areas: "employe employe" "frais frais" "paye date" "actions actions"; }
        .table-frais .cell-employe  { grid-area: employe; }
        .table-frais .cell-frais    { grid-area: frais; }
        .table-frais td[data-label="Payé"] { grid-area: paye; }
        .table-frais .cell-date     { grid-area: date; justify-content: flex-end; }
        .table-frais .cell-actions  { grid-area: actions; }
        .table-frais .cell-frais { text-align: left; }
        .table-frais .cell-frais .pill { max-width: 100%; overflow: hidden; text-overflow: ellipsis; }
    }

    @media (max-width: 400px) {
        .page { padding: 1.25rem 0.75rem; }
        .page-title { font-size: 1.4rem; }
        .page-subtitle { font-size: 0.8rem; }
        .content-header { padding: 0.85rem 1rem; }
        .content-title  { font-size: 0.9rem; }
        .data-table tbody tr { padding: 0.85rem; margin: 0.75rem; gap: 0.6rem 0.4rem; }
        .eleve-avatar { width: 36px; height: 36px; font-size: 0.8rem; }
        .cell-primary { font-size: 0.9rem; }
        .amount-label { font-size: 0.6rem; }
        .amount-usd   { font-size: 0.88rem; }
        .amount-fc    { font-size: 0.65rem; }
        .action-btn { padding: 0 0.55rem; height: 34px; font-size: 0.75rem; }
        .action-label { font-size: 0.72rem; }
        .empty-icon-wrapper { width: 64px; height: 64px; font-size: 1.6rem; }
        .empty-title { font-size: 1rem; }
        .empty-text  { font-size: 0.85rem; }
        .toolbar-group { grid-template-columns: 1fr; }
    }

    [x-cloak] { display: none !important; }
    @media (prefers-reduced-motion: reduce) {
        * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>

{{-- ═══════════════════════════════════════════════════════════════
     JS — CASCADE + AUTO-SUBMIT
═══════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    /* =========================================================
       INITIALISATION AU CHARGEMENT
    ========================================================= */
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

        // Session → reset Section + Salle + Option + Période
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

        // Section → reset Salle + Option + Période
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

        // Salle → reset Option + Période
        if (salleSel) {
            salleSel.addEventListener('change', function () {
                if (optionSel)  optionSel.value  = '';
                if (periodeSel) periodeSel.value = '';
                filtrerOptions(true);
                submitSoon();
            });
        }

        // Option / Période / Statut → auto-submit
        if (optionSel)  optionSel.addEventListener('change', submitSoon);
        if (periodeSel) periodeSel.addEventListener('change', submitSoon);
        if (statutSel)  statutSel.addEventListener('change', submitSoon);

        /* =========================================================
           INITIALISATION (au chargement, sans reset)
        ========================================================= */

        filtrerSections(false);
        filtrerSalles(false);
        filtrerOptions(false);
    });
})();
</script>
@endsection