<nav class="space-x-4">
    @auth
        <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
        <form action="{{ route('account.logout') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="hover:underline">Logout</button>
        </form>
    @else
        <a href="{{ route('login', ['locale' => app()->getLocale() ?? config('app.locale', 'en')]) }}" class="hover:underline">Login</a>
        <a href="{{ route('register', ['locale' => app()->getLocale() ?? config('app.locale', 'en')]) }}" class="hover:underline">Register</a>
    @endauth
</nav>
