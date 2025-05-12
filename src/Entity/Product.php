<?php

namespace App\Entity;
use App\Repository\AlerteStockRepository;
use App\Repository\ProductRepository;
use App\Service\NotificationService;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @Vich\Uploadable
 */
#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    // This field will hold the uploaded file before it's stored in the database.
    /**
     * @Vich\UploadableField(mapping="product_image", fileNameProperty="imageName")
     * @Assert\File(mimeTypes={"image/jpeg", "image/png"})
     * @var File|null
     */
    #[Vich\UploadableField(mapping: 'product_image', fileNameProperty: 'imageName')]
    #[Assert\File(mimeTypes: ['image/jpeg', 'image/png'])]
    private ?File $imageFile = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero(message: 'Quantity must be zero or positive.')]
    private ?int $quantity = null;

    #[ORM\OneToMany(targetEntity: AlerteStock::class, mappedBy: 'product', cascade: ['remove'])]
    private Collection $alertes;

    private static ?NotificationService $notifier = null;
    private static ?AlerteStockRepository $alerteRepo = null;

    public static function setNotifier(NotificationService $n): void { self::$notifier = $n; }
    public static function setAlerteRepo(AlerteStockRepository $r): void { self::$alerteRepo = $r; }


    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
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
    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;
        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if ($imageFile !== null) {
            // Update the updatedAt timestamp automatically on file upload
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

}
