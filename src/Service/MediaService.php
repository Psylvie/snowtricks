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
    public function handlePictures(array $picturesData, Trick $trick, string $folder, bool $isMain = false): void
    {
        foreach ($picturesData as $picture) {
            if ($picture instanceof UploadedFile) {
                $fileName = $this->pictureService->addPicture($picture, $folder);

                $pictureEntity = new Picture();
                $pictureEntity->setFilename($fileName);
                $pictureEntity->setTrick($trick);

                if ($isMain) {
                    $mainPicture = $trick->getPictures()->first();
                    if ($mainPicture) {
                        $this->deletePicture($mainPicture);
                    }

                    $trick->getPictures()->clear();
                    $trick->getPictures()->add($pictureEntity);
                } else {
                    $trick->getPictures()->add($pictureEntity);
                }

                $this->em->persist($pictureEntity);
            }
        }

        $this->em->flush();
    }

    /**
     * @throws \Exception
     */
    public function handleVideos(array $videosData, Trick $trick): void
    {
        foreach ($videosData as $video) {
            if ($video instanceof Video) {
                $sanitizedUrl = $this->videoEmbedService->sanitizeUrl($video->getVideoLink());

                if ($this->videoEmbedService->validateVideoLink($sanitizedUrl)) {
                    $embedUrl = $this->videoEmbedService->getEmbedUrl($sanitizedUrl);

                    $videoEntity = new Video();
                    $videoEntity->setVideoLink($embedUrl);
                    $videoEntity->setTrick($trick);

                    $trick->getVideos()->add($videoEntity);
                    $this->em->persist($videoEntity);
                } else {
                    throw new \Exception('Une ou plusieurs vidéos ne sont pas valides.');
                }
            }
        }
        $this->em->flush();
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
