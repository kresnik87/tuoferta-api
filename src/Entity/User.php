<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiResource;
use FOS\UserBundle\Model\User as BaseUser;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @Vich\Uploadable
 */
class User extends BaseUser
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups("user-read")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"user-read", "user-write"})
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"user-read", "user-write"})
     */
    private $lastName;



    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Vehicle", mappedBy="user")
     */
    private $ads;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Review", mappedBy="user")
     */
    private $review;

    /**
     * @Groups({"user-read",  "meeting-read", "worker-read","employee-read","employee-write"})
     */
    protected $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"user-read","employee-read", "worker-read"})
     * @var string
     */
    private $image;

    /**
     * @Vich\UploadableField(mapping="user", fileNameProperty="image")
     * @var File
     */
    private $imageFile;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"user"})
     */
    private $createdDate;

    /**
     * @ORM\Column(type="datetime" ,nullable=true)
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Vehicle")
     */
    private $favorites;
    public function __construct()
    {
        parent::__construct();
        $this->ads = new ArrayCollection();
        $this->createdDate = new \dateTime();
        $this->updatedAt = new \dateTime();
        $this->review = new ArrayCollection();
        $this->favorites = new ArrayCollection();
    }

    function getId()
    {
        return $this->id;
    }

    function getName()
    {
        return $this->name;
    }

    function getLastName()
    {
        return $this->lastName;
    }



    function setId($id)
    {
        $this->id = $id;
    }

    function setName($name)
    {
        $this->name = $name;
    }

    function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }



    /**
     * @return Collection|Vehicle[]
     */
    public function getAds(): Collection
    {
        return $this->ads;
    }

    public function addAd(Vehicle $ad): self
    {
        if (!$this->ads->contains($ad)) {
            $this->ads[] = $ad;
            $ad->setUser($this);
        }

        return $this;
    }

    public function removeAd(Vehicle $ad): self
    {
        if ($this->ads->contains($ad)) {
            $this->ads->removeElement($ad);
            // set the owning side to null (unless already changed)
            if ($ad->getUser() === $this) {
                $ad->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Review[]
     */
    public function getReview(): Collection
    {
        return $this->review;
    }

    public function addReview(Review $review): self
    {
        if (!$this->review->contains($review)) {
            $this->review[] = $review;
            $review->setUser($this);
        }

        return $this;
    }

    public function removeReview(Review $review): self
    {
        if ($this->review->contains($review)) {
            $this->review->removeElement($review);
            // set the owning side to null (unless already changed)
            if ($review->getUser() === $this) {
                $review->setUser(null);
            }
        }

        return $this;
    }

    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;
        if ($image) {
            $this->setUpdatedAt();
        }

    }

    public function getImageFile()
    {
        return $this->imageFile;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image): self
    {

        $this->image = $image;

        return $this;
    }

    public function getCreatedDate(): ?\dateTime
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?\dateTime $createdDate = null): self
    {
        $this->createdDate = $createdDate ? $createdDate : new \dateTime();

        return $this;
    }

    public function getUpdatedAt(): ?\dateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\dateTime $updateAt = null): self
    {
        $this->updatedAt = $updateAt ? $updateAt : new \dateTime();

        return $this;
    }

    /**
     * @return Collection|Vehicle[]
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Vehicle $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites[] = $favorite;
        }

        return $this;
    }

    public function removeFavorite(Vehicle $favorite): self
    {
        if ($this->favorites->contains($favorite)) {
            $this->favorites->removeElement($favorite);
        }

        return $this;
    }

}
