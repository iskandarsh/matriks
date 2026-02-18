@props(['active'])

@php
$baseClasses = 'flex items-center px-4 py-2 rounded-md transition duration-150 ease-in-out whitespace-nowrap overflow-hidden overflow-ellipsis';
$activeClasses = 'bg-blue-500 text-white font-semibold hover:bg-blue-400';
$inactiveClasses = 'text-gray-700 dark:text-gray-300 hover:bg-blue-400 hover:text-white dark:hover:bg-gray-700';
$classes = ($active ?? false)
? "$baseClasses $activeClasses"
: "$baseClasses $inactiveClasses";
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>