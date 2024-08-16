<?php

namespace App\Controller;

use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    #[Route('/user', name: 'app_user')]
    public function show(): Response
    {
        return $this->render('user/profileShow.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/user/edit', name: 'app_user_edit')]
    public function editProfile(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour éditer votre profil.');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_user_edit');
        }

        return $this->render('user/profileEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/delete', name: 'app_user_delete')]
    public function delete(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof UserInterface) {
            throw new AccessDeniedException('Vous devez être connecté pour supprimer votre profil.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$user->getId(), $token)) {
            throw new AccessDeniedException('Le jeton CSRF est invalide.');
        }

        $this->em->remove($user);
        $this->em->flush();

        $this->addFlash('success', 'Votre profil a été supprimé avec succès.');

        return $this->redirectToRoute('app_logout');
    }
}
