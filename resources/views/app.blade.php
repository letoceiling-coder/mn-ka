<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Применяем тему до загрузки страницы, чтобы избежать мигания
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            if (theme === 'dark') {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
                html.style.colorScheme = 'dark';
            } else {
                html.style.colorScheme = 'light';
            }
        })();
    </script>
</head>
<body class="min-h-screen bg-background text-foreground">
    <div id="app"></div>
</body>
</html>

