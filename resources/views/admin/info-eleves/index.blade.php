@extends('layouts.admin')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Info élèves</strong></h1>
            <p class="index-subtitle">Liste des élèves par option, salle, section et session</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.eleves.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nouvel élève
            </a>
        </div>
    </div>

    {{-- Résumé rapide --}}
    <div class="stats-row">
        <div class="stat-card">
            <i class="fa-solid fa-users text-indigo-500"></i>
            <div>
                <span class="stat-number">{{ $inscriptions->total() }}</span>
                <span class="stat-label">Inscriptions</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-door-open text-emerald-500"></i>
            <div>
                <span class="stat-number">{{ $salles->count() }}</span>
                <span class="stat-label">Salles de classe</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-cog text-amber-500"></i>
            <div>
                <span class="stat-number">{{ $options->count() }}</span>
                <span class="stat-label">Options</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-layer-group text-purple-500"></i>
            <div>
                <span class="stat-number">{{ $sections->count() }}</span>
                <span class="stat-label">Sections</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fa-solid fa-calendar-alt text-cyan-500"></i>
            <div>
                <span class="stat-number">{{ $sessions->count() }}</span>
                <span class="stat-label">Sessions</span>
            </div>
        </div>
    </div>

    {{-- Barre d'outils avec exports et impression --}}
    <div class="toolbar no-print">
        <div class="toolbar-group">
            <span class="toolbar-label">Exports :</span>
            <a href="{{ route('admin.info-eleves.export.pdf', request()->query()) }}" class="export-btn pdf" title="Télécharger PDF">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route('admin.info-eleves.export.csv', request()->query()) }}" class="export-btn csv" title="Télécharger CSV">
                <i class="fa-solid fa-file-excel"></i> CSV
            </a>
            <a href="{{ route('admin.info-eleves.export.xml', request()->query()) }}" class="export-btn xml" title="Télécharger XML">
                <i class="fa-solid fa-file-code"></i> XML
            </a>
            <a href="{{ route('admin.info-eleves.export.word', request()->query()) }}" class="export-btn doc" title="Télécharger DOC">
                <i class="fa-solid fa-file-word"></i> DOC
            </a>
            <a href="{{ route('admin.info-eleves.imprimer', request()->query()) }}" target="_blank" class="export-btn print" title="Imprimer">
                <i class="fa-solid fa-print"></i> Imprimer
            </a>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="filter-card no-print" x-data="infoElevesFiltre()">
        <form method="GET" action="{{ route('admin.info-eleves.index') }}" class="filter-form" id="filterForm">
            <div class="filter-field">
                <label class="filter-label">Session</label>
                <select name="session" x-model="session" class="filter-select" @change="filtrerSections(); submitForm()">
                    <option value="">Toutes les sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ request('session') == $session->id ? 'selected' : '' }}>
                            {{ $session->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label">Section</label>
                <select name="section" x-model="section" id="section_select" class="filter-select" @change="filtrerOptionsEtSalles(); submitForm()">
                    <option value="">Toutes les sections</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" data-session-id="{{ $section->session_id }}"
                                {{ request('section') == $section->id ? 'selected' : '' }}>
                            {{ $section->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label">Option</label>
                <select name="option" x-model="option" id="option_select" class="filter-select" @change="filtrerSalles()">
                    <option value="">Toutes les options</option>
                    @foreach($options as $opt)
                        <option value="{{ $opt->id }}" data-section-ids="{{ json_encode($opt->sallesDeClasse->pluck('section.id')->unique()->values()->toArray()) }}"
                                {{ request('option') == $opt->id ? 'selected' : '' }}>
                            {{ $opt->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label">Salle de classe</label>
                <select name="salle" x-model="salle" id="salle_select" class="filter-select" @change="submitSalle()">
                    <option value="">Toutes les salles</option>
                    @foreach($salles as $salle)
                        <option value="{{ $salle->id }}"
                                data-option-id="{{ $salle->option_id }}"
                                data-section-id="{{ $salle->section_id }}"
                                {{ request('salle') == $salle->id ? 'selected' : '' }}>
                            {{ $salle->nom }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label">Recherche</label>
                <input type="text" name="recherche" value="{{ request('recherche') }}" placeholder="Nom, prénom..." class="filter-input">
            </div>
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
            @if(request('session') || request('section') || request('option') || request('salle') || request('recherche'))
                <a href="{{ route('admin.info-eleves.index') }}" class="btn-reset">Réinitialiser</a>
            @endif
        </form>
    </div>

    {{-- Tableau des élèves --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Élève</th>
                    <th>Sexe</th>
                    <th>Âge</th>
                    <th>Session</th>
                    <th>Section</th>
                    <th>Année</th>
                    <th>Salle</th>
                    <th>Option</th>
                    <th class="text-right no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inscriptions as $ins)
                    @php
                        $eleve = $ins->eleve;
                        $age = $eleve?->date_naissance?->age;
                        $salle = $ins->salleDeClasse;
                        $section = $salle?->section;
                        $session = $section?->session;
                    @endphp
                    <tr>
                        <td data-label="Élève">
                            <div class="insc-cell">
                                <div class="avatar">{{ strtoupper(substr($eleve?->nom ?? '?', 0, 1) . substr($eleve?->prenom ?? '?', 0, 1)) }}</div>
                                <span class="cell-primary">{{ $eleve?->nom }} {{ $eleve?->prenom }}</span>
                            </div>
                        </td>
                        <td data-label="Sexe">{{ $eleve?->sexe_libelle ?? '—' }}</td>
                        <td data-label="Âge">{{ $age !== null ? $age . ' ans' : '—' }}</td>
                        <td data-label="Session">{{ $session?->nom ?? '—' }}</td>
                        <td data-label="Section">{{ $section?->nom ?? '—' }}</td>
                        <td data-label="Année">{{ optional($ins->anneeScolaire)->libelle ?? '—' }}</td>
                        <td data-label="Salle">{{ $salle?->nom ?? '—' }}</td>
                        <td data-label="Option">{{ $salle?->option?->nom ?? '—' }}</td>
                        <td data-label="Actions" class="text-right action-cell no-print">
                            <div class="action-icons">
                                <a href="{{ route('admin.info-eleves.show', $ins->eleve_id) }}" class="action-icon" title="Voir fiche complète">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-cell">
                            Aucun élève trouvé avec ces critères.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination non centrée --}}
    <div class="pagination-wrapper no-print">
        {{ $inscriptions->appends(request()->query())->links() }}
    </div>
</div>

<style>
    /* ==== Styles communs ==== */
    .index-container { max-width: 1300px; margin: 0 auto; padding: 2rem 1rem; }
    .index-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.25rem; margin-bottom: 2rem; }
    .index-title { font-size: 1.75rem; font-weight: 700; color: #1e293b; margin-bottom: 0.2rem; letter-spacing: -0.5px; }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: #94a3b8; font-size: 0.95rem; }
    .flex { display: flex; }
    .gap-2 { gap: 0.5rem; }
    .btn-primary { display: inline-flex; align-items: center; gap: 8px; padding: 0.7rem 1.5rem; border-radius: 12px; font-weight: 600; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; background: #1e293b; color: white; }
    .btn-primary:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }

    /* ==== Résumé statistique ==== */
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; }
    .stat-card i { font-size: 2rem; }
    .stat-card .stat-number { font-size: 1.5rem; font-weight: 700; color: #1e293b; display: block; }
    .stat-card .stat-label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }

    /* ==== Barre d'outils / exports ==== */
    .toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; margin-bottom: 1.5rem; background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); padding: 0.75rem 1.25rem; }
    .toolbar-group { display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
    .toolbar-label { font-size: 0.8rem; font-weight: 600; color: #64748b; }
    .export-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 0.6rem 1rem; border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: white; text-decoration: none; transition: all 0.2s; min-width: 80px; }
    .export-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
    .export-btn.pdf { background: #ef4444; }
    .export-btn.csv { background: #22c55e; }
    .export-btn.xml { background: #3b82f6; }
    .export-btn.doc { background: #0ea5e9; }
    .export-btn.print { background: #475569; }

    /* ==== Filtres ==== */
    .filter-card { background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); padding: 1.25rem; margin-bottom: 2rem; }
    .filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
    .filter-field { display: flex; flex-direction: column; gap: 0.5rem; flex: 1; min-width: 160px; }
    .filter-label { font-size: 0.8rem; font-weight: 600; color: #475569; }
    .filter-input, .filter-select { width: 100%; padding: 0.7rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.95rem; color: #1e293b; transition: border-color 0.3s; outline: none; }
    .filter-input:focus, .filter-select:focus { border-color: #667eea; background: white; }
    .btn-filter, .btn-reset { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 0.7rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; border: none; }
    .btn-filter { background: #1e293b; color: white; }
    .btn-filter:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.3); }
    .btn-reset { background: white; border: 1.5px solid #e2e8f0; color: #64748b; }
    .btn-reset:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    /* ==== Tableau ==== */
    .table-card { background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); overflow: hidden; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: #475569; }
    .data-table thead th { text-align: left; padding: 0.9rem 1.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .data-table tbody td { padding: 0.9rem 1.5rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .text-right { text-align: right; }
    .insc-cell { display: flex; align-items: center; gap: 0.75rem; }
    .avatar { width: 36px; height: 36px; border-radius: 10px; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; flex-shrink: 0; }
    .cell-primary { font-weight: 600; color: #1e293b; }
    .action-cell { white-space: nowrap; }
    .action-icons { display: flex; justify-content: flex-end; gap: 0.5rem; }
    .action-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; color: #64748b; text-decoration: none; transition: all 0.2s; background: none; border: none; cursor: pointer; font-size: 1rem; }
    .action-icon:hover { background: #e2e8f0; color: #1e293b; }
    .empty-cell { text-align: center; padding: 3rem; color: #94a3b8; }
    .pagination-wrapper { margin-top: 1.5rem; } /* Pagination alignée à gauche par défaut */

    /* ==== Responsive ==== */
    @media (max-width: 1024px) {
        .toolbar { flex-direction: column; align-items: stretch; }
        .toolbar-group { width: 100%; flex-direction: column; align-items: stretch; }
        .toolbar-label { margin-bottom: 0.5rem; }
        .export-btn { width: 100%; }
        .filter-form { flex-direction: column; align-items: stretch; }
        .filter-field { min-width: auto; }
        .btn-filter, .btn-reset { width: 100%; }
    }

    @media (max-width: 640px) {
        .index-header { flex-direction: column; align-items: flex-start; }
        .index-title { font-size: 1.5rem; }
        .index-subtitle { font-size: 0.85rem; }
        .stats-row { grid-template-columns: 1fr; }
        .toolbar { padding: 0.5rem 1rem; }
        .export-btn { min-width: auto; padding: 0.5rem; font-size: 0.75rem; }
        .filter-card { padding: 1rem; }
        .table-card { background: transparent; box-shadow: none; border-radius: 0; overflow: visible; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table thead { display: none; }
        .data-table tr { background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); margin-bottom: 1.25rem; padding: 1rem; }
        .data-table td { border: none; padding: 0.5rem 0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .data-table td::before { content: attr(data-label); font-weight: 600; color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; min-width: 100px; }
        .data-table td[data-label="Actions"] { justify-content: flex-end; }
        .data-table td[data-label="Actions"]::before { display: none; }
        .action-icons { justify-content: flex-end; gap: 0.5rem; }
        .insc-cell { justify-content: flex-start; }
        .pagination-wrapper { text-align: left; } /* Alignement gauche aussi sur mobile */
    }

    /* ==== Impression ==== */
    @media print {
        .no-print { display: none !important; }
        body { background: white !important; }
        .index-container { max-width: 100%; padding: 0; }
        .table-card { box-shadow: none; border-radius: 0; }
        .data-table { font-size: 0.8rem; }
        .data-table thead th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; }
        .data-table tbody tr:nth-child(even) { background: #f8fafc; }
        .data-table tbody td { padding: 0.5rem; }
    }
</style>

<script>
    function infoElevesFiltre() {
        return {
            session: '{{ request('session') }}',
            section: '{{ request('section') }}',
            option: '{{ request('option') }}',
            salle: '{{ request('salle') }}',

            init() {
                this.filtrerSections();
                this.filtrerOptionsEtSalles();
                this.filtrerSalles();
            },

            submitForm() {
                setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 0);
            },

            filtrerSections() {
                const selectSection = document.getElementById('section_select');
                if (!selectSection) return;

                const options = selectSection.querySelectorAll('option');
                options.forEach(opt => {
                    if (opt.value === '') return;
                    const sessionId = opt.getAttribute('data-session-id');
                    if (this.session === '' || sessionId == this.session) {
                        opt.style.display = '';
                    } else {
                        opt.style.display = 'none';
                    }
                });

                const selectedOption = selectSection.querySelector(`option[value="${this.section}"]`);
                if (selectedOption && selectedOption.style.display === 'none') {
                    selectSection.value = '';
                    this.section = '';
                    this.filtrerOptionsEtSalles();
                }
            },

            filtrerOptionsEtSalles() {
                const selectOption = document.getElementById('option_select');
                if (selectOption) {
                    const options = selectOption.querySelectorAll('option');
                    options.forEach(opt => {
                        if (opt.value === '') return;
                        let sectionIds = [];
                        try {
                            sectionIds = JSON.parse(opt.getAttribute('data-section-ids') || '[]');
                        } catch (e) {
                            sectionIds = [];
                        }
                        if (this.section === '' || sectionIds.includes(parseInt(this.section))) {
                            opt.style.display = '';
                        } else {
                            opt.style.display = 'none';
                        }
                    });

                    const selectedOpt = selectOption.querySelector(`option[value="${this.option}"]`);
                    if (selectedOpt && selectedOpt.style.display === 'none') {
                        selectOption.value = '';
                        this.option = '';
                    }
                }

                this.filtrerSalles();
            },

            filtrerSalles() {
                const selectSalle = document.getElementById('salle_select');
                if (!selectSalle) return;

                const options = selectSalle.querySelectorAll('option');
                options.forEach(opt => {
                    if (opt.value === '') return;
                    const optionId = opt.getAttribute('data-option-id');
                    const sectionId = opt.getAttribute('data-section-id');

                    let visible = true;
                    if (this.option !== '' && optionId != this.option) {
                        visible = false;
                    }
                    if (this.section !== '' && sectionId != this.section) {
                        visible = false;
                    }

                    opt.style.display = visible ? '' : 'none';
                });

                const selectedSalle = selectSalle.querySelector(`option[value="${this.salle}"]`);
                if (selectedSalle && selectedSalle.style.display === 'none') {
                    selectSalle.value = '';
                    this.salle = '';
                }
            },

            submitSalle() {
                this.submitForm();
            }
        }
    }
</script>
@endsection