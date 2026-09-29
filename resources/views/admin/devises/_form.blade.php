@php $d = $devise ?? null; @endphp

<div class="form-grid">

    {{-- CODE --}}
    <div class="form-field">
        <div class="input-wrap">
            <input type="text"
                   name="code"
                   id="code"
                   value="{{ old('code', $d?->code) }}"
                   placeholder=" "
                   maxlength="10"
                   required
                   autofocus
                   autocomplete="off"
                   style="text-transform: uppercase;"
                   class="@error('code') is-invalid @enderror">
            <label for="code" class="float-label">
                Code <span class="req">*</span>
            </label>
            <i class="fa-solid fa-key icon" aria-hidden="true"></i>
            <div class="line-focus"></div>
        </div>
        <small class="form-hint">Ex : USD, CDF, EUR — 3 lettres recommandées</small>
        @error('code')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    {{-- NOM --}}
    <div class="form-field">
        <div class="input-wrap">
            <input type="text"
                   name="nom"
                   id="nom"
                   value="{{ old('nom', $d?->nom) }}"
                   placeholder=" "
                   required
                   autocomplete="off"
                   class="@error('nom') is-invalid @enderror">
            <label for="nom" class="float-label">
                Nom <span class="req">*</span>
            </label>
            <i class="fa-solid fa-tag icon" aria-hidden="true"></i>
            <div class="line-focus"></div>
        </div>
        <small class="form-hint">Ex : Dollar américain, Franc congolais</small>
        @error('nom')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    {{-- SYMBOLE --}}
    <div class="form-field">
        <div class="input-wrap">
            <input type="text"
                   name="symbole"
                   id="symbole"
                   value="{{ old('symbole', $d?->symbole) }}"
                   placeholder=" "
                   maxlength="10"
                   autocomplete="off"
                   class="@error('symbole') is-invalid @enderror">
            <label for="symbole" class="float-label">
                Symbole
            </label>
            <i class="fa-solid fa-dollar-sign icon" aria-hidden="true"></i>
            <div class="line-focus"></div>
        </div>
        <small class="form-hint">Ex : $, FC, € (optionnel)</small>
        @error('symbole')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    {{-- PAR DÉFAUT --}}
    <div class="form-field">
        <label class="toggle-wrap {{ old('est_defaut', $d?->est_defaut) ? 'is-checked' : '' }}"
               id="toggle-defaut">
            <input type="checkbox"
                   name="est_defaut"
                   value="1"
                   id="est_defaut"
                   {{ old('est_defaut', $d?->est_defaut) ? 'checked' : '' }}>
            <div class="toggle-track">
                <div class="toggle-thumb"></div>
            </div>
            <span class="toggle-label">
                <strong>Devise par défaut</strong>
                <small>Utilisée comme référence principale</small>
            </span>
        </label>
    </div>
</div>

<style>
    /* ════════════════════════════════════════════════════════
       FORM GRID
       ════════════════════════════════════════════════════════ */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    @media (min-width: 640px) {
        .form-grid { grid-template-columns: repeat(2, 1fr); }
        /* Nom prend toute la largeur */
        .form-field:nth-child(2) { grid-column: 1 / -1; }
    }

    /* ════════════════════════════════════════════════════════
       INPUT FLOTTANT
       ════════════════════════════════════════════════════════ */
    .form-field {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .input-wrap { position: relative; }

    .input-wrap input {
        width: 100%;
        padding: 0.9rem 2.5rem 0.9rem 0;
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
        font-family: inherit;
    }
    .input-wrap input::placeholder { color: transparent; }
    .input-wrap input:focus { border-bottom-color: #6366f1; }
    .input-wrap input.is-invalid { border-bottom-color: #ef4444; }

    .input-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.9rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label {
        top: -0.5rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: #6366f1;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label {
        color: #ef4444;
    }

    .input-wrap i.icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1rem;
        transition: all 0.3s;
        pointer-events: none;
    }
    .input-wrap input:focus ~ i.icon {
        color: #6366f1;
        transform: translateY(-50%) scale(1.1);
    }
    .input-wrap input.is-invalid ~ i.icon { color: #ef4444; }

    .input-wrap .line-focus {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .input-wrap input:focus ~ .line-focus { width: 100%; }

    .form-hint {
        font-size: 0.72rem;
        color: #94a3b8;
        margin: 0;
        padding-left: 0.1rem;
        line-height: 1.3;
    }

    .error-text {
        color: #ef4444;
        font-size: 0.78rem;
        margin: 0;
        padding-left: 0.1rem;
    }
    .req { color: #ef4444; }

    /* ════════════════════════════════════════════════════════
       TOGGLE
       ════════════════════════════════════════════════════════ */
    .toggle-wrap {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 1rem 1.25rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.2s;
        user-select: none;
    }
    .toggle-wrap:hover {
        border-color: #c7d2fe;
        background: #eef2ff;
    }
    .toggle-wrap.is-checked {
        border-color: #6366f1;
        background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
    }

    .toggle-wrap input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .toggle-track {
        position: relative;
        width: 44px;
        height: 24px;
        background: #cbd5e1;
        border-radius: 9999px;
        transition: background 0.25s;
        flex-shrink: 0;
    }
    .toggle-wrap input:checked ~ .toggle-track {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
    }

    .toggle-thumb {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .toggle-wrap input:checked ~ .toggle-track .toggle-thumb {
        transform: translateX(20px);
    }

    .toggle-label {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        min-width: 0;
    }
    .toggle-label strong {
        font-size: 0.85rem;
        font-weight: 700;
        color: #1e293b;
    }
    .toggle-wrap.is-checked .toggle-label strong { color: #4338ca; }
    .toggle-label small {
        font-size: 0.72rem;
        color: #64748b;
        line-height: 1.3;
    }
</style>

<script>
    // Met à jour l'état visuel du toggle
    document.addEventListener('DOMContentLoaded', function () {
        const checkbox = document.getElementById('est_defaut');
        const wrapper  = document.getElementById('toggle-defaut');

        if (checkbox && wrapper) {
            const update = () => {
                wrapper.classList.toggle('is-checked', checkbox.checked);
            };
            checkbox.addEventListener('change', update);
            update();
        }
    });
</script>