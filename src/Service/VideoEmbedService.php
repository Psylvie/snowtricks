<?php

namespace App\Service;

class VideoEmbedService
{
    private array $platforms = [
        'youtube' => [
            'pattern' => '/(?:youtube\.com\/(?:embed\/|v\/|watch\?v=)|youtu\.be\/)([^\/?#&]+)/',
            'embed_url' => 'https://www.youtube.com/embed/$1',
        ],
        'dailymotion' => [
            'pattern' => '/(?:dailymotion\.com\/video\/|dai\.ly\/)([^\s&]+)/',
            'embed_url' => 'https://www.dailymotion.com/embed/video/$1',
        ],
        'vimeo' => [
            'pattern' => '/vimeo\.com\/([0-9]+)/',
            'embed_url' => 'https://player.vimeo.com/video/$1',
        ],
        'tiktok' => [
            'pattern' => '/tiktok\.com\/@[^\/]+\/video\/([0-9]+)/',
            'embed_url' => 'https://www.tiktok.com/embed/v2/$1',
        ],
        'instagram' => [
            'pattern' => '/instagram\.com\/p\/([^\/]+)/',
            'embed_url' => 'https://www.instagram.com/p/$1/embed/',
        ],
    ];

    public function getEmbedUrl(string $videoLink): ?string
    {
        foreach ($this->platforms as $data) {
            if (preg_match($data['pattern'], $videoLink, $matches)) {
                return str_replace('$1', $matches[1], $data['embed_url']);
            }
        }

        return null;
    }

    public function validateVideoLink(string $videoLink): bool
    {
        foreach ($this->platforms as $data) {
            if (preg_match($data['pattern'], $videoLink)) {
                return true;
            }
        }

        return false;
    }

    public function sanitizeUrl(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}
