@extends('layouts.contact')

@section('title', 'Mes messages')

@section('content')
@php
    $urls = [
        'send'     => route('external.message.store'),
        'markRead' => route('external.messages.mark-read'),
    ];
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="chat-wrapper" x-data="subscriberChat()" x-init="init()" x-cloak>

    {{-- ===== EN-TÊTE ===== --}}
    <header class="chat-header">
        <div class="chat-header-info">
            <div class="avatar-admin" aria-hidden="true">
                <i class="bi bi-headset"></i>
            </div>
            <div class="chat-header-text">
                <h4>Administration</h4>
                <small class="status">
                    <span class="status-dot" aria-hidden="true"></span>
                    En ligne
                </small>
            </div>
        </div>

        <a href="{{ route('external.dashboard') }}"
           class="btn-back"
           aria-label="Retour au tableau de bord">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>Retour</span>
        </a>
    </header>

    {{-- ===== MESSAGES ===== --}}
    <div class="messages-list" x-ref="messageList" aria-live="polite">

        {{-- État vide --}}
        <template x-if="messages.length === 0">
            <div class="empty-thread">
                <div class="empty-thread-icon">
                    <i class="bi bi-chat-heart" aria-hidden="true"></i>
                </div>
                <h4>Commencez la conversation</h4>
                <p>Envoyez votre premier message à l'administration ci-dessous.</p>
            </div>
        </template>

        {{-- Fil de messages --}}
        <template x-for="(msg, idx) in messages" :key="msg.id">
            <div class="message-block">
                <div class="date-separator"
                     x-show="shouldShowDate(idx)"
                     x-text="formatDate(msg.date)">
                </div>

                <div class="message-row"
                     :class="msg.from_admin ? 'from-admin' : 'from-subscriber'">
                    <div class="bubble">
                        <div class="bubble-meta">
                            <span class="sender"
                                  x-text="msg.from_admin ? 'Administration' : 'Moi'"></span>
                            <span class="time" x-text="msg.time"></span>
                        </div>
                        <p x-text="msg.message"></p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- ===== BANNIÈRE D'ERREUR ===== --}}
    <div x-show="error"
         x-transition.opacity
         class="error-banner"
         role="alert">
        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
        <span x-text="error"></span>
    </div>

    {{-- ===== FORMULAIRE ===== --}}
    <form class="reply-form" @submit.prevent="sendReply()" :aria-busy="loading">
        <textarea x-model="replyText"
                  x-ref="replyInput"
                  rows="1"
                  maxlength="5000"
                  placeholder="Écrivez votre message… (Entrée pour envoyer)"
                  aria-label="Votre message"
                  @input="autoResize($event)"
                  @keydown.enter.exact.prevent="sendReply()"
                  @keydown.enter.shift.prevent="$event.target.value += '\n'; autoResize($event)"></textarea>

        <button type="submit"
                class="send-btn"
                :disabled="!replyText.trim() || loading"
                :aria-label="loading ? 'Envoi en cours…' : 'Envoyer le message'">
            <i class="bi"
               :class="loading ? 'bi-arrow-clockwise spin' : 'bi-send'"
               aria-hidden="true"></i>
        </button>
    </form>
</div>

<style>
    /* ============================================================
       X-CLOAK
       ============================================================ */
    [x-cloak] { display: none !important; }

    /* ============================================================
       CONTENEUR
       ============================================================ */
    .chat-wrapper {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 150px);
        height: calc(100dvh - 150px);
        min-height: 500px;
        max-width: 800px;
        margin: 1rem auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid #f1f5f9;
    }

    /* ============================================================
       EN-TÊTE
       ============================================================ */
    .chat-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9fafb;
        gap: 1rem;
        flex-shrink: 0;
    }
    .chat-header-info {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }
    .avatar-admin {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(102,126,234,0.3);
    }
    .chat-header-text { min-width: 0; }
    .chat-header-text h4 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .chat-header-text .status {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.78rem;
        color: #10b981;
        font-weight: 500;
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.9rem;
        border-radius: 999px;
        border: 1.5px solid #e2e8f0;
        background: white;
        color: #475569;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .btn-back:hover {
        border-color: #667eea;
        color: #667eea;
        background: #f8fafc;
    }

    /* ============================================================
       LISTE DES MESSAGES
       ============================================================ */
    .messages-list {
        flex: 1;
        overflow-y: auto;
        padding: 1.25rem 1.25rem 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        background: #f8fafc;
        scroll-behavior: smooth;
        scrollbar-width: thin;
    }

    .message-block { display: contents; }

    .date-separator {
        text-align: center;
        font-size: 0.72rem;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0.75rem 0 0.5rem;
        position: relative;
    }
    .date-separator::before,
    .date-separator::after {
        content: '';
        position: absolute;
        top: 50%;
        width: calc(50% - 3.5rem);
        height: 1px;
        background: #e2e8f0;
    }
    .date-separator::before { left: 0.5rem; }
    .date-separator::after  { right: 0.5rem; }

    .message-row {
        display: flex;
        animation: msgIn 0.25s ease-out;
    }
    @keyframes msgIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .message-row.from-admin { justify-content: flex-start; }
    .message-row.from-subscriber { justify-content: flex-end; }

    .bubble {
        max-width: 75%;
        padding: 0.7rem 1rem;
        border-radius: 18px;
        font-size: 0.95rem;
        line-height: 1.55;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    .from-admin .bubble {
        background: white;
        border: 1px solid #e5e7eb;
        border-bottom-left-radius: 4px;
        color: #1e293b;
    }
    .from-subscriber .bubble {
        background: linear-gradient(135deg, #667eea, #5a52d5);
        color: white;
        border-bottom-right-radius: 4px;
    }

    .bubble-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .from-subscriber .bubble-meta { color: rgba(255,255,255,0.85); }
    .from-admin .bubble-meta { color: #94a3b8; }
    .bubble-meta .sender { text-transform: uppercase; letter-spacing: 0.3px; }
    .bubble p { margin: 0; white-space: pre-wrap; }

    .empty-thread {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 0.5rem;
        padding: 2rem 1.5rem;
        color: #64748b;
    }
    .empty-thread-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, #eef2ff, #e0e7ff);
        color: #667eea;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }
    .empty-thread h4 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #334155;
        margin: 0;
    }
    .empty-thread p {
        font-size: 0.9rem;
        max-width: 320px;
        margin: 0;
        line-height: 1.5;
    }

    .error-banner {
        background: #fef2f2;
        border-top: 1px solid #fecaca;
        color: #b91c1c;
        padding: 0.65rem 1.25rem;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .reply-form {
        display: flex;
        gap: 0.5rem;
        padding: 0.9rem 1.25rem;
        border-top: 1px solid #e5e7eb;
        background: white;
        align-items: flex-end;
        flex-shrink: 0;
    }
    .reply-form textarea {
        flex: 1;
        border: 2px solid #e2e8f0;
        border-radius: 20px;
        padding: 0.75rem 1.1rem;
        font-size: 0.95rem;
        font-family: inherit;
        resize: none;
        outline: none;
        min-height: 46px;
        max-height: 140px;
        line-height: 1.5;
        transition: border-color 0.2s, background 0.2s;
        background: #f8fafc;
    }
    .reply-form textarea:focus {
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }

    .send-btn {
        background: #1e293b;
        color: white;
        border: none;
        width: 46px;
        height: 46px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.15rem;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .send-btn:hover:not(:disabled) {
        background: #667eea;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(102,126,234,0.35);
    }
    .send-btn:disabled { opacity: 0.4; cursor: not-allowed; }

    .spin {
        display: inline-block;
        animation: spin 0.9s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .chat-wrapper {
            max-width: 100%;
            margin: 0.75rem;
            border-radius: 16px;
        }
        .bubble { max-width: 85%; }
        .messages-list { padding: 1rem; }
    }

    @media (max-width: 576px) {
        .chat-wrapper {
            height: calc(100dvh - 60px);
            min-height: 400px;
            border-radius: 0;
            margin: 0;
            box-shadow: none;
            border: none;
        }
        .chat-header { padding: 0.85rem 1rem; }
        .avatar-admin { width: 40px; height: 40px; font-size: 1rem; }
        .chat-header-text h4 { font-size: 0.98rem; }
        .btn-back span { display: none; }
        .btn-back { padding: 0.5rem 0.7rem; }

        .messages-list { padding: 1rem 0.85rem 1.25rem; }
        .bubble { max-width: 88%; font-size: 0.9rem; padding: 0.65rem 0.9rem; }
        .reply-form { padding: 0.75rem 0.85rem; }
        .reply-form textarea { font-size: 16px; }
    }

    @media (max-width: 400px) {
        .chat-header-text h4 { font-size: 0.9rem; }
        .chat-header-text .status { font-size: 0.72rem; }
        .bubble { max-width: 92%; }
        .empty-thread h4 { font-size: 1rem; }
        .empty-thread p { font-size: 0.85rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .message-row { animation: none; }
        .spin { animation-duration: 2s; }
        .messages-list { scroll-behavior: auto; }
    }
</style>

<script>
function subscriberChat() {
    return {
        messages: {{ Js::from($messages) }},
        replyText: '',
        loading: false,
        error: '',
        hasMarkedRead: false,   // ✅ évite les appels multiples

        urls: {{ Js::from($urls) }},

        init() {
            // ✅ 1. Reset le badge navbar IMMÉDIATEMENT au chargement
            //    (l'utilisateur est en train de lire ses messages)
            this.resetBadge();

            // ✅ 2. Marque tous les messages admin comme lus côté serveur
            this.markAllAsRead();

            // ✅ 3. Scroll + focus
            this.scrollToBottom();
            this.$nextTick(() => {
                this.$refs.replyInput?.focus();
            });
        },

        /* ============================================================
           BADGE DE NOTIFICATION
           ============================================================ */
        resetBadge() {
            // Déclenche un event écouté par `layouts/contact.blade.php`
            // → met `count` à 0 → badge disparaît avec animation
            window.dispatchEvent(new Event('notifications-reset'));
        },

        async markAllAsRead() {
            if (this.hasMarkedRead) return;
            this.hasMarkedRead = true;

            try {
                await fetch(this.urls.markRead, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Accept':       'application/json',
                    },
                });
                // Silencieux — le badge est déjà reset visuellement
            } catch (err) {
                console.error('markAllAsRead:', err);
                // En cas d'échec, on autorise un nouvel essai
                this.hasMarkedRead = false;
            }
        },

        /* ============================================================
           FORMATAGE
           ============================================================ */
        formatDate(dateStr) {
            if (!dateStr) return '';

            const today     = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            const parts = dateStr.split('/');
            const d = new Date(parts[2], parts[1] - 1, parts[0]);

            if (d.toDateString() === today.toDateString())     return "Aujourd'hui";
            if (d.toDateString() === yesterday.toDateString()) return 'Hier';

            return d.toLocaleDateString('fr-FR', {
                weekday: 'long',
                day: '2-digit',
                month: 'long',
                year: d.getFullYear() !== today.getFullYear() ? 'numeric' : undefined,
            });
        },

        shouldShowDate(idx) {
            if (idx === 0) return true;
            return this.messages[idx - 1].date !== this.messages[idx].date;
        },

        /* ============================================================
           HELPERS
           ============================================================ */
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        async scrollToBottom() {
            await this.$nextTick();
            const list = this.$refs.messageList;
            if (list) list.scrollTop = list.scrollHeight;
        },

        autoResize(e) {
            const el = e.target;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 140) + 'px';
        },

        /* ============================================================
           ENVOI DU MESSAGE
           ============================================================ */
        async sendReply() {
            const text = this.replyText.trim();
            if (!text || this.loading) return;

            this.loading = true;
            this.error = '';

            try {
                const response = await fetch(this.urls.send, {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'X-CSRF-TOKEN':  this.csrfToken(),
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify({
                        sujet:   'Message depuis l\'espace abonné',
                        message: text,
                    }),
                });

                const data = await response.json();

                if (response.status === 422) {
                    const firstError = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : (data.message || 'Données invalides.');
                    this.error = firstError;
                    return;
                }

                if (!response.ok || !data.success) {
                    this.error = data.error || data.message || 'Échec de l\'envoi.';
                    return;
                }

                this.messages.push(data.message);
                this.replyText = '';

                if (this.$refs.replyInput) {
                    this.$refs.replyInput.style.height = 'auto';
                }

                await this.scrollToBottom();

            } catch (err) {
                console.error('sendReply:', err);
                this.error = 'Erreur réseau. Vérifiez votre connexion et réessayez.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection