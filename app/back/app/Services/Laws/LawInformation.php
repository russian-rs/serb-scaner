<?php

namespace App\Services\Laws;

class LawInformation
{
    private ?string $lawName;
    private ?string $lawLink;
    private ?string $lawSlug;

    public function __construct(?string $lawName = null, ?string $lawLink = null, ?string $lawSlug = null)
    {
        $this->lawName = $lawName;
        $this->lawLink = $lawLink;
        $this->lawSlug = $lawSlug;
    }

    public function getLink(): ?string
    {
        return $this->lawLink;
    }

    public function getName(): ?string
    {
        return $this->lawName;
    }

    public function getSlug(): ?string
    {
        return $this->lawSlug;
    }
}
