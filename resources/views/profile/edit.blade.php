<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Profile Settings</h1>
        <p class="page-subtitle">Update your account's profile information and email address</p>
    </x-slot>

    <div class="max-w-3xl space-y-6 animate-fade-in">
        <div class="card p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card p-6">
            @include('profile.partials.update-password-form')
        </div>

        <div class="card p-6 border-red-900/50">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
