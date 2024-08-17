<?php

namespace App\Controller;

use App\Repository\TrickRepository;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private TrickRepository $trickRepository;

    public function __construct(TrickRepository $trickRepository)
    {
        $this->trickRepository = $trickRepository;
    }

    #[Route('/', name: 'app_home')]
    public function index(MailerService $mailerS): Response
    {
        $tricks = $this->trickRepository->findAll();
        return $this->render('/home/homepage.html.twig', [
            'tricks' => $tricks,
        ]);
    }
}
