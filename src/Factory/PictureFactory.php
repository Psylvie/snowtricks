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
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
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
            'filename' => $this->getRandomTrickImage(),
            'trick' => self::faker()->randomElement(TrickFactory::repository()->findAll()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
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
        $sourceImagePath = __DIR__.'/../../public/uploads/TrickPictures/'.$filename;
        $destinationImagePath = $directory.'/'.$filename;

        if (file_exists($sourceImagePath)) {
            copy($sourceImagePath, $destinationImagePath);
        }
    }

    /**
     * @throws \Exception
     */
    private function getRandomTrickImage(): string
    {
        $imageDirectory = __DIR__.'/../../public/uploads/TrickPictures';
        $finder = new Finder();
        $finder->files()->in($imageDirectory)->name('*.{jpg,jpeg,png,gif}');

        $imageFiles = iterator_to_array($finder);

        if (empty($imageFiles)) {
            throw new \Exception('No images found in directory: '.$imageDirectory);
        }
        $randomImage = $imageFiles[array_rand($imageFiles)];

        return $randomImage->getFilename();
    }
}
