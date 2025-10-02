<?php

namespace Hamadou\Fundry\Events;

use Hamadou\Fundry\Models\Wallet;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class WalletCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Wallet $wallet;

    public function __construct(Wallet $wallet)
    {
        $this->wallet = $wallet;
    }
}
