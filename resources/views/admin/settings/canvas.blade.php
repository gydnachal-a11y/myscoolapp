@extends('layouts.admin')

@section('content')
<div x-data="headerEditor()" x-init="initCanvas()" class="header-editor-container">
    {{-- CDN FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <div class="header-container">
        <div>
            <h1 class="form-title">Personnalisation de l'<strong>en-tête</strong></h1>
            <p class="form-subtitle">Créez et enregistrez la mise en page du document officiel</p>
        </div>
    </div>

    <div class="toolbar">
        <button @click="addText" class="toolbar-btn">
            <i class="fa-solid fa-font"></i> Texte
        </button>
        <label class="toolbar-btn file-label">
            <i class="fa-solid fa-image"></i> Logo
            <input type="file" accept="image/*" @change="addLogo" class="hidden">
        </label>
        <button @click="deleteSelected" class="toolbar-btn danger">
            <i class="fa-solid fa-trash"></i> Supprimer
        </button>
        <button @click="saveCanvas" class="btn-primary ml-auto">
            <i class="fa-solid fa-check-circle"></i> Enregistrer
        </button>
    </div>

    <div class="canvas-wrapper">
        <canvas id="headerCanvas"></canvas>
    </div>

    <form id="saveForm" action="{{ route('admin.settings.canvas.store') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="canvas_data" id="canvasData">
    </form>
</div>

<style>
    /* ==== Styles locaux premium pour l'éditeur d'en-tête ==== */
    .header-editor-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    .header-container {
        margin-bottom: 2rem;
        animation: fadeUp 0.6s 0.1s ease forwards;
        opacity: 0;
    }
    .form-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
    }
    .form-title strong { font-weight: 800; }
    .form-subtitle { color: #94a3b8; font-size: 0.9rem; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        animation: fadeUp 0.6s 0.2s ease forwards;
        opacity: 0;
    }
    .toolbar-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.6rem 1.25rem;
        background: #f1f5f9;
        border: none;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 500;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s;
    }
    .toolbar-btn:hover { background: #e2e8f0; }
    .toolbar-btn.danger { background: #fee2e2; color: #dc2626; }
    .toolbar-btn.danger:hover { background: #fecaca; }
    .file-label { cursor: pointer; }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.6rem 1.5rem;
        background: #1e293b;
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .btn-primary:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.3); }

    .canvas-wrapper {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        padding: 1.5rem;
        border: 1px solid #f1f5f9;
        animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
        opacity: 0;
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }

    #headerCanvas {
        width: 100%;
        height: auto;
        background: #ffffff;
        border: 1px dashed #e2e8f0;
        border-radius: 10px;
    }

    @media (max-width: 768px) {
        .header-editor-container { padding: 1rem; }
        .toolbar { flex-direction: column; align-items: stretch; }
        .toolbar-btn, .btn-primary { width: 100%; justify-content: center; }
        .btn-primary { margin-left: 0 !important; }
    }
</style>

<script>
    function headerEditor() {
        return {
            canvas: null,
            initCanvas() {
                this.canvas = new fabric.Canvas('headerCanvas', {
                    width: 800,
                    height: 150,
                    backgroundColor: '#ffffff'
                });
                // Charger la mise en page existante
                fetch('{{ route('admin.settings.canvas.load') }}')
                    .then(r => r.json())
                    .then(data => {
                        if (data) {
                            this.canvas.loadFromJSON(data, () => {
                                this.canvas.renderAll();
                            });
                        }
                    });
            },
            addText() {
                const text = new fabric.IText('Nouveau texte', {
                    left: 50,
                    top: 50,
                    fontFamily: 'Arial',
                    fontSize: 16,
                    fill: '#000000'
                });
                this.canvas.add(text);
                this.canvas.setActiveObject(text);
            },
            addLogo(event) {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    fabric.Image.fromURL(e.target.result, (img) => {
                        img.set({
                            left: 10,
                            top: 10,
                            scaleX: 0.5,
                            scaleY: 0.5
                        });
                        this.canvas.add(img);
                        this.canvas.setActiveObject(img);
                    });
                };
                reader.readAsDataURL(file);
            },
            deleteSelected() {
                const obj = this.canvas.getActiveObject();
                if (obj) this.canvas.remove(obj);
            },
            saveCanvas() {
                const json = this.canvas.toJSON();
                document.getElementById('canvasData').value = JSON.stringify(json);
                document.getElementById('saveForm').submit();
            }
        }
    }
</script>
@endsection