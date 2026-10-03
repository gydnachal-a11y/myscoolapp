@extends('layouts.admin')

@section('content')
<div class="index-container" x-data="infoElevesFiltre()" x-init="init()">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    {{-- ============ EN-TÊTE ============ --}}
    <div class="index-header no-print" data-reveal="auto">
        <div class="index-header-text">
            <h1 class="index-title"><strong>Info élèves</strong></h1>
            <p class="index-subtitle">Liste des élèves par option, salle, section et session</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.eleves.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> <span>Nouvel élève</span>
            </a>
        </div>
    </div>

    {{-- ============ RÉSUMÉ RAPIDE ============ --}}
    <div class="stats-grid stats-grid-5">
        <div class="stat-card border-indigo" data-reveal="auto" data-delay="1">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-content">
                <p class="stat-label">Inscriptions</p>
                <p class="stat-value">{{ $inscriptions->total() }}</p>
            </div>
        </div>
        <div class="stat-card border-emerald" data-reveal="auto" data-delay="2">
            <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
            <div class="stat-content">
                <p class="stat-label">Salles de classe</p>
                <p class="stat-value">{{ $salles->count() }}</p>
            </div>
        </div>
        <div class="stat-card border-amber" data-reveal="auto" data-delay="3">
            <div class="stat-icon"><i class="fa-solid fa-cog"></i></div>
            <div class="stat-content">
                <p class="stat-label">Options</p>
                <p class="stat-value">{{ $options->count() }}</p>
            </div>
        </div>
        <div class="stat-card border-purple" data-reveal="auto" data-delay="4">
            <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div class="stat-content">
                <p class="stat-label">Sections</p>
                <p class="stat-value">{{ $sections->count() }}</p>
            </div>
        </div>
        <div class="stat-card border-cyan" data-reveal="auto" data-delay="5">
            <div class="stat-icon"><i class="fa-solid fa-calendar-alt"></i></div>
            <div class="stat-content">
                <p class="stat-label">Sessions</p>
                <p class="stat-value">{{ $sessions->count() }}</p>
            </div>
        </div>
    </div>

    {{-- ============ BARRE D'OUTILS EXPORTS ============ --}}
    <div class="toolbar no-print" data-reveal="auto" data-delay="1">
        <div class="toolbar-group">
            <span class="toolbar-label">Exports :</span>
            <div class="export-buttons">
                <a href="{{ route('admin.info-eleves.export.pdf', request()->query()) }}" class="export-btn pdf" title="Télécharger PDF">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('admin.info-eleves.export.csv', request()->query()) }}" class="export-btn csv" title="Télécharger CSV">
                    <i class="fa-solid fa-file-excel"></i> CSV
                </a>
                <a href="{{ route('admin.info-eleves.export.xml', request()->query()) }}" class="export-btn xml" title="Télécharger XML">
                    <i class="fa-solid fa-file-code"></i> XML
                </a>
                <a href="{{ route('admin.info-eleves.export.word', request()->query()) }}" class="export-btn doc" title="Télécharger DOC">
                    <i class="fa-solid fa-file-word"></i> DOC
                </a>
                <a href="{{ route('admin.info-eleves.imprimer', request()->query()) }}" target="_blank" class="export-btn print" title="Imprimer">
                    <i class="fa-solid fa-print"></i> Imprimer
                </a>
            </div>
        </div>
    </div>

    {{-- ============ FILTRES ============ --}}
    <div class="filter-card no-print" data-reveal="auto" data-delay="2">
        <button type="button" class="filter-toggle" id="filterToggle" aria-expanded="false" aria-controls="filterForm">
            <span class="filter-toggle-label">
                <i class="fa-solid fa-sliders"></i>
                Filtres
                @php
                    $activeFilters = collect([request('session'), request('section'), request('option'), request('salle'), request('recherche')])
                        ->filter(fn($v) => !empty($v))->count();
                @endphp
                @if($activeFilters > 0)
                    <span class="filter-badge">{{ $activeFilters }}</span>
                @endif
            </span>
            <i class="fa-solid fa-chevron-down filter-chevron"></i>
        </button>

        <form method="GET" action="{{ route('admin.info-eleves.index') }}" class="filter-form" id="filterForm">
            <div class="filter-field">
                <label class="filter-label" for="f-session">Session</label>
                <select name="session" id="f-session" x-model="session" class="filter-select" @change="filtrerSections(); submitForm()">
                    <option value="">Toutes les sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ request('session') == $session->id ? 'selected' : '' }}>
                            {{ $session->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label" for="section_select">Section</label>
                <select name="section" x-model="section" id="section_select" class="filter-select" @change="filtrerOptionsEtSalles(); submitForm()">
                    <option value="">Toutes les sections</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" data-session-id="{{ $section->session_id }}"
                                {{ request('section') == $section->id ? 'selected' : '' }}>
                            {{ $section->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label" for="option_select">Option</label>
                <select name="option" x-model="option" id="option_select" class="filter-select" @change="filtrerSalles()">
                    <option value="">Toutes les options</option>
                    @foreach($options as $opt)
                        <option value="{{ $opt->id }}" data-section-ids="{{ json_encode($opt->sallesDeClasse->pluck('section.id')->unique()->values()->toArray()) }}"
                                {{ request('option') == $opt->id ? 'selected' : '' }}>
                            {{ $opt->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label" for="salle_select">Salle de classe</label>
                <select name="salle" x-model="salle" id="salle_select" class="filter-select" @change="submitSalle()">
                    <option value="">Toutes les salles</option>
                    @foreach($salles as $salle)
                        <option value="{{ $salle->id }}"
                                data-option-id="{{ $salle->option_id }}"
                                data-section-id="{{ $salle->section_id }}"
                                {{ request('salle') == $salle->id ? 'selected' : '' }}>
                            {{ $salle->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label" for="f-recherche">Recherche</label>
                <div class="filter-input-wrap">
                    <i class="fa-solid fa-magnifying-glass filter-input-icon"></i>
                    <input type="text" id="f-recherche" name="recherche" value="{{ request('recherche') }}" placeholder="Nom, prénom..." class="filter-input">
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filter">
                    <i class="fa-solid fa-filter"></i> Filtrer
                </button>
                @if(request('session') || request('section') || request('option') || request('salle') || request('recherche'))
                    <a href="{{ route('admin.info-eleves.index') }}" class="btn-reset">
                        <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ============ TABLEAU / CARTES ============ --}}
    <div class="table-card" data-reveal="auto" data-delay="3">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Sexe</th>
                        <th>Âge</th>
                        <th>Session</th>
                        <th>Section</th>
                        <th>Année</th>
                        <th>Salle</th>
                        <th>Option</th>
                        <th class="text-right no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inscriptions as $ins)
                        @php
                            $eleve = $ins->eleve;
                            $age = $eleve?->date_naissance?->age;
                            $salle = $ins->salleDeClasse;
                            $section = $salle?->section;
                            $session = $section?->session;
                        @endphp
                        <tr>
                            <td data-label="Élève">
                                <div class="insc-cell">
                                    <div class="avatar">{{ strtoupper(substr($eleve?->nom ?? '?', 0, 1) . substr($eleve?->prenom ?? '?', 0, 1)) }}</div>
                                    <span class="cell-primary">{{ $eleve?->nom }} {{ $eleve?->prenom }}</span>
                                </div>
                            </td>
                            <td data-label="Sexe">
                                <span class="cell-text">{{ $eleve?->sexe_libelle ?? '—' }}</span>
                            </td>
                            <td data-label="Âge">
                                <span class="cell-text">{{ $age !== null ? $age . ' ans' : '—' }}</span>
                            </td>
                            <td data-label="Session">
                                <span class="cell-text">{{ $session?->nom ?? '—' }}</span>
                            </td>
                            <td data-label="Section">
                                <span class="cell-text">{{ $section?->nom ?? '—' }}</span>
                            </td>
                            <td data-label="Année">
                                <span class="cell-text">{{ optional($ins->anneeScolaire)->libelle ?? '—' }}</span>
                            </td>
                            <td data-label="Salle">
                                <span class="cell-text">{{ $salle?->nom ?? '—' }}</span>
                            </td>
                            <td data-label="Option">
                                <span class="cell-text">{{ $salle?->option?->nom ?? '—' }}</span>
                            </td>
                            <td data-label="Actions" class="text-right action-cell no-print">
                                <div class="action-icons">
                                    <a href="{{ route('admin.info-eleves.show', $ins->eleve_id) }}" class="action-icon" title="Voir fiche complète" aria-label="Voir fiche complète">
                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="9" class="empty-cell">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    <p>Aucun élève trouvé avec ces critères.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============ PAGINATION ============ --}}
    @if($inscriptions->hasPages())
        <div class="pagination-wrapper no-print" data-reveal="auto">
            {{ $inscriptions->appends(request()->query())->links() }}
        </div>
    @endif
</div>

<style>
    /* ============================================================
       POLICES & TOKENS
    ============================================================ */
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

    .index-container *,
    .index-container *::before,
    .index-container *::after { box-sizing: border-box; }

    .index-container h1,
    .index-container h2,
    .index-container h3 {
        font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        letter-spacing: -0.02em;
    }

    /* ============================================================
       EN-TÊTE
    ============================================================ */
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

    .header-actions {
        display: flex;
        flex-direction: column;
        gap: .6rem;
        align-items: stretch;
    }

    /* ============================================================
       BOUTON PRINCIPAL
    ============================================================ */
    .btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: .8rem 1.4rem;
        min-height: 46px;
        background: var(--c-ink-900);
        color: #fff;
        border-radius: var(--radius-md);
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
    .btn-primary:hover {
        background: var(--c-primary);
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(99,102,241,.35);
    }

    /* ============================================================
       STATS CARDS
    ============================================================ */
    .stats-grid {
        display: grid;
        gap: clamp(.75rem, 1.5vw, 1.25rem);
        margin-bottom: clamp(1.25rem, 2.5vw, 2rem);
    }

    .stats-grid-5 {
        grid-template-columns: 1fr;
    }

    .stat-card {
        background: var(--c-white);
        padding: clamp(1rem, 2vw, 1.35rem) clamp(1rem, 2vw, 1.4rem);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        gap: clamp(.75rem, 1.5vw, 1.1rem);
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
    .border-amber   { border-left-color: #f59e0b; }
    .border-purple  { border-left-color: #a855f7; }
    .border-cyan    { border-left-color: #06b6d4; }

    .stat-icon {
        width: clamp(42px, 5vw, 52px);
        height: clamp(42px, 5vw, 52px);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: clamp(1.1rem, 2vw, 1.4rem);
        flex-shrink: 0;
        transition: transform 400ms var(--ease-out-expo);
    }
    .stat-card:hover .stat-icon { transform: scale(1.08) rotate(-5deg); }

    .border-indigo  .stat-icon { background: #eef2ff; color: #667eea; }
    .border-emerald .stat-icon { background: #ecfdf5; color: #10b981; }
    .border-amber   .stat-icon { background: #fffbeb; color: #f59e0b; }
    .border-purple  .stat-icon { background: #faf5ff; color: #a855f7; }
    .border-cyan    .stat-icon { background: #ecfeff; color: #06b6d4; }

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
        font-size: clamp(1.4rem, 3vw, 1.75rem);
        font-weight: 800;
        color: var(--c-ink-900);
        margin: .15rem 0 0;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }

    /* ============================================================
       BARRE D'OUTILS
    ============================================================ */
    .toolbar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: clamp(1rem, 2vw, 1.5rem);
        background: var(--c-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        padding: 1rem 1.25rem;
        border: 1px solid var(--c-gray-100);
    }

    .toolbar-group {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .toolbar-label {
        font-size: .78rem;
        font-weight: 700;
        color: var(--c-ink-500);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    /* ============================================================
       EXPORT BUTTONS
    ============================================================ */
    .export-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(96px, 1fr));
        gap: .45rem;
        width: 100%;
    }

    .export-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: .6rem .85rem;
        min-height: 40px;
        border-radius: 10px;
        font-size: .78rem;
        font-weight: 700;
        color: #fff;
        text-decoration: none;
        transition: all 300ms var(--ease-out-expo);
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,.18);
        filter: brightness(1.08);
    }
    .export-btn.pdf   { background: #ef4444; }
    .export-btn.csv   { background: #22c55e; }
    .export-btn.xml   { background: #3b82f6; }
    .export-btn.doc   { background: #0ea5e9; }
    .export-btn.print { background: #475569; }

    /* ============================================================
       FILTRES
    ============================================================ */
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
    }

    .filter-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .filter-input-icon {
        position: absolute;
        left: 1rem;
        color: var(--c-ink-400);
        font-size: .85rem;
        pointer-events: none;
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

    .filter-input { padding-left: 2.4rem; }

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

    /* ============================================================
       TABLEAU
    ============================================================ */
    .table-card {
        background: var(--c-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        border: 1px solid var(--c-gray-100);
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

    .data-table tbody tr {
        transition: background 250ms ease;
    }
    .data-table tbody tr:hover { background: var(--c-gray-50); }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .text-right { text-align: right; }

    /* ============================================================
       ✅ FIX DÉFINITIF — Restaure <table> en desktop
       Activé dès 768px (au lieu de 1024px) pour couvrir tous les
       viewports entre 768px et 1023px.
    ============================================================ */
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

        /* Distribution des 9 colonnes = 100% */
        .index-container .data-table thead th:nth-child(1) { width: 20%; } /* Élève */
        .index-container .data-table thead th:nth-child(2) { width: 8%; }  /* Sexe */
        .index-container .data-table thead th:nth-child(3) { width: 8%; }  /* Âge */
        .index-container .data-table thead th:nth-child(4) { width: 12%; } /* Session */
        .index-container .data-table thead th:nth-child(5) { width: 12%; } /* Section */
        .index-container .data-table thead th:nth-child(6) { width: 12%; } /* Année */
        .index-container .data-table thead th:nth-child(7) { width: 10%; } /* Salle */
        .index-container .data-table thead th:nth-child(8) { width: 10%; } /* Option */
        .index-container .data-table thead th:nth-child(9) { width: 8%; }  /* Actions */
    }

    /* Empêche le débordement du contenu */
    .insc-cell { overflow: hidden; }
    .cell-primary {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ============================================================
       CELLULES SPÉCIFIQUES
    ============================================================ */
    .insc-cell {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
    }

    .avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .8rem;
        flex-shrink: 0;
        transition: transform 400ms var(--ease-out-expo);
    }
    .data-table tbody tr:hover .avatar { transform: scale(1.06) rotate(-3deg); }

    .cell-primary {
        font-weight: 600;
        color: var(--c-ink-900);
        min-width: 0;
    }

    .cell-text {
        color: var(--c-ink-600);
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ============================================================
       ACTIONS
    ============================================================ */
    .action-cell { white-space: nowrap; }

    .action-icons {
        display: inline-flex;
        justify-content: flex-end;
        gap: .4rem;
    }

    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        color: var(--c-ink-500);
        text-decoration: none;
        transition: all 300ms var(--ease-soft);
        background: transparent;
        border: 1.5px solid transparent;
        cursor: pointer;
        font-size: .95rem;
        flex-shrink: 0;
    }
    .action-icon:hover {
        background: var(--c-gray-100);
        color: var(--c-ink-900);
        border-color: var(--c-gray-200);
        transform: translateY(-1px);
    }

    /* ============================================================
       ÉTAT VIDE
    ============================================================ */
    .empty-cell { padding: 0 !important; }
    .empty-state {
        text-align: center;
        padding: clamp(2rem, 6vw, 4rem) 1rem;
        color: var(--c-ink-400);
    }
    .empty-state i {
        font-size: clamp(2rem, 5vw, 3rem);
        color: var(--c-gray-200);
        margin-bottom: 1rem;
        display: block;
    }
    .empty-state p {
        font-size: .95rem;
        margin: 0;
    }

    /* ============================================================
       PAGINATION
    ============================================================ */
    .pagination-wrapper {
        margin-top: clamp(1rem, 2.5vw, 1.75rem);
        display: flex;
        justify-content: flex-start;
        overflow-x: auto;
        padding: .25rem 0;
    }
    .pagination-wrapper :deep(> *) { max-width: 100%; }

    /* ============================================================
       ✅ ANIMATIONS BIDIRECTIONNELLES
    ============================================================ */
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

    .index-container [data-reveal="left"]  { transform: translateX(-40px); }
    .index-container [data-reveal="right"] { transform: translateX(40px); }
    .index-container [data-reveal="scale"] { transform: scale(.94); }
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
        .stat-card:hover, .btn-primary:hover, .btn-filter:hover, .export-btn:hover { transform: none; }
    }

    /* ============================================================
       RESPONSIVE — TABLETTE (≥ 640px)
    ============================================================ */
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

        .stats-grid-5 { grid-template-columns: repeat(2, 1fr); }

        .toolbar {
            flex-direction: row;
            align-items: center;
            flex-wrap: wrap;
        }
        .toolbar-group { width: auto; }
        .export-buttons {
            width: auto;
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }
        .export-btn {
            flex: 0 0 auto;
            min-width: 84px;
        }

        .filter-form {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .filter-actions {
            flex-direction: row;
            grid-column: 1 / -1;
            justify-content: flex-end;
        }
        .filter-actions > * { flex: 0 1 auto; }
    }

    /* ============================================================
       RESPONSIVE — TABLETTE LARGE (≥ 768px)
    ============================================================ */
    @media (min-width: 768px) {
        .stats-grid-5 { grid-template-columns: repeat(3, 1fr); }

        .filter-form {
            grid-template-columns: repeat(3, 1fr);
            align-items: end;
        }
        .filter-actions { grid-column: 1 / -1; }
    }

    /* ============================================================
       RESPONSIVE — DESKTOP (≥ 1024px)
    ============================================================ */
    @media (min-width: 1024px) {
        .stats-grid-5 { grid-template-columns: repeat(5, 1fr); }

        .toolbar { flex-wrap: nowrap; }
        .export-btn { min-width: 88px; }

        .filter-form {
            grid-template-columns: 1fr 1fr 1fr 1fr 1.4fr auto;
            align-items: end;
            gap: .9rem;
        }
        .filter-actions {
            grid-column: auto;
            flex-direction: row;
            justify-content: flex-end;
        }
    }

    /* ============================================================
       RESPONSIVE MOBILE (≤ 767px)
    ============================================================ */
    @media (max-width: 767px) {
        /* Export : 2 colonnes + Imprimer pleine largeur */
        .export-buttons {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .5rem;
            width: 100%;
        }
        .export-btn {
            width: 100%;
            min-width: 0;
            flex: initial;
            padding: .65rem .5rem;
        }
        .export-btn.print {
            grid-column: 1 / -1;
            min-height: 46px;
            font-size: .82rem;
        }

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
            max-height: 1400px;
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
            margin-bottom: 1rem;
            overflow: hidden;
            transition: box-shadow 300ms var(--ease-soft), transform 300ms var(--ease-soft);
        }

        .data-table tr:not(.empty-row):hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .data-table tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .85rem 1.15rem;
            border-bottom: 1px solid var(--c-gray-100);
            text-align: right;
            min-height: 52px;
        }

        .data-table tbody td:last-child { border-bottom: none; }

        .data-table tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--c-ink-400);
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            flex-shrink: 0;
            text-align: left;
        }

        /* Cellule Élève : avatar + nom à droite */
        .data-table td[data-label="Élève"] { justify-content: space-between; }
        .data-table td[data-label="Élève"]::before { order: 0; }
        .data-table td[data-label="Élève"] .insc-cell {
            order: 1;
            justify-content: flex-end;
            margin-left: auto;
        }

        /* Actions en pied de carte */
        .data-table td.action-cell {
            justify-content: flex-end;
            padding: .75rem 1rem;
            background: var(--c-gray-50);
            border-top: 1.5px solid var(--c-gray-100);
            min-height: auto;
        }
        .data-table td.action-cell::before { display: none; }

        .action-icons {
            width: 100%;
            justify-content: flex-end;
            gap: .5rem;
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
        }
        .data-table tr.empty-row td {
            display: block;
            padding: 0;
        }
        .data-table tr.empty-row td::before { display: none; }

        /* Cellules texte : alignement à droite */
        .cell-text, .cell-primary { text-align: right; }

        /* Pagination */
        .pagination-wrapper { justify-content: flex-start; }
    }

    /* ============================================================
       RESPONSIVE — PETIT MOBILE (≤ 480px)
    ============================================================ */
    @media (max-width: 480px) {
        .index-container { padding-inline: .85rem; }

        .stat-card { padding: .9rem 1rem; gap: .75rem; }
        .stat-label { font-size: .68rem; }
        .stat-value { font-size: 1.35rem; }

        .toolbar { padding: .85rem 1rem; }

        .btn-primary {
            padding: .75rem 1.15rem;
            font-size: .88rem;
            width: 100%;
        }

        .export-btn {
            font-size: .72rem;
            padding: .6rem .5rem;
        }
        .export-btn.print {
            font-size: .82rem;
            padding: .7rem .5rem;
        }

        .data-table tbody td {
            padding: .75rem 1rem;
            font-size: .88rem;
            min-height: 48px;
        }
        .data-table tbody td::before { font-size: .66rem; }

        .action-icon { width: 40px; height: 40px; font-size: .9rem; }

        .avatar { width: 34px; height: 34px; font-size: .75rem; }
    }

    /* ============================================================
       RESPONSIVE — TRÈS PETIT MOBILE (≤ 360px)
    ============================================================ */
    @media (max-width: 360px) {
        .export-buttons { grid-template-columns: 1fr; }
        .export-btn.print { grid-column: auto; }

        .data-table tbody td {
            font-size: .84rem;
            padding: .65rem .85rem;
            gap: .5rem;
        }
        .data-table tbody td::before { font-size: .62rem; }

        .stat-value { font-size: 1.2rem; }
    }

    /* ============================================================
       TRÈS GRAND ÉCRAN (≥ 1440px)
    ============================================================ */
    @media (min-width: 1440px) {
        .index-container { max-width: 1400px; }
    }

    /* ============================================================
       IMPRESSION
    ============================================================ */
    @media print {
        .index-container { padding: 0; max-width: 100%; }
        .no-print { display: none !important; }
        body { background: white !important; }

        .table-card {
            box-shadow: none;
            border-radius: 0;
            border: 1px solid #ddd;
            break-inside: avoid;
        }
        .data-table { font-size: .8rem; }
        .data-table thead th {
            background: #f1f5f9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .data-table tbody tr:nth-child(even) { background: #f8fafc; }
        .data-table tbody td { padding: .5rem; }
        .index-container [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
    }
</style>

<script>
    /* ============================================================
       ALPINE.JS — Filtres dépendants (session > section > option > salle)
    ============================================================ */
    function infoElevesFiltre() {
        return {
            session: '{{ request('session') }}',
            section: '{{ request('section') }}',
            option: '{{ request('option') }}',
            salle: '{{ request('salle') }}',

            init() {
                this.filtrerSections();
                this.filtrerOptionsEtSalles();
                this.filtrerSalles();
                this.initFilterToggle();
            },

            submitForm() {
                setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 0);
            },

            filtrerSections() {
                const selectSection = document.getElementById('section_select');
                if (!selectSection) return;

                const options = selectSection.querySelectorAll('option');
                options.forEach(opt => {
                    if (opt.value === '') return;
                    const sessionId = opt.getAttribute('data-session-id');
                    if (this.session === '' || sessionId == this.session) {
                        opt.style.display = '';
                    } else {
                        opt.style.display = 'none';
                    }
                });

                const selectedOption = selectSection.querySelector(`option[value="${this.section}"]`);
                if (selectedOption && selectedOption.style.display === 'none') {
                    selectSection.value = '';
                    this.section = '';
                    this.filtrerOptionsEtSalles();
                }
            },

            filtrerOptionsEtSalles() {
                const selectOption = document.getElementById('option_select');
                if (selectOption) {
                    const options = selectOption.querySelectorAll('option');
                    options.forEach(opt => {
                        if (opt.value === '') return;
                        let sectionIds = [];
                        try {
                            sectionIds = JSON.parse(opt.getAttribute('data-section-ids') || '[]');
                        } catch (e) {
                            sectionIds = [];
                        }
                        if (this.section === '' || sectionIds.includes(parseInt(this.section))) {
                            opt.style.display = '';
                        } else {
                            opt.style.display = 'none';
                        }
                    });

                    const selectedOpt = selectOption.querySelector(`option[value="${this.option}"]`);
                    if (selectedOpt && selectedOpt.style.display === 'none') {
                        selectOption.value = '';
                        this.option = '';
                    }
                }

                this.filtrerSalles();
            },

            filtrerSalles() {
                const selectSalle = document.getElementById('salle_select');
                if (!selectSalle) return;

                const options = selectSalle.querySelectorAll('option');
                options.forEach(opt => {
                    if (opt.value === '') return;
                    const optionId = opt.getAttribute('data-option-id');
                    const sectionId = opt.getAttribute('data-section-id');

                    let visible = true;
                    if (this.option !== '' && optionId != this.option) {
                        visible = false;
                    }
                    if (this.section !== '' && sectionId != this.section) {
                        visible = false;
                    }

                    opt.style.display = visible ? '' : 'none';
                });

                const selectedSalle = selectSalle.querySelector(`option[value="${this.salle}"]`);
                if (selectedSalle && selectedSalle.style.display === 'none') {
                    selectSalle.value = '';
                    this.salle = '';
                }
            },

            submitSalle() {
                this.submitForm();
            },

            /* Toggle filtres sur mobile */
            initFilterToggle() {
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
        }
    }

    /* ============================================================
       ANIMATIONS BIDIRECTIONNELLES
    ============================================================ */
    (function () {
        'use strict';

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