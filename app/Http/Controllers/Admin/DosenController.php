<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DosenController extends Controller
{
    public function index()
    {
        $dosen = Dosen::with('user')->get();
        return Inertia::render('Admin/Dosen/Index', [
            'dosenList' => $dosen
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode_dosen' => 'required|string|unique:dosen,kode_dosen|max:50',
            'email' => 'required|email|unique:users,email',
            'program_studi' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => 'Dosen ' . $request->nama,
                'email' => $request->email,
                'password' => Hash::make('password'), // default password
                'role' => 'dosen',
            ]);

            Dosen::create([
                'user_id' => $user->id,
                'kode_dosen' => $request->kode_dosen,
                'nama' => $request->nama,
                'program_studi' => $request->program_studi,
            ]);
        });

        return redirect()->back()->with('success', 'Dosen berhasil ditambahkan.');
    }

    public function update(Request $request, Dosen $dosen)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kode_dosen' => 'required|string|max:50|unique:dosen,kode_dosen,' . $dosen->id,
            'email' => 'required|email|unique:users,email,' . $dosen->user_id,
            'program_studi' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request, $dosen) {
            $dosen->user->update([
                'name' => 'Dosen ' . $request->nama,
                'email' => $request->email,
            ]);

            $dosen->update([
                'kode_dosen' => $request->kode_dosen,
                'nama' => $request->nama,
                'program_studi' => $request->program_studi,
            ]);
        });

        return redirect()->back()->with('success', 'Dosen berhasil diperbarui.');
    }

    public function destroy(Dosen $dosen)
    {
        // Deleting the user will cascade delete the Dosen
        $dosen->user->delete();

        return redirect()->back()->with('success', 'Dosen berhasil dihapus.');
    }
}
