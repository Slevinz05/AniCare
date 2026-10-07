# AniCare

## Stack technique
- Symfony 7.x, Twig, Doctrine ORM, Bootstrap 5
- `symfony serve` sur port 8000
- Compte test PRO : `pierre.renault83@gmail.com` / `Test1234!` (id=2)
- Design system : `--primary-color: #1a4d45`, `--accent-color: #C8973E`, `--soft-teal: #eef7f5`, `--secondary-color` (brown), `--border-color: #EDE6DB`

## Droits, référents et partages (coeur du système)
Voir `docs/droits-referents-partages.md` pour la synthèse exhaustive des règles métier extraites du dossier V3.1.
Ce document est la **référence obligatoire** pour toute fonctionnalité touchant :
- Les rôles auprès du cheval (principal, secondaire, PRO, tiers autorisé)
- La désignation, le transfert et la révocation de référents
- Le partage de consultations et de dossiers
- Les invitations, accès externes et expiration des droits
- La visibilité des CR (brouillon/validé/partagé)
- La traçabilité (qui a fait quoi, quand)

## Double espace Particulier / Professionnel
Voir `docs/double-espace-pro-particulier.md` pour la synthèse exhaustive du système de double espace.
Ce document est la **référence obligatoire** pour toute fonctionnalité touchant :
- La bascule d'espace (PRO ↔ particulier)
- Le menu et la navigation conditionnels selon l'espace actif
- Le filtrage des données (chevaux, consultations, agenda) par espace
- L'upgrade particulier → PRO
- Le choix "les deux" à l'inscription (T01)
- L'impact de l'espace sur le transfert de principal

## Routes principales
- Animaux : `/mes-chevaux/{slug}` (prefix `/mes-chevaux`, route `app_animal_show`)
- Structures : `/structures/{id}` (prefix `/structures`, route `app_structure_show`)
- Répertoire : `/repertoire` (route `app_repertoire_reseau`)
- Calendrier : `/calendrier` (route `app_calendar`)
- Consultations : `/consultations` (route `app_health_book_entry_index`)
- Profil : `/profil` (route `app_profile_show`, uniquement l'utilisateur connecté)

---

## Dossier Métier V3.1 — 29.09.2026

Source : AniCare_Dossier_metier_V3.pdf (28 pages)
Contenu brut ci-dessous, sans interprétation.

---

### Page 1 — Vue d'ensemble

**La promesse :** Un dossier de santé partageable, rattaché au cheval, pas à un seul praticien.

**Choix structurants :**
- Un compte unique par personne, avec deux espaces possibles (particulier / professionnel)
- Le cheval est l'objet central : une fiche, un dossier de santé, des référents désignés
- Les droits dépendent du rôle auprès du cheval, pas d'un statut global
- Le professionnel enregistre son propre travail ; le partage passe par le référent

**Table des matières :**
1. Glossaire
2. Un compte, deux espaces possibles
3. Les règles communes et objets métier
4. Qui peut faire quoi
5. Trouver ou créer le bon cheval
6. Désigner les référents à la création
7. Changer de principal sans perdre le dossier
8. Réaliser une consultation professionnelle
9. Conserver une information fiable dans le temps
10. Partager à l'initiative du référent
11. Invitations, accès externes et fin des droits
12. Une structure utile, même sans gérant connecté
13. Rendez-vous et tournées
14. Rappels et notifications sans effets cachés
15. Les informations à prévoir
16. Construire une première version testable
17. D1 à D4 — Les décisions de base
18. D5 à D8 — Le partage et la continuité
19. Huit situations pour vérifier le socle (T01-T08)
20. Huit situations de changement ou d'échec (T09-T16)
21. La consigne pour Pierre
22. Logigrammes simples (6 schémas)

---

### Page 2 — Glossaire

- **Utilisateur** : Toute personne inscrite sur AniCare. Un seul compte par personne.
- **Statut** : Particulier ou professionnel. Détermine les espaces accessibles, pas les droits sur un cheval.
- **Espace** : Interface adaptée au statut. Un PRO peut aussi avoir un espace particulier.
- **Rôle auprès du cheval** : Propriétaire, référent principal, référent secondaire, professionnel intervenant. Indépendant du statut.
- **Désignation comme référent** : Acte explicite du créateur de la fiche ou d'un référent existant. Ne découle pas automatiquement du statut ou de la propriété.
- **Autorisation** : Droit accordé par le référent principal (ou secondaire habilité) à un tiers pour consulter ou agir sur le dossier. Limitée dans le temps et le périmètre.
- **Client / contact** : Personne liée au professionnel par une relation de service. Pas nécessairement référent.
- **Structure** : Lieu d'exercice ou d'hébergement (écurie, clinique, etc.). Peut exister sans gérant inscrit.
- **Tiers autorisé** : Personne ayant reçu un accès limité au dossier, sans rôle de référent.

---

### Page 3 — Un compte, deux espaces possibles

- L'inscription crée un compte unique avec un espace particulier.
- L'activation PRO ajoute un second espace, sans remplacer le premier.
- Un particulier peut devenir PRO plus tard ; un PRO conserve toujours son espace particulier.
- Les deux espaces partagent le même compte, la même identité.
- L'espace particulier sert à gérer ses propres chevaux.
- L'espace professionnel sert à exercer : consultations, rendez-vous, tournées.

---

### Page 4 — Les règles communes et objets métier

**6 règles :**
1. Un cheval = une fiche unique. Pas de doublon.
2. Le dossier de santé appartient au cheval, pas au praticien.
3. Les droits dépendent du rôle auprès du cheval.
4. Le professionnel enregistre son propre travail.
5. Le partage est à l'initiative du référent.
6. Aucune action silencieuse : l'utilisateur sait ce qui se passe.

**7 objets à distinguer :**
1. Compte utilisateur
2. Fiche animal (cheval)
3. Dossier de santé (ensemble des CR)
4. Compte-rendu (consultation)
5. Rendez-vous
6. Structure
7. Rappel / notification

---

### Page 5 — Qui peut faire quoi (matrice des droits)

| Action | Propriétaire | Réf. principal | Réf. secondaire | PRO intervenant | Tiers autorisé |
|--------|:---:|:---:|:---:|:---:|:---:|
| Créer la fiche | ✓ | ✓ | — | ✓ | — |
| Désigner un référent | — | ✓ | — | — | — |
| Modifier la fiche | — | ✓ | selon droits | — | — |
| Voir le dossier complet | — | ✓ | selon droits | — | — |
| Rédiger un CR | — | — | — | ✓ | — |
| Valider un CR | — | — | — | ✓ (auteur) | — |
| Partager un CR | — | ✓ | selon droits | ✓ (son propre) | — |
| Organiser un RDV | — | ✓ | selon droits | ✓ | — |
| Consulter un partage reçu | — | — | — | — | ✓ (lecture) |

---

### Page 6 — Parcours : Trouver ou créer le bon cheval

- Recherche obligatoire avant création (contrôle doublons).
- Si le cheval existe : réutiliser la fiche. AniCare vérifie les droits avant d'ouvrir l'historique.
- Si le cheval n'existe pas : créer une fiche. Le créateur désigne un référent (schéma 2).
- Si doute : AniCare conserve le travail en attente. Une personne habilitée examine le doute (D3/D4).

---

### Page 7 — Désigner les référents à la création

**3 cas :**
1. Le créateur se désigne lui-même comme principal → choix explicite sur fiche nouvelle → activer après contrôles (D3/D8).
2. Le créateur propose un autre utilisateur → invitation → le candidat accepte ou refuse → accord = activer, refus ou silence = non actif.
3. Un principal existe déjà → transfert ou récupération (voir page 8).

**Référent secondaire :**
- Nommé par le principal.
- Suit les droits du principal.
- Une demande d'accès entrante peut compléter ce parcours mais n'est pas obligatoire avant chaque partage.

**Règle retenue :** Un seul principal actif reste une proposition. Refus ou silence du candidat = pas d'activation. Le secondaire suit les droits du principal.

---

### Page 8 — Changer de principal sans perdre le dossier

**Transfert :** Le principal propose un successeur. Pas de bascule unilatérale. Accord des personnes concernées ou procédure de récupération selon D8.

**Proposition :** Le professionnel peut proposer un nouveau principal ; il ne l'impose pas.

**Fiche sans principal :** Si aucun principal actif, les accès restent en lecture. La fiche n'est pas supprimée.

**Conflit :** Deux fiches semblent désigner le même cheval avec deux principaux → contrôle et résolution du conflit ; aucune fusion automatique des historiques et permissions.

**Retenu D8 :** Le créateur peut se désigner explicitement sans écraser un principal existant. Une proposition à autrui n'est pas une acceptation.

---

### Page 9 — Réaliser une consultation professionnelle

**6 étapes :**
1. Choisir le cheval
2. Accéder à l'historique (si autorisé) ou continuer sans
3. Rédiger et sauvegarder
4. Garder en brouillon ou valider
5. (Optionnel) Partager le CR validé
6. Le CR validé déclenche les règles D1/D4/D5

**Contenu d'une consultation :**
- Date, motif, observations, actes réalisés, prescriptions, suivi recommandé
- Documents joints (photos, radios, etc.)
- Le brouillon reste privé, non partagé, ne crée aucun rappel

---

### Page 10 — Conserver une information fiable dans le temps

**États d'un CR :**
- **Brouillon** : privé, non partagé, pas de rappel. Date éventuelle conservée.
- **Validé** : rattaché au dossier. Visible selon D1. Partageable. Déclenche rappels si échéance.
- **Correction** : provenance conservée. Version/mise à jour selon D7. Pas de doublon.
- **Mauvais cheval** : erreur de rattachement. Détacher et re-rattacher selon les droits.
- **Retrait d'accès** : le CR reste dans le dossier. Retirer un droit ne supprime pas le contenu.
- **Archivage** : accès de l'auteur après fin du suivi. Durées de conservation selon D7.

**Socle D7 :** Provenance conservée ; un tiers ne réécrit pas le CR d'un autre ; retirer un droit ne signifie pas supprimer une donnée.

---

### Page 11 — Partager à l'initiative du référent

**Le parcours principal (6 étapes) :**
1. **Choisir** (principal ou secondaire habilité) : sélectionner une fiche ou les contenus à partager.
2. **Désigner** (le même référent) : choisir la personne destinataire : particulier, professionnel ou contact d'une structure.
3. **Vérifier** (AniCare) : vérifier le droit de partage sur chaque contenu et le périmètre autorisé.
4. **Confirmer** (le référent) : confirmer la sélection et les conditions retenues.
5. **Donner accès** (AniCare) : activer ou envoyer selon le mécanisme choisi ; tracer l'opération.
6. **Consulter** (le destinataire) : lire seulement ce qui lui a été accordé. Aucun rôle de référent n'est créé.

**Deux usages à ne pas confondre :**
- **Partager des documents** : envoyer une sélection précise. Le destinataire ne découvre pas le reste du dossier.
- **Partager la fiche / le dossier** : accorder un périmètre de consultation dans le temps. Il faut décider si les futurs contenus entrent dans ce périmètre ; ce n'est pas automatique par le seul mot « fiche ».

**Et le professionnel ?**
Le professionnel dispose d'un circuit pour transmettre son propre travail, pas pour partager librement les comptes rendus des autres. Toute annexe provenant d'un tiers doit être contrôlée séparément.

**Retenu :** Le secondaire suit les droits du principal. Une demande d'accès entrante peut compléter ce parcours, mais n'est pas obligatoire avant chaque partage.

**Proposition :** lecture seule par défaut, sans droit de repartage implicite ; réglages avancés hors du parcours courant.

**À arbitrer D1/D5 :** accès initial au CR et éventuel accord du référent avant sa transmission par le professionnel. D2 : contenus futurs et étendue des délégations.

---

### Page 12 — Invitations, accès externes et fin des droits

**Les états à distinguer :**
- **Préparé** : le contenu et le destinataire sont choisis ; rien n'a encore été transmis.
- **Invitation en attente** : une réponse ou une vérification manque. L'invitation seule n'active pas une nomination ou un accès qui exige cette étape.
- **Actif** : les conditions d'accès retenues sont réunies. Chaque ouverture reste contrôlée.
- **Refusé / annulé** : aucun nouvel accès issu de cette demande.
- **Expiré / révoqué** : l'accès concerné est fermé ; les autres autorisations indépendantes sont examinées séparément.

**Envoyé, reçu et lu décrivent l'acheminement ou l'usage, pas les droits.** Un échec d'envoi doit être visible et ne doit pas produire une seconde autorisation lors d'une relance.

**Destinataire sans compte :** Le besoin est de pouvoir partager avec une personne externe sans la nommer référente. Le mécanisme reste ouvert : création de compte, lien protégé avec vérification, ou autre parcours retenu en D5. Ne pas confondre l'adresse d'envoi et une identité vérifiée.

**Retirer un droit :** La fermeture doit s'appliquer aussi aux accès directs aux documents et aux futurs envois dépendants. Si le droit d'un secondaire est retiré, le devenir des partages qu'il a émis dépend de la règle de dépendance choisie en D2/D5.

Le retrait d'un accès ne rappelle pas un PDF déjà téléchargé ou transmis. Il ne supprime pas non plus automatiquement le contenu original.

**À arbitrer D5 :** compte ou lien, vérification du destinataire, durée, téléchargement, version figée ou courante, personnes pouvant révoquer et sort des liens après transfert de principal. D2 : retrait en cascade et conservation des droits indépendants.

---

### Page 13 — Une structure utile, même sans gérant connecté

**Besoin exprimé :** pouvoir organiser les interventions et les tournées sans attendre que les gérants adoptent AniCare.

**Fonctionnement simple proposé :**
1. Le particulier ou le professionnel recherche la structure par son nom et son adresse.
2. S'il la retrouve, il réutilise sa fiche.
3. Sinon, il renseigne un nom et une adresse exploitable. Les coordonnées du gérant sont facultatives.
4. AniCare signale les correspondances possibles avant d'ajouter une nouvelle structure.
5. Un gérant pourra éventuellement rejoindre la structure plus tard, sans recevoir les dossiers de santé par cette seule démarche.

**Trois liens différents :**
- **Cheval - lieu de vie** : savoir où le cheval vit habituellement ; utile à l'organisation.
- **Utilisateur - structure** : représenter ou administrer la structure selon une habilitation définie.
- **Utilisateur - dossier de santé** : consulter ou agir selon une autorisation personnelle. Ce lien n'est pas déduit des deux précédents.

**Lieu de vie et lieu du rendez-vous :** Un cheval peut vivre dans une écurie et être vu ailleurs. La tournée utilise l'adresse réelle du rendez-vous, pas obligatoirement le lieu de vie. Un changement de rendez-vous ne déménage pas automatiquement le cheval dans son dossier.

**Arrivée, départ et correction d'adresse :** Le lien au lieu peut être daté en arrière-plan sans imposer une saisie lourde. Qui confirme un changement durable de lieu, qui corrige une fiche commune et qui peut consulter les présences restent à décider. Le professionnel ne voit pas tous les chevaux d'une structure parce qu'il en connaît l'adresse.

**Proposition pilote :** faire de la structure un lieu réutilisable avant d'en faire un espace collaboratif complet. Une structure sans utilisateur associé ne peut pas accepter une invitation ou un partage à la place d'une personne.

**À arbitrer D6 :** dédoublonnage, édition des adresses, confirmation des présences, liens simultanés, gestion par un gérant et effet des départs.

---

### Page 14 — Rendez-vous et tournées

**Le rendez-vous organise une intervention :** Il relie un professionnel, un client ou contact, un ou plusieurs animaux selon le besoin, une date ou période, un lieu réel et un état. La consultation réalisée reste un objet distinct.

**États proposés :**
- **À prévoir** : besoin d'intervention identifié. Décider si une date peut être absente.
- **À confirmer** : un créneau est envisagé ; préciser qui doit confirmer.
- **Confirmé** : le créneau est retenu. Cela ne donne pas de droit sur la santé.
- **Annulé / reporté** : l'organisation change ; mettre à jour les rappels et prévenir les destinataires concernés.

**La tournée regroupe des déplacements :** Le professionnel sélectionne des rendez-vous selon leur date et leurs adresses. Il les regroupe par lieu ou secteur et organise leur ordre en tenant compte des durées et des déplacements.

**Proposition pour le pilote :** regroupement simple et ordre ajustable manuellement. Le calcul automatique d'itinéraire, les frais de déplacement et la récurrence sont des évolutions à arbitrer, pas des exigences déduites des visuels.

**Organisation privée et annonce publique :** Une tournée de travail contient des rendez-vous identifiés. Annoncer un passage dans un secteur est une fonction différente. Si elle est retenue, elle ne doit pas publier les noms des clients, leurs chevaux, leurs adresses précises ou les motifs de consultation.

Un contact de structure peut aider à organiser les rendez-vous sans devenir référent ni recevoir de données médicales.

**À arbitrer :** Périmètre pilote : états, confirmation, annulation, plusieurs animaux, destinataires des messages et besoin réel d'optimisation. Une réservation en ligne ou une annonce de tournée n'est pas actée par sa présence passée dans une maquette.

---

### Page 15 — Rappels et notifications sans effets cachés

**Un rappel, un rendez-vous et une notification :**
- **Rappel** : une échéance de suivi à venir, avec son origine et son destinataire.
- **Rendez-vous** : un créneau organisé pour une intervention.
- **Notification** : un message signalant une information ou une action à accomplir.

Un rappel ne prend pas automatiquement rendez-vous. Un rendez-vous passé ne prouve pas qu'une consultation a été réalisée.

**Déclencheurs et traitements proposés :**
- **Brouillon de consultation** : conserver la date éventuelle dans le brouillon ; ne pas créer de rappel ni d'envoi.
- **CR validé et correctement rattaché** : créer le rappel prévu si l'échéance et les destinataires sont autorisés et confirmés.
- **Rappel saisi directement** : identifier son auteur et vérifier son droit. Distinguer une déclaration du référent d'une recommandation du professionnel.
- **Correction ou annulation** : mettre à jour ou annuler les effets liés sans créer de doublon ; garder une trace.
- **Invitation / partage** : informer la bonne personne. Le message ne vaut pas acceptation ni droit supplémentaire.
- **Échec d'envoi** : signaler l'échec, permettre une relance et recontrôler les droits avant l'envoi.

**Les préférences ne créent pas d'autorisation :** Le choix d'un canal, d'une fréquence ou d'un destinataire proposé doit respecter les droits existants. Les messages ne doivent pas exposer d'informations que leur destinataire ne peut pas consulter.

**Exemple :** Élodie valide une consultation avec un suivi dans six semaines. AniCare ne crée qu'une échéance liée à ce CR. Si elle corrige cette échéance, la précédente est mise à jour, pas ajoutée une seconde fois.

**À arbitrer D4 :** qui crée et reçoit les rappels, canaux, récurrence et activation. D7 : effets d'une correction ou d'un retrait. Aucun délai ni canal obligatoire n'est fixé par ce dossier.

---

### Page 16 — Les informations à prévoir

**Ce dictionnaire est une base de cadrage. Il ne rend pas tous les champs obligatoires et n'impose pas une table par ligne.**

| Élément | Informations métier utiles |
|---------|---------------------------|
| Compte et profil | Connexion, personne, espaces et état. Pour le professionnel : métier, identité d'exercice, coordonnées et éléments à publier. |
| Contact / client | Coordonnées utiles, éventuel compte lié et notes internes privées. |
| Animal | Nom, espèce, identité disponible, SIRE ou puce si connus, origine et état de vérification. |
| Lien avec l'animal | Personne, rôle auprès de ce cheval, désignation comme référent, état et dates. |
| Autorisation / partage | Émetteur, bénéficiaire, animal, actions, contenus, durée, état et dépendance à un autre droit. |
| Contenu | Type, auteur / source, personne ayant ajouté, dates, animal, état, version, visibilité et documents. |
| Structure / présence | Nom, adresse, contact facultatif ; lien du cheval au lieu et dates. Habilitations des membres séparées. |
| Organisation / suivi | Rendez-vous, adresse réelle, participants, état ; tournée de rendez-vous ; rappel et sa source. |
| Trace | Qui a créé, accepté, partagé, corrigé, retiré ou transféré ; quand et sur quel élément. |

**Paramétrer sans changer les règles :** Les modèles de CR, durées d'intervention, préférences de rappel ou coordonnées d'affichage facilitent la saisie. Ils ne doivent pas modifier les autorisations, publier un brouillon ou transformer une recommandation préremplie en décision non vérifiée.

**Proposition :** Demander seulement les données indispensables à l'action en cours. Un contact incomplet peut être utile pour préparer le travail ; une transmission exige ensuite un destinataire exploitable et vérifié selon D5.

**À arbitrer D3/D4 :** minimum par création et validation. Les rubriques médicales détaillées, l'annuaire public, la facturation et les options avancées restent des cadrages distincts.

---

### Page 17 — Construire une première version testable

**Situation déclarée par l'équipe au 29 septembre 2026 :** Version utilisable en interne, vue seulement par l'équipe. Fonctions déclarées exécutables : créer un compte et se connecter, créer un client et un cheval, créer et enregistrer un compte rendu. Lucas et Élodie prévoient un engagement à temps principal ; Pierre intervient à temps partiel. L'objectif est de commencer les tests en conditions réelles **avant fin 2026**.

**Pilote commercial et terrain :** Occitanie en priorité. Recrutement envisagé par les contacts professionnels d'Élodie, la prospection directe, les écoles et organismes de formation. Aucun groupe de pilotes n'est encore sélectionné. Principe retenu : test gratuit contre retours réguliers.

**4 lots pilote :**
1. **Compte et identité** : une personne utilise ses espaces sans doublon ; elle retrouve le bon cheval et comprend son lien avec lui.
2. **Consultation et transmission** : le professionnel sauvegarde, valide et transmet selon les règles choisies ; le bon destinataire retrouve le bon CR.
3. **Partage et droits** : principal et secondaire disposent de pouvoirs maîtrisés ; refus, attente et révocation fonctionnent.
4. **Organisation terrain** : structures sans gérant, rendez-vous, rappels et regroupement simple en tournée soutiennent le travail réel.

**À garder hors du premier lot, sauf besoin démontré :** Gestion avancée des équipes de structure, optimisation automatique complexe, réservation publique, facturation complète, pédagogie Campus dédiée et extension fonctionnelle à d'autres espèces. Les préparer dans la réflexion ne signifie pas les développer avant le pilote.

**Cadre entrepreneurial distinct :** Lucas et Élodie sont les associés envisagés ; société non créée et apport non arrêté. Accord avec Pierre, propriété et usage du code, financement et incubation restent à formaliser séparément.

**Avant les tests réels :** Choisir les règles bloquantes D1 à D8, tester les accès et prévoir assistance, sauvegarde/restauration et traitement d'incident. Les conditions d'utilisation, la protection des données et l'usage du code doivent être cadrés avec les interlocuteurs compétents.

---

### Page 18 — D1 à D4 : Les décisions de base

**D1 — Visibilité initiale des contenus**
- Retenu : pas d'ouverture à tous les utilisateurs liés au cheval. Un brouillon n'est pas diffusé ; les vues dérivées respectent le droit sur la source.
- À décider : après validation et rattachement d'un CR, auteur seul jusqu'au partage, ou auteur et référents habilités ? Même règle pour une observation et un document importé ? Quelles exceptions et quels futurs contenus dans un accès au dossier ?
- Bloque : première mise à disposition d'un CR et affichage de l'historique partagé.

**D2 — Droits, délégation et durée**
- Retenu : principal pilote des accès ; partage du secondaire limité aux droits reçus ; aucun droit par simple statut, rôle déclaré ou appartenance à une structure.
- À décider : droits initiaux, regroupements simples de permissions, capacités délégables et éventuelle sous-délégation ; durée ; invitation et acceptation ; retrait en cascade ; droits indépendants ; personnes pouvant consulter le journal des accès.
- Proposition : peu de choix lisibles pour l'utilisateur, avec des permissions explicites derrière. Ne pas imposer les anciens « quatre profils » comme choix validé.

**D3 — Identité, création et doublons**
- Retenu : recherche avant création ; pas de doublon pour contourner un refus ; identité et santé séparées.
- À décider : créateurs autorisés selon le contexte, minimum de données, SIRE/puce manquant ou contradictoire, visibilité des résultats, travail provisoire, rapprochement contact-compte, contrôle et fusion des animaux. Qui vérifie ? Que deviennent les liens, contenus et droits ?

**D4 — Consultation en attente et effets**
- Retenu : le professionnel peut saisir sans accès à l'historique ; c'est lui qui valide son CR ; pas d'effet partagé issu d'un brouillon.
- À décider : minimum validable ; CR validé mais non rattaché ; transmission avant identité confirmée ; validation du rattachement ; déclenchement des vues et rappels ; canaux et destinataires ; traitement des échecs et répétitions sans doublon.

**Priorité :** D1 à D4 doivent être suffisamment tranchés avant la recette du premier parcours complet. Les règles propres à chaque autre fonction doivent l'être avant son ouverture.

---

### Page 19 — D5 à D8 : Le partage et la continuité

**D5 — Partage et accès externe**
- Retenu : le parcours du dossier part du référent ; le secondaire suit ses droits ; le professionnel ne partage pas librement le travail des autres ; recevoir ne donne pas la gestion.
- Point à résoudre explicitement : un ancien cadrage prévoyait l'accord du référent avant la transmission professionnelle, alors que H2 prévoyait une autonomie sur le propre CR. Ne pas choisir implicitement l'une des deux règles. Décider aussi si recevoir un CR le rend visible dans le dossier et à qui, avec D1.
- Autres choix : compte ou lien protégé, identité du destinataire, durée, téléchargement, version figée/courante, annexes tierces, personnes pouvant révoquer, corrections et sort des liens après retrait de l'émetteur ou changement de principal.

**D6 — Structures et présences**
- Besoin retenu : les tournées ne doivent pas dépendre de l'inscription d'un gérant ; un lien de lieu n'ouvre pas la santé.
- Proposition : fiche-lieu réutilisable sans gérant obligatoire.
- À décider : création et doublons, modification d'une adresse partagée, lieu principal et autres liens, validation d'arrivée/départ, visibilité des présences, habilitation des membres et retrait de leurs accès dépendants.

**D7 — Corrections et conservation**
- Socle : provenance conservée ; un tiers ne réécrit pas le CR d'un autre ; retirer un droit ne signifie pas supprimer une donnée.
- À décider : états par type de contenu, valideurs et correcteurs, versions et motifs, archivage/retrait/suppression, accès de l'auteur après fin du suivi, durées de conservation y compris journaux et sauvegardes, conséquences sur rappels et partages. Faire vérifier la politique avant exploitation ; aucune durée n'est inventée ici.

**D8 — Principal, transfert et récupération**
- Retenu : le créateur peut se désigner explicitement sans écraser un principal existant. Une proposition à autrui n'est pas une acceptation.
- À décider : principal unique, contrôles en création et sur fiche sans principal, preuves selon les cas, invitations et relances, accord des parties en transfert, arbitre et contestation en litige, droits résiduels et transferts concurrents. Le professionnel peut proposer ; il n'impose pas le successeur.

**Pour noter un accord :** ID ; règle retenue ; exceptions ; exemple accepté ; exemple refusé ; validé par ; date. Un sujet laissé ouvert ne devient pas une règle pour Pierre.

---

### Page 20 — Huit situations pour vérifier le socle (T01-T08)

**Recette métier proposée : jouer la situation avec Élodie, puis en déduire les tests avec Pierre.**

| Cas | Action / situation | Résultat attendu |
|-----|-------------------|------------------|
| T01 | Élodie choisit « les deux » à l'inscription. | Un compte et deux espaces ; aucun pouvoir supplémentaire sur les chevaux de ses clients. |
| T02 | Lucas crée une fiche réellement nouvelle et se désigne explicitement principal. | Désignation possible selon les contrôles D3/D8 ; ne pas l'activer uniquement parce qu'il a créé la fiche. |
| T03 | Élodie crée le cheval d'un client et propose ce client comme principal. | Consultation préservée ; Élodie n'est pas nommée par défaut ; la proposition au client attend son acceptation. |
| T04 | Un propriétaire retrouve une fiche dont un autre principal est actif. | Réutilisation de la fiche ; pas de remplacement par une case ni de doublon pour prendre la gestion. |
| T05 | Un secondaire peut lire, mais n'a pas le droit de partager. | Lecture autorisée ; partage refusé sans envoi. Après délégation valable, partage limité au périmètre reçu. |
| T06 | Le principal partage un seul CR avec sa fille. | Accès à ce CR selon D5 ; pas au reste du dossier ; aucune nomination comme référente. |
| T07 | Un professionnel rédige sans accès à l'historique. | Son travail peut être enregistré ; le passé reste inaccessible. Validation/rattachement suivent D4. |
| T08 | Un brouillon contient une date de suivi et un destinataire. | Pas de partage, de publication ni de rappel partagé ou déclenché par le brouillon. |

**Ce que l'on observe :** Pour chaque cas : qui agit, ce qu'il voit, ce qu'il peut faire, l'état obtenu et les messages réellement envoyés. Vérifier aussi les documents ouverts par leur lien direct, pas seulement les boutons de l'interface.

**Important :** Lorsqu'un résultat dépend d'un arbitrage, terminer d'abord D1 à D8 puis fixer l'attendu précis. Un test proposé n'est ni exécuté ni réputé réussi par sa présence dans ce dossier.

---

### Page 21 — Huit situations de changement ou d'échec (T09-T16)

| Cas | Action / situation | Résultat attendu / décision liée |
|-----|-------------------|--------------------------------|
| T09 | Élodie organise une tournée dans une écurie sans gérant inscrit. | Utilisation du lieu et des rendez-vous possible selon D6 ; aucun compte gérant ni droit de santé créé automatiquement. |
| T10 | Un cheval est vu ailleurs que dans son lieu de vie. | La tournée utilise l'adresse du rendez-vous ; le lieu de vie ne change pas sans action distincte. |
| T11 | Une invitation au rôle de principal reste sans réponse. | Aucun transfert par silence. Si un principal existe, il reste en place selon D8. |
| T12 | Un accès expire ou est retiré. | Le lien concerné et l'accès direct sont fermés ; les droits indépendants et sous-partages sont traités selon D2/D5. |
| T13 | Le même enregistrement ou envoi est relancé après un échec. | Pas de second CR, rappel ou autorisation involontaire ; l'état d'envoi reste compréhensible. |
| T14 | L'auteur corrige un CR avec un rappel. | Provenance/version et mise à jour du rappel selon D7 ; pas de doublon. Le sort du partage est déterminé par D5. |
| T15 | Deux fiches semblent désigner le même cheval, avec deux principaux. | Contrôle et résolution du conflit ; aucune fusion automatique des historiques et permissions. |
| T16 | Un nouveau principal est proposé par un professionnel. | Pas de bascule unilatérale. Accord des personnes concernées ou procédure de récupération selon D8. |

**Autres contrôles avant le pilote :**
- Les notes internes du professionnel ne sont pas présentes dans les documents transmis.
- Un contact ajouté au répertoire ne peut pas se connecter sans véritable compte activé.
- Un cheval présent dans une structure n'est pas visible à tous ses membres par défaut.
- Aucun titre, notification ou pièce jointe ne révèle un contenu sans autorisation.
- Un retrait d'accès n'est jamais présenté comme capable de récupérer un fichier déjà téléchargé.

**Recette :** Pour les actions sensibles, tester trois issues : acceptée, refusée, en attente. Vérifier également le retour arrière, les corrections et les reprises après erreur.

---

### Page 22 — La consigne pour Pierre

**Une tranche fonctionnelle à la fois.** Pour chaque lot, partir du code existant, des décisions retenues et des cas terrain. Ce dossier n'impose ni une organisation d'écrans ni une réécriture de l'application.

**À fournir dans chaque tâche :**
| À fournir | Question à laquelle répondre |
|-----------|----------------------------|
| Objectif et acteur | Qui doit réussir quelle action, pour quel cheval ? |
| Règles et prérequis | Quels droits, quelle identité et quels arbitrages D1 à D8 sont nécessaires ? |
| États et transitions | Que se passe-t-il en cas de succès, refus, attente, annulation ou erreur ? |
| Visibilité et effets | Qui voit quoi ? Quels contenus, rappels ou envois sont créés ou modifiés ? |
| Trace et correction | Qui a agi ? Comment corriger sans perdre l'origine ni créer de doublon ? |
| Recette | Quels cas T01 à T16 et quels tests complémentaires doivent réussir ? |

**Répartition de travail proposée :** Lucas consigne les choix produit et le périmètre. Élodie éprouve les situations et les formulaires sur le terrain. Pierre vérifie l'existant, traduit les règles, estime les adaptations et met en place les tests. Une question ouverte revient à l'équipe ; elle n'est pas tranchée implicitement par le code ou par une IA.

**Origine et priorité des informations :** Sources : dossier métier H2 et registre D1 à D8 du 15 septembre 2026 ; dossier simplifié annoté ; décisions exprimées dans les échanges jusqu'au 29 septembre 2026. Les maquettes ont été exclues comme source de règles.

Cette V3 remplace, pour la lecture métier, les formulations contradictoires : comptes séparés, confusion statut/rôle/référent, création sans choix de désignation, partage principalement demandé par un tiers et dépendance obligatoire au gérant. Elle ne clôt pas les arbitrages signalés.

**Prochaine étape :** Trancher les points bloquants du premier lot, jouer les cas correspondants avec Élodie, puis transmettre à Pierre les règles et résultats attendus. Le logiciel sera vérifié par cette recette, pas par la seule lecture du dossier.

---

### Pages 23-28 — Logigrammes simples (6 schémas)

**Logigramme 1 — Trouver ou créer le cheval**
Particulier/Professionnel → Rechercher le cheval → Résultat ?
- Existe → Utilisateur réutilise la fiche → AniCare vérifie les droits avant l'historique
- Non retrouvé → Créateur crée une fiche → Créateur désigne un référent (schéma 2)
- Doute → AniCare conserve le travail en attente → Personne habilitée examine le doute (D3/D4)

Règle : Non retrouvé = aucun signal après les contrôles retenus. Cela ne prouve pas l'absence de fiche. Identité et traitement de l'attente : D3/D4.

**Logigramme 2 — Désigner le référent principal**
Créateur → Choisir un référent → Un principal existe déjà ?
- Oui → Utilisateur : transfert ou récupération → AniCare : aucun remplacement automatique
- Non → Le créateur se désigne lui-même ?
  - Oui → Choix explicite sur fiche nouvelle → AniCare : activer après contrôles*
  - Non → Candidat : accepter ou refuser l'invitation → Accord = activer* / Sinon = non actif

*Contrôles à fixer en D3/D8. Un seul principal actif reste une proposition. Refus ou silence du candidat = pas d'activation. Le secondaire suit les droits du principal.

**Logigramme 3 — Rédiger une consultation**
Professionnel → Choisir le cheval → Accès à l'historique ?
- Oui → Lire le périmètre autorisé
- Non → Continuer sans historique
→ Rédiger et sauvegarder → Prêt à valider ?
- Non → Garder le brouillon privé
- Oui → Valider le CR

Après validation, AniCare applique D1/D4/D5 : rattachement, visibilité, partage. Le brouillon ne diffuse rien et ne crée aucun rappel partagé.

**Logigramme 4 — Partager un contenu**
Référent → Choisir les contenus et le destinataire → Droit de partager ces contenus ?
- Non → AniCare refuse le partage, aucun envoi
- Oui → Référent confirme le partage* → AniCare active l'accès autorisé → Destinataire lit la sélection seulement

*Mode d'accès, destinataire, durée et repartage : D2/D5. Le professionnel transmet son propre travail selon le circuit D5 ; il ne partage pas librement celui des autres.

**Logigramme 5 — Utiliser une structure**
Utilisateur → Rechercher nom + adresse → Structure retrouvée ?
- Oui → Réutiliser le lieu
- Non → Créer un lieu* (gérant facultatif)
→ Choisir ce lieu pour le rendez-vous → AniCare utilise l'adresse réelle du RDV

Lieu de vie et lieu du rendez-vous restent distincts. Aucun accès santé par le simple lieu. *Création, doublons et modification d'une fiche commune : D6.

**Logigramme 6 — Organiser une tournée**
Professionnel → Sélectionner les rendez-vous → Adresses et créneaux exploitables ?
- Non → Compléter les informations (boucle)
- Oui → Regrouper par secteur → Ajuster l'ordre et les temps → Enregistrer la tournée

Version simple proposée : organisation manuelle. Le calcul automatique d'itinéraire et l'annonce publique d'un passage sont deux fonctions supplémentaires à arbitrer.
