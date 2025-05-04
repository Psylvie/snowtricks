<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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

    /**
     * @throws \Exception
     */
    #[Route('/user/edit', name: 'app_user_edit')]
    public function editProfile(Request $request, PictureService $pictureService, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour éditer votre profil.');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('danger', 'Le mot de passe actuel est incorrect.');

                return $this->redirectToRoute('app_user_edit');
            }
            $profileImage = $form->get('profileImage')->getData();
            if ($profileImage) {
                $fileName = $pictureService->addPicture($profileImage, 'ProfileImages', 250, 250, 'profileImages');
                $user->setProfileImage($fileName);
            }
            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_user_edit');
        }

        return $this->render('user/profileEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
