@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="max-w-4xl mx-auto px-4 py-8">
    {{-- Barre d'actions (non imprimée) --}}
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6 no-print">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Fiche de l’élève</h1>
            <p class="text-sm text-gray-500">Informations personnelles et paiements</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.info-eleves.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fa-solid fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    {{-- Facture --}}
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden print:shadow-none print:border-0">
        {{-- En-tête dynamique avec logo et coordonnées --}}
        <div class="p-8 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-6">
                <div class="flex items-center gap-4">
                    @if($siteSettings->site_logo)
                        <img src="{{ asset('storage/' . $siteSettings->site_logo) }}" 
                             alt="{{ $siteSettings->site_name }}" 
                             class="w-14 h-14 rounded-xl object-cover shadow-lg">
                    @else
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center text-xl font-bold shadow-lg">
                            <i class="fa-solid fa-school"></i>
                        </div>
                    @endif
                    <div>
                        <h2 class="text-xl font-extrabold text-gray-900">{{ $siteSettings->site_name ?? config('app.name', 'Mon École') }}</h2>
                        @if($siteSettings->site_address)
                            <p class="text-sm text-gray-500">{{ $siteSettings->site_address }}</p>
                        @endif
                        @if($siteSettings->site_email)
                            <p class="text-sm text-gray-500">{{ $siteSettings->site_email }}</p>
                        @endif
                        @if($siteSettings->site_phone)
                            <p class="text-sm text-gray-500">{{ $siteSettings->site_phone }}</p>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <h3 class="text-lg font-bold text-indigo-600 uppercase tracking-wider">Fiche élève</h3>
                    <p class="text-sm text-gray-600">Réf : {{ $eleve->id }}</p>
                    <p class="text-sm text-gray-600">Date : {{ now()->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        {{-- Informations élève + inscription --}}
        <div class="px-8 py-6 border-b border-gray-200 bg-gray-50/50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-sm text-gray-500 uppercase font-medium">Élève</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $eleve->nom_complet }}</p>
                    <p class="text-sm text-gray-600">{{ $eleve->sexe_libelle }}</p>
                    @if($eleve->date_naissance)
                        <p class="text-sm text-gray-600">Né(e) le {{ $eleve->date_naissance->format('d/m/Y') }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-sm text-gray-500 uppercase font-medium">Inscription active</p>
                    @if($inscriptionActive)
                        <p class="text-lg font-semibold text-gray-900">{{ $inscriptionActive->anneeScolaire->libelle ?? 'Année inconnue' }}</p>
                        <p class="text-sm text-gray-600">{{ $inscriptionActive->salleDeClasse->nom ?? 'Salle inconnue' }}</p>
                    @else
                        <p class="text-gray-500">Aucune inscription</p>
                    @endif
                </div>
                <div class="md:text-right">
                    <p class="text-sm text-gray-500 uppercase font-medium">Frais d'inscription</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($inscriptionActive->frais_inscription_final ?? 0, 0, ',', ' ') }} $</p>
                    <p class="text-sm text-gray-600">Frais annuel : {{ number_format($inscriptionActive->frais_annuel_final ?? 0, 0, ',', ' ') }} $</p>
                </div>
            </div>
        </div>

        {{-- Tableau des paiements principaux --}}
        <div class="px-8 py-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-indigo-600"></i> Paiements principaux
            </h3>
            @if($eleve->paiements->isNotEmpty())
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 text-left font-semibold text-gray-600">Période</th>
                            <th class="py-2 text-left font-semibold text-gray-600">Type</th>
                            <th class="py-2 text-right font-semibold text-gray-600">Attendu (USD)</th>
                            <th class="py-2 text-right font-semibold text-gray-600">Payé (USD)</th>
                            <th class="py-2 text-right font-semibold text-gray-600">Restant (USD)</th>
                            <th class="py-2 text-center font-semibold text-gray-600">Statut</th>
                            <th class="py-2 text-right font-semibold text-gray-600">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eleve->paiements as $paiement)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">{{ $paiement->periode }}</td>
                                <td class="py-2">{{ ucfirst($paiement->type_periode) }}</td>
                                <td class="py-2 text-right">{{ number_format($paiement->montant_attendu_usd, 0, ',', ' ') }}</td>
                                <td class="py-2 text-right font-medium">{{ number_format($paiement->montant_paye_usd, 0, ',', ' ') }}</td>
                                <td class="py-2 text-right text-red-600">{{ number_format($paiement->montant_restant_usd, 0, ',', ' ') }}</td>
                                <td class="py-2 text-center">
                                    @switch($paiement->statut)
                                        @case('paye')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Payé</span>
                                            @break
                                        @case('partiel')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">Partiel</span>
                                            @break
                                        @case('impaye')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">Impayé</span>
                                            @break
                                        @case('surpaye')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">Surpayé</span>
                                            @break
                                        @default
                                            {{ $paiement->statut }}
                                    @endswitch
                                </td>
                                <td class="py-2 text-right">{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500">Aucun paiement principal enregistré.</p>
            @endif
        </div>

        {{-- Tableau des frais supplémentaires --}}
        <div class="px-8 py-6 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-indigo-600"></i> Frais supplémentaires payés
            </h3>
            @if($eleve->paiementsFraisSupplementaires->isNotEmpty())
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 text-left font-semibold text-gray-600">Frais</th>
                            <th class="py-2 text-right font-semibold text-gray-600">Montant (USD)</th>
                            <th class="py-2 text-right font-semibold text-gray-600">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eleve->paiementsFraisSupplementaires as $paiementFrais)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">{{ $paiementFrais->fraisSupplementaire->libelle ?? 'Frais supprimé' }}</td>
                                <td class="py-2 text-right font-medium">{{ number_format($paiementFrais->montant_paye_usd, 0, ',', ' ') }}</td>
                                <td class="py-2 text-right">{{ $paiementFrais->date_paiement->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500">Aucun frais supplémentaire payé.</p>
            @endif
        </div>

        {{-- Totaux --}}
        <div class="px-8 py-6 border-t border-gray-200 bg-gray-50/50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Total payé (principaux)</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($totalPayePrincipalUsd, 0, ',', ' ') }} $</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Total restant (principaux)</p>
                    <p class="text-xl font-bold text-red-600">{{ number_format($totalRestantPrincipalUsd, 0, ',', ' ') }} $</p>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Total frais suppl.</p>
                    <p class="text-xl font-bold text-indigo-600">{{ number_format($totalPayeFraisSuppUsd, 0, ',', ' ') }} $</p>
                </div>
            </div>
        </div>

        {{-- Pied de facture --}}
        <div class="px-8 py-6 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-500">
                <p>Merci de votre confiance.</p>
                <p>Document généré le {{ now()->translatedFormat('d F Y à H:i') }}</p>
            </div>
            <div class="text-sm text-gray-500">
                <p class="font-semibold">Signature autorisée</p>
                <div class="mt-2 h-10 w-40 border-b border-gray-400"></div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        body { background: white !important; }
        .shadow-2xl { box-shadow: none !important; }
        .rounded-2xl { border-radius: 0 !important; }
        .border { border-color: #ddd !important; }
        .bg-gray-50\/50 { background: #fafafa !important; }
        .bg-green-50\/50 { background: #f0fdf4 !important; }
    }
</style>
@endsection