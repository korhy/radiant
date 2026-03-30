<?php

namespace App\Entity;

use App\Repository\AppRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppRepository::class)]
class App
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 100)]
    private ?string $label = null;

    #[ORM\Column(length: 100)]
    private ?string $route = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?array $techStack = null;

    #[ORM\Column(nullable: true)]
    private ?array $challenges = null;

    #[ORM\Column(nullable: true)]
    private ?array $improvements = null;

    #[ORM\Column(nullable: true)]
    private ?array $resources = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(string $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTechStack(): ?array
    {
        return $this->techStack;
    }

    public function setTechStack(?array $techStack): static
    {
        $this->techStack = $techStack;

        return $this;
    }

    public function getChallenges(): ?array
    {
        return $this->challenges;
    }

    public function setChallenges(?array $challenges): static
    {
        $this->challenges = $challenges;

        return $this;
    }

    public function getImprovements(): ?array
    {
        return $this->improvements;
    }

    public function setImprovements(?array $improvements): static
    {
        $this->improvements = $improvements;

        return $this;
    }

    public function getResources(): ?array
    {
        return $this->resources;
    }

    public function setResources(?array $resources): static
    {
        $this->resources = $resources;

        return $this;
    }

    public function getJsonTechStack(): ?string
    {
        return json_encode($this->techStack, JSON_PRETTY_PRINT);
    }

    public function setJsonTechStack(?string $json): static
    {
        $this->techStack = $json ? json_decode($json, true) : null;

        return $this;
    }

    public function getJsonChallenges(): ?string
    {
        return json_encode($this->challenges, JSON_PRETTY_PRINT);
    }

    public function setJsonChallenges(?string $json): static
    {
        $this->challenges = $json ? json_decode($json, true) : null;

        return $this;
    }

    public function getJsonImprovements(): ?string
    {
        return json_encode($this->improvements, JSON_PRETTY_PRINT);
    }

    public function setJsonImprovements(?string $json): static
    {
        $this->improvements = $json ? json_decode($json, true) : null;

        return $this;
    }

    public function getJsonResources(): ?string
    {
        return json_encode($this->resources, JSON_PRETTY_PRINT);
    }

    public function setJsonResources(?string $json): static
    {
        $this->resources = $json ? json_decode($json, true) : null;

        return $this;
    }
}
