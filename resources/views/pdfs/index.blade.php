<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF List</title>
    @vite('resources/js/app.js')
</head>

<body>
    <h1>PDF List</h1>
    <div id="app">
        <pdf-viewer pdf-id="{{ 1 }}"></pdf-viewer>
    </div>
</body>

</html>
