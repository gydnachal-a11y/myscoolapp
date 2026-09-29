<?php

namespace Database\Seeders;

use App\Models\Eleve;
use App\Models\Responsable;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class EleveSeeder extends Seeder
{
    public function run(): void
    {
        // ⛔ GARDE-FOU : jamais en production
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('EleveSeeder ignoré (env non-production uniquement).');
            return;
        }

        // ⛔ Ceinture + bretelles : Faker est une dépendance dev
        if (! class_exists(Faker::class)) {
            $this->command?->error('Faker non installé. Lance: composer install');
            return;
        }

        $faker = Faker::create('fr_FR');

        $prenomsMasculins = [
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

        $prenomsFeminins = [
            'Grâce', 'Esther', 'Sarah', 'Ruth', 'Deborah', 'Marie', 'Anne',
            'Raïssa', 'Bénédicte', 'Dorcas', 'Noëlla', 'Gloria', 'Priscille',
            'Sandra', 'Chantal', 'Ange', 'Fidélie', 'Victoire', 'Laure',
            'Merveille', 'Abigaël', 'Béthanie', 'Chrisma', 'Daniella',
            'Elisabeth', 'Florence', 'Hadassa', 'Irène', 'Jemima',
            'Ketura', 'Léa', 'Michaëlla', 'Naomi', 'Ornella', 'Pélagie',
            'Rachelle', 'Salomé', 'Tabitha', 'Urielle', 'Véronique', 'Wivine',
            'Yvette', 'Zippora', 'Aurélie', 'Béatrice', 'Carine', 'Doriane',
        ];

        $noms = [
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

        $totalEleves = 300;

        for ($i = 0; $i < $totalEleves; $i++) {
            $sexe   = $faker->randomElement(['M', 'F']);
            $prenom = $sexe === 'M'
                ? $faker->randomElement($prenomsMasculins)
                : $faker->randomElement($prenomsFeminins);

            $nom     = $faker->randomElement($noms);
            $postnom = $faker->optional(0.7)->lastName;

            $dateNaissance = $faker->dateTimeBetween('-20 years', '-3 years')->format('Y-m-d');

            $eleve = Eleve::create([
                'nom'               => $nom,
                'postnom'           => $postnom,
                'prenom'            => $prenom,
                'sexe'              => $sexe,
                'date_naissance'    => $dateNaissance,
                'lieu_naissance'    => $faker->city,
                'adresse'           => $faker->address,
                'photo'             => null,
                'maladie_chronique' => $faker->optional(0.1)->randomElement(['Asthme', 'Drépanocytose', 'Diabète', 'Épilepsie']),
                'allergies'         => $faker->optional(0.2)->randomElement(['Arachides', 'Poussière', 'Lactose', 'Pollens']),
            ]);

            // Père
            $pereVivant = $faker->boolean(90);
            Responsable::create([
                'eleve_id'   => $eleve->id,
                'type'       => 'pere',
                'nom'        => $faker->firstNameMale . ' ' . $nom,
                'profession' => $faker->optional(0.9)->jobTitle,
                'telephone'  => '0' . $faker->numberBetween(800000000, 999999999),
                'vivant'     => $pereVivant,
            ]);

            // Mère
            $mereVivante = $faker->boolean(95);
            Responsable::create([
                'eleve_id'   => $eleve->id,
                'type'       => 'mere',
                'nom'        => $faker->firstNameFemale . ' ' . $nom,
                'profession' => $faker->optional(0.8)->jobTitle,
                'telephone'  => '0' . $faker->numberBetween(800000000, 999999999),
                'vivant'     => $mereVivante,
            ]);

            // Tuteur si l'un des parents est décédé
            if (! $pereVivant || ! $mereVivante) {
                Responsable::create([
                    'eleve_id'   => $eleve->id,
                    'type'       => 'tuteur',
                    'nom'        => $faker->name,
                    'profession' => $faker->jobTitle,
                    'telephone'  => '0' . $faker->numberBetween(800000000, 999999999),
                    'vivant'     => true,
                ]);
            }
        }

        $this->command?->info("{$totalEleves} élèves créés avec succès.");
    }
}