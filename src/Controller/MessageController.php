<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\AnimalRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/message')]
#[IsGranted('ROLE_USER')]
final class MessageController extends AbstractController
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/var/uploads/messages')]
        private readonly string $messageUploadsDirectory,
    ) {
    }

    #[Route('/', name: 'app_message_index', methods: ['GET'])]
    public function index(MessageRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('message/index.html.twig', [
            'conversations' => $repository->findConversationsByUser($user),
            'directConversations' => $repository->findDirectConversationsByUser($user),
        ]);
    }

    #[Route('/new', name: 'app_message_new', methods: ['GET'])]
    public function new(AnimalRepository $animalRepository, MessageRepository $messageRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $animals = $animalRepository->findAccessibleAnimals($user);

        $existingConversations = $messageRepository->findConversationsByUser($user);
        $animalsWithMessages = array_map(fn ($c) => $c['animal']->getId(), $existingConversations);

        return $this->render('message/new.html.twig', [
            'animals' => $animals,
            'animalsWithMessages' => $animalsWithMessages,
        ]);
    }

    #[Route('/animal/{id}', name: 'app_message_thread', methods: ['GET', 'POST'])]
    public function thread(Request $request, Animal $animal, MessageRepository $repository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animal);

        /** @var User $user */
        $user = $this->getUser();

        $messages = $repository->findByAnimal($animal);

        foreach ($messages as $msg) {
            if ($msg->getSender() !== $user && !$msg->isRead()) {
                $msg->setIsRead(true);
            }
        }
        $em->flush();

        $newMessage = new Message();
        $form = $this->createForm(MessageType::class, $newMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animal);

            /** @var UploadedFile|null $attachmentFile */
            $attachmentFile = $form->get('attachmentFile')->getData();

            if (!$newMessage->getContent() && !$attachmentFile) {
                $this->addFlash('warning', 'Veuillez saisir un message ou joindre un fichier.');

                return $this->redirectToRoute('app_message_thread', ['id' => $animal->getId()]);
            }

            if ($attachmentFile) {
                if (!is_dir($this->messageUploadsDirectory)) {
                    mkdir($this->messageUploadsDirectory, 0775, true);
                }

                $originalName = $attachmentFile->getClientOriginalName();
                $safeFilename = $this->slugger->slug(pathinfo($originalName, PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $attachmentFile->guessExtension();

                $attachmentFile->move($this->messageUploadsDirectory, $newFilename);

                $newMessage->setAttachmentFilename($newFilename);
                $newMessage->setAttachmentOriginalName($originalName);
            }

            if (!$newMessage->getContent()) {
                $newMessage->setContent('');
            }

            $newMessage->setSender($user);
            $newMessage->setAnimal($animal);

            $em->persist($newMessage);
            $em->flush();

            return $this->redirectToRoute('app_message_thread', ['id' => $animal->getId()]);
        }

        return $this->render('message/thread.html.twig', [
            'animal' => $animal,
            'messages' => $messages,
            'form' => $form,
        ]);
    }

    #[Route('/contact/{id}', name: 'app_message_contact', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function contact(Request $request, User $recipient, MessageRepository $repository, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user === $recipient) {
            return $this->redirectToRoute('app_message_index');
        }

        $messages = $repository->findDirectMessages($user, $recipient);

        foreach ($messages as $msg) {
            if ($msg->getSender() !== $user && !$msg->isRead()) {
                $msg->setIsRead(true);
            }
        }
        $em->flush();

        $newMessage = new Message();
        $form = $this->createForm(MessageType::class, $newMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $attachmentFile */
            $attachmentFile = $form->get('attachmentFile')->getData();

            if (!$newMessage->getContent() && !$attachmentFile) {
                $this->addFlash('warning', 'Veuillez saisir un message ou joindre un fichier.');
                return $this->redirectToRoute('app_message_contact', ['id' => $recipient->getId()]);
            }

            if ($attachmentFile) {
                if (!is_dir($this->messageUploadsDirectory)) {
                    mkdir($this->messageUploadsDirectory, 0775, true);
                }
                $originalName = $attachmentFile->getClientOriginalName();
                $safeFilename = $this->slugger->slug(pathinfo($originalName, PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $attachmentFile->guessExtension();
                $attachmentFile->move($this->messageUploadsDirectory, $newFilename);
                $newMessage->setAttachmentFilename($newFilename);
                $newMessage->setAttachmentOriginalName($originalName);
            }

            if (!$newMessage->getContent()) {
                $newMessage->setContent('');
            }

            $newMessage->setSender($user);
            $newMessage->setRecipient($recipient);

            $em->persist($newMessage);
            $em->flush();

            return $this->redirectToRoute('app_message_contact', ['id' => $recipient->getId()]);
        }

        return $this->render('message/direct.html.twig', [
            'recipient' => $recipient,
            'messages' => $messages,
            'form' => $form,
        ]);
    }

    #[Route('/download/{id}', name: 'app_message_download', methods: ['GET'])]
    public function download(Message $message): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $message->getAnimal());

        if (!$message->getAttachmentFilename()) {
            throw $this->createNotFoundException('Aucun fichier attaché.');
        }

        $filePath = $this->messageUploadsDirectory . '/' . $message->getAttachmentFilename();

        if (!is_file($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $message->getAttachmentOriginalName() ?? $message->getAttachmentFilename(),
        );

        return $response;
    }
}
