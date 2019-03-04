<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass="App\Repository\ReportRepository")
 */
class Report
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Vehicle", inversedBy="report")
     */
    private $aDS;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getADS(): ?Vehicle
    {
        return $this->aDS;
    }

    public function setADS(?Vehicle $aDS): self
    {
        $this->aDS = $aDS;

        return $this;
    }
}
