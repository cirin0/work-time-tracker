@php
    $brandName = config('mail.brand.name', config('app.name'));
    $brandHomeUrl = config('mail.brand.home_url', config('app.url'));
    $defaultFooterText = '© ' . date('Y') . ' ' . $brandName . '.';
@endphp

<x-mail::layout>
    <x-slot:header>
        <x-mail::header :url="$brandHomeUrl">
            {{ $brandName }}
        </x-mail::header>
    </x-slot:header>

    {{ $slot }}

    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    <x-slot:footer>
        <x-mail::footer>
            {{ config('mail.brand.footer_text', $defaultFooterText) }}
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
