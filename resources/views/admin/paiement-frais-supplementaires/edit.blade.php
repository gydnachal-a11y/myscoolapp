@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

{{-- Messages flash --}}
@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
        <i class="fa-regular fa-check-circle mt-0.5"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
        <i class="fa-regular fa-circle-exclamation mt-0.5"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif
@if ($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="paiement-page" x-data="paiementFraisForm()" x-init="init()">

    {{-- EN-TÊTE --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Modifier <strong>paiement frais</strong></h1>
            <p class="form-subtitle">
                <i class="fa-solid fa-receipt text-indigo-500 mr-1"></i>
                {{ $paiement->eleve?->nom_complet ?? '—' }} ·
                {{ $paiement->fraisSupplementaire?->libelle ?? '—' }}
            </p>
        </div>
        <a href="{{ route('admin.paiement-frais-supplementaires.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour aux paiements
        </a>
    </div>

    {{-- PROGRESSION --}}
    <div class="progress-container">
        <div class="progress-steps">
            <template x-for="(step, index) in steps" :key="index">
                <div class="progress-step" :class="{ 'active': currentStep >= index + 1, 'completed': currentStep > index + 1 }">
                    <button type="button" @click="goToStep(index + 1)"
                            :disabled="index + 1 > currentStep + 1"
                            class="step-circle"
                            :class="{
                                'bg-indigo-600 text-white shadow-lg scale-110 cursor-pointer': currentStep >= index + 1,
                                'bg-white text-gray-400 border-2 border-gray-200 cursor-pointer': currentStep < index + 1 && index + 1 <= currentStep + 1,
                                'bg-gray-100 text-gray-300 border-2 border-gray-100 cursor-not-allowed': index + 1 > currentStep + 1
                            }">
                        <span x-text="index + 1"></span>
                    </button>
                    <div class="step-line" x-show="index < steps.length - 1"
                         :class="currentStep > index + 1 ? 'bg-indigo-600' : 'bg-gray-200'"></div>
                </div>
            </template>
        </div>
        <div class="progress-labels">
            <span :class="currentStep >= 1 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Frais & Salle</span>
            <span :class="currentStep >= 2 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Élève & Montant</span>
            <span :class="currentStep >= 3 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Confirmation</span>
        </div>
    </div>

    {{-- FORMULAIRE --}}
    <form action="{{ route('admin.paiement-frais-supplementaires.update', $paiement) }}"
          method="POST" id="paiementForm">
        @csrf
        @method('PUT')

        {{-- Inputs cachés --}}
        <input type="hidden" name="frais_supplementaire_id" x-model="selectedFraisId">
        <input type="hidden" name="salle_classe_id" x-model="selectedSalleId">
        <input type="hidden" name="eleve_id" :value="selectedEleveId">
        <input type="hidden" name="montant_paye_usd" x-model="montantPaye">
        <input type="hidden" name="montant_paye_fc" x-model="montantPayeFC">

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">

                {{-- ════════════════════════════════════════════ --}}
                {{-- ÉTAPE 1 : Frais & Salle --}}
                {{-- ════════════════════════════════════════════ --}}
                <div x-show="currentStep === 1" class="form-card">
                    <h2 class="card-title">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        Frais et salle
                    </h2>

                    <div class="form-row">
                        <div class="form-field col-span-2">
                            <label for="frais_select" class="field-label">
                                Frais supplémentaire <span class="text-red-500">*</span>
                                <span class="badge-count-inline" x-text="fraisData.length + ' disponible(s)'"></span>
                            </label>
                            <div class="select-wrap">
                                <select id="frais_select" x-model="selectedFraisId" @change="onFraisChange()"
                                        class="@error('frais_supplementaire_id') is-invalid @enderror" required>
                                    <option value="">— Choisir un frais —</option>
                                    <template x-for="f in fraisData" :key="f.id">
                                        <option :value="f.id"
                                                x-text="f.libelle + ' · ' + formatMontant(f.montant) + ' $'"></option>
                                    </template>
                                </select>
                                <i class="fa-solid fa-chevron-down select-icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('frais_supplementaire_id')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Rappel du frais sélectionné --}}
                    <template x-if="fraisSelectionne">
                        <div class="info-banner">
                            <div class="info-banner-icon">
                                <i class="fa-solid fa-tag"></i>
                            </div>
                            <div class="info-banner-body">
                                <p class="info-banner-title" x-text="fraisSelectionne.libelle"></p>
                                <p class="info-banner-text">
                                    <span x-text="formatMontant(fraisSelectionne.montant) + ' $'"></span>
                                    <span class="text-muted">·</span>
                                    <span class="text-muted" x-text="formatMontant(fraisSelectionne.montant * tauxChange) + ' FC'"></span>
                                    <span class="text-muted">·</span>
                                    <span x-text="fraisSelectionne.est_pour_toutes_salles
                                        ? 'Toutes les salles'
                                        : fraisSelectionne.salles_ids.length + ' salle(s) concernée(s)'"></span>
                                </p>
                            </div>
                        </div>
                    </template>

                    {{-- Filtres salles --}}
                    <template x-if="selectedFraisId && sallesDisponibles.length > 0">
                        <div class="filters-row">
                            <div class="filter-field">
                                <label for="filter_section" class="filter-label-sm">
                                    <i class="fa-solid fa-layer-group"></i> Section
                                </label>
                                <select id="filter_section" x-model="sectionFilter" class="filter-select-sm">
                                    <option value="">Toutes les sections</option>
                                    <template x-for="s in sectionsData" :key="s.id">
                                        <option :value="s.id" x-text="s.nom"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="filter-field filter-field-grow">
                                <label for="filter_salle" class="filter-label-sm">
                                    <i class="fa-solid fa-magnifying-glass"></i> Recherche
                                </label>
                                <input type="text" id="filter_salle" x-model="salleSearch"
                                       placeholder="Nom de la salle..."
                                       class="filter-input-sm">
                            </div>
                        </div>
                    </template>

                    {{-- Select salle --}}
                    <template x-if="selectedFraisId">
                        <div class="form-row mt-4">
                            <div class="form-field col-span-2">
                                <label for="salle_select" class="field-label">
                                    Salle de classe <span class="text-red-500">*</span>
                                    <span class="badge-count-inline"
                                          x-text="filteredSalles.length + ' / ' + sallesDisponibles.length"></span>
                                </label>
                                <div class="select-wrap">
                                    <select id="salle_select" x-model="selectedSalleId"
                                            @change="onSalleChange()"
                                            :disabled="filteredSalles.length === 0"
                                            class="@error('salle_classe_id') is-invalid @enderror" required>
                                        <option value="">— Choisir une salle —</option>
                                        <template x-for="salle in filteredSalles" :key="salle.id">
                                            <option :value="salle.id" x-text="salle.nom"></option>
                                        </template>
                                    </select>
                                    <i class="fa-solid fa-chevron-down select-icon"></i>
                                    <div class="line-focus"></div>
                                </div>
                                @error('salle_classe_id')<p class="error-text">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </template>

                    <div class="flex justify-between mt-6">
                        <a href="{{ route('admin.paiement-frais-supplementaires.index') }}" class="btn-cancel">
                            <i class="fa-solid fa-arrow-left"></i> Annuler
                        </a>
                        <button type="button" @click="validateStep1()"
                                :disabled="!selectedFraisId || !selectedSalleId"
                                :class="(selectedFraisId && selectedSalleId) ? 'btn-submit' : 'btn-disabled'">
                            Continuer <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ════════════════════════════════════════════ --}}
                {{-- ÉTAPE 2 : Élève & Montant --}}
                {{-- ════════════════════════════════════════════ --}}
                <div x-show="currentStep === 2" class="form-card">
                    <h2 class="card-title">
                        <i class="fa-solid fa-user-graduate"></i>
                        Élève et montant
                    </h2>

                    {{-- Rappel --}}
                    <div class="info-banner info-banner-light">
                        <div class="info-banner-icon info-banner-icon-light">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div class="info-banner-body">
                            <p class="info-banner-title" x-text="fraisSelectionne?.libelle ?? ''"></p>
                            <p class="info-banner-text">
                                <i class="fa-solid fa-school"></i> <span x-text="selectedSalleNom || '—'"></span>
                                <span class="text-muted">·</span>
                                <span x-text="formatMontant(montantFrais) + ' $'"></span>
                            </p>
                        </div>
                    </div>

                    {{-- Filtre élèves --}}
                    <div class="filters-row mb-4">
                        <div class="filter-field filter-field-grow">
                            <label for="filter_eleve" class="filter-label-sm">
                                <i class="fa-solid fa-magnifying-glass"></i> Rechercher un élève
                            </label>
                            <input type="text" id="filter_eleve" x-model="eleveSearch"
                                   placeholder="Nom, postnom ou prénom..."
                                   class="filter-input-sm">
                        </div>
                    </div>

                    <div class="form-row">
                        {{-- Élève --}}
                        <div class="form-field">
                            <label for="eleve_select" class="field-label">
                                Élève <span class="text-red-500">*</span>
                                <span class="badge-count-inline"
                                      x-text="filteredEleves.length + ' / ' + elevesDisponibles.length"></span>
                            </label>
                            <div class="select-wrap">
                                <select id="eleve_select" x-model="selectedEleveId"
                                        @change="onEleveChange()"
                                        :disabled="filteredEleves.length === 0"
                                        class="@error('eleve_id') is-invalid @enderror" required>
                                    <option value="">— Choisir un élève —</option>
                                    <template x-for="eleve in filteredEleves" :key="eleve.id">
                                        <option :value="eleve.id" x-text="eleve.nom_complet"></option>
                                    </template>
                                </select>
                                <i class="fa-solid fa-chevron-down select-icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('eleve_id')<p class="error-text">{{ $message }}</p>@enderror

                            <template x-if="filteredEleves.length === 0">
                                <p class="error-text">
                                    <i class="fa-solid fa-inbox"></i>
                                    Aucun élève trouvé pour cette salle.
                                </p>
                            </template>
                        </div>

                        {{-- Montant USD --}}
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="number" step="0.01" min="0"
                                       x-model="montantPaye" @input="syncUSDToFC()"
                                       placeholder=" " required
                                       :max="montantFrais"
                                       :class="{'is-invalid': montantPaye > montantFrais || montantPaye <= 0}">
                                <label class="float-label">Montant payé (USD) <span class="text-red-500">*</span></label>
                                <i class="fa-solid fa-dollar-sign icon"></i>
                                <div class="line-focus"></div>
                            </div>

                            <template x-if="montantPaye > montantFrais">
                                <p class="error-text">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    Le montant ne peut pas dépasser
                                    <span x-text="formatMontant(montantFrais)"></span> $.
                                </p>
                            </template>

                            <template x-if="montantPaye > 0 && montantPaye <= montantFrais">
                                <p class="text-success">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span x-text="formatMontant(montantPayeFC) + ' FC'"></span>
                                </p>
                            </template>

                            @error('montant_paye_usd')<p class="error-text">{{ $message }}</p>@enderror

                            <div class="btn-row-inline">
                                <button type="button" @click="payerTout()" class="btn-small">
                                    <i class="fa-solid fa-check-double"></i> Payer tout
                                </button>
                                <button type="button" @click="payerMoitie()" class="btn-small">
                                    <i class="fa-solid fa-divide"></i> Moitié
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Montant FC --}}
                    <div class="form-field mt-4">
                        <div class="input-wrap">
                            <input type="number" step="1" min="0"
                                   x-model="montantPayeFC" @input="syncFCToUSD()"
                                   placeholder=" " class="bg-gray-50">
                            <label class="float-label">Montant payé (FC)</label>
                            <i class="fa-solid fa-franc-sign icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        <small class="text-muted">
                            Taux : <span x-text="formatMontant(tauxChange)"></span> FC/USD
                        </small>
                    </div>

                    {{-- Commentaire --}}
                    <div class="form-field mt-4">
                        <div class="textarea-wrap">
                            <textarea name="commentaire" rows="2" placeholder=" "
                                      x-model="commentaire"
                                      class="@error('commentaire') is-invalid @enderror"></textarea>
                            <label class="float-label">Commentaire (optionnel)</label>
                            <i class="fa-regular fa-comment-dots icon"></i>
                            <div class="line-focus"></div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" @click="currentStep = 1" class="btn-cancel">
                            <i class="fa-solid fa-arrow-left"></i> Retour
                        </button>
                        <button type="button" @click="validateStep2()" class="btn-submit">
                            Continuer <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ════════════════════════════════════════════ --}}
                {{-- ÉTAPE 3 : Confirmation --}}
                {{-- ════════════════════════════════════════════ --}}
                <div x-show="currentStep === 3" class="form-card">
                    <h2 class="card-title">
                        <i class="fa-solid fa-check-circle"></i>
                        Confirmation
                    </h2>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="summary-card">
                            <span>Frais</span>
                            <p x-text="fraisSelectionne?.libelle ?? '—'"></p>
                        </div>
                        <div class="summary-card">
                            <span>Salle</span>
                            <p x-text="selectedSalleNom || '—'"></p>
                        </div>
                        <div class="summary-card">
                            <span>Élève</span>
                            <p x-text="nomEleve || '—'"></p>
                        </div>
                        <div class="summary-card summary-card-highlight">
                            <span>Montant payé</span>
                            <p x-text="formatMontant(montantPaye) + ' USD'"></p>
                            <small x-text="formatMontant(montantPayeFC) + ' FC'"></small>
                        </div>
                    </div>

                    <div x-show="commentaire" class="mb-4">
                        <span class="text-sm text-gray-600">Commentaire :</span>
                        <p class="text-sm" x-text="commentaire"></p>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" @click="currentStep = 2" class="btn-cancel">
                            <i class="fa-solid fa-arrow-left"></i> Retour
                        </button>
                        <button type="submit" class="btn-submit blue" :disabled="!canSubmit">
                            <i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </div>

            {{-- PANNEAU LATÉRAL --}}
            <div class="space-y-6">
                <div class="selected-eleve-card">
                    <div class="selected-eleve-avatar">
                        <i class="fa-solid fa-money-check-dollar text-2xl"></i>
                    </div>
                    <div>
                        <p class="selected-eleve-name">Modifier le paiement</p>
                        <p class="selected-eleve-age">Étape <span x-text="currentStep"></span> / 3</p>
                    </div>
                </div>

                <div class="financial-card">
                    <h3 class="financial-title">Détails du paiement</h3>

                    <div class="financial-row">
                        <span>Frais</span>
                        <strong x-text="fraisSelectionne?.libelle ?? '—'"></strong>
                    </div>
                    <div class="financial-row">
                        <span>Montant du frais</span>
                        <strong x-text="montantFrais ? formatMontant(montantFrais) + ' $' : '—'"></strong>
                    </div>
                    <div class="financial-row muted">
                        <span>Équivalent FC</span>
                        <span x-text="montantFrais ? formatMontant(montantFrais * tauxChange) + ' FC' : '—'"></span>
                    </div>
                    <div class="financial-row">
                        <span>Salle</span>
                        <strong x-text="selectedSalleNom || '—'"></strong>
                    </div>
                    <div class="financial-row">
                        <span>Élève</span>
                        <strong x-text="nomEleve || '—'"></strong>
                    </div>

                    <hr class="financial-divider">

                    <div class="financial-row financial-row-highlight">
                        <span>Montant payé</span>
                        <strong class="text-success"
                                x-text="montantPaye ? formatMontant(montantPaye) + ' $' : '—'"></strong>
                    </div>
                    <div class="financial-row muted">
                        <span>Équivalent FC</span>
                        <span x-text="montantPayeFC ? formatMontant(montantPayeFC) + ' FC' : '—'"></span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .paiement-page { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .form-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.2rem; letter-spacing: -0.5px; }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: #94a3b8; font-size: 0.9rem; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: #667eea; font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: color 0.2s; }
    .back-link:hover { color: #4f46e5; }

    .progress-container { margin-bottom: 2rem; }
    .progress-steps { display: flex; align-items: center; gap: 0.5rem; }
    .progress-step { display: flex; align-items: center; flex: 1; }
    .step-circle { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; transition: all 0.3s; border: none; outline: none; }
    .step-line { flex: 1; height: 3px; border-radius: 2px; margin: 0 8px; transition: background-color 0.3s; }
    .progress-labels { display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.8rem; }

    .form-card { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
    .card-title { font-size: 1.2rem; font-weight: 600; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px; }
    .card-title i { color: #667eea; }

    .form-row { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
    @media (min-width: 768px) { .form-row { grid-template-columns: 1fr 1fr; } }
    .col-span-2 { grid-column: span 2; }
    @media (max-width: 768px) { .col-span-2 { grid-column: span 1; } }

    .form-field { position: relative; }
    .field-label { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem; }

    .badge-count-inline { display: inline-flex; align-items: center; background: #eef2ff; color: #4f46e5; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.5rem; border-radius: 9999px; letter-spacing: 0.3px; }

    .input-wrap, .select-wrap { position: relative; }
    .input-wrap input, .select-wrap select { width: 100%; padding: 0.8rem 2.5rem 0.8rem 0; border: none; border-bottom: 2px solid #e2e8f0; background: transparent; font-size: 1rem; color: #1e293b; font-weight: 500; transition: border-color 0.3s; outline: none; -webkit-appearance: none; -moz-appearance: none; appearance: none; }
    .input-wrap input::placeholder { color: transparent; }
    .input-wrap input:focus, .select-wrap select:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid, .select-wrap select.is-invalid { border-bottom-color: #ef4444; }
    .select-wrap select:disabled { background: #f8fafc; cursor: not-allowed; opacity: 0.6; }

    .input-wrap .float-label { position: absolute; left: 0; top: 0.8rem; color: #94a3b8; font-size: 1rem; pointer-events: none; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label { top: -0.6rem; font-size: 0.72rem; font-weight: 700; color: #667eea; letter-spacing: 0.5px; text-transform: uppercase; }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label { color: #ef4444; }

    .input-wrap i.icon, .select-wrap .select-icon { position: absolute; right: 0; top: 50%; transform: translateY(-50%); color: #cbd5e1; font-size: 1.1rem; transition: all 0.3s; pointer-events: none; }
    .input-wrap input:focus ~ i.icon, .select-wrap select:focus ~ .select-icon { color: #667eea; transform: translateY(-50%) scale(1.1); }

    .input-wrap .line-focus, .select-wrap .line-focus { position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: linear-gradient(90deg, #667eea, #764ba2); transition: all 0.4s cubic-bezier(0.4,0,0.2,1); transform: translateX(-50%); pointer-events: none; }
    .input-wrap input:focus ~ .line-focus, .select-wrap select:focus ~ .line-focus { width: 100%; }

    .textarea-wrap { position: relative; }
    .textarea-wrap textarea { width: 100%; padding: 0.8rem 2.5rem 0.8rem 0; border: none; border-bottom: 2px solid #e2e8f0; background: transparent; font-size: 1rem; color: #1e293b; font-weight: 500; transition: border-color 0.3s; outline: none; resize: vertical; min-height: 40px; }
    .textarea-wrap textarea::placeholder { color: transparent; }
    .textarea-wrap textarea:focus { border-bottom-color: #667eea; }
    .textarea-wrap textarea.is-invalid { border-bottom-color: #ef4444; }
    .textarea-wrap .float-label { position: absolute; left: 0; top: 0.8rem; color: #94a3b8; font-size: 1rem; pointer-events: none; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
    .textarea-wrap textarea:focus ~ .float-label,
    .textarea-wrap textarea:not(:placeholder-shown) ~ .float-label { top: -0.6rem; font-size: 0.72rem; font-weight: 700; color: #667eea; letter-spacing: 0.5px; text-transform: uppercase; }
    .textarea-wrap i.icon { position: absolute; right: 0; top: 0.8rem; transform: translateY(-50%); color: #cbd5e1; font-size: 1.1rem; transition: all 0.3s; pointer-events: none; }
    .textarea-wrap textarea:focus ~ i.icon { color: #667eea; transform: translateY(-50%) scale(1.1); }
    .textarea-wrap .line-focus { position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: linear-gradient(90deg, #667eea, #764ba2); transition: all 0.4s cubic-bezier(0.4,0,0.2,1); transform: translateX(-50%); pointer-events: none; }
    .textarea-wrap textarea:focus ~ .line-focus { width: 100%; }

    .filters-row { display: flex; flex-wrap: wrap; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 12px; border: 1px dashed #e2e8f0; margin-top: 1rem; }
    .filter-field { display: flex; flex-direction: column; gap: 0.35rem; min-width: 180px; }
    .filter-field-grow { flex: 1; min-width: 220px; }
    .filter-label-sm { font-size: 0.7rem; font-weight: 700; color: #475569; letter-spacing: 0.3px; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
    .filter-label-sm i { color: #667eea; font-size: 0.75rem; }
    .filter-input-sm, .filter-select-sm { width: 100%; padding: 0.55rem 0.9rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: white; font-size: 0.9rem; color: #1e293b; transition: all 0.2s; outline: none; font-weight: 500; }
    .filter-input-sm:focus, .filter-select-sm:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }

    .info-banner { display: flex; align-items: center; gap: 0.9rem; padding: 0.9rem 1.1rem; background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%); border: 1px solid #c7d2fe; border-radius: 14px; margin-top: 1rem; }
    .info-banner-light { background: #f8fafc; border-color: #e2e8f0; margin-top: 0; margin-bottom: 1rem; }
    .info-banner-icon { width: 40px; height: 40px; border-radius: 10px; background: #4f46e5; color: white; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
    .info-banner-icon-light { background: #eef2ff; color: #4f46e5; }
    .info-banner-body { min-width: 0; }
    .info-banner-title { font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 0.15rem; }
    .info-banner-text { font-size: 0.85rem; color: #64748b; display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; }

    .btn-submit, .btn-cancel, .btn-disabled {
        padding: 0.8rem 1.75rem; border-radius: 12px;
        font-weight: 600; font-size: 0.95rem;
        display: inline-flex; align-items: center; justify-content: center;
        gap: 8px; cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        outline: none; border: none; text-decoration: none;
    }
    .btn-submit { background: #1e293b; color: white; }
    .btn-submit:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-submit.blue { background: #2563eb; }
    .btn-submit.blue:hover { background: #1d4ed8; box-shadow: 0 8px 16px rgba(37,99,235,0.3); }
    .btn-submit:disabled, .btn-disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-cancel { background: white; border: 1.5px solid #e2e8f0; color: #64748b; }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    .btn-row-inline { display: flex; gap: 0.5rem; margin-top: 0.5rem; flex-wrap: wrap; }
    .btn-small {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 0.3rem 0.75rem; border-radius: 8px;
        font-size: 0.75rem; font-weight: 600;
        background: #eef2ff; color: #4f46e5;
        border: 1px solid #c7d2fe; cursor: pointer;
        transition: all 0.2s;
    }
    .btn-small:hover { background: #e0e7ff; transform: translateY(-1px); }

    .summary-card { background: #f8fafc; border-radius: 12px; padding: 1rem; border: 1px solid #f1f5f9; }
    .summary-card span { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
    .summary-card p { font-weight: 700; color: #1e293b; margin-top: 0.25rem; font-size: 1rem; }
    .summary-card-highlight { background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); border-color: #bbf7d0; }
    .summary-card-highlight p { color: #15803d; }
    .summary-card-highlight small { color: #16a34a; font-weight: 600; }

    .selected-eleve-card { background: linear-gradient(135deg, #2563eb, #7c3aed); border-radius: 20px; padding: 1.5rem; color: white; display: flex; align-items: center; gap: 1rem; box-shadow: 0 20px 40px rgba(37,99,235,0.3); }
    .selected-eleve-avatar { width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .selected-eleve-name { font-weight: 700; font-size: 1.1rem; }
    .selected-eleve-age { color: #e0e7ff; font-size: 0.9rem; }

    .financial-card { background: white; border-radius: 20px; padding: 1.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
    .financial-title { font-size: 1rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem; }
    .financial-row { display: flex; justify-content: space-between; font-size: 0.9rem; padding: 0.5rem 0; gap: 1rem; }
    .financial-row strong { text-align: right; overflow: hidden; text-overflow: ellipsis; }
    .financial-row.muted { color: #64748b; font-size: 0.8rem; }
    .financial-row-highlight { border-top: 1px solid #e2e8f0; padding-top: 0.75rem; margin-top: 0.5rem; }
    .financial-divider { border: none; border-top: 1px solid #e2e8f0; margin: 0.75rem 0; }

    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.4rem; display: flex; align-items: center; gap: 5px; }
    .text-success { color: #16a34a; font-size: 0.8rem; margin-top: 0.3rem; font-weight: 600; display: flex; align-items: center; gap: 5px; }
    .text-muted { font-size: 0.8rem; color: #64748b; }
    .bg-gray-50 { background-color: #f8fafc; }

    @media (max-width: 768px) {
        .paiement-page { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .header-container { flex-direction: column; align-items: flex-start; gap: 0.5rem; margin-bottom: 1rem; }
        .form-title { font-size: 1.3rem; }
        .progress-container { margin-bottom: 1rem; }
        .progress-labels { font-size: 0.65rem; }
        .form-card { padding: 1.25rem; }
        .form-row { grid-template-columns: 1fr; }
        .col-span-2 { grid-column: span 1; }
        .btn-submit, .btn-cancel { width: 100%; }
        .filters-row { flex-direction: column; padding: 0.75rem; }
        .filter-field, .filter-field-grow { flex: 1 1 100%; min-width: 100%; }
    }
</style>

<script>
    function paiementFraisForm() {
        return {
            /* =========================================================
               DONNÉES INJECTÉES
            ========================================================= */
            fraisData:       @json($frais),
            sallesData:      @json($salles),
            sectionsData:    @json($sections),
            elevesParSalle:  @json($elevesParSalle),
            tauxChange:      Number({{ $tauxChange ?? 2800 }}),

            /* =========================================================
               ÉTAT INITIAL (pré-rempli avec les données du paiement)
            ========================================================= */
            selectedFraisId:  @json(old('frais_supplementaire_id', $paiement->frais_supplementaire_id)),
            selectedSalleId:  @json(old('salle_classe_id', $paiement->salle_classe_id ?? '')),
            selectedEleveId:  @json(old('eleve_id', $paiement->eleve_id)),
            commentaire:      @json(old('commentaire', $paiement->commentaire)),

            /* Filtres */
            sectionFilter: '',
            salleSearch:   '',
            eleveSearch:   '',

            /* Montants */
            montantFrais:  0,
            montantPaye:   {{ (float) old('montant_paye_usd', $paiement->montant_paye_usd ?? 0) }},
            montantPayeFC: {{ (float) old('montant_paye_fc', $paiement->montant_paye_fc ?? 0) }},

            /* Affichage */
            selectedSalleNom: '',
            nomEleve: '',

            /* Étapes */
            currentStep: 1,
            steps: [{}, {}, {}],

            /* =========================================================
               INIT
            ========================================================= */
            init() {
                // Charger le frais et déclencher les cascades
                if (this.selectedFraisId) {
                    // On appelle sans reset pour préserver les sélections
                    this.chargerFrais(true);
                }
            },

            /* =========================================================
               COMPUTED
            ========================================================= */
            get fraisSelectionne() {
                return this.fraisData.find(f => f.id == this.selectedFraisId);
            },

            get sallesDisponibles() {
                if (!this.fraisSelectionne) return [];
                if (this.fraisSelectionne.est_pour_toutes_salles) return this.sallesData;
                const ids = this.fraisSelectionne.salles_ids || [];
                return this.sallesData.filter(s => ids.includes(s.id));
            },

            get filteredSalles() {
                let list = this.sallesDisponibles;
                if (this.sectionFilter) {
                    list = list.filter(s => String(s.section_id) === String(this.sectionFilter));
                }
                if (this.salleSearch) {
                    const q = this.salleSearch.toLowerCase();
                    list = list.filter(s => s.nom.toLowerCase().includes(q));
                }
                return list;
            },

            get elevesDisponibles() {
                if (!this.selectedSalleId) return [];
                return this.elevesParSalle[this.selectedSalleId] || [];
            },

            get filteredEleves() {
                let list = this.elevesDisponibles;
                if (this.eleveSearch) {
                    const q = this.eleveSearch.toLowerCase();
                    list = list.filter(e => (e.nom_complet || '').toLowerCase().includes(q));
                }
                return list;
            },

            get canSubmit() {
                return this.selectedFraisId
                    && this.selectedSalleId
                    && this.selectedEleveId
                    && this.montantPaye > 0
                    && this.montantPaye <= this.montantFrais;
            },

            /* =========================================================
               HANDLERS
            ========================================================= */
            chargerFrais(initial = false) {
                if (this.fraisSelectionne) {
                    this.montantFrais = parseFloat(this.fraisSelectionne.montant) || 0;
                } else {
                    this.montantFrais = 0;
                }

                // Pré-remplir les noms (pour l'affichage panneau latéral)
                if (this.selectedSalleId) {
                    const salle = this.sallesData.find(s => s.id == this.selectedSalleId);
                    this.selectedSalleNom = salle ? salle.nom : '';
                }
                if (this.selectedEleveId) {
                    const eleve = this.elevesDisponibles.find(e => e.id == this.selectedEleveId);
                    this.nomEleve = eleve ? eleve.nom_complet : '';
                }
            },

            onFraisChange() {
                this.selectedSalleId  = '';
                this.selectedEleveId  = '';
                this.selectedSalleNom = '';
                this.nomEleve = '';
                this.sectionFilter = '';
                this.salleSearch = '';
                this.eleveSearch = '';

                this.montantFrais = this.fraisSelectionne
                    ? parseFloat(this.fraisSelectionne.montant) || 0
                    : 0;

                // On conserve le montant payé initial de l'édition
                // mais on vide seulement si l'utilisateur change explicitement
            },

            onSalleChange() {
                this.selectedEleveId = '';
                this.nomEleve = '';
                this.eleveSearch = '';

                const salle = this.sallesData.find(s => s.id == this.selectedSalleId);
                this.selectedSalleNom = salle ? salle.nom : '';
            },

            onEleveChange() {
                const eleve = this.elevesDisponibles.find(e => e.id == this.selectedEleveId);
                this.nomEleve = eleve ? eleve.nom_complet : '';
            },

            /* =========================================================
               CONVERSIONS
            ========================================================= */
            syncUSDToFC() {
                this.montantPayeFC = (this.montantPaye && this.tauxChange)
                    ? Math.round(parseFloat(this.montantPaye) * this.tauxChange)
                    : 0;
            },

            syncFCToUSD() {
                if (this.montantPayeFC && this.tauxChange) {
                    let usd = Math.round(parseFloat(this.montantPayeFC) / this.tauxChange * 100) / 100;
                    if (usd > this.montantFrais) usd = this.montantFrais;
                    this.montantPaye = usd;
                } else {
                    this.montantPaye = 0;
                }
            },

            payerTout() {
                this.montantPaye = this.montantFrais;
                this.syncUSDToFC();
            },

            payerMoitie() {
                this.montantPaye = Math.round(this.montantFrais / 2 * 100) / 100;
                this.syncUSDToFC();
            },

            /* =========================================================
               UTILITAIRES
            ========================================================= */
            formatMontant(v) {
                const n = parseFloat(v) || 0;
                return n.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            },

            /* =========================================================
               NAVIGATION
            ========================================================= */
            validateStep1() {
                if (!this.selectedFraisId) return alert('Veuillez sélectionner un frais.');
                if (!this.selectedSalleId) return alert('Veuillez sélectionner une salle.');
                this.currentStep = 2;
            },

            validateStep2() {
                if (!this.selectedEleveId) return alert('Veuillez sélectionner un élève.');
                if (this.montantPaye <= 0) return alert('Le montant payé doit être supérieur à 0.');
                if (this.montantPaye > this.montantFrais) return alert('Le montant ne peut pas dépasser celui du frais.');
                this.currentStep = 3;
            },

            goToStep(step) {
                if (step <= this.currentStep + 1) this.currentStep = step;
            }
        }
    }
</script>
@endsection