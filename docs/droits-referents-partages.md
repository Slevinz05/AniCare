# Synthèse : Droits, référents et partages — Dossier métier V3.1

---

## 1. LES RÔLES AUPRÈS DU CHEVAL

Le dossier définit 5 acteurs avec des droits distincts. Les droits dépendent du **rôle auprès du cheval**, pas du statut (particulier/PRO).

**Sources :** Page 2 (glossaire), Page 4 (règle 3), Page 5 (matrice)

| Rôle | Qui | Droits clés |
|------|-----|-------------|
| **Référent principal** | Désigné explicitement (pas automatique) | Désigne les référents, modifie la fiche, voit le dossier complet, partage des CR, organise des RDV |
| **Référent secondaire** | Nommé par le principal | Droits **selon ce que le principal lui accorde** (lecture fiche, partage limité, organisation RDV) |
| **Propriétaire** | Se déclare comme tel (rôle de lien, pas de gestion) | Peut créer la fiche — mais **pas** de droits de gestion automatiques |
| **PRO intervenant** | Le professionnel qui consulte/traite | Rédige et valide **ses propres** CR, peut partager **son propre** travail, organise des RDV |
| **Tiers autorisé** | Reçoit un accès limité | Lecture seule de ce qui lui a été partagé, aucun rôle de référent |

**Point essentiel** (Page 2, glossaire "Désignation comme référent") : La désignation est un **acte explicite**. Être propriétaire, créer la fiche, ou être PRO ne confère **aucun** droit de gestion automatique.

---

## 2. RÉFÉRENT PRINCIPAL — Cycle de vie complet

### 2a. Désignation à la création de la fiche

**Source :** Page 7 (3 cas), Logigramme 2 (page 24)

**3 scénarios possibles :**

1. **Le créateur se désigne lui-même** → Choix explicite sur fiche nouvelle → Activation après contrôles (D3/D8)
   - *Dans le site :* Le formulaire de création propose "Je suis le référent principal" → Activation immédiate après vérification qu'aucun doublon n'existe

2. **Le créateur propose un autre utilisateur** → Invitation → Le candidat accepte ou refuse
   - Accord = activation, refus ou silence = **pas d'activation** (Page 7, règle retenue + T11 page 21)
   - *Dans le site :* Le formulaire de création propose de chercher un utilisateur existant → Envoi d'une invitation en statut `pending` → Page "Mes invitations" pour accepter/refuser

3. **Un principal existe déjà** → Transfert ou récupération (Page 8)
   - *Dans le site :* Le formulaire signale qu'un principal existe déjà → Pas de remplacement automatique (T04, page 20)

**Règle retenue** (Page 7) : Un seul principal actif à la fois. Le refus ou le silence du candidat = pas d'activation.

**État du code :**
- `AnimalReferent` avec `type = principal|secondaire` et `status = active|pending|revoked|refused` — conforme
- Le formulaire de création (`AnimalController::new()`) gère déjà les 3 cas
- Le controller `ReferentController::invite()` crée toujours un secondaire — **il manque le cas de la proposition de principal**

### 2b. Transfert de principal

**Source :** Page 8, D8 (page 19), T16 (page 21), Logigramme 2

**Règles retenues :**
- Le principal propose un successeur. **Pas de bascule unilatérale** (Page 8, T16)
- Le professionnel peut **proposer** un nouveau principal, mais ne l'impose pas (Page 8)
- Le créateur peut se désigner explicitement **sans écraser un principal existant** (D8)
- Accord des personnes concernées nécessaire (T16)

**Ce que ça implique dans le site :**

| Fonctionnalité | Description | Source |
|---|---|---|
| **Bouton "Proposer un transfert"** | Visible uniquement par le principal actif, sur la fiche animal | Page 8 |
| **Recherche du successeur** | Chercher un utilisateur par nom/email | Page 8 |
| **Invitation de transfert** | Créer un `AnimalReferent` type=principal, status=pending pour le successeur | Logigramme 2 |
| **Acceptation par le successeur** | Le successeur accepte → l'ancien principal perd son rôle de principal (mais peut rester secondaire ou simplement perdre ses droits) | D8 |
| **Refus/silence** | Rien ne change, le principal actuel reste en place | T11 (page 21) |
| **Fiche sans principal** | Si le principal se retire sans successeur → les accès restent en lecture | Page 8 |
| **PRO peut proposer** | Un PRO peut suggérer un transfert, mais c'est le principal qui décide | Page 8, T16 |

**Ce qui manque dans le code :**
- Pas de parcours "transfert de principal" (ni route, ni formulaire, ni logique)
- `ReferentController::revoke()` interdit la révocation du principal mais ne propose pas de transfert
- Pas de gestion de "fiche sans principal" (accès dégradés en lecture seule)

### 2c. Conflit de doublons

**Source :** Page 8, T15 (page 21)

Deux fiches semblent désigner le même cheval avec deux principaux → **aucune fusion automatique** des historiques et permissions. Résolution manuelle.

*Dans le site :* À terme, une interface admin/support pour signaler et résoudre les conflits. Pour le pilote, le contrôle doublons à la création (déjà implémenté) est la première ligne de défense.

---

## 3. RÉFÉRENT SECONDAIRE — Droits et limites

**Source :** Page 7 (référent secondaire), Page 5 (matrice "selon droits"), D2 (page 18), T05 (page 20)

**Règles retenues :**
- Nommé **par le principal** uniquement (Page 7)
- **Suit les droits du principal** mais est limité à ce qui lui est accordé (Page 7, D2)
- Aucun droit par simple statut ou appartenance à une structure (D2)

**Ce que ça implique dans le site :**

| Fonctionnalité | Description | Source |
|---|---|---|
| **Invitation secondaire** | Le principal (ou un secondaire habilité ?) invite quelqu'un | Page 7 |
| **Rôle fonctionnel** | Cavalier, entraîneur, gérant, groom, éleveur, autre | Existant dans `AnimalReferent::ROLES` |
| **Permissions granulaires** | Le secondaire peut lire mais pas partager (T05), sauf délégation | T05, D2 |
| **Partage limité** | Après délégation valable, partage limité au périmètre reçu | T05 |
| **Révocation** | Le principal peut révoquer un secondaire | Existant dans `ReferentController::revoke()` |
| **Cascade sur retrait** | Si le secondaire est retiré, le devenir de ses partages émis dépend de D2/D5 | Page 12 |

**État du code :**
- Invitation de secondaire : `ReferentController::invite()` — OK, crée bien un `TYPE_SECONDAIRE`
- Acceptation/refus : `accept()` / `refuse()` — OK
- Révocation par le principal : `revoke()` — OK (bloque sur le principal)

**Ce qui manque :**
- **Permissions granulaires** par secondaire (lecture seule vs. partage vs. organisation RDV) — aujourd'hui le secondaire est soit actif soit non, sans nuance
- **Contrôle de partage** : vérifier que le secondaire a le droit de partager avant de l'autoriser (T05)
- **Impact de la révocation** sur les partages émis par le secondaire

**Point "à arbitrer" D2 :** Les droits initiaux du secondaire, les capacités délégables, la durée, l'invitation et l'acceptation, le retrait en cascade. **Ce n'est pas encore tranché** — Lucas doit décider.

---

## 4. PARTAGE DE CONSULTATIONS (CR)

**Source :** Page 11 (parcours complet), Page 5 (matrice), Logigramme 4 (page 26), D1/D5

### 4a. Qui peut partager quoi

| Acteur | Ce qu'il peut partager | Condition | Source |
|---|---|---|---|
| **Réf. principal** | Tout CR du dossier, la fiche, ou une sélection | Toujours | Page 5, Page 11 |
| **Réf. secondaire** | Ce à quoi il a accès | **Seulement si le principal lui a accordé le droit de partager** | T05, D2 |
| **PRO intervenant** | **Son propre CR uniquement** | Ne partage pas le travail des autres | Page 5, Page 11 ("Et le professionnel ?") |
| **Tiers autorisé** | Rien | Lecture seule | Page 5 |

**Point crucial** (Page 11, "Et le professionnel ?") : Le professionnel a un circuit pour transmettre **son propre travail**, pas pour partager librement les CR des autres. Toute annexe provenant d'un tiers doit être contrôlée séparément.

### 4b. Le parcours de partage en 6 étapes

**Source :** Page 11

1. **Choisir** les contenus à partager (principal ou secondaire habilité)
2. **Désigner** le destinataire (particulier, PRO, ou contact de structure)
3. **Vérifier** (AniCare) le droit de partage sur chaque contenu
4. **Confirmer** la sélection
5. **Donner accès** (AniCare active, trace l'opération)
6. **Consulter** (le destinataire lit seulement ce qui lui est accordé — **aucun rôle de référent créé**, T06)

**Deux types de partage à distinguer** (Page 11) :
- **Partager des documents** : sélection ponctuelle. Le destinataire ne découvre pas le reste.
- **Partager la fiche/dossier** : périmètre de consultation dans le temps. Les futurs contenus ne sont pas automatiquement inclus.

**État du code :**
- `HealthBookEntryShare` : partage d'un CR vers un user, email, ou structure — OK pour le partage ponctuel
- `HealthBookEntryController` gère le partage depuis la page show d'une consultation

**Ce qui manque :**
- **Vérification des droits de partage** : aujourd'hui pas de check que le secondaire a le droit de partager
- **Partage de dossier** (périmètre continu dans le temps, pas juste un CR à la fois)
- **Lecture seule par défaut, sans droit de repartage** (Page 11, proposition)
- **Mode d'accès du destinataire** : version figée vs courante, durée, révocation (D5)
- **Traçabilité partage** : qui a partagé quoi, quand (Page 16, "Trace")

### 4c. Partage par le PRO de son propre CR

**Source :** Page 11, D5 (page 19, point à résoudre)

**Point non tranché** (D5) : L'ancien cadrage prévoyait l'accord du référent avant la transmission par le PRO. H2 prévoyait une autonomie du PRO sur son propre CR. **Lucas doit arbitrer.**

*Dans le site actuel :* Le PRO peut partager sa consultation depuis la page show — il faudrait vérifier si le référent doit approuver ou non, selon l'arbitrage D5.

---

## 5. INVITATIONS ET ACCÈS EXTERNES

**Source :** Page 12

**Les 5 états d'une invitation/autorisation :**

| État | Signification | Source |
|---|---|---|
| `pending` (Préparé/En attente) | Contenu et destinataire choisis, réponse attendue | Page 12 |
| `active` (Actif) | Conditions réunies, accès contrôlé | Page 12 |
| `refused` (Refusé/annulé) | Aucun accès | Page 12 |
| `revoked` (Expiré/révoqué) | Accès fermé, droits indépendants examinés séparément | Page 12 |
| *(manque)* `expired` | Accès fermé par durée | Page 12 ("Expiré / révoqué"), D5 |

**État du code :** `AnimalReferent::STATUSES` = `active`, `pending`, `revoked`, `refused` — conforme.

**Ce qui manque :**
- **Durée / expiration** : aucun champ `expiresAt` sur `AnimalReferent` ni `HealthBookEntryShare`
- **Destinataire sans compte** (Page 12) : possibilité de partager via lien protégé (D5, pas encore arbitré)
- **Retrait en cascade** : si un secondaire perd ses droits, que deviennent les partages qu'il a émis ? (D2/D5)
- **Retrait d'accès ≠ suppression de contenu** (Page 12) : le CR reste dans le dossier, seul l'accès est fermé

---

## 6. VISIBILITÉ DES CR (D1)

**Source :** Page 10, D1 (page 18), T07-T08 (page 20)

**Retenu :**
- Un brouillon n'est jamais diffusé, pas de rappel, pas de partage (T08)
- Un PRO peut rédiger sans accès à l'historique (T07) — son travail est enregistré, le passé reste inaccessible
- Après validation, visibilité selon D1 (pas encore tranché : auteur seul jusqu'au partage, ou auteur + référents ?)

**État du code :** `HealthBookEntry` a un champ `status` (draft/validated). Le controller vérifie l'accès aux brouillons.

**Ce qui manque :**
- **Arbitrage D1** à implémenter : qui voit un CR validé avant partage explicite ?

---

## 7. CORRECTIONS ET CONSERVATION (D7)

**Source :** Page 10, D7 (page 19), T14 (page 21)

**Socle retenu :**
- Provenance conservée (qui a écrit quoi)
- Un tiers ne réécrit pas le CR d'un autre
- Retirer un droit ne supprime pas le contenu
- Correction = version mise à jour, pas doublon (T14)

**Ce qui manque dans le code :**
- **Versioning** des CR (historique des modifications)
- **Lien correction ↔ rappels** : quand un CR est corrigé, ses rappels sont mis à jour (T14)

---

## 8. RÉCAPITULATIF : CE QUI EST À DÉVELOPPER

### Fonctionnel — nécessite un arbitrage de Lucas (D1-D8)

| Sujet | Décision | Source |
|---|---|---|
| Visibilité d'un CR validé | Auteur seul ou auteur + référents ? | D1 |
| Permissions du secondaire | Quels droits précisément ? Granularité ? | D2 |
| Retrait en cascade | Si secondaire révoqué, ses partages restent ? | D2/D5 |
| PRO : accord du référent avant partage | Autonomie sur son CR ou validation requise ? | D5 |
| Durée des partages | Durée limitée ? Expiration ? | D5 |
| Destinataire sans compte | Lien protégé ? Création de compte obligatoire ? | D5 |

### Fonctionnel — implémentable sans arbitrage

| Fonctionnalité | État actuel | À faire | Source |
|---|---|---|---|
| **Transfert de principal** | Non implémenté | Proposer un successeur, invitation, acceptation, ancien principal dégradé | Page 8, D8, Logigramme 2 |
| **Fiche sans principal** | Non géré | Accès dégradés en lecture si aucun principal actif | Page 8 |
| **Proposition de principal par invitation** | Invitation = toujours secondaire | Permettre `invite()` avec `TYPE_PRINCIPAL` + statut `pending` | Page 7, cas 2 |
| **Révocation du principal impossible sans transfert** | `revoke()` bloque | Proposer le transfert à la place | Page 8 |
| **PRO ne partage que ses propres CR** | Pas de vérification | Check `$entry->getCreatedBy() === $user` avant partage | Page 11 |
| **Secondaire ne partage pas sans droit** | Pas de vérification | Check droits avant partage | T05 |
| **Traçabilité partage** | `HealthBookEntryShare::createdAt` uniquement | Ajouter qui a partagé (`sharedBy`), tracer les révocations | Page 16 |
| **Durée/expiration** | Aucun champ | Ajouter `expiresAt` sur `HealthBookEntryShare` | D5, Page 12 |
