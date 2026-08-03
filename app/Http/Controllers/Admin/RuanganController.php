<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RuanganController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Ruangan/Index', [
            'roomsList' => Ruangan::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_ruangan' => 'required|string|unique:ruangan,nama_ruangan|max:100',
            'kapasitas' => 'required|integer|min:1',
            'tipe' => 'required|string|in:kelas,lab',
        ]);

        Ruangan::create($request->all());

        return redirect()->back()->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function update(Request $request, Ruangan $ruangan)
    {
        $request->validate([
            'nama_ruangan' => 'required|string|max:100|unique:ruangan,nama_ruangan,' . $ruangan->id,
            'kapasitas' => 'required|integer|min:1',
            'tipe' => 'required|string|in:kelas,lab',
        ]);

        $ruangan->update($request->all());

        return redirect()->back()->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function destroy(Ruangan $ruangan)
    {
        $ruangan->delete();
        return redirect()->back()->with('success', 'Ruangan berhasil dihapus.');
    }
}
