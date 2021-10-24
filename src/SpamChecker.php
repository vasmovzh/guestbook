<?php

declare(strict_types=1);

namespace App;

use App\Entity\Comment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SpamChecker
{
    private string              $endpoint;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient, string $akismetKey)
    {
        $this->httpClient = $httpClient;
        $this->endpoint   = sprintf('https://%s.rest.akismet.com/1.1/comment-check', $akismetKey);
    }

    /**
     * @param Comment $comment
     * @param array   $context
     *
     * @return int Spam score: 0 - not spam, 1 - maybe spam, 2 - blatant spam
     */
    public function getSpamScore(Comment $comment, array $context): int
    {
        $response = $this->httpClient->request(Request::METHOD_POST, $this->endpoint, [
            'body' => array_merge($context, [
                'blog'                 => 'https://vasmovzh.github.io/guestbook',
                'blog_charset'         => 'UTF-8',
                'blog_lang'            => 'en',
                'comment_author'       => $comment->getAuthor(),
                'comment_author_email' => $comment->getEmail(),
                'comment_content'      => $comment->getText(),
                'comment_date_gmt'     => $comment->getCreatedAt()->format('c'),
                'comment_type'         => 'comment',
                'is_test'              => true,
            ]),
        ]);

        if (! $response instanceof ResponseInterface || $response->getStatusCode() !== Response::HTTP_OK) {
            throw new \RuntimeException(json_encode($response->toArray(false), JSON_THROW_ON_ERROR));
        }

        $headers = $response->getHeaders();
        if ('discard' === ($headers['x-akismet-pro-tip'][0] ?? '')) {
            return 2;
        }

        $content = $response->getContent();
        if (isset($headers['x-akismet-debug-help'][0])) {
            throw new \RuntimeException(sprintf('Unable to check for spam: %s (%s).', $content, $headers['x-akismet-debug-help'][0]));
        }

        return 'true' === $content ? 1 : 0;
    }
}
