<?php

namespace App\Entity;

use App\Repository\RecetteRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(paginationItemsPerPage: 5),
        new Get(),
        new Post(security: "is_granted('ROLE_USER')"),
        new Put(security: "object.getAuteur() === user"),
        new Delete(security: "object.getAuteur() === user"),
        new Patch(security: "object.getAuteur() === user"),
    ],
    normalizationContext: ['groups' => ['recette:read']],
    denormalizationContext: ['groups' => ['recette:write']],
)]
#[ORM\Entity(repositoryClass: RecetteRepository::class)]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['recette:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['recette:read', 'recette:write', 'categorie:read'])]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    #[Groups(['recette:read', 'recette:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['recette:read', 'recette:write'])]
    private ?string $instructions = null;

    #[ORM\Column]
    #[Groups(['recette:read', 'recette:write'])]
    private ?int $tempsPreparation = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['recette:read', 'recette:write'])]
    private ?int $tempsCuisson = null;

    #[ORM\Column(length: 255)]
    #[Groups(['recette:read', 'recette:write'])]
    private ?string $difficulte = null;

    #[ORM\Column]
    #[Groups(['recette:read', 'recette:write'])]
    private ?int $nbPersonnes = null;

    #[ORM\Column]
    #[Groups(['recette:read'])]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column]
    #[Groups(['recette:read', 'recette:write'])]
    private ?bool $publiee = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['recette:read', 'recette:write'])]
    private ?string $imageName = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['recette:read', 'recette:write'])]
    private ?CategorieRecette $categorie = null;

    /**
     * @var Collection<int, Ingredient>
     */
    #[ORM\OneToMany(targetEntity: Ingredient::class, mappedBy: 'recette',cascade: ['persist','remove'])]
    #[Groups(['recette:read', 'recette:write'])]
    private Collection $ingredients;

    /**
     * @var Collection<int, TagRecette>
     */
    #[ORM\ManyToMany(targetEntity: TagRecette::class, inversedBy: 'recettes')]
    #[Groups(['recette:read'])]
    private Collection $tags;

    #[ORM\ManyToOne(inversedBy: 'recettes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['recette:read'])]
    private ?User $auteur = null;

    public function __construct()
    {
        $this->ingredients = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function getTempsPreparation(): ?int
    {
        return $this->tempsPreparation;
    }

    public function setTempsPreparation(int $tempsPreparation): static
    {
        $this->tempsPreparation = $tempsPreparation;

        return $this;
    }

    public function getTempsCuisson(): ?int
    {
        return $this->tempsCuisson;
    }

    public function setTempsCuisson(?int $tempsCuisson): static
    {
        $this->tempsCuisson = $tempsCuisson;

        return $this;
    }

    public function getDifficulte(): ?string
    {
        return $this->difficulte;
    }

    public function setDifficulte(string $difficulte): static
    {
        $this->difficulte = $difficulte;

        return $this;
    }

    public function getNbPersonnes(): ?int
    {
        return $this->nbPersonnes;
    }

    public function setNbPersonnes(int $nbPersonnes): static
    {
        $this->nbPersonnes = $nbPersonnes;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function isPubliee(): ?bool
    {
        return $this->publiee;
    }

    public function setPubliee(bool $publiee): static
    {
        $this->publiee = $publiee;

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getCategorie(): ?CategorieRecette
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieRecette $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, Ingredient>
     */
    public function getIngredients(): Collection
    {
        return $this->ingredients;
    }

    public function addIngredient(Ingredient $ingredient): static
    {
        if (!$this->ingredients->contains($ingredient)) {
            $this->ingredients->add($ingredient);
            $ingredient->setRecette($this);
        }

        return $this;
    }

    public function removeIngredient(Ingredient $ingredient): static
    {
        if ($this->ingredients->removeElement($ingredient)) {
            // set the owning side to null (unless already changed)
            if ($ingredient->getRecette() === $this) {
                $ingredient->setRecette(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TagRecette>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(TagRecette $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(TagRecette $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }
}
