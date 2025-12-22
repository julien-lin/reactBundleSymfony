# Quick Start avec Docker - ReactBundleSymfony

Guide pour démarrer un projet Symfony + React en **5 minutes** avec Docker.

---

## 🚀 Prérequis

- Docker et Docker Compose installés
- Git (optionnel)

---

## ⚡ Installation en 5 Minutes

### Étape 1 : Créer un nouveau projet Symfony

```bash
composer create-project symfony/skeleton:"8.0.*" my-react-app
cd my-react-app
```

### Étape 2 : Installer ReactBundle

```bash
composer require julien-lin/react-bundle-symfony
```

Le bundle va automatiquement :
- ✅ Installer les dépendances npm
- ✅ Générer `vite.config.js`
- ✅ Créer la structure de dossiers

### Étape 3 : Créer `docker-compose.yml`

Créez un fichier `docker-compose.yml` à la racine :

```yaml
version: '3.8'

services:
  php:
    image: php:8.4-fpm
    volumes:
      - .:/var/www/html
    working_dir: /var/www/html
    networks:
      - app-network

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "3000:3000"  # Port Vite pour HMR
    volumes:
      - .:/var/www/html
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - php
    networks:
      - app-network

  node:
    image: node:20-alpine
    volumes:
      - .:/var/www/html
    working_dir: /var/www/html
    command: sh -c "npm install && npm run dev"
    ports:
      - "3000:3000"
    networks:
      - app-network
    environment:
      - VITE_HMR_HOST=localhost
      - VITE_HMR_PORT=3000

networks:
  app-network:
    driver: bridge
```

### Étape 4 : Créer la configuration Nginx

Créez `docker/nginx.conf` :

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass php:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    # Proxy pour Vite HMR
    location /build {
        proxy_pass http://node:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

### Étape 5 : Créer un composant React

Créez `assets/React/Components/HelloWorld.jsx` :

```jsx
import React from 'react';

const HelloWorld = ({ name = 'World' }) => {
  return (
    <div style={{
      padding: '20px',
      border: '2px solid #4CAF50',
      borderRadius: '8px',
      textAlign: 'center',
      backgroundColor: '#f1f8f4'
    }}>
      <h1>Hello, {name}!</h1>
      <p>ReactBundle avec Docker fonctionne ! 🎉</p>
    </div>
  );
};

export default HelloWorld;
```

Exportez dans `assets/React/index.js` :

```javascript
export { default as HelloWorld } from './Components/HelloWorld';
```

### Étape 6 : Créer une route Symfony

Créez `src/Controller/HomeController.php` :

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
```

Créez `templates/home/index.html.twig` :

```twig
{% extends '@React/react_base.html.twig' %}

{% block title %}Hello React!{% endblock %}

{% block body %}
    <div class="container">
        {{ react_component('HelloWorld', {
            name: 'Docker User'
        }) }}
    </div>
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

### Étape 7 : Démarrer Docker

```bash
docker-compose up -d
```

### Étape 8 : Installer les dépendances et démarrer Vite

```bash
# Dans un terminal
docker-compose exec node npm install

# Démarrer Vite en mode dev (dans un autre terminal)
docker-compose exec node npm run dev
```

### Étape 9 : Accéder à l'application

Ouvrez votre navigateur sur : **http://localhost**

Le HMR fonctionne automatiquement ! Modifiez `HelloWorld.jsx` et voyez les changements en temps réel.

---

## ✅ Vérification

### Vérifier que tout fonctionne

1. **Vérifier le serveur Vite :**
```bash
docker-compose exec node npm run dev
# Devrait afficher : VITE v5.x.x ready in xxx ms
```

2. **Vérifier Symfony :**
```bash
docker-compose exec php php bin/console about
```

3. **Vérifier le HMR :**
- Ouvrez http://localhost
- Modifiez `HelloWorld.jsx`
- Le navigateur devrait se recharger automatiquement

---

## 🔧 Configuration HMR pour Docker

Le `vite.config.js` généré automatiquement est déjà configuré pour Docker :

```javascript
server: {
  host: '0.0.0.0',  // Accepte les connexions externes
  port: 3000,
  hmr: {
    host: 'localhost',  // Pour le HMR depuis le navigateur
    port: 3000
  },
  watch: {
    usePolling: true  // Nécessaire pour Docker
  }
}
```

### Variables d'environnement

Vous pouvez personnaliser via `.env` :

```env
VITE_HMR_HOST=localhost
VITE_HMR_PORT=3000
```

---

## 🐛 Dépannage

### Le HMR ne fonctionne pas

1. **Vérifier que le port 3000 est exposé :**
```yaml
ports:
  - "3000:3000"
```

2. **Vérifier la configuration Nginx :**
Le proxy pour `/build` doit pointer vers `node:3000`

3. **Vérifier les logs Vite :**
```bash
docker-compose logs node
```

### Le serveur Vite ne démarre pas

```bash
# Vérifier que Node est bien démarré
docker-compose ps node

# Redémarrer le service
docker-compose restart node

# Voir les logs
docker-compose logs -f node
```

### Les assets ne se chargent pas

1. **Vérifier que le build existe :**
```bash
docker-compose exec node npm run build
```

2. **Vérifier les permissions :**
```bash
docker-compose exec php chmod -R 777 public/build
```

---

## 📚 Prochaines Étapes

- Créer plus de composants React
- Ajouter TypeScript (voir [TYPESCRIPT.md](TYPESCRIPT.md))
- Configurer la base de données
- Ajouter des tests

---

## 🔗 Ressources

- [Documentation complète](README.md)
- [Guide TypeScript](TYPESCRIPT.md)
- [Exemples](EXAMPLES.md)
- [Configuration](CONFIG.md)

---

**Temps total : ~5 minutes** ⚡

---

**Dernière mise à jour :** 2024-12-22

