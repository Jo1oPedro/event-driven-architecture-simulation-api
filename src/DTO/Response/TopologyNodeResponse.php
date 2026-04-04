<?php

namespace App\DTO\Response;

use App\Entity\TopologyNode;

class TopologyNodeResponse
{
    public readonly int $id;
    public readonly string $type;
    public readonly string $label;
    public readonly float $positionX;
    public readonly float $positionY;
    public readonly array $config;

    public function __construct(TopologyNode $node) {
        $this->id = $node->getId();
        $this->type = $node->getType()->value;
        $this->label = $node->getLabel();
        $this->positionX = $node->getPositionX();
        $this->positionY = $node->getPositionY();
        $this->config = $node->getConfig();
    }
}
