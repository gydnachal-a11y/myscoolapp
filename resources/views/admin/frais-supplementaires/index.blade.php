@extends('layouts.admin')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- EN-TÊTE --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="index-header" data-reveal="auto">
        <div class="index-header-text">
            <h1 class="index-title"><strong>Frais</strong> supplémentaires</h1>
            <p class="index-subtitle">
                Gérez les frais ponctuels en dehors de la scolarité
                @if(request()->hasAny(['search', 'est_ouvert']))
                    <span class="filter-indicator"> • Filtres actifs</span>
                @endif
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.paiement-frais-supplementaires.index') }}" class="btn-secondary">
                <i class="fa-solid fa-money-bill-wave"></i> <span>Paiements</span>
            </a>
            <a href="{{ route('admin.frais-supplementaires.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> <span>Nouveau frais</span>
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- STATISTIQUES --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="stats-grid">
        <div class="stat-card border-indigo" data-reveal="auto" data-delay="1">
            <div class="stat-icon"><i class="fa-solid fa-tag" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totalFrais, 0, ',', ' ') }}</p>
                <p class="stat-label">Total frais</p>
            </div>
        </div>

        <div class="stat-card border-emerald" data-reveal="auto" data-delay="2">
            <div class="stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totalOuverts, 0, ',', ' ') }}</p>
                <p class="stat-label">Ouverts</p>
            </div>
        </div>

        <div class="stat-card border-rose" data-reveal="auto" data-delay="3">
            <div class="stat-icon"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totalFermes, 0, ',', ' ') }}</p>
                <p class="stat-label">Fermés</p>
            </div>
        </div>

        <div class="stat-card border-purple" data-reveal="auto" data-delay="4">
            <div class="stat-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($montantTotal, 0, ',', ' ') }} $</p>
                <p class="stat-label">Montant total (USD)</p>
            </div>
        </div>

        <div class="stat-card border-amber" data-reveal="auto" data-delay="5">
            <div class="stat-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($montantTotal * $tauxChange, 0, ',', ' ') }} FC</p>
                <p class="stat-label">Montant total (FC)</p>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- FILTRES --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="filter-card" data-reveal="auto" data-delay="1">
        <button type="button" class="filter-toggle" id="filterToggle" aria-expanded="false" aria-controls="filterForm">
            <span class="filter-toggle-label">
                <i class="fa-solid fa-sliders"></i>
                Filtres
                @php
                    $activeFilters = collect(['search', 'est_ouvert'])
                        ->filter(fn($k) => request()->filled($k))->count();
                @endphp
                @if($activeFilters > 0)
                    <span class="filter-badge">{{ $activeFilters }}</span>
                @endif
            </span>
            <i class="fa-solid fa-chevron-down filter-chevron"></i>
        </button>

        <form method="GET" action="{{ route('admin.frais-supplementaires.index') }}" class="filter-form" id="filterForm">
            <div class="filter-field">
                <label class="filter-label" for="f-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Recherche
                </label>
                <div class="filter-input-wrap">
                    <input type="text" name="search" id="f-search" class="filter-input"
                           placeholder="Libellé ou description..."
                           value="{{ request('search') }}">
                </div>
            </div>

            <div class="filter-field">
                <label class="filter-label" for="f-statut">
                    <i class="fa-regular fa-circle" aria-hidden="true"></i> Statut
                </label>
                <select name="est_ouvert" id="f-statut" class="filter-select" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <option value="1" @selected(request('est_ouvert') == '1')>Ouvert</option>
                    <option value="0" @selected(request('est_ouvert') == '0')>Fermé</option>
                </select>
            </div>

            <div class="filter-field">
                <label class="filter-label" for="f-per-page">
                    <i class="fa-solid fa-list" aria-hidden="true"></i> Lignes / page
                </label>
                <select name="per_page" id="f-per-page" class="filter-select" onchange="this.form.submit()">
                    @foreach([15, 30, 50, 100] as $n)
                        <option value="{{ $n }}" @selected(request('per_page') == $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-filter" aria-label="Appliquer les filtres">
                    <i class="fa-solid fa-filter" aria-hidden="true"></i> Filtrer
                </button>

                @if(request()->hasAny(['search', 'est_ouvert']))
                    <a href="{{ route('admin.frais-supplementaires.index') }}" class="btn-reset">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Réinitialiser
                    </a>
                @endif
            </div>
        </form>

        <div class="filter-result-count" role="status" aria-live="polite">
            <i class="fa-solid fa-list-check" aria-hidden="true"></i>
            {{ $frais->total() }} frais trouvé(s)
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- TABLEAU --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="table-card" data-reveal="auto" data-delay="2">
        <div class="table-header">
            <div class="table-title">
                <i class="fa-solid fa-list title-icon-sm" aria-hidden="true"></i>
                <span>Frais enregistrés</span>
                <span class="badge-count">{{ $frais->total() }}</span>
            </div>
            <div class="table-actions">
                <span class="text-muted text-sm">Taux : {{ number_format($tauxChange, 2, ',', ' ') }} FC/USD</span>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Libellé</th>
                        <th scope="col" class="text-right">Montant</th>
                        <th scope="col">Période</th>
                        <th scope="col">Portée</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($frais as $fraisItem)
                        @php
                            /* ---------- Calculs ---------- */
                            $montantUSD  = number_format((float) $fraisItem->montant, 0, ',', ' ');
                            $montantFC   = number_format((float) $fraisItem->montant * $tauxChange, 0, ',', ' ');
                            $estOuvert   = (bool) $fraisItem->est_ouvert;
                            $estToutes   = (bool) $fraisItem->est_pour_toutes_salles;
                            $nbSalles    = $fraisItem->salles->count();

                            /* ---------- Portée ---------- */
                            $libellePortee = $estToutes
                                ? 'Toutes les salles'
                                : $nbSalles . ' salle' . ($nbSalles > 1 ? 's' : '');
                            $badgePortee = $estToutes ? 'badge-blue' : 'badge-gray';

                            /* ---------- Statut ---------- */
                            $badgeStatut = $estOuvert ? 'badge-green' : 'badge-red';
                            $labelStatut = $estOuvert ? 'Ouvert' : 'Fermé';

                            /* ---------- Période ---------- */
                            $debut = $fraisItem->date_debut?->format('d/m/Y') ?? '—';
                            $fin   = $fraisItem->date_fin?->format('d/m/Y')   ?? '—';
                        @endphp
                        <tr>
                            <td data-label="Libellé">
                                <div class="cell-primary">{{ $fraisItem->libelle }}</div>
                                @if($fraisItem->description)
                                    <div class="cell-description">{{ \Illuminate\Support\Str::limit($fraisItem->description, 60) }}</div>
                                @endif
                            </td>

                            <td data-label="Montant" class="text-right">
                                <div class="cell-money">
                                    <strong>{{ $montantUSD }} $</strong>
                                    <span class="text-muted small">{{ $montantFC }} FC</span>
                                </div>
                            </td>

                            <td data-label="Période">
                                <span class="date-cell">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    {{ $debut }} → {{ $fin }}
                                </span>
                            </td>

                            <td data-label="Portée">
                                <span class="badge {{ $badgePortee }}">
                                    <i class="fa-solid {{ $estToutes ? 'fa-globe' : 'fa-door-open' }}" aria-hidden="true"></i>
                                    {{ $libellePortee }}
                                </span>
                            </td>

                            <td data-label="Statut">
                                <span class="badge {{ $badgeStatut }}">
                                    <i class="fa-solid fa-circle" aria-hidden="true"></i>
                                    {{ $labelStatut }}
                                </span>
                            </td>

                            <td data-label="Actions" class="text-center action-cell">
                                <div class="action-icons">
                                    {{-- Paiement --}}
                                    <a href="{{ route('admin.paiement-frais-supplementaires.create', ['frais' => $fraisItem->id]) }}"
                                       class="action-icon" title="Enregistrer un paiement"
                                       aria-label="Enregistrer un paiement pour {{ $fraisItem->libelle }}">
                                        <i class="fa-solid fa-money-bill" aria-hidden="true"></i>
                                    </a>

                                    {{-- Toggle ouverture (PATCH) --}}
                                    <form action="{{ route('admin.frais-supplementaires.toggle', $fraisItem) }}"
                                          method="POST" class="inline-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="action-icon {{ $estOuvert ? 'action-warning' : 'action-success' }}"
                                                title="{{ $estOuvert ? 'Fermer' : 'Ouvrir' }}"
                                                aria-label="{{ $estOuvert ? 'Fermer' : 'Ouvrir' }} le frais">
                                            <i class="fa-solid fa-power-off" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    {{-- Modifier --}}
                                    <a href="{{ route('admin.frais-supplementaires.edit', $fraisItem) }}"
                                       class="action-icon" title="Modifier"
                                       aria-label="Modifier le frais">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    {{-- Supprimer --}}
                                    <form action="{{ route('admin.frais-supplementaires.destroy', $fraisItem) }}"
                                          method="POST" class="inline-form"
                                          onsubmit="return confirm('Supprimer définitivement ce frais ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-icon danger"
                                                title="Supprimer"
                                                aria-label="Supprimer le frais">
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
                                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                                    <p>Aucun frais supplémentaire enregistré.</p>
                                    <a href="{{ route('admin.frais-supplementaires.create') }}" class="btn-secondary">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Créer un frais
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="text-muted text-sm">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                Taux de change utilisé : {{ number_format($tauxChange, 2, ',', ' ') }} FC/USD
            </span>
        </div>

        @if($frais->hasPages())
            <div class="pagination-container">
                {{ $frais->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    /* ════════════════════════════════════════════════════════
       TOKENS
       ════════════════════════════════════════════════════════ */
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

        max-width: 1400px;
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

    /* ════════════════════════════════════════════════════════
       EN-TÊTE
       ════════════════════════════════════════════════════════ */
    .index-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
        margin-bottom: clamp(1.5rem, 3vw, 2.25rem);
    }

    .index-title {
        font-size: clamp(1.4rem, 3vw, 1.85rem);
        font-weight: 700;
        color: var(--c-ink-900);
        margin: 0 0 .25rem;
        line-height: 1.15;
    }
    .index-title strong { font-weight: 800; }

    .index-subtitle {
        color: var(--c-ink-400);
        font-size: clamp(.85rem, 1.4vw, .95rem);
        margin: 0;
        line-height: 1.5;
    }
    .filter-indicator { color: var(--c-primary); font-weight: 700; }

    .header-actions {
        display: flex;
        flex-direction: column;
        gap: .6rem;
        align-items: stretch;
    }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .btn-primary,
    .btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: .8rem 1.4rem;
        min-height: 46px;
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: .92rem;
        text-decoration: none;
        transition: all 400ms var(--ease-out-expo);
        box-shadow: var(--shadow-sm);
        white-space: nowrap;
        border: none;
        cursor: pointer;
        text-align: center;
    }

    .btn-primary {
        background: var(--c-ink-900);
        color: #fff;
    }
    .btn-primary:hover {
        background: var(--c-primary);
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(99,102,241,.35);
    }

    .btn-secondary {
        background: var(--c-white);
        border: 1.5px solid var(--c-gray-200);
        color: var(--c-ink-500);
    }
    .btn-secondary:hover {
        border-color: var(--c-primary);
        color: var(--c-primary);
        background: var(--c-gray-50);
        transform: translateY(-2px);
    }

    /* ════════════════════════════════════════════════════════
       STATS
       ════════════════════════════════════════════════════════ */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: clamp(.75rem, 1.5vw, 1.25rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 2rem);
    }

    .stat-card {
        background: var(--c-white);
        padding: clamp(1rem, 2vw, 1.25rem) clamp(1rem, 2vw, 1.35rem);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: clamp(.75rem, 1.5vw, 1rem);
        border-left: 4px solid;
        transition: transform 400ms var(--ease-out-expo), box-shadow 400ms var(--ease-out-expo);
        min-width: 0;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }

    .border-indigo  { border-left-color: #667eea; }
    .border-emerald { border-left-color: #10b981; }
    .border-rose    { border-left-color: #ef4444; }
    .border-purple  { border-left-color: #a855f7; }
    .border-amber   { border-left-color: #f59e0b; }

    .stat-icon {
        width: clamp(42px, 5vw, 48px);
        height: clamp(42px, 5vw, 48px);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: clamp(1.05rem, 2vw, 1.2rem);
        flex-shrink: 0;
        transition: transform 400ms var(--ease-out-expo);
    }
    .stat-card:hover .stat-icon { transform: scale(1.08) rotate(-5deg); }

    .border-indigo  .stat-icon { background: #eef2ff; color: #667eea; }
    .border-emerald .stat-icon { background: #ecfdf5; color: #10b981; }
    .border-rose    .stat-icon { background: #fef2f2; color: #ef4444; }
    .border-purple  .stat-icon { background: #faf5ff; color: #a855f7; }
    .border-amber   .stat-icon { background: #fffbeb; color: #f59e0b; }

    .stat-content { min-width: 0; flex: 1; }

    .stat-label {
        font-size: clamp(.7rem, 1.2vw, .78rem);
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--c-ink-400);
        font-weight: 700;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .stat-value {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: clamp(1.2rem, 2.5vw, 1.5rem);
        font-weight: 800;
        color: var(--c-ink-900);
        margin: 0 0 .15rem;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }

    /* ════════════════════════════════════════════════════════
       FILTRES
       ════════════════════════════════════════════════════════ */
    .filter-card {
        background: var(--c-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        padding: clamp(1rem, 2vw, 1.5rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 2rem);
        border: 1px solid var(--c-gray-100);
    }

    .filter-toggle {
        display: none;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem 1.1rem;
        background: var(--c-gray-50);
        border: 1.5px solid var(--c-gray-200);
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: .95rem;
        color: var(--c-ink-700);
        cursor: pointer;
        transition: all 300ms var(--ease-soft);
        min-height: 48px;
    }
    .filter-toggle:hover { border-color: var(--c-primary); color: var(--c-primary); }
    .filter-toggle-label { display: inline-flex; align-items: center; gap: .6rem; }

    .filter-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 7px;
        background: var(--c-primary);
        color: #fff;
        border-radius: var(--radius-full);
        font-size: .72rem;
        font-weight: 700;
    }

    .filter-chevron {
        font-size: .85rem;
        transition: transform 400ms var(--ease-out-expo);
        color: var(--c-ink-400);
    }
    .filter-toggle[aria-expanded="true"] .filter-chevron { transform: rotate(180deg); }

    .filter-form {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-top: 0;
    }

    .filter-field {
        display: flex;
        flex-direction: column;
        gap: .45rem;
        min-width: 0;
    }

    .filter-label {
        font-size: .78rem;
        font-weight: 700;
        color: var(--c-ink-600);
        letter-spacing: .02em;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .filter-label i { color: var(--c-primary); font-size: .75rem; }

    .filter-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .filter-input,
    .filter-select {
        width: 100%;
        padding: .8rem 1rem;
        min-height: 46px;
        border: 1.5px solid var(--c-gray-200);
        border-radius: var(--radius-md);
        background: var(--c-gray-50);
        font-family: inherit;
        font-size: .92rem;
        color: var(--c-ink-900);
        transition: all 300ms var(--ease-soft);
        outline: none;
        -webkit-appearance: none;
        appearance: none;
    }

    .filter-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2364748b' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        padding-right: 2.5rem;
        cursor: pointer;
    }

    .filter-input:focus,
    .filter-select:focus {
        border-color: var(--c-primary);
        background: var(--c-white);
        box-shadow: 0 0 0 4px rgba(99,102,241,.12);
    }

    .filter-actions {
        display: flex;
        gap: .6rem;
        flex-direction: column;
        align-items: stretch;
    }

    .btn-filter,
    .btn-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: .8rem 1.35rem;
        min-height: 46px;
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: .9rem;
        cursor: pointer;
        transition: all 300ms var(--ease-soft);
        text-decoration: none;
        border: none;
        white-space: nowrap;
    }
    .btn-filter {
        background: var(--c-ink-900);
        color: #fff;
    }
    .btn-filter:hover {
        background: var(--c-primary);
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(99,102,241,.3);
    }
    .btn-reset {
        background: var(--c-white);
        border: 1.5px solid var(--c-gray-200);
        color: var(--c-ink-500);
    }
    .btn-reset:hover {
        border-color: var(--c-primary);
        color: var(--c-primary);
        background: var(--c-gray-50);
    }

    .filter-result-count {
        margin-top: 1rem;
        padding-top: .85rem;
        border-top: 1px dashed var(--c-gray-200);
        font-size: .88rem;
        color: var(--c-ink-500);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .filter-result-count i { color: var(--c-primary); }

    /* ════════════════════════════════════════════════════════
       TABLEAU
       ════════════════════════════════════════════════════════ */
    .table-card {
        background: var(--c-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--c-gray-100);
        overflow: hidden;
        width: 100%;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--c-gray-100);
        flex-wrap: wrap;
        gap: .5rem;
    }

    .table-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--c-ink-900);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .title-icon-sm { color: var(--c-primary); font-size: 1rem; }

    .badge-count {
        background: var(--c-gray-100);
        color: var(--c-ink-600);
        padding: .15rem .65rem;
        border-radius: var(--radius-full);
        font-size: .75rem;
        font-weight: 700;
    }

    .table-actions { font-size: .82rem; }
    .text-muted { color: var(--c-ink-400); }
    .text-sm { font-size: .8rem; }

    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .9rem;
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

    .data-table .text-right  { text-align: right; }
    .data-table .text-center { text-align: center; }

    /* ✅ FIX DÉFINITIF — Restaure <table> en desktop */
    @media (min-width: 768px) {
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
        .index-container .data-table thead th:nth-child(1) { width: 28%; } /* Libellé */
        .index-container .data-table thead th:nth-child(2) { width: 14%; } /* Montant */
        .index-container .data-table thead th:nth-child(3) { width: 20%; } /* Période */
        .index-container .data-table thead th:nth-child(4) { width: 14%; } /* Portée */
        .index-container .data-table thead th:nth-child(5) { width: 10%; } /* Statut */
        .index-container .data-table thead th:nth-child(6) { width: 14%; } /* Actions */
    }

    /* Empêche le débordement du contenu */
    .cell-primary {
        font-weight: 600;
        color: var(--c-ink-900);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cell-description {
        color: var(--c-ink-400);
        font-size: .8rem;
        margin-top: .25rem;
        line-height: 1.4;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cell-money {
        display: flex;
        flex-direction: column;
        gap: .1rem;
    }
    .cell-money strong {
        color: var(--c-ink-900);
        font-weight: 700;
        font-size: .92rem;
    }
    .cell-money .small {
        font-size: .75rem;
        color: var(--c-ink-400);
        font-weight: 500;
    }

    .date-cell {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--c-ink-500);
        white-space: nowrap;
    }
    .date-cell i { color: var(--c-ink-400); }

    /* ════════════════════════════════════════════════════════
       BADGES
       ════════════════════════════════════════════════════════ */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: .3rem .7rem;
        border-radius: var(--radius-full);
        font-size: .73rem;
        font-weight: 700;
        white-space: nowrap;
        line-height: 1.4;
    }
    .badge i { font-size: .7rem; }
    .badge-blue  { background: #dbeafe; color: #1d4ed8; }
    .badge-gray  { background: var(--c-gray-100); color: var(--c-ink-500); }
    .badge-green { background: #dcfce7; color: #16a34a; }
    .badge-red   { background: #fee2e2; color: #dc2626; }

    /* ════════════════════════════════════════════════════════
       ACTIONS
       ════════════════════════════════════════════════════════ */
    .action-cell { white-space: nowrap; }

    .action-icons {
        display: inline-flex;
        justify-content: center;
        gap: .35rem;
    }

    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        color: var(--c-ink-500);
        text-decoration: none;
        transition: all 300ms var(--ease-soft);
        background: transparent;
        border: 1.5px solid transparent;
        cursor: pointer;
        font-size: .9rem;
        flex-shrink: 0;
    }
    .action-icon:hover {
        background: var(--c-gray-100);
        color: var(--c-ink-900);
        border-color: var(--c-gray-200);
        transform: translateY(-1px);
    }
    .action-icon.danger:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecaca;
    }
    .action-icon.action-success:hover {
        background: #ecfdf5;
        color: #059669;
        border-color: #a7f3d0;
    }
    .action-icon.action-warning:hover {
        background: #fffbeb;
        color: #d97706;
        border-color: #fcd34d;
    }

    .inline-form { display: inline-block; }

    .table-footer {
        padding: 1rem 1.5rem;
        background: var(--c-gray-50);
        border-top: 1px solid var(--c-gray-100);
        text-align: right;
        font-size: .82rem;
        color: var(--c-ink-500);
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
    }
    .table-footer i { color: var(--c-primary); }

    /* ════════════════════════════════════════════════════════
       ÉTAT VIDE
       ════════════════════════════════════════════════════════ */
    .empty-cell { padding: 0 !important; }
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: clamp(2.5rem, 6vw, 4rem) 1rem;
        text-align: center;
        color: var(--c-ink-400);
    }
    .empty-state i {
        font-size: clamp(2rem, 5vw, 3rem);
        color: #cbd5e1;
    }
    .empty-state p {
        font-size: .95rem;
        margin: 0;
        color: var(--c-ink-400);
    }

    /* ════════════════════════════════════════════════════════
       PAGINATION
       ════════════════════════════════════════════════════════ */
    .pagination-container {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--c-gray-100);
        display: flex;
        justify-content: center;
        overflow-x: auto;
    }

    /* ════════════════════════════════════════════════════════
       ANIMATIONS BIDIRECTIONNELLES
       ════════════════════════════════════════════════════════ */
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
    .index-container [data-delay="5"] { transition-delay: 300ms; }

    @media (prefers-reduced-motion: reduce) {
        .index-container [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
        .stat-card:hover, .btn-primary:hover, .btn-secondary:hover,
        .btn-filter:hover, .action-icon:hover,
        .data-table tbody tr:hover { transform: none; }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE — TABLETTE (≥ 640px)
       ════════════════════════════════════════════════════════ */
    @media (min-width: 640px) {
        .index-header {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .header-actions {
            flex-direction: row;
            align-items: center;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .filter-form {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            align-items: end;
        }
        .filter-actions {
            grid-column: 1 / -1;
            flex-direction: row;
            justify-content: flex-end;
        }
        .filter-actions > * { flex: 0 1 auto; }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE — DESKTOP (≥ 1024px)
       ════════════════════════════════════════════════════════ */
    @media (min-width: 1024px) {
        .filter-form {
            grid-template-columns: 2fr 1fr 1fr auto;
            align-items: end;
            gap: .9rem;
        }
        .filter-actions {
            grid-column: auto;
            flex-direction: row;
            justify-content: flex-end;
        }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE MOBILE (≤ 767px) — Cartes
       ════════════════════════════════════════════════════════ */
    @media (max-width: 767px) {
        /* Filtres repliables */
        .filter-toggle { display: flex; }

        .filter-form {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            margin-top: 0;
            transition:
                max-height 500ms var(--ease-out-expo),
                opacity 350ms ease,
                margin-top 350ms ease,
                padding-top 350ms ease;
            padding-top: 0;
        }
        .filter-form.is-open {
            max-height: 1200px;
            opacity: 1;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--c-gray-100);
        }

        .filter-actions {
            flex-direction: row;
            gap: .5rem;
        }
        .filter-actions > * { flex: 1; }

        /* Tableau → cartes */
        .table-wrapper { overflow-x: visible; }

        .data-table {
            display: block;
            min-width: 0;
            font-size: .92rem;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead { display: none; }
        .data-table tbody { display: block; }

        .data-table tr {
            display: block;
            background: var(--c-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--c-gray-100);
            margin: 1rem;
            padding: 1rem 1.15rem;
            transition: box-shadow 300ms var(--ease-soft), transform 300ms var(--ease-soft);
        }
        .data-table tr:not(.empty-row):hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .data-table tbody td {
            display: block;
            padding: .6rem 0 !important;
            border-bottom: 1px solid var(--c-gray-100);
            text-align: left !important;
        }
        .data-table tbody td:last-child { border-bottom: none; }

        .data-table tbody td::before {
            content: attr(data-label);
            display: block;
            font-weight: 700;
            color: var(--c-ink-400);
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: .35rem;
        }

        /* Actions en pied de carte */
        .data-table td.action-cell {
            padding-top: 1rem !important;
            border-top: 1.5px solid var(--c-gray-100);
            margin-top: .5rem;
        }
        .data-table td.action-cell::before { display: none; }

        .action-icons {
            justify-content: flex-start;
            gap: .5rem;
            width: 100%;
        }

        .action-icon {
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
            margin: 1rem;
        }
        .data-table tr.empty-row td { display: block; padding: 0 !important; }
        .data-table tr.empty-row td::before { display: none; }

        .badge-count { display: none; }

        .table-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .table-footer {
            text-align: center;
            justify-content: center;
        }

        .cell-money { align-items: flex-start; }
        .date-cell { white-space: normal; }

        .cell-primary,
        .cell-description {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
        }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE — PETIT MOBILE (≤ 480px)
       ════════════════════════════════════════════════════════ */
    @media (max-width: 480px) {
        .index-container { padding-inline: .85rem; }

        .index-title { font-size: 1.3rem; }
        .index-subtitle { font-size: .82rem; }

        .stat-card { padding: .85rem .95rem; gap: .7rem; }
        .stat-icon { width: 36px; height: 36px; font-size: .95rem; }
        .stat-value { font-size: 1.1rem; }
        .stat-label { font-size: .65rem; }

        .btn-primary, .btn-secondary { width: 100%; font-size: .86rem; }

        .filter-input,
        .filter-select { font-size: .85rem; padding: .7rem .85rem; }

        .data-table tr { padding: .85rem .95rem; margin: .75rem; }

        .action-icon { width: 40px; height: 40px; font-size: .85rem; }

        .filter-result-count { font-size: .82rem; }
        .table-footer { font-size: .75rem; }
    }

    /* ════════════════════════════════════════════════════════
       IMPRESSION
       ════════════════════════════════════════════════════════ */
    @media print {
        .index-container { padding: 0; max-width: 100%; }
        .index-header, .filter-card, .pagination-container,
        .action-icons, .stats-grid, .table-footer,
        .table-actions { display: none !important; }
        .table-card { box-shadow: none; border: 1px solid #ddd; }
        .data-table { font-size: .8rem; min-width: 0; }
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

        /* ============================================================
           FILTRES REPLIABLES (MOBILE)
        ============================================================ */
        function initFilterToggle() {
            const toggle = document.getElementById('filterToggle');
            const form   = document.getElementById('filterForm');
            if (!toggle || !form) return;

            const mq = window.matchMedia('(max-width: 767px)');

            const handleViewport = () => {
                if (mq.matches) {
                    const hasActive = new URLSearchParams(window.location.search).toString().length > 0;
                    if (hasActive) {
                        form.classList.add('is-open');
                        toggle.setAttribute('aria-expanded', 'true');
                    } else {
                        form.classList.remove('is-open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                } else {
                    form.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            };

            toggle.addEventListener('click', () => {
                const isOpen = form.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', String(isOpen));
            });

            mq.addEventListener('change', handleViewport);
            handleViewport();
        }

        /* ============================================================
           INIT
        ============================================================ */
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initReveal();
                initFilterToggle();
            });
        } else {
            initReveal();
            initFilterToggle();
        }
    })();
</script>
@endsection 