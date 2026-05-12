<h1>Fiche à Régulariser</h1>

<table width="100%" border="1">
    <tr>
        <th>Avance</th>
        <th>Dépenses</th>
        <th>Écart</th>
    </tr>

    <tr>
        <td>{{ $mission->advance_amount }}</td>
        <td>{{ $mission->total_expenses }}</td>
        <td>
            {{
                $mission->advance_amount
                - $mission->total_expenses
            }}
        </td>
    </tr>
</table>