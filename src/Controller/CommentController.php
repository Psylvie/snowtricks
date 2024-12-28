<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Service\LoadMoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class CommentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommentRepository $commentRepository,
        private readonly LoadMoreService $loadMoreService
    ) {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/trick/{slug}/comments/load-more/{offset}', name: 'app_comment_load_more')]
    public function loadMoreComments(string $slug, int $offset): JsonResponse
    {
        $limit = 2;

        $trick = $this->em->getRepository(Trick::class)->findOneBy(['slug' => $slug]);

        if (!$trick) {
            return new JsonResponse(['html' => '', 'hasMore' => false]);
        }

        $result = $this->loadMoreService->loadItems(
            Comment::class,
            $offset,
            $limit,
            'trick/partials/_comments_list.html.twig',
            'comments',
            ['trick' => $trick]
        );

        return new JsonResponse([
            'html' => $result['html'],
            'hasMore' => $result['hasMore'],
        ]);
    }

    #[Route('/trick/{slug}/comment/add', name: 'app_comment_add', methods: ['POST'])]
    public function addComment(Request $request, string $slug): Response
    {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['slug' => $slug]);
        if (!$trick) {
            $this->addFlash('danger', 'Trick introuvable');

            return $this->redirectToRoute('app_tricks_list');
        }

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setTrick($trick);
            $comment->setUser($this->getUser());
            $this->em->persist($comment);
            $this->em->flush();
            $this->addFlash('success', 'Commentaire ajouté avec succès');

            return $this->redirectToRoute('app_trick_show', ['slug' => $slug]);
        }
        $limit = 2;
        $comments = $this->commentRepository->findBy(
            ['trick' => $trick],
            ['id' => 'DESC'],
            $limit
        );

        $totalComments = count($comments);
        $hasMore = $totalComments > $limit;

        return $this->render('trick/show.html.twig', [
            'trick' => $trick,
            'comments' => $comments,
            'commentForm' => $form->createView(),
            'hasMore' => $hasMore,
        ]);
    }
}
