@extends('layouts.admin')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Inscriptions</strong></h1>
            <p class="index-subtitle">Gérez les inscriptions des élèves</p>
        </div>
        <div class="flex gap-2">
            @if(auth()->user() && auth()->user()->role === 'admin')
                <form action="{{ route('admin.inscriptions.truncate') }}" method="POST" class="inline-form" onsubmit="return confirm('⚠️ Vider toutes les inscriptions ? Cette action est irréversible !')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger" title="Vider la table des inscriptions (test)">
                        <i class="fa-solid fa-trash-can"></i> Vider la table
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.inscriptions.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nouvelle inscription
            </a>
            <a href="{{ route('admin.info-eleves.index') }}" class="btn-secondary">
                <i class="fa-solid fa-search"></i> Recherche avancée
            </a>
        </div>
    </div>

    {{-- Barre d'outils --}}
    <div class="toolbar">
        <div class="toolbar-group">
            <span class="toolbar-label">Tri :</span>
            <a href="{{ route('admin.inscriptions.index', array_merge(request()->query(), ['tri' => 'alpha'])) }}"
               class="toolbar-btn {{ request('tri') == 'alpha' ? 'active' : '' }}">
                <i class="fa-solid fa-arrow-down-a-z"></i> A-Z
            </a>
            <a href="{{ route('admin.inscriptions.index', array_merge(request()->query(), ['tri' => null])) }}"
               class="toolbar-btn {{ request('tri') != 'alpha' ? 'active' : '' }}">
                <i class="fa-solid fa-calendar"></i> Par date
            </a>
        </div>

        <div class="toolbar-group toolbar-group-right export-buttons">
            <a href="{{ route('admin.inscriptions.export.pdf', request()->query()) }}" class="export-btn pdf" title="Télécharger PDF">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route('admin.inscriptions.export.csv', request()->query()) }}" class="export-btn csv" title="Télécharger CSV">
                <i class="fa-solid fa-file-excel"></i> CSV
            </a>
            <a href="{{ route('admin.inscriptions.export.xml', request()->query()) }}" class="export-btn xml" title="Télécharger XML">
                <i class="fa-solid fa-file-code"></i> XML
            </a>
            <a href="{{ route('admin.inscriptions.export.doc', request()->query()) }}" class="export-btn doc" title="Télécharger DOC">
                <i class="fa-solid fa-file-word"></i> DOC
            </a>
            <a href="{{ route('admin.inscriptions.imprimer', request()->query()) }}" target="_blank" class="export-btn print" title="Imprimer">
                <i class="fa-solid fa-print"></i> Imprimer
            </a>
        </div>
    </div>

    {{-- Lien paramètres (admin) --}}
    @if(auth()->user() && auth()->user()->role === 'admin')
        <div class="admin-link">
            <a href="{{ route('admin.settings.edit') }}">
                <i class="fa-solid fa-gear"></i> Personnaliser l'en-tête des documents (logo, nom de l'école…)
            </a>
        </div>
    @endif

    {{-- Tableau / Cartes --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Élève</th>
                    <th>Année</th>
                    <th>Salle</th>
                    <th>Option</th>
                    <th>Frais inscription</th>
                    <th>Frais annuel</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inscriptions as $ins)
                    @php
                        $taux = $tauxChange ?? 2800;
                        $fraisInscriptionFc = $ins->frais_inscription_final * $taux;
                        $fraisAnnuelFc = $ins->frais_annuel_final * $taux;
                    @endphp
                    <tr>
                        <td data-label="Élève">
                            <div class="insc-cell">
                                <div class="avatar">{{ strtoupper(substr(optional($ins->eleve)->nom ?? '?', 0, 1) . substr(optional($ins->eleve)->prenom ?? '?', 0, 1)) }}</div>
                                <span class="cell-primary">{{ optional($ins->eleve)->nom }} {{ optional($ins->eleve)->prenom }}</span>
                            </div>
                        </td>
                        <td data-label="Année">
                            {{ optional($ins->anneeScolaire)->libelle ?? 'Année supprimée' }}
                        </td>
                        <td data-label="Salle">
                            {{ optional($ins->salleDeClasse)->nom ?? 'Salle supprimée' }}
                        </td>
                        <td data-label="Option">
                            {{ optional($ins->salleDeClasse->option)->nom ?? '—' }}
                        </td>
                        <td data-label="Frais inscription">
                            <strong>{{ number_format($ins->frais_inscription_final, 0) }} $</strong>
                            <span class="text-muted small">{{ number_format($fraisInscriptionFc, 0, ',', ' ') }} FC</span>
                        </td>
                        <td data-label="Frais annuel">
                            <strong>{{ number_format($ins->frais_annuel_final, 0) }} $</strong>
                            <span class="text-muted small">{{ number_format($fraisAnnuelFc, 0, ',', ' ') }} FC</span>
                        </td>
                        <td data-label="Actions" class="text-right action-cell">
                            <div class="action-icons">
                                <a href="{{ route('admin.inscriptions.edit', $ins) }}" class="action-icon" title="Modifier">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form action="{{ route('admin.inscriptions.destroy', $ins) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cette inscription ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-icon danger" title="Supprimer">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-cell">
                            Aucune inscription enregistrée.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper">
        {{ $inscriptions->appends(request()->query())->links() }}
    </div>
</div>

<style>
    /* ==== Styles communs ==== */
    .index-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    .index-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.25rem; margin-bottom: 2rem; }
    .index-title { font-size: 1.75rem; font-weight: 700; color: #1e293b; margin-bottom: 0.2rem; letter-spacing: -0.5px; }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: #94a3b8; font-size: 0.95rem; }
    .flex { display: flex; }
    .gap-2 { gap: 0.5rem; }
    .inline-form { display: inline; }
    .btn-primary, .btn-danger, .btn-secondary { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0.7rem 1.5rem; border-radius: 12px; font-weight: 600; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; border: none; cursor: pointer; }
    .btn-primary { background: #1e293b; color: white; }
    .btn-primary:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-secondary { background: white; border: 1.5px solid #e2e8f0; color: #64748b; }
    .btn-secondary:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }
    .btn-danger { background: #ef4444; color: white; }
    .btn-danger:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(239,68,68,0.3); }
    .toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; margin-bottom: 1.5rem; background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); padding: 0.75rem 1.25rem; }
    .toolbar-group { display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
    .toolbar-group-right { margin-left: auto; }
    .toolbar-label { font-size: 0.8rem; font-weight: 600; color: #64748b; }
    .toolbar-btn { display: inline-flex; align-items: center; gap: 6px; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 500; color: #475569; text-decoration: none; transition: all 0.2s; background: #f1f5f9; }
    .toolbar-btn:hover { background: #e2e8f0; }
    .toolbar-btn.active { background: #e0e7ff; color: #4338ca; font-weight: 600; }
    .export-buttons { display: flex; flex-wrap: wrap; gap: 0.4rem; }
    .export-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 0.6rem 1rem; border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: white; text-decoration: none; transition: all 0.2s; min-width: 80px; }
    .export-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
    .export-btn.pdf { background: #ef4444; }
    .export-btn.csv { background: #22c55e; }
    .export-btn.xml { background: #3b82f6; }
    .export-btn.doc { background: #0ea5e9; }
    .export-btn.print { background: #475569; }
    .admin-link { text-align: right; margin-bottom: 1rem; }
    .admin-link a { display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; color: #667eea; text-decoration: none; transition: color 0.2s; }
    .admin-link a:hover { color: #4f46e5; text-decoration: underline; }
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
    .text-muted.small { font-size: 0.75rem; color: #94a3b8; display: block; margin-top: 0.2rem; }
    .action-cell { white-space: nowrap; }
    .action-icons { display: flex; justify-content: flex-end; gap: 0.5rem; }
    .action-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; color: #64748b; text-decoration: none; transition: all 0.2s; background: none; border: none; cursor: pointer; font-size: 1rem; }
    .action-icon:hover { background: #e2e8f0; color: #1e293b; }
    .action-icon.danger:hover { background: #fee2e2; color: #dc2626; }
    .empty-cell { text-align: center; padding: 3rem; color: #94a3b8; }
    .pagination-wrapper { margin-top: 1.5rem; }
    @media (max-width: 1024px) {
        .toolbar-group-right { margin-left: 0; width: 100%; }
        .export-buttons { flex-direction: column; width: 100%; }
        .export-btn { width: 100%; }
    }
    @media (max-width: 640px) {
        .index-header { flex-direction: column; align-items: stretch; }
        .toolbar { flex-direction: column; align-items: stretch; }
        .toolbar-group-right { margin-left: 0; justify-content: flex-start; }
        .flex.gap-2 { flex-direction: column; width: 100%; }
        .btn-primary, .btn-danger, .btn-secondary { width: 100%; justify-content: center; }
        .table-card { background: transparent; box-shadow: none; border-radius: 0; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table thead { display: none; }
        .data-table tr { background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); margin-bottom: 1.25rem; padding: 1rem; }
        .data-table td { border: none; padding: 0.5rem 0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .data-table td::before { content: attr(data-label); font-weight: 600; color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; min-width: 100px; }
        .data-table td[data-label="Actions"] { justify-content: flex-end; }
        .data-table td[data-label="Actions"]::before { display: none; }
        .action-icons { justify-content: flex-end; gap: 0.5rem; }
        .insc-cell { justify-content: flex-start; }
    }
</style>

{{-- Aucun script Alpine nécessaire désormais --}}
@endsection