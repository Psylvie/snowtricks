<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }
	
    #[Route('/trick/{slug}/comments/load-more/{offset}', name: 'app_comment_load_more')]
    public function loadMore(string $slug, int $offset): JsonResponse
    {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['slug' => $slug]);

        if (!$trick) {
            throw $this->createNotFoundException('Trick not found with slug: '.$slug);
        }

        $limit = 5;
        $comments = $this->em->getRepository(Comment::class)
            ->findBy(
                ['trick' => $trick],
                ['createdAt' => 'DESC'],
                $limit,
                $offset
            );

        $totalComments = $this->em->getRepository(Comment::class)
            ->count(['trick' => $trick]);

        $hasMore = $totalComments > ($offset + $limit);

        return new JsonResponse([
            'html' => $this->renderView('trick/partials/_comments_list.html.twig', ['comments' => $comments]),
            'hasMore' => $hasMore,
        ]);
    }
}
