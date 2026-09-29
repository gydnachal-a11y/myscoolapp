@extends('layouts.contact')

@section('title', 'Contactez-nous')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="contact-page">
    <div class="contact-container">

        <div class="contact-card">
            {{-- ===== EN-TÊTE ===== --}}
            <header class="contact-header">
                <div class="contact-header-icon">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                </div>
                <h1 class="contact-title">Contactez-nous</h1>
                <p class="contact-subtitle">Nous vous répondrons dans les meilleurs délais</p>
            </header>

            {{-- ===== CORPS ===== --}}
            <div class="contact-body">

                {{-- Message flash succès --}}
                @if(session('success'))
                    <div class="flash flash-success" role="alert">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="Fermer"
                                onclick="this.closest('.flash').remove()">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif

                {{-- Message flash erreur --}}
                @if(session('error'))
                    <div class="flash flash-error" role="alert">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                        <span>{{ session('error') }}</span>
                        <button type="button" class="flash-close" aria-label="Fermer"
                                onclick="this.closest('.flash').remove()">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif

                <form action="{{ route('contact.store') }}"
                      method="POST"
                      class="contact-form"
                      novalidate>
                    @csrf

                    {{-- ===== ANTI-SPAM ===== --}}
                    {{-- Champ piège : les humains ne le voient pas, les bots le remplissent --}}
                    <div class="honeypot" aria-hidden="true">
                        <label for="website">Ne pas remplir ce champ</label>
                        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                    </div>

                    {{-- Timestamp de chargement du formulaire --}}
                    <input type="hidden" name="_form_loaded_at" value="{{ time() }}">

                    <div class="form-grid">

                        {{-- ===== NOM ===== --}}
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="text"
                                       name="nom"
                                       id="nom"
                                       value="{{ old('nom') }}"
                                       placeholder=" "
                                       required
                                       minlength="2"
                                       maxlength="120"
                                       autocomplete="name"
                                       aria-required="true"
                                       @error('nom') aria-invalid="true" aria-describedby="nom-error" @enderror
                                       class="form-input @error('nom') is-invalid @enderror">
                                <label for="nom" class="float-label">
                                    Nom complet <span class="required">*</span>
                                </label>
                                <i class="fa-solid fa-user field-icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('nom')
                                <p class="error-text" id="nom-error">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- ===== EMAIL ===== --}}
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="email"
                                       name="email"
                                       id="email"
                                       value="{{ old('email') }}"
                                       placeholder=" "
                                       required
                                       maxlength="255"
                                       inputmode="email"
                                       autocomplete="email"
                                       aria-required="true"
                                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                       class="form-input @error('email') is-invalid @enderror">
                                <label for="email" class="float-label">
                                    Adresse email <span class="required">*</span>
                                </label>
                                <i class="fa-solid fa-envelope field-icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('email')
                                <p class="error-text" id="email-error">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- ===== SUJET ===== --}}
                        <div class="form-field form-field-full">
                            <div class="input-wrap">
                                <input type="text"
                                       name="sujet"
                                       id="sujet"
                                       value="{{ old('sujet') }}"
                                       placeholder=" "
                                       required
                                       minlength="3"
                                       maxlength="200"
                                       aria-required="true"
                                       @error('sujet') aria-invalid="true" aria-describedby="sujet-error" @enderror
                                       class="form-input @error('sujet') is-invalid @enderror">
                                <label for="sujet" class="float-label">
                                    Sujet <span class="required">*</span>
                                </label>
                                <i class="fa-solid fa-heading field-icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('sujet')
                                <p class="error-text" id="sujet-error">
                                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- ===== MESSAGE ===== --}}
                        <div class="form-field form-field-full">
                            <div class="input-wrap textarea-wrap">
                                <textarea name="message"
                                          id="message"
                                          rows="5"
                                          placeholder=" "
                                          required
                                          minlength="10"
                                          maxlength="5000"
                                          data-counter-target="message-counter"
                                          aria-required="true"
                                          @error('message') aria-invalid="true" aria-describedby="message-error" @enderror
                                          class="form-input @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                                <label for="message" class="float-label">
                                    Votre message <span class="required">*</span>
                                </label>
                                <i class="fa-solid fa-comment-dots field-icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            <div class="field-meta">
                                @error('message')
                                    <p class="error-text" id="message-error">
                                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                                        {{ $message }}
                                    </p>
                                @else
                                    <span class="field-hint">
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                        Minimum 10 caractères
                                    </span>
                                @enderror
                                <span class="char-counter" id="message-counter">
                                    {{ strlen(old('message', '')) }} / 5000
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- ===== ACTIONS ===== --}}
                    <div class="form-actions">
                        <a href="{{ route('home') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            <span>Retour</span>
                        </a>

                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <span class="btn-label">
                                Envoyer
                                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            </span>
                            <span class="btn-loading" hidden>
                                <i class="fa-solid fa-circle-notch spin" aria-hidden="true"></i>
                                Envoi en cours…
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* ============================================================
       BASE
       ============================================================ */
    .contact-page {
        background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        min-height: 70vh;
        padding: 3rem 0 4rem;
    }
    .contact-page * { box-sizing: border-box; }

    .contact-container {
        max-width: 780px;
        margin: 0 auto;
        padding: 0 1.25rem;
    }

    /* ============================================================
       CARTE
       ============================================================ */
    .contact-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(0,0,0,0.08);
        border: 1px solid #f1f5f9;
    }

    /* ============================================================
       EN-TÊTE
       ============================================================ */
    .contact-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2.25rem 2rem 1.75rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .contact-header::before {
        content: '';
        position: absolute;
        inset: -50% -20% auto auto;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .contact-header-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto 0.75rem;
        border-radius: 16px;
        background: rgba(255,255,255,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        backdrop-filter: blur(10px);
        position: relative;
        z-index: 1;
    }
    .contact-title {
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0 0 0.35rem;
        position: relative;
        z-index: 1;
        letter-spacing: -0.3px;
    }
    .contact-subtitle {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }

    /* ============================================================
       CORPS
       ============================================================ */
    .contact-body {
        padding: 2rem 2.25rem 2.25rem;
    }

    /* ============================================================
       HONEYPOT (invisible pour les humains)
       ============================================================ */
    .honeypot {
        position: absolute;
        left: -9999px;
        top: -9999px;
        width: 1px;
        height: 1px;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
    }

    /* ============================================================
       GRILLE
       ============================================================ */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.75rem 1.5rem;
    }
    .form-field { min-width: 0; }
    .form-field-full { grid-column: 1 / -1; }

    /* ============================================================
       CHAMPS FLOTTANTS
       ============================================================ */
    .input-wrap {
        position: relative;
        padding-bottom: 0.4rem;
    }

    .form-input {
        width: 100%;
        padding: 0.9rem 2.4rem 0.5rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        font-family: inherit;
        color: #1e293b;
        font-weight: 500;
        border-radius: 0;
        outline: none;
        transition: border-color 0.25s;
    }
    .form-input:focus {
        border-bottom-color: #667eea;
    }
    .form-input.is-invalid {
        border-bottom-color: #ef4444;
    }

    /* Label flottant */
    .float-label {
        position: absolute;
        left: 0;
        top: 0.9rem;
        color: #94a3b8;
        font-size: 1rem;
        font-weight: 400;
        pointer-events: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        margin: 0;
        line-height: 1.2;
    }
    .float-label .required { color: #ef4444; }

    .form-input:focus ~ .float-label,
    .form-input:not(:placeholder-shown) ~ .float-label {
        top: -0.35rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.4px;
        text-transform: uppercase;
    }
    .form-input.is-invalid:focus ~ .float-label,
    .form-input.is-invalid:not(:placeholder-shown) ~ .float-label {
        color: #ef4444;
    }

    /* Icône */
    .field-icon {
        position: absolute;
        right: 0;
        top: 1rem;
        color: #cbd5e1;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.25s;
    }
    .form-input:focus ~ .field-icon {
        color: #667eea;
        transform: scale(1.1);
    }
    .form-input.is-invalid ~ .field-icon {
        color: #ef4444;
    }

    /* Ligne de focus animée */
    .line-focus {
        position: absolute;
        bottom: 0.4rem;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .form-input:focus ~ .line-focus {
        width: 100%;
    }

    /* Textarea */
    .textarea-wrap .form-input {
        min-height: 130px;
        max-height: 340px;
        resize: vertical;
        line-height: 1.55;
        padding-top: 1.15rem;
    }
    .textarea-wrap .float-label {
        top: 1.15rem;
    }

    /* ============================================================
       MESSAGES D'ERREUR & COMPTEUR
       ============================================================ */
    .error-text {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        color: #ef4444;
        font-size: 0.8rem;
        margin: 0.35rem 0 0;
        line-height: 1.4;
    }
    .error-text i { font-size: 0.75rem; }

    .field-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.35rem;
        min-height: 1rem;
    }
    .field-hint {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        color: #94a3b8;
        font-size: 0.78rem;
        margin: 0;
    }
    .field-hint i { font-size: 0.72rem; }

    .char-counter {
        font-size: 0.75rem;
        color: #94a3b8;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .char-counter.is-warning { color: #f59e0b; }
    .char-counter.is-danger  { color: #ef4444; font-weight: 600; }

    /* ============================================================
       FLASH MESSAGES
       ============================================================ */
    .flash {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        padding: 1rem 1.15rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        line-height: 1.5;
        animation: flashIn 0.3s ease-out;
    }
    @keyframes flashIn {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .flash i { margin-top: 0.15rem; flex-shrink: 0; }

    .flash-success {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #047857;
    }
    .flash-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
    }

    .flash-close {
        background: none;
        border: none;
        color: inherit;
        opacity: 0.5;
        cursor: pointer;
        padding: 0.15rem 0.35rem;
        margin-left: auto;
        border-radius: 6px;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .flash-close:hover { opacity: 1; background: rgba(0,0,0,0.05); }

    /* ============================================================
       ACTIONS
       ============================================================ */
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-top: 1.75rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.85rem 1.75rem;
        border-radius: 999px;
        font-size: 0.95rem;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.25s;
        border: none;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-primary {
        background: #1e293b;
        color: white;
        box-shadow: 0 4px 14px rgba(30, 41, 59, 0.2);
    }
    .btn-primary:hover:not(:disabled) {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(102, 126, 234, 0.35);
    }
    .btn-primary:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .btn-secondary {
        background: white;
        color: #64748b;
        border: 1.5px solid #e2e8f0;
    }
    .btn-secondary:hover {
        border-color: #667eea;
        color: #667eea;
        background: #f8fafc;
    }

    /* État loading du bouton */
    .btn-loading { display: inline-flex; align-items: center; gap: 0.5rem; }
    .btn-label   { display: inline-flex; align-items: center; gap: 0.5rem; }
    .btn.is-loading .btn-label   { display: none; }
    .btn.is-loading .btn-loading { display: inline-flex; }

    .spin { animation: spin 0.9s linear infinite; }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* ============================================================
       RESPONSIVE — TABLETTE (≤ 768px)
       ============================================================ */
    @media (max-width: 768px) {
        .contact-page { padding: 2rem 0 3rem; }
        .contact-container { padding: 0 1rem; }

        .contact-header {
            padding: 1.75rem 1.5rem 1.5rem;
        }
        .contact-header-icon { width: 48px; height: 48px; font-size: 1.25rem; }
        .contact-title { font-size: 1.3rem; }
        .contact-subtitle { font-size: 0.85rem; }

        .contact-body { padding: 1.5rem 1.5rem 1.75rem; }

        .form-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }

    /* ============================================================
       RESPONSIVE — MOBILE (≤ 576px)
       ============================================================ */
    @media (max-width: 576px) {
        .contact-page { padding: 1.25rem 0 2rem; }
        .contact-container { padding: 0 0.75rem; }
        .contact-card { border-radius: 18px; }

        .contact-header {
            padding: 1.5rem 1.25rem 1.25rem;
        }
        .contact-header-icon {
            width: 44px;
            height: 44px;
            font-size: 1.15rem;
            border-radius: 12px;
        }
        .contact-title { font-size: 1.15rem; }
        .contact-subtitle { font-size: 0.8rem; }

        .contact-body { padding: 1.25rem 1.15rem 1.5rem; }

        /* Anti-zoom iOS : 16px minimum sur les inputs */
        .form-input {
            font-size: 16px;
            padding: 0.85rem 2.2rem 0.5rem 0;
        }
        .float-label { font-size: 16px; }

        /* Actions empilées, bouton principal en premier */
        .form-actions {
            flex-direction: column-reverse;
            align-items: stretch;
            gap: 0.75rem;
        }
        .btn { width: 100%; padding: 0.85rem 1.25rem; }
    }

    /* ============================================================
       RESPONSIVE — TRÈS PETIT MOBILE (≤ 400px)
       ============================================================ */
    @media (max-width: 400px) {
        .contact-body { padding: 1rem 0.9rem 1.25rem; }
        .contact-title { font-size: 1.05rem; }
        .form-input { font-size: 16px; }
    }

    /* ============================================================
       ACCESSIBILITÉ
       ============================================================ */
    @media (prefers-reduced-motion: reduce) {
        .form-input,
        .float-label,
        .field-icon,
        .line-focus,
        .btn,
        .flash { transition: none; animation: none; }
        .spin { animation-duration: 2s; }
    }

    .form-input:focus-visible,
    .btn:focus-visible,
    .flash-close:focus-visible {
        outline: 2px solid #667eea;
        outline-offset: 2px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ============================================================
       COMPTEUR DE CARACTÈRES
       ============================================================ */
    const messageField   = document.getElementById('message');
    const messageCounter = document.getElementById('message-counter');

    if (messageField && messageCounter) {
        const max = parseInt(messageField.getAttribute('maxlength'), 10) || 5000;

        const updateCounter = () => {
            const len = messageField.value.length;
            messageCounter.textContent = `${len} / ${max}`;

            messageCounter.classList.toggle('is-warning', len > max * 0.8 && len <= max * 0.95);
            messageCounter.classList.toggle('is-danger',  len > max * 0.95);
        };

        messageField.addEventListener('input', updateCounter);
        updateCounter();
    }

    /* ============================================================
       AUTO-RESIZE DU TEXTAREA
       ============================================================ */
    if (messageField) {
        const autoResize = () => {
            messageField.style.height = 'auto';
            messageField.style.height = Math.min(messageField.scrollHeight + 2, 340) + 'px';
        };
        messageField.addEventListener('input', autoResize);
    }

    /* ============================================================
       ÉTAT LOADING DU BOUTON SUBMIT
       ============================================================ */
    const form = document.querySelector('.contact-form');
    const submitBtn = document.getElementById('submit-btn');

    if (form && submitBtn) {
        form.addEventListener('submit', function (e) {
            // Validation HTML5 native
            if (!form.checkValidity()) {
                return;   // le navigateur affiche les erreurs
            }

            // Empêche le double-clic
            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
        });
    }
});
</script>
@endsection