<?php

namespace App\Enum;

enum NodeType: string
{
    case Microservice = 'microservice';
    case Queue = 'queue';
    case Topic = 'topic';
    case Database = 'database';
}
