<?php

namespace App\Controller;

use App\Entity\Trick;
use App\Service\LoadMoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly LoadMoreService $loadMoreService,
        private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(Security $security, SessionInterface $session): Response
    {
        if ($security->getUser() && !$session->get('flash_message_shown')) {
            $user = $security->getUser();
            $this->addFlash('success', 'Salut '.$user->getUsername().', tu es maintenant connecté !');
            $session->set('flash_message_shown', true);
        }
        $tricks = $this->em->getRepository(Trick::class)->findBy([], ['createdAt' => 'DESC'], 15);

        return $this->render('home/homepage.html.twig', [
            'tricks' => $tricks,
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route('home/load-more/{offset}', name: 'app_home_load_more')]
    public function loadMore(int $offset): JsonResponse
    {
        $data = $this->loadMoreService->loadItems(
            Trick::class,
            $offset,
            5,
            'trick/partials/_tricks_list.html.twig',
            'tricks'
        );

        return new JsonResponse($data);
    }
}
