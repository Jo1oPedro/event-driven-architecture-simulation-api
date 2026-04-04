<?php

namespace App\DTO\Request;

use App\Enum\NodeType;
use Symfony\Component\Validator\Constraints as Assert;

class TopologyNodeRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $clientId,

        #[Assert\NotNull]
        public readonly NodeType $type,

        #[Assert\NotBlank]
        public readonly string $label,

        #[Assert\NotNull]
        public readonly float $positionX,

        #[Assert\NotNull]
        public readonly float $positionY,

        public readonly array $config = [],
    ) {
    }
}
