<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteContentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: SiteContentRepository::class)]
#[Vich\Uploadable]
class SiteContent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Le texte « À propos » ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $aboutText = null;

    #[Vich\UploadableField(mapping: 'cv', fileNameProperty: 'cvFileName', originalName: 'cvOriginalName')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['application/pdf'],
        maxSizeMessage: 'Le CV ne peut pas dépasser {{ limit }} {{ suffix }}.',
        mimeTypesMessage: 'Le CV doit être un fichier PDF.',
    )]
    private ?File $cvFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFileName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvOriginalName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAboutText(): ?string
    {
        return $this->aboutText;
    }

    public function setAboutText(?string $aboutText): static
    {
        $this->aboutText = $aboutText;

        return $this;
    }

    public function getCvFile(): ?File
    {
        return $this->cvFile;
    }

    /**
     * Touching `updatedAt` is what makes Doctrine see a change: the file name is written by
     * VichUploader after the changeset is computed, so without it a replacement upload is
     * silently dropped.
     */
    public function setCvFile(?File $cvFile): static
    {
        $this->cvFile = $cvFile;

        if (null !== $cvFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getCvFileName(): ?string
    {
        return $this->cvFileName;
    }

    public function setCvFileName(?string $cvFileName): static
    {
        $this->cvFileName = $cvFileName;

        return $this;
    }

    public function getCvOriginalName(): ?string
    {
        return $this->cvOriginalName;
    }

    public function setCvOriginalName(?string $cvOriginalName): static
    {
        $this->cvOriginalName = $cvOriginalName;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function hasCv(): bool
    {
        return null !== $this->cvFileName;
    }

    public function __toString(): string
    {
        return 'Site content';
    }
}
