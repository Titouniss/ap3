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

Création des autorisations :
```
php artisan passport:install
```

Construction des fichiers js :
```
npm run dev
```

Lancement de l'application (automatiser avec .env et wamp) :
```
php artisan serve
```

#### Installation de PDO

-   SQL Server
```
// Installer PEAR
apt install php-pear

// Installer le PDO
sudo pecl install sqlsrv
sudo pecl install pdo_sqlsrv

// Activer le PDO
printf "; priority=20\nextension=sqlsrv.so\n" > /etc/php/7.3/mods-available/sqlsrv.ini
printf "; priority=30\nextension=pdo_sqlsrv.so\n" > /etc/php/7.3/mods-available/pdo_sqlsrv.ini

// Installer ODBC Microsoft
curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list
apt update
ACCEPT_EULA=Y apt-get install msodbcsql17 unixodbc-dev

// Redémarrer Apache
systemctl restart apache2
```

-   SQLite
```
// Installer le PDO
apt install php7.3-sqlite 

// Redémarrer Apache
systemctl restart apache2
```

-   PostgreSQL
```
// Installer le PDO
apt install php-pgsql

// Redémarrer Apache
systemctl restart apache2
```

### Déploiement

Activer l'automatisation de tâches en tapant :
```
crontab -e
```

Puis en ajoutant cette ligne :
```
* * * * * cd /var/www/preprod && php artisan schedule:run >> /dev/null 2>&1
```