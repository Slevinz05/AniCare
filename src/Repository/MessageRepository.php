<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Message> */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findByAnimal(Animal $animal): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.animal = :animal')
            ->setParameter('animal', $animal)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{animal: Animal, lastMessage: Message, unreadCount: int}>
     */
    public function findConversationsByUser(User $user): array
    {
        $animals = $this->getEntityManager()->createQueryBuilder()
            ->select('a')
            ->from(\App\Entity\Animal::class, 'a')
            ->leftJoin('a.animalShares', 's')
            ->where('a.owner = :user OR s.sharedWithEmail = :email')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->getQuery()
            ->getResult();

        $conversations = [];

        foreach ($animals as $animal) {
            $messages = $this->findByAnimal($animal);
            if (empty($messages)) {
                continue;
            }

            $lastMessage = end($messages);
            $unreadCount = 0;
            foreach ($messages as $msg) {
                if ($msg->getSender() !== $user && !$msg->isRead()) {
                    $unreadCount++;
                }
            }

            $conversations[] = [
                'animal' => $animal,
                'lastMessage' => $lastMessage,
                'unreadCount' => $unreadCount,
                'messageCount' => count($messages),
            ];
        }

        usort($conversations, fn ($a, $b) => $b['lastMessage']->getCreatedAt() <=> $a['lastMessage']->getCreatedAt());

        return $conversations;
    }

    public function countUnreadByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->join('m.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->where('m.isRead = false')
            ->andWhere('m.sender != :user')
            ->andWhere('a.owner = :user OR s.sharedWithEmail = :email')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
