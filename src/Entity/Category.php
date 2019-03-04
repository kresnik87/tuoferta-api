<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
 */
class Category
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"category-read"})
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Subcategory", mappedBy="category", cascade={"remove","persist"})
     * @Groups({"category-read"})
     */
    private $subcategory;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"category-write","category-read","subcategory-read"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"category-write","category-read"})
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"category-write","category-read"})
     */
    private $colorName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"category-write","category-read"})
     */
    private $iconName;

    public function __construct()
    {
        $this->subcategory = new ArrayCollection();
        $this->name=null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Subcategory[]
     */
    public function getSubcategory(): Collection
    {
        return $this->subcategory;
    }

    public function addSubcategory(Subcategory $subcategory): self
    {
        if (!$this->subcategory->contains($subcategory)) {
            $this->subcategory[] = $subcategory;
            $subcategory->setCategory($this);
        }

        return $this;
    }

    public function removeSubcategory(Subcategory $subcategory): self
    {
        if ($this->subcategory->contains($subcategory)) {
            $this->subcategory->removeElement($subcategory);
            // set the owning side to null (unless already changed)
            if ($subcategory->getCategory() === $this) {
                $subcategory->setCategory(null);
            }
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getColorName(): ?string
    {
        return $this->colorName;
    }

    public function setColorName(?string $colorName): self
    {
        $this->colorName = $colorName;

        return $this;
    }

    public function getIconName(): ?string
    {
        return $this->iconName;
    }

    public function setIconName(?string $iconName): self
    {
        $this->iconName = $iconName;

        return $this;
    }
}
