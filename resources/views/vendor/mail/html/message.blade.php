@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}
<p class="text-left">This email address is not monitored, any replies will not be actioned.
So if you have any issues with this email notification. The quickest way is to get in touch on chat via the web or app ASAP.</p>

<p class="text-left">Thanks,<br>
<strong>OUTT</strong></p>

<p class="text-left">outt.com<br>
0333 0151040</p>

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
<img src="{{asset('images/largeoutt.jpg')}}" alt="{{config('app.name')}}">
<p class="text-left">Outt Limited a company registered in England under company number 12516903 and whose registered office is at Green Park House, London W1J 8LQ</p>

<p class="text-left">This email and any files transmitted with it are confidential and intended solely for the use of the individual or entity to whom they are addressed. If you have received this email in error please notify the system manager. 
Finally, the recipient should check this email and any attachments for the presence of viruses. The company accepts no liability for any damage caused by any virus transmitted by this email.</p>
@endcomponent
@endslot
@endcomponent
