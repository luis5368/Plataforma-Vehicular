<x-guest-layout>
    <div class="auth-heading"><span class="eyebrow">RECUPERA EL ACCESO</span><h1>¿Olvidaste tu contraseña?</h1></div>
    <div class="mb-4 text-sm text-gray-600">
        Ingresa el correo de tu cuenta para solicitar un enlace de recuperación.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Enviar enlace de recuperación
            </x-primary-button>
        </div>
    </form>
<p class="auth-switch"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
</x-guest-layout>
