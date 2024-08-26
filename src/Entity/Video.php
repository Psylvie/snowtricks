<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use App\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
class Video
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $videolink = null;

    #[ORM\ManyToOne(inversedBy: 'videos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Trick $trick = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->trick = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVideolink(): ?string
    {
        return $this->videolink;
    }

    public function setVideolink(string $videolink): static
    {
        $this->videolink = $videolink;

        return $this;
    }

    public function getTrick(): ?Trick
    {
        return $this->trick;
    }

    public function setTrick(?Trick $trick): static
    {
        $this->trick = $trick;

        return $this;
    }
}
