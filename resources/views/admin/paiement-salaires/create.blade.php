@extends('layouts.admin')

@section('page_title', 'Nouveau paiement de salaire')
@section('page_subtitle', 'Enregistrez les salaires mensuels d\'un ou plusieurs employés')

@section('content')
<div class="page">

    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-plus-circle title-icon" aria-hidden="true"></i>
                Nouveau paiement groupé
            </h1>
            <p class="page-subtitle">Enregistrez les salaires mensuels d'un ou plusieurs employés</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.paiement-salaires.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour</span>
            </a>
        </div>
    </header>

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
    @if($errors->any())
        <div class="flash flash-error">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <div>
                <strong>Corrigez les erreurs suivantes :</strong>
                <ul class="flash-list">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div x-data="paiementSalaireWizard({
        mode: 'create',
        usersData: {{ Js::from($usersData) }},
        mois: {{ Js::from($mois) }},
        tauxChange: {{ (float) ($tauxChange ?? 2800) }},
        initial: {
            paiements: {{ Js::from(old('paiements', [])) }},
            datePaiement: '{{ old('date_paiement', now()->toDateString()) }}'
        },
        errors: {{ Js::from($errors->any() ? array_keys($errors->toArray()) : []) }}
    })" x-init="init()" x-cloak>

        {{-- Barre de progression --}}
        <div class="wizard-progress">
            <template x-for="(label, i) in stepLabels" :key="i">
                <div class="wizard-step" :class="{
                    'is-active':    currentStep === i + 1,
                    'is-completed': currentStep > i + 1
                }">
                    <button type="button" class="wizard-step-circle"
                            @click="goToStep(i + 1)"
                            :disabled="i + 1 > currentStep + 1"
                            :aria-current="currentStep === i + 1 ? 'step' : false">
                        <template x-if="currentStep > i + 1">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                        </template>
                        <template x-if="currentStep <= i + 1">
                            <span x-text="i + 1"></span>
                        </template>
                    </button>
                    <span class="wizard-step-label" x-text="label"></span>
                    <div class="wizard-step-line" x-show="i < stepLabels.length - 1"></div>
                </div>
            </template>
        </div>

        <form action="{{ route('admin.paiement-salaires.store') }}" method="POST" @submit="onSubmit($event)" novalidate>
            @csrf

            {{-- Champs cachés --}}
            <template x-for="(item, index) in paiementsDetails" :key="index">
                <div>
                    <input type="hidden" :name="`paiements[${index}][user_id]`"          :value="item.userId">
                    <input type="hidden" :name="`paiements[${index}][mois_scolaire_id]`" :value="item.moisId">
                    <input type="hidden" :name="`paiements[${index}][montant_paye_usd]`" :value="item.montantPaye">
                    <input type="hidden" :name="`paiements[${index}][motif_ecart]`"      :value="item.motifEcart">
                    <input type="hidden" :name="`paiements[${index}][motif_ecart_type]`" :value="item.motifEcartType">
                    <input type="hidden" :name="`paiements[${index}][date_paiement]`"    :value="datePaiement">
                </div>
            </template>

            <div class="wizard-layout">
                <div class="wizard-main">

                    {{-- ÉTAPE 1 : Employés --}}
                    <section x-show="currentStep === 1" class="wizard-card">
                        <header class="wizard-card-header">
                            <div class="wizard-card-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="wizard-card-title">Employés concernés</h2>
                                <p class="wizard-card-subtitle">Sélectionnez un ou plusieurs employés</p>
                            </div>
                        </header>

                        <div class="wizard-toolbar">
                            <div class="wizard-search">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" x-model="searchEmploye" placeholder="Rechercher un employé…">
                            </div>
                            <div class="wizard-toolbar-actions">
                                <span class="wizard-count" x-text="`${selectedUserIds.length} sélectionné(s)`"></span>
                                <button type="button" @click="selectAllEmployes()" class="wizard-link">Tout</button>
                                <button type="button" @click="deselectAllEmployes()" class="wizard-link muted">Aucun</button>
                            </div>
                        </div>

                        <div class="employe-list">
                            <template x-for="u in filteredEmployes" :key="u.id">
                                <button type="button"
                                        class="employe-item"
                                        :class="{ 'is-selected': selectedUserIds.includes(u.id) }"
                                        @click="toggleUser(u.id)">
                                    <div class="employe-avatar" x-text="initials(u.name)"></div>
                                    <div class="employe-info">
                                        <p class="employe-name" x-text="u.name"></p>
                                        <p class="employe-detail" x-text="`Salaire : ${fmt(u.salaire_attendu_usd)} $`"></p>
                                        <p class="employe-detail">
                                            <template x-if="u.dette_totale_usd > 0">
                                                <span class="pill pill-danger">
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                    Dette : <span x-text="fmt(u.dette_totale_usd)"></span> $
                                                </span>
                                            </template>
                                            <template x-if="u.dette_totale_usd == 0">
                                                <span class="pill pill-success">
                                                    <i class="fa-solid fa-check-circle"></i> Aucune dette
                                                </span>
                                            </template>
                                        </p>
                                    </div>
                                    <i class="fa-solid"
                                       :class="selectedUserIds.includes(u.id)
                                           ? 'fa-circle-check text-indigo-600'
                                           : 'fa-circle text-gray-300'"
                                       aria-hidden="true"></i>
                                </button>
                            </template>
                            <p x-show="filteredEmployes.length === 0" class="wizard-empty text-center py-4 text-gray-500">
                                Aucun employé trouvé.
                            </p>
                        </div>

                        <footer class="wizard-footer">
                            <a href="{{ route('admin.paiement-salaires.index') }}" class="btn btn-ghost">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Annuler
                            </a>
                            <button type="button" class="btn btn-primary"
                                    :disabled="selectedUserIds.length === 0"
                                    @click="next()">
                                Continuer <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </footer>
                    </section>

                    {{-- ÉTAPE 2 : Mois --}}
                    <section x-show="currentStep === 2" class="wizard-card">
                        <header class="wizard-card-header">
                            <div class="wizard-card-icon"><i class="fa-regular fa-calendar-alt" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="wizard-card-title">Mois concernés</h2>
                                <p class="wizard-card-subtitle">Sélectionnez un ou plusieurs mois de paie</p>
                            </div>
                        </header>

                        <div class="wizard-toolbar">
                            <div class="wizard-search">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" x-model="searchMois" placeholder="Rechercher un mois…">
                            </div>
                            <div class="wizard-toolbar-actions">
                                <span class="wizard-count" x-text="`${selectedMoisIds.length} sélectionné(s)`"></span>
                                <button type="button" @click="selectAllMois()" class="wizard-link">Tout</button>
                                <button type="button" @click="deselectAllMois()" class="wizard-link muted">Aucun</button>
                            </div>
                        </div>

                        <div class="mois-list">
                            <template x-for="m in filteredMois" :key="m.id">
                                <button type="button"
                                        class="mois-item"
                                        :class="{ 'is-selected': selectedMoisIds.includes(m.id) }"
                                        @click="toggleMois(m.id)">
                                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                                    <span class="flex-1 text-left" x-text="m.nom_mois || m.mois"></span>
                                    <i class="fa-solid"
                                       :class="selectedMoisIds.includes(m.id)
                                           ? 'fa-circle-check text-indigo-600'
                                           : 'fa-circle text-gray-300'"
                                       aria-hidden="true"></i>
                                </button>
                            </template>
                        </div>

                        <footer class="wizard-footer">
                            <button type="button" class="btn btn-ghost" @click="prev()">
                                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Retour
                            </button>
                            <button type="button" class="btn btn-primary"
                                    :disabled="selectedMoisIds.length === 0"
                                    @click="next()">
                                Continuer <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </footer>
                    </section>

                    {{-- ÉTAPE 3 : Montants --}}
                    <section x-show="currentStep === 3" class="wizard-card">
                        <header class="wizard-card-header">
                            <div class="wizard-card-icon"><i class="fa-solid fa-dollar-sign" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="wizard-card-title">Montants des paiements</h2>
                                <p class="wizard-card-subtitle" x-text="`${paiementsDetails.length} paiement(s) à saisir`"></p>
                            </div>
                        </header>

                        <div class="space-y-4">
                            <template x-for="(item, index) in paiementsDetails" :key="index">
                                <article class="paiement-item">
                                    <header class="flex justify-between items-center mb-3">
                                        <span class="font-semibold" x-text="getUserName(item.userId)"></span>
                                        <span class="text-sm text-gray-500" x-text="getMoisLabel(item.moisId)"></span>
                                    </header>

                                    <div class="wizard-grid-2">
                                        <div class="wizard-info">
                                            <div class="wizard-info-row">
                                                <span>Salaire de base</span>
                                                <strong x-text="`${fmt(item.salaireBase)} $`"></strong>
                                            </div>
                                            <div class="wizard-info-row">
                                                <span>Dette à rembourser</span>
                                                <strong class="text-danger" x-text="`-${fmt(item.detteARembourser)} $`"></strong>
                                            </div>
                                            <div class="wizard-info-row">
                                                <span>Net à payer</span>
                                                <strong class="text-primary" x-text="`${fmt(item.netAPayer)} $`"></strong>
                                            </div>
                                        </div>

                                        <div class="wizard-field">
                                            <label class="wizard-label">Montant payé (USD) <span class="req">*</span></label>
                                            <input type="number" step="0.01" min="0"
                                                   x-model.number="item.montantPaye"
                                                   :max="item.netAPayer"
                                                   @input="recalculerStatut(item)"
                                                   class="wizard-input"
                                                   :class="{ 'is-invalid': item.error }">
                                            <p class="text-xs text-muted" x-show="item.netAPayer > 0">
                                                Max : <span x-text="`${fmt(item.netAPayer)} $`"></span>
                                            </p>
                                            <p class="text-xs text-danger" x-show="item.error" x-text="item.error"></p>
                                        </div>
                                    </div>

                                    {{-- Motif d'écart (si non soldé) --}}
                                    <div x-show="item.statut !== 'paye'" class="wizard-grid-2 mt-3">
                                        <div class="wizard-field">
                                            <label class="wizard-label">Type de motif <span class="req">*</span></label>
                                            <select x-model="item.motifEcartType" @change="checkMotif(item)" class="wizard-input">
                                                <option value="">— Choisir —</option>
                                                <option value="avance">Avance déjà versée</option>
                                                <option value="absence">Absence / congé sans solde</option>
                                                <option value="erreur">Erreur de calcul</option>
                                                <option value="sanction">Sanction disciplinaire</option>
                                                <option value="prime">Prime exceptionnelle</option>
                                                <option value="autre">Autre</option>
                                            </select>
                                        </div>
                                        <div class="wizard-field" x-show="item.motifEcartType === 'autre' || !item.motifEcartType">
                                            <label class="wizard-label">Précision</label>
                                            <textarea x-model="item.motifEcart" rows="2" class="wizard-textarea" placeholder="Détail du motif"></textarea>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>

                        <footer class="wizard-footer">
                            <button type="button" class="btn btn-ghost" @click="prev()">
                                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Retour
                            </button>
                            <button type="button" class="btn btn-primary" @click="next()">
                                Continuer <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </footer>
                    </section>

                    {{-- ÉTAPE 4 : Confirmation --}}
                    <section x-show="currentStep === 4" class="wizard-card">
                        <header class="wizard-card-header">
                            <div class="wizard-card-icon wizard-card-icon-success">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h2 class="wizard-card-title">Confirmation</h2>
                                <p class="wizard-card-subtitle">Vérifiez les informations avant validation</p>
                            </div>
                        </header>

                        <div class="wizard-summary">
                            <div class="summary-item">
                                <span class="summary-label">Employés</span>
                                <p class="summary-value" x-text="selectedUserIds.length"></p>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Mois</span>
                                <p class="summary-value" x-text="selectedMoisIds.length"></p>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Total paiements</span>
                                <p class="summary-value" x-text="`${paiementsDetails.length} ligne(s)`"></p>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Total à payer</span>
                                <p class="summary-value summary-value-accent" x-text="`$${fmt(totalPaye)}`"></p>
                            </div>
                        </div>

                        <div class="wizard-field">
                            <label class="wizard-label">Date de paiement <span class="req">*</span></label>
                            <input type="date" x-model="datePaiement" class="wizard-input" :max="today">
                        </div>

                        <footer class="wizard-footer">
                            <button type="button" class="btn btn-ghost" @click="prev()">
                                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Retour
                            </button>
                            <button type="submit" class="btn btn-success" :disabled="!canSubmit || submitting">
                                <i class="fa-solid fa-check" aria-hidden="true"></i> Confirmer
                            </button>
                        </footer>
                    </section>
                </div>

                {{-- ASIDE --}}
                <aside class="wizard-aside">
                    <div class="aside-card aside-card-highlight" x-show="selectedUserIds.length > 0">
                        <p class="summary-value summary-value-accent" x-text="`${selectedUserIds.length} employé(s) · ${selectedMoisIds.length} mois`"></p>
                        <p class="summary-label" x-text="`${paiementsDetails.length} paiement(s) à créer`"></p>
                    </div>

                    <div class="aside-card">
                        <h3 class="aside-title"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Résumé global</h3>
                        <dl class="aside-dl">
                            <div class="aside-row">
                                <dt>Employés</dt>
                                <dd x-text="selectedUserIds.length"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Mois</dt>
                                <dd x-text="selectedMoisIds.length"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Total paiements</dt>
                                <dd x-text="paiementsDetails.length"></dd>
                            </div>
                            <div class="aside-row aside-row-total">
                                <dt>Total à payer</dt>
                                <dd>
                                    <strong x-text="`$${fmt(totalPaye)}`"></strong>
                                    <small x-text="`${fmtFc(totalPaye * tauxChange)} FC`"></small>
                                </dd>
                            </div>
                        </dl>
                        <div class="mt-3 flex gap-2 flex-wrap">
                            <template x-if="nbSousPayes > 0">
                                <span class="pill pill-warning" x-text="`${nbSousPayes} sous-payé(s)`"></span>
                            </template>
                            <template x-if="nbSurPayes > 0">
                                <span class="pill pill-danger" x-text="`${nbSurPayes} sur-payé(s)`"></span>
                            </template>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div>

@include('admin.paiement-salaires._wizard-styles')

<script>
function paiementSalaireWizard(config) {
    return {
        mode: config.mode,
        usersData: config.usersData,
        mois: config.mois,
        tauxChange: config.tauxChange,

        selectedUserIds: [],
        selectedMoisIds: [],
        searchEmploye: '',
        searchMois: '',
        paiementsDetails: [],
        datePaiement: config.initial.datePaiement || new Date().toISOString().split('T')[0],
        currentStep: 1,
        submitting: false,
        today: new Date().toISOString().split('T')[0],

        stepLabels: ['Employés', 'Mois', 'Montants', 'Validation'],

        init() {
            // Restaure les erreurs du serveur
            const errs = config.errors || [];
            if (errs.some(f => f.includes('user_id')))         this.currentStep = 1;
            else if (errs.some(f => f.includes('mois_scolaire_id'))) this.currentStep = 2;
            else if (errs.some(f => f.includes('montant') || f.includes('motif'))) this.currentStep = 3;
            else if (errs.length)                               this.currentStep = 4;

            // Restaure les données old()
            const old = config.initial.paiements || [];
            if (old.length) {
                old.forEach(p => {
                    if (!this.selectedUserIds.includes(p.user_id)) this.selectedUserIds.push(p.user_id);
                    if (!this.selectedMoisIds.includes(p.mois_scolaire_id)) this.selectedMoisIds.push(p.mois_scolaire_id);
                });
                this.rebuildDetails();
                old.forEach((p, i) => {
                    const item = this.paiementsDetails[i];
                    if (item) {
                        item.montantPaye     = parseFloat(p.montant_paye_usd) || 0;
                        item.motifEcart      = p.motif_ecart || '';
                        item.motifEcartType  = p.motif_ecart_type || '';
                        this.recalculerStatut(item);
                    }
                });
            }

            // Réactivité
            this.$watch('selectedUserIds', () => this.rebuildDetails());
            this.$watch('selectedMoisIds', () => this.rebuildDetails());
        },

        // GETTERS
        get filteredEmployes() {
            if (!this.searchEmploye) return this.usersData;
            const q = this.searchEmploye.toLowerCase();
            return this.usersData.filter(u => u.name.toLowerCase().includes(q));
        },
        get filteredMois() {
            if (!this.searchMois) return this.mois;
            const q = this.searchMois.toLowerCase();
            return this.mois.filter(m => (m.nom_mois || m.mois).toLowerCase().includes(q));
        },
        get totalPaye() {
            return this.paiementsDetails.reduce((s, i) => s + (parseFloat(i.montantPaye) || 0), 0);
        },
        get nbSousPayes() {
            return this.paiementsDetails.filter(i => i.statut === 'souspaye').length;
        },
        get nbSurPayes() {
            return this.paiementsDetails.filter(i => i.statut === 'surpaye').length;
        },
        get canSubmit() {
            return this.paiementsDetails.length > 0
                && this.paiementsDetails.every(i => !i.error && this.montantValide(i));
        },

        // ACTIONS
        next() { if (this.currentStep < 4 && this.stepValid(this.currentStep)) this.currentStep++; },
        prev() { if (this.currentStep > 1) this.currentStep--; },
        goToStep(n) { if (n <= this.currentStep + 1) this.currentStep = n; },

        stepValid(step) {
            if (step === 1) return this.selectedUserIds.length > 0;
            if (step === 2) return this.selectedMoisIds.length > 0;
            if (step === 3) return this.paiementsDetails.every(i => !i.error && this.montantValide(i));
            return true;
        },

        toggleUser(id) {
            const i = this.selectedUserIds.indexOf(id);
            if (i === -1) this.selectedUserIds.push(id);
            else this.selectedUserIds.splice(i, 1);
        },
        selectAllEmployes()   { this.selectedUserIds = this.filteredEmployes.map(u => u.id); },
        deselectAllEmployes() { this.selectedUserIds = []; },

        toggleMois(id) {
            const i = this.selectedMoisIds.indexOf(id);
            if (i === -1) this.selectedMoisIds.push(id);
            else this.selectedMoisIds.splice(i, 1);
        },
        selectAllMois()   { this.selectedMoisIds = this.filteredMois.map(m => m.id); },
        deselectAllMois() { this.selectedMoisIds = []; },

        rebuildDetails() {
            const previous = new Map(
                this.paiementsDetails.map(p => [`${p.userId}_${p.moisId}`, p])
            );

            this.paiementsDetails = [];
            for (const userId of this.selectedUserIds) {
                const user = this.usersData.find(u => u.id == userId);
                if (!user) continue;

                for (const moisId of this.selectedMoisIds) {
                    const key = `${userId}_${moisId}`;
                    const prev = previous.get(key);

                    const salaireBase      = parseFloat(user.salaire_attendu_usd) || 0;
                    const detteTotale      = parseFloat(user.dette_totale_usd)  || 0;
                    const detteARembourser = Math.min(salaireBase, detteTotale);
                    const reportDette      = detteTotale - detteARembourser;
                    const netAPayer        = salaireBase - detteARembourser;

                    const item = prev || {
                        userId, moisId,
                        salaireBase, detteARembourser, reportDette, netAPayer,
                        montantPaye: netAPayer,
                        motifEcart: '', motifEcartType: '',
                        statut: 'paye', error: ''
                    };

                    // Mise à jour des infos calculées
                    item.salaireBase      = salaireBase;
                    item.detteARembourser = detteARembourser;
                    item.reportDette      = reportDette;
                    item.netAPayer        = netAPayer;

                    this.recalculerStatut(item);
                    this.paiementsDetails.push(item);
                }
            }
        },

        recalculerStatut(item) {
            const net  = parseFloat(item.netAPayer) || 0;
            const paye = parseFloat(item.montantPaye) || 0;

            if (Math.abs(paye - net) < 0.01) {
                item.statut = 'paye';
                item.motifEcart = '';
                item.motifEcartType = '';
            } else if (paye < net) {
                item.statut = 'souspaye';
            } else {
                item.statut = 'surpaye';
            }

            // Validation
            item.error = '';
            if (paye < 0) {
                item.error = 'Le montant ne peut pas être négatif.';
            } else if (paye > net && net > 0) {
                item.error = `Le montant payé ne peut pas dépasser ${this.fmt(net)} $.`;
            } else if (item.statut !== 'paye' && !item.motifEcartType && !item.motifEcart?.trim()) {
                item.error = 'Veuillez préciser un motif d\'écart.';
            }
        },

        montantValide(item) {
            const paye = parseFloat(item.montantPaye) || 0;
            const net  = parseFloat(item.netAPayer) || 0;
            if (paye < 0 || (paye > net && net > 0)) return false;
            if (item.statut !== 'paye' && !item.motifEcartType && !item.motifEcart?.trim()) return false;
            return true;
        },

        checkMotif(item) {
            if (item.motifEcartType !== 'autre') item.motifEcart = '';
            this.recalculerStatut(item);
        },

        onSubmit(e) {
            if (!this.canSubmit) {
                e.preventDefault();
                alert('Veuillez corriger les erreurs avant de soumettre.');
                return;
            }
            this.submitting = true;
        },

        getUserName(id) {
            const u = this.usersData.find(x => x.id == id);
            return u ? u.name : '—';
        },
        getMoisLabel(id) {
            const m = this.mois.find(x => x.id == id);
            return m ? (m.nom_mois || m.mois) : '—';
        },
        initials(name) {
            return name.split(' ').filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('');
        },
        fmt(v)   { return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(v || 0); },
        fmtFc(v) { return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v || 0); },
    };
}
</script>
@endsection