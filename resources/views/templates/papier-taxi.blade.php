<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Papier Taxi</title>
    <style>
        /* Reset de marges pour le PDF */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            background: #fff;
        }

        .container {
            width: 700px;
            margin: 0 auto; /* centrer horizontalement */
            padding: 20px;
            border: 2px solid #007BFF; /* bleu électrique */
            border-radius: 8px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 120px;
            height: 60px;
            /* border: 1px dashed #ccc; */
            display: flex;
            justify-content: center;
            align-items: center;
            color: #aaa;
            margin-bottom: 20px;
            font-size: 10px;
        }

        .title {
            text-align: center;
            flex-grow: 1;
            color: #007BFF;
        }

        .title h2 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }

        .info {
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .info p span {
            font-weight: bold;
            color: #D32F2F; /* rouge */
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th, .table td {
            border: 1px solid #007BFF;
            padding: 8px;
            text-align: left;
        }

        .table th {
            background-color: #007BFF;
            color: white;
        }

        .total {
            text-align: right;
            font-weight: bold;
            margin-bottom: 40px;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }

        .signature-box {
            width: 45%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 5px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #666;
        }

       .signatures {
        color: #101010 !important;
    width: 100%;
    margin-top: 100px;
    margin-bottom: 100px;
    /* overflow: hidden; pour contenir les floats */
}

.signature-left, .signature-right {
    
    width: 48%; /* laisse un petit espace entre les deux */
    text-align: center;
    /* border-top: 1px solid #333; */
    padding-top: 5px;
    font-weight: bold;
    box-sizing: border-box; /* inclut padding et bordure dans la largeur */

    
}

.signature-left {
    float: left;
}

.signature-right {
    float: right;
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img style="width: 100px;" src="{{ asset('assets/img/LOGO_CAMEROUN_ASSIST.png') }}" alt="Logo de la compagnie">
            </div>
            <div class="title"><h2>Depense de Taxi</h2></div>
        </div>

        <div class="info">
            <p>BENEFICIAIRE: <span>{{ $document['taxi_paper']['beneficiary_details']['name'] ?? '..................' }}</span></p>
            <p>DATE ET HEURE : <span>{{ $document->created_at ?? '..................' }}</span></p>
            <p>MOTIF : <span>{{ $document->taxi_paper->reason ?? '..................' }}</span></p>
        </div>

        @php $total = 0; @endphp
        <table class="table">
            <thead>
                <tr>
                    <th>TRAJET</th>
                    <th>MONTANT (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($document->taxi_paper->rides ?? [] as $trajet)
                    @php $total += (float) ($trajet['montant'] ?? 0); @endphp
                    <tr>
                        <td>{{ Str::upper($trajet['trajet']) ?? '..................' }}</td>
                        <td>{{ number_format($trajet['montant'] ?? 0, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="total">Total: {{ number_format($total, 0, ',', ' ') ?? '0' }} FCFA</p>

    <div class="signatures" style="width: 100%; margin-top: 50px; color: #101010;">
    <div class="signature-left" style="width: 48%; float: left; text-align: center;">
        @if(!empty($signatureDonneur))
            <img src="{{ $signatureDonneur }}" 
                 alt="Signature donneur d'ordre" 
                 style="max-width:200px; max-height:80px; display:block; margin: 0 auto 5px;">
        @endif
        <div style="margin-top: 5px; font-weight: bold;">Signature du donneur d'ordre</div>
    </div>

    <div class="signature-right" style="width: 48%; float: right; text-align: center;">
        @if(!empty($signatureBeneficiaire))
            <img src="{{ $signatureBeneficiaire }}" 
                 alt="Signature bénéficiaire" 
                 style="max-width:200px; max-height:80px; display:block; margin: 0 auto 5px;">
        @endif
        <div style="margin-top: 5px; font-weight: bold;">Signature du bénéficiaire</div>
    </div>
</div>

<div style="clear: both;"></div>

        <div class="footer">GED - Papier Taxi généré automatiquement</div>
    </div>
</body>
</html>