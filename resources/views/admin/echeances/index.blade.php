@extends('layouts.admin')

@section('page_title', 'Échéances')
@section('page_subtitle', 'Suivez les paiements et leurs statuts')

@section('content')
@php
    $hasFilters = request()->filled('search')
        || request()->filled('annee_scolaire_id')
        || request()->filled('salle_classe_id')
        || request()->filled('statut');

    // Statuts possibles (pour badge cohérent)
    $statusMap = [
        'paye'       => ['label' => 'Payé',       'class' => 'badge-emerald', 'icon' => 'fa-circle-check'],
        'en_attente' => ['label' => 'En attente', 'class' => 'badge-amber',   'icon' => 'fa-hourglass-half'],
        'retard'     => ['label' => 'En retard',  'class' => 'badge-rose',    'icon' => 'fa-triangle-exclamation'],
    ];
@endphp

<div class="echeances-index-page">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-calendar-check title-icon" aria-hidden="true"></i>
                <span>Échéances de paiement</span>
            </h1>
            <p class="page-subtitle">Suivez les montants à payer et leur statut</p>
        </div>
        <div class="header-actions">
            <form action="{{ route('admin.echeances.generer') }}"
                  method="POST"
                  class="inline-form"
                  onsubmit="return confirm('Générer les échéances pour l\'année scolaire en cours ?');">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i>
                    <span>Générer</span>
                </button>
            </form>
            <form action="{{ route('admin.echeances.vider') }}"
                  method="POST"
                  class="inline-form"
                  onsubmit="return confirm('Supprimer TOUTES les échéances ?\n\nCette action est irréversible.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    <span>Vider</span>
                </button>
            </form>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- STATS --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="stats-grid">
        <div class="stat-card stat-indigo">
            <div class="stat-icon"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Total échéances</p>
                <p class="stat-value">{{ number_format($totalEcheances ?? 0, 0, ',', ' ') }}</p>
            </div>
        </div>

        <div class="stat-card stat-blue">
            <div class="stat-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Montant total</p>
                <p class="stat-value">{{ number_format($totalMontant ?? 0, 0, ',', ' ') }} <small>$</small></p>
            </div>
        </div>

        <div class="stat-card stat-emerald">
            <div class="stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Payées</p>
                <p class="stat-value">{{ number_format($totalPayes ?? 0, 0, ',', ' ') }}</p>
            </div>
        </div>

        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">En attente</p>
                <p class="stat-value">{{ number_format($totalEnAttente ?? 0, 0, ',', ' ') }}</p>
            </div>
        </div>

        <div class="stat-card stat-rose">
            <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">En retard</p>
                <p class="stat-value">{{ number_format($totalEnRetard ?? 0, 0, ',', ' ') }}</p>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FILTRES --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <form method="GET"
          action="{{ route('admin.echeances.index') }}"
          class="filters-card">

        <div class="filters-grid">
            {{-- Recherche --}}
            <div class="filter-field filter-field-search">
                <label for="search" class="filter-label">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>Recherche élève</span>
                </label>
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                    <input type="text"
                           id="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Nom, prénom, matricule…"
                           autocomplete="off"
                           class="filter-input">
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.echeances.index', request()->except('search', 'page')) }}"
                           class="search-clear"
                           aria-label="Effacer la recherche">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Année --}}
            <div class="filter-field">
                <label for="annee_scolaire_id" class="filter-label">
                    <i class="fa-solid fa-calendar" aria-hidden="true"></i>
                    <span>Année scolaire</span>
                </label>
                <select id="annee_scolaire_id"
                        name="annee_scolaire_id"
                        class="filter-input">
                    <option value="">Toutes</option>
                    @foreach($anneesScolaires as $annee)
                        <option value="{{ $annee->id }}" @selected(request('annee_scolaire_id') == $annee->id)>
                            {{ $annee->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Salle --}}
            <div class="filter-field">
                <label for="salle_classe_id" class="filter-label">
                    <i class="fa-solid fa-door-open" aria-hidden="true"></i>
                    <span>Salle de classe</span>
                </label>
                <select id="salle_classe_id"
                        name="salle_classe_id"
                        class="filter-input">
                    <option value="">Toutes</option>
                    @foreach($sallesClasse as $salle)
                        <option value="{{ $salle->id }}" @selected(request('salle_classe_id') == $salle->id)>
                            {{ $salle->nom }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Statut --}}
            <div class="filter-field">
                <label for="statut" class="filter-label">
                    <i class="fa-solid fa-flag" aria-hidden="true"></i>
                    <span>Statut</span>
                </label>
                <select id="statut" name="statut" class="filter-input">
                    <option value="">Tous</option>
                    <option value="paye"       @selected(request('statut') == 'paye')>Payées</option>
                    <option value="en_attente" @selected(request('statut') == 'en_attente')>En attente</option>
                    <option value="retard"     @selected(request('statut') == 'retard')>En retard</option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-filter" aria-hidden="true"></i>
                    <span>Filtrer</span>
                </button>
                @if($hasFilters)
                    <a href="{{ route('admin.echeances.index') }}" class="btn btn-ghost">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                        <span>Réinitialiser</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- LISTE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="content-card">

        {{-- Info bar --}}
        <div class="content-info">
            <div class="content-info-left">
                <i class="fa-regular fa-calendar-check content-info-icon" aria-hidden="true"></i>
                <span class="content-info-title">Échéances</span>
                <span class="count-pill">{{ $echeances->total() }}</span>
            </div>
            @if($echeances->count() > 0)
                <div class="content-info-right">
                    <span>
                        <strong>{{ $echeances->firstItem() ?? 0 }}</strong>–<strong>{{ $echeances->lastItem() ?? 0 }}</strong>
                        sur <strong>{{ $echeances->total() }}</strong>
                    </span>
                </div>
            @endif
        </div>

        @if($echeances->isEmpty())
            {{-- État vide --}}
            <div class="empty-state">
                <div class="empty-icon-wrapper">
                    <i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i>
                </div>
                <h3 class="empty-title">Aucune échéance</h3>
                <p class="empty-text">
                    @if($hasFilters)
                        Aucun résultat avec ces critères. Essayez d'élargir votre recherche.
                    @else
                        Commencez par générer les échéances pour l'année scolaire en cours.
                    @endif
                </p>
                <div class="empty-actions">
                    @if($hasFilters)
                        <a href="{{ route('admin.echeances.index') }}" class="btn btn-primary">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                            <span>Réinitialiser les filtres</span>
                        </a>
                    @else
                        <form action="{{ route('admin.echeances.generer') }}" method="POST" class="inline-form">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i>
                                <span>Générer les échéances</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @else

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- VUE DESKTOP : TABLE --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Élève</th>
                            <th scope="col">Salle</th>
                            <th scope="col">Période</th>
                            <th scope="col" class="col-amount">Montant USD</th>
                            <th scope="col">Échéance</th>
                            <th scope="col">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($echeances as $echeance)
                            @php
                                $eleveNom = trim(($echeance->eleve->nom ?? '') . ' ' . ($echeance->eleve->prenom ?? ''));
                                $initial  = mb_substr($eleveNom ?: '?', 0, 1);
                            @endphp
                            <tr>
                                <td>
                                    <div class="cell-user">
                                        <div class="avatar" aria-hidden="true">
                                            {{ strtoupper($initial) }}
                                        </div>
                                        <div class="cell-user-info">
                                            <span class="cell-primary">{{ $eleveNom ?: '—' }}</span>
                                            @if($echeance->eleve->matricule ?? null)
                                                <span class="cell-subtitle">#{{ $echeance->eleve->matricule }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($echeance->salleClasse->nom ?? null)
                                        <span class="badge badge-slate">{{ $echeance->salleClasse->nom }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $echeance->periode ?? '—' }}</td>
                                <td class="col-amount">
                                    <strong class="amount">
                                        {{ number_format($echeance->montant_usd ?? 0, 0, ',', ' ') }}
                                        <small>$</small>
                                    </strong>
                                </td>
                                <td>
                                    @if($echeance->date_echeance)
                                        <time datetime="{{ $echeance->date_echeance->toIso8601String() }}"
                                              title="{{ $echeance->date_echeance->format('d/m/Y') }}">
                                            {{ $echeance->date_echeance->format('d/m/Y') }}
                                        </time>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($echeance->est_paye)
                                        <span class="badge {{ $statusMap['paye']['class'] }}">
                                            <i class="fa-solid {{ $statusMap['paye']['icon'] }}" aria-hidden="true"></i>
                                            {{ $statusMap['paye']['label'] }}
                                        </span>
                                    @else
                                        {{-- Distinction en_attente vs retard basée sur la date --}}
                                        @php
                                            $isLate = $echeance->date_echeance && $echeance->date_echeance->isPast();
                                            $stKey  = $isLate ? 'retard' : 'en_attente';
                                        @endphp
                                        <span class="badge {{ $statusMap[$stKey]['class'] }}">
                                            <i class="fa-solid {{ $statusMap[$stKey]['icon'] }}" aria-hidden="true"></i>
                                            {{ $statusMap[$stKey]['label'] }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- VUE MOBILE : CARDS --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            <div class="echeances-cards">
                @foreach($echeances as $echeance)
                    @php
                        $eleveNom = trim(($echeance->eleve->nom ?? '') . ' ' . ($echeance->eleve->prenom ?? ''));
                        $initial  = mb_substr($eleveNom ?: '?', 0, 1);

                        if ($echeance->est_paye) {
                            $stKey = 'paye';
                        } else {
                            $isLate = $echeance->date_echeance && $echeance->date_echeance->isPast();
                            $stKey  = $isLate ? 'retard' : 'en_attente';
                        }
                        $statusData = $statusMap[$stKey];
                    @endphp

                    <article class="echeance-card">
                        {{-- Header : avatar + nom + badge --}}
                        <header class="echeance-card-header">
                            <div class="echeance-avatar" aria-hidden="true">
                                {{ strtoupper($initial) }}
                            </div>
                            <div class="echeance-identity">
                                <h3 class="echeance-name">{{ $eleveNom ?: 'Élève inconnu' }}</h3>
                                @if($echeance->eleve->matricule ?? null)
                                    <span class="echeance-matricule">#{{ $echeance->eleve->matricule }}</span>
                                @endif
                            </div>
                            <span class="badge {{ $statusData['class'] }} badge-sm">
                                <i class="fa-solid {{ $statusData['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $statusData['label'] }}</span>
                            </span>
                        </header>

                        {{-- Corps : montant + détails --}}
                        <div class="echeance-card-body">
                            <div class="echeance-amount">
                                <span class="amount-value">
                                    {{ number_format($echeance->montant_usd ?? 0, 0, ',', ' ') }}
                                </span>
                                <span class="amount-currency">USD</span>
                            </div>

                            <div class="echeance-meta">
                                @if($echeance->salleClasse->nom ?? null)
                                    <div class="meta-line">
                                        <i class="fa-solid fa-door-open" aria-hidden="true"></i>
                                        <span>{{ $echeance->salleClasse->nom }}</span>
                                    </div>
                                @endif

                                @if($echeance->periode ?? null)
                                    <div class="meta-line">
                                        <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                                        <span>{{ $echeance->periode }}</span>
                                    </div>
                                @endif

                                @if($echeance->date_echeance ?? null)
                                    <div class="meta-line meta-line-date">
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                        <span>Échéance : {{ $echeance->date_echeance->format('d/m/Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- ════════════════════════════════════════════════════════ --}}
            {{-- PAGINATION --}}
            {{-- ════════════════════════════════════════════════════════ --}}
            @if($echeances->hasPages())
                <footer class="content-footer">
                    <nav aria-label="Pagination" class="pagination-nav">
                        {{ $echeances->appends(request()->query())->links() }}
                    </nav>
                </footer>
            @endif

        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    /* ════════════════════════════════════════════════════════
       DESIGN TOKENS
       ════════════════════════════════════════════════════════ */
    .echeances-index-page {
        --c-primary:      #4f46e5;
        --c-primary-soft: #eef2ff;
        --c-primary-mid:  #c7d2fe;

        --c-blue:      #2563eb;
        --c-blue-soft: #eff6ff;
        --c-blue-mid:  #bfdbfe;

        --c-emerald:      #059669;
        --c-emerald-soft: #ecfdf5;
        --c-emerald-mid:  #a7f3d0;

        --c-amber:      #d97706;
        --c-amber-soft: #fffbeb;
        --c-amber-mid:  #fde68a;

        --c-rose:      #dc2626;
        --c-rose-soft: #fef2f2;
        --c-rose-mid:  #fecaca;

        --c-slate-50:  #f8fafc;
        --c-slate-100: #f1f5f9;
        --c-slate-200: #e2e8f0;
        --c-slate-300: #cbd5e1;
        --c-slate-400: #94a3b8;
        --c-slate-500: #64748b;
        --c-slate-600: #475569;
        --c-slate-700: #334155;
        --c-slate-800: #1e293b;
        --c-slate-900: #0f172a;

        --radius-sm: 10px;
        --radius-md: 14px;
        --radius-lg: 16px;

        --shadow-xs: 0 1px 3px rgba(0,0,0,0.04);
        --shadow-sm: 0 4px 12px rgba(0,0,0,0.05);

        --t: 0.2s cubic-bezier(.4,0,.2,1);

        max-width: 1280px;
        margin: 0 auto;
        padding: 2rem 1rem;
        color: var(--c-slate-800);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .echeances-index-page *,
    .echeances-index-page *::before,
    .echeances-index-page *::after { box-sizing: border-box; }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .page-header {
        display: flex; flex-direction: column; gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    @media (min-width: 768px) {
        .echeances-index-page .page-header {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .echeances-index-page .page-header-text { min-width: 0; }

    .echeances-index-page .page-title {
        display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem;
        font-size: 1.6rem; font-weight: 800; color: var(--c-slate-900);
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .echeances-index-page .title-icon { color: #6366f1; font-size: 1.35rem; }

    .echeances-index-page .page-subtitle {
        color: var(--c-slate-500); font-size: 0.9rem; margin: 0;
    }

    .echeances-index-page .header-actions {
        display: flex; gap: 0.6rem; flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .echeances-index-page .header-actions {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .echeances-index-page .header-actions .btn { width: 100%; }
        .echeances-index-page .header-actions .inline-form { display: contents; }
    }

    .echeances-index-page .inline-form { display: inline; }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.7rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600; font-size: 0.875rem; font-family: inherit;
        text-decoration: none; border: none; cursor: pointer;
        transition: all var(--t); white-space: nowrap;
        min-height: 44px;
    }
    .echeances-index-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .echeances-index-page .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }
    .echeances-index-page .btn-danger {
        background: linear-gradient(135deg, #dc2626, #ef4444);
        color: #fff;
        box-shadow: 0 4px 12px rgba(220,38,38,0.25);
    }
    .echeances-index-page .btn-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(220,38,38,0.35);
    }
    .echeances-index-page .btn-ghost {
        background: #fff; color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .echeances-index-page .btn-ghost:hover {
        border-color: #6366f1; color: var(--c-primary);
        background: var(--c-slate-50);
    }
    .echeances-index-page .btn:focus-visible {
        outline: 2px solid var(--c-primary); outline-offset: 2px;
    }

    /* ════════════════════════════════════════════════════════
       STATS — Grille adaptative 2 / 3 / 5 colonnes
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.85rem;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 640px) {
        .echeances-index-page .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (min-width: 1024px) {
        .echeances-index-page .stats-grid {
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }
    }

    .echeances-index-page .stat-card {
        background: #fff;
        padding: 1rem 1.1rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-slate-100);
        border-left: 4px solid;
        box-shadow: var(--shadow-xs);
        display: flex; align-items: center; gap: 0.85rem;
        min-width: 0;
        transition: all var(--t);
    }
    .echeances-index-page .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
    }
    @media (min-width: 1024px) {
        .echeances-index-page .stat-card { padding: 1.15rem 1.25rem; gap: 0.95rem; }
    }

    .echeances-index-page .stat-indigo  { border-left-color: var(--c-primary); }
    .echeances-index-page .stat-blue    { border-left-color: var(--c-blue); }
    .echeances-index-page .stat-emerald { border-left-color: var(--c-emerald); }
    .echeances-index-page .stat-amber   { border-left-color: var(--c-amber); }
    .echeances-index-page .stat-rose    { border-left-color: var(--c-rose); }

    .echeances-index-page .stat-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    @media (min-width: 1024px) {
        .echeances-index-page .stat-icon { width: 46px; height: 46px; font-size: 1.1rem; }
    }

    .echeances-index-page .stat-indigo  .stat-icon { background: var(--c-primary-soft); color: var(--c-primary); }
    .echeances-index-page .stat-blue    .stat-icon { background: var(--c-blue-soft);    color: var(--c-blue); }
    .echeances-index-page .stat-emerald .stat-icon { background: var(--c-emerald-soft); color: var(--c-emerald); }
    .echeances-index-page .stat-amber   .stat-icon { background: var(--c-amber-soft);   color: var(--c-amber); }
    .echeances-index-page .stat-rose    .stat-icon { background: var(--c-rose-soft);    color: var(--c-rose); }

    .echeances-index-page .stat-content { min-width: 0; flex: 1; }

    .echeances-index-page .stat-label {
        font-size: 0.68rem; font-weight: 600;
        color: var(--c-slate-500);
        text-transform: uppercase; letter-spacing: 0.5px;
        margin: 0 0 0.15rem;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    @media (min-width: 1024px) {
        .echeances-index-page .stat-label { font-size: 0.72rem; }
    }

    .echeances-index-page .stat-value {
        font-size: 1.35rem; font-weight: 800;
        color: var(--c-slate-900);
        margin: 0;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }
    @media (min-width: 1024px) {
        .echeances-index-page .stat-value { font-size: 1.55rem; }
    }
    .echeances-index-page .stat-value small {
        font-size: 0.65em;
        color: var(--c-slate-500);
        font-weight: 600;
        margin-left: 0.1rem;
    }

    /* ════════════════════════════════════════════════════════
       FILTRES
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .filters-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        padding: 1.15rem 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-xs);
    }
    @media (max-width: 640px) {
        .echeances-index-page .filters-card { padding: 1rem; }
    }

    .echeances-index-page .filters-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.85rem;
        align-items: end;
    }
    @media (min-width: 640px) {
        .echeances-index-page .filters-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 1024px) {
        .echeances-index-page .filters-grid {
            grid-template-columns: 2fr repeat(3, minmax(0, 1fr)) auto;
        }
    }

    .echeances-index-page .filter-field { min-width: 0; }

    .echeances-index-page .filter-label {
        display: flex; align-items: center; gap: 0.35rem;
        font-size: 0.75rem; font-weight: 600;
        color: var(--c-slate-600);
        margin-bottom: 0.35rem;
    }
    .echeances-index-page .filter-label i {
        color: var(--c-slate-400); font-size: 0.7rem;
    }

    .echeances-index-page .filter-input {
        width: 100%;
        padding: 0.65rem 0.9rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        font-size: 0.875rem;
        font-family: inherit;
        color: var(--c-slate-900);
        outline: none;
        transition: all var(--t);
        min-height: 44px;
    }
    .echeances-index-page .filter-input:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }

    .echeances-index-page .search-wrapper { position: relative; }
    .echeances-index-page .search-icon {
        position: absolute; left: 0.9rem; top: 50%;
        transform: translateY(-50%);
        color: var(--c-slate-400); font-size: 0.8rem;
        pointer-events: none;
    }
    .echeances-index-page .search-wrapper .filter-input {
        padding-left: 2.3rem;
        padding-right: 2.4rem;
    }
    .echeances-index-page .search-clear {
        position: absolute; right: 0.5rem; top: 50%;
        transform: translateY(-50%);
        width: 26px; height: 26px;
        background: var(--c-slate-100);
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        color: var(--c-slate-500);
        text-decoration: none;
        transition: all var(--t);
    }
    .echeances-index-page .search-clear:hover {
        background: var(--c-slate-200); color: var(--c-slate-800);
    }

    .echeances-index-page .filter-actions {
        display: flex; gap: 0.5rem; flex-wrap: wrap;
    }
    @media (max-width: 1023px) {
        .echeances-index-page .filter-actions { grid-column: 1 / -1; }
        .echeances-index-page .filter-actions .btn { flex: 1; }
    }

    /* ════════════════════════════════════════════════════════
       CONTENT CARD
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .content-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xs);
        overflow: hidden;
    }

    .echeances-index-page .content-info {
        display: flex; flex-direction: column; gap: 0.5rem;
        padding: 0.85rem 1.15rem;
        background: var(--c-slate-50);
        border-bottom: 1px solid var(--c-slate-100);
    }
    @media (min-width: 640px) {
        .echeances-index-page .content-info {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .echeances-index-page .content-info-left {
        display: inline-flex; align-items: center; gap: 0.5rem;
    }
    .echeances-index-page .content-info-icon {
        color: #6366f1; font-size: 0.95rem;
    }
    .echeances-index-page .content-info-title {
        font-weight: 600; color: var(--c-slate-800); font-size: 0.9rem;
    }
    .echeances-index-page .content-info-right {
        font-size: 0.82rem; color: var(--c-slate-500);
    }
    .echeances-index-page .content-info-right strong {
        color: var(--c-slate-700); font-weight: 700;
    }

    .echeances-index-page .count-pill {
        display: inline-flex; align-items: center;
        padding: 0.1rem 0.55rem;
        background: var(--c-primary-soft); color: var(--c-primary);
        border-radius: 9999px;
        font-size: 0.72rem; font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    /* ════════════════════════════════════════════════════════
       TABLE — Desktop uniquement
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .table-wrapper {
        display: none;
    }
    @media (min-width: 768px) {
        .echeances-index-page .table-wrapper {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    .echeances-index-page .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .echeances-index-page .data-table thead th {
        padding: 0.75rem 1rem;
        background: var(--c-slate-50);
        text-align: left;
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--c-slate-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--c-slate-200);
        white-space: nowrap;
    }
    .echeances-index-page .data-table th.col-amount,
    .echeances-index-page .data-table td.col-amount {
        text-align: right;
        white-space: nowrap;
    }
    .echeances-index-page .data-table tbody tr {
        border-bottom: 1px solid var(--c-slate-100);
        transition: background var(--t);
    }
    .echeances-index-page .data-table tbody tr:hover {
        background: var(--c-slate-50);
    }
    .echeances-index-page .data-table tbody tr:last-child {
        border-bottom: none;
    }
    .echeances-index-page .data-table td {
        padding: 0.85rem 1rem;
        color: var(--c-slate-600);
        vertical-align: middle;
    }

    /* Cellule élève */
    .echeances-index-page .cell-user {
        display: flex; align-items: center; gap: 0.65rem;
        min-width: 0;
    }
    .echeances-index-page .avatar {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.8rem;
        flex-shrink: 0;
    }
    .echeances-index-page .cell-user-info {
        min-width: 0;
        display: flex; flex-direction: column; gap: 0.1rem;
    }
    .echeances-index-page .cell-primary {
        font-weight: 600; color: var(--c-slate-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .echeances-index-page .cell-subtitle {
        font-size: 0.7rem; color: var(--c-slate-500);
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
    }

    .echeances-index-page .amount {
        font-size: 0.95rem;
        color: var(--c-slate-900);
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .echeances-index-page .amount small {
        color: var(--c-slate-500);
        font-weight: 600;
        font-size: 0.8em;
        margin-left: 0.1rem;
    }

    .echeances-index-page .text-muted { color: var(--c-slate-400); }

    /* ════════════════════════════════════════════════════════
       BADGES
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.72rem; font-weight: 600;
        white-space: nowrap;
        line-height: 1.3;
    }
    .echeances-index-page .badge-sm {
        font-size: 0.65rem;
        padding: 0.15rem 0.5rem;
        flex-shrink: 0;
    }
    .echeances-index-page .badge i { font-size: 0.6rem; }

    .echeances-index-page .badge-emerald {
        background: var(--c-emerald-soft); color: var(--c-emerald);
    }
    .echeances-index-page .badge-amber {
        background: var(--c-amber-soft); color: var(--c-amber);
    }
    .echeances-index-page .badge-rose {
        background: var(--c-rose-soft); color: var(--c-rose);
    }
    .echeances-index-page .badge-slate {
        background: var(--c-slate-100); color: var(--c-slate-600);
    }

    /* ════════════════════════════════════════════════════════
       CARDS — Mobile uniquement
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .echeances-cards {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
    }
    @media (min-width: 768px) {
        .echeances-index-page .echeances-cards { display: none; }
    }

    .echeances-index-page .echeance-card {
        background: #fff;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-md);
        padding: 1rem;
        transition: all var(--t);
    }
    .echeances-index-page .echeance-card:hover {
        border-color: var(--c-primary-mid);
        box-shadow: 0 4px 12px rgba(99,102,241,0.08);
    }

    .echeances-index-page .echeance-card-header {
        display: flex; align-items: center; gap: 0.75rem;
        margin-bottom: 0.85rem;
    }
    .echeances-index-page .echeance-avatar {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.85rem;
        flex-shrink: 0;
    }
    .echeances-index-page .echeance-identity {
        flex: 1; min-width: 0;
        display: flex; flex-direction: column; gap: 0.1rem;
    }
    .echeances-index-page .echeance-name {
        font-size: 0.9rem; font-weight: 700;
        color: var(--c-slate-900);
        margin: 0;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .echeances-index-page .echeance-matricule {
        font-size: 0.72rem; color: var(--c-slate-500);
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
    }

    .echeances-index-page .echeance-card-body {
        display: flex; align-items: flex-start;
        gap: 0.85rem;
        padding-top: 0.75rem;
        border-top: 1px dashed var(--c-slate-200);
    }

    .echeances-index-page .echeance-amount {
        display: flex; flex-direction: column; gap: 0.1rem;
        padding: 0.5rem 0.75rem;
        background: var(--c-primary-soft);
        border-radius: var(--radius-sm);
        flex-shrink: 0;
        min-width: 80px;
        text-align: center;
    }
    .echeances-index-page .amount-value {
        font-size: 1.05rem; font-weight: 800;
        color: var(--c-primary);
        font-variant-numeric: tabular-nums;
        line-height: 1;
    }
    .echeances-index-page .amount-currency {
        font-size: 0.62rem; font-weight: 700;
        color: var(--c-primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.7;
    }

    .echeances-index-page .echeance-meta {
        flex: 1; min-width: 0;
        display: flex; flex-direction: column; gap: 0.35rem;
    }
    .echeances-index-page .meta-line {
        display: flex; align-items: center; gap: 0.4rem;
        font-size: 0.78rem;
        color: var(--c-slate-600);
        min-width: 0;
    }
    .echeances-index-page .meta-line i {
        color: var(--c-slate-400);
        font-size: 0.7rem;
        width: 14px;
        text-align: center;
        flex-shrink: 0;
    }
    .echeances-index-page .meta-line span {
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        min-width: 0;
    }
    .echeances-index-page .meta-line-date {
        color: var(--c-slate-700);
        font-weight: 500;
    }

    /* ════════════════════════════════════════════════════════
       ÉTAT VIDE
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .empty-state {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center;
        padding: 3.5rem 1.5rem;
        text-align: center;
        gap: 0.5rem;
    }
    .echeances-index-page .empty-icon-wrapper {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: var(--c-slate-50);
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; color: var(--c-slate-300);
        margin-bottom: 0.75rem;
    }
    .echeances-index-page .empty-title {
        font-size: 1.1rem; font-weight: 700;
        color: var(--c-slate-700); margin: 0;
    }
    .echeances-index-page .empty-text {
        font-size: 0.875rem; color: var(--c-slate-400);
        max-width: 420px; margin: 0; line-height: 1.55;
    }
    .echeances-index-page .empty-actions { margin-top: 1rem; }

    /* ════════════════════════════════════════════════════════
       PAGINATION
       ════════════════════════════════════════════════════════ */
    .echeances-index-page .content-footer {
        display: flex; justify-content: center;
        padding: 1rem 1.15rem;
        border-top: 1px solid var(--c-slate-100);
        background: var(--c-slate-50);
    }
    .echeances-index-page .pagination-nav { width: 100%; }
    .echeances-index-page .pagination-nav nav { display: flex; justify-content: center; }
    .echeances-index-page .pagination-nav nav > div { display: none; }

    @media (max-width: 640px) {
        .echeances-index-page .pagination-nav nav span[aria-current="page"] > span,
        .echeances-index-page .pagination-nav nav a {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8rem !important;
        }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE
       ════════════════════════════════════════════════════════ */
    @media (max-width: 992px) {
        .echeances-index-page { padding: 1.5rem 1rem; }
    }
    @media (max-width: 768px) {
        .echeances-index-page { padding: 1.25rem 0.85rem; }
        .echeances-index-page .page-title { font-size: 1.35rem; }
        .echeances-index-page .title-icon { font-size: 1.15rem; }
        .echeances-index-page .page-subtitle { font-size: 0.85rem; }
    }
    @media (max-width: 480px) {
        .echeances-index-page { padding: 1rem 0.65rem; }
        .echeances-index-page .page-title { font-size: 1.15rem; }

        .echeances-index-page .stats-grid { gap: 0.6rem; }
        .echeances-index-page .stat-card { padding: 0.85rem 0.9rem; gap: 0.65rem; }
        .echeances-index-page .stat-icon { width: 36px; height: 36px; font-size: 0.9rem; }
        .echeances-index-page .stat-value { font-size: 1.15rem; }
        .echeances-index-page .stat-label { font-size: 0.62rem; }

        .echeances-index-page .filters-card { padding: 0.85rem; }
        .echeances-index-page .echeances-cards { padding: 0.75rem; }
        .echeances-index-page .echeance-card { padding: 0.85rem; }
        .echeances-index-page .echeance-avatar { width: 36px; height: 36px; font-size: 0.8rem; }
        .echeances-index-page .amount-value { font-size: 0.95rem; }
    }
    @media (max-width: 360px) {
        .echeances-index-page .stats-grid { grid-template-columns: 1fr; }
        .echeances-index-page .stat-value { font-size: 1.1rem; }
    }

    /* Anti-zoom iOS */
    @media (max-width: 640px) {
        .echeances-index-page .filter-input { font-size: 16px; }
    }

    /* ════════════════════════════════════════════════════════
       A11Y + PRINT
       ════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        .echeances-index-page *,
        .echeances-index-page *::before,
        .echeances-index-page *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }
    @media print {
        .echeances-index-page .filters-card,
        .echeances-index-page .header-actions,
        .echeances-index-page .content-footer {
            display: none !important;
        }
        .echeances-index-page .content-card {
            box-shadow: none; border: 1px solid #ccc;
        }
        .echeances-index-page .table-wrapper { display: block !important; }
        .echeances-index-page .echeances-cards { display: none !important; }
        .echeances-index-page .stat-card { break-inside: avoid; }
    }
</style>
@endpush