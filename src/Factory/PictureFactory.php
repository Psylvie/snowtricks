<?php

namespace App\Factory;

use App\Entity\Picture;
use Symfony\Component\Finder\Finder;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Picture>
 */
final class PictureFactory extends PersistentProxyObjectFactory
{
    private const SOURCE_IMAGE_DIRECTORY = __DIR__.'/../../public/images';
    private const DESTINATION_IMAGE_DIRECTORY = __DIR__.'/../../public/uploads/TrickPictures';

    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return Picture::class;
    }

    /**
     * @throws \Exception
     */
    protected function defaults(): array|callable
    {
        $createdAt = self::faker()->dateTimeThisYear();

        return [
            'createdAt' => $createdAt,
            'updatedAt' => self::faker()->dateTimeBetween($createdAt, 'now'),
            'filename' => self::getRandomImageFilename(),
            'trick' => self::faker()->randomElement(TrickFactory::repository()->findAll()),
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Picture $picture): void {
            $imageDirectory = __DIR__.'/../../public/uploads/trickPictures';
            $this->copyImage($imageDirectory, $picture->getFilename());
        });
    }

    private function copyImage(string $directory, string $filename): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $sourceImagePath = self::SOURCE_IMAGE_DIRECTORY.'/'.$filename;
        $destinationImagePath = $directory.'/'.$filename;

        if (file_exists($sourceImagePath)) {
            copy($sourceImagePath, $destinationImagePath);
        }
    }

    /**
     * @throws \Exception
     */
    private static function getRandomImageFilename(): string
    {
        $finder = new Finder();
        $finder->files()->in(self::SOURCE_IMAGE_DIRECTORY)->name('/\.(jpg|jpeg|png|gif)$/i');

        $imageFiles = iterator_to_array($finder);

        if (empty($imageFiles)) {
            throw new \Exception('No images found in directory: '.self::SOURCE_IMAGE_DIRECTORY);
        }
        $randomImage = $imageFiles[array_rand($imageFiles)];

        return $randomImage->getFilename();
    }
}
