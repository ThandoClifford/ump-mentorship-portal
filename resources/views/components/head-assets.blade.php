@php
    $manifest = null;
    $cssFile = null;
    $jsFile = null;

    if (app()->environment('production')) {
        $manifestPath = public_path('build/manifest.json');
        if (file_exists($manifestPath)) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            if (is_array($manifest)) {
                $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
                $jsFile = $manifest['resources/js/app.js']['file'] ?? null;
            }
        }
    }
@endphp

@if (app()->environment('production'))
    @if ($cssFile)
        <link rel="stylesheet" href="{{ asset('build/'.$cssFile) }}">
    @endif
    @if ($jsFile)
        <script type="module" src="{{ asset('build/'.$jsFile) }}"></script>
    @endif
@else
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
