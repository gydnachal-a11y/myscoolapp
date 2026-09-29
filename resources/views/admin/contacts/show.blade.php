@extends('layouts.admin')

@section('page_title', 'Fiche abonné')
@section('page_subtitle', $contact->nom)

@section('content')
<div class="contact-show-page">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-address-card title-icon" aria-hidden="true"></i>
                <span>Fiche abonné</span>
            </h1>
            <p class="page-subtitle">Détails et historique du compte</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.contacts.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour</span>
            </a>
            <a href="{{ route('admin.contacts.edit', $contact) }}" class="btn btn-primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                <span>Modifier</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HERO --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="hero-card">
        <div class="hero-avatar" aria-hidden="true">
            {{ strtoupper(mb_substr($contact->nom ?? '?', 0, 1)) }}
        </div>
        <div class="hero-info">
            <h2 class="hero-name">{{ $contact->nom }}</h2>
            <p class="hero-email">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
            </p>
            @if($contact->est_responsable)
                <span class="hero-badge">
                    <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                    Responsable légal
                </span>
            @else
                <span class="hero-badge hero-badge-slate">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                    Contact simple
                </span>
            @endif
        </div>
        <div class="hero-actions">
            <a href="mailto:{{ $contact->email }}" class="hero-action">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                <span>Email</span>
            </a>
            @if($contact->telephone)
                <a href="tel:{{ $contact->telephone }}" class="hero-action">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    <span>Appeler</span>
                </a>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- GRILLE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="show-layout">

        {{-- Colonne principale --}}
        <div class="show-main">

            {{-- Coordonnées --}}
            <section class="info-card">
                <header class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fa-solid fa-address-book" aria-hidden="true"></i>
                    </div>
                    <h2>Coordonnées</h2>
                </header>

                <dl class="info-dl">
                    <div class="info-row">
                        <dt>Nom complet</dt>
                        <dd>{{ $contact->nom }}</dd>
                    </div>
                    <div class="info-row">
                        <dt>Email</dt>
                        <dd>
                            <a href="mailto:{{ $contact->email }}" class="link">
                                {{ $contact->email }}
                            </a>
                        </dd>
                    </div>
                    <div class="info-row">
                        <dt>Téléphone</dt>
                        <dd>
                            @if($contact->telephone)
                                <a href="tel:{{ $contact->telephone }}" class="link">
                                    {{ $contact->telephone }}
                                </a>
                            @else
                                <span class="text-muted">Non renseigné</span>
                            @endif
                        </dd>
                    </div>
                    <div class="info-row">
                        <dt>Statut</dt>
                        <dd>
                            @if($contact->est_responsable)
                                <span class="badge badge-purple">
                                    <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                                    Responsable
                                </span>
                            @else
                                <span class="badge badge-slate">
                                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                                    Contact simple
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            {{-- Élèves liés (si responsable) --}}
            @if($contact->est_responsable && isset($contact->eleves) && $contact->eleves->isNotEmpty())
                <section class="info-card">
                    <header class="info-card-header">
                        <div class="info-card-icon info-card-icon-purple">
                            <i class="fa-solid fa-user-graduate" aria-hidden="true"></i>
                        </div>
                        <h2>Élèves rattachés ({{ $contact->eleves->count() }})</h2>
                    </header>

                    <ul class="eleves-list">
                        @foreach($contact->eleves as $eleve)
                            <li class="eleve-item">
                                <div class="eleve-avatar" aria-hidden="true">
                                    {{ strtoupper(mb_substr($eleve->nom ?? '?', 0, 1)) }}
                                </div>
                                <div class="eleve-info">
                                    <span class="eleve-name">{{ $eleve->nom }}</span>
                                    @if($eleve->matricule ?? null)
                                        <span class="eleve-matricule">#{{ $eleve->matricule }}</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

        </div>

        {{-- Aside --}}
        <aside class="show-aside">

            {{-- Meta --}}
            <div class="info-card">
                <header class="info-card-header">
                    <div class="info-card-icon info-card-icon-slate">
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    </div>
                    <h2>Historique</h2>
                </header>

                <dl class="info-dl info-dl-compact">
                    <div class="info-row">
                        <dt>ID</dt>
                        <dd>#{{ $contact->id }}</dd>
                    </div>
                    <div class="info-row">
                        <dt>Créé le</dt>
                        <dd>
                            <time datetime="{{ $contact->created_at->toIso8601String() }}">
                                {{ $contact->created_at->format('d/m/Y') }}
                            </time>
                            <span class="text-muted">
                                à {{ $contact->created_at->format('H:i') }}
                            </span>
                        </dd>
                    </div>
                    @if($contact->updated_at && $contact->updated_at->ne($contact->created_at))
                        <div class="info-row">
                            <dt>Modifié le</dt>
                            <dd>
                                <time datetime="{{ $contact->updated_at->toIso8601String() }}">
                                    {{ $contact->updated_at->format('d/m/Y') }}
                                </time>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Danger --}}
            <div class="danger-card">
                <header class="danger-header">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    <h3>Zone sensible</h3>
                </header>
                <p class="danger-text">
                    La suppression est définitive et supprimera toutes les données associées.
                </p>
                <form action="{{ route('admin.contacts.destroy', $contact) }}"
                      method="POST"
                      onsubmit="return confirm('Supprimer définitivement l\'abonné « {{ addslashes($contact->nom) }} » ?\n\nCette action est irréversible.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                        <span>Supprimer l'abonné</span>
                    </button>
                </form>
            </div>

        </aside>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* ════════════════════════════════════════════════════════
       BASE — scopé sous .contact-show-page
       ════════════════════════════════════════════════════════ */
    .contact-show-page {
        --c-primary:      #4f46e5;
        --c-primary-soft: #eef2ff;
        --c-primary-mid:  #c7d2fe;

        --c-purple:      #7c3aed;
        --c-purple-soft: #f5f3ff;

        --c-emerald:      #059669;
        --c-emerald-soft: #ecfdf5;

        --c-rose:      #dc2626;
        --c-rose-soft: #fef2f2;

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

        --t: 0.2s cubic-bezier(.4,0,.2,1);

        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        color: var(--c-slate-800);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .contact-show-page *,
    .contact-show-page *::before,
    .contact-show-page *::after { box-sizing: border-box; }

    /* ─── HEADER ─── */
    .contact-show-page .page-header {
        display: flex; flex-direction: column; gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    @media (min-width: 768px) {
        .contact-show-page .page-header {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .contact-show-page .page-header-text { min-width: 0; }
    .contact-show-page .page-title {
        display: flex; align-items: center; gap: 0.6rem;
        font-size: 1.6rem; font-weight: 800; color: var(--c-slate-900);
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .contact-show-page .title-icon { color: #6366f1; font-size: 1.35rem; }
    .contact-show-page .page-subtitle {
        color: var(--c-slate-500); font-size: 0.9rem; margin: 0;
    }
    .contact-show-page .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
    @media (max-width: 640px) {
        .contact-show-page .header-actions { width: 100%; }
        .contact-show-page .header-actions .btn { flex: 1; }
    }

    /* ─── BOUTONS ─── */
    .contact-show-page .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.7rem 1.15rem;
        border-radius: var(--radius-md);
        font-weight: 600; font-size: 0.875rem; font-family: inherit;
        text-decoration: none; border: none; cursor: pointer;
        transition: all var(--t); white-space: nowrap; min-height: 44px;
    }
    .contact-show-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .contact-show-page .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }
    .contact-show-page .btn-ghost {
        background: #fff; color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .contact-show-page .btn-ghost:hover {
        border-color: #6366f1; color: var(--c-primary);
        background: var(--c-slate-50);
    }
    .contact-show-page .btn-danger {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.65rem 1rem;
        background: var(--c-rose);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 0.82rem; font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: all var(--t);
        min-height: 40px;
    }
    .contact-show-page .btn-danger:hover {
        background: #b91c1c;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220,38,38,0.3);
    }
    .contact-show-page .btn:focus-visible {
        outline: 2px solid var(--c-primary); outline-offset: 2px;
    }

    /* ─── HERO ─── */
    .contact-show-page .hero-card {
        display: flex; flex-direction: column;
        gap: 1.15rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border-radius: var(--radius-lg);
        color: #fff;
        box-shadow: 0 15px 35px rgba(79,70,229,0.25);
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    @media (min-width: 768px) {
        .contact-show-page .hero-card {
            flex-direction: row; align-items: center;
            padding: 1.75rem 2rem;
        }
    }

    .contact-show-page .hero-card::before {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.15), transparent 60%);
        pointer-events: none;
    }

    .contact-show-page .hero-avatar {
        width: 72px; height: 72px;
        border-radius: 20px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.75rem; font-weight: 800;
        flex-shrink: 0;
        letter-spacing: 0.5px;
    }

    .contact-show-page .hero-info {
        flex: 1;
        min-width: 0;
        display: flex; flex-direction: column; gap: 0.35rem;
    }
    .contact-show-page .hero-name {
        font-size: 1.35rem; font-weight: 800;
        margin: 0;
        letter-spacing: -0.3px;
    }
    .contact-show-page .hero-email {
        display: inline-flex; align-items: center; gap: 0.4rem;
        font-size: 0.85rem;
        color: rgba(255,255,255,0.85);
        margin: 0;
    }
    .contact-show-page .hero-email a {
        color: inherit;
        text-decoration: none;
    }
    .contact-show-page .hero-email a:hover {
        text-decoration: underline;
    }
    .contact-show-page .hero-email i { font-size: 0.75rem; }

    .contact-show-page .hero-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        align-self: flex-start;
        margin-top: 0.35rem;
        padding: 0.25rem 0.65rem;
        background: rgba(255,255,255,0.22);
        border-radius: 9999px;
        font-size: 0.72rem; font-weight: 700;
    }
    .contact-show-page .hero-badge i { font-size: 0.65rem; }
    .contact-show-page .hero-badge-slate {
        background: rgba(255,255,255,0.15);
    }

    .contact-show-page .hero-actions {
        display: flex; gap: 0.5rem; flex-wrap: wrap;
    }
    @media (max-width: 767px) {
        .contact-show-page .hero-actions { width: 100%; }
        .contact-show-page .hero-action { flex: 1; }
    }
    .contact-show-page .hero-action {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.4rem;
        padding: 0.6rem 1rem;
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: var(--radius-sm);
        color: #fff;
        text-decoration: none;
        font-size: 0.82rem; font-weight: 600;
        transition: all var(--t);
        min-height: 40px;
    }
    .contact-show-page .hero-action:hover {
        background: rgba(255,255,255,0.25);
        transform: translateY(-1px);
    }

    /* ─── LAYOUT ─── */
    .contact-show-page .show-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    @media (min-width: 1024px) {
        .contact-show-page .show-layout {
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            align-items: start;
        }
    }

    .contact-show-page .show-main,
    .contact-show-page .show-aside {
        display: flex; flex-direction: column; gap: 1.25rem;
        min-width: 0;
    }

    /* ─── INFO CARD ─── */
    .contact-show-page .info-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        box-shadow: var(--shadow-xs);
    }
    @media (max-width: 640px) {
        .contact-show-page .info-card { padding: 1rem; }
    }

    .contact-show-page .info-card-header {
        display: flex; align-items: center; gap: 0.7rem;
        margin-bottom: 1rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid var(--c-slate-100);
    }
    .contact-show-page .info-card-header h2 {
        font-size: 0.95rem; font-weight: 700;
        color: var(--c-slate-900);
        margin: 0;
    }

    .contact-show-page .info-card-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: var(--c-primary-soft);
        color: var(--c-primary);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    .contact-show-page .info-card-icon-purple {
        background: var(--c-purple-soft); color: var(--c-purple);
    }
    .contact-show-page .info-card-icon-slate {
        background: var(--c-slate-100); color: var(--c-slate-500);
    }

    /* ─── DL ─── */
    .contact-show-page .info-dl {
        display: flex; flex-direction: column; gap: 0.85rem;
        margin: 0;
    }
    .contact-show-page .info-dl-compact {
        gap: 0.65rem;
    }

    .contact-show-page .info-row {
        display: flex; flex-direction: column; gap: 0.2rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid var(--c-slate-100);
    }
    .contact-show-page .info-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    @media (min-width: 640px) {
        .contact-show-page .info-row {
            flex-direction: row; align-items: baseline; gap: 1rem;
            justify-content: space-between;
        }
        .contact-show-page .info-row dt {
            flex-shrink: 0;
            min-width: 140px;
        }
    }

    .contact-show-page .info-row dt {
        font-size: 0.78rem; font-weight: 600;
        color: var(--c-slate-500);
        text-transform: uppercase; letter-spacing: 0.3px;
    }
    .contact-show-page .info-row dd {
        font-size: 0.9rem; color: var(--c-slate-800);
        margin: 0;
        word-break: break-word;
    }

    .contact-show-page .link {
        color: var(--c-primary);
        text-decoration: none;
        font-weight: 500;
        transition: color var(--t);
    }
    .contact-show-page .link:hover { text-decoration: underline; }

    .contact-show-page .text-muted {
        color: var(--c-slate-400);
        font-size: 0.82rem;
    }

    /* ─── BADGES ─── */
    .contact-show-page .badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.72rem; font-weight: 600;
    }
    .contact-show-page .badge-purple {
        background: var(--c-purple-soft); color: var(--c-purple);
    }
    .contact-show-page .badge-slate {
        background: var(--c-slate-100); color: var(--c-slate-600);
    }
    .contact-show-page .badge i { font-size: 0.6rem; }

    /* ─── ÉLÈVES ─── */
    .contact-show-page .eleves-list {
        display: flex; flex-direction: column; gap: 0.5rem;
        list-style: none; padding: 0; margin: 0;
    }
    .contact-show-page .eleve-item {
        display: flex; align-items: center; gap: 0.7rem;
        padding: 0.6rem 0.7rem;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-sm);
        transition: all var(--t);
    }
    .contact-show-page .eleve-item:hover {
        border-color: var(--c-primary-mid);
        background: var(--c-primary-soft);
    }
    .contact-show-page .eleve-avatar {
        width: 34px; height: 34px;
        border-radius: 8px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.78rem; font-weight: 700;
        flex-shrink: 0;
    }
    .contact-show-page .eleve-info {
        display: flex; flex-direction: column; gap: 0.1rem;
        min-width: 0;
    }
    .contact-show-page .eleve-name {
        font-size: 0.85rem; font-weight: 600;
        color: var(--c-slate-800);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .contact-show-page .eleve-matricule {
        font-size: 0.72rem; color: var(--c-slate-500);
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
    }

    /* ─── DANGER ─── */
    .contact-show-page .danger-card {
        background: var(--c-rose-soft);
        border: 1.5px solid #fecaca;
        border-radius: var(--radius-lg);
        padding: 1.15rem;
    }
    .contact-show-page .danger-header {
        display: flex; align-items: center; gap: 0.5rem;
        margin-bottom: 0.6rem;
    }
    .contact-show-page .danger-header i {
        color: var(--c-rose); font-size: 0.95rem;
    }
    .contact-show-page .danger-header h3 {
        font-size: 0.9rem; font-weight: 700;
        color: #7f1d1d; margin: 0;
    }
    .contact-show-page .danger-text {
        font-size: 0.78rem; color: #991b1b;
        line-height: 1.5; margin: 0 0 0.85rem;
    }

    /* ─── RESPONSIVE ─── */
    @media (max-width: 992px) {
        .contact-show-page { padding: 1.5rem 1rem; }
    }
    @media (max-width: 768px) {
        .contact-show-page { padding: 1.25rem 0.85rem; }
        .contact-show-page .page-title { font-size: 1.35rem; }
        .contact-show-page .title-icon { font-size: 1.15rem; }
    }
    @media (max-width: 480px) {
        .contact-show-page { padding: 1rem 0.65rem; }
        .contact-show-page .page-title { font-size: 1.15rem; }
        .contact-show-page .hero-card { padding: 1.15rem; }
        .contact-show-page .hero-avatar { width: 60px; height: 60px; font-size: 1.4rem; }
        .contact-show-page .hero-name { font-size: 1.15rem; }
        .contact-show-page .info-card { padding: 0.85rem; }
    }

    /* ─── A11Y + PRINT ─── */
    @media (prefers-reduced-motion: reduce) {
        .contact-show-page *,
        .contact-show-page *::before,
        .contact-show-page *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }
    @media print {
        .contact-show-page .header-actions,
        .contact-show-page .hero-actions,
        .contact-show-page .danger-card { display: none !important; }
        .contact-show-page .hero-card {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            box-shadow: none;
        }
        .contact-show-page .hero-card * { color: #0f172a !important; }
        .contact-show-page .hero-badge { background: #e2e8f0 !important; }
        .contact-show-page .info-card {
            box-shadow: none; border: 1px solid #ccc;
        }
    }
</style>
@endpush