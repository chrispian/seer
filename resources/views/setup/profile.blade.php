@extends('layouts.app')

@section('content')
<div id="setup-profile" class="setup-wizard"></div>

<script>
window.__APP_BOOT__ = {
    isAuthenticated: true,
    hasUsers: true,
    user: @json(auth()->user()),
};

window.setupData = {
    step: 'profile',
    user: @json(auth()->user()),
    routes: {
        store: '{{ route('setup.profile.store') }}',
        next: '{{ route('setup.avatar') }}'
    }
};
</script>
@endsection