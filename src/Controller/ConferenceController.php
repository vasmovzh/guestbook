<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

final class ConferenceController extends AbstractController
{
    private Environment            $twig;
    private EntityManagerInterface $entityManager;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->twig          = $twig;
        $this->entityManager = $entityManager;
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
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository, string $photoDir): Response
    {
        $comment     = new Comment();
        $commentForm = $this->createForm(CommentFormType::class, $comment);

        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setConference($conference);

            $photo = $commentForm['photo'] ?? null;
            $photo = $photo ? $photo->getData() : null;

            if ($photo instanceof UploadedFile) {
                $filename = bin2hex(random_bytes(6) . '.' . $photo->guessExtension());

                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $exception) {
                    // i don't know what to do
                }

                $comment->setPhotoFilename($filename);
            }


            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        $offset    = max(0, $request->query->getInt('offset'));
        $paginator = $commentRepository->getPaginator($conference, $offset);

        return new Response($this->twig->render('conference/show.html.twig', [
            'conference'   => $conference,
            'comments'     => $paginator,
            'prev'         => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next'         => $offset + CommentRepository::COMMENTS_PER_PAGE,
            'comment_form' => $commentForm->createView(),
        ]));
    }
}
