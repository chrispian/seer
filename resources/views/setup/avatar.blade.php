@extends('layouts.app')

@section('content')
<div id="setup-avatar" class="setup-wizard"></div>

<script>
window.__APP_BOOT__ = {
    isAuthenticated: true,
    hasUsers: true,
    user: @json(auth()->user()),
};

window.setupData = {
    step: 'avatar',
    user: @json(auth()->user()),
    routes: {
        store: '{{ route('setup.avatar.store') }}',
        next: '{{ route('setup.preferences') }}'
    }
};
</script>
@endsection