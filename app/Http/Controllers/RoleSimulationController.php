<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSimulationController extends Controller
{
    public function switchRole(Request $request)
    {
        $request->validate([
            'role' => 'required|in:Super Admin,Project Manager,Pengawas Lapangan,Purchasing,Finance/Direksi',
        ]);

        $roleEmailMap = [
            'Super Admin' => 'admin@konstruksi.id',
            'Project Manager' => 'pm@konstruksi.id',
            'Pengawas Lapangan' => 'pengawas@konstruksi.id',
            'Purchasing' => 'purchasing@konstruksi.id',
            'Finance/Direksi' => 'direksi@konstruksi.id',
        ];

        $email = $roleEmailMap[$request->role];
        $user = User::where('email', $email)->first();

        if ($user) {
            Auth::login($user);
            return redirect()->back()->with('success', "Beralih peran aktif sebagai: {$request->role} ({$user->name})");
        }

        return redirect()->back()->with('error', "Pengguna untuk peran {$request->role} tidak ditemukan.");
    }
}
