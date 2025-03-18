<?php

namespace App\Entity;

use App\Repository\StripePaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StripePaymentRepository::class)]
class StripePayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $amont = null;

    #[ORM\Column(length: 10)]
    private ?string $currency = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?string $stripe_payment_intent = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'stripePayments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user_id = null;

    #[ORM\ManyToOne(inversedBy: 'stripePayments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StripeSubscription $subscription_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmont(): ?float
    {
        return $this->amont;
    }

    public function setAmont(float $amont): static
    {
        $this->amont = $amont;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
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

    public function getStripePaymentIntent(): ?string
    {
        return $this->stripe_payment_intent;
    }

    public function setStripePaymentIntent(string $stripe_payment_intent): static
    {
        $this->stripe_payment_intent = $stripe_payment_intent;

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

    public function getUserId(): ?User
    {
        return $this->user_id;
    }

    public function setUserId(?User $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getSubscriptionId(): ?StripeSubscription
    {
        return $this->subscription_id;
    }

    public function setSubscriptionId(?StripeSubscription $subscription_id): static
    {
        $this->subscription_id = $subscription_id;

        return $this;
    }
}
