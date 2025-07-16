<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Ensure full width coverage for horizontal scrolling */
        .min-h-screen {
            min-width: 100vw;
        }
        
        /* Make sure the background extends with content */
        body {
            overflow-x: auto;
        }
        
        /* Ensure navbar spans full width */
        .navbar-wrapper {
            width: 100%;
            min-width: 100vw;
        }
        
        /* Container for wide tables */
        .table-responsive-custom {
            overflow-x: auto;
            min-width: 100%;
        }
        
        /* Ensure table has minimum width */
        .wide-table {
            min-width: 1400px; /* Adjust based on your table's needs */
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <div class="navbar-wrapper">
            @include('layouts.navigation')
        </div>
        
        <!-- Page Heading -->
        @hasSection('header')
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    @yield('header')
                </div>
            </header>
        @endif
        
        <!-- Page Content -->
        <main>
            @yield('content')
        </main>
    </div>
    
    <!-- Bootstrap Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-w76A2z02tPqdjZyRvrnxKsmV9aTt4KJp1iPv1hFfCJOw1x04EcI9yI4twF3xKfD0"
            crossorigin="anonymous"></script>
</body>
</html>