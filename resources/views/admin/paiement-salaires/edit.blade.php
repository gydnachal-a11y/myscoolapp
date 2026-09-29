@extends('layouts.admin')

@section('page_title', 'Modifier paiement salaire')
@section('page_subtitle', $paiementSalaire->user?->name . ' — ' . $paiementSalaire->mois_label)

@section('content')
<div class="page">

    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-pen-to-square title-icon" aria-hidden="true"></i>
                Modifier le paiement
            </h1>
            <p class="page-subtitle">
                <strong>{{ $paiementSalaire->user?->name }}</strong>
                · {{ $paiementSalaire->mois_label }}
                · #{{ $paiementSalaire->id }}
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.paiement-salaires.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour</span>
            </a>
        </div>
    </header>

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

    <div x-data="paiementSalaireEdit({
        usersData: {{ Js::from($usersData) }},
        mois: {{ Js::from($mois) }},
        tauxChange: {{ (float) ($tauxChange ?? 2800) }},
        initial: {
            userId: {{ $paiementSalaire->user_id }},
            moisId: {{ $paiementSalaire->mois_scolaire_id }},
            montantPaye: {{ (float) $paiementSalaire->montant_paye_usd }},
            motifEcart: {{ Js::from($paiementSalaire->motif_ecart ?? '') }},
            motifEcartType: {{ Js::from($paiementSalaire->motif_ecart_type ?? '') }},
            datePaiement: '{{ $paiementSalaire->date_paiement?->format('Y-m-d') }}'
        }
    })" x-init="init()" x-cloak>

        <form action="{{ route('admin.paiement-salaires.update', $paiementSalaire) }}"
              method="POST"
              @submit="onSubmit($event)"
              novalidate>
            @csrf @method('PUT')

            <div class="wizard-layout">
                <div class="wizard-main">
                    <section class="wizard-card">
                        <header class="wizard-card-header">
                            <div class="wizard-card-icon"><i class="fa-solid fa-user-edit" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="wizard-card-title">Informations du paiement</h2>
                                <p class="wizard-card-subtitle">Modifiez les montants et le motif d'écart</p>
                            </div>
                        </header>

                        {{-- Employé --}}
                        <div class="wizard-field">
                            <label class="wizard-label">Employé <span class="req">*</span></label>
                            <div class="wizard-select">
                                <select name="user_id" x-model.number="selectedUserId" @change="onUserChange()" required>
                                    <option value="">— Sélectionner —</option>
                                    <template x-for="u in usersData" :key="u.id">
                                        <option :value="u.id" x-text="u.name"></option>
                                    </template>
                                </select>
                                <i class="fa-solid fa-chevron-down wizard-select-icon" aria-hidden="true"></i>
                            </div>
                        </div>

                        {{-- Mois --}}
                        <div class="wizard-field">
                            <label class="wizard-label">Mois <span class="req">*</span></label>
                            <div class="wizard-select">
                                <select name="mois_scolaire_id" x-model.number="selectedMoisId" required>
                                    <option value="">— Sélectionner —</option>
                                    <template x-for="m in mois" :key="m.id">
                                        <option :value="m.id" x-text="m.nom_mois || m.mois"></option>
                                    </template>
                                </select>
                                <i class="fa-solid fa-chevron-down wizard-select-icon" aria-hidden="true"></i>
                            </div>
                        </div>

                        {{-- Bloc montants calculés --}}
                        <div class="wizard-info">
                            <div class="wizard-info-row">
                                <span>Salaire de base</span>
                                <strong x-text="`${fmt(salaireBase)} $`"></strong>
                            </div>
                            <div class="wizard-info-row">
                                <span>Dette à rembourser</span>
                                <strong class="text-danger" x-text="`-${fmt(detteARembourser)} $`"></strong>
                            </div>
                            <div class="wizard-info-row">
                                <span>Net à payer</span>
                                <strong class="text-primary" x-text="`${fmt(netAPayer)} $`"></strong>
                            </div>
                        </div>

                        {{-- Montant payé USD + FC --}}
                        <div class="wizard-grid-2">
                            <div class="wizard-field">
                                <label class="wizard-label">Montant payé (USD) <span class="req">*</span></label>
                                <input type="number" step="0.01" min="0"
                                       name="montant_paye_usd"
                                       x-model.number="montantPaye"
                                       @input="onMontantChange()"
                                       :max="netAPayer"
                                       class="wizard-input"
                                       :class="{ 'is-invalid': montantPaye > netAPayer }"
                                       required>
                                <p class="text-xs text-muted" x-show="netAPayer > 0">
                                    Max : <span x-text="`${fmt(netAPayer)} $`"></span>
                                </p>
                            </div>
                            <div class="wizard-field">
                                <label class="wizard-label">Montant payé (FC)</label>
                                <input type="number" step="1" min="0"
                                       x-model.number="montantPayeFC"
                                       @input="syncFcToUsd()"
                                       class="wizard-input">
                                <p class="text-xs text-muted">
                                    Taux : <span x-text="tauxChange.toFixed(2)"></span> FC/USD
                                </p>
                            </div>
                        </div>

                        {{-- Motif d'écart (si nécessaire) --}}
                        <div x-show="statut !== 'paye'" x-cloak>
                            <div class="wizard-grid-2">
                                <div class="wizard-field">
                                    <label class="wizard-label">Type de motif <span class="req">*</span></label>
                                    <div class="wizard-select">
                                        <select name="motif_ecart_type" x-model="motifEcartType" @change="checkMotif()">
                                            <option value="">— Choisir —</option>
                                            <option value="avance">Avance déjà versée</option>
                                            <option value="absence">Absence / congé sans solde</option>
                                            <option value="erreur">Erreur de calcul</option>
                                            <option value="sanction">Sanction disciplinaire</option>
                                            <option value="prime">Prime exceptionnelle</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                        <i class="fa-solid fa-chevron-down wizard-select-icon" aria-hidden="true"></i>
                                    </div>
                                </div>
                                <div class="wizard-field" x-show="motifEcartType === 'autre' || !motifEcartType">
                                    <label class="wizard-label">Précision</label>
                                    <textarea name="motif_ecart" x-model="motifEcart" rows="2"
                                              class="wizard-textarea"
                                              placeholder="Détail du motif"></textarea>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="motif_ecart" :value="statut === 'paye' ? '' : motifEcart" x-show="false">

                        {{-- Date --}}
                        <div class="wizard-field">
                            <label class="wizard-label">Date de paiement <span class="req">*</span></label>
                            <input type="date" name="date_paiement" x-model="datePaiement"
                                   class="wizard-input" :max="today" required>
                        </div>

                        {{-- Statut visuel --}}
                        <div class="mt-2">
                            <span class="pill" :class="{
                                'pill-success': statut === 'paye',
                                'pill-warning': statut === 'souspaye',
                                'pill-danger':  statut === 'surpaye'
                            }">
                                <i class="fa-solid"
                                   :class="{
                                       'fa-circle-check': statut === 'paye',
                                       'fa-arrow-down':   statut === 'souspaye',
                                       'fa-arrow-up':     statut === 'surpaye'
                                   }"
                                   aria-hidden="true"></i>
                                <span x-text="statutLabel"></span>
                            </span>
                        </div>

                        <footer class="wizard-footer">
                            <a href="{{ route('admin.paiement-salaires.index') }}" class="btn btn-ghost">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary" :disabled="!canSubmit || submitting">
                                <i class="fa-solid fa-check" aria-hidden="true"></i> Mettre à jour
                            </button>
                        </footer>
                    </section>
                </div>

                <aside class="wizard-aside">
                    <div class="aside-card aside-card-highlight" x-show="selectedUserId">
                        <p class="summary-value summary-value-accent" x-text="selectedUserName || '—'"></p>
                        <p class="summary-label" x-text="selectedMoisLabel"></p>
                    </div>

                    <div class="aside-card">
                        <h3 class="aside-title"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i> Résumé</h3>
                        <dl class="aside-dl">
                            <div class="aside-row">
                                <dt>Salaire de base</dt>
                                <dd x-text="`${fmt(salaireBase)} $`"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Dette remboursée</dt>
                                <dd x-text="`${fmt(detteARembourser)} $`"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Net à payer</dt>
                                <dd x-text="`${fmt(netAPayer)} $`"></dd>
                            </div>
                            <div class="aside-row aside-row-total">
                                <dt>Montant payé</dt>
                                <dd>
                                    <strong x-text="`$${fmt(montantPaye)}`"></strong>
                                    <small x-text="`${fmtFc(montantPayeFC)} FC`"></small>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div>

@include('admin.paiement-salaires._wizard-styles')

<script>
function paiementSalaireEdit(config) {
    return {
        usersData: config.usersData,
        mois: config.mois,
        tauxChange: config.tauxChange,

        selectedUserId: config.initial.userId,
        selectedMoisId: config.initial.moisId,
        montantPaye: config.initial.montantPaye,
        montantPayeFC: 0,
        motifEcart: config.initial.motifEcart,
        motifEcartType: config.initial.motifEcartType,
        datePaiement: config.initial.datePaiement,

        salaireBase: 0,
        detteARembourser: 0,
        reportDette: 0,
        netAPayer: 0,
        statut: 'paye',
        today: new Date().toISOString().split('T')[0],
        submitting: false,

        init() {
            this.updateCalculations();
            this.montantPayeFC = Math.round(this.montantPaye * this.tauxChange);
            this.recalculerStatut();
        },

        updateCalculations() {
            const user = this.usersData.find(u => u.id == this.selectedUserId);
            if (!user) {
                this.salaireBase = this.detteARembourser = this.reportDette = this.netAPayer = 0;
                return;
            }
            this.salaireBase      = parseFloat(user.salaire_attendu_usd) || 0;
            const detteTotale     = parseFloat(user.dette_totale_usd)    || 0;
            this.detteARembourser = Math.min(this.salaireBase, detteTotale);
            this.reportDette      = detteTotale - this.detteARembourser;
            this.netAPayer        = this.salaireBase - this.detteARembourser;
        },

        onUserChange() {
            this.updateCalculations();
            this.recalculerStatut();
        },

        onMontantChange() {
            this.montantPayeFC = Math.round((parseFloat(this.montantPaye) || 0) * this.tauxChange);
            this.recalculerStatut();
        },

        syncFcToUsd() {
            if (this.montantPayeFC && this.tauxChange) {
                this.montantPaye = Math.round((this.montantPayeFC / this.tauxChange) * 100) / 100;
            } else {
                this.montantPaye = 0;
            }
            this.recalculerStatut();
        },

        recalculerStatut() {
            const net  = parseFloat(this.netAPayer) || 0;
            const paye = parseFloat(this.montantPaye) || 0;

            if (Math.abs(paye - net) < 0.01) {
                this.statut = 'paye';
                this.motifEcart = '';
                this.motifEcartType = '';
            } else if (paye < net) {
                this.statut = 'souspaye';
            } else {
                this.statut = 'surpaye';
            }
        },

        checkMotif() {
            if (this.motifEcartType !== 'autre') this.motifEcart = '';
        },

        onSubmit(e) {
            if (!this.canSubmit) {
                e.preventDefault();
                alert('Veuillez corriger les erreurs avant de soumettre.');
                return;
            }
            this.submitting = true;
        },

        get canSubmit() {
            const paye = parseFloat(this.montantPaye) || 0;
            const net  = parseFloat(this.netAPayer) || 0;
            if (paye < 0 || (paye > net && net > 0)) return false;
            if (this.statut !== 'paye' && !this.motifEcartType && !this.motifEcart?.trim()) return false;
            return this.selectedUserId && this.selectedMoisId && this.datePaiement;
        },

        get selectedUserName() {
            return this.usersData.find(u => u.id == this.selectedUserId)?.name || '';
        },
        get selectedMoisLabel() {
            const m = this.mois.find(x => x.id == this.selectedMoisId);
            return m ? (m.nom_mois || m.mois) : '';
        },
        get statutLabel() {
            return { paye: 'Payé', souspaye: 'Sous-payé', surpaye: 'Sur-payé' }[this.statut] || this.statut;
        },

        fmt(v)   { return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(v || 0); },
        fmtFc(v) { return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v || 0); },
    };
}
</script>
@endsection