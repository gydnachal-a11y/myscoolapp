@extends('layouts.admin')

@section('page_title', 'Modifier l\'annonce')
@section('page_subtitle', 'Mettre à jour les informations de l\'annonce')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg flex items-start gap-2 animate-slideDown">
            <i class="fa-regular fa-check-circle mt-0.5"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-start gap-2 animate-slideDown">
            <i class="fa-regular fa-circle-exclamation mt-0.5"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="index-header">
        <div>
            <h1 class="index-title">
                <i class="fa-solid fa-pen-to-square text-indigo-500"></i>
                <strong>Modifier l'annonce</strong>
            </h1>
            <p class="index-subtitle">
                <span class="badge-type badge-{{ $annonce->type }}">
                    {{ $annonce->type_libelle }}
                </span>
                <span class="badge-statut {{ $annonce->est_active ? 'is-active' : 'is-inactive' }}">
                    {{ $annonce->statut_libelle }}
                </span>
                <span class="text-slate-400">— ID #{{ $annonce->id }}</span>
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.annonces.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <form action="{{ route('admin.annonces.update', $annonce) }}"
          method="POST"
          enctype="multipart/form-data"
          class="filter-card"
          novalidate>
        @csrf
        @method('PUT')

        {{-- Section Contenu --}}
        <div class="form-section">
            <h2 class="section-title"><i class="fa-solid fa-megaphone"></i> Contenu de l'annonce</h2>

            <div class="filter-field">
                <label class="filter-label" for="titre">
                    <i class="fa-regular fa-font"></i> Titre <span class="text-red-500">*</span>
                </label>
                <input type="text" name="titre" id="titre"
                       value="{{ old('titre', $annonce->titre) }}"
                       placeholder="Titre de l'annonce" required
                       class="filter-select @error('titre') is-invalid @enderror">
                @error('titre')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="filter-field mt-4">
                <label class="filter-label" for="contenu">
                    <i class="fa-regular fa-file-lines"></i> Contenu <span class="text-red-500">*</span>
                </label>
                <textarea name="contenu" id="contenu" rows="14"
                          class="@error('contenu') is-invalid @enderror">{{ old('contenu', $annonce->contenu) }}</textarea>
                @error('contenu')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Section Période et image --}}
        <div class="form-section mt-6">
            <h2 class="section-title"><i class="fa-regular fa-calendar"></i> Période et image</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="filter-field">
                    <label class="filter-label" for="date_debut">
                        <i class="fa-regular fa-calendar-plus"></i> Date de début
                    </label>
                    <input type="datetime-local" name="date_debut" id="date_debut"
                           value="{{ old('date_debut', optional($annonce->date_debut)->format('Y-m-d\TH:i')) }}"
                           class="filter-select @error('date_debut') is-invalid @enderror">
                    @error('date_debut')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div class="filter-field">
                    <label class="filter-label" for="date_fin">
                        <i class="fa-regular fa-calendar-xmark"></i> Date de fin
                    </label>
                    <input type="datetime-local" name="date_fin" id="date_fin"
                           value="{{ old('date_fin', optional($annonce->date_fin)->format('Y-m-d\TH:i')) }}"
                           class="filter-select @error('date_fin') is-invalid @enderror">
                    @error('date_fin')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Image actuelle + upload --}}
            <div class="filter-field mt-4">
                <label class="filter-label" for="image">
                    <i class="fa-regular fa-image"></i> Image de couverture (optionnel)
                </label>

                @if($annonce->image && Storage::disk('public')->exists($annonce->image))
                    <div class="current-image-wrapper">
                        <img src="{{ asset('storage/' . $annonce->image) }}"
                             alt="Image actuelle"
                             class="current-image">
                        <div class="current-image-meta">
                            <p class="current-image-name">
                                <i class="fa-regular fa-file-image"></i>
                                {{ basename($annonce->image) }}
                            </p>
                            <p class="current-image-hint">
                                Sélectionnez un nouveau fichier ci-dessous pour la remplacer.
                            </p>
                        </div>
                    </div>
                @endif

                <input type="file" name="image" id="image" accept="image/*"
                       class="filter-select @error('image') is-invalid @enderror">
                @error('image')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="filter-field mt-4">
                <label class="filter-label" for="type">
                    <i class="fa-regular fa-eye"></i> Visibilité
                </label>
                <select name="type" id="type"
                        class="filter-select @error('type') is-invalid @enderror" required>
                    <option value="public" @selected(old('type', $annonce->type) === 'public')>
                        Publique (tous les visiteurs)
                    </option>
                    <option value="prive" @selected(old('type', $annonce->type) === 'prive')>
                        Privée (utilisateurs connectés uniquement)
                    </option>
                </select>
                @error('type')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="form-check mt-4">
                <input type="checkbox" name="est_active" value="1" id="est_active"
                       class="form-check-input"
                       @checked(old('est_active', $annonce->est_active))>
                <label class="form-check-label" for="est_active">Activer cette annonce</label>
            </div>
        </div>

        <div class="flex justify-between items-center mt-6 form-actions">
            <a href="{{ route('admin.annonces.index') }}" class="btn-secondary">Annuler</a>
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<style>
    /* =========================================================
       BASE
       ========================================================= */
    .index-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    .index-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .index-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .index-title strong { font-weight: 800; }
    .index-subtitle {
        color: #94a3b8;
        font-size: 0.95rem;
        margin-top: 0.5rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .header-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }

    /* =========================================================
       BADGES
       ========================================================= */
    .badge-type,
    .badge-statut {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1;
    }
    .badge-type.badge-public { background: #eef2ff; color: #4f46e5; }
    .badge-type.badge-prive  { background: #fef3c7; color: #b45309; }
    .badge-statut.is-active   { background: #dcfce7; color: #15803d; }
    .badge-statut.is-inactive { background: #fee2e2; color: #b91c1c; }

    /* =========================================================
       BOUTONS
       ========================================================= */
    .btn-primary,
    .btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0.7rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-primary { background: #1e293b; color: white; }
    .btn-primary:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102,126,234,0.3);
    }
    .btn-secondary {
        background: white;
        border: 1.5px solid #e2e8f0;
        color: #64748b;
    }
    .btn-secondary:hover {
        border-color: #667eea;
        color: #667eea;
        background: #f8fafc;
    }

    /* =========================================================
       CARTE / SECTIONS
       ========================================================= */
    .filter-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        padding: 1.5rem;
        border: 1px solid #f1f5f9;
    }
    .form-section { margin-bottom: 1.5rem; }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .section-title i { color: #667eea; }

    /* =========================================================
       CHAMPS
       ========================================================= */
    .filter-field {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        margin-bottom: 1rem;
    }
    .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }
    .filter-select {
        width: 100%;
        padding: 0.6rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-size: 0.95rem;
        color: #1e293b;
        transition: all 0.3s;
        outline: none;
        box-sizing: border-box;
    }
    .filter-select:focus {
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }
    .filter-select.is-invalid { border-color: #ef4444; }
    .error-text {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 0.3rem;
    }

    /* =========================================================
       IMAGE ACTUELLE
       ========================================================= */
    .current-image-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
    }
    .current-image {
        width: 90px;
        height: 90px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        background: white;
        flex-shrink: 0;
    }
    .current-image-meta { flex: 1; min-width: 0; }
    .current-image-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
        word-break: break-all;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .current-image-hint {
        font-size: 0.78rem;
        color: #94a3b8;
        margin-top: 0.2rem;
    }

    /* =========================================================
       CHECKBOX
       ========================================================= */
    .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .form-check-input {
        width: 18px;
        height: 18px;
        accent-color: #4f46e5;
        flex-shrink: 0;
    }
    .form-check-label {
        font-size: 0.9rem;
        color: #475569;
    }

    /* =========================================================
       CKEDITOR 5
       ========================================================= */
    .ck-editor__editable_inline {
        min-height: 420px;
        max-height: 70vh;
        overflow-y: auto;
        padding: 1.25rem 1.5rem !important;
        border: 2px solid #e2e8f0 !important;
        border-top: none !important;
        border-radius: 0 0 10px 10px !important;
        background: #fff !important;
        font-size: 0.98rem;
        line-height: 1.65;
        color: #1e293b;
    }
    .ck-editor__editable_inline:focus,
    .ck-editor__editable_inline.ck-focused {
        border-color: #667eea !important;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1) !important;
    }
    .ck.ck-editor__top .ck-sticky-panel .ck-toolbar {
        border: 2px solid #e2e8f0 !important;
        border-bottom: none !important;
        border-radius: 10px 10px 0 0 !important;
        background: #f8fafc !important;
    }
    .ck.ck-toolbar { flex-wrap: wrap; }
    .ck.ck-editor__editable.is-invalid { border-color: #ef4444 !important; }
    .ck-content h1 { font-size: 1.9rem; }
    .ck-content h2 { font-size: 1.5rem; }
    .ck-content h3 { font-size: 1.25rem; }
    .ck-content h4 { font-size: 1.1rem; }
    .ck-content img { max-width: 100%; height: auto; }
    .ck-content table { max-width: 100%; }

    /* =========================================================
       ACTIONS FORMULAIRE
       ========================================================= */
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .form-actions .btn-primary,
    .form-actions .btn-secondary {
        flex: 0 0 auto;
    }

    /* =========================================================
       RESPONSIVE — TABLETTE
       ========================================================= */
    @media (max-width: 992px) {
        .index-container { max-width: 100%; }
        .ck-editor__editable_inline { min-height: 360px; }
    }

    /* =========================================================
       RESPONSIVE — MOBILE
       ========================================================= */
    @media (max-width: 768px) {
        .index-container {
            padding: 1rem 0.75rem;
        }
        .index-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .index-title { font-size: 1.35rem; }
        .index-subtitle { font-size: 0.85rem; }
        .header-actions {
            width: 100%;
        }
        .header-actions .btn-secondary {
            width: 100%;
            justify-content: center;
        }
        .filter-card {
            padding: 1rem;
            border-radius: 12px;
        }
        .grid-cols-2 { grid-template-columns: 1fr; }
        .section-title { font-size: 1rem; }

        /* Champs plus compacts */
        .filter-select { font-size: 16px; /* évite le zoom iOS */ }

        /* Image actuelle empilée */
        .current-image-wrapper {
            flex-direction: column;
            align-items: flex-start;
        }
        .current-image {
            width: 100%;
            height: 160px;
        }

        /* CKEditor compact */
        .ck-editor__editable_inline {
            min-height: 280px;
            max-height: 60vh;
            padding: 0.9rem 1rem !important;
            font-size: 0.95rem;
        }
        .ck.ck-toolbar {
            padding: 0.25rem 0.35rem !important;
            overflow-x: auto;
            flex-wrap: nowrap !important;
        }
        .ck.ck-toolbar .ck-toolbar__items {
            flex-wrap: nowrap;
        }

        /* Actions empilées */
        .form-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }
        .form-actions .btn-primary,
        .form-actions .btn-secondary {
            width: 100%;
            justify-content: center;
        }
    }

    /* =========================================================
       RESPONSIVE — TRÈS PETIT MOBILE
       ========================================================= */
    @media (max-width: 480px) {
        .index-container { padding: 0.75rem 0.5rem; }
        .index-title { font-size: 1.15rem; }
        .index-title i { font-size: 1rem; }
        .index-subtitle { font-size: 0.78rem; gap: 0.35rem; }
        .filter-card { padding: 0.85rem; }
        .section-title { font-size: 0.95rem; }
        .filter-label { font-size: 0.7rem; }
        .ck-editor__editable_inline {
            min-height: 220px;
            padding: 0.75rem !important;
        }
    }

    /* =========================================================
       UTILITAIRE — évite les débordements horizontaux
       ========================================================= */
    .index-container img,
    .index-container table,
    .index-container pre {
        max-width: 100%;
    }
    .index-container * { box-sizing: border-box; }
</style>

{{-- CKEditor 5 - Build Classique v41 --}}
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ✅ Nom EXACT de la route défini dans routes/web.php : admin.annonces.upload_image
    const uploadUrl = "{{ route('admin.annonces.upload-image') }}";
    const csrfToken = "{{ csrf_token() }}";

    /* =========================================================
       UPLOAD ADAPTER PERSONNALISÉ (branché sur Laravel)
       ========================================================= */
    class LaravelUploadAdapter {
        constructor(loader) { this.loader = loader; }

        upload() {
            return this.loader.file.then(file => new Promise((resolve, reject) => {
                this._initRequest();
                this._initListeners(resolve, reject, file);
                this._sendRequest(file);
            }));
        }

        abort() { if (this.xhr) this.xhr.abort(); }

        _initRequest() {
            const xhr = this.xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl, true);
            xhr.responseType = 'json';
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');
        }

        _initListeners(resolve, reject, file) {
            const xhr = this.xhr;
            const loader = this.loader;
            const genericError = `Impossible de télécharger l'image "${file.name}".`;

            xhr.addEventListener('error', () => reject(genericError));
            xhr.addEventListener('abort', () => reject());

            xhr.addEventListener('load', () => {
                const response = xhr.response;

                if (xhr.status >= 400 || !response) {
                    let msg = genericError;
                    if (response?.errors) {
                        msg = Object.values(response.errors).flat().join(' ');
                    } else if (response?.message) {
                        msg = response.message;
                    }
                    reject(msg);
                    return;
                }

                const url = response.location || response.url || response.default;
                if (!url) { reject(genericError); return; }
                resolve({ default: url });
            });

            if (xhr.upload) {
                xhr.upload.addEventListener('progress', evt => {
                    if (evt.lengthComputable) {
                        loader.uploadTotal = evt.total;
                        loader.uploaded = evt.loaded;
                    }
                });
            }
        }

        _sendRequest(file) {
            const data = new FormData();
            data.append('file', file);
            this.xhr.send(data);
        }
    }

    function LaravelUploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter =
            (loader) => new LaravelUploadAdapter(loader);
    }

    /* =========================================================
       INITIALISATION CKEDITOR 5
       ========================================================= */
    ClassicEditor
        .create(document.querySelector('#contenu'), {
            language: 'fr',
            placeholder: 'Rédigez ici le contenu de votre annonce…',
            extraPlugins: [LaravelUploadAdapterPlugin],

            toolbar: {
                shouldNotGroupWhenFull: true,
                items: [
                    'heading', '|',
                    'fontFamily', 'fontSize', '|',
                    'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', '|',
                    'fontColor', 'fontBackgroundColor', 'highlight', '|',
                    'alignment', '|',
                    'bulletedList', 'numberedList', 'todoList', '|',
                    'outdent', 'indent', '|',
                    'link', 'blockQuote', 'uploadImage', 'mediaEmbed',
                    'insertTable', 'horizontalLine', 'specialCharacters', '|',
                    'removeFormat', 'sourceEditing', '|',
                    'undo', 'redo'
                ]
            },

            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraphe', class: 'ck-heading_paragraph' },
                    { model: 'heading1',  view: 'h1', title: 'Titre 1', class: 'ck-heading_heading1' },
                    { model: 'heading2',  view: 'h2', title: 'Titre 2', class: 'ck-heading_heading2' },
                    { model: 'heading3',  view: 'h3', title: 'Titre 3', class: 'ck-heading_heading3' },
                    { model: 'heading4',  view: 'h4', title: 'Titre 4', class: 'ck-heading_heading4' }
                ]
            },

            fontFamily: {
                options: [
                    'default',
                    'Arial, Helvetica, sans-serif',
                    'Calibri, Candara, Segoe, sans-serif',
                    'Courier New, Courier, monospace',
                    'Georgia, serif',
                    'Lucida Sans Unicode, Lucida Grande, sans-serif',
                    'Tahoma, Geneva, sans-serif',
                    'Times New Roman, Times, serif',
                    'Trebuchet MS, Helvetica, sans-serif',
                    'Verdana, Geneva, sans-serif'
                ],
                supportAllValues: true
            },

            fontSize: {
                options: [9, 10, 11, 'default', 13, 14, 16, 18, 20, 22, 24, 28, 32, 36, 48],
                supportAllValues: true
            },

            alignment: {
                options: ['left', 'center', 'right', 'justify']
            },

            list: {
                properties: { styles: true, startIndex: true, reversed: true }
            },

            link: {
                addTargetToExternalLinks: true,
                defaultProtocol: 'https://',
                decorators: {
                    openInNewTab: {
                        mode: 'manual',
                        label: 'Ouvrir dans un nouvel onglet',
                        attributes: { target: '_blank', rel: 'noopener noreferrer' }
                    }
                }
            },

            image: {
                toolbar: [
                    'imageStyle:inline',
                    'imageStyle:block',
                    'imageStyle:side',
                    '|',
                    'toggleImageCaption',
                    'imageTextAlternative',
                    '|',
                    'resizeImage'
                ],
                resizeOptions: [
                    { name: 'resizeImage:original', value: null, label: 'Original' },
                    { name: 'resizeImage:25', value: '25', label: '25%' },
                    { name: 'resizeImage:50', value: '50', label: '50%' },
                    { name: 'resizeImage:75', value: '75', label: '75%' }
                ]
            },

            table: {
                contentToolbar: [
                    'tableColumn', 'tableRow', 'mergeTableCells',
                    'tableProperties', 'tableCellProperties'
                ]
            }
        })
        .then(editor => {
            editor.editing.view.change(writer => {
                writer.setStyle(
                    'min-height',
                    '420px',
                    editor.editing.view.document.getRoot()
                );
            });

            // Nettoyage visuel si la validation Laravel a échoué
            editor.model.document.on('change:data', () => {
                editor.sourceElement?.classList.remove('is-invalid');
            });

            console.log('CKEditor 5 initialisé (édition)');
        })
        .catch(error => {
            console.error('Erreur CKEditor 5 :', error);
        });
});
</script>
@endsection