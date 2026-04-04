<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class TopologyEdgeRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $source,

        #[Assert\NotBlank]
        public readonly string $target,

        #[Assert\PositiveOrZero]
        public readonly int $simulatedLatency = 100,

        #[Assert\Range(min: 0.0, max: 1.0)]
        public readonly float $failureRate = 0.0,
    ) {}
}
