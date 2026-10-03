@extends('layouts.admin')

@section('page_title', 'Années scolaires')
@section('page_subtitle', 'Gérez les périodes académiques')

@section('content')
@php
    $stats = [
        'total'      => \App\Models\AnneeScolaire::count(),
        'actives'    => \App\Models\AnneeScolaire::where('cloturee', false)->count(),
        'cloturees'  => \App\Models\AnneeScolaire::where('cloturee', true)->count(),
        'paiements'  => \App\Models\AnneeScolaire::where('cloturee', false)
                            ->where('paiement_ouvert', true)
                            ->count(),
    ];

    $anneeActive = \App\Models\AnneeScolaire::enCours()->first();
    $anneeActiveId = $anneeActive?->id;
@endphp

<div class="index-container">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- EN-TÊTE --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <header class="page-header" data-reveal="auto">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-calendar-days title-icon" aria-hidden="true"></i>
                Années scolaires
                <span class="count-badge">{{ $stats['total'] }}</span>
            </h1>
            <p class="page-subtitle">
                @if($anneeActive)
                    Année active : <strong>{{ $anneeActive->libelle }}</strong>
                @else
                    Aucune année active actuellement
                @endif
            </p>
        </div>

        <div class="header-actions">
            <a href="{{ route('admin.annees-scolaires.corbeille') }}" class="btn btn-outline">
                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                <span>Corbeille</span>
            </a>
            <a href="{{ route('admin.annees-scolaires.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Nouvelle année</span>
            </a>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- STATISTIQUES --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="stats-grid" aria-label="Statistiques globales">
        <article class="stat-card stat-indigo" data-reveal="auto" data-delay="1">
            <div class="stat-icon"><i class="fa-solid fa-calendar-alt" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Total</p>
                <p class="stat-value">{{ $stats['total'] }}</p>
            </div>
        </article>

        <article class="stat-card stat-emerald" data-reveal="auto" data-delay="2">
            <div class="stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Actives</p>
                <p class="stat-value">{{ $stats['actives'] }}</p>
            </div>
        </article>

        <article class="stat-card stat-rose" data-reveal="auto" data-delay="3">
            <div class="stat-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Clôturées</p>
                <p class="stat-value">{{ $stats['cloturees'] }}</p>
            </div>
        </article>

        <article class="stat-card stat-amber" data-reveal="auto" data-delay="4">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Paiements ouverts</p>
                <p class="stat-value">{{ $stats['paiements'] }}</p>
            </div>
        </article>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- BARRE DE FILTRES --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="filter-bar" data-reveal="auto" data-delay="1">
        <form method="GET" role="search" class="filter-form">
            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                <input type="search"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Rechercher une année (ex : 2024)"
                       aria-label="Rechercher une année scolaire">
            </div>

            <select name="statut" class="filter-select" aria-label="Filtrer par statut">
                <option value="">Tous les statuts</option>
                <option value="en_cours"  @selected(request('statut') === 'en_cours')>En cours</option>
                <option value="cloturee"  @selected(request('statut') === 'cloturee')>Clôturée</option>
                <option value="a_venir"   @selected(request('statut') === 'a_venir')>À venir</option>
                <option value="passee"    @selected(request('statut') === 'passee')>Passée</option>
            </select>

            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-filter" aria-hidden="true"></i> Filtrer
            </button>

            @if(request()->filled('search') || request()->filled('statut'))
                <a href="{{ route('admin.annees-scolaires.index') }}" class="btn-reset">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Réinitialiser
                </a>
            @endif
        </form>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TABLEAU --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="table-card" data-reveal="auto" data-delay="2">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Année</th>
                        <th scope="col">Période</th>
                        <th scope="col">Effectif</th>
                        <th scope="col">Structure</th>
                        <th scope="col">État</th>
                        <th scope="col" class="text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($annees as $annee)
                        @php
                            $isActive   = $annee->id === $anneeActiveId;
                            $effectif   = (int) ($annee->effectif_attendu ?? 0);
                            $reel       = (int) ($annee->inscriptions_count ?? 0);
                            $progress   = $effectif > 0 ? min(100, round(($reel / $effectif) * 100)) : null;
                        @endphp

                        <tr class="{{ $isActive ? 'row-active' : '' }}">
                            {{-- Année --}}
                            <td data-label="Année">
                                <div class="cell-year">
                                    <div class="year-avatar {{ $isActive ? 'is-active' : '' }}">
                                        {{ $annee->date_debut?->format('y') ?? '—' }}
                                    </div>
                                    <div class="year-meta">
                                        <span class="cell-primary">{{ $annee->libelle }}</span>
                                        @if($isActive)
                                            <span class="pill pill-active">
                                                <span class="pulse-dot" aria-hidden="true"></span> En cours
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Période --}}
                            <td data-label="Période">
                                <div class="period">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    <span>
                                        {{ $annee->date_debut?->format('d/m/Y') ?? '—' }}
                                        <span class="period-arrow">→</span>
                                        {{ $annee->date_fin?->format('d/m/Y') ?? '—' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Effectif --}}
                            <td data-label="Effectif">
                                @if($progress !== null)
                                    <div class="effectif">
                                        <div class="effectif-numbers">
                                            <strong>{{ number_format($reel, 0, ',', ' ') }}</strong>
                                            <span class="muted">/ {{ number_format($effectif, 0, ',', ' ') }}</span>
                                        </div>
                                        <div class="progress" role="progressbar"
                                             aria-valuenow="{{ $progress }}"
                                             aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar" style="width: {{ $progress }}%"></div>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge badge-neutral">
                                        <i class="fa-solid fa-user-group" aria-hidden="true"></i>
                                        {{ number_format($reel, 0, ',', ' ') }} élèves
                                    </span>
                                @endif
                            </td>

                            {{-- Structure --}}
                            <td data-label="Structure">
                                <div class="badge-group">
                                    <span class="badge badge-indigo">
                                        <i class="fa-solid fa-calendar-day" aria-hidden="true"></i>
                                        {{ $annee->nombre_mois ?? '—' }} mois
                                    </span>
                                    <span class="badge badge-purple">
                                        <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                                        {{ $annee->nombre_tranches ?? '—' }} tranches
                                    </span>
                                </div>
                            </td>

                            {{-- État --}}
                            <td data-label="État">
                                <div class="badge-group">
                                    @if ($annee->cloturee)
                                        <span class="badge badge-rose">
                                            <i class="fa-solid fa-lock" aria-hidden="true"></i> Clôturée
                                        </span>
                                    @else
                                        <span class="badge badge-emerald">
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Active
                                        </span>
                                        <span class="badge {{ $annee->paiement_ouvert ? 'badge-emerald-soft' : 'badge-neutral' }}">
                                            <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                                            {{ $annee->paiement_ouvert ? 'Paiements ouverts' : 'Paiements fermés' }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td data-label="Actions" class="text-right action-cell">
                                <div class="action-bar">
                                    <a href="{{ route('admin.annees-scolaires.edit', $annee) }}"
                                       class="action-btn"
                                       aria-label="Modifier {{ $annee->libelle }}"
                                       title="Modifier">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    @if (!$annee->cloturee)
                                        <form action="{{ route('admin.annees-scolaires.toggle-paiement', $annee) }}"
                                              method="POST" class="inline-form">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="action-btn action-{{ $annee->paiement_ouvert ? 'warning' : 'success' }}"
                                                    aria-label="{{ $annee->paiement_ouvert ? 'Fermer les paiements' : 'Ouvrir les paiements' }}"
                                                    title="{{ $annee->paiement_ouvert ? 'Fermer les paiements' : 'Ouvrir les paiements' }}">
                                                <i class="fa-solid fa-money-bill" aria-hidden="true"></i>
                                            </button>
                                        </form>

                                        <a href="{{ route('admin.annees-scolaires.cloturer', $annee) }}"
                                           class="action-btn action-warning"
                                           aria-label="Clôturer {{ $annee->libelle }}"
                                           title="Clôturer l'année">
                                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <form action="{{ route('admin.annees-scolaires.toggle-cloture', $annee) }}"
                                              method="POST" class="inline-form"
                                              onsubmit="return confirm('Rouvrir cette année scolaire ? Les notes et paiements redeviendront modifiables.')">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="action-btn action-success"
                                                    aria-label="Rouvrir {{ $annee->libelle }}"
                                                    title="Rouvrir l'année">
                                                <i class="fa-solid fa-unlock" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('admin.annees-scolaires.destroy', $annee) }}"
                                          method="POST" class="inline-form"
                                          onsubmit="return confirm('Déplacer « {{ $annee->libelle }} » dans la corbeille ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="action-btn action-danger"
                                                aria-label="Supprimer {{ $annee->libelle }}"
                                                title="Supprimer">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="6" class="empty-cell">
                                <div class="empty-state">
                                    <i class="fa-regular fa-calendar-xmark empty-icon" aria-hidden="true"></i>
                                    <h3 class="empty-title">
                                        @if(request()->filled('search') || request()->filled('statut'))
                                            Aucun résultat
                                        @else
                                            Aucune année scolaire
                                        @endif
                                    </h3>
                                    <p class="empty-text">
                                        @if(request()->filled('search') || request()->filled('statut'))
                                            Aucune année ne correspond à vos critères de recherche.
                                        @else
                                            Commencez par créer votre première année scolaire.
                                        @endif
                                    </p>
                                    <div class="empty-actions">
                                        @if(request()->filled('search') || request()->filled('statut'))
                                            <a href="{{ route('admin.annees-scolaires.index') }}" class="btn btn-secondary">
                                                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Effacer les filtres
                                            </a>
                                        @else
                                            <a href="{{ route('admin.annees-scolaires.create') }}" class="btn btn-primary">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Créer une année
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- PAGINATION --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($annees->hasPages())
        <div class="pagination-wrapper" data-reveal="auto">
            {{ $annees->links() }}
        </div>
    @endif
</div>

<style>
    /* ========== LAYOUT ========== */
    .index-container {
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

    .index-container *,
    .index-container *::before,
    .index-container *::after { box-sizing: border-box; }

    .index-container h1,
    .index-container h2,
    .index-container h3 {
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        letter-spacing: -0.02em;
    }

    /* ========== HEADER ========== */
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
            justify-content: space-between;
            align-items: center;
        }
    }

    .page-header-text { min-width: 0; width: 100%; }
    @media (min-width: 768px) { .page-header-text { width: auto; } }

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

    .title-icon { color: var(--c-primary); flex-shrink: 0; }

    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 28px;
        padding: 0 0.6rem;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: var(--radius-full);
        font-size: 0.8rem;
        font-weight: 700;
    }

    .page-subtitle {
        color: var(--c-ink-500);
        font-size: clamp(.85rem, 1.4vw, .95rem);
        margin: 0;
        line-height: 1.5;
    }

    .page-subtitle strong { color: var(--c-ink-900); }

    .header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        width: 100%;
    }
    @media (min-width: 768px) { .header-actions { width: auto; } }
    .header-actions .btn { flex: 1 1 auto; justify-content: center; }
    @media (min-width: 768px) { .header-actions .btn { flex: 0 0 auto; } }

    /* ========== BOUTONS ========== */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.8rem 1.35rem;
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

    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.35);
    }

    .btn-secondary {
        background: var(--c-gray-100);
        color: var(--c-ink-600);
    }
    .btn-secondary:hover {
        background: var(--c-gray-200);
        color: var(--c-ink-900);
        transform: translateY(-1px);
    }

    .btn-outline {
        background: var(--c-white);
        border: 1.5px solid var(--c-gray-200);
        color: var(--c-ink-500);
    }
    .btn-outline:hover {
        border-color: var(--c-primary);
        color: var(--c-primary-dark);
        background: var(--c-gray-50);
        transform: translateY(-1px);
    }

    .btn-reset {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.7rem 1.1rem;
        min-height: 46px;
        border-radius: var(--radius-md);
        background: transparent;
        border: 1.5px solid var(--c-gray-200);
        color: var(--c-ink-500);
        font-weight: 500;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 250ms var(--ease-soft);
        cursor: pointer;
    }
    .btn-reset:hover {
        background: var(--c-gray-50);
        color: var(--c-ink-900);
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }

    /* ========== STATS ========== */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: clamp(.75rem, 1.5vw, 1rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 2rem);
    }

    @media (min-width: 640px)  { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }

    .stat-card {
        display: flex;
        align-items: center;
        gap: clamp(.75rem, 1.5vw, 1rem);
        padding: clamp(1rem, 2vw, 1.25rem);
        background: var(--c-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--c-gray-100);
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
        font-size: clamp(1.1rem, 2vw, 1.25rem);
        flex-shrink: 0;
        transition: transform 400ms var(--ease-out-expo);
    }
    .stat-card:hover .stat-icon { transform: scale(1.08) rotate(-5deg); }

    .stat-indigo  .stat-icon { background: #eef2ff; color: #4f46e5; }
    .stat-emerald .stat-icon { background: #ecfdf5; color: #059669; }
    .stat-rose    .stat-icon { background: #fff1f2; color: #e11d48; }
    .stat-amber   .stat-icon { background: #fffbeb; color: #d97706; }

    .stat-content { min-width: 0; }

    .stat-label {
        font-size: clamp(.7rem, 1.2vw, .78rem);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
        color: var(--c-ink-400);
        margin: 0 0 0.2rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .stat-value {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: clamp(1.4rem, 3vw, 1.6rem);
        font-weight: 800;
        color: var(--c-ink-900);
        line-height: 1;
        margin: 0;
        letter-spacing: -0.02em;
    }

    /* ========== FILTRES ========== */
    .filter-bar {
        background: var(--c-white);
        border-radius: var(--radius-lg);
        padding: clamp(1rem, 2vw, 1.25rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 1.5rem);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--c-gray-100);
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }

    .search-field {
        position: relative;
        flex: 1 1 240px;
        min-width: 0;
    }

    .search-field .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--c-ink-400);
        pointer-events: none;
        font-size: 0.9rem;
    }

    .search-field input {
        width: 100%;
        padding: 0.8rem 1rem 0.8rem 2.5rem;
        min-height: 46px;
        border: 1.5px solid var(--c-gray-200);
        border-radius: var(--radius-md);
        background: var(--c-gray-50);
        font-family: inherit;
        font-size: 0.9rem;
        color: var(--c-ink-900);
        transition: all 300ms var(--ease-soft);
        outline: none;
    }

    .search-field input:focus {
        border-color: var(--c-primary);
        background: var(--c-white);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
    }

    .filter-select {
        padding: 0.8rem 2.5rem 0.8rem 1rem;
        min-height: 46px;
        border: 1.5px solid var(--c-gray-200);
        border-radius: var(--radius-md);
        background: var(--c-gray-50);
        font-family: inherit;
        font-size: 0.9rem;
        color: var(--c-ink-900);
        transition: all 300ms var(--ease-soft);
        outline: none;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.7rem center;
        background-size: 1.1rem;
    }

    .filter-select:focus {
        border-color: var(--c-primary);
        background-color: var(--c-white);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
    }

    /* ========== TABLEAU ========== */
    .table-card {
        background: var(--c-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--c-gray-100);
        overflow: hidden;
        width: 100%;
    }

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
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
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

    /* Ligne de l'année active */
    .data-table tbody tr.row-active {
        background: linear-gradient(to right, #eef2ff 0%, #ffffff 35%);
    }
    .data-table tbody tr.row-active:hover { background: #eef2ff; }

    .text-right { text-align: right; }

    /* ✅ FIX DÉFINITIF — Restaure <table> en desktop */
    @media (min-width: 1024px) {
        .index-container .data-table {
            display: table !important;
            width: 100% !important;
            max-width: 100%;
            table-layout: fixed !important;
            border-collapse: collapse !important;
        }
        .index-container .data-table thead { display: table-header-group !important; }
        .index-container .data-table tbody { display: table-row-group !important; }
        .index-container .data-table tr    { display: table-row !important; }
        .index-container .data-table th,
        .index-container .data-table td    { display: table-cell !important; }

        /* Distribution des 6 colonnes = 100% */
        .index-container .data-table thead th:nth-child(1) { width: 20%; } /* Année */
        .index-container .data-table thead th:nth-child(2) { width: 16%; } /* Période */
        .index-container .data-table thead th:nth-child(3) { width: 14%; } /* Effectif */
        .index-container .data-table thead th:nth-child(4) { width: 16%; } /* Structure */
        .index-container .data-table thead th:nth-child(5) { width: 18%; } /* État */
        .index-container .data-table thead th:nth-child(6) { width: 16%; } /* Actions */
    }

    /* ✅ TABLETTE : scroll horizontal si nécessaire */
    @media (min-width: 768px) and (max-width: 1023px) {
        .data-table { min-width: 1000px; }
    }

    /* Empêche le débordement */
    .cell-primary {
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ========== CELLULE ANNÉE ========== */
    .cell-year {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .year-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--c-gray-100);
        color: var(--c-ink-600);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        flex-shrink: 0;
        letter-spacing: -0.3px;
        transition: transform 400ms var(--ease-out-expo);
    }
    .data-table tbody tr:hover .year-avatar { transform: scale(1.06); }

    .year-avatar.is-active {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
    }

    .year-meta {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
    }

    .cell-primary {
        font-weight: 600;
        color: var(--c-ink-900);
    }

    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: var(--radius-full);
        width: fit-content;
    }

    .pill-active {
        background: #ecfdf5;
        color: #059669;
    }

    .pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: var(--radius-full);
        background: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-ring 2s infinite;
    }

    @keyframes pulse-ring {
        0%   { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70%  { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* ========== CELLULE PÉRIODE ========== */
    .period {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--c-ink-500);
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .period i { color: var(--c-ink-400); }

    .period-arrow {
        color: #cbd5e1;
        margin: 0 0.15rem;
    }

    /* ========== CELLULE EFFECTIF ========== */
    .effectif {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-width: 100px;
    }

    .effectif-numbers {
        font-size: 0.85rem;
        color: var(--c-ink-900);
    }

    .effectif-numbers strong { font-weight: 700; }

    .effectif-numbers .muted {
        color: var(--c-ink-400);
        font-weight: 500;
    }

    .progress {
        width: 100%;
        height: 6px;
        background: var(--c-gray-100);
        border-radius: var(--radius-full);
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        border-radius: var(--radius-full);
        transition: width 0.4s ease;
    }

    /* ========== BADGES ========== */
    .badge-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.65rem;
        border-radius: var(--radius-full);
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
        line-height: 1.4;
    }

    .badge i { font-size: 0.7rem; }

    .badge-indigo       { background: #eef2ff; color: #4338ca; }
    .badge-purple       { background: #f5f3ff; color: #7e22ce; }
    .badge-emerald      { background: #ecfdf5; color: #047857; }
    .badge-emerald-soft { background: #f0fdf4; color: #16a34a; }
    .badge-rose         { background: #fff1f2; color: #be123c; }
    .badge-neutral      { background: var(--c-gray-100); color: var(--c-ink-500); }

    /* ========== ACTIONS ========== */
    .action-cell { white-space: nowrap; }

    .action-bar {
        display: inline-flex;
        gap: 0.35rem;
        justify-content: flex-end;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        color: var(--c-ink-500);
        background: transparent;
        border: 1.5px solid transparent;
        cursor: pointer;
        transition: all 300ms var(--ease-soft);
        font-size: 0.9rem;
        text-decoration: none;
        flex-shrink: 0;
    }

    .action-btn:hover {
        background: var(--c-gray-100);
        color: var(--c-ink-900);
        border-color: var(--c-gray-200);
        transform: translateY(-1px);
    }

    .action-btn.action-success:hover {
        background: #ecfdf5;
        color: #059669;
        border-color: #a7f3d0;
    }

    .action-btn.action-warning:hover {
        background: #fffbeb;
        color: #d97706;
        border-color: #fcd34d;
    }

    .action-btn.action-danger:hover {
        background: #fff1f2;
        color: #e11d48;
        border-color: #fecdd3;
    }

    .inline-form { display: inline-block; }

    /* ========== ÉTAT VIDE ========== */
    .empty-cell { padding: 0 !important; }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        max-width: 420px;
        margin: 0 auto;
        padding: clamp(2.5rem, 6vw, 4rem) 1rem;
        text-align: center;
    }

    .empty-icon {
        font-size: clamp(2rem, 5vw, 3rem);
        color: #cbd5e1;
        margin-bottom: 0.5rem;
    }

    .empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--c-ink-700);
        margin: 0;
    }

    .empty-text {
        color: var(--c-ink-400);
        font-size: 0.9rem;
        margin: 0;
        line-height: 1.55;
    }

    .empty-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 1rem;
    }

    /* ========== PAGINATION ========== */
    .pagination-wrapper {
        margin-top: clamp(1rem, 2.5vw, 1.75rem);
        display: flex;
        justify-content: center;
        overflow-x: auto;
        padding: .25rem 0;
    }
    .pagination-wrapper :deep(> *) { max-width: 100%; }

    /* ========== ANIMATIONS BIDIRECTIONNELLES ========== */
    .index-container [data-reveal] {
        opacity: 0;
        transform: translateY(32px) scale(.985);
        filter: blur(6px);
        transition:
            opacity 700ms var(--ease-out-expo),
            transform 700ms var(--ease-out-expo),
            filter 700ms var(--ease-out-expo);
        will-change: opacity, transform, filter;
    }

    .index-container [data-reveal="auto"][data-scroll-dir="up"]   { transform: translateY(-32px); }
    .index-container [data-reveal="auto"][data-scroll-dir="down"] { transform: translateY(32px); }

    .index-container [data-reveal].is-visible {
        opacity: 1;
        transform: translate(0, 0) scale(1);
        filter: blur(0);
    }

    .index-container [data-delay="1"] { transition-delay: 60ms; }
    .index-container [data-delay="2"] { transition-delay: 120ms; }
    .index-container [data-delay="3"] { transition-delay: 180ms; }
    .index-container [data-delay="4"] { transition-delay: 240ms; }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
        .index-container [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
        .stat-card:hover, .stat-card:hover .stat-icon, .year-avatar,
        .data-table tbody tr:hover .year-avatar,
        .btn-primary:hover, .btn-secondary:hover, .btn-outline:hover,
        .btn-reset:hover, .action-btn:hover { transform: none; }
    }

    /* ========== RESPONSIVE — TABLETTE ========== */
    @media (min-width: 640px) {
        .page-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    /* ========== RESPONSIVE MOBILE (≤ 767px) — Cartes ========== */
    @media (max-width: 767px) {
        .table-card {
            background: transparent;
            box-shadow: none;
            border: none;
            border-radius: 0;
            overflow: visible;
        }

        .table-wrapper { overflow-x: visible; }

        .data-table {
            display: block;
            min-width: 0;
            font-size: 0.92rem;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead { display: none; }
        .data-table tbody { display: block; }

        .data-table tbody tr {
            display: block;
            background: var(--c-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--c-gray-100);
            padding: 1rem;
            margin-bottom: 1rem;
            transition: box-shadow 300ms var(--ease-soft), transform 300ms var(--ease-soft);
        }

        .data-table tbody tr:not(.empty-row):hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .data-table tbody tr.row-active {
            background: linear-gradient(135deg, #eef2ff, #fff);
            border-color: #c7d2fe;
        }

        .data-table td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.5rem 0 !important;
            border: none;
            text-align: right;
            min-height: 40px;
        }

        .data-table td::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--c-ink-400);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            flex-shrink: 0;
            text-align: left;
            min-width: 90px;
        }

        /* Cellule Année : avatar + nom à droite */
        .data-table td[data-label="Année"] { justify-content: space-between; }
        .data-table td[data-label="Année"]::before { order: 0; }
        .data-table td[data-label="Année"] .cell-year {
            order: 1;
            justify-content: flex-end;
            margin-left: auto;
        }

        /* Groupe badges alignés à droite */
        .badge-group { justify-content: flex-end; }

        /* Effectif aligné à droite */
        .effectif { align-items: flex-end; min-width: 0; }

        /* Actions en pied de carte */
        .data-table td.action-cell {
            justify-content: flex-end;
            padding: 0.75rem 0 0 !important;
            border-top: 1px solid var(--c-gray-100);
            margin-top: 0.5rem;
            min-height: auto;
        }
        .data-table td.action-cell::before { display: none; }

        .action-bar { width: 100%; justify-content: flex-end; gap: 0.5rem; }

        .action-btn {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: var(--c-white);
            border-color: var(--c-gray-200);
        }

        /* Ligne vide */
        .data-table tr.empty-row {
            background: var(--c-white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--c-gray-100);
            padding: 0;
        }
        .data-table tr.empty-row td { display: block; padding: 0 !important; }
        .data-table tr.empty-row td::before { display: none; }

        .period { justify-content: flex-end; }
        .cell-primary { text-align: right; }
    }

    /* ========== RESPONSIVE — PETIT MOBILE (≤ 480px) ========== */
    @media (max-width: 480px) {
        .index-container { padding-inline: .85rem; }

        .btn-primary, .btn-secondary, .btn-outline {
            padding: .75rem 1.15rem;
            font-size: .86rem;
        }

        .data-table td {
            font-size: 0.88rem;
            min-height: 38px;
        }
        .data-table td::before { font-size: 0.66rem; }

        .action-btn { width: 40px; height: 40px; font-size: 0.88rem; }

        .year-avatar { width: 36px; height: 36px; font-size: 0.78rem; }

        .badge { font-size: 0.68rem; padding: 0.25rem 0.55rem; }
        .badge i { display: none; }
    }

    /* ========== RESPONSIVE — TRÈS PETIT MOBILE (≤ 360px) ========== */
    @media (max-width: 360px) {
        .data-table td {
            font-size: 0.84rem;
            padding: 0.4rem 0 !important;
            gap: 0.5rem;
        }
        .data-table td::before { font-size: 0.62rem; min-width: 80px; }
    }

    /* ========== IMPRESSION ========== */
    @media print {
        .index-container { padding: 0; max-width: 100%; }
        .header-actions, .filter-bar, .action-cell, .pagination-wrapper,
        .empty-actions { display: none !important; }
        .stat-card, .table-card {
            box-shadow: none;
            border: 1px solid #ddd;
            break-inside: avoid;
        }
        .index-container [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
    }
</style>

<script>
    (function () {
        'use strict';

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /* ============================================================
           DÉTECTION DE DIRECTION DE SCROLL
        ============================================================ */
        let lastY = window.scrollY;
        let scrollDir = 'down';

        window.addEventListener('scroll', () => {
            const y = window.scrollY;
            if (Math.abs(y - lastY) > 4) {
                scrollDir = y > lastY ? 'down' : 'up';
                lastY = y;
            }
        }, { passive: true });

        /* ============================================================
           ANIMATIONS BIDIRECTIONNELLES
        ============================================================ */
        function initReveal() {
            const els = document.querySelectorAll('.index-container [data-reveal]');
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
</script>
@endsection