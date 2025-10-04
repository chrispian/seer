@extends('layouts.app')

@section('content')
<div id="settings-app" class="settings-page"></div>

<script>
window.__APP_BOOT__ = {
    isAuthenticated: true,
    hasUsers: true,
    user: @json($user),
};

window.settingsData = {
    user: @json($user),
    profile_settings: @json($profile_settings),
    routes: {
        updateProfile: '{{ route('settings.profile.update') }}',
        updateAvatar: '{{ route('settings.avatar.update') }}',
        updatePreferences: '{{ route('settings.preferences.update') }}',
        updateAI: '{{ route('settings.ai.update') }}',
        exportSettings: '{{ route('settings.export') }}',
        home: '{{ route('root') }}'
    }
};
</script>
@endsection