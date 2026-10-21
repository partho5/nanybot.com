<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Models\UsernameRedirect;
use App\Services\Blog\ImageCleaner;
use App\Support\SocialNetworks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private ImageCleaner $images) {}

    /** Admin only (route middleware). */
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->withCount('articles')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('name', 'ilike', $like)->orWhere('email', 'ilike', $like)->orWhere('username', 'ilike', $like));
            })
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'role' => $u->role->value,
                'avatar' => $u->avatar,
                'headline' => $u->headline,
                'articles_count' => $u->articles_count,
                'created_at' => $u->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/users', [
            'users' => $users,
            'filters' => ['q' => $q],
            'roles' => $this->roleOptions(),
        ]);
    }

    /** Admin only (route middleware). */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = new User;
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'] ?? $this->generateUsername($data['name']),
            'role' => $data['role'],
            'password' => $data['password'],
            'email_verified_at' => now(),
        ])->save();

        Inertia::flash('success', 'User created.');

        return redirect()->route('admin.users.index');
    }

    /** Open to any signed-in user: admins edit anyone, authors only themselves. */
    public function edit(Request $request, User $user): Response
    {
        $this->ensureCanEdit($request->user(), $user);

        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'avatar' => $user->avatar,
                'headline' => $user->headline,
                'bio' => $user->bio,
                'social_links' => (object) ($user->social_links ?? []),
                'author_url' => $user->authorUrl(),
            ],
            'roles' => $this->roleOptions(),
            'socialNetworks' => SocialNetworks::forClient(),
            'isAdmin' => $request->user()->isAdmin(),
            'isSelf' => $request->user()->is($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();

        // Role and password resets are admin-only; authors can never change their role.
        if (! $actor->isAdmin()) {
            unset($data['role'], $data['password']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (array_key_exists('social_links', $data)) {
            $data['social_links'] = $data['social_links'] ?: null;
        }

        $oldUsername = $user->username;
        $oldAvatar = $user->avatar;

        $user->fill($data)->save();

        // The old public author URL keeps working (301) after a rename.
        if ($oldUsername && $user->username !== $oldUsername) {
            UsernameRedirect::updateOrCreate(['username' => $oldUsername], ['user_id' => $user->id]);
        }
        if ($user->username) {
            $user->usernameRedirects()->where('username', $user->username)->delete();
        }

        // A replaced or removed avatar is deleted from Cloudinary unless it is used elsewhere.
        if ($oldAvatar && $user->avatar !== $oldAvatar) {
            $this->images->purgeUnused([$oldAvatar]);
        }

        Inertia::flash('success', 'Profile updated.');

        return redirect()->route('admin.users.edit', $user);
    }

    /** Admin only (route middleware). */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            Inertia::flash('error', 'You cannot delete your own account here.');

            return redirect()->route('admin.users.index');
        }

        // articles.author_id is restrictOnDelete: reassign or delete the articles first.
        if ($user->articles()->exists()) {
            Inertia::flash('error', "Cannot delete {$user->name}: they still have articles. Reassign or delete them first.");

            return redirect()->route('admin.users.index');
        }

        $user->delete();

        Inertia::flash('success', 'User deleted.');

        return redirect()->route('admin.users.index');
    }

    private function ensureCanEdit(User $actor, User $target): void
    {
        abort_unless($actor->isAdmin() || $actor->is($target), 403);
    }

    /** @return list<array{value: string, label: string}> */
    private function roleOptions(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $r) => ['value' => $r->value, 'label' => ucfirst($r->value)])
            ->all();
    }

    private function generateUsername(string $name): string
    {
        $base = Str::limit(Str::slug($name) ?: 'author', 50, '');

        do {
            $username = $base.'-'.Str::lower(Str::random(4));
        } while (User::where('username', $username)->exists());

        return $username;
    }
}
