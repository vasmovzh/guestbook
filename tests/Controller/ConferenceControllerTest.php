<?php

namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ConferenceControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = self::createClient();

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Give your feedback');
    }

    public function testConferencePage(): void
    {
        $client  = self::createClient();
        $crawler = $client->request('GET', '/');

        self::assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        self::assertPageTitleContains('Tomsk');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('div:contains("There are 4 comments")');
        self::assertSelectorTextContains('h2', 'Tomsk 2020');
    }

    public function testCommentSubmission(): void
    {
        $client = self::createClient();
        $email  = 'iv@n.org';

        $client->request('GET', '/conference/moscow-2021');
        $client->submitForm('Submit', [
            'comment_form[author]' => 'Ivan',
            'comment_form[email]'  => $email,
            'comment_form[photo]'  => dirname(__DIR__, 2) . '/public/images/under-construction.gif',
            'comment_form[text]'   => 'test moscow text from functional text',
        ]);

        self::assertResponseRedirects();

        $container = self::getContainer();
        /** @var CommentRepository $repository */
        $repository = $container->get(CommentRepository::class);
        $comment    = $repository->findOneBy(['email' => $email]);

        self::assertInstanceOf(Comment::class, $comment);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        $entityManager->flush();

        $client->followRedirect();

        self::assertSelectorExists('div:contains("There are 5 comments")');
    }
}
