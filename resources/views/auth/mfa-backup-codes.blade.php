@extends('auth.layout')

@section('title', 'Backup codes')
@section('brand', 'Keep your backup codes somewhere safe, away from your phone.')
@section('heading', $title ?: 'Your backup codes')
@section('lead', 'If you lose your phone, sign in with one of these codes. Each code works once. This is the only time they are shown.')

@section('card')
    <div x-data="{ copied: false }">
        <ul class="mfa-codes" id="mfaCodes">
            @foreach ($backupCodes as $code)
                <li>{{ $code }}</li>
            @endforeach
        </ul>

        <div class="mfa-actions">
            <button type="button"
                    @click="navigator.clipboard.writeText(@js(implode("\n", $backupCodes))).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    x-text="copied ? 'Copied' : 'Copy codes'">Copy codes</button>
            <button type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <a href="{{ $continueUrl }}" class="login-submit" style="text-decoration: none;">I saved them, continue</a>
@endsection
