# Synthèse : Double espace Particulier / Professionnel — Dossier métier V3.1

---

## 1. CE QUE DIT LE DOSSIER MÉTIER

**Sources :** Page 2 (glossaire "Statut", "Espace"), Page 3 (un compte, deux espaces), Page 4 (règle 3), T01 (page 20)

### 1a. Principes fondamentaux

| Règle | Source |
|-------|--------|
| Un seul compte par personne, identité unique | Page 2, Page 3 |
| L'inscription crée un espace **particulier** | Page 3 |
| L'activation PRO **ajoute** un second espace, sans remplacer le premier | Page 3 |
| Un PRO conserve **toujours** son espace particulier | Page 3 |
| Le statut (particulier/PRO) détermine les espaces accessibles, **pas les droits sur un cheval** | Page 2 |
| Les droits dépendent du **rôle auprès du cheval**, indépendamment du statut | Page 4, règle 3 |

### 1b. Définitions (glossaire Page 2)

| Terme | Définition |
|-------|-----------|
| **Statut** | Particulier ou Professionnel — détermine les espaces accessibles |
| **Espace** | Interface et fonctionnalités adaptées au contexte (particulier ou professionnel) |
| **Compte** | Unique par personne, peut donner accès à 1 ou 2 espaces |

### 1c. Fonctions de chaque espace

**Espace particulier** (Page 3) :
- Gérer ses propres chevaux (créer la fiche, désigner des référents)
- Suivre le dossier de santé de ses chevaux
- Recevoir et consulter les CR partagés
- Organiser des RDV pour ses chevaux

**Espace professionnel** (Page 3) :
- Exercer son activité : consultations, rédaction de CR
- Gérer son agenda et ses tournées
- Accéder au répertoire de contacts professionnels
- Gérer sa structure et ses paramètres PRO

### 1d. Cas concret — T01 (Élodie, page 20)

Élodie est ostéopathe et propriétaire de chevaux. Elle choisit "les deux" espaces lors de l'inscription. Résultat :
- **Un seul compte**, deux espaces
- Dans son espace professionnel : elle traite les chevaux de ses clients
- Dans son espace particulier : elle gère ses propres chevaux
- **Aucun pouvoir supplémentaire** sur les chevaux de ses clients du fait de son statut PRO — les droits restent liés au rôle auprès du cheval

---

## 2. ÉTAT ACTUEL DU CODE

### 2a. Entité User — champs liés au double espace

**Fichier :** `src/Entity/User.php`

| Champ | Type | Valeurs | État |
|-------|------|---------|------|
| `accountType` | string | `OWNER`, `PRO`, `BOTH`, `STRUCTURE` | Utilisé à l'inscription |
| `roles` | array | `ROLE_USER`, `ROLE_PRO`, `ROLE_STRUCTURE` | Utilisé dans la sécurité |
| `activeSpace` | string nullable | `'particulier'`, `'professionnel'`, `null` | **Jamais lu ni écrit par les controllers** |

### 2b. Méthodes existantes dans User.php

| Méthode | Logique | État |
|---------|---------|------|
| `hasProSpace()` | Vérifie `ROLE_PRO` dans les rôles | OK |
| `hasParticulierSpace()` | `accountType` in `['OWNER', 'BOTH']` ou fallback | OK |
| `hasBothSpaces()` | `hasProSpace() && hasParticulierSpace()` | OK |
| `isInProSpace()` | Lit `activeSpace`, fallback sur `hasProSpace()` | OK mais **jamais appelé** hors de l'entité |
| `enableProSpace()` | Ajoute `ROLE_PRO`, `OWNER` → `BOTH` | OK mais **jamais appelé** depuis un formulaire utilisateur |

### 2c. Header et navigation

**Fichier :** `templates/_partials/_header.html.twig`

| Élément | Comportement actuel |
|---------|-------------------|
| Badge "PRO" | Affiché si `is_granted('ROLE_PRO')` |
| Menu principal | Conditionnel sur `ROLE_PRO` : agenda, répertoire, structures visibles uniquement pour PRO |
| Bascule d'espace | **Inexistante** — pas de bouton ni de lien pour changer d'espace |
| Menu "particulier" | **Inexistant** — pas de menu alternatif quand un PRO veut gérer ses propres chevaux |

### 2d. Sécurité (Voter)

**Fichier :** `src/Security/Voter/AnimalVoter.php`

Le voter vérifie les rôles/référents sur l'animal mais **ne tient pas compte de l'espace actif**. C'est conforme au dossier : les droits dépendent du rôle auprès du cheval, pas du statut (Page 4, règle 3).

### 2e. Inscription

**Fichier :** `src/Controller/RegistrationController.php`

- Inscription PRO → `ROLE_PRO`, `accountType = PRO`
- Inscription particulier → `ROLE_USER`, `accountType = OWNER`
- Inscription structure → `ROLE_STRUCTURE`, `accountType = STRUCTURE`
- **Pas de choix "les deux"** (T01 non implémenté)

---

## 3. ÉCARTS AVEC LE DOSSIER MÉTIER

| # | Attendu (dossier) | État actuel | Source |
|---|-------------------|-------------|--------|
| 1 | Un utilisateur `BOTH` peut basculer entre espace PRO et particulier | `activeSpace` existe mais n'est jamais lu/écrit par les controllers | Page 3 |
| 2 | Le menu s'adapte à l'espace actif | Menu fixe basé uniquement sur `ROLE_PRO` | Page 3 |
| 3 | En espace particulier : "Mes chevaux" (propriétaire/référent) | Les chevaux affichés ne dépendent pas de l'espace | Page 3 |
| 4 | En espace professionnel : "Mes patients" (via consultations) | Pas de filtrage par espace | Page 3 |
| 5 | Choix "les deux" à l'inscription | Seulement PRO ou particulier | T01 |
| 6 | Un particulier peut devenir PRO après l'inscription | `enableProSpace()` existe mais aucun formulaire | Page 3 |
| 7 | Pas de pouvoir supplémentaire du fait du statut PRO | Conforme (voter agnostique de l'espace) | Page 4, règle 3 |

---

## 4. CE QUI DOIT ÊTRE IMPLÉMENTÉ

### 4a. Bascule d'espace (utilisateurs BOTH)

| Fonctionnalité | Description | Priorité |
|---|---|---|
| **Bouton switch dans le header** | Visible si `hasBothSpaces()`, bascule entre "Espace PRO" et "Espace Particulier" | Haute |
| **Route `/switch-space`** | POST qui toggle `activeSpace` entre `'professionnel'` et `'particulier'`, persiste en BDD, redirige | Haute |
| **Initialisation** | À la connexion, si `activeSpace` est `null` et `hasBothSpaces()`, initialiser à `'particulier'` par défaut | Haute |
| **Persistence session** | `activeSpace` lu depuis la BDD à chaque requête (ou mis en session) | Haute |

### 4b. Menu conditionnel selon l'espace actif

| Espace actif | Menu principal |
|---|---|
| **Particulier** | Mes chevaux, Mon profil, Mes invitations |
| **Professionnel** | Dashboard PRO, Agenda, Consultations, Répertoire, Structures, Tournées |
| **Commun (toujours visible)** | Profil, Paramètres, Déconnexion |

### 4c. Filtrage des données selon l'espace

| Contexte | Espace particulier | Espace professionnel |
|---|---|---|
| Liste chevaux | Mes propres chevaux (owner + référent actif) | Chevaux de mes clients (via consultations/RDV) |
| Consultations | CR reçus / partagés avec moi | CR que j'ai rédigés |
| Agenda | RDV personnels (pour mes chevaux) | RDV professionnels (consultations) |
| Répertoire | Non affiché | Mes contacts PRO |

### 4d. Choix "les deux" à l'inscription

| Étape | Description | Source |
|---|---|---|
| Option supplémentaire au formulaire | Ajouter un choix "Particulier et Professionnel" | T01 |
| Résultat | `accountType = BOTH`, `roles = [ROLE_USER, ROLE_PRO]` | T01 |
| Redirection | Vers un formulaire de paramétrage PRO (métier, spécialité) | Page 16 |

### 4e. Upgrade particulier → PRO (après inscription)

| Étape | Description | Source |
|---|---|---|
| Bouton "Devenir professionnel" | Visible dans le profil si `!hasProSpace()` | Page 3 |
| Formulaire | Métier, spécialité, numéro professionnel | Page 16 |
| Activation | `enableProSpace()` : ajoute `ROLE_PRO`, `OWNER` → `BOTH` | Code existant |
| Résultat | L'utilisateur voit le switch d'espace dans le header | Page 3 |

---

## 5. IMPACT SUR LE TRANSFERT DE PRINCIPAL

Le transfert de principal doit fonctionner **quel que soit l'espace actif** :

| Situation | Comportement | Source |
|---|---|---|
| Un particulier est principal de son cheval | Il peut proposer un transfert depuis son espace particulier | Page 8 |
| Un PRO dans son espace professionnel | Peut **proposer** un nouveau principal, mais ne l'impose pas | Page 8, T16 |
| Le destinataire est particulier ou PRO | L'invitation arrive dans "Mes invitations" quel que soit l'espace | Page 12 |

Le voter `AnimalVoter::isPrincipalReferent()` est déjà **agnostique de l'espace** — il vérifie le rôle via `AnimalReferent`, pas le `accountType`. C'est conforme : "Les droits dépendent du rôle auprès du cheval, pas du statut" (Page 4, règle 3).

---

## 6. ORDRE D'IMPLÉMENTATION RECOMMANDÉ

1. **Route `/switch-space`** + persistence `activeSpace` en BDD
2. **Bouton switch dans le header** (visible si `hasBothSpaces()`)
3. **Menu conditionnel** selon `isInProSpace()`
4. **Filtrage des listes** (chevaux, consultations, agenda) selon l'espace
5. **Choix "les deux" à l'inscription** (T01)
6. **Upgrade particulier → PRO** (formulaire dans le profil)
7. **Transfert de principal** (qui utilise le `isInProSpace()` pour adapter l'UI)
