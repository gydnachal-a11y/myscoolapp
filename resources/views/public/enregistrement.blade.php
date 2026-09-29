<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Enregistrement élève – {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* ============================================================
           BASE
           ============================================================ */
        * { box-sizing: border-box; }

        html {
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            padding: 2rem 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .enregistrement-wrapper {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* ============================================================
           FLASH MESSAGES
           ============================================================ */
        .flash {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .flash i { margin-top: 0.15rem; flex-shrink: 0; }
        .flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
        .flash-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

        /* ============================================================
           HEADER
           ============================================================ */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
            animation: fadeUp 0.6s 0.1s ease forwards;
            opacity: 0;
        }
        .form-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 0.25rem;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        .form-title strong { font-weight: 800; color: #4f46e5; }
        .form-subtitle {
            color: #64748b;
            font-size: 0.92rem;
            margin: 0;
            line-height: 1.5;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #667eea;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
            padding: 0.5rem 0.9rem;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: white;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .back-link:hover {
            color: #4f46e5;
            border-color: #667eea;
            background: #f8fafc;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================
           STEPPER
           ============================================================ */
        .stepper {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f1f5f9;
            margin-bottom: 1.5rem;
            animation: fadeUp 0.6s 0.2s ease forwards;
            opacity: 0;
        }
        .stepper-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .step-item {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 0;
        }
        .step-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            background: #f1f5f9;
            color: #94a3b8;
            transition: all 0.3s;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
        }
        .step-circle.active {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2);
        }
        .step-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .step-line {
            flex: 1;
            height: 2px;
            background: #e2e8f0;
            margin: 0 8px;
            transition: background 0.3s;
            min-width: 20px;
        }
        .step-line.active { background: #4f46e5; }

        /* ============================================================
           FORM CONTAINER
           ============================================================ */
        .form-container {
            background: white;
            padding: 2.5rem 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
            opacity: 0;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-section { margin-bottom: 1.75rem; }
        .section-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .section-title i { color: #667eea; }

        /* ============================================================
           FORM ROWS
           ============================================================ */
        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            align-items: start;
        }
        .form-row:last-child { margin-bottom: 0; }

        .form-field {
            position: relative;
            animation: fieldSlide 0.5s ease forwards;
            opacity: 0;
            min-width: 0;
        }
        .form-row .form-field:nth-child(1) { animation-delay: 0.35s; }
        .form-row .form-field:nth-child(2) { animation-delay: 0.45s; }
        .form-row .form-field:nth-child(3) { animation-delay: 0.55s; }

        @keyframes fieldSlide {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .field-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        /* ============================================================
           INPUTS — FLOAT LABEL
           ============================================================ */
        .input-wrap,
        .textarea-wrap,
        .select-wrap { position: relative; }

        .input-wrap input,
        .textarea-wrap textarea,
        .select-wrap select {
            width: 100%;
            padding: 0.9rem 2.8rem 0.5rem 0;
            border: none;
            border-bottom: 2px solid #e2e8f0;
            background: transparent;
            font-size: 1rem;
            font-family: inherit;
            color: #1e293b;
            font-weight: 500;
            transition: border-color 0.3s;
            outline: none;
            -webkit-appearance: none;
            appearance: none;
            border-radius: 0;
        }
        .input-wrap input::placeholder,
        .textarea-wrap textarea::placeholder { color: transparent; }

        .input-wrap input:focus,
        .textarea-wrap textarea:focus,
        .select-wrap select:focus { border-bottom-color: #667eea; }

        .input-wrap input.is-invalid,
        .textarea-wrap textarea.is-invalid,
        .select-wrap select.is-invalid { border-bottom-color: #ef4444; }

        .input-wrap .float-label,
        .textarea-wrap .float-label {
            position: absolute;
            left: 0;
            top: 0.85rem;
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            white-space: nowrap;
            max-width: calc(100% - 2.5rem);
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .input-wrap input:focus ~ .float-label,
        .input-wrap input:not(:placeholder-shown) ~ .float-label,
        .textarea-wrap textarea:focus ~ .float-label,
        .textarea-wrap textarea:not(:placeholder-shown) ~ .float-label {
            top: -0.55rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .input-wrap input.is-invalid:focus ~ .float-label,
        .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label,
        .textarea-wrap textarea.is-invalid:focus ~ .float-label,
        .textarea-wrap textarea.is-invalid:not(:placeholder-shown) ~ .float-label {
            color: #ef4444;
        }

        .input-wrap i.icon,
        .textarea-wrap i.icon {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e1;
            font-size: 1.1rem;
            transition: all 0.3s;
            pointer-events: none;
        }
        .textarea-wrap i.icon { top: 0.9rem; transform: none; }

        .input-wrap input:focus ~ i.icon,
        .textarea-wrap textarea:focus ~ i.icon {
            color: #667eea;
            transform: translateY(-50%) scale(1.1);
        }
        .textarea-wrap textarea:focus ~ i.icon { transform: scale(1.1); }

        .input-wrap input.is-invalid ~ i.icon,
        .textarea-wrap textarea.is-invalid ~ i.icon { color: #ef4444; }

        .select-wrap .select-icon {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e1;
            font-size: 1.1rem;
            pointer-events: none;
            transition: color 0.3s;
        }
        .select-wrap select:focus ~ .select-icon { color: #667eea; }

        .input-wrap .line-focus,
        .textarea-wrap .line-focus,
        .select-wrap .line-focus {
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
            transform: translateX(-50%);
            pointer-events: none;
        }
        .input-wrap input:focus ~ .line-focus,
        .textarea-wrap textarea:focus ~ .line-focus,
        .select-wrap select:focus ~ .line-focus { width: 100%; }

        .textarea-wrap textarea {
            resize: vertical;
            min-height: 60px;
            line-height: 1.5;
        }

        /* ============================================================
           FILE UPLOAD
           ============================================================ */
        .file-upload-wrap {
            position: relative;
            margin-top: 0.5rem;
            display: block;
        }
        .file-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        .file-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.9rem 1.25rem;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            color: #475569;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            text-align: center;
            width: 100%;
        }
        .file-input:hover + .file-label,
        .file-input:focus + .file-label {
            border-color: #667eea;
            color: #667eea;
            background: #f1f5f9;
        }
        .file-name {
            font-size: 0.78rem;
            color: #667eea;
            margin-top: 0.4rem;
            font-weight: 500;
            word-break: break-all;
        }

        /* ============================================================
           RESPONSABLE CARD
           ============================================================ */
        .responsable-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            animation: fieldSlide 0.4s ease forwards;
            min-width: 0;
        }
        .responsable-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .responsable-select {
            position: relative;
            flex: 1 1 180px;
            min-width: 0;
        }
        .responsable-type-select {
            width: 100%;
            padding: 0.6rem 2rem 0.6rem 0.85rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: white;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1e293b;
            appearance: none;
            outline: none;
            cursor: pointer;
            font-weight: 500;
        }
        .responsable-select .select-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #94a3b8;
            font-size: 1rem;
        }
        .vivant-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
            color: #475569;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .form-check-input {
            width: 18px;
            height: 18px;
            border: 1.5px solid #cbd5e1;
            border-radius: 4px;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            position: relative;
            flex-shrink: 0;
            transition: all 0.2s;
            background: white;
        }
        .form-check-input:checked {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        .form-check-input:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 11px;
            font-weight: 700;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .btn-remove-responsable {
            background: #fee2e2;
            border: none;
            color: #dc2626;
            cursor: pointer;
            font-size: 0.95rem;
            padding: 0.5rem 0.7rem;
            border-radius: 8px;
            transition: all 0.2s;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            min-height: 36px;
        }
        .btn-remove-responsable:hover {
            background: #dc2626;
            color: white;
            transform: scale(1.05);
        }
        .btn-remove-responsable:active { transform: scale(0.95); }

        .btn-add-responsable {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 0.5rem;
            padding: 0.7rem 1.25rem;
            background: #eef2ff;
            border: 1.5px dashed #c7d2fe;
            border-radius: 10px;
            color: #4f46e5;
            font-weight: 600;
            font-size: 0.9rem;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s;
            width: 100%;
        }
        .btn-add-responsable:hover {
            background: #e0e7ff;
            border-color: #818cf8;
        }
        .btn-add-responsable:active { transform: scale(0.98); }

        /* ============================================================
           ERRORS
           ============================================================ */
        .error-text {
            color: #ef4444;
            font-size: 0.78rem;
            margin: 0.35rem 0 0;
            line-height: 1.4;
        }

        /* ============================================================
           BUTTONS
           ============================================================ */
        .btn-submit,
        .btn-next,
        .btn-prev,
        .btn-cancel {
            padding: 0.8rem 1.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.92rem;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            outline: none;
            border: none;
            white-space: nowrap;
            text-decoration: none;
        }

        .btn-submit,
        .btn-next {
            background: #1e293b;
            color: white;
        }
        .btn-submit:hover:not(:disabled),
        .btn-next:hover {
            background: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102,126,234,0.3);
        }
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-prev {
            background: #f1f5f9;
            color: #475569;
        }
        .btn-prev:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: white;
            border: 1.5px solid #e2e8f0;
            color: #64748b;
        }
        .btn-cancel:hover {
            border-color: #667eea;
            color: #667eea;
            background: #f8fafc;
        }

        /* ============================================================
           STEP ACTIONS
           ============================================================ */
        .step-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .step-actions-right {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* ============================================================
           UTILITAIRES
           ============================================================ */
        .opacity-50 { opacity: 0.5; }
        .pointer-events-none { pointer-events: none; }
        .text-red-500 { color: #ef4444; }

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
           RESPONSIVE — TABLETTE (≥ 640px)
           ============================================================ */
        @media (min-width: 640px) {
            .form-row { grid-template-columns: repeat(2, 1fr); }
            .form-row.three-col { grid-template-columns: repeat(2, 1fr); }
            .form-row.single-col { grid-template-columns: 1fr; }
            .responsable-header {
                flex-wrap: nowrap;
            }
            .btn-add-responsable { width: auto; }
        }

        /* ============================================================
           RESPONSIVE — DESKTOP (≥ 768px)
           ============================================================ */
        @media (min-width: 768px) {
            .form-row.three-col { grid-template-columns: repeat(3, 1fr); }
        }

        /* ============================================================
           RESPONSIVE — TABLETTE PORTRAIT (≤ 992px)
           ============================================================ */
        @media (max-width: 992px) {
            body { padding: 1.5rem 0; }
            .form-container { padding: 2rem 1.75rem; border-radius: 20px; }
            .enregistrement-wrapper { padding: 0 0.85rem; }
        }

        /* ============================================================
           RESPONSIVE — MOBILE (≤ 768px)
           ============================================================ */
        @media (max-width: 768px) {
            body { padding: 1rem 0; }

            .enregistrement-wrapper { padding: 0 0.75rem; }

            /* Header empilé */
            .header-container {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
                margin-bottom: 1.25rem;
            }
            .form-title { font-size: 1.4rem; }
            .form-subtitle { font-size: 0.85rem; }
            .back-link {
                width: 100%;
                justify-content: center;
            }

            /* Stepper compact */
            .stepper {
                padding: 0.85rem 1rem;
                border-radius: 14px;
                margin-bottom: 1rem;
            }
            .step-circle {
                width: 28px;
                height: 28px;
                font-size: 0.72rem;
            }
            .step-label { font-size: 0.75rem; }
            .step-line { margin: 0 5px; min-width: 14px; }

            /* Container */
            .form-container {
                padding: 1.5rem 1.15rem;
                border-radius: 18px;
            }
            .form-section { margin-bottom: 1.5rem; }
            .section-title { font-size: 0.95rem; margin-bottom: 1.25rem; }

            /* Anti-zoom iOS */
            .input-wrap input,
            .textarea-wrap textarea,
            .select-wrap select,
            .responsable-type-select {
                font-size: 16px;
            }

            /* Actions empilées */
            .step-actions {
                flex-direction: column-reverse;
                align-items: stretch;
                gap: 0.6rem;
                margin-top: 1.5rem;
            }
            .step-actions .btn-cancel,
            .step-actions .btn-submit,
            .step-actions .btn-next,
            .step-actions .btn-prev {
                width: 100%;
            }
            .step-actions-right {
                flex-direction: column-reverse;
                width: 100%;
                gap: 0.6rem;
            }
            .step-actions-right .btn-prev,
            .step-actions-right .btn-next,
            .step-actions-right .btn-submit {
                width: 100%;
            }

            /* Responsable card */
            .responsable-card {
                padding: 1rem;
                border-radius: 14px;
            }
            .responsable-header {
                flex-direction: column;
                align-items: stretch;
                gap: 0.6rem;
            }
            .responsable-select { flex: 1 1 auto; }
            .vivant-checkbox {
                align-self: flex-start;
                padding: 0.4rem 0;
            }
            .btn-remove-responsable {
                align-self: flex-end;
                width: 100%;
                padding: 0.6rem;
                justify-content: center;
            }
            .btn-remove-responsable:hover { transform: none; }
        }

        /* ============================================================
           RESPONSIVE — PETIT MOBILE (≤ 576px)
           ============================================================ */
        @media (max-width: 576px) {
            .form-container { padding: 1.25rem 0.95rem; }
            .form-title { font-size: 1.25rem; }
            .form-subtitle { font-size: 0.8rem; }

            .form-row { gap: 1.15rem; margin-bottom: 1.15rem; }
            .step-info { gap: 0.35rem; }
            .step-circle { width: 26px; height: 26px; font-size: 0.68rem; }
            .step-label { font-size: 0.7rem; }
        }

        /* ============================================================
           RESPONSIVE — TRÈS PETIT MOBILE (≤ 400px)
           ============================================================ */
        @media (max-width: 400px) {
            .enregistrement-wrapper { padding: 0 0.6rem; }
            .form-container { padding: 1.1rem 0.85rem; border-radius: 16px; }
            .form-title { font-size: 1.15rem; }
            .form-subtitle { font-size: 0.78rem; }
            .section-title { font-size: 0.9rem; }
            .field-label { font-size: 0.82rem; }

            .step-circle { width: 24px; height: 24px; font-size: 0.65rem; }
            .step-line { margin: 0 3px; min-width: 8px; }

            .btn-submit,
            .btn-next,
            .btn-prev,
            .btn-cancel {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
            }

            .responsable-card { padding: 0.85rem; }
        }

        /* ============================================================
           ACCESSIBILITÉ
           ============================================================ */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
            .header-container,
            .stepper,
            .form-container,
            .form-field,
            .responsable-card {
                opacity: 1 !important;
                transform: none !important;
            }
        }

        :focus-visible {
            outline: 2px solid #667eea;
            outline-offset: 2px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="enregistrement-wrapper" x-data="enregistrementForm()" x-init="init()">

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="flash flash-success" role="alert">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="flash flash-error" role="alert">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Header --}}
        <div class="header-container">
            <div>
                <h1 class="form-title">Enregistrement <strong>élève</strong></h1>
                <p class="form-subtitle">
                    Complétez les informations. L'inscription définitive se fera à l'établissement.
                </p>
            </div>
            <a href="{{ route('home') }}" class="back-link">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour
            </a>
        </div>

        {{-- Stepper --}}
        <div class="stepper">
            <div class="stepper-inner">
                @foreach(['Identité', 'Santé', 'Responsables'] as $label)
                    <div class="step-item">
                        <div class="step-info">
                            <div class="step-circle"
                                 :class="step >= {{ $loop->index + 1 }} ? 'active' : ''">
                                <span x-show="step > {{ $loop->index + 1 }}"
                                      class="text-white"
                                      aria-hidden="true">
                                    <i class="bi bi-check-lg"></i>
                                </span>
                                <span x-show="step <= {{ $loop->index + 1 }}">{{ $loop->index + 1 }}</span>
                            </div>
                            <span class="step-label">{{ $label }}</span>
                        </div>
                        @if(!$loop->last)
                            <div class="step-line"
                                 :class="{ 'active': step > {{ $loop->index + 1 }} }"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('public.enregistrement.store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="form-container"
              novalidate
              id="enregistrementForm">
            @csrf

            {{-- ═══════════════════════════════════════════════════
                 ÉTAPE 1 — IDENTITÉ
                 ═══════════════════════════════════════════════════ --}}
            <div x-show="step === 1" x-transition:enter.duration.300ms>
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="bi bi-person" aria-hidden="true"></i> Identité
                    </h2>

                    <div class="form-row three-col">
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="text" name="nom" id="nom"
                                       value="{{ old('nom') }}"
                                       placeholder=" " required maxlength="255"
                                       autocomplete="family-name"
                                       class="@error('nom') is-invalid @enderror">
                                <label for="nom" class="float-label">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <i class="bi bi-person icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('nom')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="text" name="postnom" id="postnom"
                                       value="{{ old('postnom') }}"
                                       placeholder=" " maxlength="255"
                                       autocomplete="additional-name"
                                       class="@error('postnom') is-invalid @enderror">
                                <label for="postnom" class="float-label">Post-nom</label>
                                <i class="bi bi-person icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('postnom')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="text" name="prenom" id="prenom"
                                       value="{{ old('prenom') }}"
                                       placeholder=" " required maxlength="255"
                                       autocomplete="given-name"
                                       class="@error('prenom') is-invalid @enderror">
                                <label for="prenom" class="float-label">
                                    Prénom <span class="text-red-500">*</span>
                                </label>
                                <i class="bi bi-person icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('prenom')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <label for="sexe" class="field-label">
                                Sexe <span class="text-red-500">*</span>
                            </label>
                            <div class="select-wrap">
                                <select name="sexe" id="sexe" required
                                        class="@error('sexe') is-invalid @enderror">
                                    <option value="">Choisir...</option>
                                    <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                                    <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                                </select>
                                <i class="bi bi-chevron-down select-icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('sexe')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="date" name="date_naissance" id="date_naissance"
                                       value="{{ old('date_naissance') }}"
                                       placeholder=" " required
                                       max="{{ now()->format('Y-m-d') }}"
                                       autocomplete="bday"
                                       class="@error('date_naissance') is-invalid @enderror">
                                <label for="date_naissance" class="float-label">
                                    Date de naissance <span class="text-red-500">*</span>
                                </label>
                                <i class="bi bi-calendar icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('date_naissance')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="text" name="lieu_naissance" id="lieu_naissance"
                                       value="{{ old('lieu_naissance') }}"
                                       placeholder=" " required maxlength="255"
                                       class="@error('lieu_naissance') is-invalid @enderror">
                                <label for="lieu_naissance" class="float-label">
                                    Lieu de naissance <span class="text-red-500">*</span>
                                </label>
                                <i class="bi bi-geo-alt icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('lieu_naissance')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="textarea-wrap">
                                <textarea name="adresse" id="adresse" rows="1"
                                          placeholder=" " required maxlength="500"
                                          class="@error('adresse') is-invalid @enderror">{{ old('adresse') }}</textarea>
                                <label for="adresse" class="float-label">
                                    Adresse <span class="text-red-500">*</span>
                                </label>
                                <i class="bi bi-house icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('adresse')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="step-actions">
                    <a href="{{ route('home') }}" class="btn-cancel">Annuler</a>
                    <button type="button" class="btn-next" @click="step = 2">
                        Suivant <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════
                 ÉTAPE 2 — SANTÉ ET PHOTO
                 ═══════════════════════════════════════════════════ --}}
            <div x-show="step === 2" x-transition:enter.duration.300ms>
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="bi bi-file-image" aria-hidden="true"></i> Photo et santé
                    </h2>

                    <div class="form-row single-col">
                        <div class="form-field">
                            <label for="photo" class="field-label">Photo</label>
                            <div class="file-upload-wrap">
                                <input type="file" name="photo" id="photo"
                                       accept="image/jpeg,image/png,image/jpg,image/webp"
                                       class="file-input">
                                <label for="photo" class="file-label">
                                    <i class="bi bi-cloud-upload" aria-hidden="true"></i>
                                    <span id="photo-label-text">Choisir une image</span>
                                </label>
                            </div>
                            <p class="file-name" id="photo-name"></p>
                            @error('photo')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <div class="textarea-wrap">
                                <textarea name="maladie_chronique" id="maladie_chronique"
                                          rows="2" placeholder=" " maxlength="500"
                                          class="@error('maladie_chronique') is-invalid @enderror">{{ old('maladie_chronique') }}</textarea>
                                <label for="maladie_chronique" class="float-label">Maladie chronique</label>
                                <i class="bi bi-heart-pulse icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('maladie_chronique')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="textarea-wrap">
                                <textarea name="allergies" id="allergies"
                                          rows="2" placeholder=" " maxlength="500"
                                          class="@error('allergies') is-invalid @enderror">{{ old('allergies') }}</textarea>
                                <label for="allergies" class="float-label">Allergies</label>
                                <i class="bi bi-exclamation-triangle icon" aria-hidden="true"></i>
                                <span class="line-focus" aria-hidden="true"></span>
                            </div>
                            @error('allergies')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="step-actions">
                    <a href="{{ route('home') }}" class="btn-cancel">Annuler</a>
                    <div class="step-actions-right">
                        <button type="button" class="btn-prev" @click="step = 1">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i> Précédent
                        </button>
                        <button type="button" class="btn-next" @click="step = 3">
                            Suivant <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════
                 ÉTAPE 3 — RESPONSABLES
                 ═══════════════════════════════════════════════════ --}}
            <div x-show="step === 3" x-transition:enter.duration.300ms>
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="bi bi-people" aria-hidden="true"></i> Responsables
                    </h2>

                    <template x-for="(responsable, index) in responsables" :key="index">
                        <div class="responsable-card">
                            <div class="responsable-header">
                                <div class="responsable-select">
                                    <select :name="'responsables['+index+'][type]'"
                                            required
                                            class="responsable-type-select"
                                            x-model="responsable.type"
                                            :aria-label="'Type de responsable ' + (index + 1)">
                                        <option value="pere">Père</option>
                                        <option value="mere">Mère</option>
                                        <option value="tuteur">Tuteur</option>
                                    </select>
                                    <i class="bi bi-chevron-down select-icon" aria-hidden="true"></i>
                                </div>

                                <label class="vivant-checkbox">
                                    <input type="checkbox"
                                           :name="'responsables['+index+'][vivant]'"
                                           value="1"
                                           x-model="responsable.vivant"
                                           class="form-check-input">
                                    <span>Vivant</span>
                                </label>

                                <button type="button"
                                        @click="removeResponsable(index)"
                                        class="btn-remove-responsable"
                                        :aria-label="'Supprimer le responsable ' + (index + 1)"
                                        title="Supprimer ce responsable">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="form-row three-col"
                                 :class="{ 'opacity-50 pointer-events-none': !responsable.vivant }">
                                <div class="form-field">
                                    <div class="input-wrap">
                                        <input type="text"
                                               :name="'responsables['+index+'][nom]'"
                                               placeholder=" " required maxlength="255"
                                               x-model="responsable.nom">
                                        <label class="float-label">
                                            Nom <span class="text-red-500">*</span>
                                        </label>
                                        <i class="bi bi-person icon" aria-hidden="true"></i>
                                        <span class="line-focus" aria-hidden="true"></span>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <div class="input-wrap">
                                        <input type="text"
                                               :name="'responsables['+index+'][profession]'"
                                               placeholder=" " maxlength="255"
                                               x-model="responsable.profession">
                                        <label class="float-label">Profession</label>
                                        <i class="bi bi-briefcase icon" aria-hidden="true"></i>
                                        <span class="line-focus" aria-hidden="true"></span>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <div class="input-wrap">
                                        <input type="tel"
                                               :name="'responsables['+index+'][telephone]'"
                                               placeholder=" " required maxlength="20"
                                               inputmode="tel"
                                               x-model="responsable.telephone">
                                        <label class="float-label">
                                            Téléphone <span class="text-red-500">*</span>
                                        </label>
                                        <i class="bi bi-phone icon" aria-hidden="true"></i>
                                        <span class="line-focus" aria-hidden="true"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <button type="button"
                            @click="addResponsable"
                            class="btn-add-responsable">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Ajouter un responsable
                    </button>

                    @error('responsables')<p class="error-text">{{ $message }}</p>@enderror
                    @error('responsables.*')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="step-actions">
                    <a href="{{ route('home') }}" class="btn-cancel">Annuler</a>
                    <div class="step-actions-right">
                        <button type="button" class="btn-prev" @click="step = 2">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i> Précédent
                        </button>
                        <button type="submit" class="btn-submit" id="submitBtn">
                            Envoyer l'enregistrement <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('enregistrementForm', () => ({
            step: 1,
            responsables: [],

            init() {
                this.responsables = [
                    { type: 'pere', vivant: true, nom: '', profession: '', telephone: '' },
                    { type: 'mere', vivant: true, nom: '', profession: '', telephone: '' }
                ];
            },

            addResponsable() {
                this.responsables.push({
                    type: 'tuteur',
                    vivant: true,
                    nom: '',
                    profession: '',
                    telephone: ''
                });
            },

            removeResponsable(index) {
                if (this.responsables.length > 1) {
                    this.responsables.splice(index, 1);
                } else {
                    // Message discret au lieu d'alert()
                    const msg = document.createElement('div');
                    msg.className = 'flash flash-error';
                    msg.style.marginTop = '1rem';
                    msg.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> Vous devez avoir au moins un responsable.';
                    this.$el.prepend(msg);
                    setTimeout(() => msg.remove(), 3000);
                }
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('enregistrementForm');
        const btn = document.getElementById('submitBtn');

        /* ============================================================
           APERÇU DU FICHIER PHOTO
           ============================================================ */
        const photoInput = document.getElementById('photo');
        const photoName = document.getElementById('photo-name');
        const photoLabelText = document.getElementById('photo-label-text');

        if (photoInput) {
            photoInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) {
                    if (photoName) photoName.textContent = '';
                    if (photoLabelText) photoLabelText.textContent = 'Choisir une image';
                    return;
                }
                const sizeKb = (file.size / 1024).toFixed(0);
                const displayName = file.name.length > 40
                    ? file.name.substring(0, 37) + '...'
                    : file.name;

                if (photoName) photoName.textContent = `${displayName} — ${sizeKb} Ko`;
                if (photoLabelText) photoLabelText.textContent = 'Changer l\'image';
            });
        }

        /* ============================================================
           SOUMISSION — ÉTAT CHARGEMENT
           ============================================================ */
        if (form && btn) {
            form.addEventListener('submit', function () {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border" aria-hidden="true"></span> Envoi...';
            });
        }
    });
    </script>
</body>
</html>