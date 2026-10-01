<?php

declare(strict_types=1);

/*
|==========================================================================
| ROUTES — MyscoolApp
|
| Structure (dans l'ordre du fichier) :
|
|   1. PUBLIC              → sans authentification
|   2. AUTH CONTACT GUEST  → login/register/reset (guard "contact")
|   3. AUTH CONTACT        → espace abonné (guard "contact")
|   4. AUTH WEB GUEST      → login admin (guard "web")
|   5. AUTH WEB            → dashboard + espace membre (guard "web")
|   6. ADMIN               → administration (auth:web + permission.route)
|   7. API                 → endpoints internes
|   8. FALLBACK            → 404
|==========================================================================
*/

use Illuminate\Support\Facades\Route;

// ─── Public ────────────────────────────────────────────────────────────
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PublicEnregistrementController;
use App\Http\Controllers\PublicPaiementController;

// ─── Auth abonné (guard "contact") ────────────────────────────────────
use App\Http\Controllers\ContactForgotPasswordController;
use App\Http\Controllers\ContactResetPasswordController;
use App\Http\Controllers\ExternalAuthController;

// ─── Espace membre (guard "web") ──────────────────────────────────────
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\DemandeAvanceController as MemberDemandeAvanceController;
use App\Http\Controllers\Member\ProfilController;

// ─── Admin : Configuration ────────────────────────────────────────────
use App\Http\Controllers\Admin\AnneeScolaireController;
use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\DeviseController;
use App\Http\Controllers\Admin\FonctionController;
use App\Http\Controllers\Admin\MoisScolaireController;
use App\Http\Controllers\Admin\OptionController;
use App\Http\Controllers\Admin\ReferenceController;
use App\Http\Controllers\Admin\SalleDeClasseController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\TrancheScolaireController;

// ─── Admin : Inscriptions & Élèves ────────────────────────────────────
use App\Http\Controllers\Admin\EleveController;
use App\Http\Controllers\Admin\InfoEleveController;
use App\Http\Controllers\Admin\InfoPaiementController;
use App\Http\Controllers\Admin\InscriptionController;

// ─── Admin : Pédagogie ────────────────────────────────────────────────
use App\Http\Controllers\Admin\CourController;
use App\Http\Controllers\Admin\NoteController;
use App\Http\Controllers\Admin\PeriodeNoteController;

// ─── Admin : Finances ─────────────────────────────────────────────────
use App\Http\Controllers\Admin\AvanceSalaireController;
use App\Http\Controllers\Admin\EcheanceController;
use App\Http\Controllers\Admin\FraisSupplementaireController;
use App\Http\Controllers\Admin\PaiementController;
use App\Http\Controllers\Admin\PaiementDashboardController;
use App\Http\Controllers\Admin\PaiementFraisSupplementaireController;
use App\Http\Controllers\Admin\PlanificationPaiementController;
use App\Http\Controllers\Admin\SalaireController;
use App\Http\Controllers\Admin\SalaireHoraireController;
use App\Http\Controllers\Admin\SalairePaiementController;
use App\Http\Controllers\Admin\StatistiqueController;
use App\Http\Controllers\Admin\TauxChangeController;

// ─── Admin : Avances & Demandes ───────────────────────────────────────
use App\Http\Controllers\Admin\DemandeAvanceController as AdminDemandeAvanceController;
use App\Http\Controllers\Admin\SessionAvanceController;

// ─── Admin : Sécurité & Permissions ───────────────────────────────────
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;

// ─── Admin : Communication ────────────────────────────────────────────
use App\Http\Controllers\Admin\AnnonceController;
use App\Http\Controllers\Admin\BulkEmailController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\ReglementInterieurController;

// ─── Admin : Paramètres ───────────────────────────────────────────────
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SiteSettingController;

// ─── Invokables ───────────────────────────────────────────────────────
use App\Http\Controllers\Admin\AdminRedirectController;
use App\Http\Controllers\Api\SalleDetailsController;


/*
|==========================================================================
| 1. 🌐 ROUTES PUBLIQUES — aucune authentification
|==========================================================================
|
| Toutes les routes visibles par n'importe quel visiteur.
| Rate-limiting appliqué sur les routes "actives" (POST).
|==========================================================================
*/

// ─── Accueil ──────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');

// ─── Annonces (throttle léger pour éviter le scraping) ───────────────
Route::get('/annonces', [ExternalAuthController::class, 'annonces'])
    ->name('annonces')
    ->middleware('throttle:60,1');

// ─── Classements publics ─────────────────────────────────────────────
Route::get('/classements', [HomeController::class, 'classement'])
    ->name('public.classement')
    ->middleware('throttle:60,1');

// ─── Consultation des élèves inscrits (informations publiques) ───────
// ⚠️  Vérifier que le contrôleur ne retourne QUE les champs publics
//    (nom, prénom, salle — pas de téléphone, adresse, etc.)
Route::get('/inscriptions', [HomeController::class, 'inscriptionsParSalle'])
    ->name('public.inscriptions')
    ->middleware('throttle:60,1');

// ─── Pré-inscription d'un élève (formulaire public) ──────────────────
Route::prefix('enregistrement')
    ->name('public.enregistrement.')
    ->controller(PublicEnregistrementController::class)
    ->group(function () {
        Route::get('/',             'create')->name('create');
        Route::post('/',            'store')->name('store')->middleware('throttle:10,1');
        Route::get('/confirmation', 'confirmation')->name('confirmation');
    });

// ─── Contact ─────────────────────────────────────────────────────────
Route::controller(ContactController::class)->group(function () {
    Route::get('/contact',  'show')->name('contact');
    Route::post('/contact', 'store')->name('contact.store')->middleware('throttle:5,1');
});

// ─── Newsletter ──────────────────────────────────────────────────────
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->name('newsletter.subscribe')
    ->middleware('throttle:10,1');

Route::get('/newsletter/unsubscribe/{contact}', [NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe')
    ->middleware('signed');


/*
|==========================================================================
| 2. 🔐 AUTH CONTACT — Utilisateurs non connectés (guard "contact")
|==========================================================================
|
| Pages login / register / reset password pour les abonnés (parents, etc.).
| Redirige vers /espace-contact si déjà connecté (guest:contact).
|==========================================================================
*/

Route::middleware('guest:contact')->group(function () {

    // ─── Inscription / Connexion ─────────────────────────────────────
    Route::controller(ExternalAuthController::class)->group(function () {
        Route::get('/inscription',  'showRegister')->name('external.register');
        Route::post('/inscription', 'register')->middleware('throttle:6,1');

        Route::get('/connexion',  'showLogin')->name('external.login');
        Route::post('/connexion', 'login')->middleware('throttle:6,1');

        Route::get('/auth/google/redirect', 'redirectToGoogle')->name('google.redirect');
        Route::get('/auth/google/callback', 'handleGoogleCallback')->name('google.callback');
    });

    // ─── Mot de passe oublié ─────────────────────────────────────────
    Route::controller(ContactForgotPasswordController::class)->group(function () {
        Route::get('/mot-de-passe-oublie',  'showLinkRequestForm')->name('external.password.request');
        Route::post('/mot-de-passe-oublie', 'sendResetLinkEmail')
            ->name('external.password.email')
            ->middleware('throttle:6,1');
    });

    // ─── Reset password ──────────────────────────────────────────────
    Route::controller(ContactResetPasswordController::class)->group(function () {
        Route::get('/reinitialiser-mot-de-passe/{token}', 'showResetForm')
            ->name('external.password.reset');

        Route::post('/reinitialiser-mot-de-passe', 'reset')
            ->name('external.password.update')
            ->middleware('throttle:6,1');
    });
});

// ─── Déconnexion (nécessite d'être connecté) ─────────────────────────
Route::post('/deconnexion', [ExternalAuthController::class, 'logout'])
    ->name('external.logout')
    ->middleware('auth:contact');


/*
|==========================================================================
| 3. 👤 ESPACE CONTACT — Abonnés connectés (guard "contact")
|==========================================================================
|
| Espace personnel des abonnés : dashboard, messagerie, règlement,
| consultation des paiements (données sensibles).
|==========================================================================
*/

Route::middleware('auth:contact')->group(function () {

    // ─── Dashboard abonné ────────────────────────────────────────────
    Route::get('/espace-contact', [ExternalAuthController::class, 'dashboard'])
        ->name('external.dashboard');

    // ─── Messagerie ──────────────────────────────────────────────────
    Route::controller(ExternalAuthController::class)->group(function () {
        Route::get('/mes-messages', 'messages')->name('external.messages');

        Route::post('/espace-contact/message', 'sendMessage')
            ->name('external.message.store')
            ->middleware('throttle:20,1');

        Route::post('/mes-messages/mark-read', 'markRead')
            ->name('external.messages.mark-read')
            ->middleware('throttle:30,1');

        Route::get('/api/messages/unread-count', 'unreadCount')
            ->name('external.messages.unread-count')
            ->middleware('throttle:120,1');
    });

    // ─── Règlement intérieur ─────────────────────────────────────────
    Route::get('/reglement-interieur', [ExternalAuthController::class, 'reglementInterieur'])
        ->name('external.reglement')
        ->middleware('throttle:30,1');

    // ─── 🔒 Suivi des paiements élèves (données sensibles) ──────────
    // Ce déplacement protège les paiements : ils ne sont plus accessibles
    // aux visiteurs anonymes. Seuls les abonnés connectés y ont accès.
    Route::get('/paiements', [PublicPaiementController::class, 'index'])
        ->name('public.paiements')
        ->middleware('throttle:60,1');

    // ─── 🔒 API détails des salles (utilisée par la page paiements) ──
    Route::get('/api/salles-details', SalleDetailsController::class)
        ->name('api.salles-details')
        ->middleware('throttle:120,1');
});


/*
|==========================================================================
| 4. 🔑 AUTH WEB — Personnel non connecté (guard "web")
|==========================================================================
|
| Login des administrateurs / membres du personnel.
|==========================================================================
*/

Route::middleware('guest')->controller(AuthController::class)->group(function () {
    Route::get('/login',  'showLoginForm')->name('login');
    Route::post('/login', 'login')->name('login.store')->middleware('throttle:6,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth:web');

// ─── Redirection après login (selon le rôle) ─────────────────────────
Route::get('/admin-redirect', AdminRedirectController::class)
    ->name('admin.redirect')
    ->middleware('auth:web');


/*
|==========================================================================
| 5. 🏠 ESPACE MEMBRE — Personnel connecté (guard "web")
|==========================================================================
|
| Espace personnel du personnel scolaire (professeurs, secrétaires, etc.).
| Les permissions sont explicites car les routes `member.*` ne suivent
| pas la convention `admin.*` (utilisée par `permission.route`).
|==========================================================================
*/

Route::middleware('auth:web')->group(function () {

    // ─── Dashboard principal ─────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard.index');

    // ─── Espace membre ───────────────────────────────────────────────
    Route::prefix('member')
        ->name('member.')
        ->group(function () {

            // ─── Profil personnel ────────────────────────────────────
            Route::controller(ProfilController::class)
                ->prefix('profil')
                ->name('profil')
                ->group(function () {
                    Route::get('/',       'show');
                    Route::get('/edit',   'edit')->name('.edit');
                    Route::put('/update', 'update')->name('.update');
                });

            // ─── Demandes d'avance ───────────────────────────────────
            Route::prefix('demandes-avance')
                ->name('demandes-avance.')
                ->controller(MemberDemandeAvanceController::class)
                ->group(function () {
                    Route::get('/',       'index')->name('index');
                    Route::get('/create', 'create')->name('create');
                    Route::post('/',      'store')->name('store');
                });

            // ─── Mes cours (permission: cours.index / cours.assign) ──
            Route::controller(CourController::class)
                ->prefix('cours')
                ->name('cours.')
                ->group(function () {
                    Route::get('/', 'index')->name('index')
                        ->middleware('permission:cours.index');

                    Route::get('/assignements', 'assign')->name('assignements')
                        ->middleware('permission:cours.assignements');
                });

            // ─── Notes (permissions granulaires) ─────────────────────
            Route::prefix('notes')
                ->name('notes.')
                ->controller(NoteController::class)
                ->group(function () {
                    Route::middleware('permission:notes.index')->group(function () {
                        Route::get('/', 'index')->name('index');
                    });

                    Route::middleware('permission:notes.saisie')->group(function () {
                        Route::get('saisie',      'saisie')->name('saisie');
                        Route::post('store-mass', 'storeMass')->name('store-mass');
                    });

                    Route::middleware('permission:notes.bulletin')->group(function () {
                        Route::get('bulletin',       'bulletin')->name('bulletin');
                        Route::get('bulletin-pdf',   'exportBulletinPdf')->name('bulletin.pdf');
                        Route::get('bulletin-print', 'printBulletin')->name('bulletin.print');
                    });

                    Route::middleware('permission:notes.classement')->group(function () {
                        Route::get('classement',       'classement')->name('classement');
                        Route::get('classement-print', 'printClassement')->name('classement.print');
                    });
                });

            // ─── Consultation élèves/inscriptions (professeurs) ──────
            Route::get('eleves', [EleveController::class, 'index'])
                ->name('eleves.index')
                ->middleware('permission:eleves.index');

            Route::get('inscriptions', [InscriptionController::class, 'index'])
                ->name('inscriptions.index')
                ->middleware('permission:inscriptions.index');

            // ─── Mon salaire ─────────────────────────────────────────
            Route::get('salaire', [SalaireController::class, 'show'])
                ->name('salaire')
                ->middleware('permission:salaires.index');
        });
});


/*
|==========================================================================
| 6. 🛡️ ADMINISTRATION
|==========================================================================
|
| Middleware : auth:web + permission.route
|
| `permission.route` :
|   1. Dérive la permission du nom de route
|      (admin.eleves.index → eleves.index)
|   2. Laisse passer super_admin automatiquement
|   3. Vérifie les permissions pour tous les autres rôles
|
| Résultat :
|   - Un secrétaire avec `eleves.index` PEUT accéder à /admin/eleves
|   - Un comptable avec `paiements.index` PEUT accéder à /admin/paiements
|==========================================================================
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth:web', 'permission.route', 'log.route'])
    ->group(function () {

        // ═══════════════════════════════════════════════════════════
        // 📢 COMMUNICATION
        // ═══════════════════════════════════════════════════════════

        // ─── Annonces ────────────────────────────────────────────────
        Route::post('annonces/upload-image', [AnnonceController::class, 'uploadImage'])
            ->name('annonces.upload-image');

        Route::patch('annonces/{annonce}/toggle-active', [AnnonceController::class, 'toggleActive'])
            ->name('annonces.toggle-active')
            ->whereNumber('annonce');

        Route::resource('annonces', AnnonceController::class)
            ->except(['show'])
            ->whereNumber('annonce');

        // ─── Contacts (abonnés) ──────────────────────────────────────
        Route::resource('contacts', AdminContactController::class)
            ->parameters(['contacts' => 'contact'])
            ->whereNumber('contact');

        // ─── Messages reçus ──────────────────────────────────────────
        Route::prefix('messages')
            ->name('messages.')
            ->controller(AdminContactMessageController::class)
            ->group(function () {
                Route::get('/',                  'index')->name('index');
                Route::post('{email}/mark-read', 'markRead')->name('mark-read');
                Route::post('{email}/reply',     'reply')->name('reply');
                Route::post('{email}/treat',     'markConversationTreated')->name('treat');
                Route::delete('{email}/delete',  'deleteConversation')->name('delete');
                Route::delete('{email}',         'destroy')->name('destroy');
            });

        // ─── Emails groupés ──────────────────────────────────────────
        Route::prefix('emails')
            ->name('emails.')
            ->controller(BulkEmailController::class)
            ->group(function () {
                Route::get('compose', 'compose')->name('compose');
                Route::post('send',   'send')->name('send')->middleware('throttle:10,1');
            });

        // ─── Règlement intérieur ─────────────────────────────────────
        Route::patch('reglements/{reglement}/toggle-active', [ReglementInterieurController::class, 'toggleActive'])
            ->name('reglements.toggle-active')
            ->whereNumber('reglement');

        Route::resource('reglements', ReglementInterieurController::class)
            ->whereNumber('reglement');

        // ═══════════════════════════════════════════════════════════
        // ⚙️ CONFIGURATION
        // ═══════════════════════════════════════════════════════════

        // ─── Années scolaires ────────────────────────────────────────
        Route::prefix('annees-scolaires')
            ->name('annees-scolaires.')
            ->controller(AnneeScolaireController::class)
            ->group(function () {
                Route::get('corbeille', 'corbeille')->name('corbeille');

                Route::patch('{anneeScolaire}/toggle-cloture',      'toggleCloture')->name('toggle-cloture');
                Route::patch('{anneeScolaire}/toggle-paiement',     'togglePaiement')->name('toggle-paiement');
                Route::post('{anneeScolaire}/regenerer-periodes',   'regenererPeriodes')->name('regenerer-periodes');
                Route::get('{anneeScolaire}/cloturer',              'cloturer')->name('cloturer');
                Route::post('{anneeScolaire}/appliquer-transition', 'appliquerTransition')->name('appliquer-transition');

                Route::patch('{id}/restaurer',     'restaurer')->name('restaurer')->whereNumber('id');
                Route::delete('{id}/force-delete', 'forceDelete')->name('force-delete')->whereNumber('id');
            });

        Route::resource('annees-scolaires', AnneeScolaireController::class)
            ->parameters(['annees-scolaires' => 'anneeScolaire'])
            ->whereNumber('anneeScolaire');

        // ─── Mois scolaires ──────────────────────────────────────────
        Route::controller(MoisScolaireController::class)
            ->prefix('mois-scolaires')
            ->name('mois-scolaires.')
            ->group(function () {
                Route::post('generer', 'generer')->name('generer');
                Route::delete('vider', 'vider')->name('vider');
            });

        Route::resource('mois-scolaires', MoisScolaireController::class)
            ->parameters(['mois-scolaires' => 'moisScolaire'])
            ->whereNumber('moisScolaire');

        // ─── Tranches scolaires ──────────────────────────────────────
        Route::controller(TrancheScolaireController::class)
            ->prefix('tranches-scolaires')
            ->name('tranches-scolaires.')
            ->group(function () {
                Route::post('generer', 'generer')->name('generer');
                Route::delete('vider', 'vider')->name('vider');
            });

        Route::resource('tranches-scolaires', TrancheScolaireController::class)
            ->parameters(['tranches-scolaires' => 'trancheScolaire'])
            ->whereNumber('trancheScolaire');

        // ─── Ressources de base ──────────────────────────────────────
        Route::resources([
            'sessions'   => SessionController::class,
            'sections'   => SectionController::class,
            'options'    => OptionController::class,
            'fonctions'  => FonctionController::class,
            'users'      => UserController::class,
            'categories' => CategorieController::class,
            'devises'    => DeviseController::class,
        ]);

        Route::resource('salles-de-classe', SalleDeClasseController::class)
            ->parameters(['salles-de-classe' => 'salleDeClasse'])
            ->whereNumber('salleDeClasse');

        // ─── Paramètres du site ──────────────────────────────────────
        Route::prefix('settings')
            ->name('settings.')
            ->controller(SettingController::class)
            ->group(function () {
                Route::get('edit',        'edit')->name('edit');
                Route::put('/',           'update')->name('update');
                Route::get('canvas',      'canvas')->name('canvas.edit');
                Route::post('canvas',     'storeCanvas')->name('canvas.store');
                Route::get('canvas/load', 'loadCanvas')->name('canvas.load');
            });

        Route::prefix('site-settings')
            ->name('site-settings.')
            ->controller(SiteSettingController::class)
            ->group(function () {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                Route::post('/carousel', 'storeCarouselImage')->name('carousel.store');
                Route::delete('/carousel/{carouselImage}', 'destroyCarouselImage')
                    ->name('carousel.destroy')
                    ->whereNumber('carouselImage');
            });

        // ═══════════════════════════════════════════════════════════
        // 📚 PÉDAGOGIE
        // ═══════════════════════════════════════════════════════════

        // ─── Cours ───────────────────────────────────────────────────
        Route::resource('cours', CourController::class);

        Route::controller(CourController::class)->group(function () {
            Route::get('cours/{cour}/assign',        'assign')->name('cours.assign')->whereNumber('cour');
            Route::post('cours/{cour}/assign',       'storeAssign')->name('cours.assign.store')->whereNumber('cour');
            Route::get('cours-assign/{assign}/edit', 'editAssign')->name('cours.assign.edit')->whereNumber('assign');
            Route::put('cours-assign/{assign}',      'updateAssign')->name('cours.assign.update')->whereNumber('assign');
            Route::delete('cours-assign/{assign}',   'destroyAssign')->name('cours.assign.destroy')->whereNumber('assign');
        });

        // ─── Références (libellés, pondérations, heures, créneaux) ───
        Route::prefix('references/{type}')
            ->name('references.')
            ->whereIn('type', ['libelles', 'ponderations', 'nombre-heures', 'creneaux-horaires'])
            ->controller(ReferenceController::class)
            ->group(function () {
                Route::get('/',          'index')->name('index');
                Route::get('/create',    'create')->name('create');
                Route::post('/',         'store')->name('store');
                Route::get('/{id}/edit', 'edit')->name('edit')->whereNumber('id');
                Route::put('/{id}',      'update')->name('update')->whereNumber('id');
                Route::delete('/{id}',   'destroy')->name('destroy')->whereNumber('id');
            });

        // ─── Périodes de notes ───────────────────────────────────────
        Route::patch('periode-notes/{periodeNote}/toggle', [PeriodeNoteController::class, 'toggle'])
            ->name('periode-notes.toggle')
            ->whereNumber('periodeNote');

        Route::resource('periode-notes', PeriodeNoteController::class)
            ->parameters(['periode-notes' => 'periodeNote'])
            ->whereNumber('periodeNote');

        // ─── Notes ───────────────────────────────────────────────────
        Route::prefix('notes')
            ->name('notes.')
            ->controller(NoteController::class)
            ->group(function () {
                Route::get('/',                'index')->name('index');
                Route::get('saisie',           'saisie')->name('saisie');
                Route::post('store-mass',      'storeMass')->name('store-mass');
                Route::get('bulletin',         'bulletin')->name('bulletin');
                Route::get('bulletin-pdf',     'exportBulletinPdf')->name('bulletin.pdf');
                Route::get('bulletin-print',   'printBulletin')->name('bulletin.print');
                Route::get('classement',       'classement')->name('classement');
                Route::get('classement-print', 'printClassement')->name('classement.print');
                Route::get('get-salle-data',   'getSalleData')->name('get-salle-data');
                Route::get('eleves-par-salle', 'getElevesParSalle')->name('eleves-par-salle');
                Route::get('notes-eleve',      'getNotesEleve')->name('notes-eleve');
            });

        // ═══════════════════════════════════════════════════════════
        // 👥 INSCRIPTIONS & ÉLÈVES
        // ═══════════════════════════════════════════════════════════

        Route::resource('eleves', EleveController::class)
            ->parameters(['eleves' => 'eleve'])
            ->whereNumber('eleve');

        Route::prefix('info-eleves')
            ->name('info-eleves.')
            ->controller(InfoEleveController::class)
            ->group(function () {
                Route::get('/',           'index')->name('index');
                Route::get('export/pdf',  'exportPdf')->name('export.pdf');
                Route::get('export/csv',  'exportCsv')->name('export.csv');
                Route::get('export/xml',  'exportXml')->name('export.xml');
                Route::get('export/word', 'exportWord')->name('export.word');
                Route::get('imprimer',    'imprimer')->name('imprimer');
                Route::get('{eleve}',     'show')->name('show')->whereNumber('eleve');
            });

        Route::prefix('inscriptions')
            ->name('inscriptions.')
            ->controller(InscriptionController::class)
            ->group(function () {
                Route::get('export/pdf',         'exportPdf')->name('export.pdf');
                Route::get('export/csv',         'exportCsv')->name('export.csv');
                Route::get('export/xml',         'exportXml')->name('export.xml');
                Route::get('export/doc',         'exportDoc')->name('export.doc');
                Route::get('imprimer',           'imprimer')->name('imprimer');
                Route::get('eleves-disponibles', 'getElevesDisponibles')->name('eleves-disponibles');
                Route::delete('truncate',        'truncate')->name('truncate');
            });

        Route::resource('inscriptions', InscriptionController::class)
            ->parameters(['inscriptions' => 'inscription'])
            ->whereNumber('inscription');

        // ═══════════════════════════════════════════════════════════
        // 💰 FINANCES
        // ═══════════════════════════════════════════════════════════

        // ─── Statistiques & Taux de change ──────────────────────────
        Route::get('statistiques', [StatistiqueController::class, 'index'])
            ->name('statistiques.index');

        Route::controller(TauxChangeController::class)
            ->prefix('taux-change')
            ->name('taux.')
            ->group(function () {
                Route::get('edit', 'edit')->name('edit');
                Route::put('/',    'update')->name('update');
            });

        // ─── Salaires ────────────────────────────────────────────────
        Route::controller(SalaireController::class)
            ->prefix('salaires')
            ->name('salaires.')
            ->group(function () {
                Route::get('/',           'index')->name('index');
                Route::get('{user}/edit', 'edit')->name('edit')->whereNumber('user');
                Route::put('{user}',      'update')->name('update')->whereNumber('user');
            });

        // ─── Paiements salaires ──────────────────────────────────────
        Route::prefix('paiement-salaires')
            ->name('paiement-salaires.')
            ->controller(SalairePaiementController::class)
            ->group(function () {
                Route::get('export/pdf',  'exportPdf')->name('export.pdf');
                Route::get('export/csv',  'exportCsv')->name('export.csv');
                Route::get('export/xml',  'exportXml')->name('export.xml');
                Route::get('export/word', 'exportWord')->name('export.word');

                Route::patch('{id}/restaurer',     'restaurer')->name('restaurer')->whereNumber('id');
                Route::delete('{id}/force-delete', 'forceDelete')->name('force-delete')->whereNumber('id');
            });

        Route::resource('paiement-salaires', SalairePaiementController::class)
            ->parameters(['paiement-salaires' => 'paiementSalaire'])
            ->whereNumber('paiementSalaire');

        // ─── Taux horaires ───────────────────────────────────────────
        Route::prefix('salaire-horaires')
            ->name('salaire-horaires.')
            ->controller(SalaireHoraireController::class)
            ->group(function () {
                Route::get('/',                           'index')->name('index');
                Route::get('/create',                     'create')->name('create');
                Route::post('/',                          'store')->name('store');
                Route::get('/{salaireHoraire}/edit',      'edit')->name('edit')->whereNumber('salaireHoraire');
                Route::put('/{salaireHoraire}',           'update')->name('update')->whereNumber('salaireHoraire');
                Route::patch('/{salaireHoraire}/activer', 'activer')->name('activer')->whereNumber('salaireHoraire');
                Route::delete('/{salaireHoraire}',        'destroy')->name('destroy')->whereNumber('salaireHoraire');
            });

        // ─── Paiements élèves ────────────────────────────────────────
        Route::get('paiements/create-multiple', [PaiementController::class, 'createMultiple'])
            ->name('paiements.create-multiple');

        Route::resource('paiements', PaiementController::class)->whereNumber('paiement');

        // ─── Planification paiements ─────────────────────────────────
        Route::post('planification-paiements/planifier-toutes', [PlanificationPaiementController::class, 'planifierToutes'])
            ->name('planification-paiements.planifier-toutes');

        Route::controller(PlanificationPaiementController::class)->group(function () {
            Route::get('planification-paiements',                   'index')->name('planification-paiements.index');
            Route::get('planification-paiements/{salle}',           'show')->name('planification-paiements.show')->whereNumber('salle');
            Route::post('planification-paiements/{salle}/sessions', 'store')->name('planification-paiements.sessions.store')->whereNumber('salle');
            Route::put('sessions-paiement/{session}',               'update')->name('sessions-paiement.update')->whereNumber('session');
            Route::delete('sessions-paiement/{session}',            'destroy')->name('sessions-paiement.destroy')->whereNumber('session');
        });

        // ─── Échéances ───────────────────────────────────────────────
        Route::delete('echeances/vider', [EcheanceController::class, 'vider'])->name('echeances.vider');

        Route::controller(EcheanceController::class)
            ->prefix('echeances')
            ->name('echeances.')
            ->group(function () {
                Route::get('/',        'index')->name('index');
                Route::post('generer', 'generer')->name('generer');
            });

        // ─── Dashboard paiements ─────────────────────────────────────
        Route::get('paiement-dashboard', [PaiementDashboardController::class, 'index'])
            ->name('paiement-dashboard.index');

        // ─── Frais supplémentaires ───────────────────────────────────
        Route::patch('frais-supplementaires/{fraisSupplementaire}/toggle', [FraisSupplementaireController::class, 'toggleOuverture'])
            ->name('frais-supplementaires.toggle')
            ->whereNumber('fraisSupplementaire');

        Route::resource('frais-supplementaires', FraisSupplementaireController::class)
            ->parameters(['frais-supplementaires' => 'fraisSupplementaire'])
            ->whereNumber('fraisSupplementaire');

        // ─── Paiements frais supplémentaires ─────────────────────────
        Route::get('paiement-frais-supplementaires/export/{format}', [PaiementFraisSupplementaireController::class, 'export'])
            ->name('paiement-frais-supplementaires.export');

        Route::resource('paiement-frais-supplementaires', PaiementFraisSupplementaireController::class)
            ->parameters(['paiement-frais-supplementaires' => 'paiement'])
            ->whereNumber('paiement');

        // ─── Info paiements ──────────────────────────────────────────
        Route::prefix('info-paiements')
            ->name('info-paiements.')
            ->controller(InfoPaiementController::class)
            ->group(function () {
                Route::get('/',                        'index')->name('index');
                Route::get('export/pdf',               'exportPdfPaiements')->name('export.pdf');
                Route::get('export/csv',               'exportCsvPaiements')->name('export.csv');
                Route::get('export/xml',               'exportXmlPaiements')->name('export.xml');
                Route::get('export/word',              'exportWordPaiements')->name('export.word');
                Route::get('imprimer',                 'imprimerPaiements')->name('imprimer');
                Route::get('paiement/{paiement}/recu', 'recuPaiement')->name('recu.paiement')->whereNumber('paiement');
                Route::get('frais/{paiement}/recu',    'recuFraisSupplementaire')->name('recu.frais')->whereNumber('paiement');
            });

        // ═══════════════════════════════════════════════════════════
        // 💸 AVANCES & DEMANDES
        // ═══════════════════════════════════════════════════════════

        Route::resource('avances', AvanceSalaireController::class)
            ->parameters(['avances' => 'avance'])
            ->except(['show'])
            ->whereNumber('avance');

        Route::patch('session-avances/{session_avance}/fermer', [SessionAvanceController::class, 'fermer'])
            ->name('session-avances.fermer')
            ->whereNumber('session_avance');

        Route::resource('session-avances', SessionAvanceController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->parameters(['session-avances' => 'session_avance'])
            ->whereNumber('session_avance');

        Route::prefix('demandes-avance')
            ->name('demandes-avance.')
            ->controller(AdminDemandeAvanceController::class)
            ->group(function () {
                Route::get('/',                  'index')->name('index');
                Route::get('{demande}',          'show')->name('show')->whereNumber('demande');
                Route::post('{demande}/valider', 'valider')->name('valider')->whereNumber('demande');
                Route::post('{demande}/refuser', 'refuser')->name('refuser')->whereNumber('demande');
            });

        // ═══════════════════════════════════════════════════════════
        // 🔒 SÉCURITÉ & PERMISSIONS
        // ═══════════════════════════════════════════════════════════

        // ─── Rôles ───────────────────────────────────────────────────
        Route::post('roles/sync-admin-permissions', [RoleController::class, 'syncAllPermissionsToAdmin'])
            ->name('roles.sync-admin-permissions');

        Route::prefix('roles/{role}')
            ->name('roles.')
            ->controller(RoleController::class)
            ->group(function () {
                Route::get('assign-users',      'assignUsers')->name('assign-users');
                Route::post('sync-users',       'syncUsers')->name('sync-users');
                Route::get('permissions',       'permissions')->name('permissions');
                Route::post('sync-permissions', 'syncPermissions')->name('sync-permissions');
            });

        Route::resource('roles', RoleController::class)->whereNumber('role');

        // ─── Permissions ─────────────────────────────────────────────
        Route::prefix('permissions')
            ->name('permissions.')
            ->controller(PermissionController::class)
            ->group(function () {
                Route::get('manage',                 'manage')->name('manage');
                Route::get('sync-all',               'syncAll')->name('sync-all');
                Route::post('sync-all-assign-admin', 'syncAllAndAssignAdmin')->name('sync-all-assign-admin');
                Route::get('bulk-create',            'bulkCreate')->name('bulk-create');
                Route::post('sync/{role}',           'sync')->name('sync')->whereNumber('role');

                Route::get('{type}/{id}/permissions', 'getTargetPermissions')
                    ->name('target.permissions')
                    ->whereNumber('id');

                Route::post('{type}/{id}/sync', 'syncTargetPermissions')
                    ->name('target.sync')
                    ->whereNumber('id');
            });

        Route::resource('permissions', PermissionController::class)->except(['show']);
    });


/*
|==========================================================================
| 7. 🌐 API INTERNE (protégée — voir section 3)
|==========================================================================
|
| Toutes les routes API sont déclarées dans les sections précédentes
| avec les middlewares appropriés (auth:contact, throttle...).
|==========================================================================
*/


/*
|==========================================================================
| 8. 🚫 FALLBACK — 404
|==========================================================================
*/

Route::fallback(fn () => response()->view('errors.404', [], 404));