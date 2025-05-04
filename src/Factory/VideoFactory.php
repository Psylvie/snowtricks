<?php

namespace App\Factory;

use App\Entity\Video;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Video>
 */
final class VideoFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return Video::class;
    }

    protected function defaults(): array|callable
    {
        $createdAt = self::faker()->dateTimeThisYear();

        $youtubeIds = [
            'LAuDJsReHlw',
            'cPeCbmHIGEE',
            'UOLsXjoQbGc',
            '8KotvBY28Mo',
            'KWLg5-5RP18',
            't705_V-RDcQ',
        ];

        $videoId = $youtubeIds[array_rand($youtubeIds)];
        $videoLink = 'https://www.youtube.com/embed/'.$videoId;

        $randomTrick = TrickFactory::random();

        return [
            'createdAt' => $createdAt,
            'updatedAt' => self::faker()->dateTimeBetween($createdAt, 'now'),
            'trick' => $randomTrick,
            'videolink' => $videoLink,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
