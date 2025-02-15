<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1024">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Aurora - Secure Healthcare Collaboration Platform for Clinical Teams">
    <meta name="theme-color" content="#1f2937">
    <meta name="color-scheme" content="dark">
    <meta name="application-name" content="Aurora">
    
    <title>{{ config('app.name', 'Aurora') }}</title>


    <!-- Prevent caching during development -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- Scripts and Styles -->
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-900 text-gray-100 antialiased">
    <div id="root"></div>
</body>
</html>
