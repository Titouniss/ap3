# PROJETX_WEB

La plateforme web sera destinée à la gestion de vos contenus et à la configuration de votre entreprise. Elle sera accessible directement depuis n’importe quel navigateur grâce à un système d’authentification.
Une fois l’utilisateur authentifié et selon son rôle, il aura accès à différents modules :
     Gestion des projets.
     Gestion des utilisateurs.
     Gestion des clients.
     Gestions des rôles.
     Gestion des compétences.
     Gestion de la documentation.
     Gestion des gammes.
     Gestion des îlots.

La plateforme web de gestion sera destiné aux administrateurs de l’application, ils seront en charge de créer les nouveaux projets, les gammes, les utilisateurs les compétences et les rôles.

## Getting Started

Le projet web est développé en php et vuejs sous le framework Laravel. 

### Prerequisites

* [Composer](https://getcomposer.org/) - Composer is a tool for dependency management in PHP.
* [NPM](https://www.npmjs.com/get-npm) - NPM is a tool for dependency management in Node.js.
* [Wamp](http://www.wampserver.com/) - WampServer is a Windows web development environment.

### Installation

Récupération du dépôt :
```
git clone https://bitbucket.org/NumidevDeveloppers/projetx_web.git
```

Installation des dépendances :
```
composer install && npm install
```

Récupération d'une base de données existante ou création d'un base de données nommée `projetx` puis :
```
php artisan migrate
```

Construction des fichiers js :
```
npm run dev
```

Lancement de l'application (automatiser avec .env et wamp) :
```
php artisan serve
```