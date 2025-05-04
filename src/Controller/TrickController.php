<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\CommentType;
use App\Form\TrickType;
use App\Repository\CommentRepository;
use App\Repository\TrickRepository;
use App\Service\MediaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class TrickController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MediaService $mediaService,
        private readonly CommentRepository $commentRepository,
    ) {
    }


    /**
     * Show a trick.
     */
    #[Route('/trick/{slug}', name: 'app_trick_show')]
    public function show(
        string $slug,
        TrickRepository $trickRepository,
        Request $request,
    ): Response {
        $trick = $trickRepository->findOneBy(['slug' => $slug]);

        if (!$trick) {
            $this->addFlash('error', 'Trick introuvable.');

            return $this->redirectToRoute('app_home');
        }
        $mainPicture = $trick->getMainPicture();
        $isEditing = false;

        $page = $request->query->getInt('page', 1);
        $comments = $this->commentRepository->findPaginatedComments($page, 10, $trick->getId());
        $totalComments = $this->commentRepository->countPaginatedComments($trick->getId());

        $hasMore = $totalComments > ($page * 10);
        $commentForm = $this->createForm(CommentType::class, new Comment());

        return $this->render('trick/show.html.twig', [
            'trick' => $trick,
            'comments' => $comments,
            'commentForm' => $commentForm->createView(),
            'mainPicture' => $mainPicture,
            'isEditing' => $isEditing,
            'hasMore' => $hasMore,
        ]);
    }

    /**
     * Create a new trick.
     *
     * @throws \Exception
     */
    #[Route('/newTrick', name: 'app_trick_newTrick')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(
        Request $request,
        TrickRepository $trickRepository,
        SluggerInterface $slugger): Response
    {
        $trick = new Trick();
        $trickForm = $this->createForm(TrickType::class, $trick);
        $trickForm->handleRequest($request);

        if ($trickForm->isSubmitted() && $trickForm->isValid()) {
            $existingTrick = $trickRepository->findOneBy(['name' => $trick->getName()]);
            if ($existingTrick) {
                $this->addFlash('error', 'Une figure avec ce nom existe déjà.');

                return $this->redirectToRoute('app_trick_new');
            }

            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);
            $trick->setUser($this->getUser());

            $pictures = $trickForm->get('pictures')->getData();
            $this->mediaService->handlePictures($trick, $pictures);

            $videoData = $trickForm->get('videos')->getData();
            $this->mediaService->handleVideos($trick, $videoData);

            $this->em->persist($trick);
            $this->em->flush();
            $this->addFlash('success', 'Le trick a bien été ajouté.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('trick/create.html.twig', [
            'form' => $trickForm->createView(),
            'isEditing' => false,
        ]);
    }

    /**
     * Edit a trick.
     *
     * @throws \Exception
     */
    #[Route('/trick/edit/{slug}', name: 'app_trick_edit')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(
        Request $request,
        string $slug,
        SluggerInterface $slugger,
    ): Response {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['slug' => $slug]);
        if (!$trick) {
            $this->addFlash('error', 'Trick introuvable.');

            return $this->redirectToRoute('app_home');
        }
        if ($request->isXmlHttpRequest()) {
            return $this->mediaService->handleDeletion($request, $trick);
        }
        try {
            $this->mediaService->handleExistingMediaUpdates($request, $trick);
            if ($request->request->get('newImage') && $request->request->get('pictureId')) {
                $this->addFlash('success', 'Image mise à jour avec succès !');
            }
            if ($request->request->get('newVideoLink') && $request->request->get('videoId')) {
                $this->addFlash('success', 'Vidéo mise à jour avec succès !');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $form = $this->createForm(TrickType::class, $trick, ['is_edit' => true]);
        $form->handleRequest($request);

        $mainPicture = $trick->getPictures()->first();

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);
            $picturesData = $form->get('pictures')->getData();
            $videoData = $form->get('videos')->getData();

            $this->mediaService->handlePictures($trick, $picturesData);

            $this->mediaService->handleVideos($trick, $videoData);
            $trick->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->addFlash('success', 'Le trick a été mis à jour avec succès !');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('trick/edit.html.twig', [
            'form' => $form->createView(),
            'trick' => $trick,
            'mainPicture' => $mainPicture,
            'isEditing' => true,
        ]);
    }

    /**
     * Delete a trick.
     *
     * @throws \Exception
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/trick/delete/{id}', name: 'app_trick_delete', methods: ['GET'])]
    public function delete(Trick $trick): Response
    {
        $this->em->remove($trick);
        $this->em->flush();

        $this->addFlash('success', 'Le trick a été supprimé avec succès !');

        return $this->redirectToRoute('app_home');
    }
}
