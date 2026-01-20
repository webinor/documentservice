<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Papier Taxi</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header, .footer { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table td { border: 1px solid #000; padding: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Depense de taxi</h2>
        <p>Date: {{ $document->created_at ?? '..................' }}  Heure: {{ $document->heure ?? '..................' }}</p>
        <p>Motif: {{ $document->taxi_paper->reason ?? '..................' }}</p>
    </div>

     @php
    $total = 0;
@endphp

    <table class="table">
        <thead>
            <tr>
                <td>Trajet</td>
                <td>Montant</td>
            </tr>
        </thead>
        <tbody>
         

@foreach($document->taxi_paper->rides ?? [] as $trajet)
    @php
        $total += (float) ($trajet['montant'] ?? 0);
    @endphp
    <tr>
        <td>{{ $trajet['trajet'] ?? '..................' }}</td>
        <td>{{ number_format($trajet['montant'] ?? 0, 0, ',', ' ') }}</td>
    </tr>
@endforeach
        </tbody>
    </table>

    <p>Total: {{ $total ?? '..................' }}</p>
    <p>Ordre de M.: {{ $document->ordre ?? '..................' }}</p>

    <p>Signature: ..................................</p>
</body>
</html>
