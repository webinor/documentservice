<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Note de frais</title>

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

FICHE À RÉGULARISER

</td>

</tr>

</table>

<br>

<!-- ====================================================== -->
<!-- INFORMATIONS GÉNÉRALES -->
<!-- ====================================================== -->

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
Date
</td>

<td>
{{ $document['created_at'] }}
</td>

</tr>

<tr>

<td class="label">
Collaborateur
</td>

<td>
{{ $document['actor_details']['nom'] ?? '-' }}
</td>

<td class="label">
N° Pièce
</td>

<td>
{{-- {{ $document['actor_details']['matricule'] ?? '-' }} --}}
{{ $document['numero_piece'] ?? '-' }}
</td>

</tr>

<tr>

<td class="label">
Département
</td>

<td>
{{ $document['actor_details']['organization']['position']['department']['name'] ?? '-' }}
</td>

<td class="label">
Poste
</td>

<td>
{{ $document['actor_details']['organization']['position']['position']['name'] ?? '-' }}
</td>

</tr>

</table>

<br>

<!-- ====================================================== -->
<!-- NOTE DE FRAIS -->
<!-- ====================================================== -->

<table>

<tr>
    <td colspan="5" class="section">
        DÉPENSES À RÉGULARISER
    </td>
</tr>

<tr>
    <td width="40%" class="label">Désignation</td>
    <td width="15%" class="label" align="center">Qté</td>
    <td width="20%" class="label" align="right">Prix unitaire</td>
    <td width="25%" class="label" align="right">Total</td>
</tr>

@php
$total = 0;
@endphp

@foreach($document['regularization_sheet']['items'] ?? [] as $item)

@php
$total += $item['total_amount'];
@endphp

<tr>

    <td>
        {{ $item['designation'] }}
    </td>

    <td align="center">
        {{ $item['quantity'] }}
    </td>

    <td align="right">
        {{ number_format($item['unit_price'],0,","," ") }}
    </td>

    <td align="right">
        {{ number_format($item['total_amount'],0,","," ") }}
    </td>

</tr>

@endforeach

</table>

<br>

<table>

<tr>
    <td colspan="2" class="section">
        RÉSUMÉ FINANCIER
    </td>
</tr>

<tr>
    <td class="label">
        Avance reçue
    </td>

    <td align="right">
        {{ number_format($document['regularization_sheet']['amount'],0,","," ") }} FCFA
    </td>
</tr>

<tr>
    <td class="label">
        Dépenses réelles
    </td>

    <td align="right">
        {{ number_format($total,0,","," ") }} FCFA
    </td>
</tr>

@php
$balance = $total - ($document['regularization_sheet']['amount'] ?? 0);
@endphp

<tr>

    <td class="label">

        @if($balance > 0)

            Solde à rembourser au collaborateur

        @elseif($balance < 0)

            Montant à restituer à la caisse

        @else

            Solde

        @endif

    </td>

    <td align="right">

        <b>

            {{ number_format(abs($balance),0,","," ") }}

            FCFA

        </b>

    </td>

</tr>

</table>

@if(!empty($document['regularization_sheet']['comment']))

<br>

<table>

<tr>
    <td class="section">
        OBSERVATIONS
    </td>
</tr>

<tr>
    <td>

        {{ $document['regularization_sheet']['comment'] }}

    </td>
</tr>

</table>

@endif

<!-- ====================================================== -->
<!-- SIGNATURES -->
<!-- ====================================================== -->

<table>

<tr>

<td colspan="{{ count($allSignatures) }}"
class="section">

SIGNATURES

</td>

</tr>

<tr>

@foreach($allSignatures as $item)

<td
width="{{ floor(100 / max(count($allSignatures),1)) }}%"
align="center"
valign="top"
style="padding:8px;">

<div style="height:55px;">

@if(!empty($item['signatureUrl']))
<img
src="{{ $item['signatureUrl'] }}"
style="max-width:120px;max-height:55px;">
@endif

</div>

<div style="font-size:11px;font-weight:bold;">

{{ $item['user']['name'] ?? $item['user'] }}

</div>

<div style="font-size:10px;">

{{ $item['role'] }}

</div>

@if(!empty($item['signature_type']))

<div style="font-size:10px;">

{{ $item['signature_type'] }}

</div>

@endif

@if(!empty($item['date']))

<div
style="font-size:9px;color:#666;margin-top:4px;">

{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') }}

</div>

@endif

</td>

@endforeach

</tr>

</table>

<br>

<!-- ====================================================== -->
<!-- FOOTER -->
<!-- ====================================================== -->

<table>

<tr>

<td
align="center"
style="border:none;font-size:9px;color:#777;">

Document généré automatiquement par la GED Cameroun Assistance

</td>

</tr>

</table>

</body>

</html>