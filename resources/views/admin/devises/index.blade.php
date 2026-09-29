@extends('layouts.admin')

@section('page_title', 'Devises')
@section('page_subtitle', 'Gestion des devises et monnaies')

@section('content')
<div class="page">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
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
        <div class="flash flash-success">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- LISTE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <section class="content-card">
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
                                           title="Voir">
                                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="{{ route('admin.devises.edit', $d) }}"
                                           class="action-btn"
                                           title="Modifier">
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                        </a>
                                        <form action="{{ route('admin.devises.destroy', $d) }}"
                                              method="POST" class="inline-form"
                                              onsubmit="return confirm('Supprimer la devise {{ $d->code }} ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="action-btn action-danger"
                                                    title="Supprimer">
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
       LAYOUT DE BASE
       ════════════════════════════════════════════════════════ */
    .page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        padding-left: max(1rem, env(safe-area-inset-left));
        padding-right: max(1rem, env(safe-area-inset-right));
    }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .page-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 1.25rem;
        margin-bottom: 2rem;
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
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        margin: 0 0 0.25rem;
        line-height: 1.15;
    }
    @media (min-width: 640px) { .page-title { font-size: 1.75rem; } }
    .title-icon { color: #6366f1; flex-shrink: 0; }
    .page-subtitle {
        color: #64748b;
        font-size: 0.85rem;
        margin: 0;
        line-height: 1.4;
    }
    @media (min-width: 640px) { .page-subtitle { font-size: 0.9rem; } }

    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 0.6rem;
        min-width: 30px;
        height: 26px;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 9999px;
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
        padding: 0.7rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
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
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
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
        border-radius: 12px;
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
        background: #fff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        overflow: hidden;
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
       TABLEAU — Desktop / Tablette (≥ 768px)
       ════════════════════════════════════════════════════════ */
    .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        color: #475569;
    }
    .data-table thead th {
        text-align: left;
        padding: 0.9rem 1.25rem;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .data-table tbody td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .data-table tbody tr { transition: background 0.15s; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .text-right  { text-align: right !important; }
    .text-center { text-align: center !important; }
    .text-muted  { color: #cbd5e1; }
    .cell-primary { font-weight: 600; color: #0f172a; }

    .devise-code {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.65rem;
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
        color: #334155;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .pill-success { background: #ecfdf5; color: #047857; }

    .action-bar {
        display: inline-flex;
        gap: 0.3rem;
        justify-content: flex-end;
    }
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: transparent;
        border: none;
        color: #64748b;
        cursor: pointer;
        transition: all 0.18s ease;
        font-size: 0.9rem;
        text-decoration: none;
        flex-shrink: 0;
    }
    .action-btn:hover { background: #f1f5f9; color: #0f172a; transform: translateY(-1px); }
    .action-btn.action-danger:hover { background: #fef2f2; color: #dc2626; }
    .inline-form { display: inline; }

    /* ════════════════════════════════════════════════════════
       CARTES — Mobile (< 768px)
       ════════════════════════════════════════════════════════ */
    .devise-card {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
    }
    .devise-card:last-child { border-bottom: none; }
    .devise-card:active { background: #f8fafc; }

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
    }

    .devise-card-info {
        flex: 1;
        min-width: 0;
    }
    .devise-card-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
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
        color: #64748b;
    }

    .pill-sm {
        padding: 0.3rem 0.5rem;
        font-size: 0.65rem;
        flex-shrink: 0;
    }

    /* Actions des cartes */
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
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 0.78rem;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
        min-height: 40px;
        font-family: inherit;
        width: 100%;
    }
    .card-action-btn:active { transform: scale(0.97); }
    .card-action-btn i { font-size: 0.8rem; }

    .card-action-view:hover  { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .card-action-edit:hover  { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
    .card-action-delete      { color: #dc2626; }
    .card-action-delete:hover { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

    /* ════════════════════════════════════════════════════════
       EMPTY STATE
       ════════════════════════════════════════════════════════ */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1.5rem;
        text-align: center;
        gap: 0.75rem;
    }
    @media (min-width: 640px) { .empty-state { padding: 4rem 1.5rem; } }

    .empty-icon-wrapper {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: #cbd5e1;
        margin-bottom: 0.5rem;
    }
    @media (min-width: 640px) {
        .empty-icon-wrapper { width: 80px; height: 80px; font-size: 2rem; }
    }
    .empty-title { font-size: 1rem; font-weight: 700; color: #334155; margin: 0; }
    @media (min-width: 640px) { .empty-title { font-size: 1.1rem; } }
    .empty-text  { font-size: 0.85rem; color: #94a3b8; margin: 0 0 0.5rem; max-width: 340px; }
    @media (min-width: 640px) { .empty-text { font-size: 0.9rem; } }

    /* ════════════════════════════════════════════════════════
       TRÈS PETITS ÉCRANS (< 400px)
       ════════════════════════════════════════════════════════ */
    @media (max-width: 400px) {
        .page { padding: 1.25rem 0.75rem; }
        .page-title { font-size: 1.35rem; }
        .page-subtitle { font-size: 0.8rem; }
        .count-badge { height: 22px; font-size: 0.7rem; padding: 0 0.5rem; }

        .btn { padding: 0.6rem 1rem; font-size: 0.85rem; }

        .devise-card { padding: 1rem; gap: 0.85rem; }
        .devise-card-logo { width: 42px; height: 42px; font-size: 0.75rem; }
        .devise-card-name { font-size: 0.88rem; }

        .card-action-btn { font-size: 0.72rem; padding: 0.55rem 0.35rem; min-height: 38px; gap: 0.25rem; }
        .card-action-btn i { font-size: 0.72rem; }
    }

    /* ════════════════════════════════════════════════════════
       MODE SOMBRE (optionnel — si support)
       ════════════════════════════════════════════════════════ */
    @media (prefers-color-scheme: dark) {
        /* Rien ici — l'app est en mode clair */
    }

    /* ════════════════════════════════════════════════════════
       ACCESSIBILITÉ : réduction des animations
       ════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
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
    }
</style>
@endsection