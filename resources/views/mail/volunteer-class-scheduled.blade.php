<x-mail::message>
# Your training class is set, {{ $application->first_name }}!

Thank you for signing up to volunteer with the Go Bike Project. Your class is now complete, and your free 2-day DRRM-CCAM with First Aid & BLS training is scheduled:

<x-mail::panel>
**Date:** {{ $class->starts_on->format('l, F j, Y') }}<br>
**Venue:** {{ $class->venue }}
@if ($class->notes)
<br>**Notes:** {{ $class->notes }}
@endif
</x-mail::panel>

Please arrive on time and bring a valid ID.
@if ($application->guardian_name)
Because you are under 18, please bring your signed parent or guardian consent.
@endif

If you can't make it, reply to this email or message us so we can save your slot for someone else.

<x-mail::button :url="url('/contact')">
Contact Go Bike
</x-mail::button>

See you there,<br>
The Go Bike Team
</x-mail::message>
