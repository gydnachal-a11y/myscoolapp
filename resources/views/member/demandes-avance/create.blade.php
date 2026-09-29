@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="max-w-3xl mx-auto"
     x-data="{
        montant: {{ old('montant_demande_usd', 0) }},
        taux: {{ $tauxChange }},
        salaire: {{ $salaireMensuel }},
        dette: {{ $detteTotale }},
        limite: {{ $limiteEmprunt }},
        maxDispo: {{ $montantMaxDisponible }},
        get montantFC() { return Math.round((this.montant || 0) * this.taux); },
        get detteApres() { return this.dette + (this.montant || 0); },
        get resteApres() { return Math.max(0, this.salaire - this.dette - (this.montant || 0)); },
        get depasse() { return (this.montant || 0) > this.maxDispo; },
        get valide() { return this.montant >= 1 && !this.depasse; }
     }">

    {{-- En-tête --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Nouvelle <strong>demande d'avance</strong></h1>
            <p class="form-subtitle">
                Session : <strong>{{ $session->libelle }}</strong> —
                se termine le {{ $session->date_fin->translatedFormat('d M Y') }}
            </p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Synthèse financière --}}
    <div class="financial-summary">
        <div class="summary-card">
            <div class="summary-icon icon-salaire">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="summary-body">
                <div class="summary-label">Salaire</div>
                <div class="summary-value">{{ number_format($salaireMensuel, 0, ',', ' ') }} $</div>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon icon-dette">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="summary-body">
                <div class="summary-label">Dette actuelle</div>
                <div class="summary-value value-danger">{{ number_format($detteTotale, 0, ',', ' ') }} $</div>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon icon-limite">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="summary-body">
                <div class="summary-label">Limite</div>
                <div class="summary-value">{{ number_format($limiteEmprunt, 0, ',', ' ') }} $</div>
            </div>
        </div>

        <div class="summary-card summary-card-primary">
            <div class="summary-icon icon-dispo">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="summary-body">
                <div class="summary-label">Max disponible</div>
                <div class="summary-value">{{ number_format($montantMaxDisponible, 0, ',', ' ') }} $</div>
            </div>
        </div>
    </div>

    {{-- Formulaire --}}
    <form action="{{ route('member.demandes-avance.store') }}" method="POST"
          class="form-container" novalidate>
        @csrf

        <div class="form-grid">

            {{-- Montant --}}
            <div class="form-field">
                <label for="montant_demande_usd" class="field-label">
                    Montant demandé (USD) <span style="color:#ef4444;">*</span>
                </label>

                <div class="input-wrap amount-wrap"
                     :class="{ 'is-over': depasse }">
                    <input type="number"
                           name="montant_demande_usd"
                           id="montant_demande_usd"
                           x-model.number="montant"
                           min="1"
                           max="{{ $montantMaxDisponible }}"
                           step="1"
                           placeholder=" "
                           required
                           class="@error('montant_demande_usd') is-invalid @enderror">
                    <span class="amount-suffix">USD</span>
                    <div class="line-focus"></div>
                </div>

                @error('montant_demande_usd')
                    <p class="error-text">{{ $message }}</p>
                @enderror

                <div class="amount-hint">
                    <span class="hint-left">
                        Équivalent : <strong x-text="montantFC.toLocaleString('fr-FR')"></strong> FC
                    </span>
                    <span class="hint-right" x-show="depasse" x-cloak>
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Dépasse le maximum disponible
                    </span>
                </div>
            </div>

            {{-- Simulation --}}
            <div class="simulation-card">
                <div class="simulation-header">
                    <i class="bi bi-calculator"></i>
                    <span>Simulation après validation</span>
                </div>

                <div class="simulation-row">
                    <span>Dette totale après avance :</span>
                    <strong x-text="detteApres.toLocaleString('fr-FR') + ' $'"></strong>
                </div>

                <div class="simulation-row">
                    <span>Reste à percevoir ce mois :</span>
                    <strong x-text="resteApres.toLocaleString('fr-FR') + ' $'"></strong>
                </div>
            </div>

            {{-- Motif --}}
            <div class="form-field">
                <div class="textarea-wrap">
                    <textarea name="motif"
                              id="motif"
                              rows="5"
                              placeholder=" "
                              required
                              minlength="10"
                              maxlength="1000"
                              class="@error('motif') is-invalid @enderror">{{ old('motif') }}</textarea>
                    <label for="motif" class="float-label">
                        Motif de la demande <span style="color:#ef4444;">*</span>
                    </label>
                    <i class="bi bi-chat-left-text icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('motif')
                    <p class="error-text">{{ $message }}</p>
                @enderror
                <p class="hint-text">
                    <i class="bi bi-info-circle"></i>
                    Minimum 10 caractères. Cette demande sera examinée par l'administration
                    et vous serez notifié du résultat.
                </p>
            </div>
        </div>

        {{-- Boutons --}}
        <div class="button-group">
            <a href="{{ route('member.demandes-avance.index') }}" class="btn-cancel">
                Annuler
            </a>
            <button type="submit"
                    class="btn-submit"
                    :disabled="!valide"
                    :class="{ 'is-disabled': !valide }">
                Soumettre la demande <i class="bi bi-send"></i>
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
    .form-subtitle {
        color: #94a3b8;
        font-size: 0.9rem;
    }
    .form-subtitle strong { color: #64748b; font-weight: 600; }

    /* ============================================================
       ALERTE
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
       SYNTHÈSE FINANCIÈRE
       ============================================================ */
    .financial-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        animation: fadeUp 0.6s 0.2s ease forwards;
        opacity: 0;
    }

    .summary-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.9rem 1rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        transition: all 0.25s;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    }

    .summary-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .icon-salaire { background: #e0e7ff; color: #4f46e5; }
    .icon-dette   { background: #fee2e2; color: #dc2626; }
    .icon-limite  { background: #fef3c7; color: #d97706; }
    .icon-dispo   { background: rgba(255,255,255,0.25); color: #fff; }

    .summary-body { flex: 1; min-width: 0; }
    .summary-label {
        font-size: 0.65rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .summary-value {
        font-size: 1.05rem;
        font-weight: 800;
        color: #1e293b;
        margin-top: 0.15rem;
        letter-spacing: -0.02em;
    }
    .summary-value.value-danger { color: #dc2626; }

    .summary-card-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border-color: transparent;
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
    }
    .summary-card-primary .summary-label { color: rgba(255,255,255,0.85); }
    .summary-card-primary .summary-value { color: #fff; }

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
        gap: 1.75rem;
    }

    .form-field {
        position: relative;
        animation: fieldSlide 0.5s ease forwards;
        opacity: 0;
    }
    .form-field:nth-of-type(1) { animation-delay: 0.35s; }
    .form-field:nth-of-type(2) { animation-delay: 0.5s; }
    .form-field:nth-of-type(3) { animation-delay: 0.6s; }

    .field-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.75rem;
    }

    /* ============================================================
       INPUT MONTANT (spécial)
       ============================================================ */
    .amount-wrap {
        position: relative;
        border-bottom: 2px solid #e2e8f0;
        transition: border-color 0.3s;
    }
    .amount-wrap:focus-within { border-bottom-color: #667eea; }
    .amount-wrap.is-over { border-bottom-color: #ef4444; }

    .amount-wrap input {
        width: 100%;
        padding: 0.8rem 4rem 0.8rem 0;
        border: none;
        background: transparent;
        font-size: 1.75rem;
        font-weight: 800;
        color: #1e293b;
        outline: none !important;
        letter-spacing: -0.02em;
        -webkit-appearance: none;
        -moz-appearance: textfield;
        appearance: textfield;
    }
    .amount-wrap input::-webkit-outer-spin-button,
    .amount-wrap input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .amount-suffix {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1rem;
        font-weight: 700;
        pointer-events: none;
    }

    .amount-wrap .line-focus {
        position: absolute;
        bottom: -2px;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .amount-wrap:focus-within .line-focus { width: 100%; }

    .amount-hint {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.78rem;
        margin-top: 0.6rem;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .hint-left { color: #94a3b8; }
    .hint-left strong { color: #475569; font-weight: 700; }
    .hint-right {
        color: #dc2626;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    /* ============================================================
       SIMULATION CARD
       ============================================================ */
    .simulation-card {
        padding: 1.1rem 1.25rem;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        animation: fieldSlide 0.5s 0.45s ease forwards;
        opacity: 0;
    }
    .simulation-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .simulation-header i { font-size: 1rem; }

    .simulation-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.88rem;
        padding: 0.35rem 0;
    }
    .simulation-row span { color: #1e40af; }
    .simulation-row strong {
        color: #1e3a8a;
        font-weight: 800;
    }

    /* ============================================================
       TEXTAREA FLOTTANT
       ============================================================ */
    .textarea-wrap { position: relative; }

    .textarea-wrap textarea {
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
        resize: vertical;
        min-height: 90px;
        font-family: inherit;
        line-height: 1.5;
    }
    .textarea-wrap textarea::placeholder { color: transparent; }
    .textarea-wrap textarea:focus { border-bottom-color: #667eea; }
    .textarea-wrap textarea.is-invalid { border-bottom-color: #ef4444; }

    .textarea-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.8rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .textarea-wrap textarea:focus ~ .float-label,
    .textarea-wrap textarea:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .textarea-wrap textarea.is-invalid:focus ~ .float-label,
    .textarea-wrap textarea.is-invalid:not(:placeholder-shown) ~ .float-label {
        color: #ef4444;
    }

    .textarea-wrap i.icon {
        position: absolute;
        right: 0;
        top: 0.8rem;
        color: #cbd5e1;
        font-size: 1.1rem;
        transition: all 0.3s;
        pointer-events: none;
    }
    .textarea-wrap textarea:focus ~ i.icon {
        color: #667eea;
        transform: scale(1.1);
    }
    .textarea-wrap textarea.is-invalid ~ i.icon { color: #ef4444; }

    .textarea-wrap .line-focus {
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
    .textarea-wrap textarea:focus ~ .line-focus { width: 100%; }

    /* ============================================================
       HINTS & ERRORS
       ============================================================ */
    .hint-text {
        color: #94a3b8;
        font-size: 0.75rem;
        margin-top: 0.5rem;
        display: flex;
        align-items: center;
        gap: 4px;
        line-height: 1.5;
    }
    .hint-text i { color: #cbd5e1; }

    .error-text {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.4rem;
        font-weight: 500;
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
        margin-top: 1.75rem;
    }

    .btn-submit {
        padding: 0.85rem 2rem;
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
    .btn-submit:hover:not(:disabled):not(.is-disabled) {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    .btn-submit:active:not(:disabled):not(.is-disabled) {
        transform: translateY(0) scale(0.98);
    }
    .btn-submit i { transition: transform 0.3s; }
    .btn-submit:hover:not(:disabled):not(.is-disabled) i {
        transform: translateX(4px) translateY(-1px);
    }
    .btn-submit.is-disabled,
    .btn-submit:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        opacity: 0.7;
        box-shadow: none;
    }

    .btn-cancel {
        padding: 0.85rem 1.5rem;
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
    @media (max-width: 1024px) {
        .financial-summary { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .form-container { padding: 1.5rem; }
        .financial-summary { grid-template-columns: 1fr 1fr; }
        .button-group { flex-direction: column-reverse; }
        .btn-submit, .btn-cancel { width: 100%; }
        .amount-wrap input { font-size: 1.5rem; }
    }

    @media (max-width: 480px) {
        .financial-summary { grid-template-columns: 1fr; }
        .summary-card { padding: 0.75rem; }
        .summary-value { font-size: 0.95rem; }
    }
</style>
@endsection