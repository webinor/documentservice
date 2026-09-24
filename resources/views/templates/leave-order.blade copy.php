<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <title>
        Lettre de mise en congé
    </title>

    <style>

    /*
    |--------------------------------------------------------------------------
    | BASE
    |--------------------------------------------------------------------------
    */

    body {
        font-family: helvetica;
        font-size: 9px;
        color: #202830;
        margin: 0;
        padding: 0;
    }


    /*
    |--------------------------------------------------------------------------
    | TABLES
    |--------------------------------------------------------------------------
    */

    table {
        width: 100%;
        border-collapse: collapse;
    }

    td {
        vertical-align: top;
    }


    /*
    |--------------------------------------------------------------------------
    | COULEURS CAS
    |--------------------------------------------------------------------------
    */

    .blue {
        color: {{ $branding['primary_color'] ?? '#123B63' }};
    }

    .red {
        color: {{ $branding['accent_color'] ?? '#C6202E' }};
    }

    .grey {
        color: {{ $branding['muted_color'] ?? '#687784' }};
    }


    /*
    |--------------------------------------------------------------------------
    | HEADER
    |--------------------------------------------------------------------------
    */

    .header-table {
        width: 100%;
    }

    .header-logo {
        width: 20%;
        text-align: center;
        vertical-align: middle;
        padding: 8px 5px 10px 5px;
    }

    .header-main {
        width: 55%;
        vertical-align: middle;
        padding: 8px 10px 10px 10px;
    }

    .header-document {
        width: 25%;
        text-align: right;
        vertical-align: middle;
        padding: 8px 5px 10px 5px;
    }


    .company-name {
        font-size: 14px;
        font-weight: bold;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
    }

    .company-tagline {
        margin-top: 5px;
        font-size: 7px;
        color: {{ $branding['muted_color'] ?? '#687784' }};
    }


    .document-code {
        font-size: 7px;
        font-weight: bold;
        color: {{ $branding['accent_color'] ?? '#C6202E' }};
        text-transform: uppercase;
    }

    .document-name {
        margin-top: 5px;
        font-size: 7px;
        font-weight: bold;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        text-transform: uppercase;
        line-height: 1.35;
    }

    .document-reference {
        margin-top: 5px;
        font-size: 6px;
        color: {{ $branding['muted_color'] ?? '#687784' }};
        line-height: 1.35;
    }

    .confidentiality {
        margin-top: 6px;
        font-size: 5px;
        font-weight: bold;
        color: {{ $branding['muted_color'] ?? '#687784' }};
        text-transform: uppercase;
    }


    /*
    |--------------------------------------------------------------------------
    | BARRE INSTITUTIONNELLE
    |--------------------------------------------------------------------------
    */

    .institution-table {
        margin-top: 12px;
    }

    .institution-accent {
        width: 4px;
        background: {{ $branding['accent_color'] ?? '#C6202E' }};
    }

    .institution-content {
        padding: 8px 10px;
        background: {{ $branding['light_color'] ?? '#F4F7FA' }};
        font-size: 6.5px;
        font-weight: bold;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        line-height: 1.5;
    }


    /*
    |--------------------------------------------------------------------------
    | TITRE
    |--------------------------------------------------------------------------
    */

    .title-table {
        margin-top: 18px;
    }

    .title-overline {
        font-size: 6px;
        font-weight: bold;
        letter-spacing: 1px;
        color: {{ $branding['accent_color'] ?? '#C6202E' }};
        text-transform: uppercase;
    }

    .title-main {
        margin-top: 6px;
        font-size: 16px;
        font-weight: bold;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        text-transform: uppercase;
        line-height: 1.3;
    }

    .title-line {
        width: 45px;
        height: 3px;
        margin-top: 7px;
        background: {{ $branding['accent_color'] ?? '#C6202E' }};
    }


    /*
    |--------------------------------------------------------------------------
    | DESTINATAIRE
    |--------------------------------------------------------------------------
    */

    .recipient-table {
        margin-top: 17px;
        border: 1px solid {{ $branding['border_color'] ?? '#D9E1E8' }};
    }

    .recipient-accent {
        width: 5px;
        background: {{ $branding['primary_color'] ?? '#123B63' }};
    }

    .recipient-content {
        padding: 10px 12px;
    }

    .recipient-label {
        font-size: 6px;
        font-weight: bold;
        color: {{ $branding['muted_color'] ?? '#687784' }};
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .recipient-name {
        margin-top: 3px;
        font-size: 10px;
        font-weight: bold;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        line-height: 1.4;
    }

    .recipient-position {
        margin-top: 4px;
        font-size: 7px;
        color: {{ $branding['muted_color'] ?? '#687784' }};
        line-height: 1.4;
    }


    /*
    |--------------------------------------------------------------------------
    | OBJET
    |--------------------------------------------------------------------------
    */

    .subject-table {
        margin-top: 15px;
    }

    .subject-label {
        width: 12%;
        font-weight: bold;
        font-size: 8px;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        padding: 3px 0;
    }

    .subject-content {
        width: 88%;
        font-size: 8px;
        line-height: 1.5;
        padding: 3px 0;
    }


    /*
    |--------------------------------------------------------------------------
    | PÉRIODE
    |--------------------------------------------------------------------------
    */

    .period-table {
        margin-top: 15px;
        border: 1px solid {{ $branding['border_color'] ?? '#D9E1E8' }};
    }

    .period-cell {
        width: 33.33%;
        padding: 10px 11px;
        border-right: 1px solid {{ $branding['border_color'] ?? '#D9E1E8' }};
        vertical-align: middle;
    }

    .period-cell:last-child {
        border-right: none;
    }

    .period-label {
        font-size: 6px;
        font-weight: bold;
        color: {{ $branding['muted_color'] ?? '#687784' }};
        text-transform: uppercase;
        line-height: 1.4;
    }

    .period-value {
        margin-top: 5px;
        font-size: 8px;
        font-weight: bold;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        line-height: 1.4;
    }


    /*
    |--------------------------------------------------------------------------
    | CORPS DE LA LETTRE
    |--------------------------------------------------------------------------
    */

    .content-table {
        margin-top: 16px;
    }

    .content {
        font-size: 11.5px;
        line-height: 1.65;
        text-align: justify;
    }

    .content p {
        margin: 0 0 10px 0;
        line-height: 1.65;
    }


    /*
    |--------------------------------------------------------------------------
    | NOTE RH
    |--------------------------------------------------------------------------
    */

    .notice-table {
        margin-top: 10px;
    }

    .notice-accent {
        width: 4px;
        background: {{ $branding['accent_color'] ?? '#C6202E' }};
    }

    .notice-content {
        padding: 8px 10px;
        background: {{ $branding['light_color'] ?? '#F4F7FA' }};
        font-size: 6.5px;
        line-height: 1.5;
    }


    /*
    |--------------------------------------------------------------------------
    | SIGNATURES
    |--------------------------------------------------------------------------
    */

    .signatures-wrapper {
        margin-top: 15px;
    }

    .signatures-title {
        padding: 7px 8px;
        background: {{ $branding['light_color'] ?? '#F4F7FA' }};
        border-left: 4px solid {{ $branding['primary_color'] ?? '#123B63' }};
        font-size: 6px;
        font-weight: bold;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        text-transform: uppercase;
        letter-spacing: .8px;
    }

    .signatures-table {
        margin-top: 7px;
        width: 100%;
        /* border-collapse: collapse; */
    }

    .signature-cell {
        padding: 8px 7px 6px 7px;
        text-align: center;
        vertical-align: top;
        /* border: 1px solid {{ $branding['border_color'] ?? '#D9E1E8' }}; */
    }

    .signature-type {
        font-size: 5.5px;
        font-weight: bold;
        color: {{ $branding['accent_color'] ?? '#C6202E' }};
        text-transform: uppercase;
        line-height: 1.4;
        min-height: 9px;
    }

    .signature-image {
        height: 48px;
        margin-top: 6px;
        margin-bottom: 5px;
        text-align: center;
    }

    .signature-image img {
        max-width: 100px;
        max-height: 45px;
    }

    .signature-name {
        font-size: 8px;
        font-weight: bold;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        line-height: 1.35;
    }

    .signature-position {
        margin-top: 4px;
        font-size: 6.5px;
        color: {{ $branding['muted_color'] ?? '#687784' }};
        line-height: 1.35;
    }

    .signature-kind {
        margin-top: 4px;
        font-size: 6px;
        line-height: 1.35;
    }

    .signature-date {
        margin-top: 5px;
        font-size: 5.5px;
        color: #777777;
        line-height: 1.3;
    }


    /*
    |--------------------------------------------------------------------------
    | FOOTER
    |--------------------------------------------------------------------------
    */

    .footer {
        margin-top: 18px;
        padding-top: 9px;
        border-top: 1px solid {{ $branding['border_color'] ?? '#D9E1E8' }};
    }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
    }

    .footer-left {
        width: 65%;
        vertical-align: top;
        padding-right: 12px;
    }

    .footer-right {
        width: 35%;
        vertical-align: top;
        text-align: right;
        padding-left: 12px;
    }

    .footer-company {
        font-size: 10px;
        font-weight: bold;
        letter-spacing: 0.6px;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        line-height: 1.35;
    }

    .footer-info {
        margin-top: 4px;
        font-size: 10px;
        line-height: 1.55;
        color: {{ $branding['muted_color'] ?? '#687784' }};
    }

    .footer-website {
        font-size: 10px;
        font-weight: bold;
        color: {{ $branding['primary_color'] ?? '#123B63' }};
        line-height: 1.35;
    }

    .footer-tagline {
        margin-top: 4px;
        font-size: 8px;
        font-style: italic;
        color: {{ $branding['accent_color'] ?? '#C6202E' }};
        line-height: 1.4;
    }

    /*
    | Petit élément visuel du footer
    */

    .footer-accent {
        margin-top: 5px;
        margin-left: auto;
        width: 35px;
        height: 2px;
        background: {{ $branding['accent_color'] ?? '#C6202E' }};
    }


    /*
    |--------------------------------------------------------------------------
    | LIGNE GED
    |--------------------------------------------------------------------------
    */

    .footer-system {
        margin-top: 7px;
        padding-top: 5px;
        border-top: 1px solid #EEF1F4;

        text-align: center;

        font-size: 7px;

        color: #9AA4AD;

        letter-spacing: 0.2px;

        line-height: 1.4;
    }

</style>

</head>


<body>


{{-- =====================================================================
     HEADER
===================================================================== --}}

<table class="header-table">

    <tr>

        {{-- LOGO --}}
        <td
            class="header-logo"
            width="20%"
        >

            @if(file_exists(public_path('assets/img/LOGO_CAMEROUN_ASSIST.png')))

                <img
                    src="{{ public_path('assets/img/LOGO_CAMEROUN_ASSIST.png') }}"
                    width="70"
                >

            @endif

        </td>


        {{-- IDENTITÉ --}}
        <td
            class="header-main"
            width="55%"
        >

            <div class="company-name">

                {{ $company['name'] ?? 'CAMEROUN ASSISTANCE SANITAIRE' }}

            </div>

            <div class="company-tagline">

                {{ $company['tagline'] ?? 'Nous sommes là quand il le faut !' }}

            </div>

        </td>


        {{-- DOCUMENT --}}
        <td
            class="header-document"
            width="25%"
        >

            <div class="document-code">

                {{ $document['code'] ?? 'RH / CONGÉ' }}

            </div>

            <div class="document-name">

                {{ $document['type'] ?? 'LETTRE DE MISE EN CONGÉ' }}

            </div>


            @if(!empty($document['reference']))

                <div class="document-reference">

                    Référence :
                    {{ $document['reference'] }}

                </div>

            @endif


            <div class="document-reference">

                {{-- Date : --}}
                Douala,
                {{ $documentDate ?? now()->format('d/m/Y') }}

            </div>


            <div class="confidentiality">

                {{ $document['confidentiality'] ?? 'DOCUMENT INTERNE' }}

            </div>

        </td>

    </tr>

</table>



{{-- =====================================================================
     BARRE INSTITUTIONNELLE
===================================================================== --}}

<table class="institution-table">

    <tr>

        <td
            class="institution-accent"
            width="4"
        >
        </td>

        <td class="institution-content">

            {{ $company['services'] ?? 'ASSISTANCE AUX PERSONNES • TRANSPORTS MÉDICALISÉS • ÉVACUATIONS SANITAIRES' }}

        </td>

    </tr>

</table>



{{-- =====================================================================
     TITRE
===================================================================== --}}

<table class="title-table">

    <tr>

        <td>

            <div class="title-overline">

                Gestion des ressources humaines

            </div>


            <div class="title-main">

                Lettre de mise en congé

            </div>


            <div class="title-line"></div>

        </td>

    </tr>

</table>



{{-- =====================================================================
     DESTINATAIRE
===================================================================== --}}

<table class="recipient-table">

    <tr>

        <td
            class="recipient-accent"
            width="5"
        >
        </td>


        <td class="recipient-content">

            <div class="recipient-label">

                À l'attention de

            </div>


            <div class="recipient-name">

                {{ $employeeCivility ?? '-' }}  {{ $employeeName ?? '-' }}

            </div>


            @if(!empty($leave_employee['fonction']))

                <div class="recipient-position">

                    {{ $leave_employee['fonction'] }}

                </div>

            @elseif(!empty($leave_employee['job_title']))

                <div class="recipient-position">

                    {{ $leave_employee['job_title'] }}

                </div>

            @elseif(!empty($leave_employee['poste']))

                <div class="recipient-position">

                    {{ $leave_employee['poste'] }}

                </div>

            @endif

        </td>

    </tr>

</table>



{{-- =====================================================================
     OBJET
===================================================================== --}}

<table class="subject-table">

    <tr>

        <td
            class="subject-label"
            width="12%"
        >

            Objet :

        </td>


        <td
            class="subject-content"
            width="88%"
        >

            Mise en congé au titre de :
            {{ $leaveType ?? 'annuelle' }}

        </td>

    </tr>

</table>



{{-- =====================================================================
     PÉRIODE
===================================================================== --}}

<table class="period-table">

    <tr>


        {{-- TYPE --}}
        <td
            class="period-cell"
            width="33%"
        >

            <div class="period-label">

                Type de congé

            </div>


            <div class="period-value">

                {{ $leaveType ?? '-' }}

            </div>

        </td>


        {{-- DÉPART --}}
        <td
            class="period-cell"
            width="33%"
        >

            <div class="period-label">

                Départ en congé

            </div>


            <div class="period-value">

                @if(!empty($departureDate))

                    {{ \Carbon\Carbon::parse($departureDate)->format('d/m/Y') }}

                @else

                    -

                @endif

            </div>

        </td>


        {{-- REPRISE --}}
        <td
            class="period-cell"
            width="34%"
        >

            <div class="period-label">

                Reprise du travail

            </div>


            <div class="period-value">

                @if(!empty($resumptionDate))

                    {{ \Carbon\Carbon::parse($resumptionDate)->format('d/m/Y') }}

                @else

                    -

                @endif

            </div>

        </td>

    </tr>

</table>



{{-- =====================================================================
     CORPS
===================================================================== --}}

<table class="content-table">

    <tr>

        <td class="content">

            <p>

                {{-- Madame, Monsieur --}}
                {{ $employeeCivility }}
                <strong>{{ $employeeName ?? '' }}</strong>,

            </p>


            <p>

                Nous vous informons par la présente que vous êtes mis(e)
                en congé au titre de vos droits à congé
                <strong>{{ $leaveType ?? '' }}</strong>.

            </p>


            <p>

                Votre congé prendra effet à compter du

                <strong>

                    @if(!empty($departureDate))

                        {{ \Carbon\Carbon::parse($departureDate)->format('d/m/Y') }}

                    @else

                        -

                    @endif

                </strong>

                et prendra fin le

                <strong>

                    @if(!empty($returnDate))

                        {{ \Carbon\Carbon::parse($returnDate)->format('d/m/Y') }}

                    @else

                        -

                    @endif

                </strong>.

            </p>


            <p>

                Vous êtes prié(e) de prendre toutes les dispositions
                nécessaires afin d'assurer la continuité des activités
                relevant de vos responsabilités avant votre départ.

            </p>


            <p>

                La reprise effective de vos fonctions est prévue le

                <strong>

                    @if(!empty($resumptionDate))

                        {{ \Carbon\Carbon::parse($resumptionDate)->format('d/m/Y') }}

                    @else

                        -

                    @endif

                </strong>.

            </p>


            <p>

                Nous vous souhaitons un excellent congé et vous prions
                d'agréer, Madame, Monsieur, l'expression de nos
                salutations distinguées.

            </p>

        </td>

    </tr>

</table>



{{-- =====================================================================
     NOTE RH
===================================================================== --}}

<table class="notice-table">

    <tr>

        <td
            class="notice-accent"
            width="4"
        >
        </td>


        <td class="notice-content">

            <strong>Information RH :</strong>

            La présente lettre constitue la notification officielle de
            la mise en congé dans le cadre du processus de gestion des
            absences de l'entreprise.

        </td>

    </tr>

</table>



{{-- =====================================================================
     SIGNATURES
===================================================================== --}}

@if(!empty($allSignatures) && count($allSignatures) > 0)

    <table class="signatures-wrapper">

        {{-- <tr>

            <td class="signatures-title">

                Validation et traçabilité du document

            </td>

        </tr> --}}


        <tr>

            <td>

                <table class="signatures-table">

                    <tr>

                        @foreach($allSignatures as $item)

                            <td
                                class="signature-cell"
                                width="{{ floor(100 / max(count($allSignatures), 1)) }}%"
                            >


                                {{-- TYPE --}}
                                <div class="signature-type">

                                    @if(($item['type_block'] ?? '') === 'VALIDATION')

                                        {{-- Validation --}}

                                    @elseif(($item['type_block'] ?? '') === 'RECEPTION')

                                        {{-- Réception --}}

                                    @else

                                        {{ $item['type_block'] ?? 'Signature' }}

                                    @endif

                                </div>


                                {{-- SIGNATURE --}}
                                <div class="signature-image">

                                    @if(!empty($item['signatureUrl']))

                                        <img
                                            src="{{ $item['signatureUrl'] }}"
                                            style="max-width:100px; max-height:45px;"
                                        >

                                    @endif

                                </div>


                                {{-- NOM --}}
                                <div class="signature-name">

                                    @if(is_array($item['user'] ?? null))

                                        {{ $item['user']['name'] ?? '' }}

                                    @else

                                        {{ $item['user'] ?? '' }}

                                    @endif

                                </div>


                                {{-- FONCTION --}}
                                @if(!empty($item['display_job_title']))

                                    <div class="signature-position">

                                        {{ $item['display_job_title'] }}

                                    </div>

                                @endif


                                {{-- TYPE SIGNATURE --}}
                                @if(!empty($item['signature_type']))

                                    <div class="signature-kind">

                                        {{ $item['signature_type'] }}

                                    </div>

                                @endif


                                {{-- DATE --}}
                                @if(!empty($item['date']))

                                    <div class="signature-date">

                                        {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') }}

                                    </div>

                                @endif

                            </td>

                        @endforeach

                    </tr>

                </table>

            </td>

        </tr>

    </table>

@endif



{{-- =====================================================================
     FOOTER
===================================================================== --}}

<table class="footer">

    <tr>

        <td class="footer-left">

            <div class="footer-company">

                {{ $company['name'] ?? 'CAMEROUN ASSISTANCE SANITAIRE' }}

            </div>


            <div class="footer-info">

                {{ $company['address'] ?? '' }}

                <br>

                Tél. :
                {{ $company['phone'] ?? '' }}

                @if(!empty($company['support_phone']))

                    · Assistance :
                    {{ $company['support_phone'] }}

                @endif

                <br>

                {{ $company['email'] ?? '' }}

            </div>

        </td>


        <td class="footer-right">

            <div class="footer-website">

                {{ $company['website'] ?? 'www.cas-assistance.com' }}

            </div>


            <div class="footer-tagline">

                {{ $company['tagline'] ?? 'Nous sommes là quand il le faut !' }}

            </div>


            <div class="footer-accent"></div>

        </td>

    </tr>

</table>


{{-- =====================================================================
     GED
===================================================================== --}}

<div class="footer-system">

    Document généré électroniquement par la plateforme CAS CONNECT
    de Cameroun Assistance Sanitaire SA

    @if(!empty($document['reference']))

        · Réf. {{ $document['reference'] }}

    @endif

</div>


</body>

</html>