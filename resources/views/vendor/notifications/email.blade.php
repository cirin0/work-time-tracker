<x-mail::message>
    @if (! empty($greeting))
        # {{ $greeting }}
    @else
        @if ($level === 'error')
            # @lang('Whoops!')
        @else
            # Привіт
        @endif
    @endif

    @foreach ($introLines as $line)
        {{ $line }}

    @endforeach

    @isset($actionText)
            <?php
            $color = match ($level) {
                'success', 'error' => $level,
                default => 'primary',
            };
            ?>
        <x-mail::button :url="$actionUrl" :color="$color">
            {{ $actionText }}
        </x-mail::button>
    @endisset

    @foreach ($outroLines as $line)
        {{ $line }}

    @endforeach

    @if (! empty($salutation))
        {{ $salutation }}
    @else
        @if (! empty(config('mail.brand.salutation')))
            {{ config('mail.brand.salutation') }}<br>
        @endif
        {{ config('mail.brand.name', config('app.name')) }}
    @endif

    @isset($actionText)
        <x-slot:subcopy>
            @lang(
                "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
                'into your web browser:',
                [
                    'actionText' => $actionText,
                ]
            ) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
        </x-slot:subcopy>
    @endisset
</x-mail::message>
