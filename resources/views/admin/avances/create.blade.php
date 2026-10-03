@extends('layouts.admin')

@section('page_title', 'Nouvelle avance')
@section('page_subtitle', 'Créer un bon de paiement anticipé')

@section('content')
@php
    $config = [
        'usersData'  => $usersData  ?? [],
        'mois'       => $mois       ?? [],
        'tauxChange' => (float) ($tauxChange ?? 2800),
        'initial'    => [
            'userId'      => (string) old('user_id', ''),
            'moisId'      => (string) old('mois_scolaire_id', ''),
            'dateAvance'  => (string) old('date_avance', now()->toDateString()),
            'montant'     => (float)  old('montant_avance_usd', 0),
            'montantFC'   => (float)  old('montant_avance_fc', 0),
            'motif'       => (string) old('motif', ''),
            'commentaire' => (string) old('commentaire', ''),
        ],
    ];
@endphp

<div class="page" x-data="avancePage({{ Js::from($config) }})" x-init="init()" x-cloak>

    {{-- EN-TÊTE --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-hand-holding-dollar title-icon"></i>
                Nouvelle avance sur salaire
            </h1>
            <p class="page-subtitle">Créez un bon de paiement anticipé pour un personnel</p>
        </div>
        <a href="{{ route('admin.avances.index') }}" class="btn btn-ghost">
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

    {{-- FILTRE EMPLOYÉ --}}
    <section class="filters-card">
        <div class="filters-header">
            <div class="filters-header-left">
                <i class="fa-solid fa-user-tag filter-icon"></i>
                <span class="filters-title">Sélection de l'employé</span>
            </div>
            <button type="button" class="btn btn-ghost btn-sm"
                    x-show="selectedUserId"
                    @click="reset()">
                <i class="fa-solid fa-rotate-left"></i> Réinitialiser
            </button>
        </div>

        <div class="cascade-steps">
            <div class="cascade-step" :class="selectedUserId ? 'is-done' : 'is-active'">
                <div class="cascade-circle">
                    <template x-if="selectedUserId"><i class="fa-solid fa-check"></i></template>
                    <template x-if="!selectedUserId"><span>1</span></template>
                </div>
                <span class="cascade-label">Employé</span>
            </div>
            <div class="cascade-line" :class="selectedUserId ? 'is-done' : ''"></div>

            <div class="cascade-step" :class="selectedUserId ? 'is-active' : ''">
                <div class="cascade-circle"><span>2</span></div>
                <span class="cascade-label">Détails</span>
            </div>
            <div class="cascade-line"></div>

            <div class="cascade-step">
                <div class="cascade-circle"><span>3</span></div>
                <span class="cascade-label">Confirmation</span>
            </div>
        </div>
    </section>

    {{-- WIZARD --}}
    <form action="{{ route('admin.avances.store') }}" method="POST"
          @submit="onSubmit($event)" novalidate>
        @csrf

        <input type="hidden" name="user_id"            :value="selectedUserId">
        <input type="hidden" name="mois_scolaire_id"   :value="moisId">
        <input type="hidden" name="date_avance"        :value="dateAvance">
        <input type="hidden" name="montant_avance_usd" :value="montant">
        <input type="hidden" name="montant_avance_fc"  :value="montantFC">

        {{-- PROGRESSION --}}
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

                {{-- ═══ ÉTAPE 1 : Employé ═══ --}}
                <section x-show="currentStep === 1" class="wizard-card">
                    <header class="wizard-card-header">
                        <div class="wizard-card-icon"><i class="fa-solid fa-user-tag"></i></div>
                        <div>
                            <h2 class="wizard-card-title">Sélection de l'employé</h2>
                            <p class="wizard-card-subtitle">
                                <span x-show="!selectedUserId">Choisissez l'employé qui bénéficiera de l'avance.</span>
                                <span x-show="selectedUserId"
                                      x-text="`Employé sélectionné : ${selectedUser?.name || '—'}`"></span>
                            </p>
                        </div>
                    </header>

                    <div class="wizard-toolbar">
                        <div class="wizard-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search"
                                   x-model.debounce.150ms="userSearch"
                                   placeholder="Rechercher un employé…"
                                   autocomplete="off">
                        </div>
                        <div class="wizard-toolbar-actions">
                            <span class="wizard-count">
                                <strong x-text="filteredUsers.length"></strong> employé(s)
                            </span>
                        </div>
                    </div>

                    <div class="students-grid">
                        <template x-for="user in filteredUsers" :key="user.id">
                            <button type="button"
                                    class="student-card"
                                    :class="{ 'is-selected': String(selectedUserId) === String(user.id), 'is-disabled': user.bloque }"
                                    :disabled="user.bloque"
                                    @click="selectUser(user.id)">
                                <div class="student-avatar" x-text="initials(user.name)"></div>
                                <div class="student-meta">
                                    <span class="student-name" x-text="user.name"></span>
                                    <span class="student-sub" x-text="`${fc(user.salaire)} $ / mois`"></span>
                                </div>
                                <template x-if="user.bloque">
                                    <i class="fa-solid fa-lock student-locked"></i>
                                </template>
                                <template x-if="!user.bloque">
                                    <i class="fa-solid"
                                       :class="String(selectedUserId) === String(user.id)
                                           ? 'fa-circle-check student-check'
                                           : 'fa-circle student-circle'"></i>
                                </template>
                            </button>
                        </template>
                        <p x-show="filteredUsers.length === 0" class="wizard-empty">
                            Aucun employé ne correspond à votre recherche.
                        </p>
                    </div>

                    <footer class="wizard-footer">
                        <a href="{{ route('admin.avances.index') }}" class="btn btn-ghost">
                            <i class="fa-solid fa-xmark"></i> Annuler
                        </a>
                        <button type="button" class="btn btn-primary"
                                :disabled="!canGoStep2" @click="next()">
                            Continuer <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </footer>
                </section>

                {{-- ═══ ÉTAPE 2 : Détails ═══ --}}
                <section x-show="currentStep === 2" x-cloak class="wizard-card">
                    <header class="wizard-card-header">
                        <div class="wizard-card-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <div>
                            <h2 class="wizard-card-title">Détails de l'avance</h2>
                            <p class="wizard-card-subtitle">
                                Bénéficiaire : <strong x-text="selectedUser?.name || '—'"></strong>
                            </p>
                        </div>
                    </header>

                    {{-- Mois + Date --}}
                    <div class="wizard-grid-2">
                        <div class="wizard-field">
                            <label class="wizard-label">Mois scolaire <span class="req">*</span></label>
                            <div class="wizard-select">
                                <select x-model="moisId" required>
                                    <option value="">— Choisir un mois —</option>
                                    <template x-for="m in mois" :key="m.id">
                                        <option :value="String(m.id)"
                                                x-text="m.nom_mois || m.mois"></option>
                                    </template>
                                </select>
                                <i class="fa-solid fa-chevron-down wizard-select-icon"></i>
                            </div>
                        </div>

                        <div class="wizard-field">
                            <label class="wizard-label">Date de l'avance <span class="req">*</span></label>
                            <input type="date"
                                   x-model="dateAvance"
                                   :max="today"
                                   required
                                   class="wizard-input">
                        </div>
                    </div>

                    {{-- ✅ MONTANT USD + FC (bidirectionnel) --}}
                    <div class="amount-pair">
                        <div class="wizard-field amount-field">
                            <label class="wizard-label">
                                Montant (USD) <span class="req">*</span>
                            </label>
                            <div class="amount-input-wrap">
                                <span class="amount-currency">$</span>
                                <input type="number" step="1" min="0"
                                       x-model.number="montant"
                                       @input="onUSDChange()"
                                       :max="limite"
                                       required
                                       class="wizard-input amount-input">
                            </div>
                        </div>

                        {{-- Icône d'échange --}}
                        <div class="amount-sync" aria-hidden="true">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                            <span>auto</span>
                        </div>

                        <div class="wizard-field amount-field">
                            <label class="wizard-label">
                                Montant (FC) <span class="req">*</span>
                            </label>
                            <div class="amount-input-wrap">
                                <span class="amount-currency">FC</span>
                                <input type="number" step="1" min="0"
                                       x-model.number="montantFC"
                                       @input="onFCChange()"
                                       class="wizard-input amount-input">
                            </div>
                        </div>
                    </div>

                    {{-- Taux de change info --}}
                    <div class="rate-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>
                            Taux appliqué :
                            <strong x-text="`1 USD = ${fc(tauxChange)} FC`"></strong>
                            — Vous pouvez saisir dans l'un ou l'autre champ,
                            la conversion est automatique.
                        </span>
                    </div>

                    {{-- Récap montants --}}
                    <div class="wizard-info">
                        <div class="wizard-info-row">
                            <span>Salaire mensuel</span>
                            <strong x-text="`$${fc(salaireMensuel)} (${fc(salaireMensuelFCNum)} FC)`"></strong>
                        </div>
                        <div class="wizard-info-row">
                            <span>Dette actuelle</span>
                            <strong x-text="`$${fc(detteActuelle)}`"></strong>
                        </div>
                        <div class="wizard-info-row">
                            <span>Limite d'emprunt (2 mois)</span>
                            <strong x-text="`$${fc(limite)}`"></strong>
                        </div>
                        <div class="wizard-info-row">
                            <span>Reste disponible</span>
                            <strong class="text-accent" x-text="`$${fc(resteDisponible)}`"></strong>
                        </div>
                        <div class="wizard-info-row" x-show="montant > 0">
                            <span>Montant demandé</span>
                            <strong class="text-accent"
                                    x-text="`$${fc(montant)} ≈ ${fc(montantFC)} FC`"></strong>
                        </div>
                        <div class="wizard-info-row" x-show="montant > 0">
                            <span>Solde après avance</span>
                            <strong x-text="`$${fc(soldeRestant)} (${fc(soldeRestantFCNum)} FC)`"></strong>
                        </div>
                    </div>

                    {{-- Alerte dépassement --}}
                    <template x-if="avertissement">
                        <div class="alert-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <div>
                                <strong>Attention</strong>
                                <p x-text="avertissement"></p>
                            </div>
                        </div>
                    </template>

                    {{-- Motif + Commentaire --}}
                    <div class="wizard-grid-2">
                        <div class="wizard-field">
                            <label class="wizard-label">Motif <span class="optional">(Optionnel)</span></label>
                            <input type="text"
                                   x-model="motif"
                                   maxlength="255"
                                   placeholder="Ex: Frais médicaux, scolarité…"
                                   class="wizard-input">
                        </div>

                        <div class="wizard-field">
                            <label class="wizard-label">Commentaire <span class="optional">(Optionnel)</span></label>
                            <input type="text"
                                   x-model="commentaire"
                                   maxlength="500"
                                   placeholder="Notes complémentaires…"
                                   class="wizard-input">
                        </div>
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
                            <p class="wizard-card-subtitle">Vérifiez les informations avant validation</p>
                        </div>
                    </header>

                    <div class="wizard-summary">
                        <div class="summary-item">
                            <span class="summary-label">Employé</span>
                            <p class="summary-value" x-text="selectedUser?.name || '—'"></p>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Mois scolaire</span>
                            <p class="summary-value" x-text="moisLabel || '—'"></p>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Date</span>
                            <p class="summary-value" x-text="formatDate(dateAvance)"></p>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Montant</span>
                            <p class="summary-value summary-value-accent"
                               x-text="`$${fc(montant)} ≈ ${fc(montantFC)} FC`"></p>
                        </div>
                    </div>

                    <div class="wizard-list" x-show="motif || commentaire">
                        <h4 class="wizard-list-title">
                            <i class="fa-solid fa-circle-info"></i>
                            Détails optionnels
                        </h4>
                        <ul class="wizard-list-items">
                            <template x-if="motif">
                                <li><strong>Motif :</strong> <span x-text="motif"></span></li>
                            </template>
                            <template x-if="commentaire">
                                <li><strong>Commentaire :</strong> <span x-text="commentaire"></span></li>
                            </template>
                        </ul>
                    </div>

                    <footer class="wizard-footer">
                        <button type="button" class="btn btn-ghost" @click="prev()">
                            <i class="fa-solid fa-arrow-left"></i> Retour
                        </button>
                        <button type="submit" class="btn btn-primary"
                                :disabled="!canSubmit || submitting">
                            <span x-show="!submitting">
                                <i class="fa-solid fa-check"></i> Confirmer l'avance
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
                <template x-if="selectedUser">
                    <div class="aside-card">
                        <h3 class="aside-title">
                            <i class="fa-solid fa-user-circle"></i> Détails de l'employé
                        </h3>
                        <dl class="aside-dl">
                            <div class="aside-row">
                                <dt>Nom</dt>
                                <dd x-text="selectedUser.name"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Salaire mensuel</dt>
                                <dd>
                                    <strong x-text="`$${fc(salaireMensuel)}`"></strong>
                                    <small x-text="`${fc(salaireMensuelFCNum)} FC`"></small>
                                </dd>
                            </div>
                            <div class="aside-row">
                                <dt>Dette actuelle</dt>
                                <dd x-text="`$${fc(detteActuelle)}`"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Limite (2 mois)</dt>
                                <dd x-text="`$${fc(limite)}`"></dd>
                            </div>
                            <div class="aside-row">
                                <dt>Disponible</dt>
                                <dd>
                                    <span class="pill pill-indigo" x-text="`$${fc(resteDisponible)}`"></span>
                                </dd>
                            </div>
                            <div class="aside-row aside-row-total">
                                <dt>Statut</dt>
                                <dd>
                                    <template x-if="selectedUser.bloque">
                                        <span class="pill pill-danger">
                                            <i class="fa-solid fa-lock"></i> Bloqué
                                        </span>
                                    </template>
                                    <template x-if="!selectedUser.bloque">
                                        <span class="pill pill-success">
                                            <i class="fa-solid fa-circle-check"></i> Éligible
                                        </span>
                                    </template>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </template>

                <template x-if="!selectedUser">
                    <div class="aside-card aside-card-empty">
                        <i class="fa-solid fa-user-slash"></i>
                        <p>Sélectionnez un employé pour voir ses informations.</p>
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
    .alert-warning { display: flex; gap: 0.75rem; align-items: flex-start; background: #fffbeb; border-left: 4px solid #f59e0b; color: #92400e; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.25rem; font-size: 0.9rem; }
    .alert-warning strong { display: block; margin-bottom: 0.15rem; }
    .alert-warning p { margin: 0; font-size: 0.85rem; }

    .filters-card { background: #fff; border: 1px solid #f1f5f9; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 1.5rem; margin-bottom: 1.5rem; }
    .filters-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; padding-bottom: 1rem; margin-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; }
    .filters-header-left { display: flex; align-items: center; gap: 0.6rem; }
    .filter-icon { color: #6366f1; }
    .filters-title { font-weight: 700; color: #0f172a; font-size: 0.95rem; }

    .cascade-steps { display: flex; align-items: center; gap: 0.5rem; padding: 1rem 1.25rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
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
    .optional { color: #94a3b8; font-weight: 600; text-transform: none; letter-spacing: 0; }

    .wizard-select { position: relative; }
    .wizard-select select { width: 100%; padding: 0.75rem 2.5rem 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.95rem; color: #0f172a; appearance: none; cursor: pointer; outline: none; transition: all 0.2s; }
    .wizard-select select:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .wizard-select-icon { position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 0.85rem; }

    .wizard-input, .wizard-textarea { width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.95rem; color: #0f172a; outline: none; transition: all 0.2s; font-family: inherit; }
    .wizard-input:focus, .wizard-textarea:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .wizard-input-readonly { background: #f1f5f9; cursor: not-allowed; color: #64748b; }
    .wizard-textarea { resize: vertical; min-height: 60px; }

    /* ✅ MONTANT USD ↔ FC — paire bidirectionnelle */
    .amount-pair {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 0.75rem;
        align-items: end;
        margin-bottom: 0.5rem;
    }
    @media (max-width: 640px) {
        .amount-pair {
            grid-template-columns: 1fr;
            align-items: stretch;
        }
        .amount-sync {
            justify-self: center;
            transform: rotate(90deg);
            margin: -0.25rem 0;
        }
    }

    .amount-field { margin-bottom: 0.75rem; }

    .amount-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .amount-currency {
        position: absolute;
        left: 0.9rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: #64748b;
        pointer-events: none;
        z-index: 1;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .amount-input {
        padding-left: 3rem !important;
        font-weight: 700;
        font-size: 1rem !important;
    }
    .amount-input:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
    }

    .amount-sync {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.15rem;
        padding: 0.5rem 0.4rem;
        color: #6366f1;
        font-size: 0.95rem;
        flex-shrink: 0;
    }
    .amount-sync i {
        font-size: 1.1rem;
        color: #6366f1;
        animation: pulseSync 2.5s ease-in-out infinite;
    }
    .amount-sync span {
        font-size: 0.62rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    @keyframes pulseSync {
        0%, 100% { transform: scale(1); opacity: 1; }
        50%      { transform: scale(1.12); opacity: 0.75; }
    }

    .rate-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 0.9rem;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        font-size: 0.8rem;
        color: #1e40af;
        margin-bottom: 1.25rem;
    }
    .rate-info i { color: #3b82f6; flex-shrink: 0; }
    .rate-info strong { color: #1e3a8a; }

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

    .students-grid { display: grid; grid-template-columns: 1fr; gap: 0.5rem; max-height: 400px; overflow-y: auto; }
    @media (min-width: 640px) { .students-grid { grid-template-columns: repeat(2, 1fr); } }
    .student-card { display: flex; align-items: center; gap: 0.65rem; padding: 0.7rem 0.85rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fff; cursor: pointer; text-align: left; transition: all 0.15s; }
    .student-card:hover:not(:disabled) { border-color: #c7d2fe; background: #fafaff; }
    .student-card.is-selected { border-color: #6366f1; background: #eef2ff; box-shadow: 0 2px 8px rgba(99,102,241,0.12); }
    .student-card.is-disabled, .student-card:disabled { opacity: 0.55; cursor: not-allowed; background: #f8fafc; }
    .student-avatar { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.78rem; flex-shrink: 0; }
    .student-meta { display: flex; flex-direction: column; flex: 1; min-width: 0; }
    .student-name { font-weight: 600; color: #0f172a; font-size: 0.88rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .student-sub { font-size: 0.72rem; color: #64748b; margin-top: 0.1rem; }
    .student-check { color: #4f46e5; font-size: 1.1rem; flex-shrink: 0; }
    .student-circle { color: #cbd5e1; font-size: 1.1rem; flex-shrink: 0; }
    .student-locked { color: #dc2626; font-size: 1rem; flex-shrink: 0; }
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
    .wizard-list-items { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.4rem; }
    .wizard-list-items li { background: #fff; padding: 0.5rem 0.85rem; border-radius: 8px; font-size: 0.82rem; color: #334155; border: 1px solid #e2e8f0; }
    .wizard-list-items strong { color: #0f172a; }

    .wizard-footer { display: flex; justify-content: space-between; gap: 0.6rem; padding-top: 1.25rem; margin-top: 1.25rem; border-top: 1px solid #f1f5f9; }
    @media (max-width: 640px) { .wizard-footer { flex-direction: column-reverse; } .wizard-footer .btn { width: 100%; justify-content: center; } }

    .aside-card { background: #fff; border-radius: 14px; border: 1px solid #f1f5f9; padding: 1.25rem; }
    .aside-card-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.6rem; padding: 2rem 1rem; text-align: center; color: #94a3b8; }
    .aside-card-empty i { font-size: 2rem; color: #cbd5e1; }
    .aside-card-empty p { margin: 0; font-size: 0.85rem; }
    .aside-title { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; font-weight: 700; color: #0f172a; margin: 0 0 1rem; }
    .aside-title i { color: #6366f1; }
    .aside-dl { margin: 0; }
    .aside-row { display: flex; justify-content: space-between; gap: 0.5rem; padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
    .aside-row:last-child { border-bottom: none; }
    .aside-row dt { color: #64748b; margin: 0; }
    .aside-row dd { color: #0f172a; font-weight: 600; margin: 0; text-align: right; }
    .aside-row-total dd strong { color: #4f46e5; font-size: 1rem; display: block; }
    .aside-row-total dd small { display: block; color: #94a3b8; font-weight: 500; font-size: 0.72rem; }

    .pill { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; }
    .pill-indigo { background: #eef2ff; color: #4338ca; }
    .pill-success { background: #ecfdf5; color: #047857; }
    .pill-danger { background: #fef2f2; color: #b91c1c; }

    [x-cloak] { display: none !important; }
</style>

<script>
function avancePage(config) {
    return {
        /* ---------------- DONNÉES ---------------- */
        usersData:  config.usersData  || [],
        mois:       config.mois       || [],
        tauxChange: Number(config.tauxChange || 2800),

        /* ---------------- ÉTAT ---------------- */
        selectedUserId: '',
        moisId:         '',
        dateAvance:     '',
        montant:        0,        // USD
        montantFC:      0,        // FC
        motif:          '',
        commentaire:    '',
        userSearch:     '',
        currentStep:    1,
        submitting:     false,

        /* ✅ Flag interne pour éviter les boucles de synchronisation */
        _syncing: false,

        stepLabels: ['Employé', 'Détails', 'Confirmation'],

        /* ---------------- INIT ---------------- */
        init() {
            const ini = config.initial || {};

            if (ini.userId) this.selectedUserId = String(ini.userId);
            if (ini.moisId) this.moisId = String(ini.moisId);
            this.dateAvance = ini.dateAvance || new Date().toISOString().split('T')[0];

            // Init USD
            if (ini.montant) {
                this.montant = parseFloat(ini.montant) || 0;
            }

            // Init FC (priorité : valeur FC fournie, sinon calcul depuis USD)
            if (ini.montantFC) {
                this.montantFC = parseFloat(ini.montantFC) || 0;
            } else if (this.montant > 0) {
                this.montantFC = Math.round(this.montant * this.tauxChange);
            }

            if (ini.motif) this.motif = String(ini.motif);
            if (ini.commentaire) this.commentaire = String(ini.commentaire);
        },

        /* ---------------- SYNCHRONISATION USD ↔ FC ---------------- */
        onUSDChange() {
            if (this._syncing) return;
            this._syncing = true;

            const usd = Number(this.montant) || 0;
            this.montant = Math.max(0, Math.floor(usd));
            this.montantFC = Math.round(this.montant * this.tauxChange);

            this.$nextTick(() => { this._syncing = false; });
        },

        onFCChange() {
            if (this._syncing) return;
            this._syncing = true;

            const fc = Number(this.montantFC) || 0;
            this.montantFC = Math.max(0, Math.floor(fc));
            this.montant = Math.round(this.montantFC / this.tauxChange);

            this.$nextTick(() => { this._syncing = false; });
        },

        /* ---------------- GETTERS ---------------- */
        get today() {
            return new Date().toISOString().split('T')[0];
        },

        get selectedUser() {
            return this.usersData.find(u => String(u.id) === String(this.selectedUserId)) || null;
        },

        get filteredUsers() {
            const q = (this.userSearch || '').trim().toLowerCase();
            if (!q) return this.usersData;
            return this.usersData.filter(u => (u.name || '').toLowerCase().includes(q));
        },

        get salaireMensuel() {
            return this.selectedUser ? Number(this.selectedUser.salaire || 0) : 0;
        },

        get salaireMensuelFCNum() {
            return Math.round(this.salaireMensuel * this.tauxChange);
        },

        get detteActuelle() {
            return this.selectedUser ? Number(this.selectedUser.dette || 0) : 0;
        },

        get limite() {
            return this.selectedUser ? Number(this.selectedUser.limite || 0) : 0;
        },

        get resteDisponible() {
            return Math.max(0, this.limite - this.detteActuelle);
        },

        get soldeRestant() {
            return Math.max(0, this.salaireMensuel - (this.montant || 0));
        },

        get soldeRestantFCNum() {
            return Math.round(this.soldeRestant * this.tauxChange);
        },

        get moisLabel() {
            const m = this.mois.find(x => String(x.id) === String(this.moisId));
            return m ? (m.nom_mois || m.mois) : '';
        },

        get avertissement() {
            if (!this.selectedUserId || !this.montant) return '';

            const total = this.detteActuelle + this.montant;

            if (total > this.limite) {
                return `Dette totale après cette avance (${this.fc(total)} $) dépassera la limite de ${this.fc(this.limite)} $. Réduisez le montant.`;
            }
            if (this.montant > this.salaireMensuel) {
                return `L'avance dépasse le salaire mensuel. Un montant de ${this.fc(this.montant - this.salaireMensuel)} $ sera reporté sur les mois suivants.`;
            }
            return '';
        },

        /* ---------------- VALIDATION ---------------- */
        get canGoStep2() {
            const u = this.selectedUser;
            return !!u && !u.bloque;
        },

        get canGoStep3() {
            if (!this.selectedUserId) return false;
            if (!this.moisId) return false;
            if (!this.dateAvance) return false;
            if (!this.montant || this.montant <= 0) return false;
            if (this.detteActuelle + this.montant > this.limite) return false;
            return true;
        },

        get canSubmit() {
            return this.canGoStep3 && !this.submitting;
        },

        /* ---------------- NAVIGATION ---------------- */
        next() { if (this.currentStep < 3) this.currentStep++; },
        prev() { if (this.currentStep > 1) this.currentStep--; this.submitting = false; },
        goToStep(n) { if (n <= this.currentStep + 1) this.currentStep = n; },

        /* ---------------- SÉLECTION ---------------- */
        selectUser(id) {
            const u = this.usersData.find(x => String(x.id) === String(id));
            if (!u || u.bloque) return;

            this.selectedUserId = String(id);
            this.montant = 0;
            this.montantFC = 0;
        },

        reset() {
            this.selectedUserId = '';
            this.moisId = '';
            this.montant = 0;
            this.montantFC = 0;
            this.motif = '';
            this.commentaire = '';
            this.userSearch = '';
            this.currentStep = 1;
        },

        /* ---------------- UTILITAIRES ---------------- */
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

        formatDate(d) {
            if (!d) return '—';
            const date = new Date(d);
            if (isNaN(date.getTime())) return d;
            return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(date);
        },

        onSubmit(e) {
            if (!this.canSubmit) { e.preventDefault(); return; }
            this.submitting = true;
        },
    };
}
</script>
@endsection