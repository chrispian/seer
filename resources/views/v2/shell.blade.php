<!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  @vite(['resources/css/app.css','resources/js/v2-app.tsx'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - UI Builder v2</title>
</head>
<body class="h-full bg-white text-black antialiased"
      style="font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, Apple Color Emoji, Segoe UI Emoji;">
  <div class="min-h-screen">
    <div id="v2-root" class="h-screen" data-page-key="{{ $pageKey }}"></div>
  </div>

  <script>
    window.__V2_BOOT__ = {
      isAuthenticated: @json($isAuthenticated),
      hasUsers: @json($hasUsers),
      user: @json($user),
      pageKey: @json($pageKey),
    };
  </script>
</body>
</html>
