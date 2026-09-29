@extends('layouts.admin')

@section('page_title', 'Nouvel abonné')
@section('page_subtitle', 'Créez un compte d\'abonné')

@section('content')
@php
    $hasErrors = $errors->any();
@endphp

<div class="contact-form-page"
     x-data="contactForm({
         old: {
             nom: {{ Js::from(old('nom')) }},
             email: {{ Js::from(old('email')) }},
             telephone: {{ Js::from(old('telephone')) }},
             est_responsable: {{ Js::from((bool) old('est_responsable')) }},
         }
     })"
     x-init="init()">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-user-plus title-icon" aria-hidden="true"></i>
                <span>Nouvel abonné</span>
            </h1>
            <p class="page-subtitle">Ajoutez un nouveau compte d'abonné (parent/responsable)</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.contacts.index') }}"
               class="btn btn-ghost"
               @click="onCancelClick($event)">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour aux abonnés</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- ERREURS GLOBALES --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    @if($hasErrors)
        <div class="alert alert-error" role="alert">
            <i class="fa-solid fa-circle-exclamation alert-icon" aria-hidden="true"></i>
            <div class="alert-body">
                <strong>{{ $errors->count() }} erreur(s) détectée(s)</strong>
                <ul class="alert-list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FORMULAIRE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <form action="{{ route('admin.contacts.store') }}"
          method="POST"
          @submit="onSubmit($event)"
          novalidate>

        @csrf

        <div class="form-layout">

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- COLONNE PRINCIPALE --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-main">

                {{-- Carte : Informations --}}
                <section class="form-card">
                    <header class="card-header">
                        <div class="card-icon">
                            <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                        </div>
                        <div class="card-header-text">
                            <h2 class="card-title">Informations de l'abonné</h2>
                            <p class="card-subtitle">Renseignez les coordonnées et l'identité</p>
                        </div>
                    </header>

                    <div class="form-grid">
                        {{-- Nom --}}
                        <div class="form-field form-field-full">
                            <label for="nom" class="form-label">
                                Nom complet <span class="req">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-user input-icon" aria-hidden="true"></i>
                                <input type="text"
                                       id="nom"
                                       name="nom"
                                       x-model="nom"
                                       value="{{ old('nom') }}"
                                       placeholder="Ex : Jean Dupont"
                                       required
                                       autofocus
                                       autocomplete="name"
                                       class="form-input @error('nom') is-invalid @enderror">
                            </div>
                            @error('nom')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-field form-field-full">
                            <label for="email" class="form-label">
                                Adresse email <span class="req">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-envelope input-icon" aria-hidden="true"></i>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       x-model="email"
                                       value="{{ old('email') }}"
                                       placeholder="Ex : jean.dupont@email.com"
                                       required
                                       autocomplete="email"
                                       class="form-input @error('email') is-invalid @enderror">
                            </div>
                            <p class="form-help">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                Cette adresse servira à la connexion à l'espace abonné
                            </p>
                            @error('email')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        {{-- Téléphone --}}
                        <div class="form-field">
                            <label for="telephone" class="form-label">
                                Téléphone <span class="optional">(optionnel)</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-phone input-icon" aria-hidden="true"></i>
                                <input type="tel"
                                       id="telephone"
                                       name="telephone"
                                       x-model="telephone"
                                       value="{{ old('telephone') }}"
                                       placeholder="Ex : +243 81 234 5678"
                                       maxlength="20"
                                       autocomplete="tel"
                                       class="form-input @error('telephone') is-invalid @enderror">
                            </div>
                            @error('telephone')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        {{-- Checkbox responsable --}}
                        <div class="form-field">
                            <label class="checkbox-field" for="est_responsable">
                                <input type="checkbox"
                                       id="est_responsable"
                                       name="est_responsable"
                                       value="1"
                                       x-model="estResponsable"
                                       {{ old('est_responsable') ? 'checked' : '' }}
                                       class="checkbox-input">
                                <span class="checkbox-mark" aria-hidden="true">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                                <span class="checkbox-text">
                                    <strong>Parent / Responsable légal</strong>
                                    <span>Cochez si cet abonné est le parent ou tuteur d'un élève</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </section>

                {{-- Carte : Sécurité --}}
                <section class="form-card">
                    <header class="card-header">
                        <div class="card-icon card-icon-security">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        </div>
                        <div class="card-header-text">
                            <h2 class="card-title">Sécurité du compte</h2>
                            <p class="card-subtitle">Définissez un mot de passe sécurisé</p>
                        </div>
                    </header>

                    <div class="form-grid">
                        {{-- Mot de passe --}}
                        <div class="form-field">
                            <label for="password" class="form-label">
                                Mot de passe <span class="req">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                                <input :type="showPassword ? 'text' : 'password'"
                                       id="password"
                                       name="password"
                                       x-model="password"
                                       placeholder="Minimum 8 caractères"
                                       required
                                       minlength="8"
                                       autocomplete="new-password"
                                       class="form-input @error('password') is-invalid @enderror">
                                <button type="button"
                                        class="input-toggle"
                                        @click="showPassword = !showPassword"
                                        :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                                    <i class="fa-solid"
                                       :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"
                                       aria-hidden="true"></i>
                                </button>
                            </div>

                            {{-- Indicateur de force --}}
                            <div class="password-strength"
                                 x-show="password.length > 0"
                                 x-cloak>
                                <div class="strength-bar">
                                    <div class="strength-fill"
                                         :class="`strength-${passwordStrength.level}`"
                                         :style="`width: ${passwordStrength.percent}%`"></div>
                                </div>
                                <span class="strength-label"
                                      :class="`strength-text-${passwordStrength.level}`"
                                      x-text="passwordStrength.label"></span>
                            </div>

                            @error('password')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        {{-- Confirmation --}}
                        <div class="form-field">
                            <label for="password_confirmation" class="form-label">
                                Confirmation <span class="req">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                                <input :type="showPassword ? 'text' : 'password'"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       x-model="passwordConfirmation"
                                       placeholder="Répétez le mot de passe"
                                       required
                                       minlength="8"
                                       autocomplete="new-password"
                                       class="form-input @error('password_confirmation') is-invalid @enderror">
                                <span class="input-status"
                                      x-show="passwordConfirmation.length > 0"
                                      x-cloak
                                      :class="passwordsMatch ? 'is-success' : 'is-error'">
                                    <i class="fa-solid"
                                       :class="passwordsMatch ? 'fa-circle-check' : 'fa-circle-xmark'"
                                       aria-hidden="true"></i>
                                </span>
                            </div>
                            @error('password_confirmation')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Actions --}}
                <footer class="form-actions">
                    <a href="{{ route('admin.contacts.index') }}"
                       class="btn btn-ghost"
                       @click="onCancelClick($event)">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        <span>Annuler</span>
                    </a>
                    <button type="submit"
                            class="btn btn-primary"
                            :disabled="submitting"
                            :aria-busy="submitting">
                        <template x-if="!submitting">
                            <span class="btn-content">
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                                <span>Créer l'abonné</span>
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="btn-content">
                                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                                <span>Création…</span>
                            </span>
                        </template>
                    </button>
                </footer>
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- COLONNE LATÉRALE --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <aside class="form-aside">

                {{-- Preview card --}}
                <div class="preview-card">
                    <div class="preview-avatar"
                         x-text="initials"></div>
                    <div class="preview-info">
                        <p class="preview-name"
                           x-text="nom || 'Nouvel abonné'"></p>
                        <p class="preview-subtitle"
                           x-text="email || 'En attente de l\'email'"></p>
                        <template x-if="estResponsable">
                            <span class="preview-badge">
                                <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                                Responsable
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Carte règles --}}
                <div class="info-card">
                    <header class="info-header">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <h3>Règles de création</h3>
                    </header>
                    <ul class="info-list">
                        <li>
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <span>Le mot de passe doit contenir <strong>au moins 8 caractères</strong>.</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <span>L'email doit être <strong>unique</strong> dans le système.</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <span>Le téléphone est optionnel mais doit être <strong>unique</strong> s'il est fourni.</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <span>Un email de bienvenue peut être envoyé automatiquement.</span>
                        </li>
                    </ul>
                </div>

            </aside>
        </div>
    </form>
</div>
@endsection

@push('styles')
@include('admin.contacts._form-styles')
@endpush

@push('scripts')
<script>
    function contactForm(config = {}) {
        return {
            // Champs (bindés)
            nom: config.old?.nom || '',
            email: config.old?.email || '',
            telephone: config.old?.telephone || '',
            estResponsable: config.old?.est_responsable || false,
            password: '',
            passwordConfirmation: '',
            showPassword: false,

            // UI
            submitting: false,

            init() {
                // Sync avec les vraies inputs HTML (name="...")
                this.$watch('nom', v => this.syncInput('nom', v));
                this.$watch('email', v => this.syncInput('email', v));
                this.$watch('telephone', v => this.syncInput('telephone', v));

                window.addEventListener('pageshow', () => { this.submitting = false; });
            },

            syncInput(name, value) {
                // L'input porte déjà name="xxx" et x-model, donc la synchro est faite.
                // Cette méthode existe pour permettre des hooks futurs.
            },

            get initials() {
                const source = (this.nom || '?').trim();
                if (!source) return '?';
                return source
                    .split(' ')
                    .filter(Boolean)
                    .slice(0, 2)
                    .map(w => w[0].toUpperCase())
                    .join('');
            },

            get passwordsMatch() {
                return this.password.length > 0
                    && this.password === this.passwordConfirmation;
            },

            get passwordStrength() {
                const pwd = this.password || '';
                let score = 0;

                if (pwd.length >= 8)        score++;
                if (pwd.length >= 12)       score++;
                if (/[a-z]/.test(pwd))      score++;
                if (/[A-Z]/.test(pwd))      score++;
                if (/[0-9]/.test(pwd))      score++;
                if (/[^a-zA-Z0-9]/.test(pwd)) score++;

                if (score <= 2) return { level: 'weak',   label: 'Faible',   percent: 33 };
                if (score <= 4) return { level: 'medium', label: 'Moyen',    percent: 66 };
                return              { level: 'strong', label: 'Fort',     percent: 100 };
            },

            onSubmit(event) {
                if (this.submitting) {
                    event.preventDefault();
                    return;
                }
                if (this.password !== this.passwordConfirmation) {
                    event.preventDefault();
                    alert('Les mots de passe ne correspondent pas.');
                    return;
                }
                this.submitting = true;
            },

            onCancelClick(event) {
                // Pas besoin de confirm si rien n'a été saisi
                const hasContent = this.nom || this.email || this.telephone || this.password;
                if (hasContent && !this.submitting) {
                    if (!confirm('Vous avez des informations non enregistrées. Quitter ?')) {
                        event.preventDefault();
                    }
                }
            },
        };
    }
</script>
@endpush