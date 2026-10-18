@extends('layouts.auth')
@section('title','Verifica tu correo')

@section('auth-content')
<section class="card p-4 mx-auto" style="max-width:650px" aria-labelledby="verification-title">
    <div class="verification-icon" aria-hidden="true"><i class="bi bi-envelope-check"></i></div><h1 id="verification-title" class="h3">Verifica tu correo</h1>
    <p>Para proteger tu cuenta, debes verificar tu correo antes de entrar al panel, aunque tengas el rol de administrador.</p>
    <p>Pulsa «Reenviar enlace» y abre el mensaje de verificación en tu correo. Revisa también la carpeta de spam. El enlace vence después de {{ config('auth.verification.expire', 60) }} minutos.</p>
    <p>Mantén iniciada la sesión y abre el enlace en este mismo navegador. Después podrás acceder a las funciones autorizadas de tu cuenta.</p>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="btn btn-primary">Reenviar enlace</button>
    </form>
    <p class="small text-secondary mt-3">Puedes solicitar hasta seis reenvíos por minuto. Si alcanzas el límite, espera un minuto antes de intentar nuevamente.</p>
    <form class="mt-3" method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-secondary">Cerrar sesión</button>
    </form>
</section>
@endsection
