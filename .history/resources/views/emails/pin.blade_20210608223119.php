<h1>Hello {{$details['name']}},</h1>
<p>Your 6 digit pin is {{$details['pin']}}</p>
<a href="{{route('confirm',$details['token'])}}">Click here to confirm</a>