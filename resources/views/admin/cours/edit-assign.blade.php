@extends('layouts.admin')

@section('content')
<div class="max-w-2xl mx-auto">
    {{-- En-tête avec retour --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Modifier l'<strong>assignation</strong></h1>
            <p class="form-subtitle">Mettez à jour les paramètres de cette assignation</p>
        </div>
        <a href="{{ route('admin.cours.assign', $assign->cours_id) }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Retour aux assignations
        </a>
    </div>

    <div class="form-container">
        <form action="{{ route('admin.cours.assign.update', $assign->id) }}" method="POST" class="form-grid" novalidate>
            @csrf
            @method('PUT')

            {{-- Libellé --}}
            <div class="form-field">
                <label for="libelle_id" class="field-label">Libellé <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="libelle_id" id="libelle_id" required class="@error('libelle_id') is-invalid @enderror">
                        @foreach($libelles as $l)
                            <option value="{{ $l->id }}" {{ old('libelle_id', $assign->libelle_id) == $l->id ? 'selected' : '' }}>
                                {{ $l->nom }}
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('libelle_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Nombre d'heures --}}
            <div class="form-field">
                <label for="nombre_heure_id" class="field-label">Nombre d'heures <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="nombre_heure_id" id="nombre_heure_id" required class="@error('nombre_heure_id') is-invalid @enderror">
                        @foreach($nombreHeures as $nh)
                            <option value="{{ $nh->id }}" {{ old('nombre_heure_id', $assign->nombre_heure_id) == $nh->id ? 'selected' : '' }}>
                                {{ $nh->libelle }}
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('nombre_heure_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Pondération --}}
            <div class="form-field">
                <label for="ponderation_id" class="field-label">Pondération <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="ponderation_id" id="ponderation_id" required class="@error('ponderation_id') is-invalid @enderror">
                        @foreach($ponderations as $p)
                            <option value="{{ $p->id }}" {{ old('ponderation_id', $assign->ponderation_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->nom }} ({{ $p->valeur }})
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('ponderation_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- Titulaire --}}
            <div class="form-field">
                <label for="titulaire_id" class="field-label">Titulaire</label>
                <div class="select-wrap">
                    <select name="titulaire_id" id="titulaire_id">
                        <option value="">-- Aucun --</option>
                        @foreach($titulaires as $t)
                            <option value="{{ $t->id }}" {{ old('titulaire_id', $assign->titulaire_id) == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
            </div>

            {{-- Jours de cours (chips stylés) --}}
            <div class="form-field">
                <label class="field-label">Jours de cours</label>
                <div class="day-chips">
                    @foreach($jours as $jour)
                        <label class="day-chip">
                            <input type="checkbox" name="jours[]" value="{{ $jour }}"
                                   class="day-chip-input"
                                   {{ in_array($jour, $joursSelectionnes) ? 'checked' : '' }}>
                            <span class="day-chip-label">{{ $jour }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Créneau horaire --}}
            <div class="form-field">
                <label for="creneau_horaire_id" class="field-label">Créneau horaire <span style="color:#ef4444;">*</span></label>
                <div class="select-wrap">
                    <select name="creneau_horaire_id" id="creneau_horaire_id" required class="@error('creneau_horaire_id') is-invalid @enderror">
                        <option value="">Choisir...</option>
                        @foreach($creneauxHoraires as $creneau)
                            <option value="{{ $creneau->id }}" {{ old('creneau_horaire_id', $assign->creneau_horaire_id) == $creneau->id ? 'selected' : '' }}>
                                {{ $creneau->libelle }} ({{ $creneau->heure_debut->format('H:i') }} - {{ $creneau->heure_fin->format('H:i') }})
                            </option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down select-icon"></i>
                    <div class="line-focus"></div>
                </div>
                @error('creneau_horaire_id')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="button-group">
                <a href="{{ route('admin.cours.assign', $assign->cours_id) }}" class="btn-cancel">Annuler</a>
                <button type="submit" class="btn-submit">Mettre à jour <i class="bi bi-arrow-right"></i></button>
            </div>
        </form>
    </div>
</div>

<style>
    /* ==== Styles locaux pour le formulaire (ne modifient pas le layout) ==== */
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
    .form-subtitle { color: #94a3b8; font-size: 0.85rem; margin-bottom: 0; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

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

    .field-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .select-wrap { position: relative; }
    .select-wrap select {
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
        cursor: pointer;
    }
    .select-wrap select:focus { border-bottom-color: #667eea; }
    .select-wrap select.is-invalid { border-bottom-color: #ef4444; }

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
    .select-wrap select:focus ~ .line-focus { width: 100%; }

    /* Chips pour les jours */
    .day-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .day-chip {
        cursor: pointer;
    }
    .day-chip-input {
        display: none;
    }
    .day-chip-label {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.9rem;
        font-weight: 500;
        color: #475569;
        background: #f1f5f9;
        border: 1.5px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .day-chip-input:checked + .day-chip-label {
        background: #667eea;
        color: white;
        border-color: #667eea;
        box-shadow: 0 4px 8px rgba(102,126,234,0.3);
    }
    .day-chip-label:hover {
        border-color: #667eea;
        background: #eef2ff;
        color: #4f46e5;
    }
    .day-chip-input:checked + .day-chip-label:hover {
        background: #667eea;
        color: white;
    }

    .button-group {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f1f5f9;
        margin-top: 0.5rem;
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
        animation: fadeUp 0.5s 0.9s ease forwards;
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
        animation: fadeUp 0.5s 0.85s ease forwards;
        opacity: 0;
    }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.3rem; }

    @media (max-width: 768px) {
        .header-container { flex-direction: column; align-items: flex-start; gap: 1rem; }
        .form-container { padding: 1.5rem; }
        .button-group { flex-direction: column-reverse; }
        .btn-submit, .btn-cancel { width: 100%; }
        .day-chips { justify-content: center; }
    }
</style>
@endsection