<?php
declare(strict_types=1);

namespace App\Contracts;

interface WebsocketClient
{
    public function sendEvent(array $event): void;
}
