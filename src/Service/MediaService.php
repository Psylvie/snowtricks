<?php

namespace App\Service;

use App\Entity\Picture;
use App\Entity\Trick;
use App\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

readonly class MediaService
{
    public function __construct(
        private EntityManagerInterface $em,
        private PictureService $pictureService,
        private VideoEmbedService $videoEmbedService,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function handlePictures(Trick $trick, array $pictures): void
    {
        foreach ($pictures as $picture) {
            if ($picture instanceof UploadedFile) {
                $fileName = $this->pictureService->addPicture($picture, 'TrickPictures');
                $pictureEntity = new Picture();
                $pictureEntity->setFilename($fileName);
                $pictureEntity->setTrick($trick);
                $trick->getPictures()->add($pictureEntity);
                $this->em->persist($pictureEntity);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function handleVideos(Trick $trick, $videos): void
    {
        foreach ($videos as $video) {
            if ($video instanceof Video) {
                $videoLink = $video->getVideoLink();
                $sanitizedUrl = $this->videoEmbedService->sanitizeUrl($videoLink);

                if ($this->videoEmbedService->validateVideoLink($sanitizedUrl)) {
                    $embedUrl = $this->videoEmbedService->getEmbedUrl($sanitizedUrl);
                    if ($embedUrl) {
                        $video->setVideoLink($embedUrl);
                        $video->setTrick($trick);
                        $this->em->persist($video);
                    }
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function updatePicture(Picture $picture, UploadedFile $newImageFile): bool
    {
        try {
            if ($picture->getFilename()) {
                $this->pictureService->deletePicture($picture->getFilename(), '/TrickPictures');
            }

            $folder = '/TrickPictures';
            $newFilename = $this->pictureService->addPicture($newImageFile, $folder);
            $picture->setFilename($newFilename);

            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la mise à jour de l\'image : '.$e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public function updateVideo(Video $video, string $newVideoLink): void
    {
        $sanitizedUrl = $this->videoEmbedService->sanitizeUrl($newVideoLink);

        if ($this->videoEmbedService->validateVideoLink($sanitizedUrl)) {
            $embedUrl = $this->videoEmbedService->getEmbedUrl($sanitizedUrl);

            if ($embedUrl) {
                $video->setVideolink($embedUrl);
                $this->em->flush();
            } else {
                throw new \Exception('Erreur lors de la génération de l\'URL d\'intégration.');
            }
        } else {
            throw new \Exception('Le lien vidéo est invalide.');
        }
    }

    public function deletePicture(Picture $picture): bool
    {
        if ($picture->getFilename()) {
            $this->pictureService->deletePicture($picture->getFilename(), '/TrickPictures');
        }
        $this->em->remove($picture);
        $this->em->flush();

        return true;
    }

    public function deleteVideo(Video $video): bool
    {
        $this->em->remove($video);
        $this->em->flush();

        return true;
    }

    public function handleDeletion(Request $request, Trick $trick): JsonResponse
    {
        $response = ['success' => false];
        $data = json_decode($request->getContent(), true);

        if (!empty($data['delete_picture_id'])) {
            $picture = $this->em->getRepository(Picture::class)->find($data['delete_picture_id']);
            if ($picture && $picture->getTrick() === $trick) {
                $this->deletePicture($picture);
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
            if ($video && $video->getTrick() === $trick) {
                $this->deleteVideo($video);
                $response['success'] = true;
            }
        }

        return new JsonResponse($response);
    }

    /**
     * @throws \Exception
     */
    public function handleExistingMediaUpdates(Request $request, Trick $trick): void
    {
        $pictureId = $request->request->get('pictureId');
        $newImageFile = $request->files->get('newImage');
        $isMainPicture = $request->request->get('isMainPicture');

        if ($newImageFile && $pictureId) {
            $picture = $this->em->getRepository(Picture::class)->find($pictureId);
            if ($picture && $picture->getTrick() === $trick) {
                try {
                    $this->updatePicture($picture, $newImageFile);
                    if ($isMainPicture) {
                        $trick->setMainPicture($picture);
                        $this->em->flush();
                    }
                } catch (\Exception $e) {
                    throw new \Exception('Erreur lors de la mise à jour de l\'image : '.$e->getMessage());
                }
            }
        }

        $videoId = $request->request->get('videoId');
        $newVideoLink = $request->request->get('newVideoLink');
        if ($newVideoLink) {
            $video = $this->em->getRepository(Video::class)->find($videoId);
            if ($video && $video->getTrick() === $trick) {
                try {
                    $this->updateVideo($video, $newVideoLink);
                } catch (\Exception $e) {
                    throw new \Exception('Erreur lors de la mise à jour de la vidéo : '.$e->getMessage());
                }
            }
        }
    }

}
