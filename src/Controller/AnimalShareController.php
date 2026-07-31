<?php

namespace App\Controller;

use App\Entity\AnimalShare;
use App\Entity\User;
use App\Form\AnimalShareType;
use App\Repository\AnimalShareRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/partage')]
#[IsGranted('ROLE_USER')]
final class AnimalShareController extends AbstractController
{
    #[Route('', name: 'app_animal_share_index', methods: ['GET'])]
    public function index(AnimalShareRepository $animalShareRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_PRO')) {
            $receivedShares = $animalShareRepository->findSharedWithEmail($user->getEmail());

            return $this->render('animal_share/index_pro.html.twig', [
                'received_shares' => $receivedShares,
            ]);
        }

        return $this->render('animal_share/index.html.twig', [
            'animal_shares' => $animalShareRepository->findByOwner($user),
        ]);
    }

    #[Route('/nouveau', name: 'app_animal_share_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $animalShare = new AnimalShare();

        $prefillEmail = $request->query->get('email');
        if ($prefillEmail) {
            $animalShare->setSharedWithEmail($prefillEmail);
        }

        $form = $this->createForm(AnimalShareType::class, $animalShare, [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('ANIMAL_DELETE', $animalShare->getAnimal());

            $animalShare->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($animalShare);
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->from(new Address('noreply@anicare.fr', 'AniCare'))
                ->to($animalShare->getSharedWithEmail())
                ->subject('AniCare — ' . $user->getEmail() . ' a partagé un dossier animal avec vous')
                ->htmlTemplate('emails/animal_shared.html.twig')
                ->context([
                    'owner_email' => $user->getEmail(),
                    'animal_name' => $animalShare->getAnimal()->getName(),
                    'permission_level' => $animalShare->getPermissionLevel(),
                    'recipient_email' => $animalShare->getSharedWithEmail(),
                    'app_url' => $request->getSchemeAndHttpHost(),
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Le dossier de ' . $animalShare->getAnimal()->getName() . ' a été partagé avec ' . $animalShare->getSharedWithEmail() . '.');

            return $this->redirectToRoute('app_animal_share_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('animal_share/new.html.twig', [
            'animal_share' => $animalShare,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_animal_share_show', methods: ['GET'])]
    public function show(AnimalShare $animalShare): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animalShare->getAnimal());

        return $this->render('animal_share/show.html.twig', [
            'animal_share' => $animalShare,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_animal_share_edit', methods: ['POST'])]
    public function edit(Request $request, AnimalShare $animalShare, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_DELETE', $animalShare->getAnimal());

        if (!$this->isCsrfTokenValid('animal_share' . $animalShare->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_animal_share_index');
        }

        $level = $request->getPayload()->getString('permissionLevel');
        if (in_array($level, ['READ', 'WRITE'], true)) {
            $animalShare->setPermissionLevel($level);
            $entityManager->flush();
            $this->addFlash('success', 'Permission mise à jour.');
        }

        return $this->redirectToRoute('app_animal_share_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_animal_share_delete', methods: ['POST'])]
    public function delete(Request $request, AnimalShare $animalShare, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_DELETE', $animalShare->getAnimal());

        if ($this->isCsrfTokenValid('delete' . $animalShare->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($animalShare);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_animal_share_index', [], Response::HTTP_SEE_OTHER);
    }
}
