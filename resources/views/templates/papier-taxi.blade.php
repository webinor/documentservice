<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Papier Taxi</title>
    <style>

body{
    font-family: helvetica;
    font-size:10px;
}

table{
    width:100%;
    border-collapse:collapse;
}

td,th{
    border:1px solid #555;
    padding:6px;
}

.header{
    background:#1f4e79;
    color:#fff;
    font-size:16px;
    font-weight:bold;
}

.section{
    background:#eeeeee;
    font-weight:bold;
    font-size:11px;
}

.label{
    font-weight:bold;
}

.signature{
    height:80px;
}

.total{
    text-align:right;
    font-weight:bold;
    font-size:11px;
}



</style>
</head>
<body>

    <table>

<tr>

<td width="20%" align="center">

@if(file_exists(public_path('assets/img/LOGO_CAMEROUN_ASSIST.png')))
<img
src="{{ public_path('assets/img/LOGO_CAMEROUN_ASSIST.png') }}"
width="70">
@endif

</td>

<td width="80%" align="center" class="header">

PAPIER TAXI

<br>

Référence : #{{ $document['reference'] }}

</td>

</tr>

</table>

<br>

<table>

<tr>

<td colspan="4" class="section">
INFORMATIONS GÉNÉRALES
</td>

</tr>

<tr>

<td class="label">
Référence
</td>

<td>
{{ $document['reference'] }}
</td>

<td class="label">
N° Pièce
</td>

<td>
{{-- {{ $document['numero_piece']['attachment_number'] ?? '-' }} --}}
{{ $document['numero_piece'] ?? '-' }}
</td>

</tr>

<tr>

<td class="label">
Bénéficiaire
</td>

<td>
{{ $document['actor_details']['nom'] }}
</td>

<td class="label">
Date
</td>

<td>
{{ $document['created_at'] }}
</td>

</tr>

<tr>

<td class="label">
Motif
</td>

<td colspan="3">
{{ $document['taxi_paper']['reason'] }}
</td>

</tr>

</table>

<br>


@php

$total = 0;

@endphp

<table>

<tr>

<td colspan="2" class="section">

INFORMATIONS FINANCIERES

</td>

</tr>

<tr>

<td class="label" width="75%">
Trajet
</td>

<td class="label" width="25%">
Montant
</td>

</tr>

@foreach($document['taxi_paper']['rides'] ?? [] as $ride)

@php

$total += $ride['montant'];

@endphp

<tr>

<td>

{{ strtoupper($ride['trajet']) }}

</td>

<td>

{{ number_format($ride['montant'],0,","," ") }}

FCFA

</td>

</tr>

@endforeach

<tr>

<td align="right">

<b>TOTAL</b>

</td>

<td>

<b>

{{ number_format($total,0,","," ") }}

FCFA

</b>

</td>

</tr>

</table>

<br>


<table>

<tr>

<td colspan="{{ count($allSignatures) }}"
class="section">

SIGNATURES

</td>

</tr>

<tr>

    @foreach($allSignatures as $item)

            <td width="{{ floor(100 / count($allSignatures)) }}%"
                align="center"
                valign="top"
                style="padding:8px;">

                {{-- Badge type --}}
                <div style="font-size:10px;margin-bottom:5px;">
                    @if($item['type_block'] === 'VALIDATION')
                        {{-- 🔵 Validation --}}
                    @else
                        {{-- 🟢 Réception --}}
                    @endif
                </div>

                {{-- Signature --}}
                <div style="height:55px;">
                    @if(!empty($item['signatureUrl']))
                        <img src="{{ $item['signatureUrl'] }}"
                             style="max-width:120px; max-height:55px;">
                    @endif
                </div>

                {{-- Nom --}}
                <div style="font-weight:bold;margin-top:5px;font-size:11px;">
                    {{ $item['user']['name'] ?? $item['user'] }}
                </div>

                {{-- Rôle --}}
                <div style="font-size:10px;color:#666;">
                    {{ $item['role'] }}
                </div>

                @if (!empty($item['signature_type']))
                     {{-- Signature type --}}
                <div style="font-size:10px;color:#666;">
                    {{ $item['signature_type'] }}
                </div>
                @endif

                {{-- Date --}}
                <div style="font-size:9px;color:#999;margin-top:3px;">
                    {{ $item['date']
                        ? \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i')
                        : '' }}
                </div>

            </td>

        @endforeach

</tr>

</table>


<table>

<tr>

<td align="center"
style="border:none;
font-size:9px;
color:#777;">

Document généré automatiquement par la GED Cameroun Assistance

</td>

</tr>

</table>
    
</body>
</html>