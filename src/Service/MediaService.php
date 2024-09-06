<?php

namespace App\Service;

use App\Entity\Picture;
use App\Entity\Trick;
use App\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PictureService $pictureService,
        private readonly VideoEmbedService $videoEmbedService
    ) {
    }

    /**
     * @throws \Exception
     */
    public function handlePictures(Trick $trick, array $pictures): void
    {
        foreach ($pictures as $picture) {
            if ($picture instanceof UploadedFile) {
                $fileName = $this->pictureService->addPicture($picture, 'TrickPictures', 250, 250);
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
    public function handleVideos(Trick $trick, array $videos): void
    {
        foreach ($videos as $video) {
            if ($video instanceof Video) {
                $videoLink = $video->getVideoLink();
                $sanitizedUrl = $this->videoEmbedService->sanitizeUrl($videoLink);
                $video->setVideoLink($sanitizedUrl);
                $video->setTrick($trick);
                $this->em->persist($video);
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
}
