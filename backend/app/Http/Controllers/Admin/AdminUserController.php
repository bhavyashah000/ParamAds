<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('organization')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.users', compact('users'));
    }

    public function edit(int $id)
    {
        $user = User::with('organization')->findOrFail($id);
        return view('admin.user-edit', compact('user'));
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:owner,admin,manager,analyst,viewer',
            'is_admin' => 'nullable|boolean',
        ]);

        $user->update($request->only('name', 'email', 'role', 'is_admin'));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('admin.users')->with('success', 'User updated successfully.');
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'Cannot delete the last admin user.');
        }
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'User deleted.');
    }
}
