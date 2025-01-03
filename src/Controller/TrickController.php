<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Picture;
use App\Entity\Trick;
use App\Entity\Video;
use App\Form\CommentType;
use App\Form\TrickType;
use App\Repository\CommentRepository;
use App\Repository\TrickRepository;
use App\Service\LoadMoreService;
use App\Service\MediaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TrickController extends AbstractController
{
    public function __construct(
        private readonly LoadMoreService $loadMoreService,
        private readonly EntityManagerInterface $em,
        private readonly MediaService $mediaService,
        private readonly CommentRepository $commentRepository
    ) {
    }

    /**
     * List all tricks.
     */
    #[Route('/tricks', name: 'app_tricks_list')]
    public function list(): Response
    {
        $tricks = $this->em->getRepository(Trick::class)->findBy([], ['createdAt' => 'DESC'], 15);

        return $this->render('trick/list.html.twig', [
            'tricks' => $tricks,
        ]);
    }

    /**
     * button to load more tricks.
     *
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/tricks/load-more/{offset}', name: 'app_trick_load_more')]
    public function loadMore(int $offset): JsonResponse
    {
        $data = $this->loadMoreService->loadItems(
            Trick::class,
            $offset,
            5,
            'trick/partials/_tricks_list.html.twig',
            'tricks');

        return new JsonResponse($data);
    }

    /**
     * Show a trick.
     */
    #[Route('/trick/{slug}', name: 'app_trick_show')]
    public function show(
        string $slug,
        TrickRepository $trickRepository
    ): Response {
        $trick = $trickRepository->findOneBy(['slug' => $slug]);

        if (!$trick) {
            $this->addFlash('error', 'Trick introuvable.');

            return $this->redirectToRoute('app_tricks_list');
        }
        $mainPicture = $trick->getMainPicture();
        $isEditing = false;

        $comments = $this->commentRepository->findBy(
            ['trick' => $trick],
            ['createdAt' => 'DESC'],
            10);
        $totalComments = $this->commentRepository->count(['trick' => $trick]);
        $hasMore = $totalComments > 3;
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
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

            $this->em->persist($trick);
            $this->em->flush();

            $this->addFlash('success', 'Le trick a bien été ajouté.');

            return $this->redirectToRoute('app_tricks_list');
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
    public function edit(
        Request $request,
        string $slug,
        SluggerInterface $slugger
    ): Response {
        $response = ['success' => false];
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['slug' => $slug]);
        if (!$trick) {
            $this->addFlash('error', 'Trick introuvable.');

            return $this->redirectToRoute('app_tricks_list');
        }
        if ($request->isXmlHttpRequest()) {
            $data = json_decode($request->getContent(), true);

            if (!empty($data['delete_picture_id'])) {
                $picture = $this->em->getRepository(Picture::class)->find($data['delete_picture_id']);
                if ($picture) {
                    $this->mediaService->deletePicture($picture);

                    if ($picture === $trick->getMainPicture()) {
                        $remainingPictures = $trick->getPictures();
                        $newMainPicture = $remainingPictures->first();
                        $trick->setMainPicture($newMainPicture);
                        $this->em->flush();
                    }

                    $response['success'] = true;
                }
            }

            if (!empty($data['delete_video_id'])) {
                $video = $this->em->getRepository(Video::class)->find($data['delete_video_id']);
                if ($video) {
                    $response['success'] = $this->mediaService->deleteVideo($video);
                }
            }

            return new JsonResponse($response);
        }
        $pictureId = $request->request->get('pictureId');
        $newImageFile = $request->files->get('newImage');
        $isMainPicture = $request->request->get('isMainPicture');

        if ($newImageFile && $pictureId) {
            $picture = $this->em->getRepository(Picture::class)->find($pictureId);

            if ($picture) {
                try {
                    $this->mediaService->updatePicture($picture, $newImageFile);

                    if ($isMainPicture) {
                        $trick->setMainPicture($picture);
                        $this->em->flush();
                    }

                    $this->addFlash('success', 'Image mise à jour avec succès !');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la mise à jour de l\'image : '.$e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Image introuvable.');
            }
        }

        $videoId = $request->request->get('videoId');
        $newVideoLink = $request->request->get('newVideoLink');
        if ($newVideoLink) {
            $video = $this->em->getRepository(Video::class)->find($videoId);

            if ($video) {
                try {
                    $this->mediaService->updateVideo($video, $newVideoLink);
                    $this->addFlash('success', 'Vidéo mise à jour avec succès !');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la mise à jour de la vidéo : '.$e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Vidéo introuvable.');
            }
        }

        $form = $this->createForm(TrickType::class, $trick, ['is_edit' => true]);
        $form->handleRequest($request);

        $mainPicture = $trick->getPictures()->first();

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);
            $trick->setUpdatedAt(new \DateTimeImmutable());
            $picturesData = $form->get('pictures')->getData();
            $videoData = $form->get('videos')->getData();

            $this->mediaService->handlePictures($trick, $picturesData);

            $this->mediaService->handleVideos($trick, $videoData);
            $this->em->flush();
            $this->addFlash('success', 'Le trick a été mis à jour avec succès !');

            return $this->redirectToRoute('app_tricks_list');
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

        return $this->redirectToRoute('app_tricks_list');
    }
}
