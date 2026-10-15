@extends('admin.operations.layout')
@section('title', 'Privacy Policy')
@section('heading', 'Privacy Policy')
@section('subheading', 'The current privacy policy displayed on the public website.')

@section('heading-actions')
<a href="{{ url('/privacy-policy') }}" target="_blank" class="btn btn-secondary btn-sm">
    <x-admin.icon name="external-link" /> View on website
</a>
@endsection

@section('content')
<div class="detail-card" style="max-width:760px">
    <div class="legal-doc">
        <p class="legal-updated">Last updated: {{ date('F d, Y') }}</p>

        <section class="legal-section">
            <h2>1. Information We Collect</h2>
            <p>We collect information that you provide directly to us when you volunteer, donate, or contact us. This may include your name, email address, phone number, and any other information you choose to provide.</p>
        </section>

        <section class="legal-section">
            <h2>2. How We Use Your Information</h2>
            <p>We use the information we collect to operate our programs, communicate with you, process donations, and improve our community services across Pangasinan.</p>
        </section>

        <section class="legal-section">
            <h2>3. Sharing of Information</h2>
            <p>We do not share your personal information with third parties except as necessary to provide our services, coordinate with our government partners (such as DILG or DOH), or comply with the law.</p>
        </section>

        <section class="legal-section">
            <h2>4. Security</h2>
            <p>We take reasonable measures to help protect your personal information from loss, theft, misuse, and unauthorized access, disclosure, alteration, and destruction.</p>
        </section>

        <section class="legal-section">
            <h2>5. Contact Us</h2>
            <p>If you have any questions about this Privacy Policy, please contact our community desk through the <a href="{{ url('/contact') }}" target="_blank">contact page</a>.</p>
        </section>
    </div>

    <div class="legal-edit-note">
        <x-admin.icon name="info" />
        <span>To edit the Privacy Policy content, update the file at <code>resources/views/pages/privacy.blade.php</code></span>
    </div>
</div>
@endsection
