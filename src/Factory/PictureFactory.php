<?php

namespace App\Factory;

use App\Entity\Picture;
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

    protected function defaults(): array|callable
    {
        $createdAt = self::faker()->dateTimeThisYear();

        return [
            'createdAt' => $createdAt,
            'updatedAt' => self::faker()->dateTimeBetween($createdAt, 'now'),
            'filename' => self::faker()->unique()->word().'.jpg',
            'trick' => self::faker()->randomElement(TrickFactory::repository()->findAll()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Picture $picture): void {
            $imageDirectory = __DIR__.'/../../public/uploads/images';
            $this->saveFakeImage($imageDirectory, $picture->getFilename());
        });
    }

    private function saveFakeImage(string $directory, string $filename): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $imagePath = $directory.'/'.$filename;
        $imageContent = self::faker()->image(null, 800, 600, 'snowTricks', true, true, 'Faker', true);

        file_put_contents($imagePath, $imageContent);
    }
}
