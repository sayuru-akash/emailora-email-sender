<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate($this->perPage($request->input('per_page')))
            ->withQueryString();

        return Inertia::render('Users/Index', ['users' => $this->pagination($users), 'filters' => $request->only(['search', 'role', 'status', 'per_page'])]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Form', ['userRecord' => null]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('users.index')->with('success', 'User saved.');
    }

    public function show(User $user): Response
    {
        return Inertia::render('Users/Show', ['userRecord' => $user]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Form', ['userRecord' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        if ($request->user()->role === 'admin' && in_array($user->role, ['owner', 'admin'], true)) {
            return back()->with('error', 'Admins cannot edit owner or admin users.');
        }

        $data = $request->validated();
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);

        return back()->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Users cannot delete themselves.');
        }

        if ($request->user()->role === 'admin' && in_array($user->role, ['owner', 'admin'], true)) {
            return back()->with('error', 'Admins cannot delete owner or admin users.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
