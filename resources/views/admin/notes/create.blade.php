@extends('layouts.admin')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="header-container">
        <div>
            <h1 class="form-title">Nouvelle <strong>note</strong></h1>
            <p class="form-subtitle">Ajoutez une note pour un élève</p>
        </div>
        <a href="{{ route('admin.notes.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>

    <form action="{{ route('admin.notes.store') }}" method="POST" class="form-container" novalidate>
        @csrf

        <div class="form-grid">
            {{-- Élève --}}
            <div class="form-field">
                <div class="input-wrap">
                    <select name="eleve_id" id="eleve_id" class="@error('eleve_id') is-invalid @enderror" required>
                        <option value="">— Sélectionner un élève —</option>
                        @foreach($eleves as $e)
                            <option value="{{ $e->id }}" {{ old('eleve_id') == $e->id ? 'selected' : '' }}>
                                {{ $e->nom_complet }}
                            </option>
                        @endforeach
                    </select>
                    <label for="eleve_id" class="float-label">Élève <span style="color:#ef4444;">*</span></label>
                    <i class="fa-regular fa-user-graduate icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('eleve_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Cours (salle) --}}
            <div class="form-field">
                <div class="input-wrap">
                    <select name="cour_salle_id" id="cour_salle_id" class="@error('cour_salle_id') is-invalid @enderror" required>
                        <option value="">— Sélectionner un cours —</option>
                        @foreach($coursSalles as $cs)
                            <option value="{{ $cs->id }}" {{ old('cour_salle_id') == $cs->id ? 'selected' : '' }}>
                                {{ $cs->cour?->nom ?? 'Cours supprimé' }} - {{ $cs->salle?->nom ?? 'Salle supprimée' }}
                            </option>
                        @endforeach
                    </select>
                    <label for="cour_salle_id" class="float-label">Cours (salle) <span style="color:#ef4444;">*</span></label>
                    <i class="fa-regular fa-book-open icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('cour_salle_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Période --}}
            <div class="form-field">
                <div class="input-wrap">
                    <select name="periode_note_id" id="periode_note_id" class="@error('periode_note_id') is-invalid @enderror" required>
                        <option value="">— Sélectionner une période —</option>
                        @foreach($periodes as $p)
                            <option value="{{ $p->id }}" {{ old('periode_note_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->nom }}
                            </option>
                        @endforeach
                    </select>
                    <label for="periode_note_id" class="float-label">Période <span style="color:#ef4444;">*</span></label>
                    <i class="fa-regular fa-calendar icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('periode_note_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Note --}}
            <div class="form-field">
                <div class="input-wrap">
                    <input type="number" step="0.01" min="0" max="20" name="note" id="note"
                           value="{{ old('note') }}"
                           placeholder=" " class="@error('note') is-invalid @enderror">
                    <label for="note" class="float-label">Note (0-20)</label>
                    <i class="fa-regular fa-star icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('note')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Appréciation --}}
            <div class="form-field">
                <div class="input-wrap">
                    <textarea name="appreciation" id="appreciation" rows="2" placeholder=" " class="@error('appreciation') is-invalid @enderror">{{ old('appreciation') }}</textarea>
                    <label for="appreciation" class="float-label">Appréciation</label>
                    <i class="fa-regular fa-comment icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('appreciation')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Statut --}}
            <div class="form-field">
                <div class="input-wrap">
                    <select name="statut" id="statut" class="@error('statut') is-invalid @enderror" required>
                        <option value="brouillon" {{ old('statut', 'brouillon') == 'brouillon' ? 'selected' : '' }}>Brouillon</option>
                        <option value="publie" {{ old('statut') == 'publie' ? 'selected' : '' }}>Publié</option>
                    </select>
                    <label for="statut" class="float-label">Statut</label>
                    <i class="fa-regular fa-circle-check icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('statut')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Boutons --}}
        <div class="button-group">
            <a href="{{ route('admin.notes.index') }}" class="btn-cancel">Annuler</a>
            <button type="submit" class="btn-submit">Créer <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </form>
</div>

{{-- Styles identiques --}}
<style>
    /* ==== Styles locaux premium ==== */
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
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
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #667eea;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: color 0.2s;
    }
    .back-link:hover { color: #4f46e5; }

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
    .form-field:nth-of-type(3) { animation-delay: 0.55s; }
    .form-field:nth-of-type(4) { animation-delay: 0.65s; }
    .form-field:nth-of-type(5) { animation-delay: 0.75s; }
    .form-field:nth-of-type(6) { animation-delay: 0.85s; }
    @keyframes fieldSlide {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Input flottant */
    .input-wrap { position: relative; }
    .input-wrap input,
    .input-wrap textarea,
    .input-wrap select {
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
    .input-wrap input::placeholder,
    .input-wrap textarea::placeholder { color: transparent; }
    .input-wrap input:focus,
    .input-wrap textarea:focus,
    .input-wrap select:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid,
    .input-wrap textarea.is-invalid,
    .input-wrap select.is-invalid { border-bottom-color: #ef4444; }
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
    .input-wrap input:not(:placeholder-shown) ~ .float-label,
    .input-wrap textarea:focus ~ .float-label,
    .input-wrap textarea:not(:placeholder-shown) ~ .float-label,
    .input-wrap select:focus ~ .float-label,
    .input-wrap select:not([value=""]) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label,
    .input-wrap textarea.is-invalid:focus ~ .float-label,
    .input-wrap textarea.is-invalid:not(:placeholder-shown) ~ .float-label,
    .input-wrap select.is-invalid:focus ~ .float-label,
    .input-wrap select.is-invalid:not([value=""]) ~ .float-label {
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
    .input-wrap input:focus ~ i.icon,
    .input-wrap textarea:focus ~ i.icon,
    .input-wrap select:focus ~ i.icon {
        color: #667eea;
        transform: translateY(-50%) scale(1.1);
    }
    .input-wrap input.is-invalid ~ i.icon,
    .input-wrap textarea.is-invalid ~ i.icon,
    .input-wrap select.is-invalid ~ i.icon {
        color: #ef4444;
    }
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
    .input-wrap input:focus ~ .line-focus,
    .input-wrap textarea:focus ~ .line-focus,
    .input-wrap select:focus ~ .line-focus {
        width: 100%;
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
        .header-container { flex-direction: column; align-items: flex-start; gap: 1rem; }
    }
</style>
@endsection