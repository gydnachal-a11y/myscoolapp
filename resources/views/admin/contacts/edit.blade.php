@extends('layouts.admin')

@section('page_title', 'Modifier l\'abonné')
@section('page_subtitle', $contact->nom)

@section('content')
@php
    $hasErrors = $errors->any();
@endphp

<div class="contact-form-page"
     x-data="contactFormEdit({
         contact: {
             id: {{ (int) $contact->id }},
             nom: {{ Js::from($contact->nom) }},
             email: {{ Js::from($contact->email) }},
             telephone: {{ Js::from($contact->telephone) }},
             est_responsable: {{ Js::from((bool) $contact->est_responsable) }},
         },
         old: {
             nom: {{ Js::from(old('nom')) }},
             email: {{ Js::from(old('email')) }},
             telephone: {{ Js::from(old('telephone')) }},
             est_responsable: {{ Js::from(old('est_responsable')) }},
         }
     })"
     x-init="init()">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-user-pen title-icon" aria-hidden="true"></i>
                <span>Modifier l'abonné</span>
                <span class="entity-badge">{{ $contact->nom }}</span>
            </h1>
            <p class="page-subtitle">Mettez à jour les informations du compte</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.contacts.show', $contact) }}" class="btn btn-ghost">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                <span>Voir la fiche</span>
            </a>
            <a href="{{ route('admin.contacts.index') }}"
               class="btn btn-ghost"
               @click="onCancelClick($event)">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- ERREURS --}}
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
    <form action="{{ route('admin.contacts.update', $contact) }}"
          method="POST"
          @submit="onSubmit($event)"
          novalidate>

        @csrf
        @method('PUT')

        <div class="form-layout">

            {{-- Colonne principale --}}
            <div class="form-main">

                {{-- Informations --}}
                <section class="form-card">
                    <header class="card-header">
                        <div class="card-icon">
                            <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                        </div>
                        <div class="card-header-text">
                            <h2 class="card-title">Informations de l'abonné</h2>
                            <p class="card-subtitle">Modifiez les coordonnées et l'identité</p>
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
                                       placeholder="Ex : jean.dupont@email.com"
                                       required
                                       autocomplete="email"
                                       class="form-input @error('email') is-invalid @enderror">
                            </div>
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
                                       placeholder="Ex : +243 81 234 5678"
                                       maxlength="20"
                                       autocomplete="tel"
                                       class="form-input @error('telephone') is-invalid @enderror">
                            </div>
                            @error('telephone')<p class="error-text">{{ $message }}</p>@enderror
                        </div>

                        {{-- Checkbox --}}
                        <div class="form-field">
                            <label class="checkbox-field" for="est_responsable">
                                <input type="checkbox"
                                       id="est_responsable"
                                       name="est_responsable"
                                       value="1"
                                       x-model="estResponsable"
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

                {{-- Sécurité --}}
                <section class="form-card">
                    <header class="card-header">
                        <div class="card-icon card-icon-security">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        </div>
                        <div class="card-header-text">
                            <h2 class="card-title">Sécurité du compte</h2>
                            <p class="card-subtitle">
                                Laissez vide pour conserver le mot de passe actuel
                            </p>
                        </div>
                    </header>

                    {{-- Alerte informative --}}
                    <div class="info-alert">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <span>
                            Ne remplissez les champs ci-dessous <strong>que si vous souhaitez
                            changer le mot de passe</strong>. Sinon, laissez-les vides.
                        </span>
                    </div>

                    <div class="form-grid">
                        {{-- Nouveau mot de passe --}}
                        <div class="form-field">
                            <label for="password" class="form-label">
                                Nouveau mot de passe <span class="optional">(optionnel)</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                                <input :type="showPassword ? 'text' : 'password'"
                                       id="password"
                                       name="password"
                                       x-model="password"
                                       placeholder="Minimum 8 caractères"
                                       minlength="8"
                                       autocomplete="new-password"
                                       class="form-input @error('password') is-invalid @enderror">
                                <button type="button"
                                        class="input-toggle"
                                        @click="showPassword = !showPassword"
                                        :aria-label="showPassword ? 'Masquer' : 'Afficher'">
                                    <i class="fa-solid"
                                       :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"
                                       aria-hidden="true"></i>
                                </button>
                            </div>

                            {{-- Strength --}}
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
                                Confirmation <span class="optional">(optionnel)</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock input-icon" aria-hidden="true"></i>
                                <input :type="showPassword ? 'text' : 'password'"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       x-model="passwordConfirmation"
                                       placeholder="Répétez le mot de passe"
                                       minlength="8"
                                       autocomplete="new-password"
                                       class="form-input">
                                <span class="input-status"
                                      x-show="passwordConfirmation.length > 0"
                                      x-cloak
                                      :class="passwordsMatch ? 'is-success' : 'is-error'">
                                    <i class="fa-solid"
                                       :class="passwordsMatch ? 'fa-circle-check' : 'fa-circle-xmark'"
                                       aria-hidden="true"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Actions --}}
                <footer class="form-actions">
                    <a href="{{ route('admin.contacts.show', $contact) }}"
                       class="btn btn-ghost"
                       @click="onCancelClick($event)">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        <span>Annuler</span>
                    </a>
                    <button type="submit"
                            class="btn btn-primary"
                            :disabled="submitting || !hasChanges"
                            :aria-busy="submitting">
                        <template x-if="!submitting">
                            <span class="btn-content">
                                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                                <span>Enregistrer les modifications</span>
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="btn-content">
                                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                                <span>Enregistrement…</span>
                            </span>
                        </template>
                    </button>
                </footer>
            </div>

            {{-- Aside --}}
            <aside class="form-aside">

                {{-- Preview --}}
                <div class="preview-card">
                    <div class="preview-avatar" x-text="initials"></div>
                    <div class="preview-info">
                        <p class="preview-name" x-text="nom || 'Sans nom'"></p>
                        <p class="preview-subtitle" x-text="email || '—'"></p>
                        <template x-if="estResponsable">
                            <span class="preview-badge">
                                <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                                Responsable
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="info-card">
                    <header class="info-header">
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                        <h3>Historique</h3>
                    </header>
                    <ul class="info-list info-list-meta">
                        <li>
                            <span class="meta-label">Créé le</span>
                            <span class="meta-value">
                                {{ $contact->created_at->format('d/m/Y à H:i') }}
                            </span>
                        </li>
                        @if($contact->updated_at && $contact->updated_at->ne($contact->created_at))
                            <li>
                                <span class="meta-label">Modifié le</span>
                                <span class="meta-value">
                                    {{ $contact->updated_at->format('d/m/Y à H:i') }}
                                </span>
                            </li>
                        @endif
                        <li>
                            <span class="meta-label">ID</span>
                            <span class="meta-value">#{{ $contact->id }}</span>
                        </li>
                    </ul>
                </div>

                {{-- Danger zone --}}
                @if(!$contact->est_responsable || true)
                    <div class="danger-card">
                        <header class="danger-header">
                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                            <h3>Zone sensible</h3>
                        </header>
                        <p class="danger-text">
                            La suppression est définitive et supprimera toutes les données associées.
                        </p>
                        <button type="button"
                                class="btn btn-danger"
                                onclick="if (confirm('Supprimer définitivement l\'abonné « {{ addslashes($contact->nom) }} » ?\n\nCette action est irréversible.')) { document.getElementById('delete-contact-form').submit(); }">
                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                            <span>Supprimer l'abonné</span>
                        </button>
                    </div>
                @endif

            </aside>
        </div>
    </form>

    {{-- Formulaire de suppression séparé --}}
    <form id="delete-contact-form"
          action="{{ route('admin.contacts.destroy', $contact) }}"
          method="POST"
          style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('styles')
@include('admin.contacts._form-styles')
<style>
    /* ════════════════════════════════════════════════════════
       SPÉCIFIQUE À L'ÉDITION
       ════════════════════════════════════════════════════════ */
    .contact-form-page .entity-badge {
        display: inline-block;
        font-size: 0.75rem; font-weight: 600;
        color: #4338ca;
        background: var(--c-primary-soft);
        padding: 0.25rem 0.7rem;
        border-radius: 9999px;
        max-width: 100%;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        vertical-align: middle;
        margin-left: 0.35rem;
    }

    .contact-form-page .info-alert {
        display: flex; align-items: flex-start; gap: 0.6rem;
        padding: 0.85rem 1rem;
        background: var(--c-amber-soft);
        border-left: 3px solid var(--c-amber);
        border-radius: 8px;
        font-size: 0.82rem; color: #92400e;
        line-height: 1.5;
        margin-bottom: 1.15rem;
    }
    .contact-form-page .info-alert i {
        color: var(--c-amber); flex-shrink: 0;
        margin-top: 2px;
    }
    .contact-form-page .info-alert strong {
        color: #78350f;
    }

    /* Meta list */
    .contact-form-page .info-list-meta li {
        display: flex; justify-content: space-between; gap: 0.5rem;
        font-size: 0.8rem;
    }
    .contact-form-page .info-list-meta .meta-label {
        color: var(--c-slate-500);
    }
    .contact-form-page .info-list-meta .meta-value {
        color: var(--c-slate-800);
        font-weight: 600;
        text-align: right;
    }

    /* Danger */
    .contact-form-page .danger-card {
        background: var(--c-rose-soft);
        border: 1.5px solid #fecaca;
        border-radius: var(--radius-lg);
        padding: 1.15rem;
    }
    .contact-form-page .danger-header {
        display: flex; align-items: center; gap: 0.5rem;
        margin-bottom: 0.6rem;
    }
    .contact-form-page .danger-header i {
        color: var(--c-rose); font-size: 0.95rem;
    }
    .contact-form-page .danger-header h3 {
        font-size: 0.9rem; font-weight: 700;
        color: #7f1d1d; margin: 0;
    }
    .contact-form-page .danger-text {
        font-size: 0.78rem; color: #991b1b;
        line-height: 1.5; margin: 0 0 0.85rem;
    }
    .contact-form-page .btn-danger {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.65rem 1rem;
        background: var(--c-rose);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 0.82rem; font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: all var(--t);
        min-height: 40px;
    }
    .contact-form-page .btn-danger:hover {
        background: #b91c1c;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220,38,38,0.3);
    }

    @media (max-width: 1024px) {
        .contact-form-page .form-aside {
            position: static;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function contactFormEdit(config = {}) {
        const original = {
            nom: config.contact?.nom || '',
            email: config.contact?.email || '',
            telephone: config.contact?.telephone || '',
            est_responsable: config.contact?.est_responsable || false,
        };

        return {
            // Champs
            nom: config.old?.nom ?? original.nom,
            email: config.old?.email ?? original.email,
            telephone: config.old?.telephone ?? original.telephone,
            estResponsable: config.old?.est_responsable ?? original.est_responsable,
            password: '',
            passwordConfirmation: '',
            showPassword: false,

            // UI
            submitting: false,

            // Snapshot pour hasChanges
            initialSnapshot: '',

            init() {
                this.initialSnapshot = this.snapshot();

                window.addEventListener('pageshow', () => { this.submitting = false; });
                window.addEventListener('beforeunload', (e) => {
                    if (this.hasChanges && !this.submitting) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });
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
                if (pwd.length >= 8)          score++;
                if (pwd.length >= 12)         score++;
                if (/[a-z]/.test(pwd))        score++;
                if (/[A-Z]/.test(pwd))        score++;
                if (/[0-9]/.test(pwd))        score++;
                if (/[^a-zA-Z0-9]/.test(pwd)) score++;

                if (score <= 2) return { level: 'weak',   label: 'Faible', percent: 33 };
                if (score <= 4) return { level: 'medium', label: 'Moyen',  percent: 66 };
                return              { level: 'strong', label: 'Fort',   percent: 100 };
            },

            get hasChanges() {
                // Nouveau mot de passe = changement
                if (this.password.length > 0) return true;

                return this.snapshot() !== this.initialSnapshot;
            },

            snapshot() {
                return JSON.stringify({
                    nom: this.nom,
                    email: this.email,
                    telephone: this.telephone,
                    resp: !!this.estResponsable,
                });
            },

            onSubmit(event) {
                if (this.submitting) {
                    event.preventDefault();
                    return;
                }
                if (this.password.length > 0 && !this.passwordsMatch) {
                    event.preventDefault();
                    alert('Les mots de passe ne correspondent pas.');
                    return;
                }
                this.submitting = true;
            },

            onCancelClick(event) {
                if (this.hasChanges && !this.submitting) {
                    if (!confirm('Vous avez des modifications non enregistrées. Quitter ?')) {
                        event.preventDefault();
                    }
                }
            },
        };
    }
</script>
@endpush