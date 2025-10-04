@extends('layouts.app')

@section('content')
<div id="setup-complete" class="setup-wizard"></div>

<script>
window.__APP_BOOT__ = {
    isAuthenticated: true,
    hasUsers: true,
    user: @json(auth()->user()),
};

window.setupData = {
    step: 'complete',
    user: @json(auth()->user()),
    routes: {
        finalize: '{{ route('setup.finalize') }}',
        home: '{{ route('root') }}'
    }
};
</script>
@endsection