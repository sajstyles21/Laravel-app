<h1>Hello {{$data['name']}},</h1>
<p>Your 6 digit pin is {{$pin}}</p>
<a href="{{route('confirm',$invite->token)}}">Click here to confirm</a>