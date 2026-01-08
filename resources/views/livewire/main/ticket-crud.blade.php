<div class="max-w-4xl mx-auto p-4">
    {{-- Flash --}}
    @if (session()->has('message'))
        <div class="bg-green-100 border-green-400 text-green-700 p-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    {{-- Search --}}
    <div class="mb-4">
        <input type="text"
               wire:model.debounce.300ms="search"
               placeholder="Cari tiket..."
               class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring" />
    </div>

    {{-- Form --}}
    <form wire:submit.prevent="{{ $isEditMode ? 'update' : 'store' }}" class="mb-6 bg-white p-4 rounded shadow">
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-1">Judul</label>
            <input type="text" wire:model="judul" class="w-full px-3 py-2 border rounded" />
            @error('judul') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-1">Deskripsi</label>
            <textarea wire:model="deskripsi" class="w-full px-3 py-2 border rounded"></textarea>
            @error('deskripsi') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                {{ $isEditMode ? 'Update Tiket' : 'Buat Tiket' }}
            </button>
            @if($isEditMode)
                <button type="button" wire:click="resetForm" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded">
                    Batal
                </button>
            @endif
        </div>
    </form>

    {{-- Tabel yang Benar --}}
    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2">Judul</th>
                    <th class="border px-4 py-2">Deskripsi</th>
                    <th class="border px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-gray-50">
                        {{-- pastikan akses ke $ticket bukan $tickets --}}
                        <td class="border px-4 py-2">{{ $ticket->judul }}</td>
                        <td class="border px-4 py-2">{{ $ticket->deskripsi }}</td>
                        <td class="border px-4 py-2">
                            <button wire:click="edit({{ $ticket->id }})" class="bg-yellow-500 text-white px-3 py-1 rounded">Edit</button>
                            <button wire:click="delete({{ $ticket->id }})" class="bg-red-500 text-white px-3 py-1 rounded ml-2"
                                onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-4 text-gray-500">Tidak ada tiket.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>
