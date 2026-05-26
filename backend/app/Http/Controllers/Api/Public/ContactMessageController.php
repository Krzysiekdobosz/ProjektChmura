<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\ContactMessageStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:200'],
            'subject' => ['required', 'string', 'max:200'],
            'body'    => ['required', 'string', 'max:3000'],
        ]);

        ContactMessage::create([
            'user_id'    => $request->user()?->id,
            'name'       => $request->name,
            'email'      => $request->email,
            'subject'    => $request->subject,
            'body'       => $request->body,
            'status'     => ContactMessageStatus::New->value,
            'ip_address' => $request->ip(),
        ]);

        return $this->created(null, 'Wiadomość została wysłana. Odpiszemy wkrótce.');
    }
}
