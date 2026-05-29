<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function log(
        string  $action,
        ?int    $userId = null,
        string  $description = null,
        string  $subjectType = null,
        int     $subjectId = null,
        Request $request = null
    ): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
