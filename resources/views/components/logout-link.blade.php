{{-- resources/views/components/logout-link.blade.php --}}
@php
  $label = $label ?? 'Logout';
  // kelas default biar terlihat seperti item di sidebar
  $classes = $classes
      ?? 'w-full text-left flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700';
@endphp

<form method="POST" action="{{ route('logout') }}">
  @csrf
  <button type="submit" class="{{ $classes }}">
    {{-- pakai icon kalau ada, atau hapus span di bawah --}}
    <span class="material-icons text-gray-500">logout</span>
    <span>{{ $label }}</span>
  </button>
</form>
