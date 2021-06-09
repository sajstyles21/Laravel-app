<h1>Hello {{$data['name']}},</h1>
<p>Your 6 digit pin is {{$data['pin']}}</p>
<a href="{{route('confirm',$data['token'])}}">Click here to confirm</a>