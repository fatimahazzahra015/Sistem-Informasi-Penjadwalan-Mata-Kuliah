<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/User/Index', [
            'usersList' => User::with('dosen')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,dosen',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            // If the role is dosen, automatically create a linked dosen record if it doesn't exist
            if ($request->role === 'dosen') {
                $initials = collect(explode(' ', $request->name))
                    ->map(fn($n) => substr($n, 0, 1))
                    ->implode('');
                
                Dosen::create([
                    'user_id' => $user->id,
                    'kode_dosen' => strtoupper($initials) . rand(10, 99),
                    'nama' => $request->name,
                    'program_studi' => 'Teknik Informatika',
                ]);
            }
        });

        return redirect()->back()->with('success', 'Pengguna berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|string|in:admin,dosen',
            'password' => 'nullable|string|min:8',
        ]);

        DB::transaction(function () use ($request, $user) {
            $oldRole = $user->role;
            
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            // Handle Dosen record transitions
            if ($request->role === 'dosen' && $oldRole !== 'dosen') {
                // Became a dosen, create record
                $initials = collect(explode(' ', $request->name))
                    ->map(fn($n) => substr($n, 0, 1))
                    ->implode('');

                Dosen::create([
                    'user_id' => $user->id,
                    'kode_dosen' => strtoupper($initials) . rand(10, 99),
                    'nama' => $request->name,
                    'program_studi' => 'Teknik Informatika',
                ]);
            } elseif ($request->role !== 'dosen' && $oldRole === 'dosen') {
                // No longer a dosen, delete record
                Dosen::where('user_id', $user->id)->delete();
            }
        });

        return redirect()->back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete(); // Cascades to delete linked Dosen as well
        return redirect()->back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
