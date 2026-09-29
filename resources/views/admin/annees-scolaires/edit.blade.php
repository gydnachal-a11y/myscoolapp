@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto" x-data="{
    dateDebut: '{{ old('date_debut', $anneeScolaire->date_debut->format('Y-m-d')) }}',
    dateFin: '{{ old('date_fin', $anneeScolaire->date_fin->format('Y-m-d')) }}',
    libelle: '{{ old('libelle', $anneeScolaire->libelle) }}',
    nombreMois: {{ old('nombre_mois', $anneeScolaire->nombre_mois ?? 0) }},
    nombreTranches: {{ old('nombre_tranches', $anneeScolaire->nombre_tranches ?? 0) }},
    paiementOuvert: {{ old('paiement_ouvert', $anneeScolaire->paiement_ouvert) ? 'true' : 'false' }},

    genererLibelle() {
        if (this.dateDebut && this.dateFin) {
            const debut = new Date(this.dateDebut);
            const fin = new Date(this.dateFin);
            if (fin > debut) {
                return debut.getFullYear() + ' - ' + fin.getFullYear();
            }
        }
        return '';
    },
    calculerPeriodes() {
        if (this.dateDebut && this.dateFin) {
            const debut = new Date(this.dateDebut);
            const fin = new Date(this.dateFin);
            const diffMois = (fin.getFullYear() - debut.getFullYear()) * 12 + (fin.getMonth() - debut.getMonth()) + 1;
            this.nombreMois = diffMois > 0 ? diffMois : 1;
            this.nombreTranches = this.nombreMois >= 3 ? 3 : 1;
        }
    }
}" x-init="calculerPeriodes()">
    <div class="form-container">
        <h1 class="form-title">Modifier l'<strong>année scolaire</strong></h1>
        <p class="form-subtitle">Mettez à jour la période et les paramètres de paiement</p>

        <form action="{{ route('admin.annees-scolaires.update', $anneeScolaire) }}" method="POST" class="form-grid" novalidate>
            @csrf
            @method('PUT')

            <div class="form-field">
                <div class="input-wrap">
                    <input type="text" name="libelle" id="libelle" x-model="libelle" placeholder=" " required
                           class="@error('libelle') is-invalid @enderror">
                    <label for="libelle" class="float-label">Libellé <span style="color:#ef4444;">*</span></label>
                    <i class="bi bi-calendar2-week icon"></i>
                    <button type="button" @click="libelle = genererLibelle()" class="gen-btn" title="Générer à partir des dates">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <div class="line-focus"></div>
                </div>
                @error('libelle')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-row">
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="date" name="date_debut" id="date_debut" x-model="dateDebut"
                               @change="calculerPeriodes(); if (!libelle) libelle = genererLibelle()"
                               placeholder=" " required class="@error('date_debut') is-invalid @enderror">
                        <label for="date_debut" class="float-label">Date de début <span style="color:#ef4444;">*</span></label>
                        <i class="bi bi-calendar icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('date_debut')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="date" name="date_fin" id="date_fin" x-model="dateFin"
                               @change="calculerPeriodes(); if (!libelle) libelle = genererLibelle()"
                               placeholder=" " required class="@error('date_fin') is-invalid @enderror">
                        <label for="date_fin" class="float-label">Date de fin <span style="color:#ef4444;">*</span></label>
                        <i class="bi bi-calendar icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('date_fin')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="number" name="effectif_attendu" id="effectif_attendu"
                               value="{{ old('effectif_attendu', $anneeScolaire->effectif_attendu) }}" min="0" placeholder=" " required
                               class="@error('effectif_attendu') is-invalid @enderror">
                        <label for="effectif_attendu" class="float-label">Effectif attendu <span style="color:#ef4444;">*</span></label>
                        <i class="bi bi-people icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('effectif_attendu')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="paiement_ouvert" class="field-label">Session de paiement</label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="paiement_ouvert" id="paiement_ouvert" value="1" x-model="paiementOuvert"
                               class="form-check-input">
                        <span class="ml-2 text-sm text-gray-600">Ouvrir les paiements</span>
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="number" name="nombre_mois" id="nombre_mois" x-model="nombreMois" min="1" max="24" placeholder=" "
                               class="@error('nombre_mois') is-invalid @enderror">
                        <label for="nombre_mois" class="float-label">Nombre de mois</label>
                        <i class="bi bi-calendar-month icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('nombre_mois')<p class="error-text">{{ $message }}</p>@enderror
                    <p class="hint-text">Calculé automatiquement, modifiable.</p>
                </div>
                <div class="form-field">
                    <div class="input-wrap">
                        <input type="number" name="nombre_tranches" id="nombre_tranches" x-model="nombreTranches" min="1" max="12" placeholder=" "
                               class="@error('nombre_tranches') is-invalid @enderror">
                        <label for="nombre_tranches" class="float-label">Nombre de tranches</label>
                        <i class="bi bi-list-ol icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('nombre_tranches')<p class="error-text">{{ $message }}</p>@enderror
                    <p class="hint-text">Calculé automatiquement, modifiable.</p>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <a href="{{ route('admin.annees-scolaires.index') }}" class="btn-cancel">Annuler</a>
                <button type="submit" class="btn-submit">Mettre à jour <i class="bi bi-arrow-right"></i></button>
            </div>
        </form>
    </div>
</div>

<style>
    /* ==== Styles locaux pour le formulaire (ne modifient pas le layout) ==== */
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

    /* Taille du titre réduite pour un meilleur alignement dans la carte */
    .form-title {
        font-size: 1.5rem; /* Réduit de 2rem à 1.5rem */
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
        animation: fadeUp 0.6s 0.2s ease forwards;
        opacity: 0;
    }
    .form-title strong {
        font-weight: 800;
    }
    .form-subtitle {
        color: #94a3b8;
        font-size: 0.85rem;
        margin-bottom: 2rem;
        animation: fadeUp 0.6s 0.3s ease forwards;
        opacity: 0;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-grid {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    @media (min-width: 768px) {
        .form-row { grid-template-columns: 1fr 1fr; }
    }

    .form-field {
        position: relative;
        animation: fieldSlide 0.5s ease forwards;
        opacity: 0;
    }
    .form-row:nth-of-type(1) .form-field:nth-child(1) { animation-delay: 0.35s; }
    .form-row:nth-of-type(1) .form-field:nth-child(2) { animation-delay: 0.45s; }
    .form-row:nth-of-type(2) .form-field:nth-child(1) { animation-delay: 0.55s; }
    .form-row:nth-of-type(2) .form-field:nth-child(2) { animation-delay: 0.65s; }
    .form-row:nth-of-type(3) .form-field:nth-child(1) { animation-delay: 0.75s; }
    .form-row:nth-of-type(3) .form-field:nth-child(2) { animation-delay: 0.85s; }
    @keyframes fieldSlide {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .input-wrap {
        position: relative;
    }
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
    .input-wrap input::placeholder {
        color: transparent;
    }
    .input-wrap input:focus {
        border-bottom-color: #667eea;
    }
    .input-wrap input.is-invalid {
        border-bottom-color: #ef4444;
    }

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
    .input-wrap input.is-invalid ~ i.icon {
        color: #ef4444;
    }

    .input-wrap .gen-btn {
        position: absolute;
        right: 28px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #cbd5e1;
        cursor: pointer;
        padding: 4px;
        transition: color 0.2s;
        z-index: 2;
        outline: none !important;
    }
    .input-wrap .gen-btn:hover {
        color: #667eea;
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
    .input-wrap input:focus ~ .line-focus {
        width: 100%;
    }

    .field-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 1rem;
    }
    .form-check-input {
        outline: none !important;
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        border: 1.5px solid #cbd5e1;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        margin-right: 8px;
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
        font-size: 12px;
        font-weight: 700;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
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
        animation: fadeUp 0.5s 0.85s ease forwards;
        opacity: 0;
    }
    .btn-cancel:hover {
        border-color: #667eea;
        color: #667eea;
        background: #f8fafc;
    }

    .error-text {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.3rem;
    }
    .hint-text {
        color: #94a3b8;
        font-size: 0.75rem;
        margin-top: 0.3rem;
    }
</style>
@endsection