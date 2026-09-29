<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PublicEnregistrementController extends Controller
{
    // ==========================================
    // RÈGLES DE VALIDATION
    // ==========================================

    /**
     * Règles de validation pour l'enregistrement d'un élève.
     */
    private const VALIDATION_RULES = [
        'nom'                 => 'required|string|max:255',
        'postnom'             => 'nullable|string|max:255',
        'prenom'              => 'required|string|max:255',
        'sexe'                => 'required|in:M,F',
        'date_naissance'      => 'required|date|before:today',
        'lieu_naissance'      => 'required|string|max:255',
        'adresse'             => 'required|string|max:255',
        'photo'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'maladie_chronique'   => 'nullable|string|max:1000',
        'allergies'           => 'nullable|string|max:1000',
        'responsables'        => 'required|array|min:1|max:5',
        'responsables.*.type' => 'required|in:pere,mere,tuteur',
        'responsables.*.vivant' => 'nullable|boolean',
        'responsables.*.nom'  => 'required|string|max:255',
        'responsables.*.profession' => 'nullable|string|max:255',
        'responsables.*.telephone'  => 'required|string|max:50',
    ];

    // ==========================================
    // ACTIONS
    // ==========================================

    /**
     * Affiche le formulaire d'enregistrement.
     */
    public function create(): View
    {
        return view('public.enregistrement');
    }

    /**
     * Enregistre un élève (sans inscription).
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validation
        $data = $request->validate(self::VALIDATION_RULES);

        // 2. Nettoyage / normalisation
        $data['nom']    = trim($data['nom']);
        $data['prenom'] = trim($data['prenom']);
        $data['postnom'] = isset($data['postnom']) ? trim($data['postnom']) : null;

        try {
            // 3. Transaction : création élève + responsables
            $eleve = DB::transaction(function () use ($request, $data): Eleve {
                $photoPath = $this->storePhoto($request);

                $eleve = Eleve::create([
                    'nom'               => $data['nom'],
                    'postnom'           => $data['postnom'] ?: null,
                    'prenom'            => $data['prenom'],
                    'sexe'              => $data['sexe'],
                    'date_naissance'    => $data['date_naissance'],
                    'lieu_naissance'    => $data['lieu_naissance'],
                    'adresse'           => $data['adresse'],
                    'photo'             => $photoPath,
                    'maladie_chronique' => $data['maladie_chronique'] ?? null,
                    'allergies'         => $data['allergies'] ?? null,
                    'inscrit'           => false,
                ]);

                $this->createResponsables($eleve, $data['responsables'] ?? []);

                return $eleve;
            });

            return redirect()
                ->route('public.enregistrement.confirmation')
                ->with(
                    'success',
                    "L'enregistrement de {$eleve->nomComplet} a été effectué avec succès. "
                    . "Veuillez vous rendre à l'établissement pour finaliser l'inscription."
                );

        } catch (ValidationException $e) {
            throw $e;

        } catch (Throwable $e) {
            Log::error("Erreur enregistrement élève : {$e->getMessage()}", [
                'exception' => $e,
                'input'     => $request->except(['photo']),
            ]);

            throw ValidationException::withMessages([
                'erreur' => "Une erreur est survenue lors de l'enregistrement. "
                          . "Veuillez réessayer ou contacter l'administrateur.",
            ]);
        }
    }

    /**
     * Page de confirmation.
     */
    public function confirmation(): View
    {
        return view('public.enregistrement_confirmation');
    }

    // ==========================================
    // MÉTHODES PRIVÉES
    // ==========================================

    /**
     * Stocke la photo si présente, sinon retourne null.
     */
    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('eleves', 'public');
    }

    /**
     * Crée les responsables associés à l'élève.
     *
     * @param  array<int, array<string, mixed>>  $responsables
     */
    private function createResponsables(Eleve $eleve, array $responsables): void
    {
        $rows = [];

        foreach ($responsables as $r) {
            // Skip silencieux des lignes incomplètes
            if (empty($r['nom']) || empty($r['telephone'])) {
                continue;
            }

            $rows[] = [
                'eleve_id'   => $eleve->id,
                'type'       => $r['type'],
                'vivant'     => ! empty($r['vivant']),
                'nom'        => trim($r['nom']),
                'profession' => ! empty($r['profession']) ? trim($r['profession']) : null,
                'telephone'  => trim($r['telephone']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            Responsable::insert($rows); // Insertion en masse = 1 seule requête
        }
    }
}