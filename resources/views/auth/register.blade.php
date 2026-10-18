@extends('layouts.auth')
@section('title','Crear cuenta')
@section('auth-content')
<div class="auth-heading"><span class="eyebrow">TU CUENTA · {{ $selectedRole }}</span><h1>Crea tu cuenta</h1><p>Verifica tu correo para comenzar. No se requiere aprobación manual.</p></div>
@if(Route::has('auth.google'))
<a href="{{ route('auth.google') }}" class="btn btn-google w-100"><i class="bi bi-google" aria-hidden="true"></i> Continuar con Google</a>
<div class="auth-divider"><span>O regístrate con correo</span></div>
@endif
<form method="POST" action="{{ route('register') }}">
    @csrf
    <input type="hidden" name="role" value="{{ $selectedRole }}">
    @foreach(['name'=>'Nombre completo','email'=>'Correo electrónico','password'=>'Contraseña','password_confirmation'=>'Confirmar contraseña'] as $field=>$label)
    <div class="mb-3">
        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-{{ str_contains($field, 'password') ? 'lock' : ($field === 'email' ? 'envelope' : 'person') }}" aria-hidden="true"></i></span>
            <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" type="{{ str_contains($field,'password') ? 'password' : ($field === 'email' ? 'email' : 'text') }}" autocomplete="{{ str_contains($field,'password') ? 'new-password' : $field }}" @if(!str_contains($field,'password')) value="{{ old($field) }}" @endif required>
            @if(str_contains($field,'password'))
            <button class="btn password-toggle" type="button" data-password-toggle aria-controls="{{ $field }}" aria-pressed="false" aria-label="Mostrar {{ strtolower($label) }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
            @endif
        </div>
    </div>
    @endforeach
    <p class="small text-secondary">Usa al menos 12 caracteres, letras y números. No uses tu correo como contraseña. Puedes utilizar un gestor de contraseñas.</p>
    <p id="strength" role="status" aria-live="polite">Fortaleza: sin evaluar</p>
    <button class="btn btn-primary w-100" type="submit">Crear cuenta y verificar correo</button>
</form>
<p class="auth-switch"><a href="{{ route('register.choice') }}">Cambiar tipo de cuenta</a> · <a href="{{ route('login') }}">Iniciar sesión</a></p>
<script>
document.getElementById('password').addEventListener('input',function(){
    const value=this.value;
    const meetsRequirements=value.length>=12 && /[a-z]/i.test(value) && /[0-9]/.test(value);
    document.getElementById('strength').textContent='Fortaleza: '+(meetsRequirements ? (value.length>=16 ? 'fuerte' : 'media') : 'débil');
});
</script>
@endsection
