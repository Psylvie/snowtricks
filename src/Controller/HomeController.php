<?php

namespace App\Controller;

use App\Repository\TrickRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly TrickRepository $trickRepository,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(
        Request $request,
        Security $security,
        SessionInterface $session,
    ): Response {
        if ($security->getUser() && !$session->get('flash_message_shown')) {
            $user = $security->getUser();
            $this->addFlash('success', sprintf('Salut %s, tu es maintenant connecté !', $user->getUserIdentifier()));
            $session->set('flash_message_shown', true);
        }
        $page = max(1, (int) $request->get('page', 1));
        $limit = 15;
        $tricks = $this->trickRepository->findPaginated($page, $limit);
        $totalTricks = $this->trickRepository->countAllTricks();
        $hasMore = $page * $limit < $totalTricks;

        return $this->render('home/homepage.html.twig', [
            'tricks' => $tricks,
            'hasMore' => $hasMore,
            'page' => $page,
        ]);
    }
}
