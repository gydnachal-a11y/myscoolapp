@extends('layouts.admin')

@section('page_title', 'Planifier – ' . $salle->nom)
@section('page_subtitle', 'Créez et gérez les sessions de paiement pour cette salle')

@section('content')
<div class="index-container">
    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title">Planifier – <strong>{{ $salle->nom }}</strong></h1>
            <p class="index-subtitle">Créez et gérez les sessions de paiement pour cette salle</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.mois-scolaires.index') }}" class="btn-secondary">
                <i class="bi bi-calendar"></i> Mois scolaires
            </a>
            <a href="{{ route('admin.tranches-scolaires.index') }}" class="btn-secondary">
                <i class="bi bi-layers"></i> Tranches
            </a>
            <a href="{{ route('admin.planification-paiements.index') }}" class="btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="alert-success"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error"><i class="bi bi-exclamation-circle"></i> {{ session('error') }}</div>
    @endif

    {{-- Cartes infos salle --}}
    <div class="stats-grid">
        <div class="stat-card border-indigo">
            <div class="stat-icon"><i class="bi bi-cash"></i></div>
            <div>
                <p class="stat-label">Frais annuel</p>
                <p class="stat-value">{{ number_format($salle->frais_annuel, 0) }} $</p>
            </div>
        </div>
        <div class="stat-card border-purple">
            <div class="stat-icon"><i class="bi bi-credit-card"></i></div>
            <div>
                <p class="stat-label">Mode de paiement</p>
                <p class="stat-value">{{ $salle->mode_paiement }}</p>
            </div>
        </div>
        <div class="stat-card border-orange">
            <div class="stat-icon"><i class="bi bi-coin"></i></div>
            <div>
                <p class="stat-label">Frais selon mode</p>
                @if($salle->mode_paiement === 'mensuel')
                    <p class="stat-value">{{ number_format($salle->frais_scolarite_mensuel ?? 0, 0) }} $ / mois</p>
                @else
                    <p class="stat-value">{{ $salle->nombre_tranches ?? 1 }} tranche(s)</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Formulaire de planification --}}
    <div class="form-card">
        <h3 class="form-card-title"><i class="bi bi-plus-circle"></i> Nouvelle session de paiement</h3>
        <form action="{{ route('admin.planification-paiements.sessions.store', $salle) }}" method="POST" class="form-grid-inline">
            @csrf
            <input type="hidden" name="type_periode" value="{{ $type }}">

            <div class="form-field">
                <label class="field-label">Période</label>
                <select name="periode" id="periode_select" class="select-control" required>
                    <option value="">Choisir une période</option>
                    @foreach($periodes as $p)
                        @php $val = $type === 'mensuel' ? $p->mois : $p->tranche; @endphp
                        <option value="{{ $val }}">{{ $val }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label class="field-label">Date début session</label>
                <input type="date" name="date_debut_session" class="input-control" required>
            </div>

            <div class="form-field">
                <label class="field-label">Date fin session</label>
                <input type="date" name="date_fin_session" class="input-control" required>
            </div>

            <div class="form-field form-field-submit">
                <button type="submit" class="btn-submit">
                    <i class="bi bi-calendar-plus"></i> Planifier
                </button>
            </div>
        </form>
    </div>

    {{-- Liste des sessions --}}
    <div class="table-card">
        <div class="table-header">
            <h3 class="table-title"><i class="bi bi-list"></i> Sessions planifiées pour {{ $salle->nom }}</h3>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Période</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Statut</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                <tr>
                    <td data-label="Période">{{ $session->periode }}</td>
                    <td data-label="Début">{{ $session->date_debut_session->format('d/m/Y') }}</td>
                    <td data-label="Fin">{{ $session->date_fin_session->format('d/m/Y') }}</td>
                    <td data-label="Statut">
                        @if($session->estOuverte())
                            <span class="badge badge-green">Ouverte</span>
                        @elseif($session->estExpiree())
                            <span class="badge badge-red">Expirée</span>
                        @else
                            <span class="badge badge-blue">Programmée</span>
                        @endif
                    </td>
                    <td data-label="Actions" class="text-right action-cell">
                        <div class="action-icons">
                            <form action="{{ route('admin.sessions-paiement.destroy', $session) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cette session ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-icon danger" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-cell">Aucune session planifiée.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    /* ==== Styles locaux premium ==== */
    .index-container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }
    .index-header { display: flex; flex-direction: column; align-items: flex-start; gap: 1.25rem; margin-bottom: 2rem; animation: fadeUp 0.6s ease forwards; opacity: 0; }
    .index-title { font-size: 1.75rem; font-weight: 700; color: #1e293b; margin-bottom: 0.2rem; letter-spacing: -0.5px; }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: #94a3b8; font-size: 0.95rem; }
    .header-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

    .btn-primary, .btn-secondary { display: inline-flex; align-items: center; gap: 8px; padding: 0.7rem 1.5rem; border-radius: 12px; font-weight: 600; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; }
    .btn-primary { background: #1e293b; color: white; }
    .btn-primary:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-secondary { background: white; border: 1.5px solid #e2e8f0; color: #64748b; }
    .btn-secondary:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    .alert-success, .alert-error { display: flex; align-items: center; gap: 10px; padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem; animation: fadeUp 0.4s ease forwards; }
    .alert-success { background: #f0fdf4; border-left: 4px solid #22c55e; color: #16a34a; }
    .alert-error { background: #fef2f2; border-left: 4px solid #ef4444; color: #dc2626; }

    .stats-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-bottom: 2rem; }
    @media (min-width: 768px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
    .stat-card { background: white; padding: 1.5rem; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 1rem; border-left: 4px solid; animation: cardIn 0.6s ease forwards; opacity: 0; }
    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    @keyframes cardIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .border-indigo { border-left-color: #667eea; }
    .border-purple { border-left-color: #a855f7; }
    .border-orange { border-left-color: #f97316; }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .border-indigo .stat-icon { background: #eef2ff; color: #667eea; }
    .border-purple .stat-icon { background: #faf5ff; color: #a855f7; }
    .border-orange .stat-icon { background: #fff7ed; color: #f97316; }
    .stat-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; font-weight: 600; }
    .stat-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-top: 0.25rem; text-transform: capitalize; }

    .form-card { background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); padding: 1.5rem; margin-bottom: 2rem; animation: fadeUp 0.6s 0.4s ease forwards; opacity: 0; }
    .form-card-title { font-size: 1.1rem; font-weight: 600; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px; }
    .form-card-title i { color: #667eea; }
    .form-grid-inline { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1.25rem; }
    .form-field { display: flex; flex-direction: column; gap: 0.5rem; flex: 1; min-width: 180px; }
    .form-field-submit { flex: 0 0 auto; }
    .field-label { font-size: 0.85rem; font-weight: 600; color: #475569; }
    .input-control, .select-control { width: 100%; padding: 0.7rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.95rem; color: #1e293b; transition: border-color 0.3s; outline: none; }
    .input-control:focus, .select-control:focus { border-color: #667eea; background: white; }
    .btn-submit { display: inline-flex; align-items: center; gap: 8px; padding: 0.7rem 1.5rem; background: #1e293b; color: white; border-radius: 10px; font-weight: 600; font-size: 0.95rem; border: none; cursor: pointer; transition: all 0.3s; }
    .btn-submit:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.3); }

    .table-card { background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); overflow: hidden; animation: cardIn 0.8s 0.5s ease forwards; opacity: 0; }
    .table-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
    .table-title { font-size: 1.1rem; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 8px; }
    .table-title i { color: #667eea; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: #475569; }
    .data-table thead th { text-align: left; padding: 0.9rem 1.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .data-table tbody td { padding: 0.9rem 1.5rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .text-right { text-align: right; }

    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-green { background: #dcfce7; color: #16a34a; }
    .badge-red { background: #fee2e2; color: #dc2626; }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }

    .action-cell { white-space: nowrap; }
    .action-icons { display: flex; justify-content: flex-end; gap: 0.5rem; }
    .action-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; color: #64748b; text-decoration: none; transition: all 0.2s; background: none; border: none; cursor: pointer; font-size: 1rem; }
    .action-icon:hover { background: #e2e8f0; color: #1e293b; }
    .action-icon.danger:hover { background: #fee2e2; color: #dc2626; }
    .inline-form { display: inline; }

    .empty-cell { text-align: center; padding: 3rem; color: #94a3b8; }

    @media (max-width: 640px) {
        .form-grid-inline { flex-direction: column; align-items: stretch; }
        .form-field { min-width: 100%; }
        .table-card { background: transparent; box-shadow: none; border-radius: 0; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table thead { display: none; }
        .data-table tr { background: white; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); margin-bottom: 1.25rem; padding: 1rem; }
        .data-table td { border: none; padding: 0.5rem 0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .data-table td::before { content: attr(data-label); font-weight: 600; color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; min-width: 100px; }
        .data-table td[data-label="Actions"] { justify-content: flex-end; }
        .data-table td[data-label="Actions"]::before { display: none; }
        .action-icons { justify-content: flex-end; gap: 0.5rem; }
    }
</style>
@endsection