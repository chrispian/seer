@extends('layouts.app')

@section('content')
<div id="setup-welcome" class="setup-wizard"></div>

<script>
window.__APP_BOOT__ = {
    isAuthenticated: true,
    hasUsers: true,
    user: @json(auth()->user()),
};

window.setupData = {
    step: 'welcome',
    user: @json(auth()->user()),
    routes: {
        profile: '{{ route('setup.profile') }}',
        profileStore: '{{ route('setup.profile.store') }}'
    }
};
</script>
@endsection