<h1>Lettre de Mission</h1>

<p>
Nom :
{{ $mission->user->name ?? "KENGNE" }}
</p>

<p>
Destination :
{{ $mission->destination }}
</p>

<p>
Date départ :
{{ $mission->start_date }}
</p>

<p>
Date retour :
{{ $mission->end_date }}
</p>