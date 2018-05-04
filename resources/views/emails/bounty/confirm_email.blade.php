@component('mail::message')
# Valuto wallet signup

Dear {{ $name }}

Click the following button to create your free Valuto wallet account and receive your bounty:

@component('mail::button', ['url' => route('verify', ['confirmationCode' => $confirmationCode, 'userId' => $userId])])
Accept free wallet signup
@endcomponent

Thanks,<br>
The Valuto team
@endcomponent