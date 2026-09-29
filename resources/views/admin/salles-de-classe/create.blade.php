@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="max-w-3xl mx-auto" x-data="salleForm()" x-init="init()">
    <div class="header-container">
        <div>
            <h1 class="form-title">Nouvelle <strong>salle de classe</strong></h1>
            <p class="form-subtitle">Configurez la salle, ses frais et ses paiements</p>
        </div>
        <a href="{{ route('admin.salles-de-classe.index') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    {{-- Alerte si aucune année scolaire active --}}
    @if(!$anneeActive)
        <div class="alert alert-warning" style="display: flex; align-items: center; gap: 8px; background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
            <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.2rem;"></i>
            <span>Aucune année scolaire active n'est définie. Vous devez d'abord <a href="{{ route('admin.annee-scolaires.create') }}" style="font-weight: 600; text-decoration: underline;">créer une année scolaire</a> avant d'ajouter des salles de classe.</span>
        </div>
    @endif

    {{-- Stepper --}}
    <div class="stepper mb-6">
        <div class="flex items-center justify-between">
            @foreach(['Informations', 'Frais', 'Paiements'] as $label)
                <div class="flex items-center flex-1">
                    <div class="flex items-center gap-2">
                        <div class="step-circle" :class="step >= {{ $loop->index + 1 }} ? 'active' : ''">
                            <span x-show="step > {{ $loop->index + 1 }}" class="text-white"><i class="bi bi-check-lg"></i></span>
                            <span x-show="step <= {{ $loop->index + 1 }}">{{ $loop->index + 1 }}</span>
                        </div>
                        <span class="step-label hidden sm:block">{{ $label }}</span>
                    </div>
                    @if(!$loop->last)
                        <div class="step-line" :class="{ 'active': step > {{ $loop->index + 1 }} }"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <form action="{{ route('admin.salles-de-classe.store') }}" method="POST" class="form-container" novalidate>
        @csrf

        {{-- Étape 1 : Informations générales et capacité --}}
        <div x-show="step === 1" x-transition:enter.duration.300ms>
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-info-circle"></i> Informations générales</h3>
                <div class="form-row">
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="text" name="nom" id="nom" value="{{ old('nom') }}" placeholder=" " required
                                   class="@error('nom') is-invalid @enderror">
                            <label for="nom" class="float-label">Nom <span style="color:#ef4444;">*</span></label>
                            <i class="bi bi-door-open icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('nom')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <label for="section_id" class="field-label">Section <span style="color:#ef4444;">*</span></label>
                        <div class="select-wrap">
                            <select name="section_id" id="section_id" required class="@error('section_id') is-invalid @enderror">
                                <option value="">Choisir une section</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>
                                        {{ $section->nom }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down select-icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('section_id')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <label for="option_id" class="field-label">Option (facultatif)</label>
                        <div class="select-wrap">
                            <select name="option_id" id="option_id">
                                <option value="">Aucune</option>
                                @foreach($options as $option)
                                    <option value="{{ $option->id }}" {{ old('option_id') == $option->id ? 'selected' : '' }}>
                                        {{ $option->nom }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down select-icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-people"></i> Capacité et âges</h3>
                <div class="form-row three-col">
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="number" name="capacite_max" id="capacite_max" value="{{ old('capacite_max') }}" min="1" placeholder=" " required
                                   class="@error('capacite_max') is-invalid @enderror">
                            <label for="capacite_max" class="float-label">Capacité max <span style="color:#ef4444;">*</span></label>
                            <i class="bi bi-people icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('capacite_max')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="number" name="age_min" id="age_min" value="{{ old('age_min') }}" min="0" placeholder=" " required
                                   class="@error('age_min') is-invalid @enderror">
                            <label for="age_min" class="float-label">Âge minimum <span style="color:#ef4444;">*</span></label>
                            <i class="bi bi-arrow-down-circle icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('age_min')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="number" name="age_max" id="age_max" value="{{ old('age_max') }}" min="0" placeholder=" " required
                                   class="@error('age_max') is-invalid @enderror">
                            <label for="age_max" class="float-label">Âge maximum <span style="color:#ef4444;">*</span></label>
                            <i class="bi bi-arrow-up-circle icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('age_max')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mt-4">
                <a href="{{ route('admin.salles-de-classe.index') }}" class="btn-cancel">Annuler</a>
                <button type="button" class="btn-next" @click="validateStep1()">Suivant <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        {{-- Étape 2 : Frais --}}
        <div x-show="step === 2" x-transition:enter.duration.300ms>
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-currency-dollar"></i> Frais</h3>
                <p class="hint-text mb-4">Taux de change : 1 USD = <span x-text="tauxChange"></span> CDF</p>
                <div class="form-row">
                    <div class="form-field">
                        <label class="field-label">Frais d'inscription ($) <span style="color:#ef4444;">*</span></label>
                        <div class="input-wrap">
                            <input type="number" step="0.01" name="frais_inscription" id="frais_inscription" x-model="fraisInscriptionUsd" @input="syncInscriptionUsd()" min="0" placeholder=" " required
                                   class="@error('frais_inscription') is-invalid @enderror">
                            <label for="frais_inscription" class="float-label">USD</label>
                            <i class="bi bi-currency-dollar icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('frais_inscription')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label">Équivalent en CDF</label>
                        <div class="input-wrap">
                            <input type="number" step="1" x-model="fraisInscriptionCdf" @input="syncInscriptionCdf()" placeholder=" " class="bg-gray-50">
                            <label class="float-label">CDF</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>
                </div>
                <div class="form-row mt-4">
                    <div class="form-field">
                        <label class="field-label">Frais annuel ($) <span style="color:#ef4444;">*</span></label>
                        <div class="input-wrap">
                            <input type="number" step="0.01" name="frais_annuel" id="frais_annuel" x-model="fraisAnnuelUsd" @input="syncAnnuelUsd()" min="0" placeholder=" " required
                                   class="@error('frais_annuel') is-invalid @enderror">
                            <label for="frais_annuel" class="float-label">USD</label>
                            <i class="bi bi-currency-dollar icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('frais_annuel')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label">Équivalent en CDF</label>
                        <div class="input-wrap">
                            <input type="number" step="1" x-model="fraisAnnuelCdf" @input="syncAnnuelCdf()" placeholder=" " class="bg-gray-50">
                            <label class="float-label">CDF</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mt-4">
                <button type="button" class="btn-prev" @click="step = 1"><i class="bi bi-arrow-left"></i> Précédent</button>
                <button type="button" class="btn-next" @click="validateStep2()">Suivant <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        {{-- Étape 3 : Configuration paiement + autres --}}
        <div x-show="step === 3" x-transition:enter.duration.300ms>
            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-credit-card"></i> Configuration des paiements</h3>
                <div class="form-row">
                    <div class="form-field">
                        <label for="mode_paiement" class="field-label">Mode de paiement <span style="color:#ef4444;">*</span></label>
                        <div class="select-wrap">
                            <select name="mode_paiement" id="mode_paiement" x-model="modePaiement" @change="recalculerFrais()"
                                    class="@error('mode_paiement') is-invalid @enderror">
                                <option value="mensuel">Mensuel</option>
                                <option value="tranche">Par tranche</option>
                            </select>
                            <i class="bi bi-chevron-down select-icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('mode_paiement')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field" x-show="modePaiement === 'mensuel'" x-transition:enter.duration.300ms>
                        <label class="field-label">Frais scolarité mensuel ($)</label>
                        <div class="input-wrap">
                            <input type="number" step="0.01" name="frais_scolarite_mensuel" id="frais_scolarite_mensuel" x-model="fraisScolMensuelUsd" @input="syncMensuelUsd()" min="0" placeholder=" "
                                   class="@error('frais_scolarite_mensuel') is-invalid @enderror">
                            <label for="frais_scolarite_mensuel" class="float-label">USD</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('frais_scolarite_mensuel')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field" x-show="modePaiement === 'mensuel'" x-transition:enter.duration.300ms>
                        <label class="field-label">Équivalent en CDF</label>
                        <div class="input-wrap">
                            <input type="number" step="1" x-model="fraisScolMensuelCdf" @input="syncMensuelCdf()" placeholder=" ">
                            <label class="float-label">CDF</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>

                    <div class="form-field" x-show="modePaiement === 'tranche'" x-transition:enter.duration.300ms>
                        <label class="field-label">Frais par tranche ($)</label>
                        <div class="input-wrap">
                            <input type="number" step="0.01" name="frais_par_tranche" id="frais_par_tranche" x-model="fraisParTrancheUsd" @input="syncTrancheUsd()" min="0" placeholder=" "
                                   class="@error('frais_par_tranche') is-invalid @enderror">
                            <label for="frais_par_tranche" class="float-label">USD</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('frais_par_tranche')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field" x-show="modePaiement === 'tranche'" x-transition:enter.duration.300ms>
                        <label class="field-label">Équivalent en CDF</label>
                        <div class="input-wrap">
                            <input type="number" step="1" x-model="fraisParTrancheCdf" @input="syncTrancheCdf()" placeholder=" ">
                            <label class="float-label">CDF</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>

                    {{-- Champ nombre de tranches --}}
                    <div class="form-field" x-show="modePaiement === 'tranche'" x-transition:enter.duration.300ms>
                        <label class="field-label">Nombre de tranches <span style="color:#ef4444;">*</span></label>
                        <div class="input-wrap">
                            <input type="number" name="nombre_tranches" id="nombre_tranches" x-model="nombreTranches" @input="recalculerFrais()" min="1" placeholder=" " 
                                   class="@error('nombre_tranches') is-invalid @enderror">
                            <label for="nombre_tranches" class="float-label">Nombre de tranches</label>
                            <i class="bi bi-hash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('nombre_tranches')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="hint-text">Le montant périodique est calculé automatiquement (frais annuel ÷ nombre de tranches ou mois de l'année scolaire). Vous pouvez le modifier manuellement.</p>
            </div>

            <div class="form-section" style="border-bottom: none;">
                <div class="form-row">
                    <div class="form-field">
                        <label for="salle_superieure_id" class="field-label">Salle supérieure (facultatif)</label>
                        <div class="select-wrap">
                            <select name="salle_superieure_id" id="salle_superieure_id">
                                <option value="">Aucune</option>
                                @foreach($salles as $s)
                                    <option value="{{ $s->id }}" {{ old('salle_superieure_id') == $s->id ? 'selected' : '' }}>
                                        {{ $s->nom }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down select-icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>
                    <div class="form-field">
                        <div class="textarea-wrap">
                            <textarea name="description" id="description" rows="3" placeholder=" "
                                      class="@error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            <label for="description" class="float-label">Description</label>
                            <i class="bi bi-card-text icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mt-4">
                <button type="button" class="btn-prev" @click="step = 2"><i class="bi bi-arrow-left"></i> Précédent</button>
                <button type="submit" class="btn-submit">Enregistrer <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
    </form>
</div>

<style>
    /* (styles identiques aux versions précédentes, déjà inclus) */
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
        padding: 2rem 2.5rem;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
        opacity: 0;
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .stepper {
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #f1f5f9;
        margin-bottom: 2rem;
        animation: fadeUp 0.6s 0.2s ease forwards;
        opacity: 0;
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
        font-weight: 500;
        color: #1e293b;
        margin-left: 6px;
    }
    .step-line {
        flex: 1;
        height: 2px;
        background: #e2e8f0;
        margin: 0 12px;
        transition: background 0.3s;
    }
    .step-line.active { background: #4f46e5; }

    .form-section { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; }
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { color: #667eea; }

    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 1rem;
        align-items: start;
    }
    @media (min-width: 768px) {
        .form-row { grid-template-columns: repeat(2, 1fr); }
        .form-row.three-col { grid-template-columns: repeat(3, 1fr); }
    }

    .form-field { position: relative; }
    .field-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .input-wrap, .select-wrap, .textarea-wrap { position: relative; }
    .input-wrap input, .select-wrap select, .textarea-wrap textarea {
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
        min-height: 42px;
    }
    .input-wrap input:focus, .select-wrap select:focus, .textarea-wrap textarea:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid, .select-wrap select.is-invalid, .textarea-wrap textarea.is-invalid { border-bottom-color: #ef4444; }
    .input-wrap .float-label, .select-wrap .float-label, .textarea-wrap .float-label {
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
    .select-wrap select:focus ~ .float-label,
    .textarea-wrap textarea:focus ~ .float-label,
    .textarea-wrap textarea:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap i.icon, .select-wrap .select-icon, .textarea-wrap i.icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.1rem;
        pointer-events: none;
    }
    .input-wrap input:focus ~ i.icon,
    .select-wrap select:focus ~ .select-icon,
    .textarea-wrap textarea:focus ~ i.icon { color: #667eea; }
    .input-wrap .line-focus, .select-wrap .line-focus, .textarea-wrap .line-focus {
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
    .select-wrap select:focus ~ .line-focus,
    .textarea-wrap textarea:focus ~ .line-focus { width: 100%; }

    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.3rem; }
    .hint-text { color: #94a3b8; font-size: 0.75rem; margin-top: 0.5rem; }
    .bg-gray-50 { background-color: #f8fafc; }

    .button-group { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
    .btn-submit, .btn-next, .btn-prev {
        padding: 0.8rem 1.75rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        outline: none;
        border: none;
    }
    .btn-submit, .btn-next { background: #1e293b; color: white; }
    .btn-submit:hover, .btn-next:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-prev { background: #f1f5f9; color: #475569; }
    .btn-prev:hover { background: #e2e8f0; transform: translateY(-2px); }
    .btn-cancel {
        padding: 0.6rem 1.2rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        color: #64748b;
        background: white;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    @media (max-width: 768px) {
        .header-container { flex-direction: column; align-items: flex-start; gap: 1rem; }
        .form-container { padding: 1.5rem; }
        .form-row.three-col { grid-template-columns: 1fr; }
        .stepper { padding: 0.75rem 1rem; }
        .step-label { display: none; }
        .button-group { flex-direction: column; gap: 0.5rem; }
        .btn-submit, .btn-cancel, .btn-next, .btn-prev { width: 100%; justify-content: center; }
    }
</style>

<script>
    function salleForm() {
        return {
            step: 1,
            modePaiement: '{{ old('mode_paiement', 'mensuel') }}',
            tauxChange: {{ $tauxChange ?? 2800 }},
            nombreMois: Number({{ $nombreMois ?? 0 }}),
            nombreTranches: Number({{ $nombreTranches ?? 0 }}),

            fraisInscriptionUsd: {{ old('frais_inscription', 0) }},
            fraisAnnuelUsd: {{ old('frais_annuel', 0) }},
            fraisScolMensuelUsd: {{ old('frais_scolarite_mensuel', 0) }},
            fraisParTrancheUsd: {{ old('frais_par_tranche', 0) }},

            fraisInscriptionCdf: 0,
            fraisAnnuelCdf: 0,
            fraisScolMensuelCdf: 0,
            fraisParTrancheCdf: 0,

            init() {
                this.fraisInscriptionCdf = this.usdToCdf(this.fraisInscriptionUsd);
                this.fraisAnnuelCdf = this.usdToCdf(this.fraisAnnuelUsd);
                this.fraisScolMensuelCdf = this.usdToCdf(this.fraisScolMensuelUsd);
                this.fraisParTrancheCdf = this.usdToCdf(this.fraisParTrancheUsd);
                this.recalculerFrais();
                this.$watch('modePaiement', () => this.recalculerFrais());
            },

            usdToCdf(usd) {
                return Math.round(parseFloat(usd || 0) * this.tauxChange);
            },
            cdfToUsd(cdf) {
                return Math.round(parseFloat(cdf || 0) / this.tauxChange * 100) / 100;
            },

            syncInscriptionUsd() { this.fraisInscriptionUsd = parseFloat(this.fraisInscriptionUsd || 0); this.fraisInscriptionCdf = this.usdToCdf(this.fraisInscriptionUsd); },
            syncInscriptionCdf() { this.fraisInscriptionCdf = parseFloat(this.fraisInscriptionCdf || 0); this.fraisInscriptionUsd = this.cdfToUsd(this.fraisInscriptionCdf); },
            syncAnnuelUsd() { this.fraisAnnuelUsd = parseFloat(this.fraisAnnuelUsd || 0); this.fraisAnnuelCdf = this.usdToCdf(this.fraisAnnuelUsd); this.recalculerFrais(); },
            syncAnnuelCdf() { this.fraisAnnuelCdf = parseFloat(this.fraisAnnuelCdf || 0); this.fraisAnnuelUsd = this.cdfToUsd(this.fraisAnnuelCdf); this.recalculerFrais(); },
            syncMensuelUsd() { this.fraisScolMensuelUsd = parseFloat(this.fraisScolMensuelUsd || 0); this.fraisScolMensuelCdf = this.usdToCdf(this.fraisScolMensuelUsd); },
            syncMensuelCdf() { this.fraisScolMensuelCdf = parseFloat(this.fraisScolMensuelCdf || 0); this.fraisScolMensuelUsd = this.cdfToUsd(this.fraisScolMensuelCdf); },
            syncTrancheUsd() { this.fraisParTrancheUsd = parseFloat(this.fraisParTrancheUsd || 0); this.fraisParTrancheCdf = this.usdToCdf(this.fraisParTrancheUsd); },
            syncTrancheCdf() { this.fraisParTrancheCdf = parseFloat(this.fraisParTrancheCdf || 0); this.fraisParTrancheUsd = this.cdfToUsd(this.fraisParTrancheCdf); },

            recalculerFrais() {
                const fraisAnnuel = parseFloat(this.fraisAnnuelUsd);
                if (fraisAnnuel <= 0) {
                    this.fraisScolMensuelUsd = 0;
                    this.fraisScolMensuelCdf = 0;
                    this.fraisParTrancheUsd = 0;
                    this.fraisParTrancheCdf = 0;
                    return;
                }

                if (this.modePaiement === 'mensuel') {
                    const nbMois = this.nombreMois || 0;
                    if (nbMois > 0) {
                        this.fraisScolMensuelUsd = (fraisAnnuel / nbMois).toFixed(2);
                        this.fraisScolMensuelCdf = this.usdToCdf(this.fraisScolMensuelUsd);
                    }
                    this.fraisParTrancheUsd = 0;
                    this.fraisParTrancheCdf = 0;
                } else {
                    const nbTranches = parseInt(this.nombreTranches) || 0;
                    if (nbTranches > 0) {
                        this.fraisParTrancheUsd = (fraisAnnuel / nbTranches).toFixed(2);
                        this.fraisParTrancheCdf = this.usdToCdf(this.fraisParTrancheUsd);
                    }
                    this.fraisScolMensuelUsd = 0;
                    this.fraisScolMensuelCdf = 0;
                }
            },

            validateStep1() {
                this.step = 2;
            },

            validateStep2() {
                this.step = 3;
            }
        }
    }
</script>
@endsection