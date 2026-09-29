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
@if(session('alerte_age'))
    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg flex items-start gap-2">
        <i class="fa-regular fa-triangle-exclamation mt-0.5"></i>
        <span>{{ session('alerte_age') }}</span>
    </div>
@endif

<div class="inscription-page" x-data="inscriptionForm({{ $inscription->id }})" x-init="init()">

    {{-- En-tête modifié : le lien retour est sous le sous-titre --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Modifier <strong>l'inscription</strong></h1>
            <p class="form-subtitle">Mettez à jour les informations de l'inscription</p>
        </div>
        <a href="{{ route('admin.inscriptions.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>

    {{-- Barre de progression --}}
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
            <span :class="currentStep >= 1 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Élève</span>
            <span :class="currentStep >= 2 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Classe</span>
            <span :class="currentStep >= 3 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Validation</span>
        </div>
    </div>

    <form action="{{ route('admin.inscriptions.update', $inscription) }}" method="POST" id="inscriptionForm">
        @csrf
        @method('PUT')
        
        {{-- Input caché pour l'élève sélectionné --}}
        <input type="hidden" name="eleve_id" x-model="selectedEleveIds[0]">

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            {{-- Panneau principal --}}
            <div class="xl:col-span-2 space-y-6">

                {{-- Étape 1 : Sélection de l'élève --}}
                <div x-show="currentStep === 1" class="form-card">
                    <h2 class="card-title"><i class="fa-solid fa-user-graduate"></i> Sélectionnez l'élève</h2>
                    {{-- Espacement ajouté entre la recherche et la grille --}}
                    <div class="search-wrap mb-6">
                        <i class="fa-solid fa-search search-icon"></i>
                        <input type="text" x-model="eleveSearch" placeholder="Rechercher un élève..." class="search-input">
                    </div>
                    <div class="students-grid">
                        <template x-for="eleve in filteredEleves" :key="eleve.id">
                            <div @click="toggleEleve(eleve.id)"
                                 :class="{
                                     'student-card selected': selectedEleveIds.includes(eleve.id),
                                     'student-card': !selectedEleveIds.includes(eleve.id)
                                 }">
                                <div class="student-avatar"
                                     x-text="eleve.nom_complet.split(' ').map(n => n[0]).join('')"></div>
                                <div class="student-info">
                                    <p class="student-name" x-text="eleve.nom_complet"></p>
                                    <p class="student-details">
                                        <span x-text="eleve.age"></span> ans • <span x-text="eleve.date_naissance"></span>
                                    </p>
                                </div>
                                <div class="ml-auto">
                                    <i class="fa-regular fa-circle" :class="selectedEleveIds.includes(eleve.id) ? 'fa-circle-check text-indigo-600' : 'fa-circle text-gray-300'"></i>
                                </div>
                            </div>
                        </template>
                        <div x-show="filteredEleves.length === 0 && eleveSearch !== ''" class="text-center py-4 text-gray-500">
                            Aucun élève trouvé.
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button type="button" @click="validateStep1()" :disabled="selectedEleveIds.length === 0"
                                :class="selectedEleveIds.length > 0 ? 'btn-submit' : 'btn-disabled'">
                            Continuer <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Étape 2 : Configuration --}}
                <div x-show="currentStep === 2" class="form-card">
                    <h2 class="card-title"><i class="fa-solid fa-school"></i> Classe et année</h2>
                    <div class="form-row">
                        <div class="form-field">
                            <label class="field-label">Année scolaire <span class="text-red-500">*</span></label>
                            <div class="select-wrap">
                                <select name="annee_scolaire_id" x-model="anneeScolaireId" @change="loadEleves(anneeScolaireId)"
                                        class="@error('annee_scolaire_id') is-invalid @enderror" required>
                                    <option value="">Sélectionner</option>
                                    @foreach($annees as $an)
                                        <option value="{{ $an->id }}" {{ old('annee_scolaire_id', $inscription->annee_scolaire_id) == $an->id ? 'selected' : '' }}>
                                            {{ $an->libelle }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="fa-solid fa-chevron-down select-icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('annee_scolaire_id')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label class="field-label">Session <span class="text-red-500">*</span></label>
                            <div class="select-wrap">
                                <select x-model="selectedSessionId" @change="onSessionChange()"
                                        class="@error('session_id') is-invalid @enderror" required>
                                    <option value="">Choisir une session</option>
                                    @foreach($sessions as $session)
                                        <option value="{{ $session->id }}" {{ old('session_id', $inscription->salleDeClasse?->section?->session_id) == $session->id ? 'selected' : '' }}>
                                            {{ $session->nom }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="fa-solid fa-chevron-down select-icon"></i>
                                <div class="line-focus"></div>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="field-label">Section <span class="text-red-500">*</span></label>
                            <div class="select-wrap">
                                <select x-model="selectedSectionId" @change="onSectionChange()"
                                        class="@error('section_id') is-invalid @enderror" required>
                                    <option value="">Choisir une section</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}" {{ old('section_id', $inscription->salleDeClasse?->section_id) == $section->id ? 'selected' : '' }}>
                                            {{ $section->nom }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="fa-solid fa-chevron-down select-icon"></i>
                                <div class="line-focus"></div>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="field-label">Salle de classe <span class="text-red-500">*</span></label>
                            <div class="select-wrap">
                                <select name="salle_classe_id" x-model="selectedSalleId" @change="selectSalle($event.target.value)"
                                        :disabled="!filteredSalles.length" class="@error('salle_classe_id') is-invalid @enderror" required>
                                    <option value="">Sélectionner une salle</option>
                                    <template x-for="salle in filteredSalles" :key="salle.id">
                                        <option :value="salle.id" x-text="salle.nom + (salle.option ? ' (' + salle.option + ')' : '')"></option>
                                    </template>
                                </select>
                                <i class="fa-solid fa-chevron-down select-icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('salle_classe_id')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="input-wrap">
                            <input type="date" name="date_inscription" x-model="dateInscription"
                                   value="{{ old('date_inscription', $inscription->date_inscription->format('Y-m-d')) }}"
                                   placeholder=" " required class="@error('date_inscription') is-invalid @enderror">
                            <label class="float-label">Date d'inscription <span class="text-red-500">*</span></label>
                            <i class="fa-solid fa-calendar icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('date_inscription')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end mt-4">
                        <button type="button" @click="validateStep2()" class="btn-submit">
                            Continuer <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Étape 3 : Validation --}}
                <div x-show="currentStep === 3" class="form-card">
                    <h2 class="card-title"><i class="fa-solid fa-check-circle"></i> Confirmation</h2>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="summary-card">
                            <span>Élève</span>
                            <p x-text="getEleveNom(selectedEleveIds[0])"></p>
                        </div>
                        <div class="summary-card">
                            <span>Classe</span>
                            <p x-text="selectedSalle?.nom"></p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="input-wrap">
                            <input type="number" step="0.01" name="reduction_frais" x-model="reduction"
                                   value="{{ old('reduction_frais', $inscription->reduction_frais ?? 0) }}" min="0" placeholder=" ">
                            <label class="float-label">Réduction ($)</label>
                            <i class="fa-solid fa-percent icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        <label class="checkbox-label">
                            <input type="checkbox" name="redoublement" value="1" x-model="redoublement"
                                   {{ old('redoublement', false) ? 'checked' : '' }} class="form-check-input">
                            <span>Redoublement (autoriser les hors tranche)</span>
                        </label>
                    </div>

                    <div class="button-group mt-6">
                        <button type="button" @click="currentStep = 2" class="btn-cancel">
                            <i class="fa-solid fa-arrow-left"></i> Retour
                        </button>
                        <button type="submit" name="action" value="save"
                                x-show="!horsTranche || redoublement"
                                class="btn-submit green">
                            <i class="fa-solid fa-check"></i> Mettre à jour
                        </button>
                        <button type="submit" name="action" value="force"
                                x-show="horsTranche && !redoublement"
                                class="btn-submit amber">
                            <i class="fa-solid fa-triangle-exclamation"></i> Forcer
                        </button>
                    </div>
                </div>
            </div>

            {{-- Panneau latéral --}}
            <div class="space-y-6">
                <div x-show="selectedEleveIds.length > 0" class="selected-eleve-card">
                    <div class="selected-eleve-avatar">
                        <i class="fa-solid fa-user text-2xl"></i>
                    </div>
                    <div>
                        <p class="selected-eleve-name" x-text="getEleveNom(selectedEleveIds[0])"></p>
                        <p class="selected-eleve-age">Cliquez sur "Continuer" pour finaliser</p>
                    </div>
                </div>

                <div x-show="selectedSalle" class="financial-card">
                    <h3 class="financial-title">Détails financiers</h3>
                    <div class="financial-row">
                        <span>Frais inscription (par élève)</span>
                        <strong x-text="Number(fraisInscription).toFixed(0) + ' $'"></strong>
                    </div>
                    <div class="financial-row muted">
                        <span>Équivalent FC</span>
                        <span x-text="Math.round(Number(fraisInscriptionCDF)).toLocaleString('fr-FR') + ' FC'"></span>
                    </div>
                    <div class="financial-row">
                        <span>Frais annuels (par élève)</span>
                        <strong x-text="Number(fraisAnnuel).toFixed(0) + ' $'"></strong>
                    </div>
                    <div class="financial-row muted">
                        <span>Équivalent FC</span>
                        <span x-text="Math.round(Number(fraisAnnuelCDF)).toLocaleString('fr-FR') + ' FC'"></span>
                    </div>
                    <div x-show="reduction > 0" class="financial-row danger">
                        <span>Réduction</span>
                        <span x-text="'-' + Number(reduction).toFixed(0) + ' $'"></span>
                    </div>
                    <div x-show="reduction > 0" class="financial-row muted">
                        <span>Équivalent FC</span>
                        <span x-text="'-' + Math.round(Number(reduction) * taux).toLocaleString('fr-FR') + ' FC'"></span>
                    </div>
                    <hr class="financial-divider">
                    <div class="financial-total">
                        <span>Total</span>
                        <span x-text="Number(totalFrais).toFixed(0) + ' $'"></span>
                    </div>
                    {{-- Ajout de l'équivalent FC pour le total --}}
                    <div class="financial-row muted">
                        <span>Équivalent FC</span>
                        <span x-text="Math.round(Number(totalFrais) * taux).toLocaleString('fr-FR') + ' FC'"></span>
                    </div>
                </div>

                <div x-show="horsTranche && !redoublement" class="warning-card">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <p class="font-bold">Hors tranche d'âge</p>
                    <p class="text-sm">Âge <span x-text="age"></span> ans (requis <span x-text="selectedSalle?.age_min"></span>-<span x-text="selectedSalle?.age_max"></span> ans)</p>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    /* ==== Styles premium (identique à create, avec ajustements) ==== */
    .inscription-page { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    /* En-tête en colonne : le lien retour apparaît sous le sous-titre */
    .header-container { display: flex; flex-direction: column; align-items: flex-start; gap: 0.5rem; margin-bottom: 2rem; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: #667eea; font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: color 0.2s; }
    .back-link:hover { color: #4f46e5; }
    .form-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.2rem; letter-spacing: -0.5px; }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: #94a3b8; font-size: 0.9rem; }

    .progress-container { margin-bottom: 2rem; }
    .progress-steps { display: flex; align-items: center; gap: 0.5rem; }
    .progress-step { display: flex; align-items: center; flex: 1; }
    .step-circle { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; transition: all 0.3s; border: none; outline: none; }
    .step-line { flex: 1; height: 3px; border-radius: 2px; margin: 0 8px; transition: background-color 0.3s; }
    .progress-labels { display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.8rem; }

    .form-card { background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
    .card-title { font-size: 1.2rem; font-weight: 600; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px; }
    .card-title i { color: #667eea; }

    .search-wrap { position: relative; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; pointer-events: none; }
    .search-input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 2px solid #e2e8f0; border-radius: 12px; background: #f8fafc; font-size: 0.95rem; transition: border-color 0.3s; outline: none; }
    .search-input:focus { border-color: #667eea; background: white; }

    .students-grid { display: grid; grid-template-columns: 1fr; gap: 0.75rem; max-height: 300px; overflow-y: auto; padding-right: 4px; }
    @media (min-width: 768px) { .students-grid { grid-template-columns: repeat(2, 1fr); } }
    .student-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s; background: white; }
    .student-card:hover { border-color: #c7d2fe; }
    .student-card.selected { border-color: #667eea; background: #f5f7ff; box-shadow: 0 4px 12px rgba(102,126,234,0.15); }
    .student-avatar { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; flex-shrink: 0; }
    .student-name { font-weight: 600; color: #1e293b; }
    .student-details { font-size: 0.75rem; color: #64748b; }

    .form-row { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
    @media (min-width: 768px) { .form-row { grid-template-columns: 1fr 1fr; } }
    .form-field { position: relative; }
    .field-label { display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem; }

    .input-wrap, .select-wrap { position: relative; }
    .input-wrap input, .select-wrap select { width: 100%; padding: 0.8rem 2.5rem 0.8rem 0; border: none; border-bottom: 2px solid #e2e8f0; background: transparent; font-size: 1rem; color: #1e293b; font-weight: 500; transition: border-color 0.3s; outline: none; -webkit-appearance: none; -moz-appearance: none; appearance: none; }
    .input-wrap input::placeholder { color: transparent; }
    .input-wrap input:focus, .select-wrap select:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid, .select-wrap select.is-invalid { border-bottom-color: #ef4444; }
    .input-wrap .float-label { position: absolute; left: 0; top: 0.8rem; color: #94a3b8; font-size: 1rem; pointer-events: none; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label { top: -0.6rem; font-size: 0.72rem; font-weight: 700; color: #667eea; letter-spacing: 0.5px; text-transform: uppercase; }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label { color: #ef4444; }
    .input-wrap i.icon, .select-wrap .select-icon { position: absolute; right: 0; top: 50%; transform: translateY(-50%); color: #cbd5e1; font-size: 1.1rem; transition: all 0.3s; pointer-events: none; }
    .input-wrap input:focus ~ i.icon, .select-wrap select:focus ~ .select-icon { color: #667eea; transform: translateY(-50%) scale(1.1); }
    .input-wrap .line-focus, .select-wrap .line-focus { position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: linear-gradient(90deg, #667eea, #764ba2); transition: all 0.4s cubic-bezier(0.4,0,0.2,1); transform: translateX(-50%); pointer-events: none; }
    .input-wrap input:focus ~ .line-focus, .select-wrap select:focus ~ .line-focus { width: 100%; }

    .summary-card { background: #f8fafc; border-radius: 12px; padding: 1rem; border: 1px solid #f1f5f9; }
    .summary-card span { font-size: 0.75rem; color: #94a3b8; }
    .summary-card p { font-weight: 700; color: #1e293b; margin-top: 0.25rem; }
    .checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #475569; cursor: pointer; }
    .form-check-input { -webkit-appearance: none; appearance: none; width: 18px; height: 18px; border: 1.5px solid #cbd5e1; border-radius: 4px; cursor: pointer; position: relative; transition: all 0.2s; }
    .form-check-input:checked { background-color: #667eea; border-color: #667eea; }
    .form-check-input:checked::after { content: '✓'; position: absolute; color: white; font-size: 11px; top: 50%; left: 50%; transform: translate(-50%, -50%); }

    .button-group { display: flex; justify-content: flex-end; gap: 0.75rem; flex-wrap: wrap; }
    .btn-submit, .btn-cancel, .btn-disabled { padding: 0.8rem 1.75rem; border-radius: 12px; font-weight: 600; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); outline: none; border: none; text-decoration: none; }
    .btn-submit { background: #1e293b; color: white; }
    .btn-submit:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-submit:active { transform: translateY(0) scale(0.98); box-shadow: none; }
    .btn-submit.green { background: #16a34a; }
    .btn-submit.green:hover { background: #15803d; box-shadow: 0 8px 16px rgba(22,163,74,0.3); }
    .btn-submit.amber { background: #f59e0b; }
    .btn-submit.amber:hover { background: #d97706; box-shadow: 0 8px 16px rgba(245,158,11,0.3); }
    .btn-cancel { background: white; border: 1.5px solid #e2e8f0; color: #64748b; }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }
    .btn-disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }

    .selected-eleve-card { background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 20px; padding: 1.5rem; color: white; display: flex; align-items: center; gap: 1rem; box-shadow: 0 20px 40px rgba(102,126,234,0.3); }
    .selected-eleve-avatar { width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .selected-eleve-name { font-weight: 700; font-size: 1.1rem; }
    .selected-eleve-age { color: #e0e7ff; font-size: 0.9rem; }

    .financial-card { background: white; border-radius: 20px; padding: 1.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
    .financial-title { font-size: 1rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem; }
    .financial-row { display: flex; justify-content: space-between; font-size: 0.9rem; padding: 0.5rem 0; }
    .financial-row.muted { color: #64748b; font-size: 0.8rem; }
    .financial-row.danger { color: #ef4444; font-weight: 600; }
    .financial-divider { border-color: #f1f5f9; margin: 0.75rem 0; }
    .financial-total { display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem; color: #1e293b; }

    .warning-card { background: #fff7ed; border-left: 4px solid #f59e0b; border-radius: 12px; padding: 1.25rem; color: #92400e; }
    .warning-card i { font-size: 1.5rem; }
    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.3rem; }

    @media (max-width: 768px) {
        .progress-labels { font-size: 0.7rem; }
        .form-card { padding: 1.5rem; }
        .button-group { flex-direction: column-reverse; }
        .btn-submit, .btn-cancel { width: 100%; }
        .form-row { grid-template-columns: 1fr; }
        .students-grid { grid-template-columns: 1fr; }
    }
</style>

<script>
    function inscriptionForm(inscriptionId) {
        return {
            inscriptionId: inscriptionId,
            eleves: [],
            salles: @json($salles),
            sessions: @json($sessions),
            sections: @json($sections),
            taux: {{ $taux }},
            selectedEleveIds: [],
            selectedSalle: null,
            selectedSalleId: '{{ old('salle_classe_id', $inscription->salle_classe_id) }}',
            anneeScolaireId: '{{ old('annee_scolaire_id', $inscription->annee_scolaire_id) }}',
            dateInscription: '{{ old('date_inscription', $inscription->date_inscription->format('Y-m-d')) }}',
            reduction: {{ old('reduction_frais', $inscription->reduction_frais ?? 0) }},
            redoublement: @json(old('redoublement', false)),
            currentStep: 1,
            eleveSearch: '',
            selectedSessionId: '{{ old('session_id', $inscription->salleDeClasse?->section?->session_id) }}',
            selectedSectionId: '{{ old('section_id', $inscription->salleDeClasse?->section_id) }}',
            steps: [{}, {}, {}],
            loading: false,

            get filteredSalles() {
                let result = this.salles;
                if (this.selectedSessionId) {
                    result = result.filter(s => String(s.session_id) === String(this.selectedSessionId));
                }
                if (this.selectedSectionId) {
                    result = result.filter(s => String(s.section_id) === String(this.selectedSectionId));
                }
                return result;
            },

            get age() {
                const eleve = this.eleves.find(e => e.id == this.selectedEleveIds[0]);
                return eleve ? eleve.age : null;
            },
            get horsTranche() {
                if (!this.selectedSalle || this.selectedEleveIds.length === 0) return false;
                const eleve = this.eleves.find(e => e.id == this.selectedEleveIds[0]);
                if (!eleve || !eleve.age) return false;
                return eleve.age < this.selectedSalle.age_min || eleve.age > this.selectedSalle.age_max;
            },
            get fraisInscription() { return this.selectedSalle ? Number(this.selectedSalle.frais_inscription) : 0; },
            get fraisAnnuel() { return this.selectedSalle ? Number(this.selectedSalle.frais_annuel) : 0; },
            get fraisInscriptionCDF() { return this.fraisInscription * this.taux; },
            get fraisAnnuelCDF() { return this.fraisAnnuel * this.taux; },
            get totalFrais() { return this.fraisInscription + this.fraisAnnuel - this.reduction; },

            get filteredEleves() {
                if (!this.eleveSearch) return this.eleves;
                return this.eleves.filter(e => e.nom_complet.toLowerCase().includes(this.eleveSearch.toLowerCase()));
            },

            init() {
                this.loadEleves(this.anneeScolaireId);
                this.selectedEleveIds = [{{ $inscription->eleve_id }}];
                if (this.selectedSalleId) {
                    this.selectSalle(this.selectedSalleId);
                }
                @if($errors->any())
                    const errorFields = @json(array_keys($errors->toArray()));
                    if (errorFields.some(f => ['eleve_id', 'eleve_ids'].includes(f))) {
                        this.currentStep = 1;
                    } else if (errorFields.some(f => ['annee_scolaire_id', 'salle_classe_id', 'date_inscription'].includes(f))) {
                        this.currentStep = 2;
                    } else if (errorFields.some(f => ['reduction_frais', 'redoublement'].includes(f))) {
                        this.currentStep = 3;
                    }
                @endif
            },

            loadEleves(anneeId = null) {
                const url = anneeId
                    ? `{{ route('admin.inscriptions.eleves-disponibles') }}?annee_id=${anneeId}`
                    : `{{ route('admin.inscriptions.eleves-disponibles') }}`;
                this.loading = true;
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        this.eleves = data;
                        const currentEleveId = {{ $inscription->eleve_id }};
                        const exists = data.some(e => e.id == currentEleveId);
                        if (!exists) {
                            const eleveActuel = @json($inscription->eleve);
                            if (eleveActuel) {
                                this.eleves.push({
                                    id: eleveActuel.id,
                                    nom_complet: eleveActuel.nom + ' ' + eleveActuel.prenom,
                                    date_naissance: eleveActuel.date_naissance || 'Inconnue',
                                    age: eleveActuel.age || null
                                });
                            }
                        }
                    })
                    .catch(error => console.error('Erreur:', error))
                    .finally(() => this.loading = false);
            },

            toggleEleve(id) {
                this.selectedEleveIds = [id];
            },

            getEleveNom(id) {
                const eleve = this.eleves.find(e => e.id == id);
                return eleve ? eleve.nom_complet : 'Inconnu';
            },

            selectSalle(id) {
                const salle = this.salles.find(s => s.id == id);
                this.selectedSalle = salle || null;
                this.selectedSalleId = salle ? salle.id : null;
            },

            onSessionChange() {
                this.selectedSalleId = null;
                this.selectedSalle = null;
            },

            onSectionChange() {
                this.selectedSalleId = null;
                this.selectedSalle = null;
            },

            validateStep1() {
                if (this.selectedEleveIds.length === 0) {
                    alert('Veuillez sélectionner un élève.');
                    return;
                }
                this.nextStep();
            },

            validateStep2() {
                if (!this.anneeScolaireId || !this.selectedSessionId || !this.selectedSectionId || !this.selectedSalleId || !this.dateInscription) {
                    alert('Veuillez remplir tous les champs obligatoires.');
                    return;
                }
                this.nextStep();
            },

            nextStep() { if (this.currentStep < 3) this.currentStep++; },
            goToStep(step) { if (step <= this.currentStep + 1) this.currentStep = step; }
        }
    }
</script>
@endsection