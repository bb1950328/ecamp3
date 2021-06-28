<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ApiResource]
class MaterialList extends BaseEntity implements BelongsToCampInterface {
    /**
     * @ORM\OneToMany(targetEntity="MaterialItem", mappedBy="materialList")
     *
     * @var MaterialItem
     */
    public $materialItems;

    /**
     * @ORM\ManyToOne(targetEntity="Camp", inversedBy="materialLists")
     * @ORM\JoinColumn(nullable=false, onDelete="cascade")
     */
    public ?Camp $camp = null;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    public ?string $materialListPrototypeId = null;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    public ?string $name = null;

    public function __construct() {
        $this->materialItems = new ArrayCollection();
    }

    public function getCamp(): ?Camp {
        return $this->camp;
    }

    public function getMaterialItems(): array {
        return $this->materialItems->getValues();
    }

    public function addMaterialItem(MaterialItem $materialItem): void {
        $materialItem->materialList = $this;
        $this->materialItems->add($materialItem);
    }

    public function removeMaterialItem(MaterialItem $materialItem): void {
        $materialItem->materialList = null;
        $this->materialItems->removeElement($materialItem);
    }
}
