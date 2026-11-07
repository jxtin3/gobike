<x-mail::message>
# You're approved, {{ $firstName }}!

Thank you for signing up as a GoBiker with the Go Bike Project. An admin has reviewed and approved your account, so you can now sign in to the GoBiker app.

<x-mail::panel>
Open the GoBiker app and sign in with **{{ $user->email }}** and the password you chose when you signed up.
</x-mail::panel>

Once you're in, you can record patient check-ups and get alerts from the Go Bike team.

If you have trouble signing in, reply to this email or message us.

<x-mail::button :url="url('/contact')">
Contact Go Bike
</x-mail::button>

Welcome to the team,<br>
The Go Bike Team
</x-mail::message>
