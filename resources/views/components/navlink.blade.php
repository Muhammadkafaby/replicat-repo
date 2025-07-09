@props(['href' => '#', 'active' => false])

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => ($active ? 'text-blue-600 font-bold' : 'text-gray-700') . ' px-4 py-2 rounded hover:bg-blue-50 transition']) }}>
    {{ $slot }}
</a>
