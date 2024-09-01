<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Entity\Trick;
use App\Entity\Video;
use App\Form\TrickType;
use App\Repository\TrickRepository;
use App\Service\LoadMoreService;
use App\Service\MediaService;
use App\Service\PictureService;
use App\Service\VideoEmbedService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        private readonly PictureService $pictureService,
        private readonly VideoEmbedService $videoEmbedService,
        private readonly MediaService $mediaService
    ) {
    }

    #[Route('/trick', name: 'app_trick_list')]
    public function list(): Response
    {
        $tricks = $this->em->getRepository(Trick::class)->findBy([], ['createdAt' => 'DESC', 'id' => 'ASC'], 15);

        return $this->render('trick/list.html.twig', [
            'tricks' => $tricks,
        ]);
    }

    /**
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
            'trick/_tricks_list.html.twig',
            'tricks');

        return new JsonResponse($data);
    }

//    #[Route('/trick/{slug}', name: 'app_trick_detail')]
//    public function detail(): Response
//    {
//        return $this->render('trick/detail.html.twig', [
//        ]);
//    }

    /**
     * @throws \Exception
     */
    #[Route('/trick/new', name: 'app_trick_new')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
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
            $em->persist($trick);
            $pictures = $trickForm->get('pictures')->getData();
            if (is_array($pictures)) {
                foreach ($pictures as $picture) {
                    if ($picture instanceof UploadedFile) {
                        $fileName = $this->pictureService->addPicture($picture, 'trickPictures', 250, 250);
                        $pictureEntity = new Picture();
                        $pictureEntity->setFilename($fileName);
                        $pictureEntity->setTrick($trick);
                        $trick->getPictures()->add($pictureEntity);
                        $em->persist($pictureEntity);
                    }
                }
            }
            $videos = $trickForm->get('videos')->getData();
            foreach ($videos as $video) {
                $videoLink = $video->getVideoLink();
                $sanitizedUrl = $this->videoEmbedService->sanitizeUrl($videoLink);

                if ($this->videoEmbedService->validateVideoLink($sanitizedUrl)) {
                    $embedUrl = $this->videoEmbedService->getEmbedUrl($sanitizedUrl);

                    $video->setVideolink($embedUrl);
                    $video->setTrick($trick);
                    $em->persist($video);
                } else {
                    $this->addFlash('error', 'Une ou plusieurs vidéos ne sont pas valides.');

                    return $this->redirectToRoute('app_trick_new');
                }
            }

            $em->flush();

            $this->addFlash('success', 'Le trick a bien été ajouté.');

            return $this->redirectToRoute('app_trick_list');
        }

        return $this->render('trick/create.html.twig', [
            'form' => $trickForm->createView(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/trick/edit/{id}', name: 'app_trick_edit')]
    public function edit(Request $request, Trick $trick): Response
    {
        $response = ['success' => false];

        if ($request->isXmlHttpRequest()) {
            $data = json_decode($request->getContent(), true);

            //            if (!empty($data['delete_main_picture'])) {
            //                $mainPicture = $trick->getPictures()->first();
            //                if ($mainPicture) {
            //                    $response['success'] = $this->mediaService->deletePicture($mainPicture);
            //                }
            //            }

            if (!empty($data['delete_picture_id'])) {
                $picture = $this->em->getRepository(Picture::class)->find($data['delete_picture_id']);
                if ($picture) {
                    $response['success'] = $this->mediaService->deletePicture($picture);
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
        //        $newImageFile = $request->files->get('newImage');
        //        if ($newImageFile instanceof UploadedFile) {
        //            try {
        //                $this->mediaService->handlePictures([$newImageFile], $trick, 'TrickPictures', true);
        //                $this->addFlash('success', 'Image principale ajoutée avec succès !');
        //            } catch (\Exception $e) {
        //                $this->addFlash('error', 'Erreur lors de l\'ajout de l\'image principale : '.$e->getMessage());
        //            }
        //        }

        $pictureId = $request->request->get('pictureId');
        $newImageFile = $request->files->get('newImage');

        if ($newImageFile) {
            $picture = $this->em->getRepository(Picture::class)->find($pictureId);

            if ($picture) {
                if ($picture->getFilename()) {
                    $this->pictureService->deletePicture($picture->getFilename(), '/TrickPictures');
                }

                $folder = '/TrickPictures';

                try {
                    $newFilename = $this->pictureService->addPicture($newImageFile, $folder);
                    $picture->setFilename($newFilename);
                    $this->em->flush();

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
                $sanitizedUrl = $this->videoEmbedService->sanitizeUrl($newVideoLink);

                if ($this->videoEmbedService->validateVideoLink($sanitizedUrl)) {
                    $embedUrl = $this->videoEmbedService->getEmbedUrl($sanitizedUrl);

                    if ($embedUrl) {
                        $video->setVideolink($embedUrl);
                        $this->em->flush();

                        $this->addFlash('success', 'Vidéo mise à jour avec succès !');
                    } else {
                        $this->addFlash('error', 'Erreur lors de la génération de l\'URL d\'intégration.');
                    }
                } else {
                    $this->addFlash('error', 'Le lien vidéo est invalide.');
                }
            } else {
                $this->addFlash('error', 'Vidéo introuvable.');
            }
        }

        $form = $this->createForm(TrickType::class, $trick, ['is_edit' => true]);
        $form->handleRequest($request);

        $mainPicture = $trick->getPictures()->first();

        if ($form->isSubmitted() && $form->isValid()) {
            $picturesData = $form->get('pictures')->getData();
            $videoData = $form->get('videos')->getData();

            try {
                $this->mediaService->handlePictures($picturesData, $trick, 'TrickPictures');
                $this->mediaService->handleVideos($videoData, $trick);
                $this->em->flush();

                $this->addFlash('success', 'Le trick a été mis à jour avec succès !');

                return $this->redirectToRoute('app_trick_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour : '.$e->getMessage());

                return $this->redirectToRoute('app_trick_edit', ['id' => $trick->getId()]);
            }
        }

        return $this->render('trick/edit.html.twig', [
            'form' => $form->createView(),
            'trick' => $trick,
            'mainPicture' => $mainPicture,
        ]);
    }

    /**
     * @throws \Exception
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/trick/delete/{id}', name: 'app_trick_delete', methods: ['GET'])]
    public function delete(Trick $trick, Request $request): Response
    {
        $this->em->remove($trick);
        $this->em->flush();

        $this->addFlash('success', 'Le trick a été supprimé avec succès !');

        return $this->redirectToRoute('app_trick_list');
    }
}
