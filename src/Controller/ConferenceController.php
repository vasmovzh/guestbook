<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class ConferenceController extends AbstractController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return new Response($this->twig->render(
            'conference/index.html.twig',
            ['conferences' => $conferenceRepository->findAll()]
        ));
    }

    /**
     * @Route("/conference/{slug}", name="conference")
     */
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository): Response
    {
        $offset    = max(0, $request->query->getInt('offset'));
        $paginator = $commentRepository->getPaginator($conference, $offset);

        return new Response($this->twig->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments'   => $paginator,
            'prev'       => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next'       => $offset + CommentRepository::COMMENTS_PER_PAGE,
        ]));
    }
}
