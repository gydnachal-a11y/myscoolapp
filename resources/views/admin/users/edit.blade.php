@extends('layouts.admin')

@section('page_title', 'Modifier le personnel')
@section('page_subtitle', 'Modifier les informations d\'un membre du personnel')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8" x-data="multiStepForm()" x-init="init()">

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="flash flash-success">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- En-tête --}}
    <div class="header-container">
        <div>
            <h1 class="form-title">Modifier <strong>{{ $user->name }}</strong></h1>
            <p class="form-subtitle">Modifier les informations du personnel</p>
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

    <form action="{{ route('admin.users.update', $user) }}"
          method="POST"
          enctype="multipart/form-data"
          class="form-container"
          novalidate
          id="userForm">
        @csrf
        @method('PUT')

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
                                       value="{{ old('name', $user->name) }}"
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
                                    <option value="M" @selected(old('sexe', $user->sexe) === 'M')>Masculin</option>
                                    <option value="F" @selected(old('sexe', $user->sexe) === 'F')>Féminin</option>
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
                                       value="{{ old('telephone', $user->telephone) }}"
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
                                       value="{{ old('date_naissance', $user->date_naissance?->format('Y-m-d')) }}"
                                       placeholder=" " max="{{ now()->format('Y-m-d') }}"
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
                <button type="button" class="btn-next" @click="step = 2">
                    Suivant <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════
             ÉTAPE 2 — COMPTE
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
                                       value="{{ old('email', $user->email) }}"
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
                                                @selected(old('role', $user->role) === $role->name)>
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
                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="password" name="password" id="password"
                                       placeholder=" " minlength="6"
                                       autocomplete="new-password"
                                       class="@error('password') is-invalid @enderror">
                                <label for="password" class="float-label">
                                    Nouveau mot de passe (optionnel)
                                </label>
                                <i class="bi bi-lock icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('password')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-field">
                            <div class="input-wrap">
                                <input type="password" name="password_confirmation"
                                       id="password_confirmation"
                                       placeholder=" "
                                       autocomplete="new-password"
                                       class="@error('password_confirmation') is-invalid @enderror">
                                <label for="password_confirmation" class="float-label">
                                    Confirmer le mot de passe
                                </label>
                                <i class="bi bi-lock icon" aria-hidden="true"></i>
                                <div class="line-focus"></div>
                            </div>
                            @error('password_confirmation')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- ✅ NOUVEAU — Lien avec un compte abonné --}}
                    @if(isset($contacts) && $contacts->isNotEmpty())
                        <div class="form-row full-width">
                            <div class="form-field">
                                <div class="link-contact-wrapper {{ $user->contact_id ? 'has-linked' : '' }}">
                                    <div class="link-contact-header">
                                        <div class="link-contact-icon" aria-hidden="true">
                                            <i class="bi bi-link-45deg"></i>
                                        </div>
                                        <div class="link-contact-text">
                                            <label for="contact_id" class="link-contact-label">
                                                Compte abonné lié
                                                @if($user->contact_id)
                                                    <span class="linked-badge">
                                                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                                        Lié
                                                    </span>
                                                @else
                                                    <span class="optional-badge">Optionnel</span>
                                                @endif
                                            </label>
                                            <p class="link-contact-hint">
                                                @if($user->contact_id)
                                                    Ce personnel est lié à un compte abonné.
                                                    Il peut basculer entre son <strong>espace staff</strong>
                                                    et son <strong>espace abonné</strong> en 1 clic.
                                                @else
                                                    Liez cet utilisateur à un compte abonné (parent, tuteur).
                                                    Il pourra basculer entre son <strong>espace staff</strong>
                                                    et son <strong>espace abonné</strong> en 1 clic.
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="select-wrap mt-3">
                                        <select name="contact_id" id="contact_id"
                                                class="@error('contact_id') is-invalid @enderror">
                                            <option value="">— Aucun compte abonné lié —</option>
                                            @foreach($contacts as $c)
                                                <option value="{{ $c->id }}"
                                                        @selected(old('contact_id', $user->contact_id) == $c->id)>
                                                    {{ $c->nom }} — {{ $c->email }}
                                                    @if($user->contact_id == $c->id)
                                                        (actuellement lié)
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <i class="bi bi-chevron-down select-icon" aria-hidden="true"></i>
                                        <div class="line-focus"></div>
                                    </div>
                                    @error('contact_id')<p class="error-text">{{ $message }}</p>@enderror

                                    @if($user->contact_id)
                                        <div class="current-link-info">
                                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                                            <span>
                                                Actuellement lié à
                                                <strong>{{ $user->contact?->nom ?? 'Contact #' . $user->contact_id }}</strong>.
                                                Choisissez « Aucun » pour délier.
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex justify-between items-center mt-4 step-actions">
                <button type="button" class="btn-prev" @click="step = 1">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Précédent
                </button>
                <button type="button" class="btn-next" @click="step = 3">
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
                                                @selected(old('fonction_id', $user->fonction_id) == $fonction->id)>
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
                                                @selected(old('section_id', $user->section_id) == $section->id)>
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
                                          class="@error('adresse') is-invalid @enderror">{{ old('adresse', $user->adresse) }}</textarea>
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
                            <div class="file-upload-wrap">
                                <input type="file" name="photo" id="photo"
                                       accept="image/jpeg,image/png,image/jpg,image/webp"
                                       class="file-input">
                                <label for="photo" class="file-label">
                                    <i class="bi bi-cloud-upload" aria-hidden="true"></i>
                                    <span>Choisir une image</span>
                                    <small>JPG, PNG, WebP — max 2 Mo</small>
                                </label>
                            </div>
                            @error('photo')<p class="error-text">{{ $message }}</p>@enderror

                            @if($user->photo)
                                <div class="current-photo">
                                    <img src="{{ $user->photo_url }}"
                                         alt="Photo actuelle de {{ $user->name }}"
                                         class="current-photo-img">
                                    <div class="current-photo-meta">
                                        <p class="current-photo-name">
                                            <i class="bi bi-image" aria-hidden="true"></i>
                                            Photo actuelle
                                        </p>
                                        <p class="current-photo-hint">
                                            Sélectionnez un nouveau fichier pour la remplacer.
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-between items-center mt-4 step-actions">
                <button type="button" class="btn-prev" @click="step = 2">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Précédent
                </button>
                <button type="submit" class="btn-submit" id="submitBtn">
                    Mettre à jour <i class="bi bi-arrow-right" aria-hidden="true"></i>
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

    .input-wrap input:focus ~ i.icon,
    .textarea-wrap textarea:focus ~ i.icon {
        color: #667eea;
        transform: translateY(-50%) scale(1.1);
    }
    .textarea-wrap textarea:focus ~ i.icon { transform: scale(1.1); }
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
       LINK CONTACT WRAPPER
       ═══════════════════════════════════════════════════════════ */
    .link-contact-wrapper {
        padding: 1.25rem;
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        border: 1px solid #c7d2fe;
        border-radius: 14px;
        transition: all 0.3s;
    }

    /* Variante quand un contact est déjà lié → couleur émeraude */
    .link-contact-wrapper.has-linked {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        border-color: #a7f3d0;
    }
    .link-contact-wrapper.has-linked .link-contact-icon {
        background: rgba(16, 185, 129, 0.15);
        color: #059669;
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

    .linked-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.5rem;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #a7f3d0;
        color: #059669;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-radius: 999px;
    }
    .linked-badge i { font-size: 0.7rem; }

    .link-contact-hint {
        font-size: 0.82rem;
        color: #475569;
        line-height: 1.55;
        margin: 0;
    }
    .link-contact-hint strong { color: #1e293b; }

    .current-link-info {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-top: 0.85rem;
        padding: 0.65rem 0.85rem;
        background: rgba(255, 255, 255, 0.7);
        border-radius: 10px;
        font-size: 0.8rem;
        color: #047857;
        line-height: 1.5;
    }
    .current-link-info i {
        margin-top: 0.15rem;
        flex-shrink: 0;
        font-size: 0.9rem;
    }
    .current-link-info strong { color: #065f46; }

    /* ═══════════════════════════════════════════════════════════
       FILE UPLOAD & CURRENT PHOTO
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
        transition: all 0.3s;
        font-size: 0.9rem;
    }
    .file-label small {
        display: block;
        font-size: 0.72rem;
        color: #94a3b8;
        font-weight: 400;
        margin-top: 2px;
    }
    .file-input:hover + .file-label {
        border-color: #667eea;
        color: #667eea;
        background: #f1f5f9;
    }

    .current-photo {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
        padding: 0.75rem;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        flex-wrap: wrap;
    }

    .current-photo-img {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
        flex-shrink: 0;
    }

    .current-photo-meta { flex: 1; min-width: 0; }

    .current-photo-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .current-photo-hint {
        font-size: 0.75rem;
        color: #94a3b8;
        margin: 0.2rem 0 0;
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
    .btn-next:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    .btn-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
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
       RESPONSIVE — TABLETTE (≤ 992px)
       ═══════════════════════════════════════════════════════════ */
    @media (max-width: 992px) {
        .form-container { padding: 2rem; }
    }

    /* ═══════════════════════════════════════════════════════════
       RESPONSIVE — MOBILE (≤ 768px)
       ═══════════════════════════════════════════════════════════ */
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

        /* Anti-zoom iOS */
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
        .step-actions .btn-prev {
            width: 100%;
        }

        .file-label {
            width: 100%;
            justify-content: center;
        }
    }

    /* ═══════════════════════════════════════════════════════════
       RESPONSIVE — PETIT MOBILE (≤ 480px)
       ═══════════════════════════════════════════════════════════ */
    @media (max-width: 480px) {
        .form-container { padding: 1.15rem; }
        .form-title { font-size: 1.2rem; }
        .step-circle { width: 28px; height: 28px; font-size: 0.75rem; }
        .step-label { font-size: 0.7rem; }
        .link-contact-wrapper { padding: 1rem; }
        .link-contact-icon { width: 32px; height: 32px; font-size: 0.95rem; }
        .link-contact-label { font-size: 0.85rem; }
        .link-contact-hint { font-size: 0.78rem; }
        .current-photo { flex-direction: column; align-items: flex-start; }
        .current-photo-img { width: 56px; height: 56px; }
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
        .header-container,
        .form-container,
        .stepper,
        .form-field {
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
            }
        }));
    });

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('userForm');
        const btn = document.getElementById('submitBtn');

        if (form && btn) {
            form.addEventListener('submit', () => {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border me-2" aria-hidden="true"></span> Envoi...';
            });
        }

        // Aperçu du nom de fichier sélectionné
        const photoInput = document.getElementById('photo');
        const fileLabel = photoInput?.parentElement?.querySelector('.file-label span');
        if (photoInput && fileLabel) {
            photoInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                fileLabel.textContent = file
                    ? (file.name.length > 30 ? file.name.substring(0, 27) + '...' : file.name)
                    : 'Choisir une image';
            });
        }
    });
</script>
@endsection