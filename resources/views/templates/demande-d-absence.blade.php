<style>

body{
    font-family: helvetica;
    font-size:10px;
}


table{
    width:100%;
    border-collapse:collapse;
}


td{
    border:1px solid #555;
    padding:6px;
}


.header{
    background:#1f4e79;
    color:white;
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


</style>


<table>

<tr>

<td width="20%" align="center">
@if(file_exists(public_path('assets/img/LOGO_CAMEROUN_ASSIST.png')))
<img src="{{public_path('assets/img/LOGO_CAMEROUN_ASSIST.png')}}" width="70">
@endif
</td>


<td width="80%" align="center" class="header">

DEMANDE DE {{ $document['absence_requests']['type'] }}

<br>

Référence : #{{ $document['reference'] }}

</td>

</tr>

</table>



<br>



<!-- DEMANDEUR -->

<table>

<tr>

<td colspan="4" class="section">
INFORMATIONS DU DEMANDEUR
</td>

</tr>


<tr>

    <td class="label">Nom</td>

    <td>
        {{ trim(($document['actor_details']['nom'] ?? '').' '.($document['actor_details']['prenom'] ?? '')) }}
    </td>

    <td class="label">Matricule</td>

    <td>
        {{ $document['actor_details']['matricule'] ?? '-' }}
    </td>

</tr>



<tr>

    <td class="label">Poste</td>

    <td>
        {{ $document['actor_details']['organization']['position']['position']['name'] ?? '-' }}
    </td>

    <td class="label">Département</td>

    <td>
        {{ $document['actor_details']['organization']['position']['department']['name'] ?? '-' }}
    </td>

</tr>


</table>


<br>



<!-- ABSENCE -->


<table>

<tr>
    <td colspan="4" class="section">
         ABSENCE
    </td>
</tr>

<tr>

    <td class="label">
        Type
    </td>

    <td colspan="3">
        {{ ucfirst(strtolower($document['absence_request']['type'] ?? '-')) }}
    </td>

</tr>

<tr>

    <td class="label">
        Début
    </td>

    <td>

        {{ $document['absence_request']['departure_date'] ?? '' }}

        @if(!empty($document['absence_request']['departure_time']))
            {{ $document['absence_request']['departure_time'] }}
        @endif

    </td>

    <td class="label">
        Retour
    </td>

    <td>

        {{ $document['absence_request']['return_date'] ?? '' }}

        @if(!empty($document['absence_request']['return_time']))
            {{ $document['absence_request']['return_time'] }}
        @endif

    </td>

</tr>

<tr>

    <td class="label">
        Durée
    </td>

    <td>
        {{ $document['absence_request']['duration'] ?? 0 }} jour(s)
    </td>

    <td class="label">
        Motif
    </td>

    <td>
        {{ $document['absence_request']['reason'] ?? '-' }}
    </td>

</tr>

</table>


<br>


<!-- SOLDE -->
<table>

<tr>
    <td colspan="4" class="section">
         DIRECTION
    </td>
</tr>

<tr>

    <td>Solde disponible</td>

    <td>
        {{ $document['actor_details']['leave_balance']['remaining_days'] ?? 0 }} jours
    </td>

    <td>Demandé</td>

    <td>
        {{ $document['absence_request']['duration'] ?? 0 }} jours
    </td>

</tr>

<tr>

    <td colspan="3">
        Solde restant
    </td>

    <td>

        @php
            $remaining =
                ($document['actor_details']['leave_balance']['remaining_days'] ?? 0)
                - ($document['absence_request']['duration'] ?? 0);
        @endphp

        {{ max($remaining,0) }} jours

    </td>

</tr>

</table>


<br>



<!-- DECISION -->


<table>


<tr>

    
<td colspan="4" class="section">

DECISION

</td>


</tr>


<tr>


<td colspan="4" style="color: rgb(39, 189, 28); font-weight: bold;">

 Accordé


&nbsp;&nbsp;&nbsp;


{{-- ☐ Refus --}}


</td>


</tr>



<tr>


<td colspan="4">

Observation :

<br><br>


...........................................................................................................................

<br>


............................................................................................................................

</td>


</tr>


</table>



<br>



<!-- SIGNATURES -->
<table>

    <tr>
        <td colspan="{{ count($allSignatures) }}" class="section">
            SIGNATURES
        </td>
    </tr>

    <tr>

        @foreach($allSignatures as $item)

            <td
                width="{{ floor(100 / max(count($allSignatures),1)) }}%"
                align="center"
                valign="top"
                style="padding:8px; border:1px solid #555;">

                {{-- Type de signature --}}
                {{-- <div style="font-size:10px; font-weight:bold; margin-bottom:6px;">

                    @if(($item['type_block'] ?? '') === 'VALIDATION')
                        Validation
                    @elseif(($item['type_block'] ?? '') === 'RECEPTION')
                        Réception
                    @else
                        Signature
                    @endif

                </div> --}}

                {{-- Image de signature --}}
                <div style="height:55px; margin-bottom:5px;">

                    @if(!empty($item['signatureUrl']))
                        <img
                            src="{{ $item['signatureUrl'] }}"
                            style="max-width:120px; max-height:55px;">
                    @endif

                </div>

                {{-- Nom --}}
                <div style="font-size:11px; font-weight:bold;">
                    {{ $item['user']['name'] ?? $item['user'] ?? '' }}
                </div>

                {{-- Fonction / rôle --}}
                <div style="font-size:10px;">
                    {{ $item['role'] ?? '' }}
                </div>

                {{-- Type de signature --}}
                @if(!empty($item['signature_type']))
                    <div style="font-size:10px;">
                        {{ $item['signature_type'] }}
                    </div>
                @endif

                {{-- Date --}}
                @if(!empty($item['date']))
                    <div style="font-size:9px; color:#666; margin-top:4px;">
                        {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') }}
                    </div>
                @endif

            </td>

        @endforeach

    </tr>

</table>