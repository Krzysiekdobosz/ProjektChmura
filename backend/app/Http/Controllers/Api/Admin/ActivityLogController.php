<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user:id,name,email')
            ->when($request->action, fn($q, $a) => $q->where('action', 'like', "%$a%"))
            ->when($request->user_id, fn($q, $u) => $q->where('user_id', $u))
            ->orderByDesc('created_at')
            ->paginate(50);

        return $this->paginated($logs, $logs->getCollection()->map(fn($l) => [
            'id'           => $l->id,
            'action'       => $l->action,
            'description'  => $l->description,
            'subject_type' => $l->subject_type,
            'subject_id'   => $l->subject_id,
            'ip_address'   => $l->ip_address,
            'user'         => $l->user ? ['id' => $l->user->id, 'name' => $l->user->name] : null,
            'created_at'   => $l->created_at?->toIso8601String(),
        ]));
    }
}
