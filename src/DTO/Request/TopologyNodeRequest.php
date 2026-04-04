<?php

namespace App\DTO\Request;

use App\Enum\NodeType;
use Symfony\Component\Validator\Constraints as Assert;

class TopologyNodeRequest
{
    public function __construct(
        #temporary id from vue-flow
        #[Assert\NotBlank]
        public readonly string $clientId,

        #[Assert\NotBlank]
        public readonly NodeType $type,

        #[Assert\NotBlank]
        public readonly string $label,

        #[Assert\NotNull]
        public readonly string $positionX,

        #[Assert\NotNull]
        public readonly string $positionY,

        public readonly array $config = []
    ) {}
}
