@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="max-w-2xl mx-auto">
    {{-- En-tête --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Nouvelle <strong>session d'avance</strong></h1>
            <p class="form-subtitle">Ouvrez une période de demandes d'avance sur salaire</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('admin.session-avances.store') }}" method="POST" class="form-container" novalidate>
        @csrf

        <div class="form-grid">
            {{-- Libellé --}}
            <div class="form-field">
                <div class="input-wrap">
                    <input type="text" name="libelle" id="libelle" value="{{ old('libelle') }}" placeholder=" " required
                           class="@error('libelle') is-invalid @enderror">
                    <label for="libelle" class="float-label">Libellé de la session <span style="color:#ef4444;">*</span></label>
                    <i class="bi bi-calendar-check icon"></i>
                    <div class="line-focus"></div>
                </div>
                <p class="hint-text">Ex : Session Janvier 2026, Session Trimestre 1, etc.</p>
                @error('libelle')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Dates (grille 2 colonnes) --}}
            <div class="form-row">
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="date" name="date_debut" id="date_debut"
                               value="{{ old('date_debut', now()->toDateString()) }}" placeholder=" " required
                               class="@error('date_debut') is-invalid @enderror">
                        <label for="date_debut" class="float-label date-label-filled">Date de début <span style="color:#ef4444;">*</span></label>
                        <i class="bi bi-calendar-event icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('date_debut')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <div class="input-wrap">
                        <input type="date" name="date_fin" id="date_fin"
                               value="{{ old('date_fin') }}" placeholder=" " required
                               class="@error('date_fin') is-invalid @enderror">
                        <label for="date_fin" class="float-label date-label-filled">Date de fin <span style="color:#ef4444;">*</span></label>
                        <i class="bi bi-calendar-x icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('date_fin')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Info --}}
            <div class="info-card">
                <i class="bi bi-info-circle-fill info-icon"></i>
                <div class="info-content">
                    <strong>Information :</strong> Une seule session peut être ouverte à la fois.
                    Les membres pourront faire leur demande pendant la période définie.
                </div>
            </div>
        </div>

        {{-- Boutons --}}
        <div class="button-group">
            <a href="{{ route('admin.session-avances.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-submit">
                Créer la session <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </form>
</div>

<style>
    /* ============================================================
       ANIMATIONS
       ============================================================ */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fieldSlide {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ============================================================
       EN-TÊTE
       ============================================================ */
    .header-container {
        margin-bottom: 2rem;
        animation: fadeUp 0.6s 0.1s ease forwards;
        opacity: 0;
    }
    .form-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
    }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: #94a3b8; font-size: 0.9rem; }

    /* ============================================================
       ALERTE ERREUR
       ============================================================ */
    .alert-error {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 14px;
        color: #991b1b;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
        animation: fadeUp 0.5s ease forwards;
    }
    .alert-error i { font-size: 1.1rem; }

    /* ============================================================
       FORMULAIRE
       ============================================================ */
    .form-container {
        background: white;
        padding: 2.5rem;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
        opacity: 0;
    }

    .form-grid {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .form-field {
        position: relative;
        animation: fieldSlide 0.5s ease forwards;
        opacity: 0;
    }
    .form-field:nth-of-type(1) { animation-delay: 0.35s; }
    .form-row .form-field:nth-child(1) { animation-delay: 0.45s; }
    .form-row .form-field:nth-child(2) { animation-delay: 0.55s; }

    /* Input flottant */
    .input-wrap { position: relative; }
    .input-wrap input {
        width: 100%;
        padding: 0.8rem 2.5rem 0.8rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none !important;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    .input-wrap input::placeholder { color: transparent; }
    .input-wrap input:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid { border-bottom-color: #ef4444; }

    .input-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.8rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label {
        color: #ef4444;
    }

    /* Cas particulier pour les inputs date (toujours remplis) */
    .input-wrap input[type="date"] ~ .float-label,
    .input-wrap input[type="date"]:focus ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap input[type="date"].is-invalid ~ .float-label {
        color: #ef4444;
    }

    .input-wrap i.icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.1rem;
        transition: all 0.3s;
        pointer-events: none;
    }
    .input-wrap input:focus ~ i.icon {
        color: #667eea;
        transform: translateY(-50%) scale(1.1);
    }
    .input-wrap input.is-invalid ~ i.icon { color: #ef4444; }

    .input-wrap .line-focus {
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
    .input-wrap input:focus ~ .line-focus { width: 100%; }

    /* ============================================================
       INFO CARD
       ============================================================ */
    .info-card {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        animation: fieldSlide 0.5s 0.6s ease forwards;
        opacity: 0;
    }
    .info-icon {
        color: #2563eb;
        font-size: 1.15rem;
        flex-shrink: 0;
        margin-top: 0.1rem;
    }
    .info-content {
        font-size: 0.85rem;
        color: #1e40af;
        line-height: 1.5;
    }
    .info-content strong { color: #1e3a8a; }

    /* ============================================================
       TEXTE UTILITAIRE
       ============================================================ */
    .hint-text {
        color: #94a3b8;
        font-size: 0.75rem;
        margin-top: 0.3rem;
    }
    .error-text {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.3rem;
    }

    /* ============================================================
       BOUTONS
       ============================================================ */
    .button-group {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f1f5f9;
        margin-top: 1.5rem;
    }

    .btn-submit {
        padding: 0.8rem 2rem;
        background: #1e293b;
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        outline: none !important;
        animation: fadeUp 0.5s 0.85s ease forwards;
        opacity: 0;
    }
    .btn-submit:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    .btn-submit:active {
        transform: translateY(0) scale(0.98);
        box-shadow: none;
    }
    .btn-submit i {
        transition: transform 0.3s;
    }
    .btn-submit:hover i {
        transform: translateX(4px);
    }

    .btn-cancel {
        padding: 0.8rem 1.5rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        background: white;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        animation: fadeUp 0.5s 0.75s ease forwards;
        opacity: 0;
    }
    .btn-cancel:hover {
        border-color: #667eea;
        color: #667eea;
        background: #f8fafc;
    }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 768px) {
        .form-container { padding: 1.5rem; }
        .form-row { grid-template-columns: 1fr; }
        .button-group { flex-direction: column-reverse; }
        .btn-submit, .btn-cancel { width: 100%; }
    }
</style>
@endsection