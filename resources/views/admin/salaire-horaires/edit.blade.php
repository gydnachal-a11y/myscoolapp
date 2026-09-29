@extends('layouts.admin')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="header-container">
        <div>
            <h1 class="form-title">Modifier le <strong>taux horaire</strong></h1>
            <p class="form-subtitle">Mettez à jour le taux de salaire horaire</p>
        </div>
    </div>

    <form action="{{ route('admin.salaire-horaires.update', $salaireHoraire) }}" method="POST" class="form-container" novalidate>
        @csrf
        @method('PUT')

        <div class="form-grid">
            {{-- Taux USD --}}
            <div class="form-field">
                <div class="input-wrap">
                    <input type="number" step="0.01" name="taux_usd" id="taux_usd"
                           value="{{ old('taux_usd', $salaireHoraire->taux_usd) }}"
                           placeholder=" " required class="@error('taux_usd') is-invalid @enderror">
                    <label for="taux_usd" class="float-label">Taux (USD/h) <span style="color:#ef4444;">*</span></label>
                    <i class="bi bi-currency-dollar icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('taux_usd')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Actif --}}
            <div class="form-field">
                <label class="checkbox-label">
                    <input type="checkbox" name="actif" value="1" id="actif"
                           class="form-check-input" {{ old('actif', $salaireHoraire->actif) ? 'checked' : '' }}>
                    <span>Marquer comme actif</span>
                </label>
            </div>
        </div>

        {{-- Boutons --}}
        <div class="button-group">
            <a href="{{ route('admin.salaire-horaires.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-submit">Mettre à jour <i class="bi bi-arrow-right"></i></button>
        </div>
    </form>
</div>

<style>
    /* ==== Styles locaux premium ==== */
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
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-container {
        background: white;
        padding: 2.5rem;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
        opacity: 0;
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-grid {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .form-field {
        position: relative;
        animation: fieldSlide 0.5s ease forwards;
        opacity: 0;
    }
    .form-field:nth-of-type(1) { animation-delay: 0.35s; }
    .form-field:nth-of-type(2) { animation-delay: 0.45s; }
    @keyframes fieldSlide {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

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
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label { color: #ef4444; }
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
    .input-wrap input:focus ~ i.icon { color: #667eea; transform: translateY(-50%) scale(1.1); }
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

    /* Checkbox */
    .checkbox-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: #475569;
        cursor: pointer;
    }
    .form-check-input {
        outline: none !important;
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border: 1.5px solid #cbd5e1;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
        box-shadow: none !important;
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

    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.3rem; }

    /* Boutons */
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
    .btn-submit:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-submit:active { transform: translateY(0) scale(0.98); box-shadow: none; }
    .btn-submit i { transition: transform 0.3s; }
    .btn-submit:hover i { transform: translateX(4px); }

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
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    @media (max-width: 768px) {
        .form-container { padding: 1.5rem; }
        .button-group { flex-direction: column-reverse; }
        .btn-submit, .btn-cancel { width: 100%; }
    }
</style>
@endsection