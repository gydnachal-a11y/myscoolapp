<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const MESSAGES_PER_CONVERSATION = 200;
    private const CONVERSATIONS_LIMIT       = 500;

    /** Clé de cache du compteur admin (partagée avec AppServiceProvider). */
    private const CACHE_KEY_ADMIN_UNREAD = 'admin_unread_messages';

    // ============================================================
    // INDEX — LISTE DES CONVERSATIONS
    // ============================================================

    public function index(): View
    {
        /* ---------------------------------------------------------
         | 1. Tous les abonnés (même sans message)
         | --------------------------------------------------------- */
        $contacts = Contact::query()
            ->orderBy('nom')
            ->limit(self::CONVERSATIONS_LIMIT)
            ->get(['id', 'nom', 'email']);

        /* ---------------------------------------------------------
         | 2. Dernier message par email
         | --------------------------------------------------------- */
        $latestIds = ContactMessage::query()
            ->selectRaw('MAX(id) as last_id')
            ->groupBy('email')
            ->pluck('last_id');

        $latestMessages = ContactMessage::query()
            ->whereIn('id', $latestIds)
            ->get()
            ->keyBy('email');

        /* ---------------------------------------------------------
         | 3. Comptes non-lus par email
         | --------------------------------------------------------- */
        $unreadCounts = ContactMessage::query()
            ->selectRaw('email, COUNT(*) as unread')
            ->where('lu', false)
            ->where('from_admin', false)
            ->groupBy('email')
            ->pluck('unread', 'email')
            ->all();

        /* ---------------------------------------------------------
         | 4. Construire la liste des conversations
         | --------------------------------------------------------- */
        $conversations = [];
        $seenEmails    = [];

        foreach ($contacts as $contact) {
            $email = (string) $contact->email;
            if ($email === '') {
                continue;
            }

            $seenEmails[$email] = true;
            $lastMsg = $latestMessages->get($email);

            $conversations[] = [
                'email'         => $email,
                'contact_id'    => $contact->id,
                'name'          => $contact->nom ?: $email,
                'initial'       => $this->extractInitial($contact->nom ?: $email),
                'last_subject'  => $lastMsg?->sujet ?? 'Aucun message pour le moment',
                'last_time'     => $lastMsg?->created_at->format('H:i') ?? '',
                'last_ts'       => $lastMsg?->created_at->timestamp ?? 0,
                'last_date'     => $lastMsg?->created_at->format('d/m/Y') ?? '',
                'unread'        => (int) ($unreadCounts[$email] ?? 0),
                'has_messages'  => $lastMsg !== null,
            ];
        }

        foreach ($latestMessages as $email => $msg) {
            if (isset($seenEmails[$email])) {
                continue;
            }

            $conversations[] = [
                'email'         => $email,
                'contact_id'    => $msg->contact_id,
                'name'          => $msg->nom ?: $email,
                'initial'       => $this->extractInitial($msg->nom ?: $email),
                'last_subject'  => $msg->sujet,
                'last_time'     => $msg->created_at->format('H:i'),
                'last_ts'       => $msg->created_at->timestamp,
                'last_date'     => $msg->created_at->format('d/m/Y'),
                'unread'        => (int) ($unreadCounts[$email] ?? 0),
                'has_messages'  => true,
            ];
        }

        usort($conversations, function (array $a, array $b): int {
            $aEmpty = $a['last_ts'] === 0;
            $bEmpty = $b['last_ts'] === 0;

            if ($aEmpty && $bEmpty) return strcmp($a['name'], $b['name']);
            if ($aEmpty) return 1;
            if ($bEmpty) return -1;
            return $b['last_ts'] <=> $a['last_ts'];
        });

        /* ---------------------------------------------------------
         | 5. Charger les messages des conversations
         | --------------------------------------------------------- */
        $emailsWithMessages = array_column(
            array_filter($conversations, fn ($c) => $c['has_messages']),
            'email'
        );

        $messages = [];

        if (!empty($emailsWithMessages)) {
            $messagesRaw = ContactMessage::query()
                ->whereIn('email', $emailsWithMessages)
                ->orderBy('created_at')
                ->get()
                ->groupBy('email');

            foreach ($messagesRaw as $email => $items) {
                $items = $items->slice(-self::MESSAGES_PER_CONVERSATION);

                foreach ($items as $msg) {
                    $messages[] = [
                        'id'         => $msg->id,
                        'email'      => $email,
                        'contact_id' => $msg->contact_id,
                        'message'    => $msg->message,
                        'time'       => $msg->created_at->format('H:i'),
                        'date'       => $msg->created_at->format('d/m/Y'),
                        'from_admin' => (bool) $msg->from_admin,
                    ];
                }
            }
        }

        $totalAbonnes = $contacts->count();

        /* ---------------------------------------------------------
         | ✅ 6. NOUVEAU — Marquer tous les messages entrants comme lus
         |
         | L'admin a maintenant "vu" les messages → le badge disparaît
         | sur la prochaine page.
         | --------------------------------------------------------- */
        $this->markAllIncomingAsRead();

        return view('admin.messages.index', compact(
            'conversations',
            'messages',
            'totalAbonnes',
        ));
    }

    // ============================================================
    // MARQUER COMME LU
    // ============================================================

    public function markRead(Request $request, string $email): JsonResponse
    {
        $this->validateEmail($email);

        $updated = ContactMessage::query()
            ->where('email', $email)
            ->where('from_admin', false)
            ->where(function ($q): void {
                $q->where('lu', false)->orWhereNull('lu');
            })
            ->update(['lu' => true, 'updated_at' => now()]);

        // ✅ Invalide le compteur admin
        Cache::forget(self::CACHE_KEY_ADMIN_UNREAD);

        $this->logAction($request, 'marked_read', [
            'email_hash' => $this->hashEmail($email),
            'count'      => $updated,
        ]);

        return response()->json(['success' => true, 'updated' => $updated]);
    }

    // ============================================================
    // RÉPONDRE
    // ============================================================

    public function reply(Request $request, string $email): JsonResponse
    {
        $this->validateEmail($email);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'sujet'   => ['nullable', 'string', 'max:255'],
        ]);

        $contact = Contact::where('email', $email)->first();

        $nom = $contact?->nom
            ?: ContactMessage::query()
                ->where('email', $email)
                ->where('from_admin', false)
                ->latest('id')
                ->value('nom')
            ?: $email;

        $message = DB::transaction(function () use ($validated, $email, $contact, $nom): ContactMessage {
            $msg = ContactMessage::create([
                'contact_id' => $contact?->id,
                'nom'        => $nom,
                'email'      => $email,
                'sujet'      => $validated['sujet'] ?? ContactMessage::DEFAULT_REPLY_SUBJECT,
                'message'    => $validated['message'],
                'lu'         => true,
                'traite'     => true,
                'from_admin' => true,
            ]);

            // Auto-marquer les messages entrants comme lus ET traités
            ContactMessage::query()
                ->where('email', $email)
                ->where('from_admin', false)
                ->where(function ($q): void {
                    $q->where('lu', false)->orWhere('traite', false);
                })
                ->update([
                    'lu'         => true,
                    'traite'     => true,
                    'updated_at' => now(),
                ]);

            return $msg;
        });

        // ✅ Invalide les caches
        Cache::forget(self::CACHE_KEY_ADMIN_UNREAD);
        $this->invalidateContactUnreadCache($contact?->id);

        $this->logAction($request, 'replied', [
            'email_hash' => $this->hashEmail($email),
            'message_id' => $message->id,
            'contact_id' => $contact?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id'         => $message->id,
                'email'      => $message->email,
                'contact_id' => $message->contact_id,
                'message'    => $message->message,
                'time'       => $message->created_at->format('H:i'),
                'date'       => $message->created_at->format('d/m/Y'),
                'from_admin' => true,
            ],
        ]);
    }

    // ============================================================
    // MARQUER COMME TRAITÉ
    // ============================================================

    public function markConversationTreated(Request $request, string $email): JsonResponse
    {
        $this->validateEmail($email);

        $updated = ContactMessage::query()
            ->where('email', $email)
            ->where('from_admin', false)
            ->where('traite', false)
            ->update(['traite' => true, 'updated_at' => now()]);

        Cache::forget(self::CACHE_KEY_ADMIN_UNREAD);

        $this->logAction($request, 'marked_treated', [
            'email_hash' => $this->hashEmail($email),
            'count'      => $updated,
        ]);

        return response()->json(['success' => true, 'updated' => $updated]);
    }

    // ============================================================
    // SUPPRIMER UNE CONVERSATION
    // ============================================================

    public function deleteConversation(Request $request, string $email): JsonResponse
    {
        $this->validateEmail($email);

        $contactId = $request->integer('contact_id');

        $query = ContactMessage::query()->where('email', $email);

        if ($contactId > 0) {
            $query->where(function ($q) use ($contactId): void {
                $q->where('contact_id', $contactId)
                  ->orWhereNull('contact_id');
            });
        }

        $deleted = $query->delete();

        Cache::forget(self::CACHE_KEY_ADMIN_UNREAD);
        $this->invalidateContactUnreadCache($contactId > 0 ? $contactId : null);

        $this->logAction($request, 'conversation_deleted', [
            'email_hash' => $this->hashEmail($email),
            'count'      => $deleted,
        ]);

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }

    // ============================================================
    // SUPPRIMER UN MESSAGE INDIVIDUEL
    // ============================================================

    public function destroy(Request $request, ContactMessage $message): RedirectResponse
    {
        $messageId = $message->id;
        $message->delete();

        Cache::forget(self::CACHE_KEY_ADMIN_UNREAD);

        $this->logAction($request, 'message_deleted', ['message_id' => $messageId]);

        return redirect()
            ->route('admin.messages.index')
            ->with('success', 'Message supprimé.');
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * ✅ Marque tous les messages entrants comme lus.
     *
     * Appelée depuis `index()` : dès que l'admin ouvre la messagerie,
     * tous les messages sont considérés comme vus → le badge disparaît.
     */
    private function markAllIncomingAsRead(): void
    {
        $updated = ContactMessage::query()
            ->where('from_admin', false)
            ->where(function ($q): void {
                $q->where('lu', false)->orWhereNull('lu');
            })
            ->update(['lu' => true, 'updated_at' => now()]);

        // ✅ Invalide le cache pour que la prochaine requête retourne 0
        Cache::forget(self::CACHE_KEY_ADMIN_UNREAD);

        if ($updated > 0) {
            Log::info('Admin: messages marqués lus automatiquement', [
                'count' => $updated,
            ]);
        }
    }

    /**
     * Invalide le cache du compteur de non-lus d'un contact.
     */
    private function invalidateContactUnreadCache(?int $contactId): void
    {
        if ($contactId) {
            Cache::forget("contact_{$contactId}_unread_messages");
        }
    }

    private function extractInitial(?string $nom): string
    {
        $nom = trim((string) $nom);

        return $nom === ''
            ? '?'
            : mb_strtoupper(mb_substr($nom, 0, 1));
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            abort(404, 'Email invalide.');
        }
    }

    private function hashEmail(string $email): string
    {
        return substr(hash('sha256', strtolower($email)), 0, 16);
    }

    private function logAction(Request $request, string $action, array $extra = []): void
    {
        Log::info("ContactMessage: {$action}", array_merge([
            'user_id' => $request->user()?->id,
            'ip'      => $request->ip(),
        ], $extra));
    }
}