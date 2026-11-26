<x-mail::message>
# Hi {{ $firstName }},

Someone asked to reset the password of your Go Bike account. Enter this code to choose a new password:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

It expires in {{ $minutes }} minutes. If you didn't ask for this, ignore this email. Your password stays the same.

The Go Bike Team
</x-mail::message>
