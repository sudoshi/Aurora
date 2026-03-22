<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aurora — Advanced Clinical Case Intelligence Platform">
    <title>Aurora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @php
        $manifest = null;
        $manifestPath = public_path('build/.vite/manifest.json');
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
        }
    @endphp
    @if($manifest && isset($manifest['index.html']))
        @foreach($manifest['index.html']['css'] ?? [] as $css)
            <link rel="stylesheet" href="/build/{{ $css }}">
        @endforeach
    @endif
</head>
<body class="antialiased" style="background-color: #080816; color: #E8ECF4;">
    <div id="root"></div>
    @if($manifest && isset($manifest['index.html']))
        <script type="module" src="/build/{{ $manifest['index.html']['file'] }}"></script>
    @else
        <p style="text-align:center;margin-top:100px;color:#7A8298;">Frontend build not found. Run: cd frontend && npm run build && cp -r dist/* ../backend/public/build/</p>
    @endif
</body>
</html>
