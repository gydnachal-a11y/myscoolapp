@extends('layouts.admin')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Clôturer l'année {{ $anneeScolaire->libelle }}</h2>
        <a href="{{ route('admin.annees-scolaires.index') }}" class="text-indigo-600 hover:underline">← Retour</a>
    </div>

    <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl p-6 border border-gray-100 space-y-6">
        <p class="text-gray-700">
            Cette action va <strong>clôturer définitivement</strong> l'année scolaire <strong>{{ $anneeScolaire->libelle }}</strong>.
            Vous pouvez choisir de transférer certaines données vers la nouvelle année ou de repartir de zéro.
        </p>

        <form action="{{ route('admin.annees-scolaires.appliquer-transition', $anneeScolaire) }}" method="POST" class="space-y-6">
            @csrf

            {{-- Choix de la nouvelle année --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nouvelle année scolaire</label>
                <div class="space-y-2">
                    @if($anneeSuivante)
                        <label class="flex items-center gap-2">
                            <input type="radio" name="nouvelle_annee_id" value="{{ $anneeSuivante->id }}" class="rounded border-gray-300" checked>
                            <span>Utiliser l'existante : {{ $anneeSuivante->libelle }}</span>
                        </label>
                    @endif
                    <label class="flex items-center gap-2">
                        <input type="radio" name="creer_nouvelle_annee" value="1" class="rounded border-gray-300" {{ $anneeSuivante ? '' : 'checked' }}>
                        <span>Créer une nouvelle année (automatique)</span>
                    </label>
                </div>
            </div>

            {{-- Options de transfert --}}
            <div class="border-t pt-4">
                <h3 class="text-lg font-semibold mb-3">Options de transfert</h3>
                <div class="space-y-3">
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="transferer_eleves" value="1" class="mt-1 rounded border-gray-300">
                        <span>Transférer les élèves (promotion automatique vers salle supérieure)</span>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="transferer_config" value="1" class="mt-1 rounded border-gray-300">
                        <span>Conserver les configurations (salles, sections, options)</span>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="transferer_cours" value="1" class="mt-1 rounded border-gray-300">
                        <span>Conserver les cours et catégories</span>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="transferer_personnel" value="1" class="mt-1 rounded border-gray-300">
                        <span>Conserver le personnel et fonctions</span>
                    </label>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="reset_complet" value="1" class="mt-1 rounded border-gray-300">
                        <span>Réinitialiser complètement (aucun transfert)</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t">
                <a href="{{ route('admin.annees-scolaires.index') }}" class="px-4 py-2 border rounded-xl text-gray-700">Annuler</a>
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition shadow-sm">
                    Confirmer la clôture
                </button>
            </div>
        </form>
    </div>
</div>
@endsection