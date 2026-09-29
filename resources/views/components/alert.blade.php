@props(['type' => 'success'])

@php
$colors = [
    'success' => 'bg-green-100 border-green-400 text-green-700',
    'error'   => 'bg-red-100 border-red-400 text-red-700',
    'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-800',
];
$colorClass = $colors[$type] ?? $colors['success'];
@endphp

<div x-data="{ show: true }"
     x-init="setTimeout(() => show = false, 5000)"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-500"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="border px-4 py-3 rounded mb-4 {{ $colorClass }}">
    {{ $slot }}
</div>