<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

        return response()->json($logs);
    }

    public function getAuditLogs($ay_id)
    {
        $logs = AuditLog::with('user')
                ->where('academic_year_id', $ay_id)
                ->get();
        return response()->json(array_reverse($logs->toArray()));
    }

    public function search(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->has('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('auditable_type')) {
            $query->where('auditable_type', 'like', '%' . $request->auditable_type . '%');
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data'   => $logs
        ]);
    }
}
