<?php

namespace App\Tests\Controller;

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

        $client->request('GET', '/conference/moscow-2021');
        $client->submitForm('Submit', [
            'comment_form[author]' => 'Ivan',
            'comment_form[email]'  => 'iv@n.org',
            'comment_form[photo]'  => dirname(__DIR__, 2) . '/public/images/under-construction.gif',
            'comment_form[text]'   => 'test moscow text from functional text',
        ]);

        self::assertResponseRedirects();

        $client->followRedirect();

        self::assertSelectorExists('div:contains("There are 5 comments")');
    }
}
