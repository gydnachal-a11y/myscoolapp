@extends('layouts.admin')

@section('page_title', 'Nouvelle permission')
@section('page_subtitle', 'Créez une permission à partir d\'une ressource et d\'une action')

@section('content')
@php
    // ============================================================
    // Préparation des données (côté serveur, une seule fois)
    // ============================================================
    $permissionsByResource = $permissionsByResource ?? [];
    $allPermissions        = $allPermissions        ?? [];

    // Actions standard pour le bulk-create (configurable ici)
    $standardActions = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    // Actions disponibles pour la ressource sélectionnée
    $actionsByResource = [];
    foreach ($permissionsByResource as $resource => $perms) {
        // Ici on n'a que les actions existantes — on complète avec les standard
        $actionsByResource[$resource] = collect($standardActions)
            ->merge($perms)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
@endphp

<div class="permission-create-page"
     x-data="permissionForm({
         resources: {{ Js::from($resources) }},
         actionsByResource: {{ Js::from($actionsByResource) }},
         permissionsByResource: {{ Js::from($permissionsByResource) }},
         allPermissions: {{ Js::from($allPermissions) }},
         standardActions: {{ Js::from($standardActions) }},
         oldValues: {
             resource: {{ Js::from(old('resource')) }},
             action: {{ Js::from(old('action')) }},
             name: {{ Js::from(old('name')) }},
             label: {{ Js::from(old('label')) }},
             description: {{ Js::from(old('description')) }}
         },
         bulkCreateUrl: {{ Js::from(route('admin.permissions.bulk-create')) }},
         csrfToken: {{ Js::from(csrf_token()) }}
     })"
     x-init="init()">

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- HEADER --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div class="page-header-text">
            <h1 class="page-title">
                <i class="fa-solid fa-plus-circle title-icon" aria-hidden="true"></i>
                <span>Nouvelle permission</span>
            </h1>
            <p class="page-subtitle">Créez une permission à partir des ressources disponibles</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.permissions.index') }}"
               class="btn btn-ghost"
               @click="onCancelClick($event)">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Retour</span>
            </a>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════ --}}
    {{-- FORMULAIRE --}}
    {{-- ════════════════════════════════════════════════════════ --}}
    <form action="{{ route('admin.permissions.store') }}"
          method="POST"
          @submit="onSubmit($event)"
          class="form-card"
          novalidate>

        @csrf

        <div class="form-grid">

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- RESSOURCE --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field">
                <label for="resource" class="form-label">
                    Ressource <span class="req">*</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-folder input-icon" aria-hidden="true"></i>
                    <select id="resource"
                            name="resource"
                            x-model="selectedResource"
                            @change="onResourceChange()"
                            required
                            class="form-select @error('resource') is-invalid @enderror">
                        <option value="">— Sélectionner une ressource —</option>
                        <template x-for="res in resources" :key="res">
                            <option :value="res" x-text="prettyResource(res)"></option>
                        </template>
                    </select>
                </div>

                <p class="form-help">
                    <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                    Sélectionnez la ressource concernée (ex : paiements, notes, users)
                </p>

                @error('resource')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- ACTION --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field"
                 x-show="selectedResource"
                 x-transition:enter="field-enter"
                 x-transition:enter-start="field-enter-start"
                 x-transition:enter-end="field-enter-end"
                 x-cloak>
                <label for="action" class="form-label">
                    Action <span class="req">*</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-arrow-right input-icon" aria-hidden="true"></i>
                    <select id="action"
                            name="action"
                            x-model="selectedAction"
                            @change="onActionChange()"
                            required
                            class="form-select @error('action') is-invalid @enderror">
                        <option value="">— Sélectionner une action —</option>
                        <template x-for="act in actions" :key="act">
                            <option :value="act" x-text="act"></option>
                        </template>
                    </select>
                </div>

                <p class="form-help">
                    <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                    Actions disponibles pour la ressource sélectionnée
                </p>

                @error('action')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- NOM (auto-généré) --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field">
                <label for="name" class="form-label">
                    Nom technique <span class="req">*</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-tag input-icon" aria-hidden="true"></i>
                    <input type="text"
                           id="name"
                           name="name"
                           x-model="permissionName"
                           readonly
                           placeholder="ressource.action"
                           class="form-input is-readonly"
                           :class="{ 'is-valid': permissionName && !permissionExists,
                                     'is-invalid': permissionExists }">
                </div>

                {{-- Info : auto-généré --}}
                <p class="form-help">
                    <i class="fa-solid fa-robot" aria-hidden="true"></i>
                    Généré automatiquement à partir de la ressource et de l'action
                </p>

                {{-- Alerte : existe déjà --}}
                <p x-show="permissionExists"
                   x-cloak
                   class="form-help form-help-warn">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    Cette permission existe déjà. Choisissez une autre action.
                </p>

                @error('name')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- LIBELLÉ --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field">
                <label for="label" class="form-label">
                    Libellé
                    <span class="optional">(optionnel)</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-pen-to-square input-icon" aria-hidden="true"></i>
                    <input type="text"
                           id="label"
                           name="label"
                           x-model="label"
                           maxlength="255"
                           autocomplete="off"
                           placeholder="Ex : Voir les paiements"
                           class="form-input @error('label') is-invalid @enderror">
                </div>

                <p class="form-help">
                    <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                    Un libellé lisible pour cette permission
                </p>

                @error('label')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- DESCRIPTION --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="form-field">
                <label for="description" class="form-label">
                    Description
                    <span class="optional">(optionnel)</span>
                </label>

                <div class="input-wrapper">
                    <i class="fa-regular fa-comment input-icon input-icon-top" aria-hidden="true"></i>
                    <textarea id="description"
                              name="description"
                              x-model="description"
                              rows="2"
                              maxlength="500"
                              placeholder="Description détaillée de la permission…"
                              class="form-textarea @error('description') is-invalid @enderror"></textarea>
                </div>

                @error('description')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- APERÇU --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="preview-card"
                 x-show="permissionName"
                 x-cloak>
                <div class="preview-icon">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                </div>
                <div class="preview-content">
                    <p class="preview-title">Aperçu de la permission</p>
                    <code class="preview-name" x-text="permissionName"></code>
                    <p class="preview-meta">
                        <span x-text="'Ressource : ' + (selectedResource || '—')"></span>
                        <span class="dot">·</span>
                        <span x-text="'Action : ' + (selectedAction || '—')"></span>
                    </p>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════ --}}
            {{-- PERMISSIONS EXISTANTES + BULK CREATE --}}
            {{-- ════════════════════════════════════════════════════ --}}
            <div class="bulk-card"
                 x-show="selectedResource && existingPermissions.length > 0"
                 x-cloak>
                <header class="bulk-header">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span>Actions existantes pour <strong x-text="prettyResource(selectedResource)"></strong></span>
                </header>

                <div class="bulk-pills">
                    <template x-for="perm in existingPermissions" :key="perm">
                        <span class="bulk-pill">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            <span x-text="perm"></span>
                        </span>
                    </template>
                </div>

                <button type="button"
                        class="link-btn link-success"
                        @click="bulkCreate()"
                        :disabled="isBulkLoading || missingActions.length === 0">
                    <i class="fa-solid"
                       :class="isBulkLoading ? 'fa-spinner fa-spin' : 'fa-layer-group'"
                       aria-hidden="true"></i>
                    <span x-text="isBulkLoading
                        ? 'Création en cours…'
                        : (missingActions.length === 0
                            ? 'Toutes les actions existent déjà'
                            : `Créer les ${missingActions.length} action(s) manquante(s)`)"></span>
                </button>

                {{-- ✅ Message via x-text (pas x-html) — pas d'XSS --}}
                <p x-show="bulkMessage"
                   x-cloak
                   class="bulk-message"
                   :class="bulkMessageType === 'success' ? 'is-success' : 'is-error'">
                    <i class="fa-solid"
                       :class="bulkMessageType === 'success'
                           ? 'fa-circle-check'
                           : 'fa-circle-exclamation'"
                       aria-hidden="true"></i>
                    <span x-text="bulkMessage"></span>
                </p>
            </div>

        </div>

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- ACTIONS --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        <footer class="form-actions">
            <a href="{{ route('admin.permissions.index') }}"
               class="btn btn-ghost"
               @click="onCancelClick($event)">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <span>Annuler</span>
            </a>
            <button type="submit"
                    class="btn btn-primary"
                    :disabled="submitting || !canSubmit"
                    :aria-busy="submitting">
                <template x-if="!submitting">
                    <span class="btn-content">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        <span>Créer la permission</span>
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
    </form>
</div>
@endsection

@push('styles')
<style>
    /* ════════════════════════════════════════════════════════
       BASE — scopé sous .permission-create-page
       ════════════════════════════════════════════════════════ */
    .permission-create-page {
        --c-primary:      #4f46e5;
        --c-primary-soft: #eef2ff;
        --c-primary-mid:  #c7d2fe;

        --c-emerald:      #059669;
        --c-emerald-soft: #ecfdf5;
        --c-emerald-mid:  #a7f3d0;

        --c-rose:      #dc2626;
        --c-rose-soft: #fef2f2;

        --c-amber:      #d97706;
        --c-amber-soft: #fffbeb;

        --c-slate-50:  #f8fafc;
        --c-slate-100: #f1f5f9;
        --c-slate-200: #e2e8f0;
        --c-slate-300: #cbd5e1;
        --c-slate-400: #94a3b8;
        --c-slate-500: #64748b;
        --c-slate-600: #475569;
        --c-slate-700: #334155;
        --c-slate-800: #1e293b;
        --c-slate-900: #0f172a;

        --radius-sm: 10px;
        --radius-md: 14px;
        --radius-lg: 18px;

        --t: 0.2s cubic-bezier(.4,0,.2,1);

        max-width: 720px;
        margin: 0 auto;
        padding: 2rem 1rem;
        color: var(--c-slate-800);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .permission-create-page *,
    .permission-create-page *::before,
    .permission-create-page *::after { box-sizing: border-box; }

    .permission-create-page [x-cloak] { display: none !important; }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .permission-create-page .page-header {
        display: flex; flex-direction: column; gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    @media (min-width: 640px) {
        .permission-create-page .page-header {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .permission-create-page .page-header-text { min-width: 0; }

    .permission-create-page .page-title {
        display: flex; align-items: center; gap: 0.6rem;
        font-size: 1.55rem; font-weight: 800; color: var(--c-slate-900);
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .permission-create-page .title-icon {
        color: #6366f1; font-size: 1.35rem;
    }
    .permission-create-page .page-subtitle {
        color: var(--c-slate-500); font-size: 0.9rem; margin: 0;
    }
    .permission-create-page .header-actions { display: flex; gap: 0.6rem; }
    @media (max-width: 640px) {
        .permission-create-page .header-actions { width: 100%; }
        .permission-create-page .header-actions .btn { width: 100%; }
    }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .permission-create-page .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.7rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600; font-size: 0.875rem;
        font-family: inherit;
        text-decoration: none; border: none;
        cursor: pointer;
        transition: all var(--t);
        white-space: nowrap;
        min-height: 44px;
    }
    .permission-create-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .permission-create-page .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }
    .permission-create-page .btn-primary:disabled {
        opacity: 0.55; cursor: not-allowed; transform: none;
    }
    .permission-create-page .btn-ghost {
        background: #fff; color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .permission-create-page .btn-ghost:hover {
        border-color: #6366f1; color: var(--c-primary);
        background: var(--c-slate-50);
    }
    .permission-create-page .btn:focus-visible {
        outline: 2px solid var(--c-primary); outline-offset: 2px;
    }
    .permission-create-page .btn-content {
        display: inline-flex; align-items: center; gap: 0.5rem;
    }

    /* ════════════════════════════════════════════════════════
       FORM CARD
       ════════════════════════════════════════════════════════ */
    .permission-create-page .form-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-slate-100);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 14px rgba(0,0,0,0.03);
        padding: 1.75rem;
        animation: formFadeIn 0.4s ease-out both;
    }
    @keyframes formFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @media (max-width: 640px) {
        .permission-create-page .form-card { padding: 1.15rem; }
    }

    .permission-create-page .form-grid {
        display: flex; flex-direction: column; gap: 1.5rem;
    }

    /* ════════════════════════════════════════════════════════
       CHAMPS
       ════════════════════════════════════════════════════════ */
    .permission-create-page .form-field { min-width: 0; }

    .permission-create-page .form-label {
        display: flex; align-items: baseline; gap: 0.4rem;
        font-size: 0.85rem; font-weight: 600;
        color: var(--c-slate-700);
        margin-bottom: 0.45rem;
    }
    .permission-create-page .req { color: var(--c-rose); font-weight: 700; }
    .permission-create-page .optional {
        font-size: 0.72rem; font-weight: 500;
        color: var(--c-slate-400);
    }

    .permission-create-page .input-wrapper {
        position: relative;
    }

    .permission-create-page .input-icon {
        position: absolute; left: 0.9rem; top: 50%;
        transform: translateY(-50%);
        color: var(--c-slate-400);
        font-size: 0.9rem;
        pointer-events: none;
        transition: color var(--t);
    }
    .permission-create-page .input-icon-top {
        top: 1rem; transform: none;
    }

    .permission-create-page .form-input,
    .permission-create-page .form-select,
    .permission-create-page .form-textarea {
        width: 100%;
        padding: 0.7rem 0.9rem 0.7rem 2.5rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        font-size: 0.9rem;
        font-family: inherit;
        color: var(--c-slate-900);
        outline: none;
        transition: all var(--t);
        min-height: 44px;
    }
    .permission-create-page .form-select {
        padding-right: 2.5rem;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath fill-rule='evenodd' d='M10 12a1 1 0 01-.7-.3l-4-4a1 1 0 011.4-1.4L10 9.6l3.3-3.3a1 1 0 011.4 1.4l-4 4a1 1 0 01-.7.3z' clip-rule='evenodd'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1rem;
    }
    .permission-create-page .form-textarea {
        min-height: 60px;
        resize: vertical;
        line-height: 1.5;
        padding-left: 2.5rem;
    }

    .permission-create-page .form-input:focus,
    .permission-create-page .form-select:focus,
    .permission-create-page .form-textarea:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    .permission-create-page .input-wrapper:focus-within .input-icon {
        color: #6366f1;
    }

    .permission-create-page .form-input.is-invalid,
    .permission-create-page .form-select.is-invalid,
    .permission-create-page .form-textarea.is-invalid {
        border-color: var(--c-rose);
        background: var(--c-rose-soft);
    }

    .permission-create-page .form-input.is-readonly {
        background: var(--c-slate-100);
        color: var(--c-slate-500);
        cursor: not-allowed;
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
        font-size: 0.85rem;
        letter-spacing: 0.2px;
    }
    .permission-create-page .form-input.is-valid {
        border-color: var(--c-emerald);
        background: #fff;
    }

    /* ════════════════════════════════════════════════════════
       HELPERS TEXTES
       ════════════════════════════════════════════════════════ */
    .permission-create-page .form-help {
        display: flex; align-items: flex-start; gap: 0.4rem;
        font-size: 0.75rem; color: var(--c-slate-500);
        margin: 0.4rem 0 0; line-height: 1.5;
    }
    .permission-create-page .form-help i {
        color: var(--c-amber); flex-shrink: 0; margin-top: 2px;
        font-size: 0.75rem;
    }
    .permission-create-page .form-help-warn {
        color: var(--c-amber);
    }
    .permission-create-page .form-help-warn i {
        color: var(--c-amber);
    }

    .permission-create-page .error-text {
        display: block;
        font-size: 0.78rem; color: var(--c-rose);
        margin: 0.35rem 0 0;
        font-weight: 500;
    }

    /* ════════════════════════════════════════════════════════
       TRANSITION FIELD
       ════════════════════════════════════════════════════════ */
    .permission-create-page .field-enter {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .permission-create-page .field-enter-start {
        opacity: 0; transform: translateY(-6px);
    }
    .permission-create-page .field-enter-end {
        opacity: 1; transform: translateY(0);
    }

    /* ════════════════════════════════════════════════════════
       APERÇU
       ════════════════════════════════════════════════════════ */
    .permission-create-page .preview-card {
        display: flex; align-items: flex-start; gap: 0.9rem;
        padding: 1rem 1.15rem;
        background: linear-gradient(135deg, var(--c-primary-soft), #f5f3ff);
        border: 1.5px solid var(--c-primary-mid);
        border-radius: var(--radius-md);
        animation: previewIn 0.3s ease-out both;
    }
    @keyframes previewIn {
        from { opacity: 0; transform: scale(0.97); }
        to   { opacity: 1; transform: scale(1); }
    }

    .permission-create-page .preview-icon {
        width: 40px; height: 40px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(79,70,229,0.25);
    }
    .permission-create-page .preview-content { min-width: 0; flex: 1; }
    .permission-create-page .preview-title {
        font-size: 0.72rem; font-weight: 700;
        color: #4338ca;
        text-transform: uppercase; letter-spacing: 0.5px;
        margin: 0 0 0.25rem;
    }
    .permission-create-page .preview-name {
        display: inline-block;
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
        font-size: 0.85rem; font-weight: 600;
        color: var(--c-slate-900);
        background: rgba(255,255,255,0.7);
        padding: 0.15rem 0.5rem;
        border-radius: 5px;
        word-break: break-all;
    }
    .permission-create-page .preview-meta {
        font-size: 0.75rem; color: #6366f1;
        margin: 0.4rem 0 0;
    }
    .permission-create-page .preview-meta .dot {
        opacity: 0.5; margin: 0 0.4rem;
    }

    /* ════════════════════════════════════════════════════════
       BULK CARD
       ════════════════════════════════════════════════════════ */
    .permission-create-page .bulk-card {
        padding: 1rem 1.15rem;
        background: var(--c-slate-50);
        border: 1px solid var(--c-slate-200);
        border-radius: var(--radius-md);
    }

    .permission-create-page .bulk-header {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.85rem; color: var(--c-slate-700);
        margin-bottom: 0.75rem;
    }
    .permission-create-page .bulk-header i {
        color: #6366f1;
    }
    .permission-create-page .bulk-header strong {
        color: var(--c-slate-900);
    }

    .permission-create-page .bulk-pills {
        display: flex; flex-wrap: wrap; gap: 0.35rem;
        margin-bottom: 0.85rem;
    }
    .permission-create-page .bulk-pill {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        background: #fff;
        border: 1px solid var(--c-slate-200);
        border-radius: 9999px;
        font-size: 0.72rem; font-weight: 600;
        color: var(--c-slate-600);
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
    }
    .permission-create-page .bulk-pill i {
        color: var(--c-emerald); font-size: 0.6rem;
    }

    .permission-create-page .link-btn {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.5rem 0.85rem;
        background: transparent;
        border: 1px solid var(--c-emerald-mid);
        border-radius: 8px;
        font-weight: 600; font-size: 0.78rem;
        font-family: inherit;
        color: var(--c-emerald);
        cursor: pointer;
        transition: all var(--t);
        min-height: 36px;
    }
    .permission-create-page .link-btn:hover:not(:disabled) {
        background: var(--c-emerald-soft);
    }
    .permission-create-page .link-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        color: var(--c-slate-500);
        border-color: var(--c-slate-200);
    }

    .permission-create-page .bulk-message {
        display: flex; align-items: center; gap: 0.4rem;
        font-size: 0.78rem; font-weight: 500;
        margin: 0.75rem 0 0;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
    }
    .permission-create-page .bulk-message.is-success {
        background: var(--c-emerald-soft);
        color: var(--c-emerald);
    }
    .permission-create-page .bulk-message.is-error {
        background: var(--c-rose-soft);
        color: var(--c-rose);
    }

    /* ════════════════════════════════════════════════════════
       FORM ACTIONS
       ════════════════════════════════════════════════════════ */
    .permission-create-page .form-actions {
        display: flex; justify-content: flex-end; gap: 0.6rem;
        margin-top: 2rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--c-slate-100);
    }
    @media (max-width: 640px) {
        .permission-create-page .form-actions {
            flex-direction: column-reverse;
        }
        .permission-create-page .form-actions .btn { width: 100%; }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE
       ════════════════════════════════════════════════════════ */
    @media (max-width: 640px) {
        .permission-create-page { padding: 1.25rem 0.85rem; }
        .permission-create-page .page-title { font-size: 1.3rem; }
        .permission-create-page .title-icon { font-size: 1.15rem; }

        /* Anti-zoom iOS */
        .permission-create-page .form-input,
        .permission-create-page .form-select,
        .permission-create-page .form-textarea {
            font-size: 16px;
        }
    }

    @media (max-width: 480px) {
        .permission-create-page { padding: 1rem 0.65rem; }
        .permission-create-page .page-title { font-size: 1.15rem; }
        .permission-create-page .form-card { padding: 1rem; }
    }

    /* ════════════════════════════════════════════════════════
       A11Y + PRINT
       ════════════════════════════════════════════════════════ */
    @media (prefers-reduced-motion: reduce) {
        .permission-create-page *,
        .permission-create-page *::before,
        .permission-create-page *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }

    @media print {
        .permission-create-page .form-actions,
        .permission-create-page .header-actions,
        .permission-create-page .bulk-card,
        .permission-create-page .preview-card {
            display: none !important;
        }
        .permission-create-page .form-card {
            box-shadow: none; border: 1px solid #ccc;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        /**
         * ✅ Factory Alpine : Permission Form
         *
         * Corrections clés :
         *   1. Enregistrement idempotent (pas de dépendance à alpine:init)
         *   2. `Set` pour `allPermissions` → lookup O(1)
         *   3. `bulkCreate()` envoie le CSRF token
         *   4. Message via `x-text` (pas x-html) → pas d'XSS
         *   5. `hasChanges` détecté → `beforeunload`
         */
        const factory = (config) => ({
            /* ============================================================
               ÉTAT
               ============================================================ */
            // Données
            resources: config.resources || [],
            actionsByResource: config.actionsByResource || {},
            permissionsByResource: config.permissionsByResource || {},
            standardActions: config.standardActions || [],
            allPermissions: new Set(config.allPermissions || []),

            // Formulaire
            selectedResource: config.oldValues?.resource || '',
            selectedAction: config.oldValues?.action || '',
            actions: [],
            permissionName: config.oldValues?.name || '',
            label: config.oldValues?.label || '',
            description: config.oldValues?.description || '',
            existingPermissions: [],

            // UI
            isBulkLoading: false,
            bulkMessage: '',
            bulkMessageType: null,   // 'success' | 'error'
            submitting: false,

            // URLs
            bulkCreateUrl: config.bulkCreateUrl || '',
            csrfToken: config.csrfToken || '',

            // Snapshot initial pour hasChanges
            initialSnapshot: '',

            /* ============================================================
               INIT
               ============================================================ */
            init() {
                // Restaure l'état depuis old() si présent
                if (this.selectedResource) {
                    this.refreshActions();
                }

                if (this.permissionName) {
                    const parts = this.permissionName.split('.');
                    if (parts.length === 2) {
                        this.selectedResource = parts[0];
                        this.selectedAction = parts[1];
                        this.refreshActions();
                    }
                }

                this.refreshExisting();

                // Snapshot initial
                this.initialSnapshot = this.snapshot();

                // ✅ Reset submitting au retour navigateur
                window.addEventListener('pageshow', () => {
                    this.submitting = false;
                });

                // ✅ Warning si modifs non sauvegardées
                window.addEventListener('beforeunload', (e) => {
                    if (this.hasChanges && !this.submitting) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });
            },

            /* ============================================================
               GETTERS
               ============================================================ */
            get permissionExists() {
                return this.permissionName && this.allPermissions.has(this.permissionName);
            },

            get missingActions() {
                if (!this.selectedResource) return [];
                const existing = new Set(this.existingPermissions);
                return this.standardActions.filter(a => !existing.has(a));
            },

            get canSubmit() {
                return this.selectedResource
                    && this.selectedAction
                    && this.permissionName
                    && !this.permissionExists;
            },

            get hasChanges() {
                return this.snapshot() !== this.initialSnapshot;
            },

            /* ============================================================
               ACTIONS
               ============================================================ */
            onResourceChange() {
                this.selectedAction = '';
                this.permissionName = '';
                this.refreshActions();
                this.refreshExisting();
                this.bulkMessage = '';
                this.bulkMessageType = null;
            },

            onActionChange() {
                this.permissionName = this.selectedResource && this.selectedAction
                    ? `${this.selectedResource}.${this.selectedAction}`
                    : '';
            },

            refreshActions() {
                if (!this.selectedResource) {
                    this.actions = [];
                    return;
                }

                const standard = this.standardActions;
                const existing = this.permissionsByResource[this.selectedResource] || [];

                // Union : actions standard + actions existantes uniques
                this.actions = [...new Set([...standard, ...existing])].sort();
            },

            refreshExisting() {
                if (!this.selectedResource) {
                    this.existingPermissions = [];
                    return;
                }
                this.existingPermissions = this.permissionsByResource[this.selectedResource] || [];
            },

            /* ============================================================
               HELPERS
               ============================================================ */
            prettyResource(resource) {
                if (!resource) return '';
                const s = String(resource).replace(/[-_]/g, ' ');
                return s.charAt(0).toUpperCase() + s.slice(1);
            },

            snapshot() {
                return JSON.stringify({
                    r: this.selectedResource,
                    a: this.selectedAction,
                    n: this.permissionName,
                    l: this.label,
                    d: this.description,
                });
            },

            /* ============================================================
               BULK CREATE — ✅ CSRF + réponse robuste + pas d'XSS
               ============================================================ */
            async bulkCreate() {
                if (!this.selectedResource || this.isBulkLoading) return;

                const missing = this.missingActions;
                if (missing.length === 0) {
                    this.bulkMessage = 'Toutes les actions existent déjà.';
                    this.bulkMessageType = 'error';
                    return;
                }

                if (!confirm(
                    `Créer les permissions manquantes pour « ${this.selectedResource} » ?\n\n`
                    + `- ${missing.join('\n- ')}`
                )) {
                    return;
                }

                this.isBulkLoading = true;
                this.bulkMessage = '';
                this.bulkMessageType = null;

                try {
                    const params = new URLSearchParams({
                        resource: this.selectedResource,
                        actions: missing.join(','),
                    });

                    const response = await fetch(`${this.bulkCreateUrl}?${params}`, {
                        method: 'GET',                    // 🎯 La route est un GET (bulkCreate)
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,  // ✅ CSRF en cas de POST un jour
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        // Met à jour permissionsByResource local
                        const existing = new Set(this.permissionsByResource[this.selectedResource] || []);
                        (data.created || []).forEach(name => {
                            const parts = String(name).split('.');
                            const action = parts.slice(1).join('.');
                            existing.add(action);
                            this.allPermissions.add(name);
                        });
                        this.permissionsByResource[this.selectedResource] = [...existing].sort();
                        this.refreshExisting();

                        const count = (data.created || []).length;
                        this.bulkMessage = `${count} permission(s) créée(s) avec succès.`;
                        this.bulkMessageType = 'success';
                    } else {
                        this.bulkMessage = data.error || 'Une erreur est survenue.';
                        this.bulkMessageType = 'error';
                    }

                } catch (error) {
                    console.error('bulkCreate error:', error);
                    this.bulkMessage = 'Erreur réseau. Veuillez réessayer.';
                    this.bulkMessageType = 'error';
                } finally {
                    this.isBulkLoading = false;
                }
            },

            /* ============================================================
               SOUMISSION
               ============================================================ */
            onSubmit(event) {
                if (this.submitting) {
                    event.preventDefault();
                    return;
                }
                if (!this.canSubmit) {
                    event.preventDefault();
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
        });

        /* ============================================================
           ENREGISTREMENT IDEMPOTENT
           ============================================================ */
        const register = () => {
            if (window.Alpine && !window._permissionFormRegistered) {
                window._permissionFormRegistered = true;
                window.Alpine.data('permissionForm', factory);
            }
        };

        if (window.Alpine) {
            register();
        } else {
            document.addEventListener('alpine:init', register);
        }
    })();
</script>
@endpush