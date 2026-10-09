<?php

namespace App\DataFixtures;

use App\Entity\Allergy;
use App\Entity\Animal;
use App\Entity\AnimalShare;
use App\Entity\Appointment;
use App\Entity\HealthBookEntry;
use App\Entity\Message;
use App\Entity\Reminder;
use App\Entity\Structure;
use App\Entity\StructureMembership;
use App\Entity\User;
use App\Entity\WeightRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $users = $this->createUsers($manager);
        $structures = $this->createStructures($manager, $users);
        $this->createMemberships($manager, $users, $structures);
        $manager->flush();

        $animals = $this->createAnimals($manager, $users, $structures);
        $manager->flush();

        // Fix slugs after IDs are assigned
        foreach ($animals as $a) {
            $a->generateSlug();
        }
        $manager->flush();

        $this->createHealthBookEntries($manager, $animals, $users);
        $this->createAppointments($manager, $users, $animals);
        $this->createAllergies($manager, $animals);
        $this->createWeightRecords($manager, $animals);
        $this->createReminders($manager, $animals, $users);
        $this->createMessages($manager, $users, $animals);
        $this->createAnimalShares($manager, $animals);
        $manager->flush();
    }

    private function createUsers(ObjectManager $manager): array
    {
        $password = 'Test1234!';
        $data = [
            // Structures (gérants)
            ['email' => 'structure@test.com', 'first' => 'Laurent', 'last' => 'Mercier', 'type' => 'STRUCTURE', 'roles' => ['ROLE_STRUCTURE'], 'city' => 'Marseille', 'postal' => '13008', 'phone' => '04 91 22 33 44'],
            ['email' => 'ecurie.soleil@test.com', 'first' => 'Nathalie', 'last' => 'Blanc', 'type' => 'STRUCTURE', 'roles' => ['ROLE_STRUCTURE'], 'city' => 'Aix-en-Provence', 'postal' => '13100', 'phone' => '04 42 11 22 33'],
            ['email' => 'clinique.equine@test.com', 'first' => 'Dr. François', 'last' => 'Durand', 'type' => 'STRUCTURE', 'roles' => ['ROLE_STRUCTURE'], 'city' => 'Salon-de-Provence', 'postal' => '13300', 'phone' => '04 90 55 66 77'],
            // PRO
            ['email' => 'pierre.renault83@gmail.com', 'first' => 'Pierre', 'last' => 'Renault', 'type' => 'PRO', 'roles' => ['ROLE_PRO'], 'city' => 'Marseille', 'postal' => '13008', 'phone' => '06 12 34 56 78', 'specialty' => 'Ostéopathe équin', 'experience' => 8, 'duration' => 60, 'visible' => true, 'departments' => ['13', '83', '84']],
            ['email' => 'sophie.martin@pro.com', 'first' => 'Sophie', 'last' => 'Martin', 'type' => 'PRO', 'roles' => ['ROLE_PRO'], 'city' => 'Aix-en-Provence', 'postal' => '13100', 'phone' => '06 98 76 54 32', 'specialty' => 'Dentiste équin', 'experience' => 12, 'duration' => 45, 'visible' => true, 'departments' => ['13', '84']],
            ['email' => 'julien.roux@pro.com', 'first' => 'Julien', 'last' => 'Roux', 'type' => 'PRO', 'roles' => ['ROLE_PRO'], 'city' => 'Toulon', 'postal' => '83000', 'phone' => '06 55 44 33 22', 'specialty' => 'Maréchal-ferrant', 'experience' => 15, 'duration' => 90, 'visible' => true, 'departments' => ['83', '13']],
            ['email' => 'claire.morel@pro.com', 'first' => 'Claire', 'last' => 'Morel', 'type' => 'PRO', 'roles' => ['ROLE_PRO'], 'city' => 'Avignon', 'postal' => '84000', 'phone' => '06 77 88 99 00', 'specialty' => 'Vétérinaire équin', 'experience' => 20, 'duration' => 30, 'visible' => true, 'departments' => ['84', '13', '30']],
            // OWNER
            ['email' => 'elodie.q@hotmail.fr', 'first' => 'Élodie', 'last' => 'Quéré', 'type' => 'OWNER', 'roles' => ['ROLE_USER'], 'city' => 'Cassis', 'postal' => '13260', 'phone' => '06 11 22 33 44'],
            ['email' => 'marc.dupont@owner.com', 'first' => 'Marc', 'last' => 'Dupont', 'type' => 'OWNER', 'roles' => ['ROLE_USER'], 'city' => 'Marseille', 'postal' => '13009', 'phone' => '06 22 33 44 55'],
            ['email' => 'camille.bernard@owner.com', 'first' => 'Camille', 'last' => 'Bernard', 'type' => 'OWNER', 'roles' => ['ROLE_USER'], 'city' => 'Aubagne', 'postal' => '13400', 'phone' => '06 33 44 55 66'],
            ['email' => 'lucas.petit@owner.com', 'first' => 'Lucas', 'last' => 'Petit', 'type' => 'OWNER', 'roles' => ['ROLE_USER'], 'city' => 'La Ciotat', 'postal' => '13600', 'phone' => '06 44 55 66 77'],
            ['email' => 'emilie.garcia@owner.com', 'first' => 'Émilie', 'last' => 'Garcia', 'type' => 'OWNER', 'roles' => ['ROLE_USER'], 'city' => 'Aix-en-Provence', 'postal' => '13100', 'phone' => '06 55 66 77 88'],
            ['email' => 'thomas.leroy@owner.com', 'first' => 'Thomas', 'last' => 'Leroy', 'type' => 'OWNER', 'roles' => ['ROLE_USER'], 'city' => 'Salon-de-Provence', 'postal' => '13300', 'phone' => '06 66 77 88 99'],
            ['email' => 'julie.moreau@owner.com', 'first' => 'Julie', 'last' => 'Moreau', 'type' => 'OWNER', 'roles' => ['ROLE_USER'], 'city' => 'Martigues', 'postal' => '13500', 'phone' => '06 77 88 99 11'],
            ['email' => 'antoine.faure@owner.com', 'first' => 'Antoine', 'last' => 'Faure', 'type' => 'OWNER', 'roles' => ['ROLE_USER'], 'city' => 'Istres', 'postal' => '13800', 'phone' => '06 88 99 00 22'],
            // Admin
            ['email' => 'admin@anicare.fr', 'first' => 'Marie', 'last' => 'Dupont', 'type' => 'OWNER', 'roles' => ['ROLE_ADMIN'], 'city' => 'Paris', 'postal' => '75001', 'phone' => '01 23 45 67 89'],
        ];

        $users = [];
        foreach ($data as $d) {
            $u = new User();
            $u->setEmail($d['email']);
            $u->setPassword($this->hasher->hashPassword($u, $password));
            $u->setFirstName($d['first']);
            $u->setLastName($d['last']);
            $u->setAccountType($d['type']);
            $u->setRoles($d['roles']);
            $u->setCity($d['city'] ?? null);
            $u->setPostalCode($d['postal'] ?? null);
            $u->setPhone($d['phone'] ?? null);

            if ($d['type'] === 'PRO') {
                $u->setSpecialty($d['specialty'] ?? null);
                $u->setExperienceYears($d['experience'] ?? null);
                $u->setDefaultConsultationDuration($d['duration'] ?? null);
                $u->setDirectoryVisible($d['visible'] ?? false);
                $u->setInterventionDepartments($d['departments'] ?? null);
            }

            $manager->persist($u);
            $users[$d['email']] = $u;
        }

        return $users;
    }

    private function createStructures(ObjectManager $manager, array $users): array
    {
        $data = [
            [
                'name' => 'El Ranch', 'type' => 'Centre équestre',
                'street' => '8 Boulevard des Chênes', 'postal' => '13008', 'city' => 'Marseille', 'country' => 'France',
                'phone' => '04 91 22 33 44', 'email' => 'contact@elranch.fr', 'siret' => '12345678901234',
                'description' => 'Centre équestre familial au cœur de Marseille. Cours, pensions et randonnées.',
                'createdBy' => 'structure@test.com',
            ],
            [
                'name' => 'Écurie du Soleil', 'type' => 'Écurie de propriétaires',
                'street' => '45 Chemin des Oliviers', 'postal' => '13100', 'city' => 'Aix-en-Provence', 'country' => 'France',
                'phone' => '04 42 11 22 33', 'email' => 'contact@ecuriedusoleil.fr', 'siret' => '98765432109876',
                'description' => 'Écurie de propriétaires avec paddocks individuels, carrière et manège couverts.',
                'createdBy' => 'ecurie.soleil@test.com',
            ],
            [
                'name' => 'Clinique Vétérinaire Équine de Provence', 'type' => 'Clinique vétérinaire',
                'street' => '12 Route de Pélissanne', 'postal' => '13300', 'city' => 'Salon-de-Provence', 'country' => 'France',
                'phone' => '04 90 55 66 77', 'email' => 'contact@clinique-equine-provence.fr', 'siret' => '55566677788899',
                'description' => 'Clinique spécialisée équine : imagerie, chirurgie, reproduction, hospitalisation 24h/24.',
                'createdBy' => 'clinique.equine@test.com',
            ],
        ];

        $structures = [];
        foreach ($data as $d) {
            $s = new Structure();
            $s->setName($d['name']);
            $s->setType($d['type']);
            $s->setStreet($d['street']);
            $s->setPostalCode($d['postal']);
            $s->setCity($d['city']);
            $s->setCountry($d['country']);
            $s->setPhone($d['phone']);
            $s->setEmail($d['email']);
            $s->setSiret($d['siret']);
            $s->setDescription($d['description']);
            $s->setCreatedBy($users[$d['createdBy']]);
            $manager->persist($s);
            $structures[$d['name']] = $s;
        }

        return $structures;
    }

    private function createMemberships(ObjectManager $manager, array $users, array $structures): void
    {
        $links = [
            // El Ranch: Laurent = gérant, Pierre et Sophie = pro, Élodie et Marc = owners
            ['structure@test.com', 'El Ranch', StructureMembership::ROLE_MANAGER],
            ['pierre.renault83@gmail.com', 'El Ranch', StructureMembership::ROLE_PRO],
            ['sophie.martin@pro.com', 'El Ranch', StructureMembership::ROLE_PRO],
            ['elodie.q@hotmail.fr', 'El Ranch', StructureMembership::ROLE_OWNER],
            ['marc.dupont@owner.com', 'El Ranch', StructureMembership::ROLE_OWNER],
            ['camille.bernard@owner.com', 'El Ranch', StructureMembership::ROLE_OWNER],
            // Écurie du Soleil: Nathalie = gérant, Julien = pro, Lucas et Émilie = owners
            ['ecurie.soleil@test.com', 'Écurie du Soleil', StructureMembership::ROLE_MANAGER],
            ['julien.roux@pro.com', 'Écurie du Soleil', StructureMembership::ROLE_PRO],
            ['lucas.petit@owner.com', 'Écurie du Soleil', StructureMembership::ROLE_OWNER],
            ['emilie.garcia@owner.com', 'Écurie du Soleil', StructureMembership::ROLE_OWNER],
            // Clinique: François = gérant, Claire = pro, Thomas = owner
            ['clinique.equine@test.com', 'Clinique Vétérinaire Équine de Provence', StructureMembership::ROLE_MANAGER],
            ['claire.morel@pro.com', 'Clinique Vétérinaire Équine de Provence', StructureMembership::ROLE_PRO],
            ['thomas.leroy@owner.com', 'Clinique Vétérinaire Équine de Provence', StructureMembership::ROLE_OWNER],
        ];

        foreach ($links as [$email, $structureName, $role]) {
            $m = new StructureMembership();
            $m->setUser($users[$email]);
            $m->setStructure($structures[$structureName]);
            $m->setRole($role);
            $manager->persist($m);
        }
    }

    private function createAnimals(ObjectManager $manager, array $users, array $structures): array
    {
        $breeds = ['Selle Français', 'Pur-sang Arabe', 'Anglo-Arabe', 'KWPN', 'Lusitanien', 'Frison', 'Connemara', 'Mérens', 'Trotteur Français', 'Haflinger'];
        $coats = ['Bai', 'Alezan', 'Gris', 'Noir', 'Isabelle', 'Palomino', 'Pie', 'Rouan', 'Bai brun', 'Crème'];

        $data = [
            // El Ranch horses
            ['name' => 'Eclipse', 'breed' => 'Selle Français', 'gender' => 'Hongre', 'coat' => 'Bai', 'birth' => '2018-03-15', 'weight' => 520, 'height' => 1.68, 'owner' => 'elodie.q@hotmail.fr', 'structure' => 'El Ranch', 'micro' => '250269801234567'],
            ['name' => 'Spirit', 'breed' => 'Anglo-Arabe', 'gender' => 'Étalon', 'coat' => 'Alezan', 'birth' => '2016-05-22', 'weight' => 490, 'height' => 1.62, 'owner' => 'elodie.q@hotmail.fr', 'structure' => 'El Ranch', 'micro' => '250269801234568'],
            ['name' => 'Luna', 'breed' => 'Lusitanien', 'gender' => 'Jument', 'coat' => 'Gris', 'birth' => '2019-07-10', 'weight' => 480, 'height' => 1.58, 'owner' => 'marc.dupont@owner.com', 'structure' => 'El Ranch', 'micro' => '250269801234569'],
            ['name' => 'Tango', 'breed' => 'KWPN', 'gender' => 'Hongre', 'coat' => 'Noir', 'birth' => '2017-01-08', 'weight' => 560, 'height' => 1.72, 'owner' => 'marc.dupont@owner.com', 'structure' => 'El Ranch', 'micro' => '250269801234570'],
            ['name' => 'Perle', 'breed' => 'Connemara', 'gender' => 'Jument', 'coat' => 'Crème', 'birth' => '2020-04-18', 'weight' => 390, 'height' => 1.45, 'owner' => 'camille.bernard@owner.com', 'structure' => 'El Ranch', 'micro' => '250269801234571'],
            // Écurie du Soleil
            ['name' => 'Gatsby', 'breed' => 'Pur-sang Arabe', 'gender' => 'Étalon', 'coat' => 'Alezan', 'birth' => '2015-09-30', 'weight' => 450, 'height' => 1.55, 'owner' => 'lucas.petit@owner.com', 'structure' => 'Écurie du Soleil', 'micro' => '250269801234572'],
            ['name' => 'Noisette', 'breed' => 'Mérens', 'gender' => 'Jument', 'coat' => 'Noir', 'birth' => '2019-11-25', 'weight' => 430, 'height' => 1.50, 'owner' => 'lucas.petit@owner.com', 'structure' => 'Écurie du Soleil', 'micro' => '250269801234573'],
            ['name' => 'Caramel', 'breed' => 'Haflinger', 'gender' => 'Hongre', 'coat' => 'Palomino', 'birth' => '2018-06-12', 'weight' => 470, 'height' => 1.48, 'owner' => 'emilie.garcia@owner.com', 'structure' => 'Écurie du Soleil', 'micro' => '250269801234574'],
            ['name' => 'Olympe', 'breed' => 'Selle Français', 'gender' => 'Jument', 'coat' => 'Bai brun', 'birth' => '2017-02-14', 'weight' => 530, 'height' => 1.70, 'owner' => 'emilie.garcia@owner.com', 'structure' => 'Écurie du Soleil', 'micro' => '250269801234575'],
            // Clinique (en pension/soins)
            ['name' => 'Figaro', 'breed' => 'Trotteur Français', 'gender' => 'Hongre', 'coat' => 'Bai', 'birth' => '2014-08-20', 'weight' => 510, 'height' => 1.65, 'owner' => 'thomas.leroy@owner.com', 'structure' => 'Clinique Vétérinaire Équine de Provence', 'micro' => '250269801234576'],
            ['name' => 'Mistral', 'breed' => 'Anglo-Arabe', 'gender' => 'Étalon', 'coat' => 'Gris', 'birth' => '2020-01-05', 'weight' => 500, 'height' => 1.63, 'owner' => 'thomas.leroy@owner.com', 'structure' => 'Clinique Vétérinaire Équine de Provence', 'micro' => '250269801234577'],
            // Chevaux SANS structure (propriétaires indépendants)
            ['name' => 'Tempête', 'breed' => 'Frison', 'gender' => 'Étalon', 'coat' => 'Noir', 'birth' => '2016-12-01', 'weight' => 580, 'height' => 1.68, 'owner' => 'julie.moreau@owner.com', 'structure' => null, 'micro' => '250269801234578'],
            ['name' => 'Bijou', 'breed' => 'Connemara', 'gender' => 'Jument', 'coat' => 'Isabelle', 'birth' => '2021-03-22', 'weight' => 370, 'height' => 1.42, 'owner' => 'julie.moreau@owner.com', 'structure' => null, 'micro' => '250269801234579'],
            ['name' => 'Pégase', 'breed' => 'Selle Français', 'gender' => 'Hongre', 'coat' => 'Bai', 'birth' => '2019-10-10', 'weight' => 540, 'height' => 1.71, 'owner' => 'antoine.faure@owner.com', 'structure' => null, 'micro' => '250269801234580'],
            ['name' => 'Canelle', 'breed' => 'Pur-sang Arabe', 'gender' => 'Jument', 'coat' => 'Alezan', 'birth' => '2020-05-08', 'weight' => 440, 'height' => 1.53, 'owner' => 'antoine.faure@owner.com', 'structure' => null, 'micro' => '250269801234581'],
            // Chevaux supplémentaires pour varier
            ['name' => 'Opale', 'breed' => 'Lusitanien', 'gender' => 'Jument', 'coat' => 'Gris', 'birth' => '2022-01-15', 'weight' => 420, 'height' => 1.56, 'owner' => 'camille.bernard@owner.com', 'structure' => null, 'micro' => '250269801234582'],
            ['name' => 'Rocco', 'breed' => 'KWPN', 'gender' => 'Hongre', 'coat' => 'Bai brun', 'birth' => '2015-07-04', 'weight' => 570, 'height' => 1.74, 'owner' => 'marc.dupont@owner.com', 'structure' => null, 'micro' => '250269801234583'],
            ['name' => 'Étoile', 'breed' => 'Anglo-Arabe', 'gender' => 'Jument', 'coat' => 'Rouan', 'birth' => '2018-09-19', 'weight' => 485, 'height' => 1.60, 'owner' => 'elodie.q@hotmail.fr', 'structure' => null, 'micro' => '250269801234584'],
        ];

        $animals = [];
        foreach ($data as $d) {
            $a = new Animal();
            $a->setName($d['name']);
            $a->setBreed($d['breed']);
            $a->setGender($d['gender']);
            $a->setCoat($d['coat']);
            $a->setBirthDate(new \DateTimeImmutable($d['birth']));
            $a->setWeight($d['weight']);
            $a->setHeight($d['height']);
            $a->setOwner($users[$d['owner']]);
            $a->setMicrochipNumber($d['micro']);

            if ($d['structure'] !== null) {
                $a->setStructure($structures[$d['structure']]);
            }

            $manager->persist($a);
            $animals[$d['name']] = $a;
        }

        return $animals;
    }

    private function createHealthBookEntries(ObjectManager $manager, array $animals, array $users): void
    {
        $pierre = $users['pierre.renault83@gmail.com'];
        $sophie = $users['sophie.martin@pro.com'];
        $claire = $users['claire.morel@pro.com'];
        $julien = $users['julien.roux@pro.com'];

        $entries = [
            ['animal' => 'Eclipse', 'title' => 'Vaccination grippe équine', 'type' => 'vaccination', 'date' => '2026-01-15', 'vet' => $claire, 'desc' => 'Rappel annuel grippe. RAS.', 'batch' => 'GR2026-A12'],
            ['animal' => 'Eclipse', 'title' => 'Séance ostéopathie', 'type' => 'consultation', 'date' => '2026-03-20', 'vet' => $pierre, 'desc' => 'Tension dorsale côté droit. Manipulation vertèbres T12-T15.', 'anamnesis' => 'Raideur au galop à main droite depuis 2 semaines.'],
            ['animal' => 'Eclipse', 'title' => 'Vermifuge ivermectine', 'type' => 'traitement', 'date' => '2026-06-01', 'vet' => $claire, 'desc' => 'Vermifuge semestriel.', 'dosage' => '1 seringue 700kg', 'frequency' => 'Tous les 6 mois'],
            ['animal' => 'Spirit', 'title' => 'Dentisterie annuelle', 'type' => 'consultation', 'date' => '2026-02-10', 'vet' => $sophie, 'desc' => 'Nivellement des tables. Légère surdent côté gauche corrigée.'],
            ['animal' => 'Spirit', 'title' => 'Vaccination tétanos', 'type' => 'vaccination', 'date' => '2026-04-05', 'vet' => $claire, 'desc' => 'Rappel tétanos 3 ans.', 'batch' => 'TET2026-B44'],
            ['animal' => 'Luna', 'title' => 'Bilan locomoteur', 'type' => 'consultation', 'date' => '2026-05-12', 'vet' => $pierre, 'desc' => 'Légère sensibilité pied antérieur droit. Pas de boiterie franche.', 'anamnesis' => 'Propriétaire signale hésitation sur sol dur.', 'staticExam' => 'Test de la pince positif en talon droit.'],
            ['animal' => 'Luna', 'title' => 'Parage et ferrage', 'type' => 'consultation', 'date' => '2026-05-20', 'vet' => $julien, 'desc' => 'Parage correctif antérieurs. Fers à oignon posés.'],
            ['animal' => 'Tango', 'title' => 'Vaccination grippe + rhino', 'type' => 'vaccination', 'date' => '2026-01-20', 'vet' => $claire, 'desc' => 'Primo-vaccination rhinopneumonie + rappel grippe.', 'batch' => 'GR-RH2026-C88'],
            ['animal' => 'Gatsby', 'title' => 'Colique — hospitalisation', 'type' => 'consultation', 'date' => '2026-04-18', 'vet' => $claire, 'desc' => 'Colique spasmodique. Traitement médical, sondage naso-gastrique. Résolution en 6h.', 'anamnesis' => 'Cheval trouvé couché, en sueur, regardant ses flancs.'],
            ['animal' => 'Figaro', 'title' => 'Radiographies membres', 'type' => 'examen', 'date' => '2026-06-25', 'vet' => $claire, 'desc' => 'Radiographies des 4 pieds — bilan arthrose. Arthrose modérée P3 antérieur gauche.'],
            ['animal' => 'Tempête', 'title' => 'Séance ostéopathie', 'type' => 'consultation', 'date' => '2026-07-01', 'vet' => $pierre, 'desc' => 'Bassin légèrement décalé côté gauche. Correction sacro-iliaque.', 'anamnesis' => 'Travail en carrière difficile, refus de s\'incurver à gauche.'],
            ['animal' => 'Perle', 'title' => 'Vaccination grippe', 'type' => 'vaccination', 'date' => '2026-03-10', 'vet' => $claire, 'desc' => 'Primo-vaccination grippe.', 'batch' => 'GR2026-D55'],
            ['animal' => 'Noisette', 'title' => 'Vermifuge moxidectine', 'type' => 'traitement', 'date' => '2026-07-15', 'vet' => $claire, 'desc' => 'Vermifuge avec larvicide.', 'dosage' => '1 seringue 600kg', 'frequency' => 'Annuel'],
            ['animal' => 'Caramel', 'title' => 'Dentisterie', 'type' => 'consultation', 'date' => '2026-05-02', 'vet' => $sophie, 'desc' => 'Nivellement et extraction dent de loup bilatérale.'],
            ['animal' => 'Pégase', 'title' => 'Blessure membre postérieur', 'type' => 'consultation', 'date' => '2026-06-10', 'vet' => $claire, 'desc' => 'Plaie superficielle jarret droit, probablement coup de pied. Points de suture x3. Antibiotiques 5 jours.', 'dosage' => 'Trimétoprime-sulfa 30mg/kg', 'frequency' => '2x/jour pendant 5 jours'],
        ];

        foreach ($entries as $e) {
            $h = new HealthBookEntry();
            $h->setTitle($e['title']);
            $h->setType($e['type']);
            $h->setDate(new \DateTimeImmutable($e['date']));
            $h->setAnimal($animals[$e['animal']]);
            $h->setVeterinarian($e['vet']);
            $h->setDescription($e['desc']);
            if (isset($e['anamnesis'])) $h->setAnamnesis($e['anamnesis']);
            if (isset($e['dosage'])) $h->setDosage($e['dosage']);
            if (isset($e['frequency'])) $h->setFrequency($e['frequency']);
            if (isset($e['batch'])) $h->setBatchNumber($e['batch']);
            if (isset($e['staticExam'])) $h->setStaticExamination($e['staticExam']);
            $manager->persist($h);
        }
    }

    private function createAppointments(ObjectManager $manager, array $users, array $animals): void
    {
        $data = [
            // Past appointments
            ['reason' => 'Séance ostéo — Eclipse', 'at' => '2026-03-20 09:00', 'duration' => 60, 'status' => 'COMPLETED', 'type' => 'Ostéopathie', 'client' => 'elodie.q@hotmail.fr', 'createdBy' => 'pierre.renault83@gmail.com', 'animal' => 'Eclipse', 'location' => 'El Ranch, Marseille'],
            ['reason' => 'Dentisterie — Spirit', 'at' => '2026-02-10 14:00', 'duration' => 45, 'status' => 'COMPLETED', 'type' => 'Dentisterie', 'client' => 'elodie.q@hotmail.fr', 'createdBy' => 'sophie.martin@pro.com', 'animal' => 'Spirit', 'location' => 'El Ranch, Marseille'],
            ['reason' => 'Bilan locomoteur — Luna', 'at' => '2026-05-12 10:30', 'duration' => 60, 'status' => 'COMPLETED', 'type' => 'Ostéopathie', 'client' => 'marc.dupont@owner.com', 'createdBy' => 'pierre.renault83@gmail.com', 'animal' => 'Luna', 'location' => 'El Ranch, Marseille'],
            ['reason' => 'Ferrage correctif — Luna', 'at' => '2026-05-20 08:00', 'duration' => 90, 'status' => 'COMPLETED', 'type' => 'Maréchalerie', 'client' => 'marc.dupont@owner.com', 'createdBy' => 'julien.roux@pro.com', 'animal' => 'Luna', 'location' => 'El Ranch, Marseille'],
            ['reason' => 'Urgence colique — Gatsby', 'at' => '2026-04-18 22:00', 'duration' => 120, 'status' => 'COMPLETED', 'type' => 'Urgence', 'client' => 'lucas.petit@owner.com', 'createdBy' => 'claire.morel@pro.com', 'animal' => 'Gatsby', 'location' => 'Clinique Vétérinaire Équine de Provence'],
            ['reason' => 'Ostéo — Tempête', 'at' => '2026-07-01 11:00', 'duration' => 60, 'status' => 'COMPLETED', 'type' => 'Ostéopathie', 'client' => 'julie.moreau@owner.com', 'createdBy' => 'pierre.renault83@gmail.com', 'animal' => 'Tempête', 'location' => 'Domicile client, Martigues'],
            // Upcoming
            ['reason' => 'Suivi ostéo — Eclipse', 'at' => '2026-08-20 09:00', 'duration' => 60, 'status' => 'PENDING', 'type' => 'Ostéopathie', 'client' => 'elodie.q@hotmail.fr', 'createdBy' => 'pierre.renault83@gmail.com', 'animal' => 'Eclipse', 'location' => 'El Ranch, Marseille'],
            ['reason' => 'Vaccination rappel — Tango', 'at' => '2026-08-25 14:00', 'duration' => 30, 'status' => 'PENDING', 'type' => 'Vaccination', 'client' => 'marc.dupont@owner.com', 'createdBy' => 'claire.morel@pro.com', 'animal' => 'Tango', 'location' => 'El Ranch, Marseille'],
            ['reason' => 'Dentisterie annuelle — Olympe', 'at' => '2026-09-03 10:00', 'duration' => 45, 'status' => 'PENDING', 'type' => 'Dentisterie', 'client' => 'emilie.garcia@owner.com', 'createdBy' => 'sophie.martin@pro.com', 'animal' => 'Olympe', 'location' => 'Écurie du Soleil, Aix-en-Provence'],
            ['reason' => 'Contrôle post-blessure — Pégase', 'at' => '2026-08-15 16:00', 'duration' => 30, 'status' => 'PENDING', 'type' => 'Suivi', 'client' => 'antoine.faure@owner.com', 'createdBy' => 'claire.morel@pro.com', 'animal' => 'Pégase', 'location' => 'Domicile client, Istres'],
            ['reason' => 'Ferrage — Figaro', 'at' => '2026-09-10 08:30', 'duration' => 90, 'status' => 'PENDING', 'type' => 'Maréchalerie', 'client' => 'thomas.leroy@owner.com', 'createdBy' => 'julien.roux@pro.com', 'animal' => 'Figaro', 'location' => 'Clinique Vétérinaire Équine de Provence'],
            // Personal / rest blocks
            ['reason' => 'Formation ostéo crânienne', 'at' => '2026-09-15 09:00', 'duration' => 480, 'status' => 'CONFIRMED', 'type' => null, 'client' => null, 'createdBy' => 'pierre.renault83@gmail.com', 'animal' => null, 'location' => 'Montpellier', 'eventType' => 'personal'],
            ['reason' => 'Pause déjeuner', 'at' => '2026-08-20 12:30', 'duration' => 60, 'status' => 'CONFIRMED', 'type' => null, 'client' => null, 'createdBy' => 'pierre.renault83@gmail.com', 'animal' => null, 'location' => null, 'eventType' => 'rest'],
            // Cancelled
            ['reason' => 'Ostéo — Bijou (annulé)', 'at' => '2026-07-28 14:00', 'duration' => 60, 'status' => 'CANCELLED', 'type' => 'Ostéopathie', 'client' => 'julie.moreau@owner.com', 'createdBy' => 'pierre.renault83@gmail.com', 'animal' => 'Bijou', 'location' => 'Domicile client, Martigues'],
        ];

        foreach ($data as $d) {
            $a = new Appointment();
            $a->setReason($d['reason']);
            $a->setScheduledAt(new \DateTimeImmutable($d['at']));
            $a->setDuration($d['duration']);
            $a->setStatus($d['status']);
            $a->setConsultationType($d['type']);
            $a->setLocation($d['location']);
            $a->setCreatedBy($users[$d['createdBy']]);
            if (isset($d['eventType'])) {
                $a->setEventType($d['eventType']);
            }
            if ($d['client'] !== null) {
                $a->setClient($users[$d['client']]);
            }
            if ($d['animal'] !== null) {
                $a->setAnimal($animals[$d['animal']]);
            }
            $manager->persist($a);
        }
    }

    private function createAllergies(ObjectManager $manager, array $animals): void
    {
        $data = [
            ['animal' => 'Eclipse', 'name' => 'Piqûres de moucherons (dermite estivale)', 'severity' => 'moderate', 'symptoms' => 'Démangeaisons crinière et base de queue, croûtes', 'diagnosed' => '2023-06-15'],
            ['animal' => 'Gatsby', 'name' => 'Poussière de foin', 'severity' => 'mild', 'symptoms' => 'Toux légère au box, jetage nasal', 'diagnosed' => '2024-11-20'],
            ['animal' => 'Perle', 'name' => 'Pénicilline', 'severity' => 'severe', 'symptoms' => 'Urticaire généralisée, œdème facial', 'diagnosed' => '2025-02-10', 'notes' => 'ATTENTION : réaction anaphylactoïde. Contre-indication absolue.'],
            ['animal' => 'Figaro', 'name' => 'Phénylbutazone', 'severity' => 'moderate', 'symptoms' => 'Ulcères gastriques, perte d\'appétit', 'diagnosed' => '2025-09-05', 'notes' => 'Utiliser meloxicam en alternative.'],
            ['animal' => 'Tempête', 'name' => 'Dermite estivale (Culicoides)', 'severity' => 'moderate', 'symptoms' => 'Prurit intense encolure et queue été', 'diagnosed' => '2022-07-01'],
        ];

        foreach ($data as $d) {
            $a = new Allergy();
            $a->setAnimal($animals[$d['animal']]);
            $a->setName($d['name']);
            $a->setSeverity($d['severity']);
            $a->setSymptoms($d['symptoms']);
            $a->setDiagnosedAt(new \DateTimeImmutable($d['diagnosed']));
            if (isset($d['notes'])) $a->setNotes($d['notes']);
            $manager->persist($a);
        }
    }

    private function createWeightRecords(ObjectManager $manager, array $animals): void
    {
        $targets = ['Eclipse' => [510, 515, 520, 518, 522], 'Gatsby' => [445, 448, 452, 450, 448], 'Figaro' => [520, 515, 510, 508, 512], 'Tempête' => [575, 578, 580, 582, 580]];
        $startMonth = 2;

        foreach ($targets as $name => $weights) {
            foreach ($weights as $i => $w) {
                $wr = new WeightRecord();
                $wr->setAnimal($animals[$name]);
                $wr->setWeight($w);
                $wr->setRecordedAt(new \DateTimeImmutable(sprintf('2026-%02d-01', $startMonth + $i)));
                $manager->persist($wr);
            }
        }
    }

    private function createReminders(ObjectManager $manager, array $animals, array $users): void
    {
        $data = [
            ['animal' => 'Eclipse', 'owner' => 'elodie.q@hotmail.fr', 'title' => 'Rappel vaccin grippe', 'at' => '2027-01-15 09:00', 'recurrence' => 'yearly'],
            ['animal' => 'Eclipse', 'owner' => 'elodie.q@hotmail.fr', 'title' => 'Vermifuge semestriel', 'at' => '2026-12-01 08:00', 'recurrence' => 'biannual'],
            ['animal' => 'Spirit', 'owner' => 'elodie.q@hotmail.fr', 'title' => 'Dentiste annuel', 'at' => '2027-02-10 10:00', 'recurrence' => 'yearly'],
            ['animal' => 'Luna', 'owner' => 'marc.dupont@owner.com', 'title' => 'Contrôle ferrage (6 semaines)', 'at' => '2026-09-01 08:00', 'recurrence' => null],
            ['animal' => 'Gatsby', 'owner' => 'lucas.petit@owner.com', 'title' => 'Suivi colique — contrôle véto', 'at' => '2026-08-18 14:00', 'recurrence' => null],
            ['animal' => 'Figaro', 'owner' => 'thomas.leroy@owner.com', 'title' => 'Radiographies de contrôle', 'at' => '2026-12-25 10:00', 'recurrence' => 'yearly'],
            ['animal' => 'Tempête', 'owner' => 'julie.moreau@owner.com', 'title' => 'Début traitement dermite (avril)', 'at' => '2027-04-01 08:00', 'recurrence' => 'yearly'],
            ['animal' => 'Pégase', 'owner' => 'antoine.faure@owner.com', 'title' => 'Retrait points de suture', 'at' => '2026-08-20 10:00', 'recurrence' => null],
        ];

        foreach ($data as $d) {
            $r = new Reminder();
            $r->setAnimal($animals[$d['animal']]);
            $r->setOwner($users[$d['owner']]);
            $r->setTitle($d['title']);
            $r->setScheduledAt(new \DateTimeImmutable($d['at']));
            $r->setRecurrence($d['recurrence']);
            $manager->persist($r);
        }
    }

    private function createMessages(ObjectManager $manager, array $users, array $animals): void
    {
        $convos = [
            // Élodie <-> Pierre about Eclipse
            ['sender' => 'elodie.q@hotmail.fr', 'recipient' => 'pierre.renault83@gmail.com', 'animal' => 'Eclipse', 'content' => 'Bonjour Pierre, Eclipse est de nouveau raide à droite au galop depuis quelques jours. Pourriez-vous passer la voir ?', 'date' => '2026-08-05 10:15'],
            ['sender' => 'pierre.renault83@gmail.com', 'recipient' => 'elodie.q@hotmail.fr', 'animal' => 'Eclipse', 'content' => 'Bonjour Élodie, je peux passer le 20 août en matinée. Ça vous convient ?', 'date' => '2026-08-05 11:30'],
            ['sender' => 'elodie.q@hotmail.fr', 'recipient' => 'pierre.renault83@gmail.com', 'animal' => 'Eclipse', 'content' => 'Parfait, merci ! Je serai présente au ranch.', 'date' => '2026-08-05 12:00'],
            // Marc <-> Julien about Luna
            ['sender' => 'marc.dupont@owner.com', 'recipient' => 'julien.roux@pro.com', 'animal' => 'Luna', 'content' => 'Bonjour, Luna semble avoir un fer qui bouge sur l\'antérieur droit. Est-ce urgent ?', 'date' => '2026-08-10 08:45'],
            ['sender' => 'julien.roux@pro.com', 'recipient' => 'marc.dupont@owner.com', 'animal' => 'Luna', 'content' => 'Si le fer ne tient plus, mieux vaut le retirer proprement. Je peux passer demain matin. En attendant, évitez le travail sur sol dur.', 'date' => '2026-08-10 09:20'],
            // Lucas <-> Claire about Gatsby
            ['sender' => 'lucas.petit@owner.com', 'recipient' => 'claire.morel@pro.com', 'animal' => 'Gatsby', 'content' => 'Bonjour Dr. Morel, Gatsby a bien récupéré de la colique. Faut-il prévoir un contrôle ?', 'date' => '2026-07-20 14:00'],
            ['sender' => 'claire.morel@pro.com', 'recipient' => 'lucas.petit@owner.com', 'animal' => 'Gatsby', 'content' => 'Bonne nouvelle ! Oui, un contrôle dans un mois serait prudent. Je vous ai mis un rappel au 18 août.', 'date' => '2026-07-20 15:30'],
            // Antoine <-> Claire about Pégase
            ['sender' => 'antoine.faure@owner.com', 'recipient' => 'claire.morel@pro.com', 'animal' => 'Pégase', 'content' => 'Docteur, la plaie de Pégase a l\'air un peu rouge et gonflée. Est-ce normal ?', 'date' => '2026-08-12 07:30'],
            ['sender' => 'claire.morel@pro.com', 'recipient' => 'antoine.faure@owner.com', 'animal' => 'Pégase', 'content' => 'Envoyez-moi une photo si possible. Si c\'est chaud au toucher avec un suintement, il faut que je passe rapidement. Continuez les antibiotiques en attendant.', 'date' => '2026-08-12 08:00'],
            ['sender' => 'antoine.faure@owner.com', 'recipient' => 'claire.morel@pro.com', 'animal' => 'Pégase', 'content' => 'Finalement c\'est mieux ce matin, la rougeur a diminué. Je continue le traitement. Merci !', 'date' => '2026-08-13 09:00'],
        ];

        foreach ($convos as $c) {
            $m = new Message();
            $m->setSender($users[$c['sender']]);
            $m->setRecipient($users[$c['recipient']]);
            if ($c['animal'] !== null) {
                $m->setAnimal($animals[$c['animal']]);
            }
            $m->setContent($c['content']);
            $manager->persist($m);
        }
    }

    private function createAnimalShares(ObjectManager $manager, array $animals): void
    {
        $shares = [
            ['animal' => 'Eclipse', 'email' => 'pierre.renault83@gmail.com', 'level' => 'read'],
            ['animal' => 'Spirit', 'email' => 'pierre.renault83@gmail.com', 'level' => 'read'],
            ['animal' => 'Luna', 'email' => 'julien.roux@pro.com', 'level' => 'read'],
            ['animal' => 'Gatsby', 'email' => 'claire.morel@pro.com', 'level' => 'write'],
            ['animal' => 'Tempête', 'email' => 'pierre.renault83@gmail.com', 'level' => 'read'],
        ];

        foreach ($shares as $s) {
            $as = new AnimalShare();
            $as->setAnimal($animals[$s['animal']]);
            $as->setSharedWithEmail($s['email']);
            $as->setPermissionLevel($s['level']);
            $as->setCreatedAt(new \DateTimeImmutable('2026-01-01'));
            $manager->persist($as);
        }
    }
}
