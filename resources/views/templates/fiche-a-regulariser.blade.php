<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <title>
        FICHE A REGULARISER - {{ $document['reference'] }}
    </title>

    <style>

        body {
            font-family: helvetica;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            border: 1px solid #555;
            padding: 6px;
        }

        .header {
            background: #7c3aed;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
        }

        .section {
            background: #eeeeee;
            font-weight: bold;
            font-size: 13px;
        }

        .label {
            font-weight: bold;
            font-size: 12px;
        }

        .sub-label {
            font-weight: lighter;
            font-size: 12px;
        }

        .signature {
            height: 80px;
        }

        .total {
            text-align: right;
            font-weight: bold;
            font-size: 11px;
        }

    </style>

</head>

<body>

<!-- ====================================================== -->
<!-- EN-TÊTE -->
<!-- ====================================================== -->

<table>

    <tr>

        <td width="20%" align="center">

            @if(file_exists(public_path('assets/img/LOGO_CAMEROUN_ASSIST.png')))

                <img
                    src="{{ public_path('assets/img/LOGO_CAMEROUN_ASSIST.png') }}"
                    width="70"
                >

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
            {{ $document['numero_piece'] ?? '-' }}
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
            {{ $document['actor_details']['organization']['position']['position']['name'] ?? '-' }}
        </td>

    </tr>

</table>

<br>

<!-- ====================================================== -->
<!-- DÉPENSES À RÉGULARISER -->
<!-- ====================================================== -->

@php
    $total = 0;
@endphp

<table>

    <tr class="sub-label">

        <td colspan="4" class="section">
            DÉPENSES À RÉGULARISER
        </td>

    </tr>

    <tr class="sub-label">

        <td width="45%" class="label">
            Désignation
        </td>

        <td width="15%" class="label" align="center">
            Quantité prévue
        </td>

        <td width="20%" class="label" align="right">
            Prix unitaire prévu
        </td>

        <td width="20%" class="label" align="right">
            Total prévu
        </td>

    </tr>

    @foreach($document['regularization_sheet']['items'] ?? [] as $item)

        @php

            $plannedQuantity = $item['planned_quantity'] ?? 0;

            $plannedAmount = $item['planned_amount'] ?? 0;

            $lineTotal = $plannedQuantity * $plannedAmount;

            $total += $lineTotal;

        @endphp

        <tr class="sub-label">

            <td>
                {{ $item['designation'] ?? '-' }}
            </td>

            <td align="center">
                {{ $plannedQuantity }}
            </td>

            <td align="right">
                {{ number_format($plannedAmount, 0, ",", " ") }}
            </td>

            <td align="right">
                {{ number_format($lineTotal, 0, ",", " ") }}
            </td>

        </tr>

    @endforeach

    <tr class="sub-label">

        <td class="label">
            TOTAL
        </td>

        <td></td>

        <td></td>

        <td align="right" class="label">
            {{ number_format($total, 0, ",", " ") }}
        </td>

    </tr>

</table>

<br>

<!-- ====================================================== -->
<!-- RÉSUMÉ FINANCIER -->
<!-- ====================================================== -->

{{-- <table>

    <tr class="sub-label">

        <td colspan="2" class="section">
            RÉSUMÉ FINANCIER
        </td>

    </tr>

    <tr class="sub-label">

        <td class="label">
            Montant total prévu
        </td>

        <td align="right">

            <b>
                {{ number_format($total, 0, ",", " ") }}
                FCFA
            </b>

        </td>

    </tr>

</table> --}}

@if(!empty($document['regularization_sheet']['comment']))

    <br>

    <!-- ====================================================== -->
    <!-- OBSERVATIONS -->
    <!-- ====================================================== -->

    <table>

        <tr class="sub-label">

            <td class="section">
                OBSERVATIONS
            </td>

        </tr>

        <tr class="sub-label">

            <td>
                {{ $document['regularization_sheet']['comment'] }}
            </td>

        </tr>

    </table>

@endif

<br>

<!-- ====================================================== -->
<!-- SIGNATURES -->
<!-- ====================================================== -->

<table>

    <tr>

        <td
            colspan="{{ count($allSignatures) }}"
            class="section"
        >
            SIGNATURES
        </td>

    </tr>

    <tr>

        @foreach($allSignatures as $item)

            <td
                width="{{ floor(100 / max(count($allSignatures), 1)) }}%"
                align="center"
                valign="top"
                style="padding:8px;"
            >

                <div style="height:55px;">

                    @if(!empty($item['signatureUrl']))

                        <img
                            src="{{ $item['signatureUrl'] }}"
                            style="max-width:120px;max-height:55px;"
                        >

                    @endif

                </div>

                <div style="font-size:11px;font-weight:bold;">

                    {{ $item['user']['name'] ?? $item['user'] }}

                </div>

                <div style="font-size:10px;">

                    {{ $item['display_job_title'] }}

                </div>

                @if(!empty($item['signature_type']))

                    <div style="font-size:10px;">

                        {{ $item['signature_type'] }}

                    </div>

                @endif

                @if(!empty($item['date']))

                    <div
                        style="
                            font-size:9px;
                            color:#666;
                            margin-top:4px;
                        "
                    >

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

@include('pdf.components.document-footer')

</body>

</html>