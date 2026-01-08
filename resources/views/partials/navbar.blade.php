{{-- resources/views/partials/navbar.blade.php --}}
<nav class="bg-blue-600 text-white px-4 py-3 flex justify-between items-center dark:bg-gray-800">
  <div class="text-lg font-bold">
    <a href="{{ route('dashboard') }}">Ticketing System</a>
  </div>
  <div class="flex items-center space-x-4">
    {{-- Dark Mode Toggle --}}
    <button id="theme-toggle" class="focus:outline-none">
      {{-- Icon Bulan (untuk light mode) --}}
      <svg id="icon-light" xmlns="http://www.w3.org/2000/svg"
           class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
      </svg>
      {{-- Icon Matahari (untuk dark mode) --}}
      <svg id="icon-dark" xmlns="http://www.w3.org/2000/svg"
           class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364 6.364l-1.414-1.414M7.05 7.05L5.636 5.636m12.728 0l-1.414 1.414M7.05 16.95l-1.414 1.414" />
      </svg>
    </button>

    {{-- Logout --}}
    <form method="POST" action="{{ route('account.logout') }}">
      @csrf
      <button type="submit" class="hover:underline">Logout</button>
    </form>
  </div>
</nav>
