@extends('layouts.admin')

@section('page_title', 'Modifier règlement')
@section('page_subtitle', 'Mettre à jour une règle, obligation ou interdiction')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="flash flash-success">
            <i class="fa-regular fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">
            <i class="fa-regular fa-circle-exclamation"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="index-header">
        <div>
            <h1 class="index-title">
                <i class="fa-solid fa-gavel text-indigo-500"></i>
                <strong>Modifier règlement</strong>
            </h1>
            <p class="index-subtitle">
                <span class="badge-categorie badge-{{ \App\Models\ReglementInterieur::couleurPourCategorie($reglement->categorie) }}">
                    <i class="fa-solid {{ \App\Models\ReglementInterieur::iconePourCategorie($reglement->categorie) }}"></i>
                    {{ $reglement->categorie_label }}
                </span>
                <span class="badge-statut {{ $reglement->estActif() ? 'is-active' : 'is-inactive' }}">
                    {{ $reglement->estActif() ? 'Actif' : 'Inactif' }}
                </span>
                <span class="text-slate-400">— ID #{{ $reglement->id }}</span>
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.reglements.index') }}" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <form action="{{ route('admin.reglements.update', $reglement) }}"
          method="POST"
          class="filter-card"
          novalidate>
        @csrf
        @method('PUT')

        {{-- Section Contenu --}}
        <div class="form-section">
            <h2 class="section-title"><i class="fa-solid fa-book"></i> Contenu du règlement</h2>

            <div class="form-grid">
                <div class="filter-field">
                    <label class="filter-label" for="titre">
                        <i class="fa-regular fa-font"></i> Titre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="titre" id="titre"
                           value="{{ old('titre', $reglement->titre) }}"
                           placeholder="Titre du règlement" required
                           class="filter-select @error('titre') is-invalid @enderror">
                    @error('titre')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="categorie">
                        <i class="fa-regular fa-folder"></i> Catégorie <span class="text-red-500">*</span>
                    </label>
                    <select name="categorie" id="categorie"
                            class="filter-select @error('categorie') is-invalid @enderror" required>
                        @foreach(\App\Models\ReglementInterieur::categoriesPourSelect() as $cat)
                            <option value="{{ $cat['value'] }}"
                                    @selected(old('categorie', $reglement->categorie) === $cat['value'])>
                                {{ $cat['label'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('categorie')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="filter-field mt-4">
                <label class="filter-label" for="ordre">
                    <i class="fa-regular fa-list-ol"></i> Ordre d'affichage
                </label>
                <input type="number" name="ordre" id="ordre"
                       value="{{ old('ordre', $reglement->ordre) }}"
                       min="0" max="9999" step="1" inputmode="numeric"
                       class="filter-select @error('ordre') is-invalid @enderror">
                <p class="field-hint">
                    <i class="fa-regular fa-lightbulb"></i>
                    Plus le nombre est petit, plus le règlement apparaît haut dans la liste.
                </p>
                @error('ordre')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            <div class="filter-field mt-4">
                <label class="filter-label" for="contenu">
                    <i class="fa-regular fa-file-lines"></i> Contenu <span class="text-red-500">*</span>
                </label>
                <textarea name="contenu" id="contenu" rows="14"
                          class="@error('contenu') is-invalid @enderror">{{ old('contenu', $reglement->contenu) }}</textarea>
                @error('contenu')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Section Statut --}}
        <div class="form-section mt-6">
            <h2 class="section-title"><i class="fa-regular fa-circle-check"></i> Statut</h2>
            <div class="form-check">
                <input type="checkbox" name="est_actif" value="1" id="est_actif"
                       class="form-check-input"
                       @checked(old('est_actif', $reglement->est_actif))>
                <label class="form-check-label" for="est_actif">
                    Activer ce règlement
                </label>
            </div>
        </div>

        <div class="form-actions mt-6">
            <a href="{{ route('admin.reglements.index') }}" class="btn-secondary">Annuler</a>
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Mettre à jour
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
    .index-container * { box-sizing: border-box; }
    .index-container img,
    .index-container table { max-width: 100%; }

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
    .text-slate-400 { color: #94a3b8; }
    .header-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }

    /* =========================================================
       BADGES
       ========================================================= */
    .badge-categorie,
    .badge-statut {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        line-height: 1;
    }
    .badge-categorie.badge-blue   { background: #dbeafe; color: #1d4ed8; }
    .badge-categorie.badge-amber  { background: #fef3c7; color: #92400e; }
    .badge-categorie.badge-red    { background: #fee2e2; color: #b91c1c; }
    .badge-categorie.badge-gray   { background: #f1f5f9; color: #475569; }
    .badge-statut.is-active   { background: #dcfce7; color: #15803d; }
    .badge-statut.is-inactive { background: #fee2e2; color: #b91c1c; }

    /* =========================================================
       FLASH
       ========================================================= */
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
    .flash i { margin-top: 2px; }

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
       GRILLE INTERNE
       ========================================================= */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

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
    .filter-label i { color: #94a3b8; }

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
        font-family: inherit;
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
    .field-hint {
        font-size: 0.78rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 0.35rem;
    }
    .field-hint i { color: #cbd5e1; }

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
        min-height: 380px;
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

    /* =========================================================
       RESPONSIVE — TABLETTE (≤ 992px)
       ========================================================= */
    @media (max-width: 992px) {
        .index-container { max-width: 100%; }
        .ck-editor__editable_inline { min-height: 340px; }
    }

    /* =========================================================
       RESPONSIVE — MOBILE / TABLETTE PORTRAIT (≤ 768px)
       ========================================================= */
    @media (max-width: 768px) {
        .index-container { padding: 1rem 0.75rem; }
        .index-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .index-title { font-size: 1.35rem; }
        .index-subtitle {
            font-size: 0.85rem;
            gap: 0.4rem;
        }
        .header-actions { width: 100%; }
        .header-actions .btn-secondary {
            width: 100%;
            justify-content: center;
        }

        .filter-card {
            padding: 1rem;
            border-radius: 12px;
        }

        /* Grille : titre / catégorie empilés */
        .form-grid { grid-template-columns: 1fr; }

        .section-title { font-size: 1rem; }

        /* Anti-zoom iOS */
        .filter-select { font-size: 16px; }

        /* CKEditor compact + toolbar scrollable */
        .ck-editor__editable_inline {
            min-height: 260px;
            max-height: 60vh;
            padding: 0.9rem 1rem !important;
            font-size: 0.95rem;
        }
        .ck.ck-toolbar {
            padding: 0.25rem 0.35rem !important;
            overflow-x: auto;
            flex-wrap: nowrap !important;
        }
        .ck.ck-toolbar .ck-toolbar__items { flex-wrap: nowrap; }

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
       RESPONSIVE — TRÈS PETIT MOBILE (≤ 480px)
       ========================================================= */
    @media (max-width: 480px) {
        .index-container { padding: 0.75rem 0.5rem; }
        .index-title { font-size: 1.15rem; }
        .index-title i { font-size: 1rem; }
        .index-subtitle { font-size: 0.78rem; }
        .filter-card { padding: 0.85rem; }
        .section-title { font-size: 0.95rem; }
        .filter-label { font-size: 0.7rem; }
        .field-hint { font-size: 0.72rem; }
        .ck-editor__editable_inline {
            min-height: 220px;
            padding: 0.75rem !important;
        }
        .badge-categorie,
        .badge-statut { font-size: 0.65rem; padding: 0.18rem 0.5rem; }
    }
</style>

{{-- CKEditor 5 - Build Classique v41 --}}
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    ClassicEditor
        .create(document.querySelector('#contenu'), {
            language: 'fr',
            placeholder: 'Rédigez ici le contenu du règlement…',

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
                    'link', 'blockQuote', 'insertTable', 'horizontalLine', 'specialCharacters', '|',
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
                    '380px',
                    editor.editing.view.document.getRoot()
                );
            });

            editor.model.document.on('change:data', () => {
                editor.sourceElement?.classList.remove('is-invalid');
            });

            console.log('CKEditor 5 initialisé (règlement — édition)');
        })
        .catch(error => {
            console.error('Erreur CKEditor 5 :', error);
        });
});
</script>
@endsection