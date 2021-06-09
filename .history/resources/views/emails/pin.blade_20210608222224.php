<h1>Hello {{$name}},</h1>
<p>You have an invitation to join a system.</p>
<a href="{{route('link',$invite->token)}}">Click here</a>