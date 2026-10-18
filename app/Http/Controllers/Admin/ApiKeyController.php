<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApiKeyRequest;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/api-keys', [
            'apiKeys' => ApiKey::query()
                ->with('user:id,name')
                ->latest()
                ->get()
                ->map(fn (ApiKey $k) => [
                    'id' => $k->id,
                    'name' => $k->name,
                    'prefix' => $k->prefix,
                    'user' => $k->user ? ['id' => $k->user->id, 'name' => $k->user->name] : null,
                    'last_used_at' => $k->last_used_at?->toIso8601String(),
                    'revoked_at' => $k->revoked_at?->toIso8601String(),
                    'created_at' => $k->created_at?->toIso8601String(),
                ])
                ->all(),
            'users' => User::orderBy('name')->get(['id', 'name'])->toArray(),
        ]);
    }

    /** Creates a key and flashes the plaintext ONCE as `flash.api_key`; only its SHA-256 is stored. */
    public function store(StoreApiKeyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $owner = isset($data['user_id']) ? User::findOrFail($data['user_id']) : $request->user();

        [$key, $plain] = ApiKey::generate($data['name'], $owner);

        Inertia::flash([
            'success' => 'API key created. Copy it now: it will not be shown again.',
            'api_key' => ['id' => $key->id, 'name' => $key->name, 'key' => $plain],
        ]);

        return redirect()->route('admin.api-keys.index');
    }

    /** Revokes the key (kept for audit; the webhook rejects it from now on). */
    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        if ($apiKey->revoked_at === null) {
            $apiKey->update(['revoked_at' => now()]);
        }

        Inertia::flash('success', 'API key revoked.');

        return redirect()->route('admin.api-keys.index');
    }
}
