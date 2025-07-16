<x-guest-layout>
    <div class="auth-card">
        <!-- Logo/Brand -->
        <div class="auth-logo">
            <h1>{{ config('app.name', 'Laravel') }}</h1>
            <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Créez votre compte</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div style="background: #dcfce7; border: 1px solid #16a34a; color: #15803d; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
                {{ session('status') }}
            </div>
        @endif

        <!-- Register Form -->
        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name -->
            <div class="form-group">
                <label for="name" class="form-label">Nom complet</label>
                <input
                    id="name"
                    class="form-input"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Votre nom complet"
                />
                @error('name')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <!-- Email Address -->
            <div class="form-group">
                <label for="email" class="form-label">Adresse email</label>
                <input
                    id="email"
                    class="form-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="username"
                    placeholder="votre@email.com"
                />
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <input
                    id="password"
                    class="form-input"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Choisissez un mot de passe"
                />
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                <input
                    id="password_confirmation"
                    class="form-input"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirmez votre mot de passe"
                />
                @error('password_confirmation')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>


            <!-- Submit Button -->
            <div class="form-group">
                <button type="submit" class="btn-primary">
                    Créer mon compte
                </button>
            </div>

            <!-- Login Link -->
            <div class="text-center mt-4">
                <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">
                    Déjà un compte ?
                    <a class="auth-link" href="{{ route('login') }}">
                        Se connecter
                    </a>
                </p>
            </div>
        </form>

        <!-- Footer -->
        <div class="text-center mt-6" style="padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
            <p style="color: #9ca3af; font-size: 0.75rem; margin: 0;">
                Plateforme sécurisée de gestion des réclamations
            </p>
        </div>
    </div>
</x-guest-layout>