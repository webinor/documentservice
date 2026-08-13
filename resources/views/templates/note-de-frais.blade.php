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
            background:#0f766e;
            color:#fff;
            font-size:16px;
            font-weight:bold;
        }

        .section{
            background:#eeeeee;
            font-weight:bold;
            font-size:13px;
        }

        .label{
            font-weight:bold;
            font-size:12px;
        }

        .sub-label{
            font-weight:lighter;
            font-size:12px;
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

NOTE DE FRAIS

{{-- <br> --}}

{{-- Référence : #{{ $document['reference'] }} --}}

</td>

</tr>

</table>

<br>

<!-- ====================================================== -->
<!-- INFORMATIONS GÉNÉRALES -->
<!-- ====================================================== -->

<table>

<tr class="sub-label">

<td colspan="4" class="section">
INFORMATIONS GÉNÉRALES
</td>

</tr>

<tr class="sub-label">

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

<tr class="sub-label">

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
{{-- {{ $document['numero_piece'] ?? '-' }} --}}
{{ $accounting_reference ?? '-' }}
</td>

</tr>

<tr class="sub-label">

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
{{ $jobTitle ?? '-' }}
</td>

</tr>

</table>

<br>

<!-- ====================================================== -->
<!-- NOTE DE FRAIS -->
<!-- ====================================================== -->

<table>

<tr class="sub-label">

<td colspan="2" class="section">
INFORMATIONS FINANCIERES
</td>

</tr>

<tr class="sub-label">

<td width="75%" class="label">
Motif
</td>

<td width="25%" class="label">
Montant ( FCFA )
</td>

</tr>

<tr class="sub-label">

<td>

{{ $document['fee_note']['reason'] ?? '-' }}

</td>

<td>

{{ number_format($document['fee_note']['amount'] ?? 0,0,","," ") }}


</td>

</tr>

<tr class="sub-label">

<td align="right">

<b>TOTAL</b>

</td>

<td>

<b>

{{ number_format($document['fee_note']['amount'] ?? 0,0,","," ") }}
{{-- FCFA --}}

</b>

</td>

</tr>

</table>

<br>

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

{{-- @foreach($allSignatures as $item)

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

@endforeach --}}

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

                {{-- display_job_title --}}
                <div style="font-size:10px;color:#666;">
                    {{ $item['display_job_title'] }}
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
                        : '--' }}
                </div>

            </td>

        @endforeach

</tr>

</table>

<br>

<!-- ====================================================== -->
<!-- FOOTER -->
<!-- ====================================================== -->

@include('pdf.components.document-footer')

{{-- <table>

<tr>

<td
align="center"
style="border:none;font-size:9px;color:#777;">

Document généré automatiquement par la GED Cameroun Assistance

</td>

</tr>

</table> --}}

</body>

</html>