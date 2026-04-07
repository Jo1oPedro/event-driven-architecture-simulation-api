<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class StartSimulationRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly int $topologyId
    ) {}
}
