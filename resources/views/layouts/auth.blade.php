<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>@yield('title') - MyApp</title>
  @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
  <div class="bg-white p-6 rounded shadow w-full max-w-md">
    @yield('content')
  </div>
</body>
</html>
