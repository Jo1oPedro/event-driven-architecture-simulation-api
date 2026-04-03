<?php

namespace App\Enum;

enum EventStatus: string
{
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
