<?php

namespace App\DTO\Request;
use Symfony\Component\Validator\Constraints as Assert;

class SaveTopologyRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $name,

        public readonly string $description,

        #[Assert\Valid]
        public readonly array $nodes,

        #[Assert\Valid]
        public readonly array $edges,
    ) {}
}
