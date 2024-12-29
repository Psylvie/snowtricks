<?php

namespace App\Factory;

use App\Entity\Video;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Video>
 */
final class VideoFactory extends PersistentProxyObjectFactory
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
        return Video::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $createdAt = self::faker()->dateTimeThisYear();

        $videoUrls = [
            'https://youtu.be/t705_V-RDcQ?si=WqUdk6QhVOeqzNQw',
            'https://youtu.be/KZsvhTJ7tEk?si=ffHFtGke_6TJJvdp',
            'https://youtu.be/Ziw-ZXFlkW4?si=clZTZ_eQhEwYLBlX',
            'https://youtu.be/LAuDJsReHlw?si=L9bmHlgT7eh4jlzk',
            'https://youtu.be/OoYz6w8uecE?si=SCrZxlpfFd0C975K',
        ];
        $videoLink = $videoUrls[array_rand($videoUrls)];
        return [
            'createdAt' => $createdAt,
            'updatedAt' => self::faker()->dateTimeBetween($createdAt, 'now'),
            'trick' => self::faker()->randomElement(TrickFactory::repository()->findAll()),
            'videolink' => $videoLink,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Video $video): void {})
        ;
    }
}
