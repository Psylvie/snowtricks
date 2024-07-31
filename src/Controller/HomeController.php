<?php

namespace App\Controller;

use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(MailerService $mailerS): Response
    {
        return $this->render('/home/homepage.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
