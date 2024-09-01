<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureService
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @throws \Exception
     */
    public function addPicture(
        UploadedFile $picture,
        ?string $folder = '',
        ?int $width = 250,
        ?int $height = 250): string
    {
        $extension = $picture->guessExtension();
        $validExtensions = ['jpeg', 'jpg', 'png', 'webp'];

        if (!in_array($extension, $validExtensions)) {
            throw new \InvalidArgumentException('Invalid file format');
        }

        $fileName = md5(uniqid()).'.'.$extension;

        $uploadPath = $this->params->get('pictures_directory').$folder;
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $this->resizeAndSaveImage($picture->getPathname(), $uploadPath.'/'.$fileName, $width, $height, $extension);

        $picture->move($uploadPath, $fileName);

        return $fileName;
    }

    /**
     * @throws \Exception
     */
    private function resizeAndSaveImage(string $sourcePath, string $destinationPath, int $width, int $height, string $extension): void
    {
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $image = imagecreatefrompng($sourcePath);
                break;
            case 'webp':
                $image = imagecreatefromwebp($sourcePath);
                break;
            default:
                throw new \Exception('Unsupported image format');
        }

        $resizedImage = imagecreatetruecolor($width, $height);

        if ('png' === $extension || 'webp' === $extension) {
            imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
        }

        list($originalWidth, $originalHeight) = getimagesize($sourcePath);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                imagejpeg($resizedImage, $destinationPath);
                break;
            case 'png':
                imagepng($resizedImage, $destinationPath);
                break;
            case 'webp':
                imagewebp($resizedImage, $destinationPath);
                break;
        }
        imagedestroy($image);
        imagedestroy($resizedImage);
    }

    public function deletePicture(string $filename, ?string $folder = ''): bool
    {
        $filePath = $this->params->get('pictures_directory').$folder.'/'.$filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }
}
