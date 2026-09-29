@extends('layouts.admin')

@section('page_title', 'Modifier un élève')
@section('page_subtitle', 'Mettez à jour les informations de l\'élève et de ses responsables')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8" x-data="eleveForm({{ $eleve->id }})" x-init="init()">

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-start gap-2">
            <i class="bi bi-check-circle-fill mt-0.5"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2">
            <i class="bi bi-exclamation-circle-fill mt-0.5"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- En-tête --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Modifier <strong>l'élève</strong></h1>
            <p class="form-subtitle">Mettez à jour les informations de <strong>{{ $eleve->nom }} {{ $eleve->prenom }}</strong></p>
        </div>
        <a href="{{ route('admin.eleves.index') }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    {{-- Stepper --}}
    <div class="stepper mb-6">
        <div class="flex items-center justify-between">
            @foreach(['Identité', 'Santé', 'Responsables'] as $label)
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

    <form action="{{ route('admin.eleves.update', $eleve) }}" method="POST" enctype="multipart/form-data" class="form-container" novalidate id="eleveForm">
        @csrf
        @method('PUT')

        {{-- Étape 1 : Identité --}}
        <div x-show="step === 1" x-transition:enter.duration.300ms>
            <div class="form-section">
                <h2 class="section-title"><i class="bi bi-person"></i> Identité</h2>
                <div class="form-row three-col">
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="text" name="nom" id="nom" value="{{ old('nom', $eleve->nom) }}" placeholder=" " required class="@error('nom') is-invalid @enderror">
                            <label for="nom" class="float-label">Nom <span class="text-red-500">*</span></label>
                            <i class="bi bi-person icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('nom')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="text" name="postnom" id="postnom" value="{{ old('postnom', $eleve->postnom) }}" placeholder=" " class="@error('postnom') is-invalid @enderror">
                            <label for="postnom" class="float-label">Post-nom</label>
                            <i class="bi bi-person icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('postnom')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="text" name="prenom" id="prenom" value="{{ old('prenom', $eleve->prenom) }}" placeholder=" " required class="@error('prenom') is-invalid @enderror">
                            <label for="prenom" class="float-label">Prénom <span class="text-red-500">*</span></label>
                            <i class="bi bi-person icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('prenom')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="sexe" class="field-label">Sexe <span class="text-red-500">*</span></label>
                        <div class="select-wrap">
                            <select name="sexe" id="sexe" required class="@error('sexe') is-invalid @enderror">
                                <option value="">Choisir...</option>
                                <option value="M" {{ old('sexe', $eleve->sexe) == 'M' ? 'selected' : '' }}>Masculin</option>
                                <option value="F" {{ old('sexe', $eleve->sexe) == 'F' ? 'selected' : '' }}>Féminin</option>
                            </select>
                            <i class="bi bi-chevron-down select-icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('sexe')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="date" name="date_naissance" id="date_naissance" value="{{ old('date_naissance', $eleve->date_naissance?->format('Y-m-d')) }}" placeholder=" " required class="@error('date_naissance') is-invalid @enderror">
                            <label for="date_naissance" class="float-label">Date de naissance <span class="text-red-500">*</span></label>
                            <i class="bi bi-calendar icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('date_naissance')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <div class="input-wrap">
                            <input type="text" name="lieu_naissance" id="lieu_naissance" value="{{ old('lieu_naissance', $eleve->lieu_naissance) }}" placeholder=" " required class="@error('lieu_naissance') is-invalid @enderror">
                            <label for="lieu_naissance" class="float-label">Lieu de naissance <span class="text-red-500">*</span></label>
                            <i class="bi bi-geo-alt icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('lieu_naissance')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <div class="textarea-wrap">
                            <textarea name="adresse" id="adresse" rows="2" placeholder=" " required class="@error('adresse') is-invalid @enderror">{{ old('adresse', $eleve->adresse) }}</textarea>
                            <label for="adresse" class="float-label">Adresse <span class="text-red-500">*</span></label>
                            <i class="bi bi-house icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('adresse')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div class="flex justify-between items-center mt-4">
                <a href="{{ route('admin.eleves.index') }}" class="btn-cancel">Annuler</a>
                <button type="button" class="btn-next" @click="step = 2">Suivant <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>

        {{-- Étape 2 : Santé et photo --}}
        <div x-show="step === 2" x-transition:enter.duration.300ms>
            <div class="form-section">
                <h2 class="section-title"><i class="bi bi-file-image"></i> Photo et santé</h2>
                <div class="form-row single-col">
                    <div class="form-field">
                        <label for="photo" class="field-label">Photo</label>
                        @if($eleve->photo)
                            <div class="photo-preview mb-2">
                                <img src="{{ $eleve->photo_url }}" alt="Photo actuelle" class="h-16 w-16 rounded-lg object-cover border border-gray-200">
                                <span class="text-xs text-gray-500">Photo actuelle</span>
                            </div>
                        @endif
                        <div class="file-upload-wrap">
                            <input type="file" name="photo" id="photo" accept="image/*" class="file-input">
                            <label for="photo" class="file-label">
                                <i class="bi bi-cloud-upload"></i> Choisir une nouvelle image
                            </label>
                        </div>
                        @error('photo')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <div class="textarea-wrap">
                            <textarea name="maladie_chronique" id="maladie_chronique" rows="2" placeholder=" " class="@error('maladie_chronique') is-invalid @enderror">{{ old('maladie_chronique', $eleve->maladie_chronique) }}</textarea>
                            <label for="maladie_chronique" class="float-label">Maladie chronique</label>
                            <i class="bi bi-heart-pulse icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('maladie_chronique')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-field">
                        <div class="textarea-wrap">
                            <textarea name="allergies" id="allergies" rows="2" placeholder=" " class="@error('allergies') is-invalid @enderror">{{ old('allergies', $eleve->allergies) }}</textarea>
                            <label for="allergies" class="float-label">Allergies</label>
                            <i class="bi bi-exclamation-triangle icon"></i>
                            <div class="line-focus"></div>
                        </div>
                        @error('allergies')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div class="flex justify-between items-center mt-4">
                <a href="{{ route('admin.eleves.index') }}" class="btn-cancel">Annuler</a>
                <div class="flex gap-2">
                    <button type="button" class="btn-prev" @click="step = 1"><i class="bi bi-arrow-left"></i> Précédent</button>
                    <button type="button" class="btn-next" @click="step = 3">Suivant <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>
        </div>

        {{-- Étape 3 : Responsables --}}
        <div x-show="step === 3" x-transition:enter.duration.300ms>
            <div class="form-section">
                <h2 class="section-title"><i class="bi bi-people"></i> Responsables</h2>
                <template x-for="(responsable, index) in responsables" :key="index">
                    <div class="responsable-card">
                        <div class="responsable-header">
                            <div class="responsable-select">
                                <select :name="'responsables['+index+'][type]'" required class="responsable-type-select" x-model="responsable.type">
                                    <option value="pere">Père</option>
                                    <option value="mere">Mère</option>
                                    <option value="tuteur">Tuteur</option>
                                </select>
                                <i class="bi bi-chevron-down select-icon"></i>
                            </div>
                            <label class="vivant-checkbox">
                                <input type="checkbox" :name="'responsables['+index+'][vivant]'" value="1" x-model="responsable.vivant" class="form-check-input">
                                <span>Vivant</span>
                            </label>
                            <button type="button" @click="removeResponsable(index)" class="btn-remove-responsable">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <div class="form-row three-col" :class="{ 'opacity-50 pointer-events-none': !responsable.vivant }">
                            <div class="form-field">
                                <div class="input-wrap">
                                    <input type="text" :name="'responsables['+index+'][nom]'" placeholder=" " required x-model="responsable.nom">
                                    <label class="float-label">Nom <span class="text-red-500">*</span></label>
                                    <i class="bi bi-person icon"></i>
                                    <div class="line-focus"></div>
                                </div>
                            </div>
                            <div class="form-field">
                                <div class="input-wrap">
                                    <input type="text" :name="'responsables['+index+'][profession]'" placeholder=" " x-model="responsable.profession">
                                    <label class="float-label">Profession</label>
                                    <i class="bi bi-briefcase icon"></i>
                                    <div class="line-focus"></div>
                                </div>
                            </div>
                            <div class="form-field">
                                <div class="input-wrap">
                                    <input type="text" :name="'responsables['+index+'][telephone]'" placeholder=" " required x-model="responsable.telephone">
                                    <label class="float-label">Téléphone <span class="text-red-500">*</span></label>
                                    <i class="bi bi-phone icon"></i>
                                    <div class="line-focus"></div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" :name="'responsables['+index+'][id]'" x-model="responsable.id">
                    </div>
                </template>
                <button type="button" @click="addResponsable" class="btn-add-responsable">
                    <i class="bi bi-plus-lg"></i> Ajouter un responsable
                </button>
                @error('responsables')<p class="error-text">{{ $message }}</p>@enderror
                @error('responsables.*')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-between items-center mt-4">
                <a href="{{ route('admin.eleves.index') }}" class="btn-cancel">Annuler</a>
                <div class="flex gap-2">
                    <button type="button" class="btn-prev" @click="step = 2"><i class="bi bi-arrow-left"></i> Précédent</button>
                    <button type="submit" class="btn-submit" id="submitBtn">Mettre à jour <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>
        </div>

    </form>
</div>

{{-- Styles --}}
<style>
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        animation: fadeUp 0.6s 0.1s ease forwards;
        opacity: 0;
    }
    .form-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
    }
    .form-title strong { font-weight: 800; color: #4f46e5; }
    .form-subtitle { color: #94a3b8; font-size: 0.95rem; }
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
        padding: 2.5rem 3rem;
        border-radius: 24px;
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

    /* --- Styles flottants --- */
    .form-section { margin-bottom: 2rem; }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { color: #667eea; }

    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
        align-items: start;
    }
    @media (min-width: 768px) {
        .form-row { grid-template-columns: repeat(2, 1fr); }
        .form-row.three-col { grid-template-columns: repeat(3, 1fr); }
        .form-row.single-col { grid-template-columns: 1fr; }
    }

    .form-field {
        position: relative;
        animation: fieldSlide 0.5s ease forwards;
        opacity: 0;
    }
    .form-row .form-field:nth-child(1) { animation-delay: 0.35s; }
    .form-row .form-field:nth-child(2) { animation-delay: 0.45s; }
    .form-row .form-field:nth-child(3) { animation-delay: 0.55s; }
    @keyframes fieldSlide {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .field-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    /* Input flottant */
    .input-wrap { position: relative; }
    .input-wrap input, .input-wrap textarea, .input-wrap select {
        width: 100%;
        padding: 0.9rem 2.8rem 0.9rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none !important;
        -webkit-appearance: none;
        appearance: none;
        min-height: 42px;
    }
    .input-wrap input::placeholder, .input-wrap textarea::placeholder { color: transparent; }
    .input-wrap input:focus, .input-wrap textarea:focus, .input-wrap select:focus { border-bottom-color: #667eea; }
    .input-wrap input.is-invalid, .input-wrap textarea.is-invalid, .input-wrap select.is-invalid { border-bottom-color: #ef4444; }
    .input-wrap input:disabled { color: #64748b; background: #f8fafc; cursor: not-allowed; }

    .input-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.9rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label,
    .input-wrap textarea:focus ~ .float-label,
    .input-wrap textarea:not(:placeholder-shown) ~ .float-label,
    .input-wrap select:focus ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label,
    .input-wrap textarea.is-invalid:focus ~ .float-label,
    .input-wrap textarea.is-invalid:not(:placeholder-shown) ~ .float-label,
    .input-wrap select.is-invalid:focus ~ .float-label { color: #ef4444; }

    .input-wrap i.icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.2rem;
        transition: all 0.3s;
        pointer-events: none;
    }
    .input-wrap input:focus ~ i.icon,
    .input-wrap textarea:focus ~ i.icon,
    .input-wrap select:focus ~ i.icon { color: #667eea; transform: translateY(-50%) scale(1.1); }
    .input-wrap input.is-invalid ~ i.icon,
    .input-wrap textarea.is-invalid ~ i.icon { color: #ef4444; }

    .input-wrap .line-focus {
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
    .input-wrap textarea:focus ~ .line-focus,
    .input-wrap select:focus ~ .line-focus { width: 100%; }

    /* Textarea flottant */
    .textarea-wrap { position: relative; }
    .textarea-wrap textarea {
        width: 100%;
        padding: 0.9rem 2.8rem 0.9rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none !important;
        resize: vertical;
        min-height: 60px;
    }
    .textarea-wrap textarea::placeholder { color: transparent; }
    .textarea-wrap textarea:focus { border-bottom-color: #667eea; }
    .textarea-wrap textarea.is-invalid { border-bottom-color: #ef4444; }

    .textarea-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.9rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .textarea-wrap textarea:focus ~ .float-label,
    .textarea-wrap textarea:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .textarea-wrap textarea.is-invalid:focus ~ .float-label,
    .textarea-wrap textarea.is-invalid:not(:placeholder-shown) ~ .float-label { color: #ef4444; }

    .textarea-wrap i.icon {
        position: absolute;
        right: 0;
        top: 0.9rem;
        color: #cbd5e1;
        font-size: 1.2rem;
        transition: all 0.3s;
        pointer-events: none;
    }
    .textarea-wrap textarea:focus ~ i.icon { color: #667eea; transform: scale(1.1); }
    .textarea-wrap textarea.is-invalid ~ i.icon { color: #ef4444; }

    .textarea-wrap .line-focus {
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
    .textarea-wrap textarea:focus ~ .line-focus { width: 100%; }

    /* Select */
    .select-wrap { position: relative; }
    .select-wrap select {
        width: 100%;
        padding: 0.9rem 2.8rem 0.9rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none !important;
        -webkit-appearance: none;
        appearance: none;
        cursor: pointer;
        min-height: 42px;
    }
    .select-wrap select:focus { border-bottom-color: #667eea; }
    .select-wrap select.is-invalid { border-bottom-color: #ef4444; }
    .select-wrap .select-icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.2rem;
        pointer-events: none;
        transition: color 0.3s;
    }
    .select-wrap select:focus ~ .select-icon { color: #667eea; }
    .select-wrap .line-focus {
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
    .select-wrap select:focus ~ .line-focus { width: 100%; }

    /* File upload */
    .file-upload-wrap { position: relative; margin-top: 0.5rem; }
    .file-input {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    .file-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.75rem 1.25rem;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        color: #475569;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }
    .file-input:hover + .file-label { border-color: #667eea; color: #667eea; background: #f1f5f9; }

    /* Responsables */
    .photo-preview {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }
    .photo-preview img {
        height: 64px;
        width: 64px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        object-fit: cover;
    }

    .responsable-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        animation: fieldSlide 0.4s ease forwards;
    }
    .responsable-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        gap: 0.75rem;
    }
    .responsable-select {
        position: relative;
        flex: 1;
    }
    .responsable-type-select {
        width: 100%;
        padding: 0.5rem 2rem 0.5rem 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: white;
        font-size: 0.9rem;
        color: #1e293b;
        appearance: none;
        outline: none;
        min-height: 42px;
    }
    .responsable-select .select-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #94a3b8;
    }
    .vivant-checkbox {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
        color: #475569;
        cursor: pointer;
    }
    .btn-remove-responsable {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 1.1rem;
        padding: 0.25rem;
        transition: transform 0.2s;
    }
    .btn-remove-responsable:hover { transform: scale(1.2); }

    .btn-add-responsable {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 0.5rem;
        padding: 0.6rem 1.25rem;
        background: #f1f5f9;
        border: none;
        border-radius: 10px;
        color: #475569;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-add-responsable:hover { background: #e2e8f0; color: #1e293b; }

    .error-text { color: #ef4444; font-size: 0.8rem; margin-top: 0.3rem; }

    .btn-submit, .btn-next, .btn-prev {
        padding: 0.8rem 2rem;
        background: #1e293b;
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        outline: none !important;
    }
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
        justify-content: center;
    }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    @media (max-width: 768px) {
        .form-container { padding: 1.5rem; }
        .form-row.three-col { grid-template-columns: 1fr; }
        .form-row.single-col { grid-template-columns: 1fr; }
        .responsable-header { flex-direction: column; align-items: stretch; }
        .btn-submit, .btn-cancel, .btn-next, .btn-prev { width: 100%; }
        .header-container { flex-direction: column; align-items: flex-start; gap: 1rem; }
        .step-label { display: none; }
        .flex.justify-between.items-center { flex-direction: column; align-items: stretch; gap: 0.5rem; }
    }
</style>

{{-- Scripts --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('eleveForm', (eleveId) => ({
            step: 1,
            responsables: [],
            eleveId: eleveId,

            init() {
                @if($errors->any())
                    const errorFields = @json(array_keys($errors->toArray()));
                    if (errorFields.some(f => ['nom','postnom','prenom','sexe','date_naissance','lieu_naissance','adresse'].includes(f))) {
                        this.step = 1;
                    } else if (errorFields.some(f => ['photo','maladie_chronique','allergies'].includes(f))) {
                        this.step = 2;
                    } else if (errorFields.some(f => ['responsables','responsables.*'].includes(f))) {
                        this.step = 3;
                    }
                @endif

                // Charger les responsables existants depuis le serveur
                @if($eleve->responsables->count() > 0)
                    @php
                        $responsablesJson = $eleve->responsables->map(function($resp) {
                            return [
                                'id' => $resp->id,
                                'type' => $resp->type,
                                'nom' => $resp->nom,
                                'profession' => $resp->profession,
                                'telephone' => $resp->telephone,
                                'vivant' => (bool) $resp->vivant,
                            ];
                        })->toJson();
                    @endphp
                    this.responsables = {!! $responsablesJson !!};
                @else
                    // Par défaut : père et mère
                    this.responsables = [
                        { id: null, type: 'pere', nom: '', profession: '', telephone: '', vivant: true },
                        { id: null, type: 'mere', nom: '', profession: '', telephone: '', vivant: true }
                    ];
                @endif
            },

            addResponsable() {
                this.responsables.push({ id: null, type: 'tuteur', nom: '', profession: '', telephone: '', vivant: true });
            },

            removeResponsable(index) {
                if (this.responsables.length > 1) {
                    this.responsables.splice(index, 1);
                } else {
                    alert('Vous devez avoir au moins un responsable.');
                }
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('eleveForm');
        const btn = document.getElementById('submitBtn');
        form.addEventListener('submit', function() {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Mise à jour...';
        });
    });
</script>

<style>
    .spinner-border {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .opacity-50 { opacity: 0.5; }
    .pointer-events-none { pointer-events: none; }
    .text-red-500 { color: #ef4444; }
</style>
@endsection