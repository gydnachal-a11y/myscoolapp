@extends('layouts.admin')

@section('page_title', 'Accès refusé')
@section('page_subtitle', 'Permission insuffisante')

@section('content')
@php
    // Récupère le message d'erreur (fourni par abort(403, '...'))
    // ou un message par défaut
    $message = $exception->getMessage() ?: "Vous n'avez pas la permission d'accéder à cette page.";

    // Détecte si le user est connecté pour adapter les CTA
    $webUser = auth('web')->user();

    // Route de retour sécurisée
    $homeRoute = $webUser ? 'admin.redirect' : 'home';
@endphp

<div class="error-page">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- ICÔNE + TITRE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-hero">
        <div class="error-icon-wrapper" aria-hidden="true">
            <i class="fa-solid fa-lock"></i>
        </div>

        <span class="error-code">Erreur 403</span>

        <h1 class="error-title">Accès refusé</h1>

        <p class="error-message">{{ $message }}</p>
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- CTA --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-actions">
        @if(Route::has($homeRoute))
            <a href="{{ route($homeRoute) }}" class="error-btn error-btn-primary">
                <i class="fa-solid fa-house" aria-hidden="true"></i>
                <span>Retour à l'accueil</span>
            </a>
        @endif

        <button type="button"
                class="error-btn error-btn-ghost"
                onclick="history.length > 1 ? history.back() : window.location.href = '{{ route('home') }}'">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span>Page précédente</span>
        </button>
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- AIDE / CONTEXTE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-help">

        <div class="help-header">
            <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
            <h2>Pourquoi cette erreur ?</h2>
        </div>

        <ul class="help-list">
            <li>
                <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                <div>
                    <strong>Votre rôle ne couvre pas cette page</strong>
                    <span>Cette ressource est réservée à certains rôles uniquement.</span>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-key" aria-hidden="true"></i>
                <div>
                    <strong>Permission manquante</strong>
                    <span>Une permission spécifique est requise pour accéder à cette section.</span>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-link-slash" aria-hidden="true"></i>
                <div>
                    <strong>Lien obsolète ou mal copié</strong>
                    <span>L'adresse peut ne plus être valide ou avoir changé.</span>
                </div>
            </li>
        </ul>

        @if($webUser)
            <div class="help-footer">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <p>
                    Si vous pensez qu'il s'agit d'une erreur, contactez un administrateur
                    en lui communiquant l'adresse que vous avez essayé d'ouvrir.
                </p>
            </div>
        @else
            <div class="help-footer">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <p>
                    Connectez-vous avec un compte autorisé pour accéder à cette ressource.
                </p>
            </div>
        @endif

    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- PIED DE PAGE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="error-footer">
        <p>
            Code d'erreur <code>403</code> · Forbidden
        </p>
    </div>

</div>
@endsection

@push('styles')
<style>
    /* ════════════════════════════════════════════════════════
       BASE — scopé sous .error-page
       ════════════════════════════════════════════════════════ */
    .error-page {
        max-width: 720px;
        margin: 0 auto;
        padding: 3rem 1.25rem 2rem;
        text-align: center;
        animation: errorFadeIn 0.4s ease-out both;
    }

    @keyframes errorFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ════════════════════════════════════════════════════════
       HERO
       ════════════════════════════════════════════════════════ */
    .error-hero {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 2.5rem;
    }

    .error-icon-wrapper {
        width: 112px;
        height: 112px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.5rem;
        position: relative;
        box-shadow:
            0 0 0 8px rgba(254, 226, 226, 0.4),
            0 12px 32px rgba(220, 38, 38, 0.15);
        animation: iconPulse 2.5s ease-in-out infinite;
    }

    @keyframes iconPulse {
        0%, 100% { transform: scale(1); }
        50%      { transform: scale(1.04); }
    }

    .error-icon-wrapper i {
        font-size: 2.75rem;
        color: #dc2626;
    }

    .error-code {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.85rem;
        background: #fef2f2;
        color: #b91c1c;
        border-radius: 9999px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        border: 1px solid #fecaca;
    }

    .error-title {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.5px;
        line-height: 1.15;
    }

    .error-message {
        font-size: 1rem;
        color: #64748b;
        margin: 0;
        line-height: 1.6;
        max-width: 520px;
    }

    /* ════════════════════════════════════════════════════════
       ACTIONS
       ════════════════════════════════════════════════════════ */
    .error-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 3rem;
    }

    .error-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.85rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        font-family: inherit;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(.4, 0, .2, 1);
        white-space: nowrap;
        min-height: 48px;
        min-width: 160px;
    }

    .error-btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.28);
    }
    .error-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(79, 70, 229, 0.38);
    }
    .error-btn-primary:active {
        transform: translateY(0);
    }

    .error-btn-ghost {
        background: #fff;
        color: #475569;
        border: 1.5px solid #e2e8f0;
    }
    .error-btn-ghost:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #0f172a;
    }

    .error-btn:focus-visible {
        outline: 2px solid #4f46e5;
        outline-offset: 3px;
    }

    /* ════════════════════════════════════════════════════════
       AIDE
       ════════════════════════════════════════════════════════ */
    .error-help {
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        padding: 1.5rem;
        text-align: left;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03), 0 4px 14px rgba(0, 0, 0, 0.02);
        margin-bottom: 2rem;
    }

    .help-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 1.25rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .help-header i {
        color: #6366f1;
        font-size: 1.05rem;
    }
    .help-header h2 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.2px;
    }

    .help-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }
    .help-list li {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .help-list li > i {
        flex-shrink: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 8px;
        font-size: 0.8rem;
    }
    .help-list li > div {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        min-width: 0;
    }
    .help-list strong {
        font-size: 0.88rem;
        font-weight: 600;
        color: #1e293b;
    }
    .help-list span {
        font-size: 0.82rem;
        color: #64748b;
        line-height: 1.5;
    }

    .help-footer {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        margin-top: 1.25rem;
        padding: 0.85rem 1rem;
        background: #f8fafc;
        border-left: 3px solid #6366f1;
        border-radius: 8px;
    }
    .help-footer i {
        color: #6366f1;
        font-size: 0.85rem;
        margin-top: 2px;
        flex-shrink: 0;
    }
    .help-footer p {
        margin: 0;
        font-size: 0.82rem;
        color: #475569;
        line-height: 1.55;
    }

    /* ════════════════════════════════════════════════════════
       FOOTER
       ════════════════════════════════════════════════════════ */
    .error-footer {
        text-align: center;
    }
    .error-footer p {
        margin: 0;
        font-size: 0.75rem;
        color: #94a3b8;
        letter-spacing: 0.3px;
    }
    .error-footer code {
        display: inline-block;
        padding: 0.1rem 0.4rem;
        background: #f1f5f9;
        border-radius: 4px;
        font-size: 0.72rem;
        color: #475569;
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
        font-weight: 600;
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE — Mobile
       ════════════════════════════════════════════════════════ */
    @media (max-width: 640px) {
        .error-page {
            padding: 2rem 1rem 1.5rem;
        }

        .error-icon-wrapper {
            width: 88px;
            height: 88px;
        }
        .error-icon-wrapper i {
            font-size: 2.15rem;
        }

        .error-title {
            font-size: 1.55rem;
        }
        .error-message {
            font-size: 0.92rem;
        }

        .error-actions {
            flex-direction: column-reverse;
            gap: 0.6rem;
            margin-bottom: 2rem;
        }
        .error-btn {
            width: 100%;
            min-width: 0;
        }

        .error-help {
            padding: 1.15rem;
        }
        .help-list li > i {
            width: 28px;
            height: 28px;
            font-size: 0.72rem;
        }
        .help-list strong { font-size: 0.84rem; }
        .help-list span   { font-size: 0.78rem; }

        .help-footer {
            padding: 0.75rem 0.85rem;
        }
        .help-footer p { font-size: 0.78rem; }
    }

    /* ════════════════════════════════════════════════════════
       PETIT MOBILE
       ════════════════════════════════════════════════════════ */
    @media (max-width: 380px) {
        .error-icon-wrapper {
            width: 76px;
            height: 76px;
        }
        .error-icon-wrapper i { font-size: 1.85rem; }

        .error-title { font-size: 1.35rem; }
        .error-message { font-size: 0.85rem; }

        .error-btn {
            padding: 0.75rem 1.15rem;
            font-size: 0.85rem;
            min-height: 44px;
        }
    }

    /* ════════════════════════════════════════════════════════
       ACCESSIBILITÉ
       ════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        .error-page,
        .error-icon-wrapper,
        .error-btn {
            animation: none !important;
            transition: none !important;
        }
    }

    /* ════════════════════════════════════════════════════════
       IMPRESSION
       ════════════════════════════════════════════════════════ */
    @media print {
        .error-actions,
        .error-help {
            display: none;
        }
        .error-page {
            padding: 0;
            max-width: 100%;
        }
        .error-icon-wrapper {
            box-shadow: none;
            animation: none;
        }
    }
</style>
@endpush