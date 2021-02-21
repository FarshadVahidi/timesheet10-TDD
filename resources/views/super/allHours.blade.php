<p>all my hours</p>

@foreach($allMyHours as $hour)
    <h3>{{  $hour->date }}</h3>
    <h3>{{  $hour->hour }}</h3>
    <h3>{{  $hour->ferie }}</h3>
@endforeach
