@props(['url'])

@php
    $brandLogoUrl = config('mail.brand.logo_url');
    $brandName = config('mail.brand.name', config('app.name'));
@endphp

<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if ($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" class="logo" alt="{{ $brandName }} logo">
            @else
                {{ $brandName }}
            @endif
        </a>
    </td>
</tr>
