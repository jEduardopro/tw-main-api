@component('mail::message')
# Confirm your email address

You must complete this quick step to confirm your email address.

<br>
Enter this verification code on {{ config('app.name') }} when prompted

# <h1>{{$token}}</h1>

<br>
Verification codes expire after 10 minutes.

Thank you,<br>
{{ config('app.name') }}
@endcomponent
