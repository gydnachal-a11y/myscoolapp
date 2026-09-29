@props([
    'icon'   => 'fa-inbox',
    'title'  => 'Aucun résultat',
    'text'   => '',
])

<div class="empty-state">
    <div class="empty-icon-wrapper">
        <i class="fa-regular {{ $icon }}" aria-hidden="true"></i>
    </div>
    <h3 class="empty-title">{{ $title }}</h3>
    @if($text)
        <p class="empty-text">{{ $text }}</p>
    @endif
    @if(!$slot->isEmpty())
        <div class="empty-actions">{{ $slot }}</div>
    @endif
</div>

<style>
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1.5rem;
        text-align: center;
        gap: 0.5rem;
    }
    .empty-icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #cbd5e1;
        margin-bottom: 0.75rem;
    }
    .empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #334155;
        margin: 0;
    }
    .empty-text {
        font-size: 0.9rem;
        color: #94a3b8;
        max-width: 420px;
        margin: 0;
        line-height: 1.5;
    }
    .empty-actions {
        margin-top: 1rem;
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
        justify-content: center;
    }
</style>