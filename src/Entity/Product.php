<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Product name is required.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Product name must be at least {{ limit }} characters long",
        maxMessage: "Product name cannot exceed {{ limit }} characters"
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Description is required.")]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: "Description must be at least {{ limit }} characters long",
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Price is required.")]
    #[Assert\Positive(message: "Price must be greater than 0.")]
    #[Assert\Range(
        min: 0.01,
        max: 999999.99,
        notInRangeMessage: "Price must be between {{ min }} and {{ max }}"
    )]
    private ?float $price = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Category is required.")]
    #[Assert\Choice(
        choices: ['Bouquet', 'Arrangement', 'Single Flower', 'Wedding', 'Funeral', 'Event', 'Gift Set', 'Other'],
        message: "Please select a valid category."
    )]
    private ?string $category = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(message: "Stock quantity is required.")]
    #[Assert\PositiveOrZero(message: "Stock quantity cannot be negative.")]
    private ?int $stock = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Status is required.")]
    #[Assert\Choice(
        choices: ['active', 'inactive', 'out_of_stock'],
        message: "Status must be 'active', 'inactive', or 'out_of_stock'."
    )]
    private string $status = 'active';

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->status = 'active';
        $this->isActive = true;
        $this->stock = 0;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
        
        // Auto-update status based on stock
        if ($stock <= 0) {
            $this->status = 'out_of_stock';
        } elseif ($this->status === 'out_of_stock' && $stock > 0) {
            $this->status = 'active';
        }
        
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        // Validate status value
        $validStatuses = ['active', 'inactive', 'out_of_stock'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException("Invalid product status: $status. Must be one of: " . implode(', ', $validStatuses));
        }

        // Enforce consistency: if stock is 0, status must be out_of_stock
        if ($this->stock <= 0 && $status === 'active') {
            $status = 'out_of_stock';
        }

        // Enforce consistency: if stock > 0 and status is out_of_stock, allow but warn
        // (This will be caught by form validation)

        $this->status = $status;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $user): static
    {
        $this->createdBy = $user;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
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
}

