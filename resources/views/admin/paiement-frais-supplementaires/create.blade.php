@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

{{-- FLASH --}}
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
            <h1 class="form-title">Paiement <strong>frais supplémentaire</strong></h1>
            <p class="form-subtitle">Enregistrez le paiement d'un frais ponctuel pour un ou plusieurs élèves</p>
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
            <span :class="currentStep >= 2 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Élève(s) & Montant</span>
            <span :class="currentStep >= 3 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Confirmation</span>
        </div>
    </div>

    {{-- FORMULAIRE --}}
    <form action="{{ route('admin.paiement-frais-supplementaires.store') }}" method="POST" id="paiementForm">
        @csrf

        <input type="hidden" name="frais_supplementaire_id" x-model="selectedFraisId">
        <input type="hidden" name="salle_classe_id" x-model="selectedSalleId">
        <input type="hidden" name="montant_paye_usd" x-model="montantPaye">
        <input type="hidden" name="montant_paye_fc" x-model="montantPayeFC">

        {{-- Mode simple : 1 élève --}}
        <template x-if="selectionMode === 'simple'">
            <input type="hidden" name="eleve_id" :value="selectedEleveIds[0] ?? ''">
        </template>

        {{-- Mode multiple : N élèves --}}
        <template x-if="selectionMode === 'multiple'">
            <template x-for="id in selectedEleveIds" :key="'hidden-' + id">
                <input type="hidden" name="eleve_ids[]" :value="id">
            </template>
        </template>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">

                {{-- ═══ ÉTAPE 1 : Frais & Salle ═══ --}}
                <div x-show="currentStep === 1" class="form-card">
                    <h2 class="card-title">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        Sélectionnez le frais et la salle
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

                            <template x-if="fraisData.length === 0">
                                <p class="error-text">
                                    <i class="fa-solid fa-circle-info"></i>
                                    Aucun frais supplémentaire ouvert pour le moment.
                                </p>
                            </template>
                        </div>
                    </div>

                    {{-- Rappel du frais sélectionné --}}
                    <template x-if="fraisSelectionne">
                        <div class="info-banner">
                            <div class="info-banner-icon"><i class="fa-solid fa-tag"></i></div>
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
                                    <span class="badge-count-inline"
                                          x-text="sectionsDisponibles.length + ' / ' + sectionsData.length"></span>
                                </label>
                                <select id="filter_section" x-model="sectionFilter" class="filter-select-sm">
                                    <option value="">Toutes les sections</option>
                                    <template x-for="s in sectionsDisponibles" :key="s.id">
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
                                        <option value="">
                                            <span x-show="filteredSalles.length === 0">— Aucune salle disponible —</span>
                                            <span x-show="filteredSalles.length > 0">— Choisir une salle —</span>
                                        </option>
                                        <template x-for="salle in filteredSalles" :key="salle.id">
                                            <option :value="salle.id" x-text="salle.nom"></option>
                                        </template>
                                    </select>
                                    <i class="fa-solid fa-chevron-down select-icon"></i>
                                    <div class="line-focus"></div>
                                </div>
                                @error('salle_classe_id')<p class="error-text">{{ $message }}</p>@enderror

                                <template x-if="filteredSalles.length === 0">
                                    <p class="error-text">
                                        <i class="fa-solid fa-inbox"></i>
                                        Aucune salle ne correspond à vos filtres pour ce frais.
                                    </p>
                                </template>
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

                {{-- ═══ ÉTAPE 2 : Élève(s) & Montant ═══ --}}
                <div x-show="currentStep === 2" class="form-card">
                    <h2 class="card-title">
                        <i class="fa-solid fa-user-graduate"></i>
                        Sélectionnez le(s) élève(s) et le montant
                    </h2>

                    {{-- Rappel --}}
                    <div class="info-banner info-banner-light">
                        <div class="info-banner-icon info-banner-icon-light">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div class="info-banner-body">
                            <p class="info-banner-title" x-text="fraisSelectionne?.libelle ?? ''"></p>
                            <p class="info-banner-text">
                                <i class="fa-solid fa-school"></i> <span x-text="selectedSalleNom"></span>
                                <span class="text-muted">·</span>
                                <span x-text="formatMontant(montantFrais) + ' $ / élève'"></span>
                            </p>
                        </div>
                    </div>

                    {{-- Toggle mode simple / multiple --}}
                    <div class="mode-switch">
                        <button type="button"
                                class="mode-btn"
                                :class="selectionMode === 'simple' ? 'mode-btn-active' : ''"
                                @click="setMode('simple')">
                            <i class="fa-solid fa-user"></i>
                            <span>Un seul élève</span>
                        </button>
                        <button type="button"
                                class="mode-btn"
                                :class="selectionMode === 'multiple' ? 'mode-btn-active' : ''"
                                @click="setMode('multiple')">
                            <i class="fa-solid fa-users"></i>
                            <span>Plusieurs élèves</span>
                            <span class="mode-count" x-show="selectedEleveIds.length > 0"
                                  x-text="selectedEleveIds.length"></span>
                        </button>
                    </div>

                    {{-- Toolbar recherche --}}
                    <div class="filters-row mb-4">
                        <div class="filter-field filter-field-grow">
                            <label for="filter_eleve" class="filter-label-sm">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                Rechercher un élève
                                <span class="badge-count-inline"
                                      x-text="filteredEleves.length + ' / ' + elevesDisponibles.length"></span>
                            </label>
                            <input type="text" id="filter_eleve" x-model="eleveSearch"
                                   placeholder="Nom, postnom ou prénom..."
                                   class="filter-input-sm">
                        </div>

                        <template x-if="selectionMode === 'multiple'">
                            <div class="filter-field">
                                <label class="filter-label-sm invisible">&nbsp;</label>
                                <div class="btn-row-inline">
                                    <button type="button" @click="selectAllEleves()" class="btn-small">
                                        <i class="fa-solid fa-check-double"></i>
                                        Tout <span x-text="'(' + filteredEleves.length + ')'"></span>
                                    </button>
                                    <button type="button" @click="deselectAllEleves()" class="btn-small">
                                        <i class="fa-solid fa-xmark"></i> Aucun
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- LISTE UNIFIÉE : cartes pour les 2 modes --}}
                    <div class="eleves-checklist">
                        <template x-for="eleve in filteredEleves" :key="eleve.id">
                            <button type="button"
                                    class="eleve-card"
                                    :class="{ 'eleve-card-selected': selectedEleveIds.includes(eleve.id) }"
                                    @click="toggleEleve(eleve.id)">
                                <div class="eleve-avatar"
                                     x-text="initials(eleve.nom_complet)"></div>
                                <span class="eleve-name" x-text="eleve.nom_complet"></span>
                                <i class="fa-regular eleve-icon"
                                   :class="selectedEleveIds.includes(eleve.id)
                                       ? (selectionMode === 'simple' ? 'fa-circle-check' : 'fa-square-check')
                                       : (selectionMode === 'simple' ? 'fa-circle' : 'fa-square')"></i>
                            </button>
                        </template>

                        <div x-show="filteredEleves.length === 0" class="eleve-check-empty">
                            <i class="fa-solid fa-inbox"></i>
                            <p x-text="eleveSearch ? 'Aucun élève trouvé.' : 'Aucun élève inscrit dans cette salle.'"></p>
                        </div>
                    </div>

                    {{-- Résumé sélection --}}
                    <div class="selection-summary" x-show="selectedEleveIds.length > 0">
                        <i class="fa-solid fa-users"></i>
                        <span>
                            <strong x-text="selectedEleveIds.length"></strong>
                            élève(s) sélectionné(s)
                        </span>
                        <template x-if="selectionMode === 'multiple'">
                            <span class="text-muted">
                                · Total max : <strong x-text="formatMontant(montantFrais * selectedEleveIds.length) + ' $'"></strong>
                            </span>
                        </template>
                    </div>

                    {{-- Montant --}}
                    <div class="form-row mt-4">
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="number" step="0.01" min="0"
                                       x-model="montantPaye" @input="syncUSDToFC()"
                                       placeholder=" "
                                       :disabled="selectedEleveIds.length === 0"
                                       :max="montantMax"
                                       :class="{'is-invalid': isMontantInvalide()}">
                                <label class="float-label">
                                    <span x-text="selectionMode === 'multiple' ? 'Montant TOTAL (USD)' : 'Montant payé (USD)'"></span>
                                    <span class="text-red-500">*</span>
                                </label>
                                <i class="fa-solid fa-dollar-sign icon"></i>
                                <div class="line-focus"></div>
                            </div>

                            <div class="fc-hint" x-show="montantPayeFC > 0">
                                <i class="fa-solid fa-arrow-right"></i>
                                <span x-text="formatMontant(montantPayeFC) + ' FC'"></span>
                            </div>

                            <template x-if="isMontantInvalide()">
                                <p class="error-text">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    Maximum :
                                    <span x-text="formatMontant(montantMax)"></span> $
                                </p>
                            </template>

                            <div class="btn-row-inline">
                                <button type="button" @click="payerTout()"
                                        :disabled="selectedEleveIds.length === 0"
                                        class="btn-small">
                                    <i class="fa-solid fa-check-double"></i> Payer tout
                                </button>
                                <button type="button" @click="payerMoitie()"
                                        :disabled="selectedEleveIds.length === 0"
                                        class="btn-small">
                                    <i class="fa-solid fa-divide"></i> Moitié
                                </button>
                            </div>
                        </div>

                        {{-- Montant FC --}}
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="number" step="1" min="0"
                                       x-model="montantPayeFC" @input="syncFCToUSD()"
                                       placeholder=" " class="bg-gray-50">
                                <label class="float-label">Montant (FC)</label>
                                <i class="fa-solid fa-franc-sign icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            <small class="text-muted">
                                Taux : <span x-text="formatMontant(tauxChange)"></span> FC/USD
                            </small>
                        </div>
                    </div>

                    {{-- Répartition --}}
                    <div class="repartition-info" x-show="selectionMode === 'multiple' && selectedEleveIds.length > 1 && montantPaye > 0">
                        <i class="fa-solid fa-divide"></i>
                        <span>
                            <strong x-text="formatMontant(montantPaye / selectedEleveIds.length)"></strong> $ par élève
                            <span class="text-muted">·</span>
                            <span class="text-muted" x-text="formatMontant(montantPayeFC / selectedEleveIds.length) + ' FC'"></span>
                        </span>
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

                {{-- ═══ ÉTAPE 3 : Confirmation ═══ --}}
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
                            <span>Élève(s)</span>
                            <p x-text="getElevesSummary()"></p>
                        </div>
                        <div class="summary-card summary-card-highlight">
                            <span>Montant TOTAL</span>
                            <p x-text="formatMontant(montantPaye) + ' USD'"></p>
                            <small x-text="formatMontant(montantPayeFC) + ' FC'"></small>
                        </div>
                    </div>

                    <div x-show="selectionMode === 'multiple' && selectedEleveIds.length > 1"
                         class="repartition-info mb-4">
                        <i class="fa-solid fa-divide"></i>
                        <span>
                            <strong x-text="formatMontant(montantPaye / selectedEleveIds.length)"></strong> $ par élève
                            <span class="text-muted">(répartition égale)</span>
                        </span>
                    </div>

                    <div x-show="commentaire" class="mb-4">
                        <span class="text-sm text-gray-600">Commentaire :</span>
                        <p class="text-sm" x-text="commentaire"></p>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" @click="currentStep = 2" class="btn-cancel">
                            <i class="fa-solid fa-arrow-left"></i> Retour
                        </button>
                        <button type="submit" class="btn-submit green" :disabled="!canSubmit">
                            <i class="fa-solid fa-check"></i> Confirmer le paiement
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
                        <p class="selected-eleve-name">Paiement frais</p>
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
                        <span>Montant / élève</span>
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
                        <span>Élève(s)</span>
                        <strong x-text="getElevesSummary()"></strong>
                    </div>

                    <hr class="financial-divider">

                    <div class="financial-row financial-row-highlight">
                        <span>Montant TOTAL</span>
                        <strong class="text-success"
                                x-text="montantPaye ? formatMontant(montantPaye) + ' $' : '—'"></strong>
                    </div>
                    <div class="financial-row muted">
                        <span>Équivalent FC</span>
                        <span x-text="montantPayeFC ? formatMontant(montantPayeFC) + ' FC' : '—'"></span>
                    </div>

                    <div class="financial-row muted" x-show="selectionMode === 'multiple' && selectedEleveIds.length > 1 && montantPaye > 0">
                        <span>Part / élève</span>
                        <span x-text="formatMontant(montantPaye / selectedEleveIds.length) + ' $'"></span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    /* ════════════════════════════════════════════════════════════
       BASE
    ════════════════════════════════════════════════════════════ */
    .paiement-page { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    .header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .form-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.2rem; letter-spacing: -0.5px; }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: #94a3b8; font-size: 0.9rem; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: #667eea; font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: color 0.2s; }
    .back-link:hover { color: #4f46e5; }

    /* PROGRESSION */
    .progress-container { margin-bottom: 2rem; }
    .progress-steps { display: flex; align-items: center; gap: 0.5rem; }
    .progress-step { display: flex; align-items: center; flex: 1; }
    .step-circle { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; transition: all 0.3s; border: none; outline: none; }
    .step-line { flex: 1; height: 3px; border-radius: 2px; margin: 0 8px; transition: background-color 0.3s; }
    .progress-labels { display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.8rem; }

    /* CARTES */
    .form-card { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
    .card-title { font-size: 1.2rem; font-weight: 600; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px; }
    .card-title i { color: #667eea; }

    /* FORMULAIRE */
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
    .select-wrap select:disabled, .input-wrap input:disabled { background: #f8fafc; cursor: not-allowed; opacity: 0.6; }

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

    .fc-hint { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; font-weight: 600; color: #059669; margin-top: 0.4rem; }

    /* FILTRES */
    .filters-row { display: flex; flex-wrap: wrap; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 12px; border: 1px dashed #e2e8f0; margin-top: 1rem; }
    .filter-field { display: flex; flex-direction: column; gap: 0.35rem; min-width: 180px; }
    .filter-field-grow { flex: 1; min-width: 220px; }
    .filter-label-sm { font-size: 0.7rem; font-weight: 700; color: #475569; letter-spacing: 0.3px; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
    .filter-label-sm i { color: #667eea; font-size: 0.75rem; }
    .filter-input-sm, .filter-select-sm { width: 100%; padding: 0.55rem 0.9rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: white; font-size: 0.9rem; color: #1e293b; transition: all 0.2s; outline: none; font-weight: 500; }
    .filter-input-sm:focus, .filter-select-sm:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    .invisible { visibility: hidden; }

    /* INFO BANNER */
    .info-banner { display: flex; align-items: center; gap: 0.9rem; padding: 0.9rem 1.1rem; background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%); border: 1px solid #c7d2fe; border-radius: 14px; margin-top: 1rem; }
    .info-banner-light { background: #f8fafc; border-color: #e2e8f0; margin-top: 0; margin-bottom: 1rem; }
    .info-banner-icon { width: 40px; height: 40px; border-radius: 10px; background: #4f46e5; color: white; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
    .info-banner-icon-light { background: #eef2ff; color: #4f46e5; }
    .info-banner-body { min-width: 0; }
    .info-banner-title { font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 0.15rem; }
    .info-banner-text { font-size: 0.85rem; color: #64748b; display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; }

    /* MODE SWITCH */
    .mode-switch { display: flex; gap: 0.5rem; padding: 0.4rem; background: #f1f5f9; border-radius: 12px; margin-bottom: 1.25rem; }
    .mode-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.7rem 1rem; border-radius: 10px; background: transparent; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; color: #64748b; transition: all 0.2s; }
    .mode-btn:hover { background: rgba(255,255,255,0.6); }
    .mode-btn-active { background: white; color: #4f46e5; box-shadow: 0 2px 8px rgba(79,70,229,0.15); }
    .mode-count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 20px; padding: 0 0.4rem; background: #4f46e5; color: white; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; }
    .mode-btn:not(.mode-btn-active) .mode-count { background: #94a3b8; }

    /* LISTE ÉLÈVES UNIFIÉE */
    .eleves-checklist {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 0.5rem;
        max-height: 380px;
        overflow-y: auto;
        padding: 0.5rem;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
    }

    .eleve-card {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.65rem 0.85rem;
        background: white;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.15s;
        text-align: left;
        font-family: inherit;
    }
    .eleve-card:hover { border-color: #c7d2fe; background: #f8faff; }
    .eleve-card-selected { border-color: #667eea; background: #eef2ff; box-shadow: 0 2px 8px rgba(102,126,234,0.12); }

    .eleve-avatar {
        width: 32px; height: 32px; border-radius: 8px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white; display: flex; align-items: center;
        justify-content: center; font-weight: 700; font-size: 0.72rem;
        flex-shrink: 0;
    }
    .eleve-name {
        flex: 1; min-width: 0;
        font-weight: 500; color: #1e293b; font-size: 0.85rem;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .eleve-icon {
        color: #cbd5e1; font-size: 1.05rem;
        transition: all 0.15s; flex-shrink: 0;
    }
    .eleve-card-selected .eleve-icon { color: #4f46e5; }

    .eleve-check-empty {
        grid-column: 1 / -1;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 2rem 1rem; text-align: center; color: #94a3b8; gap: 0.5rem;
    }
    .eleve-check-empty i { font-size: 1.75rem; color: #cbd5e1; }
    .eleve-check-empty p { margin: 0; font-size: 0.85rem; }

    /* RÉSUMÉ */
    .selection-summary, .repartition-info {
        display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;
        padding: 0.7rem 1rem; margin-top: 1rem;
        background: #eef2ff; border: 1px solid #c7d2fe;
        border-radius: 10px; font-size: 0.85rem; color: #4f46e5;
    }
    .repartition-info { background: #ecfdf5; border-color: #bbf7d0; color: #15803d; }
    .selection-summary i, .repartition-info i { flex-shrink: 0; }

    /* BOUTONS */
    .btn-submit, .btn-cancel, .btn-disabled {
        padding: 0.8rem 1.75rem; border-radius: 12px;
        font-weight: 600; font-size: 0.95rem;
        display: inline-flex; align-items: center; justify-content: center;
        gap: 8px; cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        outline: none; border: none; text-decoration: none;
    }
    .btn-submit { background: #1e293b; color: white; }
    .btn-submit:hover:not(:disabled) { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-submit.green { background: #16a34a; }
    .btn-submit.green:hover { background: #15803d; box-shadow: 0 8px 16px rgba(22,163,74,0.3); }
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
    .btn-small:hover:not(:disabled) { background: #e0e7ff; transform: translateY(-1px); }
    .btn-small:disabled { opacity: 0.5; cursor: not-allowed; }

    /* RÉSUMÉ CARTES */
    .summary-card { background: #f8fafc; border-radius: 12px; padding: 1rem; border: 1px solid #f1f5f9; }
    .summary-card span { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
    .summary-card p { font-weight: 700; color: #1e293b; margin-top: 0.25rem; font-size: 1rem; }
    .summary-card-highlight { background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); border-color: #bbf7d0; }
    .summary-card-highlight p { color: #15803d; }
    .summary-card-highlight small { color: #16a34a; font-weight: 600; }

    /* PANNEAU LATÉRAL */
    .selected-eleve-card { background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 20px; padding: 1.5rem; color: white; display: flex; align-items: center; gap: 1rem; box-shadow: 0 20px 40px rgba(102,126,234,0.3); }
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

    /* DIVERS */
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
        .filter-field, .filter-field-grow { min-width: 100%; }
        .eleves-checklist { grid-template-columns: 1fr; max-height: 300px; }
        .mode-btn { font-size: 0.8rem; padding: 0.6rem 0.6rem; }
        .mode-btn span:not(.mode-count) { display: none; }
    }
</style>

<script>
    function paiementFraisForm() {
        return {
            /* ---------------- DONNÉES ---------------- */
            fraisData:       @json($frais),
            sallesData:      @json($salles),
            sectionsData:    @json($sections),
            elevesParSalle:  @json($elevesParSalle),
            tauxChange:      Number({{ $tauxChange ?? 2800 }}),

            /* ---------------- SÉLECTIONS ---------------- */
            selectedFraisId:  '',
            selectedSalleId:  '',
            selectedEleveIds: [],

            selectionMode: 'simple',
            commentaire: '',

            /* ---------------- FILTRES ---------------- */
            sectionFilter: '',
            salleSearch:   '',
            eleveSearch:   '',

            /* ---------------- MONTANTS ---------------- */
            montantFrais:  0,
            montantPaye:   0,
            montantPayeFC: 0,

            /* ---------------- AFFICHAGE ---------------- */
            selectedSalleNom: '',

            /* ---------------- ÉTAPES ---------------- */
            currentStep: 1,
            steps: [{}, {}, {}],

            /* ---------------- INIT ---------------- */
            init() {
                const oldFraisId  = @json(old('frais_supplementaire_id'));
                const oldSalleId  = @json(old('salle_classe_id'));
                const oldEleveId  = @json(old('eleve_id'));
                const oldEleveIds = @json(old('eleve_ids', []));

                if (oldFraisId) {
                    this.selectedFraisId = oldFraisId;
                    this.onFraisChange();

                    if (oldSalleId) {
                        this.selectedSalleId = oldSalleId;
                        this.onSalleChange();

                        if (Array.isArray(oldEleveIds) && oldEleveIds.length > 1) {
                            this.selectionMode = 'multiple';
                            this.selectedEleveIds = oldEleveIds.map(id => parseInt(id));
                            this.autoFillMontant();
                        } else if (oldEleveId) {
                            this.selectedEleveIds = [parseInt(oldEleveId)];
                            this.autoFillMontant();
                        }
                    }
                }

                const oldMontant = @json(old('montant_paye_usd'));
                if (oldMontant) {
                    this.montantPaye = parseFloat(oldMontant);
                    this.syncUSDToFC();
                }
            },

            /* ---------------- COMPUTED ---------------- */
            get fraisSelectionne() {
                return this.fraisData.find(f => f.id == this.selectedFraisId);
            },

            get sallesDisponibles() {
                if (!this.fraisSelectionne) return [];
                if (this.fraisSelectionne.est_pour_toutes_salles) return this.sallesData;
                const ids = this.fraisSelectionne.salles_ids || [];
                return this.sallesData.filter(s => ids.includes(s.id));
            },

            get sectionsDisponibles() {
                const salleIds = this.sallesDisponibles.map(s => s.section_id).filter(Boolean);
                const sectionIds = [...new Set(salleIds)];
                return this.sectionsData.filter(s => sectionIds.includes(s.id));
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

            /**
             * Montant maximum autorisé :
             * - Simple : 1 × montant frais
             * - Multiple : N × montant frais
             */
            get montantMax() {
                return this.selectionMode === 'multiple'
                    ? this.montantFrais * this.selectedEleveIds.length
                    : this.montantFrais;
            },

            get canSubmit() {
                if (!this.selectedFraisId || !this.selectedSalleId) return false;
                if (this.selectedEleveIds.length === 0) return false;
                if (this.montantPaye <= 0) return false;

                if (this.selectionMode === 'simple') {
                    return this.montantPaye <= this.montantFrais + 0.01;
                }

                return this.montantPaye <= (this.montantFrais * this.selectedEleveIds.length) + 0.01;
            },

            /* ---------------- HANDLERS ---------------- */
            onFraisChange() {
                this.selectedSalleId  = '';
                this.selectedEleveIds = [];
                this.selectedSalleNom = '';
                this.sectionFilter = '';
                this.salleSearch = '';
                this.eleveSearch = '';

                this.montantFrais = this.fraisSelectionne
                    ? parseFloat(this.fraisSelectionne.montant) || 0
                    : 0;

                this.montantPaye = 0;
                this.montantPayeFC = 0;
            },

            onSalleChange() {
                this.selectedEleveIds = [];
                this.eleveSearch = '';
                this.montantPaye = 0;
                this.montantPayeFC = 0;

                const salle = this.sallesData.find(s => s.id == this.selectedSalleId);
                this.selectedSalleNom = salle ? salle.nom : '';
            },

            /* ---------------- MODE ---------------- */
            setMode(mode) {
                if (mode === this.selectionMode) return;

                this.selectionMode = mode;
                this.eleveSearch = '';
                this.montantPaye = 0;
                this.montantPayeFC = 0;

                if (mode === 'simple' && this.selectedEleveIds.length > 1) {
                    // Ne garder que le premier élève
                    this.selectedEleveIds = [this.selectedEleveIds[0]];
                }

                this.autoFillMontant();
            },

            /* ---------------- SÉLECTION UNIFIÉE ---------------- */
            /**
             * Un seul clic gère les deux modes :
             * - simple : radio (un seul élève)
             * - multiple : checkbox (toggle)
             */
            toggleEleve(id) {
                if (this.selectionMode === 'simple') {
                    // Radio : sélectionne ou désélectionne
                    this.selectedEleveIds = (this.selectedEleveIds[0] === id) ? [] : [id];
                } else {
                    // Checkbox : toggle
                    const idx = this.selectedEleveIds.indexOf(id);
                    if (idx === -1) this.selectedEleveIds.push(id);
                    else this.selectedEleveIds.splice(idx, 1);
                }

                this.autoFillMontant();
            },

            selectAllEleves() {
                const ids = this.filteredEleves.map(e => e.id);
                if (this.selectionMode === 'simple') {
                    this.selectedEleveIds = ids.length > 0 ? [ids[0]] : [];
                } else {
                    this.selectedEleveIds = Array.from(new Set([...this.selectedEleveIds, ...ids]));
                }
                this.autoFillMontant();
            },

            deselectAllEleves() {
                const ids = new Set(this.filteredEleves.map(e => e.id));
                this.selectedEleveIds = this.selectedEleveIds.filter(id => !ids.has(id));
                this.autoFillMontant();
            },

            /* ---------------- MONTANT AUTO ---------------- */
            autoFillMontant() {
                const nb = this.selectedEleveIds.length;
                if (nb === 0) {
                    this.montantPaye = 0;
                    this.montantPayeFC = 0;
                    return;
                }
                this.montantPaye = Math.round(this.montantFrais * nb * 100) / 100;
                this.syncUSDToFC();
            },

            isMontantInvalide() {
                if (this.selectedEleveIds.length === 0) return false;
                return this.montantPaye > this.montantMax || this.montantPaye <= 0;
            },

            syncUSDToFC() {
                this.montantPayeFC = (this.montantPaye && this.tauxChange)
                    ? Math.round(parseFloat(this.montantPaye) * this.tauxChange)
                    : 0;
            },

            syncFCToUSD() {
                if (this.montantPayeFC && this.tauxChange) {
                    let usd = Math.round(parseFloat(this.montantPayeFC) / this.tauxChange * 100) / 100;
                    if (usd > this.montantMax) usd = this.montantMax;
                    this.montantPaye = usd;
                } else {
                    this.montantPaye = 0;
                }
            },

            payerTout() {
                this.montantPaye = this.montantMax;
                this.syncUSDToFC();
            },

            payerMoitie() {
                this.montantPaye = Math.round(this.montantMax / 2 * 100) / 100;
                this.syncUSDToFC();
            },

            /* ---------------- UTILITAIRES ---------------- */
            formatMontant(v) {
                const n = parseFloat(v) || 0;
                return n.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            },

            initials(name) {
                return (name || '')
                    .split(' ')
                    .filter(Boolean)
                    .slice(0, 2)
                    .map(w => w[0].toUpperCase())
                    .join('') || '?';
            },

            getElevesSummary() {
                const n = this.selectedEleveIds.length;
                if (n === 0) return '—';

                if (this.selectionMode === 'simple') {
                    const eleve = this.elevesDisponibles.find(e => e.id === this.selectedEleveIds[0]);
                    return eleve ? eleve.nom_complet : '1 élève';
                }

                return n === 1 ? '1 élève' : n + ' élèves';
            },

            /* ---------------- NAVIGATION ---------------- */
            validateStep1() {
                if (!this.selectedFraisId) return alert('Veuillez sélectionner un frais.');
                if (!this.selectedSalleId) return alert('Veuillez sélectionner une salle.');
                this.currentStep = 2;
            },

            validateStep2() {
                if (this.selectedEleveIds.length === 0) {
                    return alert('Veuillez sélectionner au moins un élève.');
                }
                if (this.montantPaye <= 0) {
                    return alert('Le montant payé doit être supérieur à 0.');
                }
                if (this.montantPaye > this.montantMax) {
                    return alert('Le montant ne peut pas dépasser ' + this.formatMontant(this.montantMax) + ' $.');
                }
                this.currentStep = 3;
            },

            goToStep(step) {
                if (step <= this.currentStep + 1) this.currentStep = step;
            }
        }
    }
</script>
@endsection