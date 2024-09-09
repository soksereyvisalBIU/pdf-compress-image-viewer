<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>vsial</title>
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    @vite('resources/js/app.js')
    
</head>
<body>
    <div id="app">
        <pdf-viewer pdf-id="{{ 1 }}"></pdf-viewer>
    </div>
</body>
</html>