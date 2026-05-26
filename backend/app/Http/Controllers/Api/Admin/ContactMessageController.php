<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ContactMessageStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactMessageController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $messages = ContactMessage::with('user:id,name,email')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->paginated($messages, $messages->getCollection()->map(fn($m) => $this->format($m)));
    }

    public function show(int $id): JsonResponse
    {
        $m = ContactMessage::with('user:id,name,email')->findOrFail($id);
        return $this->success($this->format($m));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'     => ['required', Rule::enum(ContactMessageStatus::class)],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $m = ContactMessage::findOrFail($id);
        $m->update(['status' => $request->status, 'admin_note' => $request->admin_note]);

        return $this->success(null, 'Status zaktualizowany.');
    }

    private function format(ContactMessage $m): array
    {
        return [
            'id'          => $m->id,
            'name'        => $m->name,
            'email'       => $m->email,
            'subject'     => $m->subject,
            'body'        => $m->body,
            'status'      => $m->status?->value,
            'status_label'=> $m->status?->label(),
            'admin_note'  => $m->admin_note,
            'user'        => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name] : null,
            'created_at'  => $m->created_at?->toIso8601String(),
        ];
    }
}
