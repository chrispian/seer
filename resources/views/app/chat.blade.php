@extends('layouts.app')

@section('content')
  <div class="h-screen flex">
    <aside class="w-16 border-r" id="ribbon-root"></aside>
    <aside class="w-72 border-r hidden md:block" id="left-nav-root"></aside>

    <main class="flex-1 flex flex-col">
      <header id="chat-header-root" class="border-b"></header>
      <section id="chat-transcript-root" class="flex-1 overflow-y-auto"></section>
      <footer id="chat-composer-root" class="border-t"></footer>
    </main>

    <aside class="hidden xl:block w-80 border-l" id="right-rail-root"></aside>
  </div>

  <div id="overlays-root"></div>

  <script>
    window.__APP_BOOT__ = {
      isAuthenticated: @json($isAuthenticated),
      hasUsers: @json($hasUsers),
      user: @json($user),
    };
  </script>
@endsection

