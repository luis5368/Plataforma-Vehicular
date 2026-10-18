<x-guest-layout>
    <div class="auth-heading"><span class="eyebrow">PROTEGE TU CUENTA</span><h1>Confirma tu contraseña</h1></div>
    <div class="mb-4 text-sm text-gray-600">
        Esta acción requiere que confirmes tu contraseña actual.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Contraseña actual" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                Confirmar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
