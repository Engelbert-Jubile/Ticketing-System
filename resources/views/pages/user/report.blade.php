{{-- LEGACY (fallback) resources/views/pages/user/report.blade.php --}}
@extends('layouts.app')
@section('title','User Report')

@section('content')
<div class="mx-auto max-w-6xl space-y-4">
  {{-- Header --}}
  <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
    <div class="flex-1">
      <h2 class="text-xl font-semibold">User Report</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">Kelola akun pengguna di sistem ticketing</p>
    </div>

    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
      <div class="relative flex-1 sm:w-72">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
          <span class="material-icons text-base">search</span>
        </span>
        <input
          type="search"
          placeholder="Cari username, nama, email…"
          class="w-full rounded-xl border border-gray-200 bg-white pl-10 pr-3 py-2 text-sm outline-none ring-0 transition
                 focus:border-blue-500 dark:border-gray-700 dark:bg-gray-800"
          disabled
        />
      </div>

      @can('create', \App\Models\User::class)
      <a
        href="{{ route('users.create') }}"
        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
      >
        <span class="material-icons text-base mr-1">person_add</span>
        Create User
      </a>
      @endcan
    </div>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-200">
      {{ session('success') }}
    </div>
  @endif
  @if($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-200">
      {{ $errors->first() }}
    </div>
  @endif

  {{-- Card + Table --}}
  <div class="relative rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="overflow-x-auto overflow-y-visible rounded-2xl">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800 dark:text-gray-400">
          <tr>
            <th class="px-4 py-3 text-left">#</th>
            <th class="px-4 py-3 text-left">User</th>
            <th class="px-4 py-3 text-left hidden md:table-cell">Nama Lengkap</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse($users as $i => $u)
            @php
              $fullname = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
              $firstRole = is_object($u) && isset($u->roles) ? optional($u->roles->first())->name : null;
            @endphp
            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
              <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>

              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="grid h-9 w-9 place-items-center rounded-full bg-gray-100 font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ strtoupper(substr($u->username,0,1)) }}
                  </div>
                  <div class="leading-tight">
                    <div class="font-medium">{{ $u->username }}</div>
                    <a href="{{ route('users.show',$u->id) }}" class="text-xs text-blue-600 hover:underline dark:text-blue-400">Detail</a>
                  </div>
                </div>
              </td>

              <td class="px-4 py-3 hidden md:table-cell">{{ $fullname ?: '—' }}</td>

              <td class="px-4 py-3">
                <a href="mailto:{{ $u->email }}" class="text-blue-600 hover:underline dark:text-blue-400">
                  {{ $u->email }}
                </a>
              </td>

              <td class="px-4 py-3">
                @if($u->roles->isEmpty())
                  —
                @else
                  {{-- tampilkan semua role sebagai badge --}}
                  @foreach($u->roles as $r)
                    @php
                      $cls = match($r->name) {
                        'superadmin' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200',
                        'admin'      => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200',
                        default      => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                      };
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $cls }} mr-1">
                      {{ strtoupper($r->name) }}
                    </span>
                  @endforeach
                @endif
              </td>

              <td class="px-4 py-3">
                <div class="flex flex-wrap justify-end gap-2">
                  @can('update', $u)
                    <a href="{{ route('users.edit',$u->id) }}" class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-3 py-1 text-xs font-semibold text-gray-600 transition hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                      <span class="material-icons text-base">edit</span>
                      Edit
                    </a>
                  @endcan
                  @can('delete', $u)
                    <form method="POST" action="{{ route('users.destroy',$u->id) }}" onsubmit="return confirm('Hapus akun {{ $u->username }}?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="inline-flex items-center gap-1 rounded-md border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/30 dark:text-rose-300 dark:hover:bg-rose-900/20">
                        <span class="material-icons text-base">delete</span>
                        Delete
                      </button>
                    </form>
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Tidak ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
