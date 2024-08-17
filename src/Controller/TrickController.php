<?php

namespace App\Controller;

use App\Entity\Trick;
use App\Repository\TrickRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TrickController extends AbstractController
{
    #[Route('/trick', name: 'app_trick_list')]
    public function list(TrickRepository $trickRepository): Response
    {
        $tricks = $trickRepository->findAll();

        return $this->render('trick/list.html.twig', [
            'tricks' => $tricks,
        ]);
    }

    #[Route('/trick/{slug}', name: 'app_trick_detail')]
    public function detail(Trick $trick): Response
    {
        return $this->render('trick/detail.html.twig', [
            'trick' => $trick,
        ]);
    }

    #[Route('/trick/edit', name: 'app_trick_edit')]
    public function edit(Request $request, Trick $trick): Response
    {

        return $this->render('trick/create.html.twig', [
        ]);
    }

    #[Route('/trick/delete', name: 'app_trick_delete')]
    public function delete(Trick $trick): Response
    {
        return $this->redirectToRoute('trick_list');
    }
}
