<?php

namespace Database\Seeders;

use App\Models\Eleve;
use App\Models\Responsable;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class EleveSeeder extends Seeder
{
    /** Nombre d'élèves de démonstration à créer. */
    private const TOTAL_ELEVES = 300;

    // ============================================================
    // JEU DE DONNÉES
    // ============================================================

    private const PRENOMS_MASCULINS = [
        'Gloire', 'Daniel', 'Joseph', 'Samuel', 'Jonathan', 'David', 'Aaron',
        'Moïse', 'Isaac', 'Jérémie', 'Emmanuel', 'Christian', 'Patrick',
        'Blaise', 'Déo', 'Gédéon', 'Elisée', 'Jospin', 'Beni',
        'Exauce', 'Plamedi', 'Cédric', 'Junior', 'Dieumerci',
        'Franck', 'Héritier', 'Israël', 'Kévin', 'Mardochée',
        'Néhémie', 'Olivier', 'Prince', 'Salomon', 'Timothée', 'Zacharie',
        'Abel', 'Boaz', 'Caleb', 'Eliakim', 'Fiston', 'Gabriel', 'Hénoc',
        'Japhet', 'Lévi', 'Malachie', 'Nathan', 'Osée', 'Phinées',
        'Ruben', 'Siméon', 'Théo', 'Uriel', 'Venceslas', 'William',
    ];

    private const PRENOMS_FEMININS = [
        'Grâce', 'Esther', 'Sarah', 'Ruth', 'Deborah', 'Marie', 'Anne',
        'Raïssa', 'Bénédicte', 'Dorcas', 'Noëlla', 'Gloria', 'Priscille',
        'Sandra', 'Chantal', 'Ange', 'Fidélie', 'Victoire', 'Laure',
        'Merveille', 'Abigaël', 'Béthanie', 'Chrisma', 'Daniella',
        'Elisabeth', 'Florence', 'Hadassa', 'Irène', 'Jemima',
        'Ketura', 'Léa', 'Michaëlla', 'Naomi', 'Ornella', 'Pélagie',
        'Rachelle', 'Salomé', 'Tabitha', 'Urielle', 'Véronique', 'Wivine',
        'Yvette', 'Zippora', 'Aurélie', 'Béatrice', 'Carine', 'Doriane',
    ];

    private const NOMS = [
        'Mukendi', 'Ilunga', 'Kabongo', 'Tshilombo', 'Kalala', 'Mwamba',
        'Ndaya', 'Luboya', 'Musonda', 'Banza', 'Kabila', 'Tshisekedi',
        'Mulumba', 'Mbayo', 'Tshibangu', 'Kasongo', 'Kamwanya', 'Bukasa',
        'Mbuyi', 'Nkongolo', 'Katumba', 'Kanyinda', 'Kalombo', 'Lutumba',
        'Mwanza', 'Nkulu', 'Nsenga', 'Lwamba', 'Kashala', 'Tshiala',
        'Mpinga', 'Kanku', 'Kanda', 'Kabeya', 'Ngalula', 'Batumona',
        'Kanza', 'Nkosi', 'Dikembe', 'Lukusa', 'Mbiya', 'Kalume',
        'Muteba', 'Kasereka', 'Kavira', 'Kambere', 'Mumbere', 'Kahindo',
        'Kakule', 'Mbusa', 'Katembo', 'Kavugho', 'Kasoki', 'Mbambu',
    ];

    // ============================================================
    // RUN
    // ============================================================

    public function run(): void
    {
        // ✅ Autorisé en local, testing, OU si ALLOW_DEMO_SEED=true
        $allowed = app()->environment(['local', 'testing'])
            || filter_var(env('ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN);

        if (! $allowed) {
            $this->command?->warn(
                'EleveSeeder ignoré. Définis ALLOW_DEMO_SEED=true dans .env pour autoriser.'
            );
            return;
        }

        // ✅ Faker doit être installé
        if (! class_exists(Faker::class)) {
            $this->command?->error('Faker non installé. Lance: composer require fakerphp/faker');
            return;
        }

        $faker = Faker::create('fr_FR');

        for ($i = 0; $i < self::TOTAL_ELEVES; $i++) {
            $this->createEleveWithResponsables($faker);
        }

        $this->command?->info(self::TOTAL_ELEVES . ' élèves créés avec succès.');
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Crée un élève + ses responsables (père, mère, + tuteur si besoin).
     */
    private function createEleveWithResponsables(\Faker\Generator $faker): void
    {
        $sexe   = $faker->randomElement(['M', 'F']);
        $prenom = $sexe === 'M'
            ? $faker->randomElement(self::PRENOMS_MASCULINS)
            : $faker->randomElement(self::PRENOMS_FEMININS);

        $nom     = $faker->randomElement(self::NOMS);
        $postnom = $faker->optional(0.7)->lastName;

        $eleve = Eleve::create([
            'nom'               => $nom,
            'postnom'           => $postnom,
            'prenom'            => $prenom,
            'sexe'              => $sexe,
            'date_naissance'    => $faker->dateTimeBetween('-20 years', '-3 years')->format('Y-m-d'),
            'lieu_naissance'    => $faker->city,
            'adresse'           => $faker->address,
            'photo'             => null,
            'maladie_chronique' => $faker->optional(0.1)->randomElement([
                'Asthme', 'Drépanocytose', 'Diabète', 'Épilepsie',
            ]),
            'allergies'         => $faker->optional(0.2)->randomElement([
                'Arachides', 'Poussière', 'Lactose', 'Pollens',
            ]),
        ]);

        // ----- Père -----
        $pereVivant = $faker->boolean(90);
        Responsable::create([
            'eleve_id'   => $eleve->id,
            'type'       => 'pere',
            'nom'        => $faker->firstNameMale . ' ' . $nom,
            'profession' => $faker->optional(0.9)->jobTitle,
            'telephone'  => $this->phone($faker),
            'vivant'     => $pereVivant,
        ]);

        // ----- Mère -----
        $mereVivante = $faker->boolean(95);
        Responsable::create([
            'eleve_id'   => $eleve->id,
            'type'       => 'mere',
            'nom'        => $faker->firstNameFemale . ' ' . $nom,
            'profession' => $faker->optional(0.8)->jobTitle,
            'telephone'  => $this->phone($faker),
            'vivant'     => $mereVivante,
        ]);

        // ----- Tuteur (si l'un des parents est décédé) -----
        if (! $pereVivant || ! $mereVivante) {
            Responsable::create([
                'eleve_id'   => $eleve->id,
                'type'       => 'tuteur',
                'nom'        => $faker->name,
                'profession' => $faker->jobTitle,
                'telephone'  => $this->phone($faker),
                'vivant'     => true,
            ]);
        }
    }

    /**
     * Génère un numéro de téléphone congolais fictif.
     */
    private function phone(\Faker\Generator $faker): string
    {
        return '0' . $faker->numberBetween(800000000, 999999999);
    }
}