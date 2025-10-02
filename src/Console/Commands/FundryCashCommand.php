<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;

class FundryCashCommand extends Command
{
    protected $signature = 'fundry:cash {action?}';
    protected $description = 'Opérations cash (ex: générer rapports / nettoyer tests)';

    public function handle()
    {
        $this->info('Commande cash — à compléter.');
        return self::SUCCESS;
    }
}