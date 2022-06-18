@component('mail::message')
# Reset your password?

### If you requested a password reset for {{ "@".$user['username']}}, please use the confirmation code below to complete the process. If you did not request this, you can ignore this email.

### {{$token}}


{{ config('app.name') }}
@endcomponent
