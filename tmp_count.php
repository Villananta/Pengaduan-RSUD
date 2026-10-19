@php
    echo str_contains(file_get_contents(resource_path('views/unit/layout.blade.php')), "@stack('scripts')") ? 'ada' : 'tdk';
@endphp
