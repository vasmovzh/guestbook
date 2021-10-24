<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class AppFixtures extends Fixture
{
    private PasswordHasherFactoryInterface $hasherFactory;

    public function __construct(PasswordHasherFactoryInterface $hasherFactory)
    {
        $this->hasherFactory = $hasherFactory;
    }

    public function load(ObjectManager $manager): void
    {
        $moscow = $this->getPersistedConference($manager, 'Moscow', '2021', true);
        $tomsk  = $this->getPersistedConference($manager, 'Tomsk', '2020');

        foreach (self::getMoscowComments() as $comment) {
            $this->persistCommentForConference($manager, $moscow, $comment);
        }

        foreach (self::getTomskComments() as $comment) {
            $this->persistCommentForConference($manager, $tomsk, $comment);
        }

        $this->persistAdmin($manager);

        $manager->flush();
    }

    private function getPersistedConference(ObjectManager $manager, string $city, string $year, bool $isInternational = false): Conference
    {
        $conference = new Conference();

        $conference->setCity($city);
        $conference->setIsInternational($isInternational);
        $conference->setYear($year);

        $manager->persist($conference);

        return $conference;
    }

    private function persistCommentForConference(ObjectManager $manager, Conference $conference, array $commentData): void
    {
        $comment = new Comment();

        $comment->setAuthor($commentData['author']);
        $comment->setConference($conference);
        $comment->setCreatedAtValue();
        $comment->setEmail($commentData['email']);
        $comment->setText($commentData['text']);

        $manager->persist($comment);
    }

    private function persistAdmin(ObjectManager $manager): void
    {
        $admin    = new Admin();
        $hasher   = $this->hasherFactory->getPasswordHasher(Admin::class);
        $password = $hasher->hash('admin');

        $admin->setPassword($password);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setUsername('admin');

        $manager->persist($admin);
    }

    private static function getMoscowComments(): array
    {
        return [
            [
                'author' => 'Valera',
                'email'  => 'v@le.ra',
                'text'   => 'test moscow text 1',
            ],
            [
                'author' => 'Kirill',
                'email'  => 'kir@du.rak',
                'text'   => 'test moscow text 2',
            ],
            [
                'author' => 'Dima',
                'email'  => 'dim@du.rak',
                'text'   => 'test moscow text 3',
            ],
            [
                'author' => 'Zheka',
                'email'  => 'zhek@du.rak',
                'text'   => 'test moscow text 4',
            ],
        ];
    }

    private static function getTomskComments(): array
    {
        return [
            [
                'author' => 'Valera',
                'email'  => 'v@le.ra',
                'text'   => 'test tomsk text 1',
            ],
            [
                'author' => 'Kirill',
                'email'  => 'kir@du.rak',
                'text'   => 'test tomsk text 2',
            ],
            [
                'author' => 'Dima',
                'email'  => 'dim@du.rak',
                'text'   => 'test tomsk text 3',
            ],
            [
                'author' => 'Zheka',
                'email'  => 'zhek@du.rak',
                'text'   => 'test tomsk text 4',
            ],
        ];
    }
}
