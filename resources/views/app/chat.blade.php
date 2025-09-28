@extends('layouts.app')

@section('content')
  <div id="app-root" class="h-screen"></div>

  <script>
    window.__APP_BOOT__ = {
      isAuthenticated: @json($isAuthenticated),
      hasUsers: @json($hasUsers),
      user: @json($user),
    };
  </script>
@endsection

