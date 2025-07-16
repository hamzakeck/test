
README.txt – Comment déployer et lancer l’application Laravel avec Docker sous Windows


etape 0 : Ce dont vous avez besoin


1.  Docker Desktop installé et lancé.
   - Si ce n’est pas encore fait, téléchargez et installez Docker Desktop ici :
     https://www.docker.com/products/docker-desktop

2. Visual Studio Code (VS Code)


etape 1 : Préparer le dossier de l’application


1. Vous allez recevoir les fichiers de l’application dans une archive ZIP, soit via un dépôt Git, dans https://github.com/hamzakeck/test
2. Décompressez (ou clonez) ce dossier 
3. Ouvrez Visual Studio Code.
4. Dans VS Code, cliquez sur "Fichier > Ouvrir un dossier", puis sélectionnez le dossier de l’application.
5. Une fois le dossier ouvert, ouvrez le terminal intégré dans VS Code :
   - Allez dans "Affichage > Terminal" ou pressez les touches **Ctrl + `** (accent grave).


etape 2 : Configuration du fichier d’environnement


1. Dans le terminal de VS Code, tapez la commande suivante et appuyez sur Entrée :	cp .env.example .env

   Cette commande copie le fichier d’exemple `.env.example` en `.env` que Laravel utilise pour ses configurations.

2. (Optionnel) Ouvrez le fichier `.env` dans VS Code si vous souhaitez modifier des paramètres (ex : nom de la base, mot de passe, etc.).


etape 3 : Démarrer les conteneurs Docker


1. Vérifiez que Docker Desktop est lancé sur votre PC.
2. Toujours dans le terminal de VS Code, tapez cette commande pour construire et lancer les conteneurs :     docker-compose up -d

   Cette commande lance votre application Laravel et la base MySQL en arrière-plan.

3. Patientez environ 30 secondes que tout démarre.


etape 4 : Configurer Laravel (migrations et liens)


1. Dans le terminal, lancez ces commandes une par une :

   - docker-compose exec app php artisan key:generate

   - Pour créer les tables dans la base de données :     docker-compose exec app php artisan migrate

   - Pour créer le lien symbolique du dossier de stockage (utile pour accéder aux fichiers uploadés) :      docker-compose exec app php artisan storage:link


etape 5 : Accéder à l’application


- Ouvrez votre navigateur web.
- copier dans la barre d’adresse :

  http://localhost:8000

- Vous devriez voir la page d’accueil ou la page de connexion de l’application.


Étape 6 : Arrêter l’application


- Pour arrêter l’application et la base de données, dans le terminal tapez :

  docker-compose down

- Cela arrête les conteneurs sans supprimer les données.


Conseils supplémentaires


- Les modifications faites dans les fichiers du dossier sont automatiquement prises en compte par l’application.
- Les fichiers uploadés sont sauvegardés dans le conteneur et accessibles via le lien symbolique.
- En cas de problème, vérifiez que Docker fonctionne et que le port 8000 n’est pas bloqué par un pare-feu.
- Pour afficher les logs et diagnostiquer une erreur, utilisez :

  docker-compose logs app

-----------------------------------------------------------
Fin du README
-----------------------------------------------------------
