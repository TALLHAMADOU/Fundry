<?php

namespace Hamadou\Fundry\Events;

use Hamadou\Fundry\Models\Transaction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class TransactionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Transaction $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
