<?php

namespace App\Entity;

use App\Repository\StripeSubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StripeSubscriptionRepository::class)]
class StripeSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiredAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user_id = null;

    /**
     * @var Collection<int, StripePayment>
     */
    #[ORM\OneToMany(targetEntity: StripePayment::class, mappedBy: 'subscription_id', orphanRemoval: true)]
    private Collection $stripePayments;

    /**
     * @var Collection<int, StripeSubscriptionEvent>
     */
    #[ORM\OneToMany(targetEntity: StripeSubscriptionEvent::class, mappedBy: 'subscription_id', orphanRemoval: true)]
    private Collection $stripeSubscriptionEvents;

    public function __construct()
    {
        $this->stripePayments = new ArrayCollection();
        $this->stripeSubscriptionEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?\DateTimeImmutable $expiredAt): static
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    public function getUserId(): ?User
    {
        return $this->user_id;
    }

    public function setUserId(?User $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * @return Collection<int, StripePayment>
     */
    public function getStripePayments(): Collection
    {
        return $this->stripePayments;
    }

    public function addStripePayment(StripePayment $stripePayment): static
    {
        if (!$this->stripePayments->contains($stripePayment)) {
            $this->stripePayments->add($stripePayment);
            $stripePayment->setSubscriptionId($this);
        }

        return $this;
    }

    public function removeStripePayment(StripePayment $stripePayment): static
    {
        if ($this->stripePayments->removeElement($stripePayment)) {
            // set the owning side to null (unless already changed)
            if ($stripePayment->getSubscriptionId() === $this) {
                $stripePayment->setSubscriptionId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StripeSubscriptionEvent>
     */
    public function getStripeSubscriptionEvents(): Collection
    {
        return $this->stripeSubscriptionEvents;
    }

    public function addStripeSubscriptionEvent(StripeSubscriptionEvent $stripeSubscriptionEvent): static
    {
        if (!$this->stripeSubscriptionEvents->contains($stripeSubscriptionEvent)) {
            $this->stripeSubscriptionEvents->add($stripeSubscriptionEvent);
            $stripeSubscriptionEvent->setSubscriptionId($this);
        }

        return $this;
    }

    public function removeStripeSubscriptionEvent(StripeSubscriptionEvent $stripeSubscriptionEvent): static
    {
        if ($this->stripeSubscriptionEvents->removeElement($stripeSubscriptionEvent)) {
            // set the owning side to null (unless already changed)
            if ($stripeSubscriptionEvent->getSubscriptionId() === $this) {
                $stripeSubscriptionEvent->setSubscriptionId(null);
            }
        }

        return $this;
    }
}
