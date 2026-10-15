@extends('admin.operations.layout')
@section('title', 'Terms & Conditions')
@section('heading', 'Terms & Conditions')
@section('subheading', 'The current terms and conditions displayed on the public website.')

@section('heading-actions')
<a href="{{ url('/terms') }}" target="_blank" class="btn btn-secondary btn-sm">
    <x-admin.icon name="external-link" /> View on website
</a>
@endsection

@section('content')
<div class="detail-card" style="max-width:760px">
    <div class="legal-doc">
        <p class="legal-updated">Last updated: {{ date('F d, Y') }}</p>

        <section class="legal-section">
            <h2>1. Acceptance of Terms</h2>
            <p>By accessing and using the Go Bike website and participating in our programs, you accept and agree to be bound by the terms and provisions of this agreement.</p>
        </section>

        <section class="legal-section">
            <h2>2. Volunteer Conduct</h2>
            <p>Volunteers and community responders are expected to maintain the highest standards of respect, safety, and community service. Go Bike reserves the right to revoke volunteer status for any behavior that violates our community guidelines.</p>
        </section>

        <section class="legal-section">
            <h2>3. Use of Site</h2>
            <p>You may use our site for lawful purposes and in accordance with these Terms. You agree not to use the site in any way that violates any applicable national or local law or regulation.</p>
        </section>

        <section class="legal-section">
            <h2>4. Intellectual Property</h2>
            <p>The content, features, and functionality of this website are owned by Go Bike and are protected by copyright, trademark, and other intellectual property laws.</p>
        </section>

        <section class="legal-section">
            <h2>5. Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. Your continued use of the site or participation in our programs constitutes acceptance of those changes.</p>
        </section>
    </div>

    <div class="legal-edit-note">
        <x-admin.icon name="info" />
        <span>To edit the Terms &amp; Conditions content, update the file at <code>resources/views/pages/terms.blade.php</code></span>
    </div>
</div>
@endsection
