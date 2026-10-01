@extends('layouts.admin')

@section('content')
<div x-data="headerEditor()" x-init="init()" class="he-wrapper">

    {{-- ══════════════════════════════════════════════════════
         HEADER
         ══════════════════════════════════════════════════════ --}}
    <div class="he-header">
        <div>
            <h1 class="he-title">Personnalisation de l'<strong>en-tête</strong></h1>
            <p class="he-subtitle">Créez la mise en page du document officiel</p>
        </div>
        <div class="he-header-actions">
            <a href="{{ route('admin.settings.edit') }}" class="he-btn he-btn-ghost" title="Retour">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
            <button @click="undo" :disabled="historyIndex <= 0" class="he-btn he-btn-ghost" title="Annuler (Ctrl+Z)">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
            <button @click="redo" :disabled="historyIndex >= history.length - 1" class="he-btn he-btn-ghost" title="Rétablir (Ctrl+Y)">
                <i class="fa-solid fa-rotate-right"></i>
            </button>
            <span class="he-divider"></span>
            <button @click="showSave = true" class="he-btn he-btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Enregistrer
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TOOLBAR
         ══════════════════════════════════════════════════════ --}}
    <div class="he-toolbar">
        <div class="he-toolbar-group">
            <button @click="addText" class="he-tool" title="Ajouter un texte">
                <i class="fa-solid fa-font"></i>
            </button>
            <label class="he-tool" title="Ajouter une image">
                <i class="fa-solid fa-image"></i>
                <input type="file" accept="image/*" @change="addImage($event)" hidden>
            </label>
            <button @click="addRect" class="he-tool" title="Rectangle">
                <i class="fa-regular fa-square"></i>
            </button>
            <button @click="addCircle" class="he-tool" title="Cercle">
                <i class="fa-regular fa-circle"></i>
            </button>
            <button @click="addTriangle" class="he-tool" title="Triangle">
                <i class="fa-solid fa-play" style="transform: rotate(-90deg);"></i>
            </button>
            <button @click="addLine" class="he-tool" title="Ligne">
                <i class="fa-solid fa-minus"></i>
            </button>
        </div>

        <span class="he-divider"></span>

        <div class="he-toolbar-group">
            <button @click="zoomOut" class="he-tool" title="Zoom -">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
            </button>
            <span class="he-zoom-label" x-text="Math.round(zoom * 100) + '%'"></span>
            <button @click="zoomIn" class="he-tool" title="Zoom +">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
            </button>
            <button @click="resetZoom" class="he-tool" title="Zoom 100%">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>

        <span class="he-divider"></span>

        <div class="he-toolbar-group">
            <select x-model="canvasSizePreset" @change="applyCanvasPreset()" class="he-select">
                <option value="header">Bandeau (800×150)</option>
                <option value="a4">A4 Portrait (794×1123)</option>
                <option value="a4_landscape">A4 Paysage (1123×794)</option>
                <option value="letter">Letter (816×1056)</option>
                <option value="custom">Personnalisé</option>
            </select>
        </div>

        <template x-if="canvasSizePreset === 'custom'">
            <div class="he-toolbar-group">
                <input type="number" min="100" max="2000" x-model.number="customW"
                       @change="applyCustomSize()" class="he-input-sm" placeholder="L">
                <span class="he-x">×</span>
                <input type="number" min="100" max="2000" x-model.number="customH"
                       @change="applyCustomSize()" class="he-input-sm" placeholder="H">
            </div>
        </template>
    </div>

    {{-- ══════════════════════════════════════════════════════
         LAYOUT PRINCIPAL
         ══════════════════════════════════════════════════════ --}}
    <div class="he-main">

        {{-- CANVAS --}}
        <div class="he-canvas-wrapper">
            <div class="he-canvas-container">
                <canvas id="headerCanvas"></canvas>
            </div>
            <div class="he-status">
                <span x-show="selectedObject">
                    <i class="fa-solid fa-crosshairs"></i>
                    X: <strong x-text="Math.round(props.x)"></strong> —
                    Y: <strong x-text="Math.round(props.y)"></strong> —
                    L: <strong x-text="Math.round(props.width * props.scaleX)"></strong> —
                    H: <strong x-text="Math.round(props.height * props.scaleY)"></strong>
                </span>
                <span x-show="!selectedObject" class="he-muted">Aucun élément sélectionné</span>
                <span class="he-divider-v"></span>
                <span>
                    <i class="fa-solid fa-layer-group"></i>
                    <strong x-text="canvas ? canvas.getObjects().length : 0"></strong> élément(s)
                </span>
            </div>
        </div>

        {{-- PANNEAU PROPRIÉTÉS --}}
        <aside class="he-sidebar">

            <template x-if="!selectedObject">
                <div class="he-empty">
                    <i class="fa-solid fa-arrow-pointer"></i>
                    <p>Sélectionnez un élément pour modifier ses propriétés</p>
                    <p class="he-hint">Astuce : utilisez les flèches pour déplacer, Ctrl+D pour dupliquer</p>
                </div>
            </template>

            <template x-if="selectedObject">
                <div class="he-props">

                    {{-- ═══ TEXTE ═══ --}}
                    <template x-if="selectedObject.type === 'i-text' || selectedObject.type === 'text'">
                        <div class="he-panel">
                            <h3 class="he-panel-title">Texte</h3>
                            <div class="he-font-row">
                                <select x-model="textProps.fontFamily"
                                        @change="updateTextProp('fontFamily', textProps.fontFamily)"
                                        class="he-input">
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Courier New">Courier New</option>
                                    <option value="Verdana">Verdana</option>
                                    <option value="Helvetica">Helvetica</option>
                                </select>
                            </div>
                            <div class="he-row-2">
                                <input type="number" min="8" max="200"
                                       x-model.number="textProps.fontSize"
                                       @input="updateTextProp('fontSize', textProps.fontSize)"
                                       class="he-input" placeholder="Taille">
                                <div class="he-color-wrapper">
                                    <input type="color" x-model="textProps.fill"
                                           @input="updateFill(textProps.fill)" class="he-color">
                                </div>
                            </div>
                            <div class="he-btns-row">
                                <button @click="toggleStyle('fontWeight', 'bold')"
                                        :class="{ 'is-active': selectedObject.fontWeight === 'bold' }"
                                        class="he-btn-toggle"><i class="fa-solid fa-bold"></i></button>
                                <button @click="toggleStyle('fontStyle', 'italic')"
                                        :class="{ 'is-active': selectedObject.fontStyle === 'italic' }"
                                        class="he-btn-toggle"><i class="fa-solid fa-italic"></i></button>
                                <button @click="toggleStyle('underline', true)"
                                        :class="{ 'is-active': selectedObject.underline }"
                                        class="he-btn-toggle"><i class="fa-solid fa-underline"></i></button>
                                <button @click="toggleStyle('linethrough', true)"
                                        :class="{ 'is-active': selectedObject.linethrough }"
                                        class="he-btn-toggle"><i class="fa-solid fa-strikethrough"></i></button>
                            </div>
                            <div class="he-btns-row">
                                <button @click="setTextAlign('left')"
                                        :class="{ 'is-active': selectedObject.textAlign === 'left' }"
                                        class="he-btn-toggle"><i class="fa-solid fa-align-left"></i></button>
                                <button @click="setTextAlign('center')"
                                        :class="{ 'is-active': selectedObject.textAlign === 'center' }"
                                        class="he-btn-toggle"><i class="fa-solid fa-align-center"></i></button>
                                <button @click="setTextAlign('right')"
                                        :class="{ 'is-active': selectedObject.textAlign === 'right' }"
                                        class="he-btn-toggle"><i class="fa-solid fa-align-right"></i></button>
                            </div>
                        </div>
                    </template>

                    {{-- ═══ FORME ═══ --}}
                    <template x-if="['rect', 'circle', 'triangle', 'line', 'path'].includes(selectedObject.type)">
                        <div class="he-panel">
                            <h3 class="he-panel-title">Forme</h3>
                            <label class="he-label">Remplissage</label>
                            <div class="he-row-2">
                                <input type="color" x-model="shapeProps.fill"
                                       @input="updateFill(shapeProps.fill)" class="he-color">
                                <input type="text" x-model="shapeProps.fill"
                                       @input="updateFill(shapeProps.fill)" class="he-input">
                            </div>
                            <label class="he-label">Contour</label>
                            <div class="he-row-2">
                                <input type="color" x-model="shapeProps.stroke"
                                       @input="updateStroke(shapeProps.stroke)" class="he-color">
                                <input type="number" min="0" max="20"
                                       x-model.number="shapeProps.strokeWidth"
                                       @input="updateStrokeWidth(shapeProps.strokeWidth)"
                                       class="he-input" placeholder="Épaisseur">
                            </div>
                        </div>
                    </template>

                    {{-- ═══ POSITION ═══ --}}
                    <div class="he-panel">
                        <h3 class="he-panel-title">Position</h3>
                        <div class="he-row-2">
                            <input type="number" x-model.number="props.x"
                                   @change="updatePos('left', props.x)" class="he-input" placeholder="X">
                            <input type="number" x-model.number="props.y"
                                   @change="updatePos('top', props.y)" class="he-input" placeholder="Y">
                        </div>
                        <div class="he-row-2">
                            <input type="number" x-model.number="props.angle"
                                   @change="updateAngle(props.angle)" class="he-input" placeholder="Rotation °">
                            <input type="number" min="0" max="100" x-model.number="props.opacity"
                                   @change="updateOpacity(props.opacity / 100)"
                                   class="he-input" placeholder="Opacité %">
                        </div>
                    </div>

                    {{-- ═══ ALIGNEMENT ═══ --}}
                    <div class="he-panel">
                        <h3 class="he-panel-title">Alignement</h3>
                        <div class="he-btns-row">
                            <button @click="alignH('left')" class="he-btn-toggle" title="Gauche">
                                <i class="fa-solid fa-align-left"></i>
                            </button>
                            <button @click="alignH('center')" class="he-btn-toggle" title="Centrer H">
                                <i class="fa-solid fa-align-center"></i>
                            </button>
                            <button @click="alignH('right')" class="he-btn-toggle" title="Droite">
                                <i class="fa-solid fa-align-right"></i>
                            </button>
                        </div>
                        <div class="he-btns-row">
                            <button @click="alignV('top')" class="he-btn-toggle" title="Haut">
                                <i class="fa-solid fa-arrow-up"></i>
                            </button>
                            <button @click="alignV('middle')" class="he-btn-toggle" title="Centrer V">
                                <i class="fa-solid fa-arrows-up-down"></i>
                            </button>
                            <button @click="alignV('bottom')" class="he-btn-toggle" title="Bas">
                                <i class="fa-solid fa-arrow-down"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ═══ CALQUES ═══ --}}
                    <div class="he-panel">
                        <h3 class="he-panel-title">Calques</h3>
                        <div class="he-btns-row">
                            <button @click="bringForward" class="he-btn-toggle" title="Avancer">
                                <i class="fa-solid fa-arrow-up"></i>
                            </button>
                            <button @click="bringToFront" class="he-btn-toggle" title="Tout devant">
                                <i class="fa-solid fa-angles-up"></i>
                            </button>
                            <button @click="sendBackward" class="he-btn-toggle" title="Reculer">
                                <i class="fa-solid fa-arrow-down"></i>
                            </button>
                            <button @click="sendToBack" class="he-btn-toggle" title="Tout derrière">
                                <i class="fa-solid fa-angles-down"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ═══ ACTIONS ═══ --}}
                    <div class="he-panel">
                        <div class="he-btns-row">
                            <button @click="duplicate" class="he-btn-action" title="Dupliquer">
                                <i class="fa-regular fa-copy"></i> Dupliquer
                            </button>
                            <button @click="deleteSelected" class="he-btn-action he-danger" title="Supprimer">
                                <i class="fa-solid fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>

                </div>
            </template>
        </aside>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL ENREGISTREMENT
         ══════════════════════════════════════════════════════ --}}
    <div x-show="showSave" x-cloak @click.self="showSave = false" class="he-modal-backdrop">
        <div class="he-modal">
            <h3>Enregistrer l'en-tête</h3>
            <p>La mise en page sera appliquée à tous les documents officiels.</p>
            <div class="he-modal-actions">
                <button @click="showSave = false" class="he-btn he-btn-ghost">Annuler</button>
                <button @click="saveCanvas" class="he-btn he-btn-primary">
                    <i class="fa-solid fa-check"></i> Confirmer
                </button>
            </div>
        </div>
    </div>

    {{-- Form caché pour la sauvegarde --}}
    <form id="saveForm" action="{{ route('admin.settings.canvas.store') }}" method="POST" hidden>
        @csrf
        <input type="hidden" name="canvas_data" id="canvasData">
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════
     CDN — FontAwesome + Fabric.js
     ══════════════════════════════════════════════════════════ --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>

<style>
    /* ═══════════════════════════════════════════════════════
       BASE
       ═══════════════════════════════════════════════════════ */
    [x-cloak] { display: none !important; }
    .he-wrapper { max-width: 1400px; margin: 0 auto; padding: 1.25rem 1rem; }

    /* ═══════════════════════════════════════════════════════
       HEADER
       ═══════════════════════════════════════════════════════ */
    .he-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
    }
    .he-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0 0 0.2rem; }
    .he-title strong { font-weight: 800; color: #4f46e5; }
    .he-subtitle { color: #94a3b8; font-size: 0.9rem; margin: 0; }
    .he-header-actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

    /* ═══════════════════════════════════════════════════════
       BOUTONS
       ═══════════════════════════════════════════════════════ */
    .he-btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.45rem; padding: 0.6rem 1rem; border-radius: 10px;
        font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer;
        transition: all 0.2s; white-space: nowrap; text-decoration: none;
    }
    .he-btn-ghost { background: #f1f5f9; color: #475569; }
    .he-btn-ghost:hover:not(:disabled) { background: #e2e8f0; }
    .he-btn-ghost:disabled { opacity: 0.4; cursor: not-allowed; }
    .he-btn-primary {
        background: #4f46e5; color: #fff;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .he-btn-primary:hover {
        background: #4338ca; transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
    }

    /* ═══════════════════════════════════════════════════════
       TOOLBAR
       ═══════════════════════════════════════════════════════ */
    .he-toolbar {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem;
        background: #fff; padding: 0.6rem 0.75rem; border-radius: 14px;
        border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        margin-bottom: 1rem;
    }
    .he-toolbar-group { display: flex; align-items: center; gap: 0.25rem; }
    .he-tool {
        width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
        background: transparent; border: none; border-radius: 8px; color: #475569;
        font-size: 0.95rem; cursor: pointer; transition: all 0.2s;
    }
    .he-tool:hover { background: #f1f5f9; color: #4f46e5; }
    .he-divider {
        width: 1px; height: 22px; background: #e5e7eb;
        margin: 0 0.4rem; flex-shrink: 0;
    }
    .he-divider-v {
        width: 1px; height: 14px; background: #e5e7eb;
        margin: 0 0.5rem; display: inline-block; vertical-align: middle;
    }
    .he-zoom-label {
        min-width: 48px; text-align: center;
        font-size: 0.8rem; font-weight: 600; color: #475569;
    }
    .he-select {
        padding: 0.45rem 0.8rem; border: 1px solid #e5e7eb; border-radius: 8px;
        background: #fff; font-size: 0.85rem; color: #475569; cursor: pointer;
    }
    .he-select:focus { outline: 2px solid #4f46e5; outline-offset: 1px; }
    .he-input-sm {
        width: 70px; padding: 0.4rem 0.5rem; border: 1px solid #e5e7eb;
        border-radius: 8px; font-size: 0.8rem; text-align: center;
    }
    .he-x { color: #94a3b8; font-size: 0.8rem; }

    /* ═══════════════════════════════════════════════════════
       LAYOUT
       ═══════════════════════════════════════════════════════ */
    .he-main {
        display: grid; grid-template-columns: 1fr 300px; gap: 1rem;
        align-items: start;
    }
    .he-canvas-wrapper {
        background: #fff; border-radius: 16px; padding: 1rem;
        border: 1px solid #e5e7eb; box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        min-width: 0;
    }
    .he-canvas-container {
        display: flex; justify-content: center; align-items: center;
        overflow: auto; padding: 0.5rem;
        background: #f9fafb; border-radius: 10px;
        min-height: 300px;
        max-height: 70vh;
    }
    #headerCanvas {
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 4px;
        max-width: 100%;
    }
    .he-status {
        display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem;
        margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #f1f5f9;
        font-size: 0.78rem; color: #64748b; font-variant-numeric: tabular-nums;
    }
    .he-status i { color: #94a3b8; margin-right: 0.25rem; }
    .he-status strong { color: #1e293b; font-weight: 600; }
    .he-muted { color: #cbd5e1; }

    /* ═══════════════════════════════════════════════════════
       SIDEBAR
       ═══════════════════════════════════════════════════════ */
    .he-sidebar {
        background: #fff; border-radius: 16px; border: 1px solid #e5e7eb;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04); overflow: hidden;
        position: sticky; top: 1rem; max-height: calc(100vh - 2rem);
        overflow-y: auto;
    }
    .he-empty {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 2.5rem 1.5rem; text-align: center; color: #94a3b8;
    }
    .he-empty i { font-size: 2rem; margin-bottom: 0.75rem; opacity: 0.5; }
    .he-empty p { margin: 0 0 0.5rem; font-size: 0.85rem; line-height: 1.5; }
    .he-hint {
        font-size: 0.75rem !important;
        color: #cbd5e1 !important;
        font-style: italic;
    }

    .he-panel { padding: 1rem 1.1rem; border-bottom: 1px solid #f1f5f9; }
    .he-panel:last-child { border-bottom: none; }
    .he-panel-title {
        font-size: 0.7rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.05em;
        color: #94a3b8; margin: 0 0 0.75rem;
    }
    .he-label {
        display: block; font-size: 0.75rem; font-weight: 600;
        color: #475569; margin-bottom: 0.35rem;
    }

    /* Inputs */
    .he-input {
        width: 100%; padding: 0.5rem 0.65rem;
        border: 1px solid #e5e7eb; border-radius: 8px;
        font-size: 0.85rem; color: #1e293b;
        background: #f9fafb; transition: all 0.15s;
        box-sizing: border-box;
    }
    .he-input:focus {
        outline: none; border-color: #4f46e5; background: #fff;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .he-font-row { margin-bottom: 0.5rem; }
    .he-row-2 {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 0.5rem; margin-bottom: 0.5rem;
    }
    .he-row-2:last-child { margin-bottom: 0; }

    .he-color-wrapper { position: relative; }
    .he-color {
        width: 100%; height: 38px;
        border: 1px solid #e5e7eb; border-radius: 8px;
        cursor: pointer; background: #fff; padding: 2px;
    }
    .he-color::-webkit-color-swatch-wrapper { padding: 2px; }
    .he-color::-webkit-color-swatch { border: none; border-radius: 5px; }

    .he-btns-row {
        display: flex; gap: 0.35rem; flex-wrap: wrap;
        margin-bottom: 0.5rem;
    }
    .he-btns-row:last-child { margin-bottom: 0; }

    .he-btn-toggle {
        flex: 1; min-width: 36px; height: 36px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;
        color: #475569; font-size: 0.85rem; cursor: pointer; transition: all 0.15s;
    }
    .he-btn-toggle:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .he-btn-toggle.is-active {
        background: #4f46e5; border-color: #4f46e5; color: #fff;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
    }

    .he-btn-action {
        flex: 1; display: inline-flex; align-items: center; justify-content: center;
        gap: 0.4rem; padding: 0.55rem 0.75rem; border-radius: 8px;
        font-size: 0.8rem; font-weight: 600;
        background: #f1f5f9; color: #475569; border: none;
        cursor: pointer; transition: all 0.15s;
    }
    .he-btn-action:hover { background: #e2e8f0; }
    .he-danger { background: #fee2e2; color: #dc2626; }
    .he-danger:hover { background: #fecaca; }

    /* ═══════════════════════════════════════════════════════
       MODAL
       ═══════════════════════════════════════════════════════ */
    .he-modal-backdrop {
        position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5);
        z-index: 9999; display: flex; align-items: center; justify-content: center;
        padding: 1rem; backdrop-filter: blur(4px);
    }
    .he-modal {
        background: #fff; border-radius: 16px; padding: 1.5rem;
        max-width: 420px; width: 100%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    }
    .he-modal h3 {
        margin: 0 0 0.5rem; font-size: 1.1rem;
        color: #1e293b; font-weight: 700;
    }
    .he-modal p {
        margin: 0 0 1.25rem; font-size: 0.875rem; color: #64748b;
    }
    .he-modal-actions {
        display: flex; gap: 0.5rem; justify-content: flex-end;
    }

    /* ═══════════════════════════════════════════════════════
       RESPONSIVE
       ═══════════════════════════════════════════════════════ */
    @media (max-width: 1024px) {
        .he-main { grid-template-columns: 1fr; }
        .he-sidebar { position: static; max-height: none; }
    }
    @media (max-width: 640px) {
        .he-wrapper { padding: 1rem 0.75rem; }
        .he-header { flex-direction: column; align-items: stretch; }
        .he-header-actions { justify-content: flex-start; }
        .he-title { font-size: 1.2rem; }
        .he-toolbar { padding: 0.5rem; }
        .he-tool { width: 34px; height: 34px; font-size: 0.85rem; }
        .he-canvas-wrapper { padding: 0.75rem; }
        .he-status { font-size: 0.7rem; }
        .he-btn { padding: 0.5rem 0.75rem; font-size: 0.8rem; }
    }
</style>

<script>
function headerEditor() {
    return {
        canvas: null,
        selectedObject: null,
        showSave: false,
        zoom: 1,
        canvasSizePreset: 'header',
        customW: 800,
        customH: 150,
        history: [],
        historyIndex: -1,
        isRestoringHistory: false,

        props: { x: 0, y: 0, width: 0, height: 0, scaleX: 1, scaleY: 1, angle: 0, opacity: 100 },
        textProps: { fontFamily: 'Arial', fontSize: 16, fill: '#000000' },
        shapeProps: { fill: '#4f46e5', stroke: '#1e293b', strokeWidth: 0 },

        // ============================================================
        // INIT
        // ============================================================
        init() {
            this.$nextTick(() => {
                this.initCanvas();
                this.bindKeyboard();
            });
        },

        initCanvas() {
            this.canvas = new fabric.Canvas('headerCanvas', {
                width: 800,
                height: 150,
                backgroundColor: '#ffffff',
                preserveObjectStacking: true,
            });

            this.canvas.on('selection:created', () => this.syncSelection());
            this.canvas.on('selection:updated', () => this.syncSelection());
            this.canvas.on('selection:cleared', () => {
                this.selectedObject = null;
            });

            this.canvas.on('object:modified', () => {
                this.syncSelection();
                this.pushHistory();
            });
            this.canvas.on('object:added', () => {
                if (!this.isRestoringHistory) this.pushHistory();
            });
            this.canvas.on('object:removed', () => {
                if (!this.isRestoringHistory) this.pushHistory();
            });

            this.loadExisting();
            this.pushHistory();
        },

        loadExisting() {
            fetch('{{ route('admin.settings.canvas.load') }}')
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (data && data.objects && data.objects.length) {
                        this.canvas.loadFromJSON(data, () => {
                            this.canvas.renderAll();
                            this.pushHistory();
                        });
                    }
                })
                .catch(() => { /* silencieux */ });
        },

        // ============================================================
        // SÉLECTION
        // ============================================================
        syncSelection() {
            const obj = this.canvas.getActiveObject();
            this.selectedObject = obj;
            if (!obj) return;

            this.props.x = obj.left || 0;
            this.props.y = obj.top || 0;
            this.props.width = obj.width || 0;
            this.props.height = obj.height || 0;
            this.props.scaleX = obj.scaleX || 1;
            this.props.scaleY = obj.scaleY || 1;
            this.props.angle = obj.angle || 0;
            this.props.opacity = (obj.opacity ?? 1) * 100;

            if (obj.type === 'i-text' || obj.type === 'text') {
                this.textProps.fontFamily = obj.fontFamily || 'Arial';
                this.textProps.fontSize = obj.fontSize || 16;
                this.textProps.fill = obj.fill || '#000000';
            }

            if (['rect', 'circle', 'triangle', 'line', 'path'].includes(obj.type)) {
                this.shapeProps.fill = typeof obj.fill === 'string' ? obj.fill : '#4f46e5';
                this.shapeProps.stroke = typeof obj.stroke === 'string' ? obj.stroke : '#1e293b';
                this.shapeProps.strokeWidth = obj.strokeWidth || 0;
            }
        },

        // ============================================================
        // AJOUT
        // ============================================================
        addText() {
            const t = new fabric.IText('Double-cliquez pour modifier', {
                left: 50, top: 50,
                fontFamily: 'Arial', fontSize: 16, fill: '#1e293b',
            });
            this.canvas.add(t).setActiveObject(t);
            this.canvas.renderAll();
        },

        addImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                fabric.Image.fromURL(e.target.result, (img) => {
                    const maxW = 200;
                    if (img.width > maxW) {
                        img.scale(maxW / img.width);
                    }
                    img.set({ left: 20, top: 20 });
                    this.canvas.add(img).setActiveObject(img);
                    this.canvas.renderAll();
                });
            };
            reader.readAsDataURL(file);
            event.target.value = '';
        },

        addRect() {
            const r = new fabric.Rect({
                left: 100, top: 40, width: 120, height: 60,
                fill: '#4f46e5', rx: 6, ry: 6, opacity: 0.9,
            });
            this.canvas.add(r).setActiveObject(r);
        },

        addCircle() {
            const c = new fabric.Circle({
                left: 100, top: 40, radius: 40,
                fill: '#4f46e5', opacity: 0.9,
            });
            this.canvas.add(c).setActiveObject(c);
        },

        addTriangle() {
            const t = new fabric.Triangle({
                left: 100, top: 40, width: 80, height: 80,
                fill: '#4f46e5', opacity: 0.9,
            });
            this.canvas.add(t).setActiveObject(t);
        },

        addLine() {
            const l = new fabric.Line([0, 0, 300, 0], {
                left: 100, top: 80,
                stroke: '#1e293b', strokeWidth: 2,
            });
            this.canvas.add(l).setActiveObject(l);
        },

        // ============================================================
        // SUPPRESSION / DUPLICATION
        // ============================================================
        deleteSelected() {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            this.canvas.remove(obj);
            this.canvas.discardActiveObject();
            this.canvas.renderAll();
            this.selectedObject = null;
        },

        async duplicate() {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            const cloned = await obj.clone();
            cloned.set({ left: obj.left + 20, top: obj.top + 20 });
            this.canvas.add(cloned).setActiveObject(cloned);
            this.canvas.renderAll();
        },

        // ============================================================
        // FORMATAGE
        // ============================================================
        updateTextProp(key, value) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            obj.set(key, value);
            this.canvas.renderAll();
        },

        updateFill(color) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            obj.set('fill', color);
            this.canvas.renderAll();
        },

        updateStroke(color) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            obj.set('stroke', color);
            this.canvas.renderAll();
        },

        updateStrokeWidth(w) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            obj.set('strokeWidth', w);
            this.canvas.renderAll();
        },

        toggleStyle(key, value) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            const current = obj.get(key);
            obj.set(key, current === value ? '' : value);
            this.canvas.renderAll();
            this.syncSelection();
        },

        setTextAlign(align) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            obj.set('textAlign', align);
            this.canvas.renderAll();
        },

        // ============================================================
        // POSITION
        // ============================================================
        updatePos(key, value) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            obj.set(key, value);
            obj.setCoords();
            this.canvas.renderAll();
        },

        updateAngle(a) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            obj.set('angle', a);
            obj.setCoords();
            this.canvas.renderAll();
        },

        updateOpacity(o) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            obj.set('opacity', o);
            this.canvas.renderAll();
        },

        // ============================================================
        // ALIGNEMENT
        // ============================================================
        alignH(pos) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            const cw = this.canvas.getWidth();
            const ow = obj.getScaledWidth();
            const left = pos === 'left' ? 0 : pos === 'center' ? (cw - ow) / 2 : cw - ow;
            obj.set('left', left);
            obj.setCoords();
            this.canvas.renderAll();
            this.syncSelection();
        },

        alignV(pos) {
            const obj = this.canvas.getActiveObject();
            if (!obj) return;
            const ch = this.canvas.getHeight();
            const oh = obj.getScaledHeight();
            const top = pos === 'top' ? 0 : pos === 'middle' ? (ch - oh) / 2 : ch - oh;
            obj.set('top', top);
            obj.setCoords();
            this.canvas.renderAll();
            this.syncSelection();
        },

        // ============================================================
        // CALQUES
        // ============================================================
        bringForward() { const o = this.canvas.getActiveObject(); if (o) this.canvas.bringForward(o); },
        sendBackward() { const o = this.canvas.getActiveObject(); if (o) this.canvas.sendBackwards(o); },
        bringToFront() { const o = this.canvas.getActiveObject(); if (o) this.canvas.bringToFront(o); },
        sendToBack() { const o = this.canvas.getActiveObject(); if (o) this.canvas.sendToBack(o); },

        // ============================================================
        // ZOOM
        // ============================================================
        zoomIn()  { this.setZoom(Math.min(3, this.zoom + 0.1)); },
        zoomOut() { this.setZoom(Math.max(0.3, this.zoom - 0.1)); },
        resetZoom() { this.setZoom(1); },
        setZoom(v) {
            this.zoom = v;
            this.canvas.setZoom(v);
            this.canvas.renderAll();
        },

        // ============================================================
        // TAILLE CANVAS
        // ============================================================
        applyCanvasPreset() {
            const presets = {
                a4: { w: 794, h: 1123 },
                a4_landscape: { w: 1123, h: 794 },
                letter: { w: 816, h: 1056 },
                header: { w: 800, h: 150 },
            };
            const p = presets[this.canvasSizePreset];
            if (p) {
                this.canvas.setWidth(p.w);
                this.canvas.setHeight(p.h);
                this.canvas.renderAll();
            }
        },

        applyCustomSize() {
            if (this.canvasSizePreset !== 'custom') return;
            if (this.customW > 0 && this.customH > 0) {
                this.canvas.setWidth(this.customW);
                this.canvas.setHeight(this.customH);
                this.canvas.renderAll();
            }
        },

        // ============================================================
        // HISTORIQUE
        // ============================================================
        pushHistory() {
            if (this.isRestoringHistory) return;
            const json = JSON.stringify(this.canvas.toJSON());
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(json);
            this.historyIndex = this.history.length - 1;
            if (this.history.length > 50) {
                this.history.shift();
                this.historyIndex--;
            }
        },

        undo() {
            if (this.historyIndex <= 0) return;
            this.historyIndex--;
            this.restoreHistory();
        },

        redo() {
            if (this.historyIndex >= this.history.length - 1) return;
            this.historyIndex++;
            this.restoreHistory();
        },

        restoreHistory() {
            this.isRestoringHistory = true;
            const json = this.history[this.historyIndex];
            this.canvas.loadFromJSON(json, () => {
                this.canvas.renderAll();
                this.selectedObject = null;
                this.isRestoringHistory = false;
            });
        },

        // ============================================================
        // RACCOURCIS CLAVIER
        // ============================================================
        bindKeyboard() {
            document.addEventListener('keydown', (e) => {
                const tag = (e.target.tagName || '').toLowerCase();
                if (['input', 'textarea', 'select'].includes(tag)) return;
                if (this.canvas && this.canvas.getActiveObject()?.isEditing) return;

                const ctrl = e.ctrlKey || e.metaKey;

                if (ctrl && e.key.toLowerCase() === 'z' && !e.shiftKey) {
                    e.preventDefault(); this.undo();
                }
                if (ctrl && (e.key.toLowerCase() === 'y' || (e.key.toLowerCase() === 'z' && e.shiftKey))) {
                    e.preventDefault(); this.redo();
                }
                if (ctrl && e.key.toLowerCase() === 'd') {
                    e.preventDefault(); this.duplicate();
                }
                if (ctrl && e.key.toLowerCase() === 's') {
                    e.preventDefault(); this.showSave = true;
                }
                if (e.key === 'Delete' || e.key === 'Backspace') {
                    e.preventDefault(); this.deleteSelected();
                }
                if (e.key === 'Escape') {
                    this.canvas.discardActiveObject();
                    this.canvas.renderAll();
                }

                const obj = this.canvas.getActiveObject();
                if (obj && ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                    const step = e.shiftKey ? 10 : 1;
                    if (e.key === 'ArrowUp')    obj.set('top',  obj.top - step);
                    if (e.key === 'ArrowDown')  obj.set('top',  obj.top + step);
                    if (e.key === 'ArrowLeft')  obj.set('left', obj.left - step);
                    if (e.key === 'ArrowRight') obj.set('left', obj.left + step);
                    obj.setCoords();
                    this.canvas.renderAll();
                    this.syncSelection();
                }
            });
        },

        // ============================================================
        // SAUVEGARDE
        // ============================================================
        saveCanvas() {
            const json = this.canvas.toJSON(['selectable', 'evented']);
            document.getElementById('canvasData').value = JSON.stringify(json);
            document.getElementById('saveForm').submit();
        },
    };
}
</script>
@endsection