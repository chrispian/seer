@extends('layouts.app')

@section('content')
<div id="setup-preferences" class="setup-wizard"></div>

<script>
window.__APP_BOOT__ = {
    isAuthenticated: true,
    hasUsers: true,
    user: @json(auth()->user()),
};

window.setupData = {
    step: 'preferences',
    user: @json(auth()->user()),
    routes: {
        store: '{{ route('setup.preferences.store') }}',
        next: '{{ route('setup.complete') }}'
    }
};
</script>
@endsection