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

<div class="paiement-page" x-data="fraisSupplementaireForm()" x-init="init()">

    {{-- En-tête --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Modifier <strong>frais supplémentaire</strong></h1>
            <p class="form-subtitle">
                <i class="fa-solid fa-tag text-indigo-500 mr-1"></i>
                {{ $fraisSupplementaire->libelle }}
            </p>
        </div>
        <a href="{{ route('admin.frais-supplementaires.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour aux frais
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
            <span :class="currentStep >= 1 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Informations</span>
            <span :class="currentStep >= 2 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Salles</span>
            <span :class="currentStep >= 3 ? 'text-indigo-600 font-semibold' : 'text-gray-400'">Validation</span>
        </div>
    </div>

    {{-- Formulaire unifié --}}
    <form action="{{ route('admin.frais-supplementaires.update', $fraisSupplementaire) }}"
          method="POST"
          id="fraisForm">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            {{-- Panneau principal --}}
            <div class="xl:col-span-2 space-y-6">

                {{-- ════════════════════════════════════════════ --}}
                {{-- Étape 1 : Informations du frais --}}
                {{-- ════════════════════════════════════════════ --}}
                <div x-show="currentStep === 1" class="form-card">
                    <h2 class="card-title"><i class="fa-solid fa-circle-info"></i> Informations du frais</h2>

                    <div class="form-row">
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="text" name="libelle" x-model="libelle" placeholder=" " required
                                       class="@error('libelle') is-invalid @enderror">
                                <label class="float-label">Libellé <span class="text-red-500">*</span></label>
                                <i class="fa-solid fa-tag icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('libelle')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="number" step="0.01" min="0" name="montant" x-model="montant"
                                       @input="syncUSDToFC" placeholder=" " required
                                       class="@error('montant') is-invalid @enderror">
                                <label class="float-label">Montant (USD) <span class="text-red-500">*</span></label>
                                <i class="fa-solid fa-dollar-sign icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('montant')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="number" step="0.01" min="0" x-model="montantFC"
                                       @input="syncFCToUSD" placeholder=" " class="bg-gray-50">
                                <label class="float-label">Montant (FC)</label>
                                <i class="fa-solid fa-franc-sign icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            <small class="text-muted">
                                Taux de change : <span x-text="tauxChange.toFixed(2)"></span> FC/USD
                            </small>
                        </div>

                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="date" name="date_debut" x-model="dateDebut" required
                                       class="@error('date_debut') is-invalid @enderror">
                                <label class="float-label">Date de début <span class="text-red-500">*</span></label>
                                <i class="fa-regular fa-calendar icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('date_debut')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="date" name="date_fin" x-model="dateFin" required
                                       class="@error('date_fin') is-invalid @enderror">
                                <label class="float-label">Date de fin <span class="text-red-500">*</span></label>
                                <i class="fa-regular fa-calendar icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('date_fin')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field col-span-2">
                            <div class="textarea-wrap">
                                <textarea name="description" rows="3" x-model="description" placeholder=" "
                                          class="@error('description') is-invalid @enderror"></textarea>
                                <label class="float-label">Description (optionnelle)</label>
                                <i class="fa-regular fa-comment-dots icon"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('description')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        {{-- Toggle est_ouvert --}}
                        <div class="form-field col-span-2">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="est_ouvert" value="1"
                                       x-model="estOuvert"
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700">
                                    Frais ouvert aux paiements
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <button type="button" @click="validateStep1()" class="btn-submit">
                            Continuer <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ════════════════════════════════════════════ --}}
                {{-- Étape 2 : Salles concernées --}}
                {{-- ════════════════════════════════════════════ --}}
                <div x-show="currentStep === 2" class="form-card">
                    <h2 class="card-title"><i class="fa-solid fa-school"></i> Salles concernées</h2>

                    {{-- Option toutes les salles --}}
                    <div class="mb-4">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="est_pour_toutes_salles" value="1"
                                   x-model="estPourToutesSalles"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Appliquer à toutes les salles</span>
                        </label>
                    </div>

                    {{-- Sélection des salles spécifiques --}}
                    <div x-show="!estPourToutesSalles">

                        {{-- FILTRE SECTION --}}
                        <div class="mb-4">
                            <label for="section_id" class="block text-sm font-semibold text-slate-700 mb-2">
                                <i class="fa-solid fa-layer-group text-indigo-600 mr-1"></i>
                                Filtrer par section
                            </label>
                            <select id="section_id" x-model="sectionId"
                                    class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl bg-slate-50 text-sm font-medium text-slate-800 focus:border-indigo-500 focus:bg-white outline-none transition">
                                <option value="">— Toutes les sections —</option>
                                @foreach($sections as $s)
                                    <option value="{{ $s->id }}">{{ $s->nom }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Barre de recherche + actions --}}
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                            <div class="search-wrap w-full md:flex-1">
                                <i class="fa-solid fa-search search-icon"></i>
                                <input type="text" x-model="salleSearch"
                                       placeholder="Rechercher une salle..."
                                       class="search-input">
                            </div>
                            <div class="flex items-center gap-3 md:justify-end">
                                <span class="text-sm text-gray-500"
                                      x-text="selectedSalles.length + ' sélectionnée(s)'"></span>
                                <button type="button" @click="selectAllSalles()"
                                        class="text-sm text-indigo-600 hover:text-indigo-800 whitespace-nowrap font-semibold">
                                    Tout <span x-text="'(' + filteredSalles.length + ')'"></span>
                                </button>
                                <button type="button" @click="deselectAllSalles()"
                                        class="text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap font-semibold">
                                    Aucun
                                </button>
                            </div>
                        </div>

                        {{-- Grille des salles --}}
                        <div class="salles-grid">
                            <template x-for="salle in filteredSalles" :key="salle.id">
                                <div @click="toggleSalle(salle.id)"
                                     :class="{
                                         'salle-card selected': selectedSalles.includes(salle.id),
                                         'salle-card': !selectedSalles.includes(salle.id)
                                     }">
                                    <div class="salle-info">
                                        <i class="fa-solid fa-school"></i>
                                        <span x-text="salle.nom"></span>
                                    </div>
                                    <i class="fa-regular"
                                       :class="selectedSalles.includes(salle.id) ? 'fa-circle-check text-indigo-600' : 'fa-circle text-gray-300'"></i>
                                </div>
                            </template>

                            <div x-show="filteredSalles.length === 0"
                                 class="text-center py-8 text-gray-500 col-span-full">
                                <i class="fa-solid fa-inbox text-3xl block mb-2 text-gray-300"></i>
                                <p class="text-sm font-medium">
                                    <span x-show="sectionId">Aucune salle dans cette section.</span>
                                    <span x-show="!sectionId">Aucune salle trouvée.</span>
                                </p>
                            </div>
                        </div>

                        {{-- Champs cachés pour les salles sélectionnées --}}
                        <template x-for="id in selectedSalles" :key="'hidden-' + id">
                            <input type="hidden" name="salles[]" :value="id">
                        </template>
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
                {{-- Étape 3 : Validation --}}
                {{-- ════════════════════════════════════════════ --}}
                <div x-show="currentStep === 3" class="form-card">
                    <h2 class="card-title"><i class="fa-solid fa-check-circle"></i> Confirmation</h2>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="summary-card">
                            <span>Libellé</span>
                            <p x-text="libelle || '—'"></p>
                        </div>
                        <div class="summary-card">
                            <span>Montant</span>
                            <p x-text="montant ? montant + ' $' : '—'"></p>
                        </div>
                        <div class="summary-card">
                            <span>Période</span>
                            <p x-text="dateDebut && dateFin ? dateDebut + ' au ' + dateFin : '—'"></p>
                        </div>
                        <div class="summary-card">
                            <span>Salles</span>
                            <p x-text="estPourToutesSalles ? 'Toutes les salles' : selectedSalles.length + ' salle(s)'"></p>
                        </div>
                    </div>

                    <div x-show="description" class="mb-4">
                        <span class="text-sm text-gray-600">Description :</span>
                        <p class="text-sm" x-text="description"></p>
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

            {{-- Panneau latéral --}}
            <div class="space-y-6">
                <div class="selected-eleve-card">
                    <div class="selected-eleve-avatar">
                        <i class="fa-solid fa-money-check-dollar text-2xl"></i>
                    </div>
                    <div>
                        <p class="selected-eleve-name">Frais supplémentaire</p>
                        <p class="selected-eleve-age">Modification en cours</p>
                    </div>
                </div>

                <div class="financial-card">
                    <h3 class="financial-title">Résumé rapide</h3>
                    <div class="financial-row">
                        <span>Montant</span>
                        <strong x-text="montant ? montant + ' $' : '—'"></strong>
                    </div>
                    <div class="financial-row muted">
                        <span>Équivalent FC</span>
                        <span x-text="montantFC ? montantFC + ' FC' : '—'"></span>
                    </div>
                    <div class="financial-row">
                        <span>Salles</span>
                        <strong x-text="estPourToutesSalles ? 'Toutes' : selectedSalles.length + ' salle(s)'"></strong>
                    </div>
                    <div class="financial-row">
                        <span>Période</span>
                        <strong x-text="dateDebut && dateFin ? dateDebut + ' / ' + dateFin : '—'"></strong>
                    </div>
                    <div class="financial-row">
                        <span>État</span>
                        <strong :class="estOuvert ? 'text-green-600' : 'text-gray-500'"
                                x-text="estOuvert ? 'Ouvert' : 'Fermé'"></strong>
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
    .search-wrap { position: relative; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; pointer-events: none; }
    .search-input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 2px solid #e2e8f0; border-radius: 12px; background: #f8fafc; font-size: 0.95rem; transition: border-color 0.3s; outline: none; }
    .search-input:focus { border-color: #667eea; background: white; }
    .salles-grid { display: grid; grid-template-columns: 1fr; gap: 0.75rem; max-height: 400px; overflow-y: auto; padding-right: 4px; }
    @media (min-width: 768px) { .salles-grid { grid-template-columns: repeat(2, 1fr); } }
    .salle-card { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s; background: white; }
    .salle-card:hover { border-color: #c7d2fe; }
    .salle-card.selected { border-color: #667eea; background: #f5f7ff; box-shadow: 0 4px 12px rgba(102,126,234,0.15); }
    .salle-info { display: flex; align-items: center; gap: 8px; font-weight: 500; color: #1e293b; }
    .salle-info i { color: #667eea; }
    .form-row { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
    @media (min-width: 768px) { .form-row { grid-template-columns: 1fr 1fr; } }
    .col-span-2 { grid-column: span 2; }
    .form-field { position: relative; }
    .input-wrap, .select-wrap { position: relative; }
    .input-wrap input, .select-wrap select { width: 100%; padding: 0.8rem 2.5rem 0.8rem 0; border: none; border-bottom: 2px solid #e2e8f0; background: transparent; font-size: 1rem; color: #1e293b; font-weight: 500; transition: border-color 0.3s; outline: none; -webkit-appearance: none; -moz-appearance: none; appearance: none; }
    .input-wrap input::placeholder { color: transparent; }
    .input-wrap input:focus, .select-wrap select:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid, .select-wrap select.is-invalid { border-bottom-color: #ef4444; }
    .input-wrap .float-label { position: absolute; left: 0; top: 0.8rem; color: #94a3b8; font-size: 1rem; pointer-events: none; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label { top: -0.6rem; font-size: 0.72rem; font-weight: 700; color: #667eea; letter-spacing: 0.5px; text-transform: uppercase; }
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
    .summary-card { background: #f8fafc; border-radius: 12px; padding: 1rem; border: 1px solid #f1f5f9; }
    .summary-card span { font-size: 0.75rem; color: #94a3b8; }
    .summary-card p { font-weight: 700; color: #1e293b; margin-top: 0.25rem; }
    .btn-submit, .btn-cancel { padding: 0.8rem 1.75rem; border-radius: 12px; font-weight: 600; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); outline: none; border: none; text-decoration: none; }
    .btn-submit { background: #1e293b; color: white; }
    .btn-submit:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-submit:active { transform: translateY(0) scale(0.98); box-shadow: none; }
    .btn-submit.blue { background: #2563eb; }
    .btn-submit.blue:hover { background: #1d4ed8; box-shadow: 0 8px 16px rgba(37,99,235,0.3); }
    .btn-submit:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
    .btn-cancel { background: white; border: 1.5px solid #e2e8f0; color: #64748b; }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }
    .selected-eleve-card { background: linear-gradient(135deg, #2563eb, #7c3aed); border-radius: 20px; padding: 1.5rem; color: white; display: flex; align-items: center; gap: 1rem; box-shadow: 0 20px 40px rgba(37,99,235,0.3); }
    .selected-eleve-avatar { width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .selected-eleve-name { font-weight: 700; font-size: 1.1rem; }
    .selected-eleve-age { color: #e0e7ff; font-size: 0.9rem; }
    .financial-card { background: white; border-radius: 20px; padding: 1.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
    .financial-title { font-size: 1rem; font-weight: 600; color: #1e293b; margin-bottom: 1rem; }
    .financial-row { display: flex; justify-content: space-between; font-size: 0.9rem; padding: 0.5rem 0; }
    .financial-row.muted { color: #64748b; font-size: 0.8rem; }
    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.3rem; }
    .text-muted { font-size: 0.8rem; color: #64748b; }
    .bg-gray-50 { background-color: #f8fafc; }
    @media (max-width: 768px) {
        .header-container { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        .form-card { padding: 1.5rem; }
        .form-row { grid-template-columns: 1fr; }
        .col-span-2 { grid-column: span 1; }
        .btn-submit, .btn-cancel { width: 100%; }
        .salles-grid { grid-template-columns: 1fr; }
    }
</style>

<script>
    function fraisSupplementaireForm() {
        return {
            /* =========================================================
               DONNÉES INJECTÉES
            ========================================================= */
            salles: @json($salles),
            tauxChange: Number({{ $tauxChange ?? 2800 }}),

            /* =========================================================
               CHAMPS DU FORMULAIRE (pré-remplis avec old() ou la valeur en base)
            ========================================================= */
            libelle:     @json(old('libelle', $fraisSupplementaire->libelle)),
            montant:     {{ old('montant', $fraisSupplementaire->montant) }},
            montantFC:   {{ old('montant', $fraisSupplementaire->montant) * ($tauxChange ?? 2800) }},
            dateDebut:   @json(old('date_debut', optional($fraisSupplementaire->date_debut)->format('Y-m-d'))),
            dateFin:     @json(old('date_fin', optional($fraisSupplementaire->date_fin)->format('Y-m-d'))),
            description: @json(old('description', $fraisSupplementaire->description)),

            /* =========================================================
               ÉTAT
            ========================================================= */
            estOuvert:           {{ old('est_ouvert', $fraisSupplementaire->est_ouvert) ? 'true' : 'false' }},
            estPourToutesSalles: {{ old('est_pour_toutes_salles', $fraisSupplementaire->est_pour_toutes_salles) ? 'true' : 'false' }},
            selectedSalles:      @json(old('salles', $sallesIds)),

            /* =========================================================
               FILTRES
            ========================================================= */
            sectionId:   '',
            salleSearch: '',

            /* =========================================================
               ÉTAPES
            ========================================================= */
            currentStep: 1,
            steps: [{}, {}, {}],

            /* =========================================================
               INITIALISATION
            ========================================================= */
            init() {
                @if($errors->any())
                    const errorFields = @json(array_keys($errors->toArray()));
                    if (errorFields.some(f => ['libelle', 'montant', 'date_debut', 'date_fin', 'description'].includes(f))) {
                        this.currentStep = 1;
                    } else if (errorFields.some(f => ['salles', 'est_pour_toutes_salles'].includes(f))) {
                        this.currentStep = 2;
                    } else {
                        this.currentStep = 3;
                    }
                @endif
            },

            /* =========================================================
               COMPUTED
            ========================================================= */
            get filteredSalles() {
                let list = this.salles;

                if (this.sectionId) {
                    list = list.filter(s => String(s.section_id) === String(this.sectionId));
                }

                if (this.salleSearch) {
                    const q = this.salleSearch.toLowerCase();
                    list = list.filter(s => s.nom.toLowerCase().includes(q));
                }

                return list;
            },

            get canSubmit() {
                return this.libelle
                    && this.montant > 0
                    && this.dateDebut
                    && this.dateFin
                    && (this.estPourToutesSalles || this.selectedSalles.length > 0);
            },

            /* =========================================================
               CONVERSIONS USD ↔ FC
            ========================================================= */
            syncUSDToFC() {
                this.montantFC = (this.montant && this.tauxChange)
                    ? Math.round(parseFloat(this.montant) * this.tauxChange * 100) / 100
                    : 0;
            },

            syncFCToUSD() {
                this.montant = (this.montantFC && this.tauxChange)
                    ? Math.round(parseFloat(this.montantFC) / this.tauxChange * 100) / 100
                    : 0;
            },

            /* =========================================================
               SÉLECTION DES SALLES
            ========================================================= */
            toggleSalle(id) {
                const index = this.selectedSalles.indexOf(id);
                if (index === -1) {
                    this.selectedSalles.push(id);
                } else {
                    this.selectedSalles.splice(index, 1);
                }
            },

            selectAllSalles() {
                const ids = this.filteredSalles.map(s => s.id);
                const set = new Set([...this.selectedSalles, ...ids]);
                this.selectedSalles = Array.from(set);
            },

            deselectAllSalles() {
                const ids = new Set(this.filteredSalles.map(s => s.id));
                this.selectedSalles = this.selectedSalles.filter(id => !ids.has(id));
            },

            /* =========================================================
               NAVIGATION ENTRE ÉTAPES
            ========================================================= */
            validateStep1() {
                if (!this.libelle || !this.montant || !this.dateDebut || !this.dateFin) {
                    alert('Veuillez remplir tous les champs obligatoires.');
                    return;
                }
                this.nextStep();
            },

            validateStep2() {
                if (!this.estPourToutesSalles && this.selectedSalles.length === 0) {
                    alert('Veuillez sélectionner au moins une salle ou cocher "Toutes les salles".');
                    return;
                }
                this.nextStep();
            },

            nextStep() {
                if (this.currentStep < 3) this.currentStep++;
            },

            goToStep(step) {
                if (step <= this.currentStep + 1) this.currentStep = step;
            }
        }
    }
</script>
@endsection