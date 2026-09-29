@extends('layouts.admin')

@section('page_title', 'Nouveau paiement')
@section('page_subtitle', 'Enregistrer un ou plusieurs paiements')

@section('content')
@php
    $config = [
        'sections'       => $sections       ?? [],
        'sallesData'     => $sallesData     ?? [],
        'elevesParSalle' => $elevesParSalle ?? [],
        'mois'           => $mois           ?? [],
        'tranches'       => $tranches       ?? [],
        'tauxChange'     => (float) ($tauxChange ?? 2800),
        'isOpen'         => (bool) ($anneeActive->paiement_ouvert ?? true),
        'initial'        => [
            'salleId'     => (string) old('salle_classe_id', ''),
            'eleveIds'    => array_values(array_map('intval', old('eleve_ids', []))),
            'periode'     => (string) old('periode', ''),
            'typePeriode' => (string) old('type_periode', ''),
            'montantPaye' => (float)  old('montant_paye_usd', 0),
            'commentaire' => (string) old('commentaire', ''),
        ],
    ];
@endphp

<div class="page" x-data="paiementPage({{ Js::from($config) }})" x-init="init()" x-cloak>

    {{-- EN-TÊTE --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-plus-circle title-icon"></i>
                Nouveau paiement
            </h1>
            <p class="page-subtitle">Choisissez une section, puis une salle, puis les élèves</p>
        </div>
        <a href="{{ route('admin.paiements.index') }}" class="btn btn-ghost">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </header>

    {{-- FLASH --}}
    @if(session('success'))
        <div class="flash flash-success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="flash flash-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                <strong>Corrigez les erreurs suivantes :</strong>
                <ul class="flash-list">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    @if(!$config['isOpen'])
        <div class="alert-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Session de paiement fermée.</strong>
                <p>Impossible d'enregistrer de nouveaux paiements.</p>
            </div>
        </div>
    @endif

    {{-- FILTRES CASCADE --}}
    <section class="filters-card">
        <div class="filters-header">
            <div class="filters-header-left">
                <i class="fa-solid fa-sliders filter-icon"></i>
                <span class="filters-title">Sélection rapide</span>
            </div>
            <button type="button" class="btn btn-ghost btn-sm"
                    x-show="selectedSectionId || selectedSalleId"
                    @click="reset()">
                <i class="fa-solid fa-rotate-left"></i> Réinitialiser
            </button>
        </div>

        <div class="cascade-steps">
            <div class="cascade-step" :class="selectedSectionId ? 'is-done' : 'is-active'">
                <div class="cascade-circle">
                    <template x-if="selectedSectionId"><i class="fa-solid fa-check"></i></template>
                    <template x-if="!selectedSectionId"><span>1</span></template>
                </div>
                <span class="cascade-label">Section</span>
            </div>
            <div class="cascade-line" :class="selectedSectionId ? 'is-done' : ''"></div>

            <div class="cascade-step"
                 :class="selectedSalleId ? 'is-done' : (selectedSectionId ? 'is-active' : '')">
                <div class="cascade-circle">
                    <template x-if="selectedSalleId"><i class="fa-solid fa-check"></i></template>
                    <template x-if="!selectedSalleId"><span>2</span></template>
                </div>
                <span class="cascade-label">Salle</span>
            </div>
            <div class="cascade-line" :class="selectedSalleId ? 'is-done' : ''"></div>

            <div class="cascade-step" :class="selectedSalleId ? 'is-active' : ''">
                <div class="cascade-circle"><span>3</span></div>
                <span class="cascade-label">Élèves</span>
            </div>
        </div>

        <div class="filters-grid">
            <div class="filter-field">
                <label for="f_section" class="filter-label">
                    <i class="fa-solid fa-layer-group"></i> Section
                </label>
                <select id="f_section" class="filter-select"
                        x-model="selectedSectionId"
                        @change="onSectionChange()">
                    <option value="">— Choisir une section —</option>
                    <template x-for="s in sections" :key="s.id">
                        <option :value="String(s.id)" x-text="s.nom"></option>
                    </template>
                </select>
            </div>

            <div class="filter-field">
                <label for="f_salle" class="filter-label">
                    <i class="fa-solid fa-chalkboard-user"></i> Salle
                    <span class="badge-count-inline" x-show="sallesFiltrees.length > 0"
                          x-text="sallesFiltrees.length"></span>
                </label>
                <select id="f_salle" class="filter-select"
                        x-model="selectedSalleId"
                        @change="onSalleChange()"
                        :disabled="!selectedSectionId || sallesFiltrees.length === 0">
                    <option value="">
                        <span x-show="!selectedSectionId">— Choisissez d'abord une section —</span>
                        <span x-show="selectedSectionId && sallesFiltrees.length === 0">— Aucune salle —</span>
                        <span x-show="selectedSectionId && sallesFiltrees.length > 0">— Choisir une salle —</span>
                    </option>
                    <template x-for="s in sallesFiltrees" :key="s.id">
                        <option :value="String(s.id)" x-text="s.nom"></option>
                    </template>
                </select>
            </div>
        </div>
    </section>

    {{-- WIZARD --}}
    <form action="{{ route('admin.paiements.store') }}" method="POST"
          @submit="onSubmit($event)" novalidate>
        @csrf

        <input type="hidden" name="salle_classe_id"     :value="selectedSalleId">
        <input type="hidden" name="periode"             :value="selectedPeriode">
        <input type="hidden" name="type_periode"        :value="typePeriode">
        <input type="hidden" name="montant_attendu_usd" :value="totalAttendu">
        <input type="hidden" name="montant_attendu_fc"  :value="totalAttenduFCNum">
        <input type="hidden" name="montant_paye_fc"     :value="montantPayeFC">

        <template x-for="id in selectedEleveIds" :key="'e-' + id">
            <input type="hidden" name="eleve_ids[]" :value="id">
        </template>

        {{-- Progression --}}
        <nav class="wizard-progress">
            <template x-for="(label, i) in stepLabels" :key="i">
                <div class="wizard-step"
                     :class="{ 'is-active': currentStep === i + 1, 'is-completed': currentStep > i + 1 }">
                    <button type="button" class="wizard-step-circle"
                            @click="goToStep(i + 1)"
                            :disabled="i + 1 > currentStep + 1">
                        <template x-if="currentStep > i + 1"><i class="fa-solid fa-check"></i></template>
                        <template x-if="currentStep <= i + 1"><span x-text="i + 1"></span></template>
                    </button>
                    <span class="wizard-step-label" x-text="label"></span>
                    <div class="wizard-step-line" x-show="i < stepLabels.length - 1"></div>
                </div>
            </template>
        </nav>

        <div class="wizard-layout">
            <div class="wizard-main">

                {{-- ═══ ÉTAPE 1 : Élèves ═══ --}}
                <section x-show="currentStep === 1" class="wizard-card">
                    <header class="wizard-card-header">
                        <div class="wizard-card-icon"><i class="fa-solid fa-user-graduate"></i></div>
                        <div>
                            <h2 class="wizard-card-title">Sélection des élèves</h2>
                            <p class="wizard-card-subtitle">
                                <span x-show="!selectedSalleId">Choisissez d'abord une section et une salle.</span>
                                <span x-show="selectedSalleId && elevesCourants.length > 0"
                                      x-text="elevesCourants.length + ' élève(s) dans cette salle.'"></span>
                                <span x-show="selectedSalleId && elevesCourants.length === 0">
                                    Aucun élève inscrit dans cette salle.
                                </span>
                            </p>
                        </div>
                    </header>

                    {{-- Toggle simple / multiple --}}
                    <div class="mode-switch">
                        <button type="button"
                                class="mode-btn"
                                :class="selectionMode === 'simple' ? 'mode-btn-active' : ''"
                                @click="setSelectionMode('simple')">
                            <i class="fa-solid fa-user"></i>
                            <span>Un seul élève</span>
                        </button>
                        <button type="button"
                                class="mode-btn"
                                :class="selectionMode === 'multiple' ? 'mode-btn-active' : ''"
                                @click="setSelectionMode('multiple')">
                            <i class="fa-solid fa-users"></i>
                            <span>Plusieurs élèves</span>
                            <span class="mode-count" x-show="selectedEleveIds.length > 1"
                                  x-text="selectedEleveIds.length"></span>
                        </button>
                    </div>

                    <div x-show="selectedSalleId && elevesCourants.length > 0" x-cloak>
                        <div class="wizard-toolbar">
                            <div class="wizard-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="search"
                                       x-model.debounce.150ms="eleveSearch"
                                       placeholder="Rechercher un élève…"
                                       autocomplete="off">
                            </div>
                            <div class="wizard-toolbar-actions">
                                <span class="wizard-count">
                                    <strong x-text="selectedEleveIds.length"></strong> /
                                    <span x-text="elevesCourants.length"></span>
                                </span>
                                <button type="button" @click="selectAll()"
                                        :disabled="filteredEleves.length === 0"
                                        class="wizard-link">Tout</button>
                                <button type="button" @click="deselectAll()"
                                        :disabled="selectedEleveIds.length === 0"
                                        class="wizard-link muted">Aucun</button>
                            </div>
                        </div>

                        <div class="students-grid">
                            <template x-for="eleve in filteredEleves" :key="eleve.id">
                                <button type="button"
                                        class="student-card"
                                        :class="{ 'is-selected': selectedEleveIds.includes(eleve.id) }"
                                        @click="toggleEleve(eleve.id)">
                                    <div class="student-avatar" x-text="initials(eleve.nom_complet)"></div>
                                    <span class="student-name" x-text="eleve.nom_complet"></span>
                                    <i class="fa-solid"
                                       :class="selectedEleveIds.includes(eleve.id)
                                           ? 'fa-circle-check student-check'
                                           : 'fa-circle student-circle'"></i>
                                </button>
                            </template>
                            <p x-show="filteredEleves.length === 0" class="wizard-empty">
                                Aucun élève ne correspond à votre recherche.
                            </p>
                        </div>
                    </div>

                    <footer class="wizard-footer">
                        <a href="{{ route('admin.paiements.index') }}" class="btn btn-ghost">
                            <i class="fa-solid fa-xmark"></i> Annuler
                        </a>
                        <button type="button" class="btn btn-primary"
                                :disabled="!canGoStep2" @click="next()">
                            Continuer <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </footer>
                </section>

                {{-- ═══ ÉTAPE 2 : Période + Montant ═══ --}}
                <section x-show="currentStep === 2" x-cloak class="wizard-card">
                    <header class="wizard-card-header">
                        <div class="wizard-card-icon"><i class="fa-solid fa-calendar-alt"></i></div>
                        <div>
                            <h2 class="wizard-card-title">Période et montant</h2>
                            <p class="wizard-card-subtitle">
                                Mode : <strong x-text="typePeriodeLabel"></strong>
                            </p>
                        </div>
                    </header>

                    <div class="wizard-grid-2">
                        <div class="wizard-field">
                            <label class="wizard-label">Période <span class="req">*</span></label>
                            <div class="wizard-select">
                                <select x-model="selectedPeriode" @change="onPeriodeChange()" required>
                                    <option value="">— Choisir —</option>
                                    <template x-for="p in periodesDisponibles" :key="p.value">
                                        <option :value="String(p.value)" x-text="p.label"></option>
                                    </template>
                                </select>
                                <i class="fa-solid fa-chevron-down wizard-select-icon"></i>
                            </div>
                        </div>

                        <div class="wizard-field">
                            <label class="wizard-label">Montant payé (USD) <span class="req">*</span></label>
                            <input type="number" name="montant_paye_usd"
                                   step="0.01" min="0"
                                   x-model.number="montantPaye"
                                   @input="syncUsdToFc()"
                                   :max="totalAttendu" required
                                   class="wizard-input">
                            {{-- Équivalent FC en direct --}}
                            <div class="fc-hint" x-show="montantPayeFC > 0">
                                <i class="fa-solid fa-arrow-right"></i>
                                <span x-text="`${fc(montantPayeFC)} FC`"></span>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-info">
                        <div class="wizard-info-row">
                            <span>Montant par élève</span>
                            <strong x-text="`$${fmt(montantAttenduParEleve)} (${fc(montantAttenduParEleveFCNum)} FC)`"></strong>
                        </div>
                        <div class="wizard-info-row">
                            <span>Élèves sélectionnés</span>
                            <strong x-text="selectedEleveIds.length"></strong>
                        </div>
                        <div class="wizard-info-row">
                            <span>Total attendu</span>
                            <strong x-text="`$${fmt(totalAttendu)} (${fc(totalAttenduFCNum)} FC)`"></strong>
                        </div>
                        <div class="wizard-info-row">
                            <span>Taux de change</span>
                            <strong x-text="`${fmt(tauxChange)} FC/USD`"></strong>
                        </div>
                        <div class="wizard-info-row" x-show="montantPaye > 0">
                            <span>Montant payé</span>
                            <strong class="text-accent"
                                    x-text="`$${fmt(montantPaye)} (${fc(montantPayeFC)} FC)`"></strong>
                        </div>
                    </div>

                    <div class="wizard-field">
                        <label class="wizard-label">Commentaire</label>
                        <textarea name="commentaire" rows="2" maxlength="500"
                                  class="wizard-textarea"
                                  placeholder="Optionnel">{{ $config['initial']['commentaire'] }}</textarea>
                    </div>

                    <footer class="wizard-footer">
                        <button type="button" class="btn btn-ghost" @click="prev()">
                            <i class="fa-solid fa-arrow-left"></i> Retour
                        </button>
                        <button type="button" class="btn btn-primary"
                                :disabled="!canGoStep3" @click="next()">
                            Continuer <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </footer>
                </section>

                {{-- ═══ ÉTAPE 3 : Confirmation ═══ --}}
                <section x-show="currentStep === 3" x-cloak class="wizard-card">
                    <header class="wizard-card-header">
                        <div class="wizard-card-icon wizard-card-icon-success">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div>
                            <h2 class="wizard-card-title">Confirmation</h2>
                            <p class="wizard-card-subtitle">Vérifiez avant validation</p>
                        </div>
                    </header>

                    <div class="wizard-summary">
                        <div class="summary-item">
                            <span class="summary-label">Élève(s)</span>
                            <p class="summary-value" x-text="selectedEleveIds.length + ' élève(s)'"></p>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Salle</span>
                            <p class="summary-value" x-text="selectedSalle?.nom || '—'"></p>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Période</span>
                            <p class="summary-value" x-text="periodeLabel || '—'"></p>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Montant total</span>
                            <p class="summary-value summary-value-accent"
                               x-text="`$${fmt(montantPaye)} (${fc(montantPayeFC)} FC)`"></p>
                        </div>
                    </div>

                    <template x-if="selectedEleveIds.length > 0">
                        <div class="wizard-list">
                            <h4 class="wizard-list-title">
                                Élèves concernés
                                <span class="badge-count-inline" x-text="selectedEleveIds.length"></span>
                            </h4>
                            <ul class="wizard-list-items">
                                <template x-for="id in selectedEleveIds" :key="'l-' + id">
                                    <li x-text="getEleveNom(id)"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <footer class="wizard-footer">
                        <button type="button" class="btn btn-ghost" @click="prev()">
                            <i class="fa-solid fa-arrow-left"></i> Retour
                        </button>
                        <button type="submit" class="btn btn-primary"
                                :disabled="!canSubmit || submitting">
                            <span x-show="!submitting">
                                <i class="fa-solid fa-check"></i> Confirmer le paiement
                            </span>
                            <span x-show="submitting">
                                <i class="fa-solid fa-spinner fa-spin"></i> Enregistrement…
                            </span>
                        </button>
                    </footer>
                </section>
            </div>

            {{-- PANNEAU LATÉRAL --}}
            <aside class="wizard-aside">
                <template x-if="selectedSalle">
                    <div class="aside-card">
                        <h3 class="aside-title">
                            <i class="fa-solid fa-school"></i> Détails de la salle
                        </h3>
                        <dl class="aside-dl">
                            <div class="aside-row">
                                <dt>Section</dt>
                                <dd x-text="selectedSalle.section || '—'"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Session</dt>
                                <dd x-text="selectedSalle.session || '—'"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Élèves inscrits</dt>
                                <dd x-text="elevesCourants.length"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Mode</dt>
                                <dd><span class="pill pill-indigo" x-text="typePeriodeLabel"></span></dd>
                            </div>
                            <div class="aside-row aside-row-total">
                                <dt>Frais périodique</dt>
                                <dd>
                                    <strong x-text="`$${fmt(montantAttenduParEleve)}`"></strong>
                                    <small x-text="`${fc(montantAttenduParEleveFCNum)} FC`"></small>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </template>
            </aside>
        </div>
    </form>
</div>

<style>
    .page { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
    .page-header { display: flex; flex-direction: column; align-items: flex-start; gap: 1.25rem; margin-bottom: 2rem; }
    @media (min-width: 768px) { .page-header { flex-direction: row; justify-content: space-between; align-items: center; } }
    .page-title { display: flex; align-items: center; gap: 0.6rem; font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem; }
    .title-icon { color: #6366f1; }
    .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; }

    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.7rem 1.25rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s; }
    .btn-sm { padding: 0.55rem 1rem; font-size: 0.85rem; }
    .btn-primary { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; box-shadow: 0 4px 12px rgba(79,70,229,0.25); }
    .btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(79,70,229,0.35); }
    .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-ghost { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .btn-ghost:hover { border-color: #6366f1; color: #4f46e5; background: #f8fafc; }

    .flash { display: flex; gap: 0.65rem; align-items: flex-start; padding: 0.9rem 1.1rem; border-radius: 12px; margin-bottom: 1.25rem; font-size: 0.9rem; }
    .flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
    .flash-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .flash-list { list-style: disc; margin: 0.4rem 0 0 1.25rem; padding: 0; font-size: 0.85rem; }
    .alert-warning { display: flex; gap: 0.75rem; align-items: flex-start; background: #fffbeb; border-left: 4px solid #f59e0b; color: #92400e; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-warning strong { display: block; margin-bottom: 0.15rem; }
    .alert-warning p { margin: 0; font-size: 0.85rem; }

    .filters-card { background: #fff; border: 1px solid #f1f5f9; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 1.5rem; margin-bottom: 1.5rem; }
    .filters-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; padding-bottom: 1rem; margin-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; }
    .filters-header-left { display: flex; align-items: center; gap: 0.6rem; }
    .filter-icon { color: #6366f1; }
    .filters-title { font-weight: 700; color: #0f172a; font-size: 0.95rem; }

    .cascade-steps { display: flex; align-items: center; gap: 0.5rem; padding: 1rem 1.25rem; margin-bottom: 1.25rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
    .cascade-step { display: flex; align-items: center; gap: 0.5rem; }
    .cascade-circle { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e2e8f0; color: #94a3b8; font-weight: 700; font-size: 0.8rem; transition: all 0.3s; flex-shrink: 0; }
    .cascade-step.is-active .cascade-circle { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; box-shadow: 0 4px 12px rgba(79,70,229,0.3); }
    .cascade-step.is-done .cascade-circle { background: #ecfdf5; color: #059669; }
    .cascade-label { font-size: 0.8rem; font-weight: 600; color: #94a3b8; }
    .cascade-step.is-active .cascade-label { color: #4f46e5; }
    .cascade-step.is-done .cascade-label { color: #059669; }
    .cascade-line { flex: 1; height: 2px; background: #e2e8f0; border-radius: 2px; min-width: 20px; }
    .cascade-line.is-done { background: #86efac; }
    @media (max-width: 640px) { .cascade-label { display: none; } }

    .filters-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
    @media (min-width: 768px) { .filters-grid { grid-template-columns: 1fr 1fr; } }

    .filter-field { display: flex; flex-direction: column; gap: 0.35rem; }
    .filter-label { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; font-size: 0.7rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .filter-label i { color: #6366f1; }
    .badge-count-inline { display: inline-flex; align-items: center; background: #eef2ff; color: #4f46e5; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.45rem; border-radius: 9999px; }

    .filter-select { width: 100%; padding: 0.7rem 2.5rem 0.7rem 0.9rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.9rem; color: #0f172a; font-weight: 500; outline: none; cursor: pointer; appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 1.1rem; transition: all 0.2s; }
    .filter-select:focus { border-color: #6366f1; background-color: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .filter-select:disabled { opacity: 0.6; cursor: not-allowed; background-color: #f1f5f9; }

    /* MODE SWITCH */
    .mode-switch { display: flex; gap: 0.5rem; padding: 0.4rem; background: #f1f5f9; border-radius: 12px; margin-bottom: 1.25rem; }
    .mode-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.7rem 1rem; border-radius: 10px; background: transparent; border: none; cursor: pointer; font-weight: 600; font-size: 0.9rem; color: #64748b; transition: all 0.2s; }
    .mode-btn:hover { background: rgba(255,255,255,0.6); }
    .mode-btn-active { background: white; color: #4f46e5; box-shadow: 0 2px 8px rgba(79,70,229,0.15); }
    .mode-count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 20px; padding: 0 0.4rem; background: #4f46e5; color: white; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; }
    .mode-btn:not(.mode-btn-active) .mode-count { background: #94a3b8; }

    /* WIZARD */
    .wizard-progress { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1.5rem; padding: 1rem 1.25rem; background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; }
    .wizard-step { display: flex; align-items: center; gap: 0.5rem; flex: 1; }
    .wizard-step-circle { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #94a3b8; font-weight: 700; font-size: 0.85rem; border: 2px solid transparent; cursor: pointer; transition: all 0.2s; flex-shrink: 0; }
    .wizard-step.is-active .wizard-step-circle { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; border-color: #c7d2fe; box-shadow: 0 4px 12px rgba(79,70,229,0.3); }
    .wizard-step.is-completed .wizard-step-circle { background: #ecfdf5; color: #059669; border-color: #86efac; }
    .wizard-step-circle:disabled { cursor: not-allowed; opacity: 0.5; }
    .wizard-step-label { font-size: 0.8rem; font-weight: 600; color: #94a3b8; display: none; }
    @media (min-width: 768px) { .wizard-step-label { display: inline; } }
    .wizard-step.is-active .wizard-step-label { color: #4f46e5; }
    .wizard-step.is-completed .wizard-step-label { color: #059669; }
    .wizard-step-line { flex: 1; height: 2px; background: #e2e8f0; border-radius: 2px; margin-left: 0.5rem; }
    .wizard-step.is-completed .wizard-step-line { background: #86efac; }

    .wizard-layout { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
    @media (min-width: 1024px) { .wizard-layout { grid-template-columns: 2fr 1fr; } }
    .wizard-main { display: flex; flex-direction: column; gap: 1.25rem; }
    .wizard-aside { display: flex; flex-direction: column; gap: 1rem; }

    .wizard-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 1.5rem; }
    .wizard-card-header { display: flex; align-items: flex-start; gap: 0.9rem; margin-bottom: 1.5rem; }
    .wizard-card-icon { width: 44px; height: 44px; border-radius: 12px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .wizard-card-icon-success { background: #ecfdf5; color: #059669; }
    .wizard-card-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0 0 0.2rem; }
    .wizard-card-subtitle { font-size: 0.85rem; color: #64748b; margin: 0; }

    .wizard-field { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.25rem; }
    .wizard-label { display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .req { color: #ef4444; }

    .wizard-select { position: relative; }
    .wizard-select select { width: 100%; padding: 0.75rem 2.5rem 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.95rem; color: #0f172a; appearance: none; cursor: pointer; outline: none; transition: all 0.2s; }
    .wizard-select select:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .wizard-select-icon { position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 0.85rem; }

    .wizard-input, .wizard-textarea { width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.95rem; color: #0f172a; outline: none; transition: all 0.2s; font-family: inherit; }
    .wizard-input:focus, .wizard-textarea:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .wizard-textarea { resize: vertical; min-height: 60px; }

    /* Affichage FC inline */
    .fc-hint { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; font-weight: 600; color: #059669; margin-top: 0.4rem; }

    .wizard-grid-2 { display: grid; grid-template-columns: 1fr; gap: 1rem; }
    @media (min-width: 640px) { .wizard-grid-2 { grid-template-columns: 1fr 1fr; } }

    .wizard-toolbar { display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1rem; }
    @media (min-width: 640px) { .wizard-toolbar { flex-direction: row; align-items: center; justify-content: space-between; } }
    .wizard-search { position: relative; flex: 1; }
    .wizard-search i { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
    .wizard-search input { width: 100%; padding: 0.6rem 1rem 0.6rem 2.4rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.9rem; outline: none; transition: all 0.2s; }
    .wizard-search input:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .wizard-toolbar-actions { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .wizard-count { font-size: 0.8rem; color: #64748b; }
    .wizard-count strong { color: #4f46e5; }
    .wizard-link { background: none; border: none; cursor: pointer; font-size: 0.8rem; font-weight: 600; color: #4f46e5; padding: 0; }
    .wizard-link.muted { color: #94a3b8; }
    .wizard-link:disabled { opacity: 0.5; cursor: not-allowed; }

    .students-grid { display: grid; grid-template-columns: 1fr; gap: 0.5rem; max-height: 340px; overflow-y: auto; }
    @media (min-width: 640px) { .students-grid { grid-template-columns: repeat(2, 1fr); } }
    .student-card { display: flex; align-items: center; gap: 0.65rem; padding: 0.65rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fff; cursor: pointer; text-align: left; transition: all 0.15s; }
    .student-card:hover { border-color: #c7d2fe; }
    .student-card.is-selected { border-color: #6366f1; background: #eef2ff; box-shadow: 0 2px 8px rgba(99,102,241,0.12); }
    .student-avatar { width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.72rem; flex-shrink: 0; }
    .student-name { font-weight: 600; color: #0f172a; font-size: 0.85rem; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .student-check { color: #4f46e5; font-size: 1.1rem; }
    .student-circle { color: #cbd5e1; font-size: 1.1rem; }
    .wizard-empty { text-align: center; color: #94a3b8; padding: 1rem; font-size: 0.85rem; grid-column: 1 / -1; }

    .wizard-info { display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; margin-bottom: 1.25rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
    .wizard-info-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; gap: 1rem; }
    .wizard-info-row span { color: #64748b; }
    .wizard-info-row strong { color: #0f172a; font-weight: 700; text-align: right; }
    .wizard-info-row .text-accent { color: #4f46e5; }

    .wizard-summary { display: grid; grid-template-columns: 1fr; gap: 0.75rem; margin-bottom: 1.5rem; }
    @media (min-width: 640px) { .wizard-summary { grid-template-columns: repeat(2, 1fr); } }
    .summary-item { padding: 0.9rem 1rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
    .summary-label { display: block; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: #94a3b8; margin-bottom: 0.25rem; }
    .summary-value { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
    .summary-value-accent { color: #4f46e5; }

    .wizard-list { padding: 1rem; margin-bottom: 1.25rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
    .wizard-list-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; font-weight: 700; color: #475569; margin: 0 0 0.5rem; text-transform: uppercase; }
    .wizard-list-items { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 0.4rem; }
    .wizard-list-items li { background: #fff; padding: 0.25rem 0.7rem; border-radius: 9999px; font-size: 0.8rem; color: #334155; border: 1px solid #e2e8f0; }

    .wizard-footer { display: flex; justify-content: space-between; gap: 0.6rem; padding-top: 1.25rem; margin-top: 1.25rem; border-top: 1px solid #f1f5f9; }
    @media (max-width: 640px) { .wizard-footer { flex-direction: column-reverse; } .wizard-footer .btn { width: 100%; justify-content: center; } }

    .aside-card { background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; padding: 1.25rem; }
    .aside-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; font-weight: 700; color: #0f172a; margin: 0 0 1rem; }
    .aside-title i { color: #6366f1; }
    .aside-dl { margin: 0; }
    .aside-row { display: flex; justify-content: space-between; gap: 0.5rem; padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
    .aside-row:last-child { border-bottom: none; }
    .aside-row dt { color: #64748b; margin: 0; }
    .aside-row dd { color: #0f172a; font-weight: 600; margin: 0; text-align: right; }
    .aside-row-total dd strong { color: #4f46e5; font-size: 1rem; display: block; }
    .aside-row-total dd small { display: block; color: #94a3b8; font-weight: 500; font-size: 0.72rem; }

    .pill { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; }
    .pill-indigo { background: #eef2ff; color: #4338ca; }

    [x-cloak] { display: none !important; }
</style>

<script>
function paiementPage(config) {
    return {
        /* ---------------- DONNÉES ---------------- */
        sections:       config.sections       || [],
        sallesData:     config.sallesData     || [],
        elevesParSalle: config.elevesParSalle || {},
        mois:           config.mois           || [],
        tranches:       config.tranches       || [],
        tauxChange:     Number(config.tauxChange || 2800),
        isOpen:         Boolean(config.isOpen),

        /* ---------------- ÉTAT ---------------- */
        selectedSectionId: '',
        selectedSalleId:   '',
        selectedEleveIds:  [],
        selectedPeriode:   '',
        typePeriode:       '',
        montantPaye:       0,
        montantPayeFC:     0,
        eleveSearch:       '',
        currentStep:       1,
        submitting:        false,
        selectionMode:     'simple',   // 'simple' | 'multiple'
        autoFilled:        true,       // permet de savoir si le montant est auto ou manuel

        stepLabels: ['Élèves', 'Période & montant', 'Confirmation'],

        /* ---------------- INIT ---------------- */
        init() {
            const ini = config.initial || {};

            if (ini.salleId) {
                this.selectedSalleId = String(ini.salleId);
                const salle = this.sallesData.find(s => String(s.id) === String(ini.salleId));
                if (salle) this.selectedSectionId = String(salle.section_id);
                this.updateTypePeriode(this.selectedSalleId);
            }

            if (Array.isArray(ini.eleveIds) && ini.eleveIds.length) {
                this.selectedEleveIds = ini.eleveIds.map(Number);
                this.selectionMode = this.selectedEleveIds.length > 1 ? 'multiple' : 'simple';
            }
            if (ini.periode) this.selectedPeriode = String(ini.periode);
            if (ini.typePeriode) this.typePeriode = String(ini.typePeriode);
            if (ini.montantPaye) {
                this.montantPaye = parseFloat(ini.montantPaye) || 0;
                this.autoFilled = false;
                this.syncUsdToFc();
            }
        },

        /* ---------------- GETTERS ---------------- */
        get sallesFiltrees() {
            if (!this.selectedSectionId) return [];
            return this.sallesData.filter(s => String(s.section_id) === String(this.selectedSectionId));
        },

        get selectedSalle() {
            return this.sallesData.find(s => String(s.id) === String(this.selectedSalleId)) || null;
        },

        get elevesCourants() {
            return this.elevesParSalle[this.selectedSalleId] || [];
        },

        get filteredEleves() {
            const list = this.elevesCourants;
            const q = (this.eleveSearch || '').trim().toLowerCase();
            if (!q) return list;
            return list.filter(e => (e.nom_complet || '').toLowerCase().includes(q));
        },

        get periodesDisponibles() {
            return this.typePeriode === 'mensuel' ? this.mois : this.tranches;
        },

        get typePeriodeLabel() {
            if (this.typePeriode === 'mensuel') return 'Mensuel';
            if (this.typePeriode === 'tranche') return 'Par tranche';
            return '—';
        },

        get periodeLabel() {
            const p = this.periodesDisponibles.find(x => String(x.value) === String(this.selectedPeriode));
            return p ? p.label : '';
        },

        get montantAttenduParEleve() {
            if (!this.selectedSalle) return 0;
            return this.typePeriode === 'mensuel'
                ? parseFloat(this.selectedSalle.frais_scolarite_mensuel || 0)
                : parseFloat(this.selectedSalle.frais_par_tranche || 0);
        },

        get montantAttenduParEleveFCNum() {
            return Math.round(this.montantAttenduParEleve * this.tauxChange);
        },

        get totalAttendu() {
            return this.montantAttenduParEleve * this.selectedEleveIds.length;
        },

        get totalAttenduFCNum() {
            return Math.round(this.totalAttendu * this.tauxChange);
        },

        /* ---------------- VALIDATION ---------------- */
        get canGoStep2() {
            return !!this.selectedSalleId && this.selectedEleveIds.length > 0;
        },

        get canGoStep3() {
            if (!this.selectedPeriode) return false;
            if (this.montantPaye <= 0) return false;
            if (this.selectedEleveIds.length > 1) {
                return Math.abs(this.montantPaye - this.totalAttendu) < 0.01;
            }
            return this.montantPaye <= this.totalAttendu + 0.01;
        },

        get canSubmit() {
            return this.canGoStep3 && !this.submitting && this.isOpen;
        },

        /* ---------------- NAVIGATION ---------------- */
        next() { if (this.currentStep < 3) this.currentStep++; },
        prev() { if (this.currentStep > 1) this.currentStep--; this.submitting = false; },
        goToStep(n) { if (n <= this.currentStep + 1) this.currentStep = n; },

        /* ---------------- SÉLECTION ---------------- */
        setSelectionMode(mode) {
            if (mode === this.selectionMode) return;
            this.selectionMode = mode;
            this.selectedEleveIds = [];
            this.montantPaye = 0;
            this.montantPayeFC = 0;
            this.autoFilled = true;
        },

        onSectionChange() {
            this.selectedSalleId = '';
            this.onSalleChange();
        },

        onSalleChange() {
            this.selectedEleveIds = [];
            this.selectedPeriode = '';
            this.montantPaye = 0;
            this.montantPayeFC = 0;
            this.eleveSearch = '';
            this.autoFilled = true;
            this.updateTypePeriode(this.selectedSalleId);
        },

        onPeriodeChange() {
            if (this.autoFilled) this.autoFillMontant();
        },

        updateTypePeriode(salleId) {
            const salle = this.sallesData.find(s => String(s.id) === String(salleId));
            this.typePeriode = salle ? (salle.mode_paiement || '') : '';
        },

        toggleEleve(id) {
            const i = this.selectedEleveIds.indexOf(id);

            if (this.selectionMode === 'simple') {
                // Un seul élève : on remplace
                this.selectedEleveIds = (i === -1 && id) ? [id] : [];
            } else {
                // Multiple
                if (i === -1) this.selectedEleveIds.push(id);
                else this.selectedEleveIds.splice(i, 1);
            }

            this.autoFilled = true;
            this.autoFillMontant();
        },

        selectAll() {
            const ids = this.filteredEleves.map(e => e.id);
            this.selectedEleveIds = this.selectionMode === 'simple'
                ? (ids.length > 0 ? [ids[0]] : [])
                : Array.from(new Set([...this.selectedEleveIds, ...ids]));
            this.autoFilled = true;
            this.autoFillMontant();
        },

        deselectAll() {
            const ids = new Set(this.filteredEleves.map(e => e.id));
            this.selectedEleveIds = this.selectedEleveIds.filter(id => !ids.has(id));
            this.autoFilled = true;
            this.autoFillMontant();
        },

        /**
         * ✅ Pré-remplit le montant dès qu'au moins un élève est sélectionné.
         * Fonctionne aussi pour 1 élève (corrige le bug "toujours 0").
         */
        autoFillMontant() {
            const nb = this.selectedEleveIds.length;

            if (nb === 0) {
                this.montantPaye = 0;
                this.montantPayeFC = 0;
                return;
            }

            this.montantPaye = this.totalAttendu;
            this.syncUsdToFc();
        },

        /* ---------------- CONVERSIONS ---------------- */
        syncUsdToFc() {
            const usd = parseFloat(this.montantPaye) || 0;
            this.montantPayeFC = Math.round(usd * this.tauxChange);
        },

        /* ---------------- UTILITAIRES ---------------- */
        reset() {
            this.selectedSectionId = '';
            this.selectedSalleId = '';
            this.onSalleChange();
        },

        getEleveNom(id) {
            const e = this.elevesCourants.find(x => String(x.id) === String(id));
            return e ? e.nom_complet : 'Inconnu';
        },

        initials(name) {
            return (name || '').split(' ').filter(Boolean).slice(0, 2)
                .map(w => w[0].toUpperCase()).join('') || '?';
        },

        fmt(v) {
            return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(v || 0);
        },

        fc(v) {
            return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);
        },

        onSubmit(e) {
            if (!this.canSubmit) { e.preventDefault(); return; }
            this.submitting = true;
        },
    };
}
</script>
@endsection