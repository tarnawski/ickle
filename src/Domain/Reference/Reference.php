<?php

declare(strict_types=1);

namespace App\Domain\Reference;

use DateTimeImmutable;

class Reference
{
    private Identity $identity;
    private Name $name;
    private Url $url;
    private DateTimeImmutable $createdAt;

    private function __construct(Identity $identity, Name $name, Url $url, DateTimeImmutable $createdAt)
    {
        $this->identity = $identity;
        $this->name = $name;
        $this->url = $url;
        $this->createdAt = $createdAt;
    }

    public static function fromParameters(Identity $identity, Name $name, Url $url, DateTimeImmutable $createdAt): self
    {
        return new self($identity, $name, $url, $createdAt);
    }

    public function getIdentity(): Identity
    {
        return $this->identity;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
