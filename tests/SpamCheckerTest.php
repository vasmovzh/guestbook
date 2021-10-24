<?php

namespace App\Tests;

use App\Entity\Comment;
use App\SpamChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SpamCheckerTest extends TestCase
{
    public function testSpamScoreWithInvalidRequest(): void
    {
        $context = [];
        $comment = new Comment();
        $comment->setCreatedAtValue();

        $headers  = ['x-akismet-debug-help: Invalid key'];
        $response = new MockResponse('invalid', ['response_headers' => $headers]);
        $client   = new MockHttpClient([$response]);
        $checker  = new SpamChecker($client, 'qwerty');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid (Invalid key).');

        $checker->getSpamScore($comment, $context);
    }

    /**
     * @param int               $expectedScore
     * @param ResponseInterface $response
     * @param Comment           $comment
     * @param array             $context
     *
     * @dataProvider getComments
     */
    public function testSpamScore(int $expectedScore, ResponseInterface $response, Comment $comment, array $context): void
    {
        $client  = new MockHttpClient([$response]);
        $checker = new SpamChecker($client, 'qwerty');
        $score   = $checker->getSpamScore($comment, $context);

        self::assertSame($expectedScore, $score);
    }

    public function getComments(): iterable
    {
        $context = [];
        $comment = new Comment();
        $comment->setCreatedAtValue();

        $headers  = ['x-akismet-pro-tip: discard'];
        $response = new MockResponse('', ['response_headers' => $headers]);

        yield 'blatant_spam' => [2, $response, $comment, $context];
        yield 'spam' => [1, new MockResponse('true'), $comment, $context];
        yield 'ham' => [0, new MockResponse('false'), $comment, $context];
    }
}
