{{-- resources/views/errors/layout.blade.php --}}
@php
  $artImage = trim($__env->yieldContent('art', 'images/access-denied-403.svg'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>@yield('title','Error')</title>
  <style>
    :root {
      --bg-start: #eef2ff;
      --bg-end: #f8fafc;
      --fg: #0f172a;
      --muted: #627093;
      --border: rgba(99, 102, 241, 0.15);
      --card-bg: rgba(255, 255, 255, 0.92);
      --accent: #4f46e5;
      --accent-soft: rgba(79, 70, 229, 0.12);
    }

    * {
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
    }

    body {
      margin: 0;
      font-family: "Inter", system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Arial, sans-serif;
      color: var(--fg);
      background: radial-gradient(circle at 0% -20%, rgba(59, 130, 246, 0.18), transparent 46%),
        radial-gradient(circle at 110% 10%, rgba(124, 58, 237, 0.16), transparent 48%),
        linear-gradient(135deg, var(--bg-start), var(--bg-end));
      display: flex;
      align-items: center;
      justify-content: center;
      padding: clamp(32px, 6vw, 72px) 20px;
    }

    .wrap {
      width: min(880px, 100%);
    }

    .card {
      position: relative;
      background: var(--card-bg);
      border-radius: 28px;
      padding: clamp(40px, 6vw, 72px) clamp(28px, 6vw, 64px);
      box-shadow: 0 38px 80px -48px rgba(30, 64, 175, 0.45);
      text-align: center;
      overflow: hidden;
      border: 1px solid var(--border);
      backdrop-filter: blur(6px);
    }

    .card::before,
    .card::after {
      content: "";
      position: absolute;
      border-radius: 999px;
      filter: blur(0);
      background: var(--accent-soft);
      z-index: 0;
    }

    .card::before {
      width: 220px;
      height: 220px;
      top: -70px;
      right: -80px;
    }

    .card::after {
      width: 160px;
      height: 160px;
      bottom: -60px;
      left: -50px;
    }

    .content {
      position: relative;
      z-index: 1;
      display: grid;
      gap: 24px;
      justify-items: center;
    }

    .art {
      width: clamp(220px, 35vw, 280px);
      height: clamp(180px, 28vw, 260px);
      margin: 0 auto;
      background-image: url("{{ asset($artImage) }}");
      background-position: center;
      background-size: contain;
      background-repeat: no-repeat;
    }

    .code {
      margin: 0;
      font-size: clamp(64px, 10vw, 96px);
      line-height: 1;
      font-weight: 800;
      letter-spacing: 6px;
      color: #1e3a8a;
    }

    .title {
      margin: 0;
      font-size: clamp(22px, 3vw, 30px);
      font-weight: 700;
    }

    .subtitle {
      margin: 0;
      font-size: clamp(16px, 2.5vw, 18px);
      color: var(--muted);
      max-width: 520px;
    }

    .helper {
      margin: 0;
      font-size: 15px;
      color: rgba(99, 102, 241, 0.8);
      font-weight: 600;
      letter-spacing: 0.4px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 14px 26px;
      border-radius: 999px;
      text-decoration: none;
      background: linear-gradient(135deg, #6366f1, #4338ca);
      color: #fff;
      font-weight: 600;
      font-size: 16px;
      box-shadow: 0 20px 40px -22px rgba(79, 70, 229, 0.75);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 24px 48px -24px rgba(79, 70, 229, 0.8);
    }

    .btn:focus-visible {
      outline: 2px solid rgba(79, 70, 229, 0.6);
      outline-offset: 4px;
    }

    @media (max-width: 600px) {
      .card {
        padding: 32px 20px;
        border-radius: 24px;
      }

      .subtitle {
        font-size: 15px;
      }

      .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <main class="wrap">
    <section class="card">
      <div class="content">
        <div class="art"></div>
        <p class="helper">@yield('helper', 'Akses dibatasi')</p>
        <h1 class="code">@yield('code','Error')</h1>
        <p class="title">@yield('title','Something went wrong')</p>
        <p class="subtitle">@yield('message','Please try again later.')</p>
        <a class="btn" href="@yield('button_url', url('/'))">
          <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24">
            <path fill="currentColor" d="M9.53 5.53a.75.75 0 0 0-1.06-1.06l-5 5a.75.75 0 0 0 0 1.06l5 5a.75.75 0 0 0 1.06-1.06L5.81 11H20a.75.75 0 0 0 0-1.5H5.81z" />
          </svg>
          @yield('button_label','Back To Home')
        </a>
      </div>
    </section>
  </main>
</body>
</html>
