<?php

namespace App\Livewire;

use App\Models\Ticket;
use Livewire\Component;
use Livewire\WithPagination;

class TicketCrud extends Component
{
    use WithPagination;

    public $judul = '';

    public $deskripsi = '';

    public $search = '';

    public $isEditMode = false;

    public $editId = null;

    protected $paginationTheme = 'tailwind';

    protected $rules = [
        'judul' => 'required|string|max:255',
        'deskripsi' => 'required|string',
    ];

    public function updatingSearch()
    {
        $this->resetPage(); // reset pagination saat cari berubah
    }

    public function render()
    {
        $tickets = Ticket::where('judul', 'like', '%'.$this->search.'%')
            ->latest()
            ->paginate(5);

        return view('livewire.ticket-crud', compact('tickets'));
    }

    public function store()
    {
        $this->validate();

        Ticket::create([
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
        ]);

        session()->flash('message', 'Tiket berhasil ditambahkan.');
        $this->resetForm();
    }

    public function edit($id)
    {
        $ticket = Ticket::findOrFail($id);
        $this->judul = $ticket->judul;
        $this->deskripsi = $ticket->deskripsi;
        $this->editId = $id;
        $this->isEditMode = true;
    }

    public function update()
    {
        $this->validate();

        Ticket::findOrFail($this->editId)->update([
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
        ]);

        session()->flash('message', 'Tiket berhasil diperbarui.');
        $this->resetForm();
    }

    public function delete($id)
    {
        Ticket::findOrFail($id)->delete();
        session()->flash('message', 'Tiket berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->judul = '';
        $this->deskripsi = '';
        $this->search = '';
        $this->editId = null;
        $this->isEditMode = false;
        $this->resetPage(); // kembali ke halaman pertama
    }
}
