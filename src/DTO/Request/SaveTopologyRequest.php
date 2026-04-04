<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class SaveTopologyRequest
{
    /**
     * @param TopologyNodeRequest[] $nodes
     * @param TopologyEdgeRequest[] $edges
     */
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $name,

        public readonly ?string $description = null,

        #[Assert\Valid]
        public readonly array $nodes = [],

        #[Assert\Valid]
        public readonly array $edges = [],
    ) {}
}
