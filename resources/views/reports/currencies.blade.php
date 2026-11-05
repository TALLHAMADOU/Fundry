<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des Devises — Fundry</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Rapport des Devises — Fundry</h1>
    <div class="meta">Généré le {{ $generatedAt }}</div>
    <table>
        <thead>
            <tr>
                <th>Code ISO</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Symbole</th>
                <th>Taux de change (USD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row->iso_code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->type instanceof \BackedEnum ? $row->type->value : $row->type }}</td>
                    <td>{{ $row->symbol }}</td>
                    <td>{{ $row->exchange_rate }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
