# Migration depuis Stimulus vers ReactBundle

Guide complet pour migrer votre application Symfony de Stimulus vers React avec ReactBundleSymfony.

---

## 📋 Table des Matières

1. [Pourquoi Migrer ?](#pourquoi-migrer)
2. [Comparaison Stimulus vs React](#comparaison-stimulus-vs-react)
3. [Guide de Migration Étape par Étape](#guide-de-migration-étape-par-étape)
4. [Exemples de Conversion](#exemples-de-conversion)
5. [Checklist de Migration](#checklist-de-migration)
6. [FAQ](#faq)

---

## Pourquoi Migrer ?

### Avantages de React

- ✅ **Écosystème riche** : Bibliothèques, composants, outils
- ✅ **TypeScript natif** : Type safety complète
- ✅ **Hooks modernes** : useState, useEffect, useContext
- ✅ **Performance** : Virtual DOM, code splitting
- ✅ **Communauté** : Large communauté et support
- ✅ **Outils** : DevTools, Storybook, Testing Library

### Quand Migrer ?

Migrez si vous avez besoin de :
- Composants complexes avec beaucoup d'état
- Bibliothèques React (React Router, Redux, etc.)
- TypeScript pour la sécurité des types
- Écosystème React (composants UI, hooks, etc.)

### Quand Rester sur Stimulus ?

Restez sur Stimulus si :
- Vos contrôleurs sont simples (peu d'état)
- Vous préférez une approche minimaliste
- Vous n'avez pas besoin de l'écosystème React

---

## Comparaison Stimulus vs React

### Stimulus (Avant)

```javascript
// app/controllers/weather_controller.js
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = ["city", "temperature", "description"]
  static values = { apiUrl: String }

  connect() {
    this.fetchWeather()
  }

  async fetchWeather() {
    const response = await fetch(`${this.apiUrlValue}?city=Paris`)
    const data = await response.json()
    this.temperatureTarget.textContent = data.temperature
    this.descriptionTarget.textContent = data.description
  }
}
```

```html
<!-- Twig -->
<div data-controller="weather" 
     data-weather-api-url-value="/api/weather">
  <input data-weather-target="city" />
  <div data-weather-target="temperature"></div>
  <div data-weather-target="description"></div>
</div>
```

### React (Après)

```tsx
// assets/React/Components/Weather.tsx
import React, { useState, useEffect } from 'react';

interface WeatherProps {
  apiUrl: string;
  initialCity?: string;
}

const Weather: React.FC<WeatherProps> = ({ apiUrl, initialCity = 'Paris' }) => {
  const [city, setCity] = useState<string>(initialCity);
  const [temperature, setTemperature] = useState<number | null>(null);
  const [description, setDescription] = useState<string>('');

  useEffect(() => {
    const fetchWeather = async () => {
      const response = await fetch(`${apiUrl}?city=${city}`);
      const data = await response.json();
      setTemperature(data.temperature);
      setDescription(data.description);
    };
    fetchWeather();
  }, [city, apiUrl]);

  return (
    <div>
      <input 
        value={city} 
        onChange={(e) => setCity(e.target.value)} 
      />
      {temperature !== null && (
        <>
          <div>{temperature}°C</div>
          <div>{description}</div>
        </>
      )}
    </div>
  );
};

export default Weather;
```

```twig
{# Twig #}
{{ react_component('Weather', {
    apiUrl: '/api/weather',
    initialCity: 'Paris'
}) }}
```

---

## Guide de Migration Étape par Étape

### Étape 1 : Installer ReactBundle

```bash
composer require julien-lin/react-bundle-symfony
```

### Étape 2 : Créer la Structure

```bash
mkdir -p assets/React/Components
touch assets/React/index.js
touch assets/js/app.jsx
```

### Étape 3 : Configurer app.jsx

```jsx
// assets/js/app.jsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import * as ReactComponents from '../React';

document.querySelectorAll('[data-react-component]').forEach(element => {
    const componentName = element.dataset.reactComponent;
    const props = JSON.parse(element.dataset.props || '{}');
    const Component = ReactComponents[componentName];
    
    if (Component) {
        createRoot(element).render(<Component {...props} />);
    }
});
```

### Étape 4 : Convertir un Contrôleur Stimulus

#### Avant (Stimulus)

```javascript
// app/controllers/counter_controller.js
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = ["count"]
  static values = { initial: Number }

  connect() {
    this.count = this.initialValue
    this.updateDisplay()
  }

  increment() {
    this.count++
    this.updateDisplay()
  }

  decrement() {
    this.count--
    this.updateDisplay()
  }

  updateDisplay() {
    this.countTarget.textContent = this.count
  }
}
```

```html
<div data-controller="counter" data-counter-initial-value="0">
  <button data-action="click->counter#increment">+</button>
  <span data-counter-target="count">0</span>
  <button data-action="click->counter#decrement">-</button>
</div>
```

#### Après (React)

```tsx
// assets/React/Components/Counter.tsx
import React, { useState } from 'react';

interface CounterProps {
  initial?: number;
}

const Counter: React.FC<CounterProps> = ({ initial = 0 }) => {
  const [count, setCount] = useState<number>(initial);

  const increment = () => setCount(count + 1);
  const decrement = () => setCount(count - 1);

  return (
    <div>
      <button onClick={increment}>+</button>
      <span>{count}</span>
      <button onClick={decrement}>-</button>
    </div>
  );
};

export default Counter;
```

```javascript
// assets/React/index.js
export { default as Counter } from './Components/Counter';
```

```twig
{{ react_component('Counter', {
    initial: 0
}) }}
```

### Étape 5 : Migrer Progressivement

1. **Commencez par un contrôleur simple**
2. **Testez chaque conversion**
3. **Migrez contrôleur par contrôleur**
4. **Supprimez Stimulus une fois tout migré**

---

## Exemples de Conversion

### Exemple 1 : Formulaire avec Validation

#### Stimulus

```javascript
// app/controllers/form_controller.js
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = ["email", "error"]

  validate() {
    const email = this.emailTarget.value
    if (!email.includes('@')) {
      this.errorTarget.textContent = 'Invalid email'
      this.errorTarget.classList.add('visible')
    } else {
      this.errorTarget.classList.remove('visible')
    }
  }
}
```

#### React

```tsx
// assets/React/Components/Form.tsx
import React, { useState } from 'react';

const Form: React.FC = () => {
  const [email, setEmail] = useState<string>('');
  const [error, setError] = useState<string>('');

  const validate = (value: string) => {
    if (!value.includes('@')) {
      setError('Invalid email');
    } else {
      setError('');
    }
  };

  return (
    <form>
      <input
        type="email"
        value={email}
        onChange={(e) => {
          setEmail(e.target.value);
          validate(e.target.value);
        }}
      />
      {error && <div className="error visible">{error}</div>}
    </form>
  );
};

export default Form;
```

### Exemple 2 : Appel API

#### Stimulus

```javascript
// app/controllers/api_controller.js
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static values = { url: String }
  static targets = ["result", "loading"]

  async fetch() {
    this.loadingTarget.classList.add('visible')
    const response = await fetch(this.urlValue)
    const data = await response.json()
    this.resultTarget.textContent = JSON.stringify(data)
    this.loadingTarget.classList.remove('visible')
  }
}
```

#### React

```tsx
// assets/React/Components/ApiData.tsx
import React, { useState, useEffect } from 'react';

interface ApiDataProps {
  url: string;
}

const ApiData: React.FC<ApiDataProps> = ({ url }) => {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState<boolean>(false);

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      const response = await fetch(url);
      const result = await response.json();
      setData(result);
      setLoading(false);
    };
    fetchData();
  }, [url]);

  if (loading) {
    return <div className="loading visible">Loading...</div>;
  }

  return <div>{JSON.stringify(data)}</div>;
};

export default ApiData;
```

### Exemple 3 : Toggle (Show/Hide)

#### Stimulus

```javascript
// app/controllers/toggle_controller.js
import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
  static targets = ["content"]

  toggle() {
    this.contentTarget.classList.toggle('hidden')
  }
}
```

#### React

```tsx
// assets/React/Components/Toggle.tsx
import React, { useState } from 'react';

interface ToggleProps {
  children: React.ReactNode;
  label?: string;
}

const Toggle: React.FC<ToggleProps> = ({ children, label = 'Toggle' }) => {
  const [visible, setVisible] = useState<boolean>(false);

  return (
    <div>
      <button onClick={() => setVisible(!visible)}>
        {label}
      </button>
      {visible && <div>{children}</div>}
    </div>
  );
};

export default Toggle;
```

---

## Checklist de Migration

### Préparation

- [ ] Installer ReactBundle
- [ ] Créer la structure de dossiers
- [ ] Configurer `app.jsx`
- [ ] Configurer `vite.config.js`

### Migration

- [ ] Lister tous les contrôleurs Stimulus
- [ ] Identifier les dépendances entre contrôleurs
- [ ] Convertir les contrôleurs simples en premier
- [ ] Convertir les contrôleurs complexes
- [ ] Tester chaque composant converti
- [ ] Mettre à jour les templates Twig

### Nettoyage

- [ ] Supprimer les contrôleurs Stimulus convertis
- [ ] Supprimer les imports Stimulus inutilisés
- [ ] Supprimer `@hotwired/stimulus` si plus utilisé
- [ ] Mettre à jour la documentation

### Tests

- [ ] Tester tous les composants React
- [ ] Vérifier le HMR en développement
- [ ] Tester le build de production
- [ ] Vérifier les performances

---

## Mapping Stimulus → React

| Stimulus | React |
|----------|-------|
| `connect()` | `useEffect(() => {}, [])` |
| `disconnect()` | `useEffect(() => { return () => {} }, [])` |
| `static targets` | Props avec noms explicites |
| `static values` | Props typées |
| `static classes` | CSS Modules ou className |
| `this.element` | `ref` ou props |
| `this.targets.*` | Props enfants ou state |
| `this.values.*` | Props |
| Actions `data-action` | `onClick`, `onChange`, etc. |
| Events | `useEffect` avec event listeners |

---

## FAQ

### Puis-je utiliser Stimulus et React ensemble ?

**Oui**, mais ce n'est pas recommandé. Vous pouvez migrer progressivement, mais évitez les interactions complexes entre les deux.

### Comment gérer les événements personnalisés Stimulus ?

Utilisez `useEffect` avec des event listeners :

```tsx
useEffect(() => {
  const handler = (e: CustomEvent) => {
    // Handle event
  };
  window.addEventListener('stimulus:event', handler);
  return () => window.removeEventListener('stimulus:event', handler);
}, []);
```

### Comment migrer les valeurs complexes (objets, arrays) ?

Passez-les comme props JSON depuis Twig :

```twig
{{ react_component('MyComponent', {
    items: items|json_encode|raw,
    config: config|json_encode|raw
}) }}
```

### Les performances sont-elles meilleures avec React ?

Généralement **oui**, surtout pour :
- Composants avec beaucoup d'état
- Listes longues (virtual DOM)
- Applications complexes

Pour des composants très simples, la différence est négligeable.

---

## Ressources

- [Documentation ReactBundle](README.md)
- [Guide TypeScript](TYPESCRIPT.md)
- [Exemples](EXAMPLES.md)
- [Documentation Stimulus](https://stimulus.hotwired.dev/) (référence)

---

**Dernière mise à jour :** 2024-12-22

