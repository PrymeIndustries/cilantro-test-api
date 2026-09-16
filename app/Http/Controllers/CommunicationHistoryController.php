<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\CommunicationHistory;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunicationHistoryController extends Controller
{
    public function index()
    {
        $history = CommunicationHistory::all();
        return response()->json($history);
    }

    public function getHistory($ay_id)
    {
        $history = CommunicationHistory::where('academic_year_id', $ay_id)->get();
        return response()->json(array_reverse($history->toArray()));
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'to' => 'required|array|min:1',
                'to.*' => 'required',
                'message' => 'required'
            ]);
            $history = new CommunicationHistory();
            $history->to = $request->input('to');
            $history->message = $request->input('message');

            $active_ay = AcademicYear::where('active', true)->first();
            $history->academic_year_id = $active_ay->id;

            if ($history->save()) {
                // $name = $request->input('to')[1];
                // AuditLog::create([
                //     'user_id' => $request->user()->id ?? 0,
                //     'action' => 'CREATE_COMMUNICATION_HISTORY',
                //     'auditable_type' => CommunicationHistory::class,
                //     'auditable_id' => $history->id,
                //     'description' => 'Communication history record created',
                //     'old_values' => null,
                //     'new_values' => json_encode(['name' => $name, 'message' => $history->message]),
                //     'ip_address' => $request->ip(),
                //     'academic_year_id' => $history->academic_year_id
                // ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $history->refresh()
                ]);

            }

        });
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $history = CommunicationHistory::where('id', $id)->first();
            if ($history->delete()) {
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'DELETE_COMMUNICATION_HISTORY',
                    'auditable_type' => CommunicationHistory::class,
                    'auditable_id' => $id,
                    'description' => 'Communication log deleted',
                    'old_values' => json_encode(['name' => $history->to[1], 'message' => $history->message]),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $history->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
            }
        });    
    }




}
