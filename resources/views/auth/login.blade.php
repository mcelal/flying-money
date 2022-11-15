<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            <x-jet-authentication-card-logo />
        </x-slot>

        <x-jet-button>
            <a href="{{ url('auth/google') }}">Login Google</a>
        </x-jet-button>
    </x-jet-authentication-card>
</x-guest-layout>
