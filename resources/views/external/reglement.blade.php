@extends('layouts.contact')

@section('title', 'Règlement intérieur')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="reglement-page">
    <div class="reglement-container">

        {{-- En-tête --}}
        <header class="reglement-header">
            <div class="reglement-header-icon">
                <i class="fa-solid fa-gavel"></i>
            </div>
            <h1 class="reglement-title">Règlement intérieur</h1>
            <p class="reglement-subtitle">Les règles de vie à connaître et à respecter</p>
        </header>

        {{-- Navigation rapide (uniquement si au moins une section a du contenu) --}}
        @php
            $hasRegles       = $regles->isNotEmpty()       ?? false;
            $hasObligations  = $obligations->isNotEmpty()  ?? false;
            $hasInterdictions= $interdictions->isNotEmpty()?? false;
            $hasAny          = $hasRegles || $hasObligations || $hasInterdictions;
        @endphp

        @if($hasAny)
            <nav class="reglement-nav" aria-label="Navigation rapide">
                @if($hasRegles)
                    <a href="#section-regles" class="nav-pill nav-pill-blue">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>Règles à suivre</span>
                        <span class="nav-count">{{ $regles->count() }}</span>
                    </a>
                @endif
                @if($hasObligations)
                    <a href="#section-obligations" class="nav-pill nav-pill-amber">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <span>Obligations</span>
                        <span class="nav-count">{{ $obligations->count() }}</span>
                    </a>
                @endif
                @if($hasInterdictions)
                    <a href="#section-interdictions" class="nav-pill nav-pill-red">
                        <i class="fa-solid fa-ban"></i>
                        <span>Interdictions</span>
                        <span class="nav-count">{{ $interdictions->count() }}</span>
                    </a>
                @endif
            </nav>
        @endif

        {{-- Section Règles à suivre --}}
        <section class="reglement-section" id="section-regles">
            <div class="section-head">
                <div class="section-icon section-icon-blue">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <h2 class="section-title">
                    Règles à suivre
                    @if($hasRegles)
                        <span class="section-count">{{ $regles->count() }}</span>
                    @endif
                </h2>
            </div>

            @forelse($regles as $regle)
                <article class="reglement-card reglement-card-blue">
                    <div class="reglement-card-head">
                        <i class="fa-solid fa-circle-check card-icon"></i>
                        <h3 class="reglement-card-title">{{ $regle->titre }}</h3>
                    </div>
                    <div class="reglement-card-body">
                        {!! $regle->contenu !!}
                    </div>
                </article>
            @empty
                <div class="empty-box empty-box-blue">
                    <i class="fa-regular fa-circle-question"></i>
                    <span>Aucune règle définie pour le moment.</span>
                </div>
            @endforelse
        </section>

        {{-- Section Obligations --}}
        <section class="reglement-section" id="section-obligations">
            <div class="section-head">
                <div class="section-icon section-icon-amber">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                </div>
                <h2 class="section-title">
                    Obligations
                    @if($hasObligations)
                        <span class="section-count">{{ $obligations->count() }}</span>
                    @endif
                </h2>
            </div>

            @forelse($obligations as $obligation)
                <article class="reglement-card reglement-card-amber">
                    <div class="reglement-card-head">
                        <i class="fa-solid fa-bullhorn card-icon"></i>
                        <h3 class="reglement-card-title">{{ $obligation->titre }}</h3>
                    </div>
                    <div class="reglement-card-body">
                        {!! $obligation->contenu !!}
                    </div>
                </article>
            @empty
                <div class="empty-box empty-box-amber">
                    <i class="fa-regular fa-circle-question"></i>
                    <span>Aucune obligation définie.</span>
                </div>
            @endforelse
        </section>

        {{-- Section Interdictions --}}
        <section class="reglement-section" id="section-interdictions">
            <div class="section-head">
                <div class="section-icon section-icon-red">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <h2 class="section-title">
                    Interdictions
                    @if($hasInterdictions)
                        <span class="section-count">{{ $interdictions->count() }}</span>
                    @endif
                </h2>
            </div>

            @forelse($interdictions as $interdiction)
                <article class="reglement-card reglement-card-red">
                    <div class="reglement-card-head">
                        <i class="fa-solid fa-times-circle card-icon"></i>
                        <h3 class="reglement-card-title">{{ $interdiction->titre }}</h3>
                    </div>
                    <div class="reglement-card-body">
                        {!! $interdiction->contenu !!}
                    </div>
                </article>
            @empty
                <div class="empty-box empty-box-red">
                    <i class="fa-regular fa-circle-question"></i>
                    <span>Aucune interdiction définie.</span>
                </div>
            @endforelse
        </section>

    </div>
</div>

<style>
    /* =========================================================
       BASE
       ========================================================= */
    .reglement-page {
        background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        min-height: 70vh;
        padding: 2.5rem 0 4rem;
        scroll-behavior: smooth;
    }
    .reglement-page * { box-sizing: border-box; }

    .reglement-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 1.25rem;
    }

    /* =========================================================
       EN-TÊTE
       ========================================================= */
    .reglement-header {
        text-align: center;
        margin-bottom: 2.5rem;
        padding: 0 0.5rem;
    }
    .reglement-header-icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 1rem;
        border-radius: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        box-shadow: 0 12px 30px rgba(102,126,234,0.35);
    }
    .reglement-title {
        font-size: 2.25rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 0.5rem;
        letter-spacing: -0.5px;
        line-height: 1.15;
    }
    .reglement-subtitle {
        font-size: 1.05rem;
        color: #64748b;
        margin: 0;
        line-height: 1.5;
    }

    /* =========================================================
       NAVIGATION RAPIDE
       ========================================================= */
    .reglement-nav {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.6rem;
        margin-bottom: 2.5rem;
    }
    .nav-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1rem;
        border-radius: 999px;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        color: #475569;
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 600;
        transition: all 0.25s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .nav-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        text-decoration: none;
    }
    .nav-pill-blue:hover  { border-color: #93c5fd; color: #1d4ed8; }
    .nav-pill-amber:hover { border-color: #fcd34d; color: #b45309; }
    .nav-pill-red:hover   { border-color: #fca5a5; color: #b91c1c; }

    .nav-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.7rem;
        font-weight: 700;
    }
    .nav-pill-blue  .nav-count { background: #dbeafe; color: #1d4ed8; }
    .nav-pill-amber .nav-count { background: #fef3c7; color: #92400e; }
    .nav-pill-red   .nav-count { background: #fee2e2; color: #b91c1c; }

    /* =========================================================
       SECTIONS
       ========================================================= */
    .reglement-section {
        margin-bottom: 2.5rem;
        scroll-margin-top: 1.5rem;
    }
    .section-head {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        margin-bottom: 1.25rem;
    }
    .section-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .section-icon-blue  { background: #dbeafe; color: #1d4ed8; }
    .section-icon-amber { background: #fef3c7; color: #b45309; }
    .section-icon-red   { background: #fee2e2; color: #b91c1c; }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        line-height: 1.2;
    }
    .section-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 26px;
        height: 26px;
        padding: 0 8px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 700;
    }

    /* =========================================================
       CARTES
       ========================================================= */
    .reglement-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        border: 1px solid #f1f5f9;
        border-left: 4px solid #e2e8f0;
        padding: 1.25rem 1.5rem;
        margin-bottom: 0.9rem;
        transition: box-shadow 0.25s ease, border-color 0.25s ease;
    }
    .reglement-card-blue  { border-left-color: #3b82f6; }
    .reglement-card-amber { border-left-color: #f59e0b; }
    .reglement-card-red   { border-left-color: #ef4444; }

    @media (hover: hover) {
        .reglement-card:hover {
            box-shadow: 0 10px 28px rgba(0,0,0,0.08);
        }
    }

    .reglement-card-head {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        margin-bottom: 0.6rem;
    }
    .card-icon {
        font-size: 1.05rem;
        margin-top: 0.3rem;
        flex-shrink: 0;
    }
    .reglement-card-blue  .card-icon { color: #10b981; } /* vert, comme une règle "OK" */
    .reglement-card-amber .card-icon { color: #d97706; }
    .reglement-card-red   .card-icon { color: #dc2626; }

    .reglement-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        line-height: 1.35;
    }

    .reglement-card-body {
        color: #475569;
        font-size: 0.98rem;
        line-height: 1.65;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Contenu HTML de CKEditor */
    .reglement-card-body p { margin: 0 0 0.75rem; }
    .reglement-card-body p:last-child { margin-bottom: 0; }
    .reglement-card-body ul,
    .reglement-card-body ol { padding-left: 1.25rem; margin: 0.5rem 0 0.75rem; }
    .reglement-card-body li { margin-bottom: 0.25rem; }
    .reglement-card-body img { max-width: 100%; height: auto; border-radius: 10px; }
    .reglement-card-body a { color: #667eea; text-decoration: underline; }
    .reglement-card-body a:hover { color: #4f46e5; }
    .reglement-card-body blockquote {
        border-left: 3px solid #e2e8f0;
        padding: 0.5rem 0 0.5rem 1rem;
        margin: 0.75rem 0;
        color: #64748b;
        font-style: italic;
    }
    .reglement-card-body table {
        width: 100%;
        max-width: 100%;
        border-collapse: collapse;
        margin: 0.75rem 0;
        display: block;
        overflow-x: auto;
    }
    .reglement-card-body th,
    .reglement-card-body td {
        padding: 0.5rem 0.75rem;
        border: 1px solid #e2e8f0;
        text-align: left;
    }
    .reglement-card-body th { background: #f8fafc; font-weight: 600; }

    /* =========================================================
       ÉTATS VIDES
       ========================================================= */
    .empty-box {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.25rem 1.5rem;
        border-radius: 14px;
        border: 1px dashed #cbd5e1;
        background: #fff;
        color: #64748b;
        font-size: 0.95rem;
    }
    .empty-box i { font-size: 1.15rem; color: #94a3b8; flex-shrink: 0; }
    .empty-box-blue  { border-color: #bfdbfe; background: #f0f7ff; }
    .empty-box-amber { border-color: #fde68a; background: #fffbeb; }
    .empty-box-red   { border-color: #fecaca; background: #fef2f2; }

    /* =========================================================
       RESPONSIVE — TABLETTE (≤ 768px)
       ========================================================= */
    @media (max-width: 768px) {
        .reglement-page { padding: 1.75rem 0 3rem; }
        .reglement-container { padding: 0 1rem; }

        .reglement-header { margin-bottom: 1.75rem; }
        .reglement-header-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            font-size: 1.6rem;
        }
        .reglement-title { font-size: 1.65rem; }
        .reglement-subtitle { font-size: 0.95rem; }

        .reglement-nav {
            gap: 0.5rem;
            margin-bottom: 1.75rem;
        }
        .nav-pill {
            padding: 0.5rem 0.85rem;
            font-size: 0.82rem;
        }

        .reglement-section { margin-bottom: 2rem; }
        .section-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            font-size: 1.15rem;
        }
        .section-title { font-size: 1.3rem; }
        .section-count { font-size: 0.72rem; min-width: 22px; height: 22px; }

        .reglement-card {
            padding: 1rem 1.15rem;
            border-radius: 14px;
        }
        .reglement-card-title { font-size: 1rem; }
        .reglement-card-body { font-size: 0.93rem; }
    }

    /* =========================================================
       RESPONSIVE — PETIT MOBILE (≤ 480px)
       ========================================================= */
    @media (max-width: 480px) {
        .reglement-page { padding: 1.25rem 0 2.5rem; }
        .reglement-container { padding: 0 0.85rem; }

        .reglement-header-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            font-size: 1.35rem;
        }
        .reglement-title { font-size: 1.35rem; }
        .reglement-subtitle { font-size: 0.88rem; }

        /* Nav : pill en pleine largeur */
        .reglement-nav { flex-direction: column; }
        .nav-pill {
            justify-content: center;
            width: 100%;
            padding: 0.65rem 1rem;
        }

        .section-head { gap: 0.65rem; margin-bottom: 1rem; }
        .section-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 1rem;
        }
        .section-title { font-size: 1.15rem; }

        .reglement-card {
            padding: 0.9rem 1rem;
            border-radius: 12px;
            border-left-width: 3px;
        }
        .reglement-card-title { font-size: 0.95rem; }
        .reglement-card-body { font-size: 0.9rem; line-height: 1.6; }

        .reglement-card-body ul,
        .reglement-card-body ol { padding-left: 1.1rem; }
    }

    /* =========================================================
       ACCESSIBILITÉ — Réduction des animations
       ========================================================= */
    @media (prefers-reduced-motion: reduce) {
        .reglement-page { scroll-behavior: auto; }
        .nav-pill,
        .reglement-card { transition: none; }
    }

    /* =========================================================
       IMPRESSION
       ========================================================= */
    @media print {
        .reglement-page { background: #fff; padding: 0; }
        .reglement-nav { display: none; }
        .reglement-card {
            box-shadow: none;
            border: 1px solid #e2e8f0;
            page-break-inside: avoid;
        }
    }
</style>
@endsection