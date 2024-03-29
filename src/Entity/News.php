<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
class News
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(nullable: true, options: [
        "default" => 666,
    ])]
    private ?int $predictRatingV1 = 666;

    #[ORM\Column(type: Types::TEXT, unique: true)]
    private ?string $url = null;

    #[ORM\Column(nullable: true, options: [
        "default" => 666,
    ])]
    private ?int $predictRatingV2 = 666;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $category = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getPredictRatingV1(): ?int
    {
        return $this->predictRatingV1;
    }

    public function setPredictRatingV1(?int $predictRatingV1): self
    {
        $this->predictRatingV1 = $predictRatingV1;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getPredictRatingV2(): ?int
    {
        return $this->predictRatingV2;
    }

    public function setPredictRatingV2(?int $predictRatingV2): self
    {
        $this->predictRatingV2 = $predictRatingV2;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }
}
