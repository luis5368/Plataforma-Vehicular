@extends('layouts.public')

@section('content')
<section class="card p-4 mx-auto" style="max-width:650px">
    <h1 class="h3">Demasiadas solicitudes</h1>
    <p>Alcanzaste el límite de solicitudes. Espera antes de intentar nuevamente.</p>
    @if(request()->routeIs('verification.send', 'verification.verify'))
        <p>El límite de verificación es de seis solicitudes por minuto. Intenta nuevamente en un minuto.</p>
    @endif
    @auth
        <a href="{{ route('verification.notice') }}">Volver a tu cuenta</a>
    @else
        <a href="{{ route('login') }}">Volver al inicio de sesión</a>
    @endauth
</section>
@endsection
