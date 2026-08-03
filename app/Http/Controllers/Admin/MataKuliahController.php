<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MataKuliahController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/MataKuliah/Index', [
            'coursesList' => MataKuliah::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_mk' => 'required|string|unique:mata_kuliah,kode_mk|max:50',
            'nama' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'required|integer|min:1|max:8',
        ]);

        MataKuliah::create($validated);

        return redirect()->back()->with('success', 'Mata Kuliah berhasil ditambahkan.');
    }

    public function update(Request $request, MataKuliah $matkul)
    {
        $validated = $request->validate([
            'kode_mk' => 'required|string|max:50|unique:mata_kuliah,kode_mk,' . $matkul->id,
            'nama' => 'required|string|max:255',
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'required|integer|min:1|max:8',
        ]);

        $matkul->update($validated);

        return redirect()->back()->with('success', 'Mata Kuliah berhasil diperbarui.');
    }

    public function destroy(MataKuliah $matkul)
    {
        $matkul->delete();
        return redirect()->back()->with('success', 'Mata Kuliah berhasil dihapus.');
    }
}