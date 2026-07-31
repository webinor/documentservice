<table style="margin-top:15px;">
    <tr>
        <td
            align="center"
            style="
                border:none;
                font-size:9px;
                color:#777;
                line-height:14px;
            "
        >


          <div>
                Document  :
                <strong>
                    {{ $metadata['document_type'] ?? '-' }}
                </strong>
            </div>

            <div>
                Document N° :
                <strong>
                    {{ $metadata['document_number'] ?? '-' }}
                </strong>
            </div>

            <div>
                Version :
                <strong>
                    {{ $metadata['version'] ?? '1.0' }}
                </strong>
            </div>

            <div>
                Date de génération :
                <strong>
                    {{ 
                        isset($metadata['generated_at'])
                        ? $metadata['generated_at']->format('d/m/Y à H:i')
                        : '-'
                    }}
                </strong>
            </div>

            <div>
                Généré par :
                <strong>
                    {{ $metadata['generated_by'] ?? 'CAS CONNECT' }}
                </strong>
            </div>

            <div>
                Référence de vérification :
                <strong>
                    {{ $metadata['verification_reference'] ?? '-' }}
                </strong>
            </div>

            <td
    width="25%"
    align="center"
    style="border:none;"
>

<img
    src="data:image/png;base64,{{ $metadata['qr_code'] }}"
    width="80"
/>

<div style="font-size:8px;color:#777;">
Scanner pour vérifier
</div>

</td>

        </td>
    </tr>
</table>