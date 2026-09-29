@extends('layouts.admin')

@section('page_title', 'Taux de change')
@section('page_subtitle', 'Configuration du taux ' . $deviseSource->code . ' → ' . $deviseCible->code)

@section('content')
<div class="taux-page">

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
    {{-- EN-TÊTE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-arrow-right-arrow-left title-icon" aria-hidden="true"></i>
                Taux de change
            </h1>
            <p class="page-subtitle">
                1 {{ $deviseSource->code }} = ? {{ $deviseCible->code }}
            </p>
        </div>
        <a href="{{ route('admin.devises.index') }}" class="btn btn-ghost">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span>Retour</span>
        </a>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- DISPLAY DES DEVISES --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="taux-display">
        <div class="devise-block">
            <div class="devise-code">{{ $deviseSource->code }}</div>
            <div class="devise-nom">{{ $deviseSource->nom }}</div>
            @if($deviseSource->symbole)
                <div class="devise-symbole">{{ $deviseSource->symbole }}</div>
            @endif
        </div>

        <div class="taux-arrow" aria-hidden="true">
            <i class="fa-solid fa-arrow-right"></i>
        </div>

        <div class="devise-block">
            <div class="devise-code">{{ $deviseCible->code }}</div>
            <div class="devise-nom">{{ $deviseCible->nom }}</div>
            @if($deviseCible->symbole)
                <div class="devise-symbole">{{ $deviseCible->symbole }}</div>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FORMULAIRE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <form action="{{ route('admin.taux.update') }}" method="POST"
          class="form-container" novalidate>
        @csrf
        @method('PUT')

        {{-- Taux enregistré --}}
        <div class="taux-current">
            <span class="taux-current-label">Taux enregistré</span>
            <span class="taux-current-value">
                1 {{ $deviseSource->code }} =
                <strong>{{ number_format((float) $taux, 2, ',', ' ') }} {{ $deviseCible->code }}</strong>
            </span>
        </div>

        {{-- Champ taux --}}
        <div class="form-field">
            <div class="input-wrap">
                <input type="number"
                       step="0.01"
                       min="0"
                       name="taux"
                       id="taux"
                       value="{{ old('taux', $taux) }}"
                       placeholder=" "
                       required
                       autofocus
                       class="@error('taux') is-invalid @enderror">
                <label for="taux" class="float-label">
                    Nouveau taux (1 {{ $deviseSource->code }} = ? {{ $deviseCible->code }})
                </label>
                <i class="fa-solid fa-coins icon" aria-hidden="true"></i>
                <div class="line-focus"></div>
            </div>
            @error('taux')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        {{-- Aperçu conversion --}}
        <div class="conversion-preview" x-data="{ taux: {{ (float) old('taux', $taux) }} }">
            <div class="preview-label">Aperçu de la conversion</div>
            <div class="preview-rows">
                <div class="preview-row">
                    <span>1 {{ $deviseSource->code }}</span>
                    <strong x-text="taux ? taux.toLocaleString('fr-FR', {minimumFractionDigits: 0, maximumFractionDigits: 2}) + ' {{ $deviseCible->code }}' : '—'"></strong>
                </div>
                <div class="preview-row">
                    <span>10 {{ $deviseSource->code }}</span>
                    <strong x-text="taux ? (taux * 10).toLocaleString('fr-FR', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' {{ $deviseCible->code }}' : '—'"></strong>
                </div>
                <div class="preview-row">
                    <span>100 {{ $deviseSource->code }}</span>
                    <strong x-text="taux ? (taux * 100).toLocaleString('fr-FR', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' {{ $deviseCible->code }}' : '—'"></strong>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="button-group">
            <a href="{{ route('admin.devises.index') }}" class="btn-cancel">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                Annuler
            </a>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Enregistrer
            </button>
        </div>
    </form>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- INFO --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <div class="taux-info">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <div>
            <strong>À quoi sert ce taux ?</strong>
            <p>
                Il est utilisé pour convertir automatiquement les montants en
                <strong>{{ $deviseSource->code }}</strong> vers
                <strong>{{ $deviseCible->code }}</strong> dans les paiements, frais,
                salaires et statistiques.
            </p>
            <p>
                Le taux est mis en cache <strong>pendant 1 heure</strong>.
                Après modification, le cache se rafraîchit automatiquement.
            </p>
        </div>
    </div>
</div>

<style>
    /* ════════════════════════════════════════════════════════
       BASE
       ════════════════════════════════════════════════════════ */
    .taux-page { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; }

    /* HEADER */
    .page-header {
        display: flex; flex-direction: column; align-items: flex-start;
        gap: 1.25rem; margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .page-header { flex-direction: row; justify-content: space-between; align-items: center; }
    }
    .page-title {
        display: flex; align-items: center; gap: 0.6rem;
        font-size: 1.75rem; font-weight: 800; color: #0f172a;
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .title-icon { color: #6366f1; }
    .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; }

    /* FLASH */
    .flash {
        display: flex; gap: 0.65rem; align-items: flex-start;
        padding: 0.9rem 1.1rem; border-radius: 12px;
        margin-bottom: 1.25rem; font-size: 0.9rem;
    }
    .flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
    .flash-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

    /* BOUTONS */
    .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.7rem 1.25rem; border-radius: 12px;
        font-weight: 600; font-size: 0.9rem; text-decoration: none;
        border: none; cursor: pointer; transition: all 0.2s;
        white-space: nowrap;
    }
    .btn-ghost { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .btn-ghost:hover { border-color: #6366f1; color: #4f46e5; background: #f8fafc; }

    /* DISPLAY DEVISES */
    .taux-display {
        display: flex; justify-content: center; align-items: center;
        gap: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap;
    }
    .devise-block {
        text-align: center; padding: 1.25rem 2rem;
        background: #f8fafc; border: 2px solid #e2e8f0;
        border-radius: 16px; min-width: 140px;
        transition: all 0.2s;
    }
    .devise-block:hover { border-color: #c7d2fe; background: #eef2ff; }
    .devise-code { font-size: 1.75rem; font-weight: 800; color: #4f46e5; letter-spacing: -1px; line-height: 1.1; }
    .devise-nom { font-size: 0.85rem; font-weight: 600; color: #0f172a; margin-top: 0.25rem; }
    .devise-symbole { font-size: 1.25rem; color: #94a3b8; margin-top: 0.5rem; }
    .taux-arrow { font-size: 1.5rem; color: #cbd5e1; display: flex; align-items: center; justify-content: center; }
    @media (max-width: 640px) { .taux-arrow { transform: rotate(90deg); } }

    /* FORM CONTAINER */
    .form-container {
        background: #fff; padding: 2rem;
        border-radius: 20px; border: 1px solid #f1f5f9;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        margin-bottom: 1.5rem;
    }

    /* TAUX ACTUEL */
    .taux-current {
        display: flex; flex-direction: column; gap: 0.25rem;
        padding: 1rem 1.25rem; margin-bottom: 1.5rem;
        background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
        border: 1px solid #c7d2fe; border-radius: 12px;
    }
    .taux-current-label {
        font-size: 0.72rem; font-weight: 700;
        color: #6366f1; text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .taux-current-value { font-size: 1rem; color: #0f172a; }
    .taux-current-value strong { color: #4f46e5; font-size: 1.25rem; font-weight: 800; }

    /* FORM FIELD */
    .form-field { position: relative; margin-bottom: 1.5rem; }

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
        outline: none;
        -webkit-appearance: none;
        appearance: none;
    }
    .input-wrap input::placeholder { color: transparent; }
    .input-wrap input:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid { border-bottom-color: #ef4444; }

    .input-wrap .float-label {
        position: absolute; left: 0; top: 0.8rem;
        color: #94a3b8; font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem; font-size: 0.72rem;
        font-weight: 700; color: #667eea;
        letter-spacing: 0.5px; text-transform: uppercase;
    }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label { color: #ef4444; }

    .input-wrap i.icon {
        position: absolute; right: 0; top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1; font-size: 1.1rem;
        transition: all 0.3s; pointer-events: none;
    }
    .input-wrap input:focus ~ i.icon { color: #667eea; transform: translateY(-50%) scale(1.1); }

    .input-wrap .line-focus {
        position: absolute; bottom: 0; left: 50%;
        width: 0; height: 2px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        transform: translateX(-50%); pointer-events: none;
    }
    .input-wrap input:focus ~ .line-focus { width: 100%; }

    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.5rem; }

    /* APERÇU CONVERSION */
    .conversion-preview {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 12px; padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }
    .preview-label {
        font-size: 0.72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.5px;
        color: #94a3b8; margin-bottom: 0.75rem;
    }
    .preview-rows { display: flex; flex-direction: column; gap: 0.5rem; }
    .preview-row {
        display: flex; justify-content: space-between; align-items: center;
        font-size: 0.85rem; padding: 0.35rem 0;
    }
    .preview-row + .preview-row { border-top: 1px dashed #e2e8f0; }
    .preview-row span { color: #64748b; }
    .preview-row strong { color: #4f46e5; font-weight: 700; font-variant-numeric: tabular-nums; }

    /* BUTTON GROUP */
    .button-group {
        display: flex; justify-content: flex-end; gap: 0.6rem;
        padding-top: 1.5rem; border-top: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .button-group { flex-direction: column-reverse; }
        .button-group > * { width: 100%; }
    }

    .btn-submit {
        padding: 0.8rem 1.75rem;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff; border: none; border-radius: 12px;
        font-weight: 600; font-size: 0.95rem;
        display: inline-flex; align-items: center;
        justify-content: center; gap: 8px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.35); }
    .btn-submit:active { transform: translateY(0) scale(0.98); box-shadow: none; }

    .btn-cancel {
        padding: 0.8rem 1.5rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        background: #fff;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        cursor: pointer;
    }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    /* INFO */
    .taux-info {
        display: flex; gap: 0.75rem; align-items: flex-start;
        padding: 1rem 1.25rem;
        background: #eff6ff; border: 1px solid #bfdbfe;
        border-radius: 12px; font-size: 0.85rem;
        color: #1e40af;
    }
    .taux-info > i { color: #2563eb; font-size: 1rem; margin-top: 2px; flex-shrink: 0; }
    .taux-info strong { color: #1e3a8a; }
    .taux-info p { margin: 0.25rem 0 0; line-height: 1.5; }
    .taux-info p + p { margin-top: 0.5rem; }

    /* RESPONSIVE */
    @media (max-width: 480px) {
        .taux-page { padding: 1.25rem 0.75rem; }
        .page-title { font-size: 1.4rem; }
        .devise-block { padding: 1rem 1.5rem; min-width: 110px; }
        .devise-code { font-size: 1.5rem; }
        .form-container { padding: 1.25rem 1rem; }
    }
</style>
@endsection