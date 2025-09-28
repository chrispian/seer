<!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  @vite(['resources/css/app.css','resources/js/app.tsx'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Project:Mentat Chat</title>
</head>
<body class="h-full bg-white text-black antialiased"
      style="font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, Apple Color Emoji, Segoe UI Emoji;">
  <div class="min-h-screen">
    @yield('content')   {{-- <<< this was missing --}}
  </div>
</body>
</html>

