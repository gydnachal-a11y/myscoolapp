@extends('layouts.admin')

@section('page_title', 'Devises')
@section('page_subtitle', 'Gestion des devises et monnaies')

@section('content')
<div class="page">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header" data-reveal="auto">
        <div class="page-header-main">
            <h1 class="page-title">
                <i class="fa-solid fa-coins title-icon" aria-hidden="true"></i>
                <span>Devises</span>
                <span class="count-badge">{{ $devises->count() }}</span>
            </h1>
            <p class="page-subtitle">Gérez les devises et monnaies de l'application</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.devises.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Nouvelle devise</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FLASH --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    @if(session('success'))
        <div class="flash flash-success" role="status" data-reveal="auto">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error" role="alert" data-reveal="auto">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- LISTE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <section class="content-card" data-reveal="auto" data-delay="1">
        @if($devises->count())

            {{-- ──────── DESKTOP / TABLETTE : TABLEAU (≥ 768px) ──────── --}}
            <div class="view-table table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Nom</th>
                            <th scope="col">Symbole</th>
                            <th scope="col" class="text-center">Par défaut</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($devises as $d)
                            <tr>
                                <td data-label="Code">
                                    <span class="devise-code">{{ $d->code }}</span>
                                </td>
                                <td data-label="Nom">
                                    <span class="cell-primary">{{ $d->nom }}</span>
                                </td>
                                <td data-label="Symbole">
                                    @if($d->symbole)
                                        <span class="devise-symbole">{{ $d->symbole }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Par défaut" class="text-center">
                                    @if($d->est_defaut)
                                        <span class="pill pill-success">
                                            <i class="fa-solid fa-star" aria-hidden="true"></i>
                                            <span>Par défaut</span>
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Actions" class="text-right">
                                    <div class="action-bar">
                                        <a href="{{ route('admin.devises.show', $d) }}"
                                           class="action-btn"
                                           title="Voir"
                                           aria-label="Voir {{ $d->nom }}">
                                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="{{ route('admin.devises.edit', $d) }}"
                                           class="action-btn"
                                           title="Modifier"
                                           aria-label="Modifier {{ $d->nom }}">
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                        </a>
                                        <form action="{{ route('admin.devises.destroy', $d) }}"
                                              method="POST" class="inline-form"
                                              onsubmit="return confirm('Supprimer la devise {{ $d->code }} ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="action-btn action-danger"
                                                    title="Supprimer"
                                                    aria-label="Supprimer {{ $d->nom }}">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ──────── MOBILE : CARTES (< 768px) ──────── --}}
            <div class="view-cards">
                @foreach($devises as $d)
                    <article class="devise-card">
                        <div class="devise-card-header">
                            <div class="devise-card-logo">
                                {{ strtoupper(substr($d->code, 0, 3)) }}
                            </div>
                            <div class="devise-card-info">
                                <h3 class="devise-card-name">{{ $d->nom }}</h3>
                                <div class="devise-card-meta">
                                    <span class="devise-code devise-code-sm">{{ $d->code }}</span>
                                    @if($d->symbole)
                                        <span class="devise-symbole-sm">{{ $d->symbole }}</span>
                                    @endif
                                </div>
                            </div>
                            @if($d->est_defaut)
                                <span class="pill pill-success pill-sm">
                                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                                </span>
                            @endif
                        </div>

                        <div class="devise-card-actions">
                            <a href="{{ route('admin.devises.show', $d) }}"
                               class="card-action-btn card-action-view">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                <span>Voir</span>
                            </a>
                            <a href="{{ route('admin.devises.edit', $d) }}"
                               class="card-action-btn card-action-edit">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                <span>Modifier</span>
                            </a>
                            <form action="{{ route('admin.devises.destroy', $d) }}"
                                  method="POST"
                                  class="inline-form card-action-form"
                                  onsubmit="return confirm('Supprimer la devise {{ $d->code }} ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="card-action-btn card-action-delete">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    <span>Suppr.</span>
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

        @else
            <div class="empty-state">
                <div class="empty-icon-wrapper">
                    <i class="fa-solid fa-coins" aria-hidden="true"></i>
                </div>
                <h3 class="empty-title">Aucune devise enregistrée</h3>
                <p class="empty-text">Commencez par créer votre première devise.</p>
                <a href="{{ route('admin.devises.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    <span>Nouvelle devise</span>
                </a>
            </div>
        @endif
    </section>
</div>

<style>
    /* ════════════════════════════════════════════════════════
       TOKENS
       ════════════════════════════════════════════════════════ */
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
        --radius-full: 9999px;

        --shadow-sm: 0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04);
        --shadow-md: 0 8px 20px rgba(15,23,42,.06), 0 2px 4px rgba(15,23,42,.04);
        --shadow-lg: 0 16px 32px rgba(15,23,42,.08), 0 4px 8px rgba(15,23,42,.04);

        --ease-out-expo: cubic-bezier(.16, 1, .3, 1);
        --ease-soft: cubic-bezier(.4, 0, .2, 1);

        max-width: 1200px;
        margin: 0 auto;
        padding: clamp(1rem, 2.5vw, 2rem) clamp(.75rem, 2vw, 1.25rem);
        padding-left: max(clamp(.75rem, 2vw, 1.25rem), env(safe-area-inset-left));
        padding-right: max(clamp(.75rem, 2vw, 1.25rem), env(safe-area-inset-right));
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

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
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
    .page-header-main { min-width: 0; width: 100%; }
    @media (min-width: 768px) { .page-header-main { width: auto; } }

    .page-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem 0.6rem;
        font-size: clamp(1.4rem, 3vw, 1.85rem);
        font-weight: 800;
        color: var(--c-ink-900);
        margin: 0 0 0.25rem;
        line-height: 1.15;
    }
    .title-icon { color: var(--c-primary); flex-shrink: 0; }
    .page-subtitle {
        color: var(--c-ink-500);
        font-size: clamp(.85rem, 1.4vw, .95rem);
        margin: 0;
        line-height: 1.5;
    }

    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 0.65rem;
        min-width: 30px;
        height: 26px;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: var(--radius-full);
        font-size: 0.78rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .header-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        width: 100%;
    }
    @media (min-width: 768px) { .header-actions { width: auto; } }
    .header-actions .btn { flex: 1 1 auto; justify-content: center; }
    @media (min-width: 768px) { .header-actions .btn { flex: 0 0 auto; } }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.8rem 1.4rem;
        min-height: 46px;
        border-radius: var(--radius-md);
        font-family: inherit;
        font-weight: 600;
        font-size: 0.92rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 400ms var(--ease-out-expo);
        white-space: nowrap;
        text-align: center;
        line-height: 1.2;
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
    .btn-primary:active { transform: translateY(0) scale(0.98); }

    /* ════════════════════════════════════════════════════════
       FLASH
       ════════════════════════════════════════════════════════ */
    .flash {
        display: flex;
        gap: 0.65rem;
        align-items: flex-start;
        padding: 0.9rem 1.1rem;
        border-radius: var(--radius-md);
        margin-bottom: 1.25rem;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    .flash i { flex-shrink: 0; margin-top: 2px; }
    .flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
    .flash-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

    /* ════════════════════════════════════════════════════════
       CONTENT CARD
       ════════════════════════════════════════════════════════ */
    .content-card {
        background: var(--c-white);
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-gray-100);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        width: 100%;
    }

    /* ════════════════════════════════════════════════════════
       SWITCH TABLE / CARDS
       ════════════════════════════════════════════════════════ */
    .view-table { display: none !important; }
    .view-cards { display: block !important; }

    @media (min-width: 768px) {
        .view-table { display: block !important; }
        .view-cards { display: none !important; }
    }

    /* ════════════════════════════════════════════════════════
       TABLEAU
       ════════════════════════════════════════════════════════ */
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

    .text-right  { text-align: right !important; }
    .text-center { text-align: center !important; }
    .text-muted  { color: #cbd5e1; }
    .cell-primary { font-weight: 600; color: var(--c-ink-900); }

    /* ✅ FIX DÉFINITIF — Restaure <table> en desktop */
    @media (min-width: 768px) {
        .page .data-table {
            display: table !important;
            width: 100% !important;
            max-width: 100%;
            table-layout: fixed !important;
            border-collapse: collapse !important;
        }
        .page .data-table thead { display: table-header-group !important; }
        .page .data-table tbody { display: table-row-group !important; }
        .page .data-table tr    { display: table-row !important; }
        .page .data-table th,
        .page .data-table td    { display: table-cell !important; }

        /* Distribution des 5 colonnes = 100% */
        .page .data-table thead th:nth-child(1) { width: 12%; } /* Code */
        .page .data-table thead th:nth-child(2) { width: 30%; } /* Nom */
        .page .data-table thead th:nth-child(3) { width: 12%; } /* Symbole */
        .page .data-table thead th:nth-child(4) { width: 20%; } /* Par défaut */
        .page .data-table thead th:nth-child(5) { width: 26%; } /* Actions */
    }

    /* Empêche le débordement */
    .cell-primary {
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ════════════════════════════════════════════════════════
       DEVISE CODE / SYMBOLE
       ════════════════════════════════════════════════════════ */
    .devise-code {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.7rem;
        background: #eef2ff;
        color: #4338ca;
        font-weight: 800;
        letter-spacing: 0.5px;
        border-radius: 8px;
        font-size: 0.9rem;
        white-space: nowrap;
    }
    .devise-symbole {
        font-size: 1rem;
        font-weight: 700;
        color: var(--c-ink-700);
    }

    /* ════════════════════════════════════════════════════════
       PILL
       ════════════════════════════════════════════════════════ */
    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.7rem;
        border-radius: var(--radius-full);
        font-size: 0.73rem;
        font-weight: 700;
        white-space: nowrap;
        line-height: 1.4;
    }
    .pill-success { background: #ecfdf5; color: #047857; }

    /* ════════════════════════════════════════════════════════
       ACTIONS
       ════════════════════════════════════════════════════════ */
    .action-bar {
        display: inline-flex;
        gap: 0.4rem;
        justify-content: flex-end;
    }
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: transparent;
        border: 1.5px solid transparent;
        color: var(--c-ink-500);
        cursor: pointer;
        transition: all 300ms var(--ease-soft);
        font-size: 0.95rem;
        text-decoration: none;
        flex-shrink: 0;
    }
    .action-btn:hover {
        background: var(--c-gray-100);
        color: var(--c-ink-900);
        border-color: var(--c-gray-200);
        transform: translateY(-1px);
    }
    .action-btn.action-danger:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecaca;
    }
    .inline-form { display: inline-block; }

    /* ════════════════════════════════════════════════════════
       CARTES MOBILE
       ════════════════════════════════════════════════════════ */
    .devise-card {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1.25rem;
        border-bottom: 1px solid var(--c-gray-100);
        transition: background 250ms ease;
    }
    .devise-card:last-child { border-bottom: none; }
    .devise-card:active { background: var(--c-gray-50); }

    .devise-card-header {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }

    .devise-card-logo {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
        transition: transform 400ms var(--ease-out-expo);
    }
    .devise-card:hover .devise-card-logo { transform: scale(1.06) rotate(-3deg); }

    .devise-card-info {
        flex: 1;
        min-width: 0;
    }
    .devise-card-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--c-ink-900);
        margin: 0 0 0.3rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .devise-card-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .devise-code-sm {
        font-size: 0.7rem;
        padding: 0.15rem 0.5rem;
    }
    .devise-symbole-sm {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--c-ink-500);
    }

    .pill-sm {
        padding: 0.3rem 0.5rem;
        font-size: 0.65rem;
        flex-shrink: 0;
    }

    .devise-card-actions {
        display: flex;
        gap: 0.4rem;
        flex-wrap: nowrap;
    }
    .card-action-form {
        flex: 1;
        display: flex;
        margin: 0;
        padding: 0;
    }
    .card-action-btn {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.65rem 0.5rem;
        border-radius: 10px;
        border: 1.5px solid var(--c-gray-200);
        background: var(--c-gray-50);
        color: var(--c-ink-600);
        font-weight: 600;
        font-size: 0.78rem;
        text-decoration: none;
        cursor: pointer;
        transition: all 250ms var(--ease-soft);
        white-space: nowrap;
        min-height: 40px;
        font-family: inherit;
        width: 100%;
    }
    .card-action-btn:active { transform: scale(0.97); }
    .card-action-btn i { font-size: 0.8rem; }

    .card-action-view:hover  { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; transform: translateY(-1px); }
    .card-action-edit:hover  { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; transform: translateY(-1px); }
    .card-action-delete      { color: #dc2626; }
    .card-action-delete:hover { background: #fef2f2; color: #b91c1c; border-color: #fecaca; transform: translateY(-1px); }

    /* ════════════════════════════════════════════════════════
       EMPTY STATE
       ════════════════════════════════════════════════════════ */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: clamp(2.5rem, 6vw, 4rem) 1.5rem;
        text-align: center;
        gap: 0.75rem;
    }

    .empty-icon-wrapper {
        width: clamp(64px, 12vw, 80px);
        height: clamp(64px, 12vw, 80px);
        border-radius: 50%;
        background: var(--c-gray-50);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: clamp(1.6rem, 4vw, 2rem);
        color: #cbd5e1;
        margin-bottom: 0.5rem;
    }
    .empty-title { font-size: clamp(1rem, 2.2vw, 1.1rem); font-weight: 700; color: var(--c-ink-700); margin: 0; }
    .empty-text  { font-size: clamp(.85rem, 1.6vw, .9rem); color: var(--c-ink-400); margin: 0 0 0.5rem; max-width: 340px; }

    /* ════════════════════════════════════════════════════════
       ANIMATIONS BIDIRECTIONNELLES
       ════════════════════════════════════════════════════════ */
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

    @media (prefers-reduced-motion: reduce) {
        .page [data-reveal] {
            opacity: 1 !important;
            transform: none !important;
            filter: none !important;
        }
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
        .btn-primary:hover, .action-btn:hover, .card-action-btn:hover,
        .devise-card:hover .devise-card-logo { transform: none; }
    }

    /* ════════════════════════════════════════════════════════
       TRÈS PETITS ÉCRANS (< 400px)
       ════════════════════════════════════════════════════════ */
    @media (max-width: 400px) {
        .page { padding: 1.25rem 0.75rem; }
        .page-title { font-size: 1.35rem; }
        .page-subtitle { font-size: 0.8rem; }
        .count-badge { height: 22px; font-size: 0.7rem; padding: 0 0.5rem; }

        .btn { padding: 0.7rem 1rem; font-size: 0.85rem; }

        .devise-card { padding: 1rem; gap: 0.85rem; }
        .devise-card-logo { width: 42px; height: 42px; font-size: 0.75rem; }
        .devise-card-name { font-size: 0.88rem; }

        .card-action-btn {
            font-size: 0.72rem;
            padding: 0.55rem 0.35rem;
            min-height: 38px;
            gap: 0.25rem;
        }
        .card-action-btn i { font-size: 0.72rem; }
    }

    /* ════════════════════════════════════════════════════════
       IMPRESSION
       ════════════════════════════════════════════════════════ */
    @media print {
        .header-actions,
        .action-bar,
        .devise-card-actions { display: none !important; }

        .page { max-width: 100%; padding: 0; }
        .content-card { border: none; box-shadow: none; }
        .view-cards { display: none !important; }
        .view-table { display: block !important; }

        .page [data-reveal] {
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
</script>
@endsection