<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Espace personnel — {{ $siteSettings->site_name ?? 'MyscoolApp' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* ============================================================
           VARIABLES
           ============================================================ */
        :root {
            --color-primary: #667eea;
            --color-secondary: #764ba2;
            --color-text: #1e293b;
            --color-muted: #94a3b8;
            --color-border: #e2e8f0;
            --color-danger: #ef4444;
            --color-danger-bg: #fef2f2;
            --color-success: #10b981;
            --color-info-bg: #eef2ff;
            --color-info-border: #c7d2fe;
            --color-info-text: #4338ca;
            --radius: 12px;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ============================================================
           RESET
           ============================================================ */
        *, *:focus, *:active {
            outline: none !important;
            box-shadow: none !important;
            -webkit-tap-highlight-color: transparent;
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px #ffffff inset !important;
            -webkit-text-fill-color: var(--color-text) !important;
            caret-color: var(--color-text);
        }

        * {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html { -webkit-text-size-adjust: 100%; }

        body {
            background: #ffffff;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 3rem 0;
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ============================================================
           DÉCORATIONS
           ============================================================ */
        .deco-circle {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .deco-1 {
            width: 600px; height: 600px;
            top: -200px; right: -150px;
            background: radial-gradient(circle, rgba(102,126,234,0.06) 0%, transparent 70%);
            animation: drift 30s ease-in-out infinite;
        }
        .deco-2 {
            width: 500px; height: 500px;
            bottom: -180px; left: -120px;
            background: radial-gradient(circle, rgba(118,75,162,0.06) 0%, transparent 70%);
            animation: drift 25s ease-in-out infinite reverse;
        }
        .deco-3 {
            width: 300px; height: 300px;
            top: 40%; left: 60%;
            background: radial-gradient(circle, rgba(102,126,234,0.04) 0%, transparent 70%);
            animation: drift 20s ease-in-out infinite 5s;
        }
        .deco-line {
            position: fixed;
            background: linear-gradient(90deg, transparent, rgba(102,126,234,0.08), transparent);
            height: 1px;
            pointer-events: none;
            z-index: 0;
        }
        .deco-line-1 { width: 40%; top: 20%; left: -5%; transform: rotate(-3deg); animation: lineFade 8s ease-in-out infinite; }
        .deco-line-2 { width: 35%; bottom: 25%; right: -5%; transform: rotate(2deg); animation: lineFade 10s ease-in-out infinite 3s; }
        .deco-dot {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .dot-1 { width: 6px; height: 6px; top: 15%; left: 10%; background: rgba(102,126,234,0.15); animation: pulse 4s ease-in-out infinite; }
        .dot-2 { width: 6px; height: 6px; top: 70%; right: 15%; background: rgba(102,126,234,0.15); animation: pulse 5s ease-in-out infinite 1s; }
        .dot-3 { width: 4px; height: 4px; top: 30%; right: 8%; background: rgba(118,75,162,0.12); animation: pulse 6s ease-in-out infinite 2s; }
        .dot-4 { width: 5px; height: 5px; bottom: 20%; left: 20%; background: rgba(118,75,162,0.1); animation: pulse 4.5s ease-in-out infinite 0.5s; }

        @keyframes drift { 0%,100% { transform: translate(0,0); } 33% { transform: translate(20px,-15px); } 66% { transform: translate(-10px,20px); } }
        @keyframes lineFade { 0%,100% { opacity: 0.3; } 50% { opacity: 1; } }
        @keyframes pulse { 0%,100% { opacity: 0.3; transform: scale(1); } 50% { opacity: 1; transform: scale(1.5); } }

        /* ============================================================
           CARTE
           ============================================================ */
        .auth-card {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 3rem 2.5rem;
            position: relative;
            z-index: 1;
            margin: 0 1rem;
            opacity: 0;
            animation: cardIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================
           LOGO
           ============================================================ */
        .logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
            opacity: 0;
            animation: fadeUp 0.6s 0.1s ease forwards;
        }
        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(102,126,234,0.2);
            overflow: hidden;
        }
        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 16px;
        }
        .logo-icon svg {
            width: 32px;
            height: 32px;
            stroke: white;
            stroke-width: 2;
            fill: none;
        }

        /* ============================================================
           TYPOGRAPHIE
           ============================================================ */
        .auth-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: 0.4rem;
            letter-spacing: -0.5px;
            text-align: center;
            opacity: 0;
            animation: fadeUp 0.6s 0.2s ease forwards;
        }
        .auth-subtitle {
            color: var(--color-muted);
            font-size: 0.9rem;
            margin-bottom: 2.5rem;
            text-align: center;
            opacity: 0;
            animation: fadeUp 0.6s 0.3s ease forwards;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================
           FORMULAIRES
           ============================================================ */
        .field {
            margin-bottom: 2rem;
            position: relative;
            opacity: 0;
            animation: fieldSlide 0.5s ease forwards;
        }
        .field:nth-of-type(1) { animation-delay: 0.35s; }
        .field:nth-of-type(2) { animation-delay: 0.45s; }
        @keyframes fieldSlide {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-wrap { position: relative; }

        .input-wrap input {
            width: 100%;
            padding: 0.8rem 4rem 0.8rem 0;
            border: none;
            border-bottom: 2px solid var(--color-border);
            background: transparent;
            font-size: 1rem;
            font-family: inherit;
            color: var(--color-text);
            font-weight: 500;
            transition: border-color 0.3s;
            -webkit-appearance: none;
            appearance: none;
        }
        .input-wrap input::placeholder { color: transparent; }
        .input-wrap input:focus { border-bottom-color: var(--color-primary); }
        .input-wrap input.is-invalid { border-bottom-color: var(--color-danger); }

        .input-wrap .float-label {
            position: absolute;
            left: 0;
            top: 0.8rem;
            color: var(--color-muted);
            font-size: 1rem;
            pointer-events: none;
            transition: all var(--transition);
        }
        .input-wrap input:focus ~ .float-label,
        .input-wrap input:not(:placeholder-shown) ~ .float-label {
            top: -0.6rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--color-primary);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .input-wrap input.is-invalid:focus ~ .float-label,
        .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label {
            color: var(--color-danger);
        }

        .input-wrap i.icon {
            position: absolute;
            right: 28px;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e1;
            font-size: 1.1rem;
            transition: all 0.3s;
            pointer-events: none;
        }
        .input-wrap input:focus ~ i.icon {
            color: var(--color-primary);
            transform: translateY(-50%) scale(1.1);
        }
        .input-wrap input.is-invalid ~ i.icon { color: var(--color-danger); }

        .input-wrap .toggle-pw {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #cbd5e1;
            cursor: pointer;
            padding: 4px;
            transition: color 0.2s;
            z-index: 2;
            font-size: 1.1rem;
        }
        .input-wrap .toggle-pw:hover { color: var(--color-primary); }

        .input-wrap .line-focus {
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(-50%);
            pointer-events: none;
        }
        .input-wrap input:focus ~ .line-focus { width: 100%; }

        /* ============================================================
           OPTIONS
           ============================================================ */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            margin-top: -0.5rem;
            margin-bottom: 0.5rem;
            opacity: 0;
            animation: fadeUp 0.5s 0.55s ease forwards;
        }
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-check-input {
            width: 16px;
            height: 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            appearance: none;
            -webkit-appearance: none;
            position: relative;
            flex-shrink: 0;
        }
        .form-check-input:checked {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }
        .form-check-input:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 10px;
            font-weight: 700;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .form-check-label {
            color: var(--color-muted);
            cursor: pointer;
            user-select: none;
        }

        /* ============================================================
           BOUTON
           ============================================================ */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: var(--color-text);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.95rem;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            margin-top: 2rem;
            transition: all var(--transition);
            opacity: 0;
            animation: fadeUp 0.5s 0.65s ease forwards;
        }
        .btn-submit:hover:not(:disabled) {
            background: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102,126,234,0.3) !important;
        }
        .btn-submit:active:not(:disabled) {
            transform: translateY(0) scale(0.98);
            box-shadow: none !important;
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .btn-submit i { transition: transform 0.3s; }
        .btn-submit:hover:not(:disabled) i { transform: translateX(4px); }

        /* ============================================================
           FOOTER
           ============================================================ */
        .footer-link {
            text-align: center;
            margin-top: 1.75rem;
            color: var(--color-muted);
            font-size: 0.85rem;
            opacity: 0;
            animation: fadeUp 0.5s 0.85s ease forwards;
        }
        .footer-link a {
            color: var(--color-primary);
            font-weight: 700;
            text-decoration: none;
        }
        .footer-link a:hover { text-decoration: underline; }

        /* ============================================================
           ✅ NOUVEAU — LIEN CROISÉ VERS L'ESPACE ABONNÉ
           ============================================================ */
        .cross-link {
            margin-top: 1.5rem;
            padding: 1rem 1.15rem;
            background: var(--color-info-bg);
            border: 1px solid var(--color-info-border);
            border-radius: var(--radius);
            font-size: 0.85rem;
            color: var(--color-info-text);
            text-align: center;
            line-height: 1.55;
            opacity: 0;
            animation: fadeUp 0.5s 0.95s ease forwards;
        }
        .cross-link i {
            display: inline-block;
            margin-right: 0.35rem;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        .cross-link strong { color: var(--color-text); }
        .cross-link a {
            color: var(--color-primary);
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        .cross-link a:hover { text-decoration: underline; }

        /* ============================================================
           ALERTES
           ============================================================ */
        .alert-err {
            background: var(--color-danger-bg);
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.83rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            border-left: 3px solid var(--color-danger);
            opacity: 0;
            animation: errShake 0.5s ease forwards;
            line-height: 1.5;
        }
        .alert-err i { margin-top: 0.15rem; flex-shrink: 0; }
        @keyframes errShake {
            0% { opacity: 0; transform: translateX(-6px); }
            50% { transform: translateX(3px); }
            100% { opacity: 1; transform: translateX(0); }
        }

        /* ============================================================
           SPINNER
           ============================================================ */
        .spinner-border {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (min-height: 800px) {
            body { align-items: center; padding: 2rem 0; }
        }

        @media (max-width: 576px) {
            body { padding: 1.5rem 0; }
            .auth-card { padding: 2rem 1.5rem; }
            .auth-title { font-size: 1.6rem; }
            .auth-subtitle { margin-bottom: 2rem; font-size: 0.85rem; }

            /* Anti-zoom iOS */
            .input-wrap input { font-size: 16px; }

            .cross-link {
                padding: 0.85rem 1rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 400px) {
            .auth-card { padding: 1.5rem 1.15rem; }
            .auth-title { font-size: 1.4rem; }
        }

        /* ============================================================
           ACCESSIBILITÉ
           ============================================================ */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            .auth-card, .logo, .auth-title, .auth-subtitle,
            .field, .form-options, .btn-submit, .footer-link, .cross-link {
                opacity: 1 !important;
                transform: none !important;
            }
        }
    </style>
</head>
<body>
    {{-- DÉCORATIONS --}}
    <div class="deco-circle deco-1" aria-hidden="true"></div>
    <div class="deco-circle deco-2" aria-hidden="true"></div>
    <div class="deco-circle deco-3" aria-hidden="true"></div>
    <div class="deco-line deco-line-1" aria-hidden="true"></div>
    <div class="deco-line deco-line-2" aria-hidden="true"></div>
    <div class="deco-dot dot-1" aria-hidden="true"></div>
    <div class="deco-dot dot-2" aria-hidden="true"></div>
    <div class="deco-dot dot-3" aria-hidden="true"></div>
    <div class="deco-dot dot-4" aria-hidden="true"></div>

    @php
        $siteName   = $siteSettings->site_name   ?? 'MyscoolApp';
        $siteLogo   = $siteSettings->site_logo   ?? null;
        $siteSlogan = $siteSettings->site_slogan ?? 'Connectez-vous à votre espace';
        $initial    = $siteName !== '' ? mb_strtoupper(mb_substr($siteName, 0, 1)) : 'A';
    @endphp

    <div class="auth-card" role="main" aria-labelledby="auth-title">
        {{-- LOGO --}}
        <div class="logo" aria-hidden="true">
            <div class="logo-icon">
                @if($siteLogo)
                    <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}">
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    </svg>
                @endif
            </div>
        </div>

        <h1 id="auth-title" class="auth-title">{{ $siteName }}</h1>
        <p class="auth-subtitle">Espace personnel — {{ $siteSlogan }}</p>

        {{-- ERREURS --}}
        @if ($errors->any())
            <div class="alert-err" role="alert" aria-live="polite">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- FORMULAIRE --}}
        <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
            @csrf

            {{-- Champ Login --}}
            <div class="field">
                <div class="input-wrap">
                    <input type="text"
                           id="login"
                           name="login"
                           value="{{ old('login') }}"
                           placeholder=" "
                           required
                           autocomplete="username"
                           inputmode="email"
                           autocapitalize="off"
                           autocorrect="off"
                           spellcheck="false"
                           maxlength="255"
                           class="@error('login') is-invalid @enderror"
                           aria-describedby="login-error">
                    <label for="login" class="float-label">Email ou téléphone</label>
                    <i class="bi bi-person icon" aria-hidden="true"></i>
                    <span class="line-focus" aria-hidden="true"></span>
                </div>
                @error('login')
                    <p id="login-error"
                       class="error-text"
                       role="alert"
                       style="color: var(--color-danger); font-size: 0.8rem; margin-top: 0.35rem;">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Mot de passe --}}
            <div class="field">
                <div class="input-wrap">
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder=" "
                           required
                           autocomplete="current-password"
                           minlength="6"
                           maxlength="255"
                           class="@error('password') is-invalid @enderror"
                           aria-describedby="password-error">
                    <label for="password" class="float-label">Mot de passe</label>

                    <i class="bi bi-lock icon" aria-hidden="true"></i>

                    <button type="button"
                            class="toggle-pw"
                            data-target="password"
                            aria-label="Afficher le mot de passe"
                            aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>

                    <span class="line-focus" aria-hidden="true"></span>
                </div>
                @error('password')
                    <p id="password-error"
                       class="error-text"
                       role="alert"
                       style="color: var(--color-danger); font-size: 0.8rem; margin-top: 0.35rem;">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Options --}}
            <div class="form-options">
                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           name="remember"
                           id="remember"
                           value="1"
                           @checked(old('remember'))>
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
            </div>

            {{-- Bouton --}}
            <button type="submit" class="btn-submit" id="btnLogin">
                <span class="btn-text">Se connecter</span>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </button>
        </form>

        {{-- ✅ NOUVEAU — Lien croisé vers l'espace abonné --}}
        <div class="cross-link" role="note">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Vous êtes <strong>parent ou abonné</strong> ?<br>
            <a href="{{ route('external.login') }}">Utilisez l'espace abonné →</a>
        </div>

        {{-- Lien retour à l'accueil --}}
        <div class="footer-link">
            <a href="{{ route('home') }}">← Retour à l'accueil</a>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        /* ============================================================
           TOGGLE MOT DE PASSE
           ============================================================ */
        document.querySelectorAll('.toggle-pw').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const input = document.getElementById(this.dataset.target);
                if (!input) return;

                const icon = this.querySelector('i');
                const isPassword = input.type === 'password';

                input.type = isPassword ? 'text' : 'password';
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';

                this.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                this.setAttribute('aria-pressed', String(isPassword));
            });
        });

        /* ============================================================
           SOUMISSION — ÉTAT "CHARGEMENT"
           ============================================================ */
        const form = document.getElementById('loginForm');
        const btn = document.getElementById('btnLogin');

        if (form && btn) {
            form.addEventListener('submit', function(e) {
                // Validation HTML5 native
                if (!form.checkValidity()) {
                    e.preventDefault();
                    form.reportValidity();
                    return;
                }

                // Évite les double-clics
                if (btn.disabled) {
                    e.preventDefault();
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border" aria-hidden="true"></span><span class="btn-text">Connexion…</span>';
            });
        }

        /* ============================================================
           FOCUS AUTOMATIQUE SUR LE PREMIER CHAMP EN ERREUR
           ============================================================ */
        document.addEventListener('DOMContentLoaded', function() {
            const firstError = document.querySelector('input.is-invalid');
            if (firstError) {
                firstError.focus();
            }
        });
    })();
    </script>
</body>
</html>