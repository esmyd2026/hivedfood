{{--
    Brand palette configured in the panel.

    Tailwind compiles its utilities as var(--color-*), so redefining the custom
    properties here overrides the compiled theme without rebuilding any asset.
    Only values matching a plain hex colour reach this point.
--}}
@if (! empty($themeVariables))
    <style>
        :root {
            @foreach ($themeVariables as $variable => $value){{ $variable }}: {{ $value }};
            @endforeach
        }
    </style>
@endif
