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
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'type']);

        return $this->success($contacts);
    }
}

