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
        if ($this->type === 'wallets') {
            $type = $row->type ?? null;
            return [
                $row->id ?? null,
                $row->user_id ?? null,
                $row->name ?? null,
                $type instanceof \BackedEnum ? $type->value : $type,
                $row->balance ?? ($row->balance_cents ?? null),
                $row->currency->iso_code ?? ($row->currency->code ?? ($row->currency_code ?? null)),
                $row->created_at ?? null,
            ];
        }

        if ($this->type === 'currencies') {
            $type = $row->type ?? null;
            return [
                $row->id ?? null,
                $row->iso_code ?? ($row->code ?? null),
                $row->name ?? null,
                $type instanceof \BackedEnum ? $type->value : $type,
                $row->exchange_rate ?? null,
            ];
        }

        if ($this->type === 'limits') {
            $item = is_array($row) ? $row : (array) $row;
            return [
                $item['type'] ?? null,
                $item['limit'] ?? ($item['daily_limit'] ?? 0),
            ];
        }

        $type = $row->type ?? null;
        $status = $row->status ?? null;

        return [
            $row->id ?? null,
            $row->reference ?? null,
            $row->user_id ?? null,
            $row->from_wallet_id ?? null,
            $row->to_wallet_id ?? null,
            $row->amount ?? ($row->amount_cents ?? null),
            $row->currency->iso_code ?? ($row->currency->code ?? ($row->currency_code ?? null)),
            $type instanceof \BackedEnum ? $type->value : $type,
            $status instanceof \BackedEnum ? $status->value : $status,
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
        return match ($this->type) {
            'wallets' => ['id', 'user_id', 'name', 'type', 'balance', 'currency', 'created_at'],
            'currencies' => ['id', 'code', 'name', 'type', 'exchange_rate'],
            'limits' => ['type', 'limit'],
            default => ['id', 'reference', 'user_id', 'from_wallet_id', 'to_wallet_id', 'amount', 'currency', 'type', 'status', 'created_at'],
        };
    }
}
