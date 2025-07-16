<x-guest-layout>
    <div class="auth-card">
        <!-- Logo/Brand -->
        <div class="auth-logo">
            <h1>{{ config('app.name', 'Laravel') }}</h1>
            <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Réinitialiser votre mot de passe</p>
        </div>

        <!-- Instructions -->
        <div style="background: #f0f9ff; border: 1px solid #0ea5e9; color: #0c4a6e; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem; line-height: 1.5;">
            <p style="margin: 0;">
                Mot de passe oublié ? Pas de problème. Indiquez-nous votre adresse email et nous vous enverrons un lien de réinitialisation.
            </p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div style="background: #dcfce7; border: 1px solid #16a34a; color: #15803d; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
                {{ session('status') }}
            </div>
        @endif

        <!-- Forgot Password Form -->
        <form method="POST" action="{{ route('password.email') }}">
            @csrf

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
                    autofocus
                    autocomplete="username"
                    placeholder="votre@email.com"
                />
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="form-group">
                <button type="submit" class="btn-primary">
                    Envoyer le lien de réinitialisation
                </button>
            </div>

            <!-- Back to Login Link -->
            <div class="text-center mt-4">
                <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">
                    Vous vous souvenez de votre mot de passe ?
                    <a class="auth-link" href="{{ route('login') }}">
                        Retour à la connexion
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