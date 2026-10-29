<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $candidat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvFilename = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Job $job = null;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getCandidat(): ?string { return $this->candidat; }
    public function setCandidat(string $candidat): static { $this->candidat = $candidat; return $this; }

    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(?string $contenu): static { $this->contenu = $contenu; return $this; }

    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): static { $this->date = $date; return $this; }

    public function getCvFilename(): ?string { return $this->cvFilename; }
    public function setCvFilename(?string $cvFilename): static { $this->cvFilename = $cvFilename; return $this; }

    public function getJob(): ?Job { return $this->job; }
    public function setJob(?Job $job): static { $this->job = $job; return $this; }
}