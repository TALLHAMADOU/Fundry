<?php

namespace Hamadou\Fundry\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;

class FundryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Collection $data;
    protected string $type;

    public function __construct($data, string $type = 'transactions')
    {
        // s'assurer d'avoir une Collection
        $this->data = $data instanceof Collection ? $data : collect($data);
        $this->type = $type;
    }

    /**
     * Retourne la collection à exporter.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Mappage par ligne (adapter selon la structure de chaque type).
     *
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        // Adapter le mapping selon $this->type
        if ($this->type === 'wallets') {
            return [
                $row->id ?? null,
                $row->user_id ?? null,
                $row->balance ?? ($row->balance_cents ?? null),
                $row->currency->code ?? ($row->currency_code ?? null),
                $row->created_at ?? null,
            ];
        }

        if ($this->type === 'currencies') {
            return [
                $row->id ?? null,
                $row->code ?? null,
                $row->name ?? null,
                $row->exchange_rate ?? null,
            ];
        }

        // par défaut : transactions
        return [
            $row->id ?? null,
            $row->user_id ?? null,
            $row->from_wallet_id ?? null,
            $row->to_wallet_id ?? null,
            $row->amount ?? ($row->amount_cents ?? null),
            $row->currency->code ?? ($row->currency_code ?? null),
            $row->type ?? null,
            $row->status ?? null,
            $row->created_at ?? null,
        ];
    }

    /**
     * En-têtes (adapter si besoin).
     *
     * @return array
     */
    public function headings(): array
    {
        return match($this->type) {
            'wallets' => ['id', 'user_id', 'balance', 'currency', 'created_at'],
            'currencies' => ['id', 'code', 'name', 'exchange_rate'],
            default => ['id', 'user_id', 'from_wallet_id', 'to_wallet_id', 'amount', 'currency', 'type', 'status', 'created_at'],
        };
    }
}