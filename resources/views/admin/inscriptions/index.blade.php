@extends('layouts.admin')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    {{-- ============ EN-TÊTE ============ --}}
    <div class="index-header" data-reveal="auto">
        <div class="index-header-text">
            <h1 class="index-title"><strong>Inscriptions</strong></h1>
            <p class="index-subtitle">Gérez les inscriptions des élèves</p>
        </div>
        <div class="header-actions">
            @if(auth()->user() && auth()->user()->role === 'admin')
                <form action="{{ route('admin.inscriptions.truncate') }}" method="POST" class="inline-form" onsubmit="return confirm('⚠️ Vider toutes les inscriptions ? Cette action est irréversible !')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger" title="Vider la table des inscriptions (test)">
                        <i class="fa-solid fa-trash-can"></i> <span>Vider la table</span>
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.inscriptions.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> <span>Nouvelle inscription</span>
            </a>
            <a href="{{ route('admin.info-eleves.index') }}" class="btn-secondary">
                <i class="fa-solid fa-search"></i> <span>Recherche avancée</span>
            </a>
        </div>
    </div>

    {{-- ============ BARRE D'OUTILS ============ --}}
    <div class="toolbar" data-reveal="auto" data-delay="1">
        <div class="toolbar-group">
            <span class="toolbar-label">Tri :</span>
            <a href="{{ route('admin.inscriptions.index', array_merge(request()->query(), ['tri' => 'alpha'])) }}"
               class="toolbar-btn {{ request('tri') == 'alpha' ? 'active' : '' }}">
                <i class="fa-solid fa-arrow-down-a-z"></i> A-Z
            </a>
            <a href="{{ route('admin.inscriptions.index', array_merge(request()->query(), ['tri' => null])) }}"
               class="toolbar-btn {{ request('tri') != 'alpha' ? 'active' : '' }}">
                <i class="fa-solid fa-calendar"></i> Par date
            </a>
        </div>

        <div class="toolbar-group toolbar-group-right export-buttons">
            <a href="{{ route('admin.inscriptions.export.pdf', request()->query()) }}" class="export-btn pdf" title="Télécharger PDF">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route('admin.inscriptions.export.csv', request()->query()) }}" class="export-btn csv" title="Télécharger CSV">
                <i class="fa-solid fa-file-excel"></i> CSV
            </a>
            <a href="{{ route('admin.inscriptions.export.xml', request()->query()) }}" class="export-btn xml" title="Télécharger XML">
                <i class="fa-solid fa-file-code"></i> XML
            </a>
            <a href="{{ route('admin.inscriptions.export.doc', request()->query()) }}" class="export-btn doc" title="Télécharger DOC">
                <i class="fa-solid fa-file-word"></i> DOC
            </a>
            <a href="{{ route('admin.inscriptions.imprimer', request()->query()) }}" target="_blank" class="export-btn print" title="Imprimer">
                <i class="fa-solid fa-print"></i> Imprimer
            </a>
        </div>
    </div>

    {{-- ============ LIEN PARAMÈTRES (ADMIN) ============ --}}
    @if(auth()->user() && auth()->user()->role === 'admin')
        <div class="admin-link" data-reveal="auto" data-delay="2">
            <a href="{{ route('admin.settings.edit') }}">
                <i class="fa-solid fa-gear"></i> Personnaliser l'en-tête des documents (logo, nom de l'école…)
            </a>
        </div>
    @endif

    {{-- ============ TABLEAU / CARTES ============ --}}
    <div class="table-card" data-reveal="auto" data-delay="3">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Année</th>
                        <th>Salle</th>
                        <th>Option</th>
                        <th>Frais inscription</th>
                        <th>Frais annuel</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inscriptions as $ins)
                        @php
                            $taux = $tauxChange ?? 2800;
                            $fraisInscriptionFc = $ins->frais_inscription_final * $taux;
                            $fraisAnnuelFc = $ins->frais_annuel_final * $taux;
                        @endphp
                        <tr>
                            <td data-label="Élève">
                                <div class="insc-cell">
                                    <div class="avatar">
                                        {{ strtoupper(substr(optional($ins->eleve)->nom ?? '?', 0, 1) . substr(optional($ins->eleve)->prenom ?? '?', 0, 1)) }}
                                    </div>
                                    <span class="cell-primary">{{ optional($ins->eleve)->nom }} {{ optional($ins->eleve)->prenom }}</span>
                                </div>
                            </td>
                            <td data-label="Année">
                                <span class="cell-text">{{ optional($ins->anneeScolaire)->libelle ?? 'Année supprimée' }}</span>
                            </td>
                            <td data-label="Salle">
                                <span class="cell-text">{{ optional($ins->salleDeClasse)->nom ?? 'Salle supprimée' }}</span>
                            </td>
                            <td data-label="Option">
                                <span class="cell-text">{{ optional($ins->salleDeClasse->option)->nom ?? '—' }}</span>
                            </td>
                            <td data-label="Frais inscription">
                                <div class="cell-money">
                                    <strong>{{ number_format($ins->frais_inscription_final, 0) }} $</strong>
                                    <span class="text-muted small">{{ number_format($fraisInscriptionFc, 0, ',', ' ') }} FC</span>
                                </div>
                            </td>
                            <td data-label="Frais annuel">
                                <div class="cell-money">
                                    <strong>{{ number_format($ins->frais_annuel_final, 0) }} $</strong>
                                    <span class="text-muted small">{{ number_format($fraisAnnuelFc, 0, ',', ' ') }} FC</span>
                                </div>
                            </td>
                            <td data-label="Actions" class="text-right action-cell">
                                <div class="action-icons">
                                    <a href="{{ route('admin.inscriptions.edit', $ins) }}" class="action-icon" title="Modifier" aria-label="Modifier l'inscription">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('admin.inscriptions.destroy', $ins) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cette inscription ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-icon danger" title="Supprimer" aria-label="Supprimer l'inscription">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="7" class="empty-cell">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    <p>Aucune inscription enregistrée.</p>
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
        <div class="pagination-wrapper" data-reveal="auto">
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

    .inline-form { display: inline-block; }

    /* ============================================================
       EN-TÊTE
    ============================================================ */
    .index-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1.25rem;
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
       BOUTONS GÉNÉRIQUES
    ============================================================ */
    .btn-primary,
    .btn-danger,
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

    .btn-danger {
        background: #ef4444;
        color: #fff;
    }
    .btn-danger:hover {
        background: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(239,68,68,.35);
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
        gap: .5rem;
    }

    .toolbar-group-right {
        margin-left: 0;
    }

    .toolbar-label {
        font-size: .78rem;
        font-weight: 700;
        color: var(--c-ink-500);
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-right: .25rem;
    }

    .toolbar-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: .55rem 1rem;
        min-height: 40px;
        border-radius: 10px;
        font-size: .85rem;
        font-weight: 600;
        color: var(--c-ink-500);
        text-decoration: none;
        transition: all 300ms var(--ease-soft);
        background: var(--c-gray-100);
        white-space: nowrap;
    }
    .toolbar-btn:hover {
        background: var(--c-gray-200);
        color: var(--c-ink-900);
        transform: translateY(-1px);
    }
    .toolbar-btn.active {
        background: #e0e7ff;
        color: #4338ca;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(99,102,241,.15);
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
       LIEN PARAMÈTRES ADMIN
    ============================================================ */
    .admin-link {
        text-align: right;
        margin-bottom: 1rem;
    }
    .admin-link a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .85rem;
        color: var(--c-primary);
        text-decoration: none;
        transition: color .2s;
        font-weight: 500;
    }
    .admin-link a:hover { color: var(--c-primary-dark); text-decoration: underline; }

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
       Le bloc s'active dès 768px (pas 1024px) pour couvrir
       tous les viewports entre 768px et 1023px.
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

        /* Distribution explicite = 100% */
        .index-container .data-table thead th:nth-child(1) { width: 22%; } /* Élève */
        .index-container .data-table thead th:nth-child(2) { width: 12%; } /* Année */
        .index-container .data-table thead th:nth-child(3) { width: 12%; } /* Salle */
        .index-container .data-table thead th:nth-child(4) { width: 12%; } /* Option */
        .index-container .data-table thead th:nth-child(5) { width: 14%; } /* Frais inscr. */
        .index-container .data-table thead th:nth-child(6) { width: 14%; } /* Frais annuel */
        .index-container .data-table thead th:nth-child(7) { width: 14%; } /* Actions */
    }

    /* ✅ Empêche le débordement */
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

    .cell-money {
        display: flex;
        flex-direction: column;
        gap: .15rem;
    }
    .cell-money strong {
        color: var(--c-ink-900);
        font-weight: 700;
        font-size: .92rem;
    }

    .text-muted.small {
        font-size: .75rem;
        color: var(--c-ink-400);
        display: block;
        font-weight: 500;
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
    .action-icon.danger:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecaca;
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
        justify-content: center;
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
        .btn-primary:hover, .btn-danger:hover, .btn-secondary:hover,
        .export-btn:hover, .action-icon:hover,
        .data-table tbody tr:hover .avatar { transform: none; }
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

        .toolbar {
            flex-direction: row;
            align-items: center;
            flex-wrap: wrap;
        }
        .toolbar-group-right {
            margin-left: auto;
            width: auto;
        }
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
    }

    /* ============================================================
       RESPONSIVE — DESKTOP (≥ 1024px)
    ============================================================ */
    @media (min-width: 1024px) {
        .toolbar { flex-wrap: nowrap; }
        .export-btn { min-width: 88px; }
    }

    /* ============================================================
       RESPONSIVE MOBILE (≤ 767px) — Bouton Imprimer pleine largeur
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

        .data-table td[data-label="Élève"] { justify-content: space-between; }
        .data-table td[data-label="Élève"]::before { order: 0; }
        .data-table td[data-label="Élève"] .insc-cell {
            order: 1;
            justify-content: flex-end;
            margin-left: auto;
        }

        .cell-money {
            align-items: flex-end;
            text-align: right;
        }

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

        .cell-text, .cell-primary { text-align: right; }
    }

    /* ============================================================
       RESPONSIVE — PETIT MOBILE (≤ 480px)
    ============================================================ */
    @media (max-width: 480px) {
        .index-container { padding-inline: .85rem; }

        .toolbar { padding: .85rem 1rem; }

        .btn-primary, .btn-danger, .btn-secondary {
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
        .btn-primary, .btn-danger, .btn-secondary,
        .toolbar, .admin-link, .action-cell, .pagination-wrapper,
        .header-actions { display: none !important; }
        .table-card {
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