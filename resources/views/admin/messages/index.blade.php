@extends('layouts.admin')

@section('page_title', 'Messagerie')
@section('page_subtitle', 'Discutez avec vos abonnés')

@section('content')
@php
    $urls = [
        'markRead' => route('admin.messages.mark-read', ['email' => '__EMAIL__']),
        'reply'    => route('admin.messages.reply',     ['email' => '__EMAIL__']),
        'treat'    => route('admin.messages.treat',     ['email' => '__EMAIL__']),
        'delete'   => route('admin.messages.delete',    ['email' => '__EMAIL__']),
    ];
@endphp

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="messagerie-app"
     x-data="messagerie()"
     x-cloak>

    {{-- ===== RÉPERTOIRE ===== --}}
    <aside class="repertoire-panel" :class="{ 'mobile-hidden': activeEmail }">
        <div class="panel-header">
            <div>
                <h1><i class="fa-solid fa-comments" aria-hidden="true"></i> Messagerie</h1>
                <p>
                    <span x-text="conversations.length"></span> conversation(s) —
                    {{ $totalAbonnes }} abonné(s)
                </p>
            </div>
            <button type="button" class="btn-icon"
                    @click="refresh()"
                    title="Rafraîchir"
                    aria-label="Rafraîchir la liste">
                <i class="fa-solid fa-rotate" aria-hidden="true"></i>
            </button>
        </div>

        <div class="search-bar">
            <i class="fa-solid fa-search" aria-hidden="true"></i>
            <input type="search"
                   x-model.debounce.150ms="search"
                   placeholder="Rechercher un abonné…"
                   aria-label="Rechercher dans les conversations">
        </div>

        <div class="conversations-list" role="list">
            <template x-for="conv in filteredConversations" :key="conv.email">
                <div class="conversation-item"
                     role="listitem"
                     tabindex="0"
                     :class="{
                         active: activeEmail === conv.email,
                         'no-messages': !conv.has_messages
                     }"
                     @click="openConversation(conv.email)"
                     @keydown.enter.prevent="openConversation(conv.email)"
                     @keydown.space.prevent="openConversation(conv.email)">

                    <div class="avatar" aria-hidden="true">
                        <span x-text="conv.initial"></span>
                    </div>

                    <div class="conversation-info">
                        <div class="name-row">
                            <span class="name" x-text="conv.name"></span>
                            <span class="time"
                                  x-show="conv.has_messages"
                                  x-text="formatTime(conv.last_ts, conv.last_time)"
                                  :title="conv.last_date + ' ' + conv.last_time"></span>
                        </div>
                        <div class="preview-row">
                            <span class="preview"
                                  :class="{ 'preview-empty': !conv.has_messages }"
                                  x-text="conv.last_subject"></span>
                            <span class="unread-badge"
                                  x-show="conv.unread > 0"
                                  x-text="conv.unread"
                                  :aria-label="conv.unread + ' message(s) non lu(s)'"></span>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="filteredConversations.length === 0"
                 class="empty-conversations">
                <i class="fa-regular fa-comment-dots" aria-hidden="true"></i>
                <p x-text="search ? 'Aucun résultat pour cette recherche.' : 'Aucun abonné pour le moment.'"></p>
            </div>
        </div>
    </aside>

    {{-- ===== CHAT ===== --}}
    <main class="chat-panel" :class="{ 'mobile-visible': activeEmail }">

        <template x-if="activeConversation">
            <div class="chat-container">
                <header class="chat-header">
                    <button type="button" class="back-btn"
                            @click="closeConversation()"
                            aria-label="Retour à la liste">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </button>

                    <div class="contact-info">
                        <div class="avatar large" aria-hidden="true">
                            <span x-text="activeConversation.initial"></span>
                        </div>
                        <div class="contact-text">
                            <h3 x-text="activeConversation.name"></h3>
                            <p x-text="activeConversation.email"></p>
                        </div>
                    </div>

                    <div class="actions">
                        <button type="button" class="btn-icon"
                                @click="markTreated()"
                                :disabled="loading.treat || !activeConversation.has_messages"
                                title="Marquer comme traité"
                                aria-label="Marquer comme traité">
                            <i class="fa-solid"
                               :class="loading.treat ? 'fa-spinner fa-spin' : 'fa-check-double'"
                               aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn-icon danger"
                                @click="deleteConversation()"
                                :disabled="loading.delete || !activeConversation.has_messages"
                                title="Supprimer la conversation"
                                aria-label="Supprimer la conversation">
                            <i class="fa-solid"
                               :class="loading.delete ? 'fa-spinner fa-spin' : 'fa-trash'"
                               aria-hidden="true"></i>
                        </button>
                    </div>
                </header>

                <div class="messages-list" x-ref="messageList" aria-live="polite">

                    {{-- Conversation sans message --}}
                    <template x-if="!activeConversation.has_messages">
                        <div class="empty-thread empty-thread-first">
                            <div class="empty-thread-icon">
                                <i class="fa-regular fa-paper-plane" aria-hidden="true"></i>
                            </div>
                            <h4>Aucun message échangé</h4>
                            <p>
                                Démarrez la conversation avec
                                <strong x-text="activeConversation.name"></strong>
                                en envoyant le premier message ci-dessous.
                            </p>
                        </div>
                    </template>

                    {{-- Messages existants --}}
                    <template x-for="(msg, idx) in activeMessages" :key="msg.id">
                        <div class="message-block">
                            <div class="date-separator"
                                 x-show="shouldShowDate(idx)"
                                 x-text="formatDate(msg.date)">
                            </div>

                            <div class="message"
                                 :class="msg.from_admin ? 'sent' : 'received'">
                                <div class="bubble">
                                    <p x-text="msg.message"></p>
                                    <span class="msg-meta" x-text="msg.time"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <form class="reply-form"
                      @submit.prevent="sendReply()"
                      :aria-busy="loading.reply">
                    <textarea x-model="replyText"
                              x-ref="replyInput"
                              rows="1"
                              maxlength="10000"
                              :placeholder="activeConversation.has_messages
                                  ? 'Écrire une réponse… (Entrée pour envoyer)'
                                  : 'Écrire le premier message… (Entrée pour envoyer)'"
                              aria-label="Votre réponse"
                              @input="autoResize($event)"
                              @keydown.enter.exact.prevent="sendReply()"
                              @keydown.enter.shift.prevent="$event.target.value += '\n'; autoResize($event)"></textarea>

                    <button type="submit"
                            class="send-btn"
                            :disabled="!replyText.trim() || loading.reply"
                            aria-label="Envoyer la réponse">
                        <i class="fa-solid"
                           :class="loading.reply ? 'fa-spinner fa-spin' : 'fa-paper-plane'"
                           aria-hidden="true"></i>
                    </button>
                </form>

                <div x-show="error" class="error-banner" x-transition>
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span x-text="error"></span>
                </div>
            </div>
        </template>

        <template x-if="!activeConversation">
            <div class="no-chat-selected">
                <i class="fa-solid fa-comments fa-3x" aria-hidden="true"></i>
                <p>Sélectionnez une conversation pour afficher les messages</p>
            </div>
        </template>
    </main>
</div>

<style>
    [x-cloak] { display: none !important; }

    /* ============================================================
       LAYOUT GLOBAL
       ============================================================ */
    .messagerie-app {
        display: flex;
        gap: 1.5rem;
        height: calc(100vh - 120px);
        height: calc(100dvh - 120px);
        min-height: 600px;
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem;
    }

    .repertoire-panel {
        width: 400px;
        min-width: 350px;
        background: white;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .chat-panel {
        flex: 1;
        background: white;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    /* ============================================================
       EN-TÊTE RÉPERTOIRE
       ============================================================ */
    .panel-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
    .panel-header h1 {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .panel-header h1 i { color: #667eea; }
    .panel-header p {
        font-size: 0.85rem;
        color: #64748b;
        margin: 0.25rem 0 0;
    }

    /* ============================================================
       BARRE DE RECHERCHE
       ============================================================ */
    .search-bar {
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        position: relative;
    }
    .search-bar i {
        position: absolute;
        left: 1.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }
    .search-bar input {
        width: 100%;
        padding: 0.7rem 1rem 0.7rem 2.5rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s, background 0.2s;
        font-family: inherit;
    }
    .search-bar input:focus {
        border-color: #667eea;
        background: white;
    }

    /* ============================================================
       LISTE DES CONVERSATIONS
       ============================================================ */
    .conversations-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.75rem;
        scrollbar-width: thin;
    }
    .conversation-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.85rem 1rem;
        border-radius: 14px;
        cursor: pointer;
        transition: background 0.2s;
        outline: none;
    }
    .conversation-item:hover { background: #f8fafc; }
    .conversation-item:focus-visible { outline: 2px solid #667eea; outline-offset: -2px; }
    .conversation-item.active { background: #eef2ff; }

    /* Abonné sans message → légèrement estompé */
    .conversation-item.no-messages .name { color: #64748b; }
    .conversation-item.no-messages .avatar {
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        color: #94a3b8;
    }

    .avatar {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .avatar.large { width: 52px; height: 52px; font-size: 1.2rem; }

    .conversation-info { flex: 1; min-width: 0; }
    .name-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
    }
    .name {
        font-weight: 600;
        color: #1e293b;
        font-size: 1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .time {
        font-size: 0.78rem;
        color: #94a3b8;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .preview-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.2rem;
        gap: 0.5rem;
    }
    .preview {
        font-size: 0.85rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }
    .preview-empty {
        color: #94a3b8;
        font-style: italic;
    }
    .unread-badge {
        background: #ef4444;
        color: white;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        min-width: 20px;
        text-align: center;
        flex-shrink: 0;
    }
    .empty-conversations {
        text-align: center;
        color: #94a3b8;
        padding: 3rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        align-items: center;
    }
    .empty-conversations i { font-size: 2rem; opacity: 0.4; }
    .empty-conversations p { font-size: 0.9rem; margin: 0; }

    /* ============================================================
       CHAT
       ============================================================ */
    .chat-container {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
    }
    .chat-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-shrink: 0;
    }
    .back-btn {
        display: none;
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0.5rem;
        border-radius: 10px;
        transition: background 0.2s;
    }
    .back-btn:hover { background: #f1f5f9; color: #1e293b; }

    .contact-info {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex: 1;
        min-width: 0;
    }
    .contact-text { min-width: 0; }
    .contact-info h3 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .contact-info p {
        font-size: 0.83rem;
        color: #64748b;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .actions { display: flex; gap: 0.35rem; flex-shrink: 0; }

    /* ============================================================
       MESSAGES
       ============================================================ */
    .messages-list {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        background: #fafbfc;
        min-height: 0;
        scroll-behavior: smooth;
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
    .date-separator::before { left: 1rem; }
    .date-separator::after  { right: 1rem; }

    .message {
        max-width: 75%;
        display: flex;
        animation: msgIn 0.25s ease-out;
    }
    @keyframes msgIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .message.sent { justify-content: flex-end; align-self: flex-end; }
    .message.received { justify-content: flex-start; align-self: flex-start; }

    .bubble {
        padding: 0.75rem 1rem;
        border-radius: 16px;
        font-size: 0.95rem;
        line-height: 1.55;
        position: relative;
        word-wrap: break-word;
        overflow-wrap: break-word;
        max-width: 100%;
    }
    .bubble p { margin: 0; white-space: pre-wrap; }
    .message.sent .bubble {
        background: linear-gradient(135deg, #667eea, #5a52d5);
        color: white;
        border-bottom-right-radius: 4px;
    }
    .message.received .bubble {
        background: white;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 4px;
    }
    .msg-meta {
        display: block;
        font-size: 0.7rem;
        margin-top: 0.3rem;
        opacity: 0.7;
    }
    .message.sent .msg-meta { text-align: right; }

    /* ============================================================
       FIL VIDE (abonné sans message)
       ============================================================ */
    .empty-thread {
        text-align: center;
        color: #94a3b8;
        padding: 2rem 1rem;
        font-size: 0.9rem;
    }
    .empty-thread-first {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 2.5rem 1.5rem;
        margin: auto;
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
    .empty-thread-first h4 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #334155;
        margin: 0;
    }
    .empty-thread-first p {
        font-size: 0.9rem;
        color: #64748b;
        max-width: 400px;
        margin: 0;
        line-height: 1.55;
    }
    .empty-thread-first strong { color: #1e293b; }

    /* ============================================================
       FORMULAIRE DE RÉPONSE
       ============================================================ */
    .reply-form {
        display: flex;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-top: 1px solid #f1f5f9;
        align-items: flex-end;
        background: white;
        flex-shrink: 0;
    }
    .reply-form textarea {
        flex: 1;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.85rem 1rem;
        font-size: 0.95rem;
        font-family: inherit;
        resize: none;
        outline: none;
        min-height: 48px;
        max-height: 140px;
        transition: border-color 0.2s;
        line-height: 1.5;
    }
    .reply-form textarea:focus { border-color: #667eea; }

    .send-btn {
        background: #1e293b;
        color: white;
        border: none;
        width: 48px;
        height: 48px;
        border-radius: 14px;
        cursor: pointer;
        font-size: 1.1rem;
        transition: all 0.2s;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .send-btn:hover:not(:disabled) { background: #667eea; transform: translateY(-1px); }
    .send-btn:disabled { opacity: 0.4; cursor: not-allowed; }

    /* ============================================================
       BOUTONS GÉNÉRIQUES
       ============================================================ */
    .btn-icon {
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
        font-size: 1.05rem;
        padding: 0.5rem;
        border-radius: 10px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
    }
    .btn-icon:hover:not(:disabled) { background: #f1f5f9; color: #1e293b; }
    .btn-icon:disabled { opacity: 0.35; cursor: not-allowed; }
    .btn-icon.danger:hover:not(:disabled) { background: #fee2e2; color: #dc2626; }

    .no-chat-selected {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        gap: 1rem;
        font-size: 1rem;
        padding: 2rem;
        text-align: center;
    }
    .no-chat-selected i { opacity: 0.3; }

    /* ============================================================
       BANNIÈRE D'ERREUR
       ============================================================ */
    .error-banner {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        padding: 0.65rem 1rem;
        margin: 0 1.25rem 1rem;
        border-radius: 10px;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 992px) {
        .messagerie-app { padding: 1rem; gap: 1rem; }
        .repertoire-panel { width: 340px; min-width: 300px; }
    }

    @media (max-width: 768px) {
        .messagerie-app {
            flex-direction: column;
            height: calc(100dvh - 60px);
            padding: 0;
            gap: 0;
            max-width: 100%;
        }
        .repertoire-panel,
        .chat-panel {
            border-radius: 0;
            box-shadow: none;
            width: 100%;
            min-width: auto;
        }
        .repertoire-panel.mobile-hidden { display: none; }
        .chat-panel:not(.mobile-visible) { display: none; }
        .chat-panel.mobile-visible { display: flex; }

        .back-btn { display: inline-flex; }
        .message { max-width: 88%; }
        .messages-list { padding: 1rem 0.85rem; }
        .reply-form { padding: 0.75rem 0.85rem; }
    }

    @media (max-width: 480px) {
        .panel-header { padding: 1rem; }
        .panel-header h1 { font-size: 1.15rem; }
        .search-bar { padding: 0.75rem 1rem; }
        .search-bar i { left: 1.5rem; }
        .conversation-item { padding: 0.7rem 0.8rem; gap: 0.75rem; }
        .avatar { width: 42px; height: 42px; font-size: 1rem; border-radius: 12px; }
        .avatar.large { width: 46px; height: 46px; }
        .chat-header { padding: 0.85rem 1rem; }
        .contact-info h3 { font-size: 0.95rem; }
        .contact-info p { font-size: 0.75rem; }
    }
</style>

<script>
function messagerie() {
    return {
        conversations: {{ Js::from($conversations) }},
        messages:      {{ Js::from($messages) }},

        activeEmail: null,
        search:      '',
        replyText:   '',
        error:       '',

        loading: {
            reply:  false,
            treat:  false,
            delete: false,
        },

        urls: {{ Js::from($urls) }},

        get filteredConversations() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.conversations;

            return this.conversations.filter(c =>
                (c.name         || '').toLowerCase().includes(q) ||
                (c.email        || '').toLowerCase().includes(q) ||
                (c.last_subject || '').toLowerCase().includes(q)
            );
        },

        get activeConversation() {
            if (!this.activeEmail) return null;
            return this.conversations.find(c => c.email === this.activeEmail) || null;
        },

        get activeMessages() {
            if (!this.activeEmail) return [];
            return this.messages.filter(m => m.email === this.activeEmail);
        },

        formatTime(ts, fallbackTime) {
            if (!ts) return fallbackTime || '';

            const date      = new Date(ts * 1000);
            const today     = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            if (date.toDateString() === today.toDateString()) {
                return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            }
            if (date.toDateString() === yesterday.toDateString()) {
                return 'Hier';
            }
            return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
        },

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
            const list = this.activeMessages;
            if (idx === 0) return true;
            return list[idx - 1].date !== list[idx].date;
        },

        buildUrl(template, email) {
            return template.replace('__EMAIL__', encodeURIComponent(email));
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        async post(url, body = null, method = 'POST') {
            const headers = {
                'X-CSRF-TOKEN': this.csrfToken(),
                'Accept':       'application/json',
            };

            const opts = { method, headers };

            if (body !== null) {
                headers['Content-Type'] = 'application/json';
                opts.body = JSON.stringify(body);
            }

            const res = await fetch(url, opts);

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || `Erreur ${res.status}`);
            }

            return res.json();
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

        async openConversation(email) {
            this.activeEmail = email;
            this.error = '';

            const conv = this.conversations.find(c => c.email === email);

            if (conv && conv.unread > 0) {
                conv.unread = 0;
                try {
                    await this.post(this.buildUrl(this.urls.markRead, email));
                } catch (err) {
                    console.error('markRead failed:', err);
                }
            }

            await this.scrollToBottom();

            this.$nextTick(() => {
                this.$refs.replyInput?.focus();
            });
        },

        closeConversation() {
            this.activeEmail = null;
            this.replyText = '';
            this.error = '';
        },

        async sendReply() {
            const text = this.replyText.trim();
            if (!text || !this.activeEmail || this.loading.reply) return;

            this.loading.reply = true;
            this.error = '';

            try {
                const data = await this.post(
                    this.buildUrl(this.urls.reply, this.activeEmail),
                    { message: text }
                );

                if (data.success) {
                    this.messages.push(data.message);
                    this.replyText = '';

                    if (this.$refs.replyInput) {
                        this.$refs.replyInput.style.height = 'auto';
                    }

                    // ✅ Marque la conversation comme ayant des messages
                    const conv = this.conversations.find(c => c.email === this.activeEmail);
                    if (conv) {
                        conv.last_time     = data.message.time;
                        conv.last_date     = data.message.date;
                        conv.last_ts       = Math.floor(Date.now() / 1000);
                        conv.last_subject  = data.message.message.slice(0, 60);
                        conv.has_messages  = true;
                    }

                    await this.scrollToBottom();
                }
            } catch (err) {
                this.error = err.message || 'Impossible d\'envoyer le message.';
            } finally {
                this.loading.reply = false;
            }
        },

        async markTreated() {
            if (!this.activeEmail || this.loading.treat) return;

            this.loading.treat = true;
            this.error = '';

            try {
                await this.post(this.buildUrl(this.urls.treat, this.activeEmail));
            } catch (err) {
                this.error = err.message || 'Impossible de marquer comme traité.';
            } finally {
                this.loading.treat = false;
            }
        },

        async deleteConversation() {
            if (!this.activeEmail || this.loading.delete) return;

            if (!confirm('Supprimer définitivement cette conversation ?')) return;

            this.loading.delete = true;
            this.error = '';

            const email = this.activeEmail;

            try {
                await this.post(
                    this.buildUrl(this.urls.delete, email),
                    null,
                    'DELETE'
                );

                // Garde l'abonné dans la liste, mais vide sa conversation
                const conv = this.conversations.find(c => c.email === email);
                if (conv) {
                    conv.has_messages  = false;
                    conv.last_subject  = 'Aucun message pour le moment';
                    conv.last_time     = '';
                    conv.last_date     = '';
                    conv.last_ts       = 0;
                    conv.unread        = 0;
                }

                this.messages = this.messages.filter(m => m.email !== email);
                this.replyText = '';
            } catch (err) {
                this.error = err.message || 'Impossible de supprimer la conversation.';
            } finally {
                this.loading.delete = false;
            }
        },

        refresh() {
            window.location.reload();
        },
    };
}
</script>
@endsection