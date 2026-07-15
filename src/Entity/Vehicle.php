<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vehicle')]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private string $imei;

    // Valst. numeris (AVL 231+232) atkeliauja tik dalyje įrašų, todėl gali būti dar nežinomas
    #[ORM\Column(length: 16, nullable: true)]
    private ?string $plate = null;

    public function __construct(string $imei)
    {
        $this->imei = $imei;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImei(): string
    {
        return $this->imei;
    }

    public function getPlate(): ?string
    {
        return $this->plate;
    }

    public function setPlate(?string $plate): void
    {
        $this->plate = $plate;
    }
}
