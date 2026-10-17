# ParisFOOT

Application Laravel d’administration des cotes football, avec synchronisation The Odds API v4, calculs 1X2, API REST Sanctum et panneau Filament.

## Prérequis

- PHP 8.2+ avec `curl`, `intl`, `mbstring`, `openssl`, `zip` et `pdo_mysql` ou `pdo_sqlite`
- Composer 2
- Node.js 20+ et npm
- MySQL 8+ ou SQLite
- Une clé The Odds API pour activer la synchronisation

Le projet utilise Laravel 12. Exécutez `composer audit` avant les mises en production et maintenez les dépendances à jour.

## Installation

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views bootstrap/cache storage/logs
npm ci
npm run build
```

Configurez `.env`. SQLite est sélectionné par défaut ; créez le fichier vide `database/database.sqlite` avant les migrations. Pour MySQL, renseignez `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` et `DB_PASSWORD`.

```dotenv
ODDS_API_KEY=votre-cle
ODDS_API_URL=https://api.the-odds-api.com/v4
ODDS_API_SPORT=soccer_france_ligue_one
ODDS_API_REGIONS=fr
ODDS_API_ODDS_FORMAT=decimal
ODDS_API_BOOKMAKERS=
ODDS_FETCH_MINUTES=30
ADMIN_NAME=Administrateur
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=changez-ce-mot-de-passe
```

Définissez un mot de passe administrateur unique dans `.env` avant le seeding ; le seeder refuse de créer le compte si `ADMIN_PASSWORD` est vide ou égal à `password`. Ne publiez jamais le fichier `.env`.

```bash
php artisan migrate --seed
php artisan serve
```

Le panneau est disponible à `/admin`. Le compte initial utilise l’adresse définie par `ADMIN_EMAIL` et le mot de passe défini par `ADMIN_PASSWORD`. Filament refuse l’accès aux comptes qui ne sont pas `admin` ou `super_admin`.

## Démo en ligne sur Render

Le projet inclut un `Dockerfile` et un Blueprint `render.yaml` pour créer une démo Render avec une base PostgreSQL. Le service gratuit peut se mettre en veille après une période sans visite ; son premier chargement peut prendre environ une minute. La base gratuite peut également avoir ses propres limites de durée et de disponibilité : consultez les conditions Render avant la remise.

1. Publiez le code source sur un dépôt GitHub sans y inclure `.env`, une base de données locale ou des clés.
2. Dans Render, choisissez **New > Blueprint**, connectez le dépôt et lancez le déploiement.
3. Lors de la configuration du Blueprint, renseignez `APP_KEY`, `ADMIN_EMAIL` et `ADMIN_PASSWORD` comme variables secrètes. Générez une clé localement avec `php artisan key:generate --show` ; ne la publiez pas. Choisissez un mot de passe admin unique.
4. Une fois le service déployé, ouvrez son URL Render suivie de `/admin`.
5. Pour synchroniser les cotes, ajoutez une nouvelle clé `ODDS_API_KEY` dans les variables d’environnement du service Render, puis lancez la commande `php artisan odds:fetch --force` depuis le shell du service.

Le démarrage applique les migrations et met à jour le compte administrateur à partir des variables d’environnement. Ne communiquez jamais ces secrets dans le dépôt ou dans un fichier `.env` envoyé au professeur.

## Calculs

Pour chaque issue $i \in \{1, X, 2\}$, la probabilité implicite est $p_i = 100 / cote_i$. La somme implicite $S = \sum_i p_i$ donne la marge bookmaker $S - 100$. La probabilité normalisée est $p_i / S \times 100$ ; l’échec est $100 - p_i$.

Le calculateur rejette les cotes décimales inférieures ou égales à 1. Les résultats sont enregistrés par bookmaker dans `analyses`.

Une value bet n’est déclarée que si une estimation indépendante est fournie et dépasse la probabilité normalisée du bookmaker pour au moins une issue. Les estimations fournies sont normalisées pour totaliser 100 %. Sans modèle statistique indépendant, aucune value bet n’est déduite automatiquement des seules cotes.

Un arbitrage est détecté si la somme des inverses des meilleures cotes disponibles pour 1, X et 2 est inférieure à 1. Le rendement théorique retourné ne tient pas compte des limites, frais ou changements de cotes.

## API

Connectez-vous pour obtenir un jeton Sanctum ; seuls les rôles `admin` et `super_admin` peuvent recevoir un jeton API :

```bash
curl -X POST http://localhost:8000/api/login \\
    -H 'Accept: application/json' -H 'Content-Type: application/json' \\
    -d '{"email":"admin@example.com","password":"votre-mot-de-passe","device_name":"dashboard"}'
```

Toutes les routes de lecture nécessitent `Authorization: Bearer <access_token>`. Appelez `POST /api/logout` avec le même en-tête pour révoquer le jeton.

Les routes d’écriture nécessitent également `admin_password` dans le JSON. Chaque action réussie est inscrite dans `admin_action_logs`. Les mots de passe incorrects sont limités à cinq tentatives par utilisateur et adresse IP sur 60 secondes.

| Méthode      | Route                        | Description                                                         |
| ------------ | ---------------------------- | ------------------------------------------------------------------- |
| POST         | `/api/login`                 | Connexion API et émission d’un jeton Sanctum                        |
| POST         | `/api/logout`                | Révoquer le jeton courant                                           |
| GET          | `/api/matches`               | Liste paginée ; filtres `date`, `competition`, `source`, `per_page` |
| GET          | `/api/matches/{id}`          | Détail du match, cotes et analyses                                  |
| GET          | `/api/matches/{id}/odds`     | Cotes du match                                                      |
| GET          | `/api/matches/{id}/analysis` | Analyses complètes du match                                         |
| GET          | `/api/value-bets`            | Cotes associées à une estimation value bet                          |
| GET          | `/api/arbitrages`            | Opportunités calculées à partir des meilleures cotes                |
| GET          | `/api/history/{odd_id}`      | Historique des modifications de cote                                |
| POST         | `/api/matches`               | Créer un match manuel                                               |
| PUT / DELETE | `/api/matches/{id}`          | Modifier / supprimer un match                                       |
| POST         | `/api/matches/{id}/odds`     | Ajouter une cote manuelle                                           |
| PUT / DELETE | `/api/odds/{id}`             | Modifier / supprimer une cote                                       |
| POST         | `/api/refresh`               | Forcer une synchronisation externe                                  |

Exemple de création de match :

```json
{
    "equipe_a": "Paris",
    "equipe_b": "Marseille",
    "date_match": "2026-10-20T20:00:00Z",
    "competition": "Ligue 1",
    "admin_password": "votre-mot-de-passe",
    "raison": "Saisie manuelle"
}
```

Exemple d’ajout de cote avec estimation indépendante facultative :

```json
{
    "bookmaker": "Bookmaker exemple",
    "cote_1": 2.1,
    "cote_X": 3.4,
    "cote_2": 3.6,
    "probabilites_estimees": { "1": 50, "X": 25, "2": 25 },
    "admin_password": "votre-mot-de-passe",
    "raison": "Analyse indépendante"
}
```

## Import et planification

Le client appelle l’endpoint The Odds API v4 `/sports/{sport}/odds` avec le marché `h2h`, le format décimal et une région. Les valeurs par défaut visent la Ligue 1 française : `ODDS_API_SPORT=soccer_france_ligue_one` et `ODDS_API_REGIONS=fr`. La documentation officielle répertorie les clés exactes de sports et de bookmakers. `ODDS_API_BOOKMAKERS` est facultatif ; si défini, il remplace les régions et doit contenir des clés bookmaker comme `betclic_fr,winamax_fr`. Les résultats `home_team`, `away_team`, `commence_time`, puis les issues `home_team`, `Draw`, `away_team` du marché `h2h` sont stockés localement. La réponse est mise en cache 25 minutes pour limiter le quota ; le coût dépend du nombre de régions et de marchés.

```bash
php artisan odds:fetch
php artisan odds:fetch --force
```

Le scheduler lance la commande selon `ODDS_FETCH_MINUTES` (30 minutes par défaut). En développement :

```bash
php artisan schedule:work
```

En production, configurez le cron Laravel standard : `* * * * * cd /chemin/ParisFOOT && php artisan schedule:run >> /dev/null 2>&1`.

## Dashboard

- **Dashboard** : compteurs matchs, cotes et value bets stockés.
- **Matchs** : recherche, filtres par source, création/édition/suppression.
- **Cotes** : saisie manuelle, valeurs estimées indépendantes, édition et historique.
- **Analyses** : probabilités normalisées, marge et statut value bet.
- **Value bets** : uniquement les opportunités associées à une estimation indépendante.
- **Historique** : auteur, action, cible, IP et raison.
- **Évolution des cotes** : anciennes et nouvelles valeurs, par match et bookmaker, y compris les mises à jour externes.
- **Paramètres** : bookmaker(s) activés, fréquence de synchronisation, seuil value bet et état de la clé API.

Les opérations sensibles du dashboard ouvrent un formulaire de confirmation demandant le mot de passe administrateur et une raison facultative. Les modifications de cotes conservent également les anciennes et nouvelles valeurs dans `odds_history`.

La clé The Odds API n’est jamais enregistrée en base ni affichée dans le panneau : définissez-la dans `.env`. Les autres paramètres du panneau sont persistés dans `app_settings` et prennent effet lors de la prochaine synchronisation/analyse.

## Espace client

Les visiteurs peuvent créer un compte à `/client/register` puis se connecter à `/client/login`. Une inscription reçoit toujours le rôle `client` côté serveur, sans champ de rôle modifiable. Toutes les pages de consultation, y compris les fiches match, sont réservées aux clients connectés. L’espace affiche uniquement les matchs à venir, les cotes et les probabilités 1/X/2, avec recherche, filtre compétition et fiche match. Les comptes clients sont refusés par l’API admin Sanctum et le panneau Filament.

Le lien « Mot de passe oublié » permet à un client de demander un lien de réinitialisation. Configurez le transport de messagerie Laravel (`MAIL_MAILER`, `MAIL_HOST`, identifiants et expéditeur) en production ; avec la configuration locale par défaut, les courriels sont écrits dans les logs.

## Tests

```bash
composer test
```

La commande exécute PHPUnit. La configuration utilise SQLite en mémoire. Les tests couvrent les parcours client, la réinitialisation de mot de passe, le calculateur, le middleware de mot de passe/rate limit, les endpoints d’écriture et la synchronisation simulée.
