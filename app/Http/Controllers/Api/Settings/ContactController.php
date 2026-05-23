<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends BaseApiController
{
    public function index(Request $request)
    {
        $contacts = Contact::query()
            ->when($request->filled('role'), function ($query) use ($request): void {
                $role = $request->string('role');
                if ($role === 'supplier') {
                    $query->whereJsonContains('tags', 'supplier');
                }
                if ($role === 'client' || $role === 'customer') {
                    $query->whereJsonContains('tags', 'customer');
                }
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'type']);

        return $this->success($contacts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'national_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:255'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $contact = Contact::query()->create($validated);

        return $this->success(
            $contact->only(['id', 'name', 'phone', 'email', 'national_id', 'address', 'type', 'tags', 'notes', 'is_active']),
            'created',
            201
        );
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'national_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:255'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $contact->fill($validated);
        $contact->save();

        return $this->success(
            $contact->only(['id', 'name', 'phone', 'email', 'national_id', 'address', 'type', 'tags', 'notes', 'is_active']),
            'updated'
        );
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return $this->success(['id' => $contact->id], 'deleted');
    }
}

