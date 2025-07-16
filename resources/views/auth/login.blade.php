<x-guest-layout>
    <div class="auth-card">
        <!-- Logo/Brand -->
        <div class="auth-logo">
            <h1>{{ config('app.name', 'Laravel') }}</h1>
            <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">Connectez-vous à votre compte</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div style="background: #dcfce7; border: 1px solid #16a34a; color: #15803d; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.875rem;">
                {{ session('status') }}
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}">
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

            <!-- Password -->
            <div class="form-group">
                <label for="password" class="form-label">Mot de passe</label>
                <input 
                    id="password" 
                    class="form-input" 
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Votre mot de passe"
                />
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <!-- Remember Me & Forgot Password -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; font-size: 0.875rem; color: #6b7280;">
                    <input 
                        type="checkbox" 
                        name="remember" 
                        style="margin-right: 0.5rem; accent-color: #3b82f6;"
                    />
                    Se souvenir de moi
                </label>
                
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link" style="font-size: 0.875rem;">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <div class="form-group">
                <button type="submit" class="btn-primary">
                    Se connecter
                </button>
            </div>

            <!-- Register Link -->
            <div class="text-center mt-4">
                <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">
                    Pas encore de compte ?
                    <a class="auth-link" href="{{ route('register') }}">
                        Créer un compte
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