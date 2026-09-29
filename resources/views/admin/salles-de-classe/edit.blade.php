@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="max-w-3xl mx-auto" x-data="salleForm()" x-init="init()">

    {{-- ============================================================
         EN-TÊTE
         ============================================================ --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Modifier <strong>{{ $salleDeClasse->nom }}</strong></h1>
            <p class="form-subtitle">Mettez à jour les informations, frais et paiements</p>
        </div>
        <a href="{{ route('admin.salles-de-classe.index') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    {{-- Alerte : pas d'année active --}}
    @if(!$anneeActive)
        <div class="alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>
                Aucune année scolaire active. Vous devez d'abord
                <a href="{{ route('admin.annees-scolaires.create') }}">créer une année scolaire</a>
                avant de modifier des salles.
            </span>
        </div>
    @endif

    {{-- Bannière d'erreurs globales --}}
    @if($errors->any())
        <div class="alert-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <div>
                <strong>Erreurs de validation :</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ============================================================
         STEPPER
         ============================================================ --}}
    <div class="stepper mb-6">
        <div class="flex items-center justify-between">
            @foreach(['Informations', 'Frais', 'Paiements'] as $label)
                <div class="flex items-center flex-1">
                    <div class="flex items-center gap-2">
                        <div class="step-circle" :class="step >= {{ $loop->index + 1 }} ? 'active' : ''">
                            <span x-show="step > {{ $loop->index + 1 }}" class="text-white">
                                <i class="bi bi-check-lg"></i>
                            </span>
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

    {{-- ============================================================
         FORMULAIRE
         ============================================================ --}}
    <form action="{{ route('admin.salles-de-classe.update', $salleDeClasse->id) }}"
          method="POST"
          id="salleForm"
          class="form-container"
          novalidate>
        @csrf
        @method('PUT')

        {{-- ============================================
             ÉTAPE 1 : Informations générales
             ============================================ --}}
        <div x-show="step === 1" x-transition:enter.duration.300ms>

            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-info-circle"></i> Informations générales</h3>

                <div class="form-row">
                    {{-- Nom --}}
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="text" name="nom" id="nom"
                                   value="{{ old('nom', $salleDeClasse->nom) }}"
                                   placeholder=" " required
                                   class="@error('nom') is-invalid @enderror">
                            <label for="nom" class="float-label">Nom <span class="required">*</span></label>
                            <i class="bi bi-door-open icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('nom')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    {{-- Section --}}
                    <div class="form-field">
                        <label for="section_id" class="field-label">Section <span class="required">*</span></label>
                        <div class="select-wrap">
                            <select name="section_id" id="section_id" required
                                    class="@error('section_id') is-invalid @enderror">
                                <option value="">Choisir une section</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}"
                                        {{ old('section_id', $salleDeClasse->section_id) == $section->id ? 'selected' : '' }}>
                                        {{ $section->nom }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down select-icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('section_id')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    {{-- Option --}}
                    <div class="form-field">
                        <label for="option_id" class="field-label">Option (facultatif)</label>
                        <div class="select-wrap">
                            <select name="option_id" id="option_id">
                                <option value="">Aucune</option>
                                @foreach($options as $option)
                                    <option value="{{ $option->id }}"
                                        {{ old('option_id', $salleDeClasse->option_id) == $option->id ? 'selected' : '' }}>
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
                            <input type="number" name="capacite_max" id="capacite_max"
                                   value="{{ old('capacite_max', $salleDeClasse->capacite_max) }}"
                                   min="1" placeholder=" " required
                                   class="@error('capacite_max') is-invalid @enderror">
                            <label for="capacite_max" class="float-label">Capacité max <span class="required">*</span></label>
                            <i class="bi bi-people icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('capacite_max')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="number" name="age_min" id="age_min"
                                   value="{{ old('age_min', $salleDeClasse->age_min) }}"
                                   min="0" placeholder=" " required
                                   class="@error('age_min') is-invalid @enderror">
                            <label for="age_min" class="float-label">Âge minimum <span class="required">*</span></label>
                            <i class="bi bi-arrow-down-circle icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('age_min')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="number" name="age_max" id="age_max"
                                   value="{{ old('age_max', $salleDeClasse->age_max) }}"
                                   min="0" placeholder=" " required
                                   class="@error('age_max') is-invalid @enderror">
                            <label for="age_max" class="float-label">Âge maximum <span class="required">*</span></label>
                            <i class="bi bi-arrow-up-circle icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('age_max')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="button-group">
                <a href="{{ route('admin.salles-de-classe.index') }}" class="btn-cancel">
                    <i class="bi bi-x-lg"></i> Annuler
                </a>
                <div class="flex gap-2">
                    <button type="button" class="btn-next" @click="goToStep(2)">
                        Suivant <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================
             ÉTAPE 2 : Frais
             ============================================ --}}
        <div x-show="step === 2" x-transition:enter.duration.300ms>

            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-currency-dollar"></i> Frais</h3>
                <p class="hint-text mb-4">
                    Taux de change : 1 USD = <span x-text="tauxChange"></span> CDF
                </p>

                {{-- Frais d'inscription --}}
                <div class="form-row">
                    <div class="form-field">
                        <label class="field-label">Frais d'inscription ($) <span class="required">*</span></label>
                        <div class="input-wrap">
                            <input type="number" step="0.01" name="frais_inscription" id="frais_inscription"
                                   x-model="fraisInscriptionUsd" @input="syncInscriptionUsd()"
                                   min="0" placeholder=" " required
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
                            <input type="number" step="1" x-model="fraisInscriptionCdf"
                                   @input="syncInscriptionCdf()" placeholder=" " class="bg-gray-50">
                            <label class="float-label">CDF</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>
                </div>

                {{-- Frais annuel --}}
                <div class="form-row mt-4">
                    <div class="form-field">
                        <label class="field-label">Frais annuel ($) <span class="required">*</span></label>
                        <div class="input-wrap">
                            <input type="number" step="0.01" name="frais_annuel" id="frais_annuel"
                                   x-model="fraisAnnuelUsd" @input="syncAnnuelUsd()"
                                   min="0" placeholder=" " required
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
                            <input type="number" step="1" x-model="fraisAnnuelCdf"
                                   @input="syncAnnuelCdf()" placeholder=" " class="bg-gray-50">
                            <label class="float-label">CDF</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="button-group">
                <button type="button" class="btn-prev" @click="goToStep(1)">
                    <i class="bi bi-arrow-left"></i> Précédent
                </button>
                <button type="button" class="btn-next" @click="goToStep(3)">
                    Suivant <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- ============================================
             ÉTAPE 3 : Configuration des paiements
             ============================================ --}}
        <div x-show="step === 3" x-transition:enter.duration.300ms>

            <div class="form-section">
                <h3 class="section-title"><i class="bi bi-credit-card"></i> Configuration des paiements</h3>

                <div class="form-row">
                    {{-- Mode de paiement --}}
                    <div class="form-field">
                        <label for="mode_paiement" class="field-label">
                            Mode de paiement <span class="required">*</span>
                        </label>
                        <div class="select-wrap">
                            <select name="mode_paiement" id="mode_paiement"
                                    x-model="modePaiement" @change="recalculerFrais()"
                                    class="@error('mode_paiement') is-invalid @enderror">
                                <option value="mensuel" {{ old('mode_paiement', $salleDeClasse->mode_paiement) == 'mensuel' ? 'selected' : '' }}>Mensuel</option>
                                <option value="tranche" {{ old('mode_paiement', $salleDeClasse->mode_paiement) == 'tranche' ? 'selected' : '' }}>Par tranche</option>
                            </select>
                            <i class="bi bi-chevron-down select-icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('mode_paiement')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    {{-- Nombre de mois --}}
                    <div class="form-field" x-show="modePaiement === 'mensuel'">
                        <label class="field-label">
                            Nombre de mois <span class="required">*</span>
                        </label>
                        <div class="input-wrap">
                            <input type="number" name="nombre_mois" id="nombre_mois"
                                   x-model.number="nombreMois" @input="recalculerFrais()"
                                   min="1" max="12" placeholder=" "
                                   class="@error('nombre_mois') is-invalid @enderror">
                            <label for="nombre_mois" class="float-label">Mois</label>
                            <i class="bi bi-calendar icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('nombre_mois')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    {{-- Nombre de tranches --}}
                    <div class="form-field" x-show="modePaiement === 'tranche'">
                        <label class="field-label">
                            Nombre de tranches <span class="required">*</span>
                        </label>
                        <div class="input-wrap">
                            <input type="number" name="nombre_tranches" id="nombre_tranches"
                                   x-model.number="nombreTranches" @input="recalculerFrais()"
                                   min="1" max="12" placeholder=" "
                                   class="@error('nombre_tranches') is-invalid @enderror">
                            <label for="nombre_tranches" class="float-label">Tranches</label>
                            <i class="bi bi-hash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('nombre_tranches')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Frais mensuel --}}
                <div class="form-row" x-show="modePaiement === 'mensuel'">
                    <div class="form-field">
                        <label class="field-label">Frais scolarité mensuel ($)</label>
                        <div class="input-wrap">
                            <input type="number" step="0.01"
                                   name="frais_scolarite_mensuel" id="frais_scolarite_mensuel"
                                   x-model="fraisScolMensuelUsd" @input="syncMensuelUsd()"
                                   min="0" placeholder=" "
                                   class="@error('frais_scolarite_mensuel') is-invalid @enderror">
                            <label for="frais_scolarite_mensuel" class="float-label">USD</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('frais_scolarite_mensuel')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label class="field-label">Équivalent en CDF</label>
                        <div class="input-wrap">
                            <input type="number" step="1" x-model="fraisScolMensuelCdf"
                                   @input="syncMensuelCdf()" placeholder=" " class="bg-gray-50">
                            <label class="float-label">CDF</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>
                </div>

                {{-- Frais par tranche --}}
                <div class="form-row" x-show="modePaiement === 'tranche'">
                    <div class="form-field">
                        <label class="field-label">Frais par tranche ($)</label>
                        <div class="input-wrap">
                            <input type="number" step="0.01"
                                   name="frais_par_tranche" id="frais_par_tranche"
                                   x-model="fraisParTrancheUsd" @input="syncTrancheUsd()"
                                   min="0" placeholder=" "
                                   class="@error('frais_par_tranche') is-invalid @enderror">
                            <label for="frais_par_tranche" class="float-label">USD</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('frais_par_tranche')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label class="field-label">Équivalent en CDF</label>
                        <div class="input-wrap">
                            <input type="number" step="1" x-model="fraisParTrancheCdf"
                                   @input="syncTrancheCdf()" placeholder=" " class="bg-gray-50">
                            <label class="float-label">CDF</label>
                            <i class="bi bi-cash icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>
                </div>

                <p class="hint-text">
                    Le montant périodique est calculé automatiquement (frais annuel ÷ nombre de mois/tranches).
                    Vous pouvez le modifier manuellement.
                </p>
            </div>

            <div class="form-section">
                <div class="form-row">
                    <div class="form-field">
                        <label for="salle_superieure_id" class="field-label">Salle supérieure (facultatif)</label>
                        <div class="select-wrap">
                            <select name="salle_superieure_id" id="salle_superieure_id">
                                <option value="">Aucune</option>
                                @foreach($salles as $s)
                                    <option value="{{ $s->id }}"
                                        {{ old('salle_superieure_id', $salleDeClasse->salle_superieure_id) == $s->id ? 'selected' : '' }}>
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
                                      class="@error('description') is-invalid @enderror">{{ old('description', $salleDeClasse->description) }}</textarea>
                            <label for="description" class="float-label">Description</label>
                            <i class="bi bi-card-text icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('description')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="button-group">
                <button type="button" class="btn-prev" @click="goToStep(2)">
                    <i class="bi bi-arrow-left"></i> Précédent
                </button>
                <button type="submit" class="btn-submit">
                    <i class="bi bi-check-lg"></i> Enregistrer les modifications
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    /* ============================================================
       VARIABLES
       ============================================================ */
    :root {
        --primary: #4f46e5;
        --primary-light: #667eea;
        --primary-gradient: linear-gradient(135deg, #4f46e5, #764ba2);
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-600: #475569;
        --gray-700: #334155;
        --gray-900: #0f172a;
        --danger: #ef4444;
        --success: #10b981;
        --warning: #f59e0b;
    }

    /* ============================================================
       EN-TÊTE
       ============================================================ */
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        gap: 1rem;
        flex-wrap: wrap;
        animation: fadeUp 0.6s 0.1s ease forwards;
        opacity: 0;
    }
    .form-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
    }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: var(--gray-400); font-size: 0.9rem; }
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--primary-light);
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: color 0.2s;
    }
    .back-link:hover { color: var(--primary); }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ============================================================
       ALERTES
       ============================================================ */
    .alert-warning,
    .alert-error {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }
    .alert-warning {
        background: #fffbeb;
        border: 1px solid #fcd34d;
        color: #92400e;
    }
    .alert-warning i { font-size: 1.2rem; flex-shrink: 0; }
    .alert-warning a {
        font-weight: 600;
        text-decoration: underline;
        color: #92400e;
    }
    .alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        animation: fadeUp 0.4s ease;
    }
    .alert-error i {
        font-size: 1.2rem;
        flex-shrink: 0;
        margin-top: 0.15rem;
    }
    .alert-error ul {
        margin: 0.5rem 0 0;
        padding-left: 1.25rem;
        list-style: disc;
    }
    .alert-error li { margin: 0.15rem 0; }

    /* ============================================================
       FORMULAIRE
       ============================================================ */
    .form-container {
        background: white;
        padding: 2rem 2.5rem;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
        opacity: 0;
    }

    /* Stepper */
    .stepper {
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid var(--gray-100);
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
        background: var(--gray-100);
        color: var(--gray-400);
        transition: all 0.3s;
        border: 2px solid var(--gray-200);
        flex-shrink: 0;
    }
    .step-circle.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
    }
    .step-label {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--gray-900);
        margin-left: 6px;
    }
    .step-line {
        flex: 1;
        height: 2px;
        background: var(--gray-200);
        margin: 0 12px;
        transition: background 0.3s;
    }
    .step-line.active { background: var(--primary); }

    /* Sections */
    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--gray-100);
    }
    .form-section:last-of-type { border-bottom: none; }
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { color: var(--primary-light); }

    /* Grilles */
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

    /* Champs */
    .form-field { position: relative; }
    .field-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--gray-900);
        margin-bottom: 0.5rem;
    }
    .required { color: var(--danger); }

    .input-wrap, .select-wrap, .textarea-wrap { position: relative; }
    .input-wrap input,
    .select-wrap select,
    .textarea-wrap textarea {
        width: 100%;
        padding: 0.8rem 2.5rem 0.8rem 0;
        border: none;
        border-bottom: 2px solid var(--gray-200);
        background: transparent;
        font-size: 1rem;
        color: var(--gray-900);
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none;
        appearance: none;
        min-height: 42px;
        font-family: inherit;
    }
    .input-wrap input:focus,
    .select-wrap select:focus,
    .textarea-wrap textarea:focus {
        border-bottom-color: var(--primary-light);
    }
    .input-wrap input.is-invalid,
    .select-wrap select.is-invalid,
    .textarea-wrap textarea.is-invalid {
        border-bottom-color: var(--danger);
    }
    .input-wrap .float-label,
    .select-wrap .float-label,
    .textarea-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.8rem;
        color: var(--gray-400);
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
        color: var(--primary-light);
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap i.icon,
    .select-wrap .select-icon,
    .textarea-wrap i.icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.1rem;
        pointer-events: none;
        transition: color 0.3s;
    }
    .input-wrap input:focus ~ i.icon,
    .select-wrap select:focus ~ .select-icon,
    .textarea-wrap textarea:focus ~ i.icon {
        color: var(--primary-light);
    }
    .input-wrap .line-focus,
    .select-wrap .line-focus,
    .textarea-wrap .line-focus {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: var(--primary-gradient);
        transition: all 0.4s cubic-bezier(0.4,0,0.2,1);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .input-wrap input:focus ~ .line-focus,
    .select-wrap select:focus ~ .line-focus,
    .textarea-wrap textarea:focus ~ .line-focus {
        width: 100%;
    }

    .error-text {
        color: var(--danger);
        font-size: 0.8rem;
        margin-top: 0.3rem;
    }
    .hint-text {
        color: var(--gray-400);
        font-size: 0.75rem;
        margin-top: 0.5rem;
    }
    .bg-gray-50 { background-color: var(--gray-50); }

    /* Boutons */
    .button-group {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.5rem;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
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
        font-family: inherit;
    }
    .btn-submit, .btn-next {
        background: var(--gray-900);
        color: white;
    }
    .btn-submit:hover, .btn-next:hover {
        background: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    .btn-prev {
        background: var(--gray-100);
        color: var(--gray-600);
    }
    .btn-prev:hover {
        background: var(--gray-200);
        transform: translateY(-2px);
    }
    .btn-cancel {
        padding: 0.7rem 1.5rem;
        border: 1.5px solid var(--gray-200);
        border-radius: 12px;
        color: var(--gray-600);
        background: white;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: inherit;
    }
    .btn-cancel:hover {
        border-color: var(--primary-light);
        color: var(--primary-light);
        background: var(--gray-50);
    }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 768px) {
        .header-container { flex-direction: column; align-items: flex-start; }
        .form-container { padding: 1.5rem; }
        .form-row.three-col { grid-template-columns: 1fr; }
        .stepper { padding: 0.75rem 1rem; }
        .step-label { display: none; }
        .button-group { flex-direction: column-reverse; }
        .btn-submit, .btn-cancel, .btn-next, .btn-prev {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    function salleForm() {
        return {
            step: 1,
            modePaiement: '{{ old('mode_paiement', $salleDeClasse->mode_paiement ?? 'mensuel') }}',
            tauxChange: {{ (float) ($tauxChange ?? 2800) }},
            nombreMois: Number({{ old('nombre_mois', $salleDeClasse->nombre_mois ?? 10) }}),
            nombreTranches: Number({{ old('nombre_tranches', $salleDeClasse->nombre_tranches ?? 3) }}),

            // Valeurs USD initiales (avec fallback pour éviter les null)
            fraisInscriptionUsd: {{ (float) old('frais_inscription', $salleDeClasse->frais_inscription ?? 0) }},
            fraisAnnuelUsd: {{ (float) old('frais_annuel', $salleDeClasse->frais_annuel ?? 0) }},
            fraisScolMensuelUsd: {{ (float) old('frais_scolarite_mensuel', $salleDeClasse->frais_scolarite_mensuel ?? 0) }},
            fraisParTrancheUsd: {{ (float) old('frais_par_tranche', $salleDeClasse->frais_par_tranche ?? 0) }},

            // Valeurs CDF (calculées)
            fraisInscriptionCdf: 0,
            fraisAnnuelCdf: 0,
            fraisScolMensuelCdf: 0,
            fraisParTrancheCdf: 0,

            init() {
                // Initialiser les CDF à partir des USD
                this.fraisInscriptionCdf  = this.usdToCdf(this.fraisInscriptionUsd);
                this.fraisAnnuelCdf       = this.usdToCdf(this.fraisAnnuelUsd);
                this.fraisScolMensuelCdf  = this.usdToCdf(this.fraisScolMensuelUsd);
                this.fraisParTrancheCdf   = this.usdToCdf(this.fraisParTrancheUsd);

                // Détecter l'étape en erreur (si erreurs de validation au retour)
                @if($errors->any())
                    const errorFields = @json(array_keys($errors->toArray()));

                    // Champs de l'étape 1
                    const step1Fields = ['nom', 'section_id', 'option_id', 'capacite_max', 'age_min', 'age_max'];
                    // Champs de l'étape 2
                    const step2Fields = ['frais_inscription', 'frais_annuel'];
                    // Champs de l'étape 3
                    const step3Fields = ['mode_paiement', 'nombre_mois', 'nombre_tranches',
                                        'frais_scolarite_mensuel', 'frais_par_tranche',
                                        'salle_superieure_id', 'description'];

                    if (errorFields.some(f => step1Fields.includes(f))) {
                        this.step = 1;
                    } else if (errorFields.some(f => step2Fields.includes(f))) {
                        this.step = 2;
                    } else if (errorFields.some(f => step3Fields.includes(f))) {
                        this.step = 3;
                    }

                    // Scroll en haut pour voir la bannière d'erreurs
                    this.$nextTick(() => {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                @endif
            },

            goToStep(n) {
                this.step = n;
                this.$nextTick(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            },

            usdToCdf(usd) {
                return Math.round(parseFloat(usd || 0) * this.tauxChange);
            },

            cdfToUsd(cdf) {
                const usd = parseFloat(cdf || 0) / this.tauxChange;
                return Math.round(usd * 100) / 100;
            },

            // === Sync inputs USD/CDF ===
            syncInscriptionUsd() {
                this.fraisInscriptionUsd = parseFloat(this.fraisInscriptionUsd || 0);
                this.fraisInscriptionCdf = this.usdToCdf(this.fraisInscriptionUsd);
            },
            syncInscriptionCdf() {
                this.fraisInscriptionCdf = parseFloat(this.fraisInscriptionCdf || 0);
                this.fraisInscriptionUsd = this.cdfToUsd(this.fraisInscriptionCdf);
            },
            syncAnnuelUsd() {
                this.fraisAnnuelUsd = parseFloat(this.fraisAnnuelUsd || 0);
                this.fraisAnnuelCdf = this.usdToCdf(this.fraisAnnuelUsd);
                this.recalculerFrais();
            },
            syncAnnuelCdf() {
                this.fraisAnnuelCdf = parseFloat(this.fraisAnnuelCdf || 0);
                this.fraisAnnuelUsd = this.cdfToUsd(this.fraisAnnuelCdf);
                this.recalculerFrais();
            },
            syncMensuelUsd() {
                this.fraisScolMensuelUsd = parseFloat(this.fraisScolMensuelUsd || 0);
                this.fraisScolMensuelCdf = this.usdToCdf(this.fraisScolMensuelUsd);
            },
            syncMensuelCdf() {
                this.fraisScolMensuelCdf = parseFloat(this.fraisScolMensuelCdf || 0);
                this.fraisScolMensuelUsd = this.cdfToUsd(this.fraisScolMensuelCdf);
            },
            syncTrancheUsd() {
                this.fraisParTrancheUsd = parseFloat(this.fraisParTrancheUsd || 0);
                this.fraisParTrancheCdf = this.usdToCdf(this.fraisParTrancheUsd);
            },
            syncTrancheCdf() {
                this.fraisParTrancheCdf = parseFloat(this.fraisParTrancheCdf || 0);
                this.fraisParTrancheUsd = this.cdfToUsd(this.fraisParTrancheCdf);
            },

            // === Recalcul automatique des frais périodiques ===
            recalculerFrais() {
                const fraisAnnuel = parseFloat(this.fraisAnnuelUsd || 0);

                if (fraisAnnuel <= 0) {
                    this.fraisScolMensuelUsd = 0;
                    this.fraisScolMensuelCdf = 0;
                    this.fraisParTrancheUsd = 0;
                    this.fraisParTrancheCdf = 0;
                    return;
                }

                if (this.modePaiement === 'mensuel') {
                    const nbMois = parseInt(this.nombreMois) || 0;
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
            }
        }
    }
</script>
@endsection