<?php

namespace App\Entity;

use App\Repository\StripeSubscriptionEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StripeSubscriptionEventRepository::class)]
class StripeSubscriptionEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $event_type = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'stripeSubscriptionEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StripeSubscription $subscription_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventType(): ?string
    {
        return $this->event_type;
    }

    public function setEventType(string $event_type): static
    {
        $this->event_type = $event_type;

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
