@extends('admin.operations.layout')
@section('title', 'Edit profile')
@section('heading', 'Edit profile')
@section('subheading', 'Update your name, email, or password.')
@section('back', route('admin.profile.show'))
@section('back_label', 'My profile')

@section('content')
<form class="form-card" action="{{ route('admin.profile.update') }}" method="POST">
    @csrf
    @method('PUT')

    <fieldset class="form-section">
        <legend>Account</legend>
        <div class="grid-2">
            <div class="field">
                <label for="name">Full name</label>
                <input class="input @error('name') is-invalid @enderror" id="name" name="name" required autocomplete="name" value="{{ old('name', $user->name) }}">
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input class="input @error('email') is-invalid @enderror" id="email" type="email" name="email" required autocomplete="email" value="{{ old('email', $user->email) }}">
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Password</legend>
        <div class="grid-2">
            <div class="field">
                <label for="password">New password</label>
                <input class="input @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="new-password">
                <span class="hint">Leave blank to keep your current password.</span>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
            </div>
        </div>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save changes</button>
        <a class="btn btn-secondary" href="{{ route('admin.profile.show') }}">Cancel</a>
    </div>
</form>
@endsection
