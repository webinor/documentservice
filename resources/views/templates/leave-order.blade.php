<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lettre de mise en congé</title>

    <style>
        @page {
            size: A4;
            margin: 22mm 15mm 28mm 15mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #111;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td { vertical-align: top; }

        /* ================================================================
           HEADER
        ================================================================= */
        .header {
            width: 100%;
            border-bottom: 1px solid #bdbdbd;
            padding-bottom: 4px;
        }

        .header-left {
            width: 70%;
            vertical-align: top;
        }

        .header-right {
            width: 30%;
            text-align: right;
            vertical-align: top;
        }

        .header-left img {
            display: block;
            height: 50px;
            width: auto;
        }

        .header-right img {
            display: inline-block;
            height: 70px;
            width: auto;
        }

        .services {
            margin-top: 2px;
            font-size: 6px;
            line-height: 1.1;
            white-space: nowrap;
        }

        .header-corner {
            position: absolute;
            left: 0;
            top: 38mm;
            width: 7px;
            height: 7px;
            border-left: 1px solid #bdbdbd;
            border-bottom: 1px solid #bdbdbd;
        }

        /* ================================================================
           DATE
        ================================================================= */
        .date-row {
            margin-top: 12px;
            text-align: right;
            font-size: 12px;
        }

        /* ================================================================
           DESTINATAIRE
        ================================================================= */
        .recipient-wrap {
            margin-top: 10px;
            text-align: center;
        }

        .recipient-box {
            display: inline-block;
            width: 155px;
            border: 1px solid #bdbdbd;
            padding: 3px 8px 4px 8px;
            text-align: center;
            font-size: 12px;
            line-height: 1.15;
        }

        .recipient-name {
            font-weight: bold;
        }

        .recipient-position {
            margin-top: 1px;
        }

        /* ================================================================
           OBJET
        ================================================================= */
        .subject {
            margin-top: 27px;
            font-size: 12px;
            line-height: 1.4;
        }

        .subject-label {
            font-weight: bold;
        }

        /* ================================================================
           CORPS
        ================================================================= */
        .letter-body {
            margin-top: 34px;
            font-size: 12px;
            line-height: 1.55;
        }

        .letter-body p {
            margin: 0 0 13px 0;
            text-align: left;
        }

        .letter-body .salutation {
            margin-bottom: 13px;
        }

        .letter-body strong {
            font-weight: bold;
        }

        /* ================================================================
           SIGNATURE DIRECTEUR GENERAL
        ================================================================= */
        .director-signature {
            margin-top: 52px;
            text-align: center;
            font-size: 12px;
            line-height: 1.35;
        }

        .director-title {
            font-weight: bold;
            text-transform: uppercase;
        }

        .director-signature-line {
            font-style: italic;
            margin-top: 2px;
        }

        /* ================================================================
           FOOTER FIXE
        ================================================================= */
        .footer-fixed {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: 22mm;
            font-size: 10px;
            line-height: 1.22;
            color: #111;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .certificate-cell {
            width: 14%;
            vertical-align: bottom;
            padding-bottom: 2px;
        }

        .certificate {
            width: 55px;
            height: 39px;
            border: 1px solid #777;
            padding: 4px 2px;
            text-align: center;
            font-size: 5px;
            line-height: 1.25;
        }

        .footer-center {
            width: 72%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 5px 1px 5px;
        }

        .footer-right {
            width: 14%;
        }

        .footer-system {
            margin-top: 2px;
            text-align: center;
            font-size: 5px;
            color: #777;
        }

        /* petits repères visuels comme sur la maquette */
        .page-corner-left,
        .page-corner-right {
            position: fixed;
            bottom: 3mm;
            width: 7px;
            height: 7px;
            border-bottom: 1px solid #c7c7c7;
        }

        .page-corner-left {
            left: 0;
            border-left: 1px solid #c7c7c7;
        }

        .page-corner-right {
            right: 0;
            border-right: 1px solid #c7c7c7;
        }
    </style>
</head>

<body>

    <div class="header-corner"></div>

    {{-- ================================================================
         EN-TÊTE
    ================================================================= --}}
    <table class="header">
        <tr>
            <td class="header-left">
                @if(file_exists(public_path('assets/img/LOGO_CAMEROUN_ASSIST.png')))
                    <img
                        src="{{ public_path('assets/img/LOGO_ISO_CAMEROUN_ASSIST.png') }}"
                        alt="Assistance aux personnes"
                    >
                @endif

                <div class="services">
                    ASSISTANCE AUX PERSONNES - TRANSPORTS MEDICALISES - EVACUATIONS SANITAIRES
                </div>
            </td>

            <td class="header-right">
                @if(file_exists(public_path('assets/img/LOGO_ISO_CAMEROUN_ASSIST.png')))
                    <img
                        src="{{ public_path('assets/img/LOGO_CAMEROUN_ASSIST.png') }}"
                        alt="Cameroun Assistance Sanitaire"
                    >
                @endif
            </td>
        </tr>
    </table>

    {{-- ================================================================
         DATE
    ================================================================= --}}
    @php
        $letterDate = $letterDate
            ?? ($document['date'] ?? null)
            ?? ($document['created_at'] ?? null)
            ?? now();

        $recipientCivility = $employeeCivility ?? '';
        $recipientName = $employeeName ?? '-';

        $recipientPosition = $jobTitle
            ?? $leave_employee['job_title']
            ?? $leave_employee['poste']
            ?? '';

        $leaveTypeLabel = $leaveType ?? 'annuel';
    @endphp

    <div class="date-row">
        Douala, le {{ \Carbon\Carbon::parse($letterDate)->locale('fr')->translatedFormat('j F Y') }}
    </div>


    <br>
    <br>
    <br>

    {{-- ================================================================
         DESTINATAIRE
    ================================================================= --}}
    <div class="recipient-wrap">
        <div class="recipient-box">
            <div class="recipient-name">
                {{ trim($recipientCivility . ' ' . $recipientName) }}
            </div>

            @if(!empty($recipientPosition))
                <div class="recipient-position">
                    {{ $recipientPosition }}
                </div>
            @endif
        </div>
    </div>

    <br>
    <br>
    <br>

    {{-- ================================================================
         OBJET
    ================================================================= --}}
    <div class="subject">
        <span class="subject-label">Objet :</span>
        {{-- Congé  --}}
        {{ $leaveTypeLabel }}
    </div>

    <br>
    <br>
    <br>

    {{-- ================================================================
         CORPS DE LA LETTRE
    ================================================================= --}}
    <div class="letter-body">

        <p class="salutation">
            {{ $recipientCivility ?: 'Monsieur' }} {{ $recipientName }},
        </p>

        <p>
            Nous vous informons par la présente que vous êtes mis(e) en congé au titre de vos
            droits de <strong>congé {{ $leaveTypeLabel }}</strong>.
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
            Vous êtes prié(e) de prendre toutes les dispositions nécessaires afin d'assurer la
            continuité des activités relevant de vos responsabilités avant votre départ.
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
            Nous vous souhaitons un excellent congé et vous prions d'agréer, l'expression de nos
            salutations distinguées.
        </p>

    </div>

    <br>
    <br>

    {{-- ================================================================
         SIGNATURE
         La maquette cible prévoit une seule signature : Directeur Général.
    ================================================================= --}}
    <div class="director-signature">
        <div class="director-title">LE DIRECTEUR GENERAL</div>

        @php
            // Ces variables peuvent être injectées par le service PDF si la
            // signature du Directeur Général doit être affichée.
            // $directorSignature = $directorGeneralSignatureUrl ?? null;
            // $directorName = $directorGeneralName ?? null;

            $directorName = trim(
    data_get($director, 'civilite', '') . ' ' .
    data_get($director, 'nom', ''). ' ' .
    data_get($director, 'prenom', '') 
);

// $signature = data_get(
//     $director,
//     'signature'
// );

$directorSignature = !empty($director['signature'])
    ? 'http://localhost:8088/storage/' . $director['signature']
    : null;


        @endphp

        @if(!empty($directorSignature))
            <div style="height:42px; margin-top:3px;">
                <img
                    src="{{ $directorSignature }}"
                    alt="Signature"
                    style="max-width:100px; max-height:40px;"
                >
            </div>
        @else
            <div class="director-signature-line">Signature</div>
        @endif

        @if(!empty($directorName))
            <div class="director-signature-line">{{ $directorName }}</div>
        @endif
    </div>


<br>
    <br>
    <br>

<!-- ====================================================== -->
<!-- FOOTER -->
<!-- ====================================================== -->

@include('pdf.components.document-footer')

<br>

    {{-- ================================================================
         FOOTER
    ================================================================= --}}
    <div class="footer-fixed">
        <table class="footer-table">
            <tr>
                <td class="certificate-cell">
                    <div class="certificate">
                        <strong>Certificat N°</strong><br>
                        Qual/2003/1357<br>
                        <br>
                        ISO 9001
                    </div>
                </td>

                <td class="footer-center">
                    BP : 2265 DOUALA CAMEROUN, 645 RUE BERTAUT BALI
                    <br>
                    Tél. H24 : (237) 233 42 14 14 • 233 42 15 15 • 233 42 20 20 •
                    233 42 48 91 • 233 43 91 91
                    <br>
                    Fax : (237) 233 42 00 79 • 233 43 30 30 •
                    Email : administration@cas-assistance.com • commercial@cas-asistance.com
                    <br>
                    www.cas-assistance.com
                    <br>
                    S.A. au capital de <strong>100 000 000 FCFA</strong> –
                    RC/DLA/1987/B/04790 – N° Empl.5613301 A – NIU : M128800000469U
                    <br>
                    Autorisation Arrêté Ministériel N°
                    <strong>1982/A/MINSANTE/SG/DOSTS/SDSSP</strong> du 07 juin 2010
                    <div class="footer-system">
                        Document généré électroniquement par la plateforme CAS CONNECT
                        de Cameroun Assistance Sanitaire SA
                        @if(!empty($document['reference']))
                            · Réf. {{ $document['reference'] }}
                        @endif
                    </div>
                </td>

                <td class="footer-right"></td>
            </tr>
        </table>
    </div>

    <div class="page-corner-left"></div>
    <div class="page-corner-right"></div>

</body>
</html>
