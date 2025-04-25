<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fragments</title>
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen flex flex-col items-center justify-center p-4">
{{ $slot }}
@livewireScripts
</body>
</html>
