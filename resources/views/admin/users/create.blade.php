@extends('layouts.admin')

@section('page_title', 'Nouveau personnel')
@section('page_subtitle', 'Ajoutez un membre au personnel de l\'école')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8" x-data="multiStepForm()" x-init="init()">

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="flash flash-success" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error" role="alert">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- En-tête --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Nouveau <strong>membre du personnel</strong></h1>
            <p class="form-subtitle">Ajoutez un utilisateur au personnel de l'école</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="back-link">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour
        </a>
    </div>

    {{-- Stepper --}}
    <div class="stepper mb-6">
        <div class="flex items-center justify-between">
            @foreach(['Identité', 'Compte', 'Profil'] as $label)
                <div class="flex items-center flex-1">
                    <div class="flex items-center gap-2">
                        <div class="step-circle" :class="step >= {{ $loop->index + 1 }} ? 'active' : ''">
                            <span x-show="step > {{ $loop->index + 1 }}" class="text-white">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
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

    <form action="{{ route('admin.users.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="form-container"
          novalidate
          id="userForm">
        @csrf

        {{-- ═══════════════════════════════════════════════════
             ÉTAPE 1 — IDENTITÉ
             ═══════════════════════════════════════════════════ --}}
        <div x-show="step === 1" x-transition:enter.duration.300ms>
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-person-badge" aria-hidden="true"></i> Identité
                </h3>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="text" name="name" id="name"
                                       value="{{ old('name') }}"
                                       placeholder=" " required maxlength="255"
                                       autocomplete="name"
                                       class="@error('name') is-invalid @enderror">
                                <label for="name" class="float-label">
                                    Nom complet <span class="text-red-500">*</span>
                                </label>
                                <i class="bi bi-person icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('name')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label for="sexe" class="field-label">Sexe</label>
                            <div class="select-wrap">
                                <select name="sexe" id="sexe"
                                        class="@error('sexe') is-invalid @enderror">
                                    <option value="">--</option>
                                    <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                                    <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                                </select>
                                <i class="bi bi-chevron-down select-icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('sexe')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="tel" name="telephone" id="telephone"
                                       value="{{ old('telephone') }}"
                                       placeholder=" " maxlength="20"
                                       inputmode="tel" autocomplete="tel"
                                       class="@error('telephone') is-invalid @enderror">
                                <label for="telephone" class="float-label">Téléphone</label>
                                <i class="bi bi-telephone icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('telephone')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="date" name="date_naissance" id="date_naissance"
                                       value="{{ old('date_naissance') }}"
                                       placeholder=" " max="{{ now()->subYears(16)->format('Y-m-d') }}"
                                       class="@error('date_naissance') is-invalid @enderror">
                                <label for="date_naissance" class="float-label">Date de naissance</label>
                                <i class="bi bi-calendar icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('date_naissance')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-between items-center mt-4 step-actions">
                <a href="{{ route('admin.users.index') }}" class="btn-cancel">Annuler</a>
                <button type="button" class="btn-next" @click="goToStep(2)">
                    Suivant <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════
             ÉTAPE 2 — COMPTE (avec nouvelles fonctionnalités)
             ═══════════════════════════════════════════════════ --}}
        <div x-show="step === 2" x-transition:enter.duration.300ms>
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i> Compte utilisateur
                </h3>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="email" name="email" id="email"
                                       value="{{ old('email') }}"
                                       placeholder=" " required maxlength="255"
                                       inputmode="email" autocomplete="email"
                                       class="@error('email') is-invalid @enderror">
                                <label for="email" class="float-label">
                                    Adresse email <span class="text-red-500">*</span>
                                </label>
                                <i class="bi bi-envelope icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('email')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label for="role" class="field-label">
                                Rôle principal <span class="text-red-500">*</span>
                            </label>
                            <div class="select-wrap">
                                <select name="role" id="role" required
                                        class="@error('role') is-invalid @enderror">
                                    <option value="">Sélectionnez un rôle</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}"
                                                @selected(old('role') === $role->name)>
                                            {{ $role->label ?? $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down select-icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('role')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        {{-- ✅ CHAMP MOT DE PASSE avec force + œil --}}
                        <div class="form-field">
                            <div class="input-wrap password-wrap">
                                <input type="password" name="password" id="password"
                                       placeholder=" " required minlength="6"
                                       autocomplete="new-password"
                                       class="@error('password') is-invalid @enderror">
                                <label for="password" class="float-label">
                                    Mot de passe <span class="text-red-500">*</span>
                                </label>
                                <i class="bi bi-lock icon" aria-hidden="true"></i>
                                <button type="button"
                                        class="toggle-password"
                                        data-target="password"
                                        aria-label="Afficher le mot de passe"
                                        aria-pressed="false"
                                        tabindex="-1">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <div class="line-focus"></div>
                            </div>

                            {{-- ✅ Indicateur de force --}}
                            <div class="password-strength" id="passwordStrength" aria-live="polite" hidden>
                                <div class="strength-bars">
                                    <span class="strength-bar" data-level="1"></span>
                                    <span class="strength-bar" data-level="2"></span>
                                    <span class="strength-bar" data-level="3"></span>
                                    <span class="strength-bar" data-level="4"></span>
                                </div>
                                <div class="strength-header">
                                    <span class="strength-label">Force : <strong id="strengthText">—</strong></span>
                                </div>
                                <ul class="strength-checks" id="strengthChecks">
                                    <li data-check="length"><i class="bi bi-circle"></i> Au moins 8 caractères</li>
                                    <li data-check="lower"><i class="bi bi-circle"></i> Une minuscule</li>
                                    <li data-check="upper"><i class="bi bi-circle"></i> Une majuscule</li>
                                    <li data-check="number"><i class="bi bi-circle"></i> Un chiffre</li>
                                    <li data-check="special"><i class="bi bi-circle"></i> Un caractère spécial</li>
                                </ul>
                            </div>

                            @error('password')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        {{-- ✅ CONFIRMATION avec vérification de correspondance --}}
                        <div class="form-field">
                            <div class="input-wrap password-wrap">
                                <input type="password" name="password_confirmation"
                                       id="password_confirmation"
                                       placeholder=" " required
                                       autocomplete="new-password"
                                       class="@error('password_confirmation') is-invalid @enderror">
                                <label for="password_confirmation" class="float-label">
                                    Confirmer <span class="text-red-500">*</span>
                                </label>
                                <i class="bi bi-lock icon" aria-hidden="true"></i>
                                <button type="button"
                                        class="toggle-password"
                                        data-target="password_confirmation"
                                        aria-label="Afficher la confirmation"
                                        aria-pressed="false"
                                        tabindex="-1">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <div class="line-focus"></div>
                            </div>

                            {{-- ✅ Indicateur de correspondance --}}
                            <div class="match-indicator" id="matchIndicator" aria-live="polite" hidden>
                                <i class="match-icon bi bi-circle" aria-hidden="true"></i>
                                <span class="match-text">—</span>
                            </div>

                            @error('password_confirmation')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Lien avec un compte abonné (optionnel) --}}
                    @if(isset($contacts) && $contacts->isNotEmpty())
                        <div class="form-row full-width">
                            <div class="form-field">
                                <div class="link-contact-wrapper">
                                    <div class="link-contact-header">
                                        <div class="link-contact-icon" aria-hidden="true">
                                            <i class="bi bi-link-45deg"></i>
                                        </div>
                                        <div class="link-contact-text">
                                            <label for="contact_id" class="link-contact-label">
                                                Compte abonné lié
                                                <span class="optional-badge">Optionnel</span>
                                            </label>
                                            <p class="link-contact-hint">
                                                Liez cet utilisateur à un compte abonné (parent, tuteur).
                                                Il pourra basculer entre son <strong>espace staff</strong>
                                                et son <strong>espace abonné</strong> en 1 clic.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="select-wrap mt-3">
                                        <select name="contact_id" id="contact_id"
                                                class="@error('contact_id') is-invalid @enderror">
                                            <option value="">— Aucun compte abonné lié —</option>
                                            @foreach($contacts as $c)
                                                <option value="{{ $c->id }}"
                                                        @selected(old('contact_id') == $c->id)>
                                                    {{ $c->nom }} — {{ $c->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <i class="bi bi-chevron-down select-icon" aria-hidden="true"></i>
                                        <div class="line-focus"></div>
                                    </div>
                                    @error('contact_id')<p class="error-text">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex justify-between items-center mt-4 step-actions">
                <button type="button" class="btn-prev" @click="goToStep(1)">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Précédent
                </button>
                <button type="button" class="btn-next" @click="goToStep(3)">
                    Suivant <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════
             ÉTAPE 3 — PROFIL
             ═══════════════════════════════════════════════════ --}}
        <div x-show="step === 3" x-transition:enter.duration.300ms>
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-briefcase" aria-hidden="true"></i> Informations professionnelles
                </h3>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-field">
                            <label for="fonction_id" class="field-label">Fonction</label>
                            <div class="select-wrap">
                                <select name="fonction_id" id="fonction_id">
                                    <option value="">Aucune</option>
                                    @foreach($fonctions as $fonction)
                                        <option value="{{ $fonction->id }}"
                                                @selected(old('fonction_id') == $fonction->id)>
                                            {{ $fonction->nom }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down select-icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('fonction_id')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <label for="section_id" class="field-label">Section</label>
                            <div class="select-wrap">
                                <select name="section_id" id="section_id">
                                    <option value="">Aucune (non affecté)</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}"
                                                @selected(old('section_id') == $section->id)>
                                            {{ $section->nom }}
                                        </option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down select-icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('section_id')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row full-width">
                        <div class="form-field">
                            <div class="textarea-wrap">
                                <textarea name="adresse" id="adresse" rows="2"
                                          placeholder=" " maxlength="500"
                                          class="@error('adresse') is-invalid @enderror">{{ old('adresse') }}</textarea>
                                <label for="adresse" class="float-label">Adresse</label>
                                <i class="bi bi-house icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('adresse')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-row full-width">
                        <div class="form-field">
                            <label for="photo" class="field-label">Photo de profil</label>
                            <div class="file-upload-wrap" id="dropZone">
                                <input type="file" name="photo" id="photo"
                                       accept="image/jpeg,image/png,image/jpg,image/webp"
                                       class="file-input">
                                <label for="photo" class="file-label">
                                    <i class="bi bi-cloud-upload" aria-hidden="true"></i>
                                    <span>Choisir une image</span>
                                    <small>JPG, PNG, WebP — max 2 Mo (glisser-déposer accepté)</small>
                                </label>
                            </div>
                            @error('photo')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-between items-center mt-4 step-actions">
                <button type="button" class="btn-prev" @click="goToStep(2)">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Précédent
                </button>
                <button type="submit" class="btn-submit" id="submitBtn">
                    Enregistrer <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    /* ═══════════════════════════════════════════════════════════
       HEADER
       ═══════════════════════════════════════════════════════════ */
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
        white-space: nowrap;
    }
    .back-link:hover { color: #4f46e5; }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ═══════════════════════════════════════════════════════════
       FORM CONTAINER
       ═══════════════════════════════════════════════════════════ */
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

    /* ═══════════════════════════════════════════════════════════
       STEPPER
       ═══════════════════════════════════════════════════════════ */
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

    /* ═══════════════════════════════════════════════════════════
       FORM SECTIONS
       ═══════════════════════════════════════════════════════════ */
    .form-section {
        padding-bottom: 1.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { color: #667eea; }

    .form-grid { display: flex; flex-direction: column; gap: 1.5rem; }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    .form-row.full-width { grid-template-columns: 1fr; }

    .form-field { position: relative; min-width: 0; }
    .field-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    /* ═══════════════════════════════════════════════════════════
       INPUTS FLOAT LABEL
       ═══════════════════════════════════════════════════════════ */
    .input-wrap, .textarea-wrap, .select-wrap { position: relative; }

    .input-wrap input,
    .textarea-wrap textarea,
    .select-wrap select {
        width: 100%;
        padding: 0.9rem 2.8rem 0.9rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        font-family: inherit;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none;
        -webkit-appearance: none;
        appearance: none;
    }
    .select-wrap select { cursor: pointer; }

    .input-wrap input::placeholder,
    .textarea-wrap textarea::placeholder { color: transparent; }

    .input-wrap input:focus,
    .textarea-wrap textarea:focus,
    .select-wrap select:focus { border-bottom-color: #667eea; }

    .input-wrap input.is-invalid,
    .textarea-wrap textarea.is-invalid,
    .select-wrap select.is-invalid { border-bottom-color: #ef4444; }

    .input-wrap .float-label,
    .textarea-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.9rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        white-space: nowrap;
        max-width: calc(100% - 2.5rem);
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label,
    .textarea-wrap textarea:focus ~ .float-label,
    .textarea-wrap textarea:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label,
    .textarea-wrap textarea.is-invalid:focus ~ .float-label,
    .textarea-wrap textarea.is-invalid:not(:placeholder-shown) ~ .float-label {
        color: #ef4444;
    }

    .input-wrap i.icon,
    .textarea-wrap i.icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        color: #cbd5e1;
        font-size: 1.2rem;
        transition: all 0.3s;
        pointer-events: none;
    }
    .textarea-wrap i.icon { top: 0.9rem; transform: none; }

    /* ✅ Décalage de l'icône cadenas pour les champs password (place à l'œil) */
    .input-wrap.password-wrap i.icon { right: 2.6rem; }

    .input-wrap input:focus ~ i.icon,
    .textarea-wrap textarea:focus ~ i.icon {
        color: #667eea;
        transform: translateY(-50%) scale(1.1);
    }
    .textarea-wrap textarea:focus ~ i.icon {
        transform: scale(1.1);
    }
    .input-wrap input.is-invalid ~ i.icon,
    .textarea-wrap textarea.is-invalid ~ i.icon { color: #ef4444; }

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

    .input-wrap .line-focus,
    .textarea-wrap .line-focus,
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
    .input-wrap input:focus ~ .line-focus,
    .textarea-wrap textarea:focus ~ .line-focus,
    .select-wrap select:focus ~ .line-focus { width: 100%; }

    .textarea-wrap textarea {
        resize: vertical;
        min-height: 60px;
        line-height: 1.5;
    }

    /* ═══════════════════════════════════════════════════════════
       ✅ TOGGLE ŒIL MOT DE PASSE
       ═══════════════════════════════════════════════════════════ */
    .toggle-password {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        color: #94a3b8;
        transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
        padding: 0;
        font-size: 1.1rem;
        z-index: 2;
    }
    .toggle-password:hover {
        color: #667eea;
        background: rgba(102,126,234,0.08);
    }
    .toggle-password:focus-visible {
        outline: 2px solid #667eea;
        outline-offset: 2px;
    }
    .toggle-password.is-visible { color: #667eea; }
    .toggle-password.is-visible:hover { background: rgba(102,126,234,0.15); }

    /* ═══════════════════════════════════════════════════════════
       ✅ INDICATEUR DE FORCE DU MOT DE PASSE
       ═══════════════════════════════════════════════════════════ */
    .password-strength {
        margin-top: 0.85rem;
        padding: 0.9rem 1rem;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        animation: fadeIn 0.35s ease forwards;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .strength-bars {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 5px;
        margin-bottom: 0.6rem;
    }
    .strength-bar {
        height: 5px;
        background: #e2e8f0;
        border-radius: 999px;
        transition: background 0.35s cubic-bezier(0.4,0,0.2,1);
    }

    /* Niveaux 1 à 4 */
    .password-strength[data-strength="1"] .strength-bar[data-level="1"] { background: #ef4444; }
    .password-strength[data-strength="2"] .strength-bar[data-level="1"],
    .password-strength[data-strength="2"] .strength-bar[data-level="2"] { background: #f59e0b; }
    .password-strength[data-strength="3"] .strength-bar[data-level="1"],
    .password-strength[data-strength="3"] .strength-bar[data-level="2"],
    .password-strength[data-strength="3"] .strength-bar[data-level="3"] { background: #eab308; }
    .password-strength[data-strength="4"] .strength-bar { background: #22c55e; }

    .strength-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.6rem;
    }
    .strength-label {
        font-size: 0.78rem;
        color: #64748b;
        font-weight: 500;
    }
    .strength-label strong {
        font-weight: 700;
        transition: color 0.3s;
    }
    .password-strength[data-strength="1"] .strength-label strong { color: #ef4444; }
    .password-strength[data-strength="2"] .strength-label strong { color: #f59e0b; }
    .password-strength[data-strength="3"] .strength-label strong { color: #ca8a04; }
    .password-strength[data-strength="4"] .strength-label strong { color: #16a34a; }

    .strength-checks {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.35rem 0.75rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .strength-checks li {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        color: #94a3b8;
        transition: color 0.25s;
        line-height: 1.3;
    }
    .strength-checks li i {
        font-size: 0.65rem;
        transition: all 0.25s;
        flex-shrink: 0;
    }
    .strength-checks li.is-valid {
        color: #16a34a;
        font-weight: 500;
    }
    .strength-checks li.is-valid i::before {
        content: "\f26b"; /* bi-check-circle-fill fallback via font */
    }

    /* ═══════════════════════════════════════════════════════════
       ✅ INDICATEUR DE CORRESPONDANCE DES MOTS DE PASSE
       ═══════════════════════════════════════════════════════════ */
    .match-indicator {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 0.6rem;
        padding: 0.55rem 0.85rem;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        animation: fadeIn 0.35s ease forwards;
    }
    .match-indicator .match-icon { font-size: 0.9rem; }

    .match-indicator.is-match {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .match-indicator.is-match .match-icon::before {
        content: "\f26b"; /* check circle */
    }

    .match-indicator.is-mismatch {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    .match-indicator.is-mismatch .match-icon::before {
        content: "\f623"; /* exclamation circle */
    }

    .match-indicator.is-empty {
        background: #f8fafc;
        color: #94a3b8;
        border: 1px solid #f1f5f9;
    }

    /* ═══════════════════════════════════════════════════════════
       LINK CONTACT WRAPPER
       ═══════════════════════════════════════════════════════════ */
    .link-contact-wrapper {
        padding: 1.25rem;
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        border: 1px solid #c7d2fe;
        border-radius: 14px;
    }

    .link-contact-header {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
    }

    .link-contact-icon {
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(79, 70, 229, 0.15);
        color: #4f46e5;
        font-size: 1.1rem;
    }

    .link-contact-text { flex: 1; min-width: 0; }

    .link-contact-label {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin: 0 0 0.35rem;
    }

    .optional-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.5rem;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #c7d2fe;
        color: #4f46e5;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-radius: 999px;
    }

    .link-contact-hint {
        font-size: 0.82rem;
        color: #475569;
        line-height: 1.55;
        margin: 0;
    }
    .link-contact-hint strong { color: #1e293b; }

    /* ═══════════════════════════════════════════════════════════
       FILE UPLOAD (avec drag & drop)
       ═══════════════════════════════════════════════════════════ */
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
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        font-size: 0.9rem;
    }
    .file-label small {
        display: block;
        font-size: 0.72rem;
        color: #94a3b8;
        font-weight: 400;
        margin-top: 2px;
    }
    .file-input:hover + .file-label,
    .file-upload-wrap.is-dragover .file-label {
        border-color: #667eea;
        color: #667eea;
        background: #f1f5f9;
        transform: scale(1.01);
    }
    .file-upload-wrap.is-dragover .file-label {
        background: #eef2ff;
        border-style: solid;
    }

    /* ═══════════════════════════════════════════════════════════
       BUTTONS
       ═══════════════════════════════════════════════════════════ */
    .btn-submit, .btn-next, .btn-prev, .btn-cancel {
        padding: 0.8rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        outline: none;
        border: none;
        white-space: nowrap;
        font-family: inherit;
    }

    .btn-submit, .btn-next {
        background: #1e293b;
        color: white;
    }
    .btn-submit:hover:not(:disabled),
    .btn-next:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }

    .btn-submit:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none;
        background: #94a3b8;
    }

    .btn-prev {
        background: #f1f5f9;
        color: #475569;
    }
    .btn-prev:hover { background: #e2e8f0; transform: translateY(-2px); }

    .btn-cancel {
        background: white;
        border: 1.5px solid #e2e8f0;
        color: #64748b;
        text-decoration: none;
    }
    .btn-cancel:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    .error-text {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.35rem;
        line-height: 1.4;
    }

    /* ═══════════════════════════════════════════════════════════
       FLASH MESSAGES
       ═══════════════════════════════════════════════════════════ */
    .flash {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    .flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
    .flash-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

    /* ═══════════════════════════════════════════════════════════
       RESPONSIVE
       ═══════════════════════════════════════════════════════════ */
    @media (max-width: 992px) {
        .form-container { padding: 2rem; }
    }

    @media (max-width: 768px) {
        .form-container { padding: 1.5rem; border-radius: 16px; }
        .form-row { grid-template-columns: 1fr; }
        .header-container {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .form-title { font-size: 1.4rem; }
        .form-subtitle { font-size: 0.85rem; }
        .section-title { font-size: 0.9rem; }

        .input-wrap input,
        .textarea-wrap textarea,
        .select-wrap select { font-size: 16px; }

        .step-actions {
            flex-direction: column-reverse;
            align-items: stretch;
            gap: 0.75rem;
        }
        .step-actions .btn-submit,
        .step-actions .btn-cancel,
        .step-actions .btn-next,
        .step-actions .btn-prev { width: 100%; }

        .file-label { width: 100%; justify-content: center; }

        .strength-checks { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 480px) {
        .form-container { padding: 1.15rem; }
        .form-title { font-size: 1.2rem; }
        .step-circle { width: 28px; height: 28px; font-size: 0.75rem; }
        .step-label { font-size: 0.7rem; }
        .link-contact-wrapper { padding: 1rem; }
        .link-contact-icon { width: 32px; height: 32px; font-size: 0.95rem; }
        .link-contact-label { font-size: 0.85rem; }
        .link-contact-hint { font-size: 0.78rem; }

        .strength-checks { grid-template-columns: 1fr; }
        .password-strength { padding: 0.75rem 0.85rem; }
    }

    /* ═══════════════════════════════════════════════════════════
       SPINNER
       ═══════════════════════════════════════════════════════════ */
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

    /* ═══════════════════════════════════════════════════════════
       ACCESSIBILITÉ
       ═══════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        .header-container, .form-container, .stepper, .form-field,
        .password-strength, .match-indicator {
            animation: none;
            opacity: 1;
            transform: none;
        }
        .btn-submit, .btn-next, .btn-prev, .btn-cancel { transition: none; }
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('multiStepForm', () => ({
            step: 1,
            init() {
                @if($errors->any())
                    const errorFields = @json(array_keys($errors->toArray()));
                    if (errorFields.some(f => ['name','sexe','telephone','date_naissance'].includes(f))) {
                        this.step = 1;
                    } else if (errorFields.some(f => ['email','password','password_confirmation','role','contact_id'].includes(f))) {
                        this.step = 2;
                    } else if (errorFields.some(f => ['fonction_id','section_id','adresse','photo'].includes(f))) {
                        this.step = 3;
                    }
                @endif
            },
            goToStep(target) {
                // ✅ Validation légère avant de changer d'étape
                if (target > this.step) {
                    if (!this.validateStep(this.step)) return;
                }
                this.step = target;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            validateStep(stepNum) {
                const stepEl = document.querySelector(`[x-show="step === ${stepNum}"]`);
                if (!stepEl) return true;

                const requiredFields = stepEl.querySelectorAll('[required]');
                let firstInvalid = null;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        if (!firstInvalid) firstInvalid = field;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                // ✅ Vérification spéciale pour l'étape 2 : mots de passe
                if (stepNum === 2) {
                    const pwd = document.getElementById('password');
                    const confirm = document.getElementById('password_confirmation');

                    if (pwd && confirm && pwd.value && confirm.value && pwd.value !== confirm.value) {
                        confirm.classList.add('is-invalid');
                        if (!firstInvalid) firstInvalid = confirm;
                        showToast('Les mots de passe ne correspondent pas.', 'error');
                    }
                }

                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
                return true;
            }
        }));
    });

    /* ============================================================
       ✅ TOAST (petit feedback)
    ============================================================ */
    function showToast(message, type = 'info') {
        let toast = document.getElementById('__toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = '__toast';
            toast.style.cssText = `
                position: fixed; top: 1.25rem; left: 50%; transform: translateX(-50%) translateY(-20px);
                background: #1e293b; color: #fff; padding: .75rem 1.25rem; border-radius: 10px;
                font-size: .88rem; font-weight: 600; z-index: 9999; box-shadow: 0 10px 30px rgba(0,0,0,.2);
                opacity: 0; transition: all .35s cubic-bezier(.16,1,.3,1); pointer-events: none;
                font-family: Inter, system-ui, sans-serif; max-width: 90vw;
            `;
            document.body.appendChild(toast);
        }
        toast.style.background = type === 'error' ? '#dc2626' : type === 'success' ? '#16a34a' : '#1e293b';
        toast.textContent = message;
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
        });
        clearTimeout(toast._t);
        toast._t = setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(-20px)';
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('userForm');
        const btn  = document.getElementById('submitBtn');

        /* ============================================================
           ✅ TOGGLE VISIBILITÉ MOT DE PASSE
        ============================================================ */
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const targetId = toggle.dataset.target;
                const input = document.getElementById(targetId);
                if (!input) return;

                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';

                const icon = toggle.querySelector('i');
                icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
                toggle.classList.toggle('is-visible', isHidden);
                toggle.setAttribute('aria-pressed', String(isHidden));
                toggle.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');

                // Garde le focus sur l'input pour une meilleure UX
                const cursorPos = input.value.length;
                input.focus();
                try { input.setSelectionRange(cursorPos, cursorPos); } catch(e) {}
            });
        });

        /* ============================================================
           ✅ FORCE DU MOT DE PASSE
        ============================================================ */
        const pwdInput    = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const strengthBox = document.getElementById('passwordStrength');
        const strengthTxt = document.getElementById('strengthText');
        const checksList  = document.getElementById('strengthChecks');
        const matchBox    = document.getElementById('matchIndicator');

        const LABELS = {
            0: '—',
            1: 'Très faible',
            2: 'Faible',
            3: 'Moyen',
            4: 'Fort',
        };

        function evaluatePassword(pwd) {
            const checks = {
                length:  pwd.length >= 8,
                lower:   /[a-z]/.test(pwd),
                upper:   /[A-Z]/.test(pwd),
                number:  /[0-9]/.test(pwd),
                special: /[^A-Za-z0-9]/.test(pwd),
            };
            const score = Object.values(checks).filter(Boolean).length;

            let level = 0;
            if (pwd.length === 0) level = 0;
            else if (score <= 2) level = 1;
            else if (score === 3) level = 2;
            else if (score === 4) level = 3;
            else level = 4;

            return { checks, score, level };
        }

        function updateStrength() {
            if (!pwdInput || !strengthBox) return;
            const pwd = pwdInput.value;

            if (!pwd) {
                strengthBox.hidden = true;
                return;
            }

            strengthBox.hidden = false;
            const { checks, level } = evaluatePassword(pwd);
            strengthBox.dataset.strength = String(level);
            if (strengthTxt) strengthTxt.textContent = LABELS[level];

            // Mise à jour de la checklist
            if (checksList) {
                checksList.querySelectorAll('li').forEach(li => {
                    const key = li.dataset.check;
                    const valid = !!checks[key];
                    li.classList.toggle('is-valid', valid);
                    const icon = li.querySelector('i');
                    if (icon) {
                        icon.className = valid
                            ? 'bi bi-check-circle-fill'
                            : 'bi bi-circle';
                    }
                });
            }
        }

        /* ============================================================
           ✅ CORRESPONDANCE MOTS DE PASSE
        ============================================================ */
        function updateMatch() {
            if (!pwdInput || !confirmInput || !matchBox) return;
            const pwd = pwdInput.value;
            const confirm = confirmInput.value;

            if (!pwd && !confirm) {
                matchBox.hidden = true;
                return;
            }

            matchBox.hidden = false;
            matchBox.classList.remove('is-match', 'is-mismatch', 'is-empty');
            const icon = matchBox.querySelector('.match-icon');
            const text = matchBox.querySelector('.match-text');

            if (!confirm) {
                matchBox.classList.add('is-empty');
                if (icon) icon.className = 'match-icon bi bi-circle';
                if (text) text.textContent = 'Confirmez le mot de passe';
                setSubmitState(false, true);
                return;
            }

            if (pwd === confirm) {
                matchBox.classList.add('is-match');
                if (icon) icon.className = 'match-icon bi bi-check-circle-fill';
                if (text) text.textContent = 'Les mots de passe correspondent';
                setSubmitState(true);
            } else {
                matchBox.classList.add('is-mismatch');
                if (icon) icon.className = 'match-icon bi bi-exclamation-circle-fill';
                if (text) text.textContent = 'Les mots de passe ne correspondent pas';
                setSubmitState(false);
            }
        }

        /* ============================================================
           ✅ ÉTAT DU BOUTON SUBMIT
        ============================================================ */
        let pwdMatchOK = true;
        function setSubmitState(ok, empty = false) {
            pwdMatchOK = ok || empty;
            const btn = document.getElementById('submitBtn');
            if (!btn) return;
            if (!pwdInput || !pwdInput.value || !confirmInput || !confirmInput.value) {
                btn.disabled = false;
                return;
            }
            btn.disabled = !ok;
            if (!ok) {
                btn.setAttribute('title', 'Les mots de passe ne correspondent pas');
            } else {
                btn.removeAttribute('title');
            }
        }

        if (pwdInput) {
            pwdInput.addEventListener('input', () => {
                updateStrength();
                updateMatch();
                pwdInput.classList.toggle('is-invalid', false);
            });
            pwdInput.addEventListener('blur', () => {
                if (pwdInput.value.length > 0 && pwdInput.value.length < 6) {
                    pwdInput.classList.add('is-invalid');
                }
            });
        }

        if (confirmInput) {
            confirmInput.addEventListener('input', () => {
                updateMatch();
                confirmInput.classList.toggle('is-invalid', false);
            });
        }

        // Initialise l'état si erreurs de validation serveur
        updateStrength();
        updateMatch();

        /* ============================================================
           ✅ SUBMIT : bloquer si mismatch
        ============================================================ */
        if (form && btn) {
            form.addEventListener('submit', (e) => {
                if (pwdInput && confirmInput && pwdInput.value && confirmInput.value) {
                    if (pwdInput.value !== confirmInput.value) {
                        e.preventDefault();
                        showToast('Les mots de passe ne correspondent pas.', 'error');
                        confirmInput.focus();
                        return;
                    }
                }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border me-2" aria-hidden="true"></span> Envoi...';
            });
        }

        /* ============================================================
           ✅ APERÇU DU FICHIER SÉLECTIONNÉ
        ============================================================ */
        const photoInput = document.getElementById('photo');
        const fileLabelSpan = photoInput?.parentElement?.querySelector('.file-label span');
        if (photoInput && fileLabelSpan) {
            photoInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                fileLabelSpan.textContent = file
                    ? (file.name.length > 30 ? file.name.substring(0, 27) + '...' : file.name)
                    : 'Choisir une image';
            });
        }

        /* ============================================================
           ✅ DRAG & DROP PHOTO
        ============================================================ */
        const dropZone = document.getElementById('dropZone');
        if (dropZone && photoInput) {
            ['dragenter', 'dragover'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropZone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(evt => {
                dropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('is-dragover');
                });
            });
            dropZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer?.files;
                if (files && files.length) {
                    photoInput.files = files;
                    photoInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
    });
</script>
@endsection