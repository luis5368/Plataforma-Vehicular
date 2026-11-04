@vite(['resources/css/app.css', 'resources/js/app.js'])
@if(app()->environment('staging'))
    <meta name="robots" content="noindex,nofollow,noarchive">
@endif

