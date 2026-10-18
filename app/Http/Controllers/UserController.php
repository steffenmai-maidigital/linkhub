<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Directory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Benutzerverwaltung (Administratoren): eigene Konten anlegen, Rechte
 * vergeben, Passwörter zurücksetzen, Konten sperren. Verzeichniskonten
 * werden nur angezeigt — ihre Rechte kommen aus den Gruppen.
 *
 * User management (administrators): create own accounts, grant rights,
 * reset passwords, lock accounts. Directory accounts are only
 * displayed — their rights come from the groups.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::orderBy('name');
        // alter Parameter ?suche= (Lesezeichen) gilt weiter / old parameter ?suche= (bookmarks) still works
        if ($s = trim((string) $request->input('search', $request->input('suche')))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('username', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%"));
        }

        return view('settings.users', [
            'users'       => $query->get(),
            'directory'   => Directory::enabled(),
            'credentials' => session('credentials'),
            // alter Parameter ?bearbeiten= gilt weiter / old parameter ?bearbeiten= still works
            'editing'     => ($id = $request->integer('edit', $request->integer('bearbeiten'))) ? User::find($id) : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $password = $data['password'] ?: User::initialPassword();

        $user = User::create([
            'username'    => $data['username'],
            'name'        => $data['name'],
            'email'       => $data['email'] ?? null,
            'auth_source' => User::LOCAL,
            'password'    => $password,
            'is_admin'    => $request->boolean('is_admin'),
            'is_manager'  => $request->boolean('is_manager') || $request->boolean('is_admin'),
            'active'      => true,
        ]);
        activity('Benutzer')->causedBy($request->user())->performedOn($user)->log('Eigenes Konto angelegt');

        return redirect()->route('users.index')
            ->with('ok', __('Konto :name angelegt.', ['name' => $user->name]))
            ->with('credentials', ['username' => $user->username, 'password' => $password]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if ($user->isLocal()) {
            if ($user->is($request->user()) && ! $request->boolean('is_admin')) {
                return back()->withErrors(['is_admin' => __('Sie können sich die Admin-Rechte nicht selbst entziehen.')]);
            }
            $user->fill([
                'username'   => $data['username'],
                'name'       => $data['name'],
                'email'      => $data['email'] ?? null,
                'is_admin'   => $request->boolean('is_admin'),
                'is_manager' => $request->boolean('is_manager') || $request->boolean('is_admin'),
            ]);
            if ($data['password']) {
                $user->password = $data['password'];
                \Illuminate\Support\Facades\RateLimiter::clear('sign-in:' . $user->username);
            }
        }
        if (! $user->is($request->user())) {
            $user->active = $request->boolean('active');
        }
        $user->mfa_policy = $data['mfa_policy'] ?: null;
        $user->save();
        activity('Benutzer')->causedBy($request->user())->performedOn($user)->log('Konto geändert');

        return redirect()->route('users.index')->with('ok', __('Konto gespeichert.'));
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_unless($user->isLocal(), 403, 'Verzeichniskonten haben hier kein Passwort.');
        $password = User::initialPassword();
        $user->forceFill(['password' => $password])->save();
        \Illuminate\Support\Facades\RateLimiter::clear('sign-in:' . $user->username);
        activity('Benutzer')->causedBy($request->user())->performedOn($user)->log('Passwort zurückgesetzt');

        return redirect()->route('users.index')
            ->with('ok', __('Neues Passwort für :name vergeben.', ['name' => $user->name]))
            ->with('credentials', ['username' => $user->username, 'password' => $password]);
    }

    /**
     * Zwei-Faktor zurücksetzen (Handy verloren) — die Person richtet ihn danach neu ein
     *
     * Reset two-factor (phone lost) — the person then sets it up again
     */
    public function resetTwoFactor(Request $request, User $user)
    {
        $user->disableTwoFactor();
        activity('Benutzer')->causedBy($request->user())->performedOn($user)->log('Zwei-Faktor zurückgesetzt');

        return redirect()->route('users.index')->with('ok', __('Zwei-Faktor-Anmeldung für :name zurückgesetzt.', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => __('Das eigene Konto kann nicht gelöscht werden.')]);
        }
        if ($user->is_admin && User::where('is_admin', true)->where('active', true)->count() <= 1) {
            return back()->withErrors(['user' => __('Der letzte Administrator kann nicht gelöscht werden.')]);
        }
        activity('Benutzer')->causedBy($request->user())->withProperties(['username' => $user->username])->log('Konto gelöscht');
        $user->delete();

        return redirect()->route('users.index')->with('ok', __('Konto gelöscht.'));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-z0-9._@\-]+$/',
                Rule::unique('users', 'username')->ignore($user?->id)],
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'max:200'],
            'mfa_policy' => ['nullable', 'in:required,optional,off'],
        ], [
            'username.regex' => __('Der Benutzername darf nur Kleinbuchstaben, Ziffern, Punkt, Minus, Unterstrich und @ enthalten.'),
        ]);
        $data['username'] = mb_strtolower($data['username']);
        $data['password'] ??= null;
        $data['mfa_policy'] ??= null;

        return $data;
    }
}
