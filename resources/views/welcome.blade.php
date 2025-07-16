<x-guest-layout>
    <div class="auth-card" style="max-width: 600px;">
        <!-- Logo/Brand -->
        <div class="auth-logo">
            <h1>{{ config('app.name', 'Gestion des réclamations') }}</h1>
            <p style="color: #6b7280; font-size: 1rem; margin: 0; margin-top: 0.5rem;">
                Plateforme de gestion des réclamations
            </p>
        </div>

        <!-- Welcome Message -->
        <div style="text-align: center; margin: 2rem 0;">
            <h2 style="color: #1f2937; font-size: 1.5rem; font-weight: 600; margin: 0; margin-bottom: 1rem;">
                Bienvenue
            </h2>
            <p style="color: #6b7280; font-size: 1rem; line-height: 1.6; margin: 0;">
                Gérez vos réclamations de manière simple et efficace
            </p>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; flex-direction: column; gap: 1rem; margin: 2rem 0;">
            <a href="{{ route('login') }}" class="btn-primary" style="text-decoration: none; display: block; text-align: center;">
                Se connecter
            </a>
            
            <a href="{{ route('register') }}" 
               style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #3b82f6; color: #3b82f6; background: transparent; border-radius: 8px; font-size: 1rem; font-weight: 500; text-decoration: none; display: block; text-align: center; transition: all 0.2s;"
               onmouseover="this.style.background='#3b82f6'; this.style.color='white';"
               onmouseout="this.style.background='transparent'; this.style.color='#3b82f6';">
                Créer un compte
            </a>
        </div>

        <!-- Features -->
        <div style="margin: 2rem 0;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; text-align: center;">
                <div>
                    <div style="width: 48px; height: 48px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                        <svg style="width: 24px; height: 24px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 style="color: #1f2937; font-size: 0.875rem; font-weight: 600; margin: 0 0 0.25rem;">Simple</h3>
                    <p style="color: #6b7280; font-size: 0.75rem; margin: 0;">Interface intuitive</p>
                </div>
                
                <div>
                    <div style="width: 48px; height: 48px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                        <svg style="width: 24px; height: 24px; color: #16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 style="color: #1f2937; font-size: 0.875rem; font-weight: 600; margin: 0 0 0.25rem;">Sécurisé</h3>
                    <p style="color: #6b7280; font-size: 0.75rem; margin: 0;">Données protégées</p>
                </div>
                
                <div>
                    <div style="width: 48px; height: 48px; background: #fef3c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                        <svg style="width: 24px; height: 24px; color: #d97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 style="color: #1f2937; font-size: 0.875rem; font-weight: 600; margin: 0 0 0.25rem;">Rapide</h3>
                    <p style="color: #6b7280; font-size: 0.75rem; margin: 0;">Traitement efficace</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6" style="padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
            <p style="color: #9ca3af; font-size: 0.75rem; margin: 0;">
                Plateforme sécurisée de gestion des réclamations
            </p>
        </div>
    </div>
</x-guest-layout>