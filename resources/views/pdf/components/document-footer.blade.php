<table style="margin-top:10px; width:100%;">
    <tr>

        <td
            style="
                border:none;
                font-size:12px;
                color:#777;
                line-height:12px;
                vertical-align:middle;
                width:85%;
            "
        >
            <strong>
                {{ $metadata['document_type'] ?? 'Document' }}
            </strong>

            N° :
            <strong>
                {{ $metadata['document_number'] ?? '-' }}
            </strong>

            , Version :
            <strong>
                {{ $metadata['version'] ?? '1.0' }}
            </strong>

            , généré le :
            <strong>
                {{
                    isset($metadata['generated_at'])
                    ? $metadata['generated_at']->format('d/m/Y H:i')
                    : '-'
                }}
            </strong>

            par :
            <strong>
                {{ $metadata['generated_by'] ?? 'CAS CONNECT' }}
            </strong>

            , Référence :
            <strong>
                {{ $metadata['verification_reference'] ?? '-' }}
            </strong>

        </td>


        <td
            align="right"
            style="
                border:none;
                width:15%;
                vertical-align:middle;
            "
        >
            <img
                src="data:image/png;base64,{{ $metadata['qr_code'] }}"
                width="45"
                height="45"
                style="display:block;"
            />
        </td>

    </tr>
</table>