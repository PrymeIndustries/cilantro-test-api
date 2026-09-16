<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AcademicYear;
use App\Models\Inbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboxController extends Controller
{
    public function index()
    {
        $inboxes = Inbox::all();
        return response()->json($inboxes);
    }

    public function getInboxes($ay_id)
    {
        $inboxes = Inbox::where('academic_year_id', $ay_id)->get();
        return response()->json(array_reverse($inboxes->toArray()));
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'from_' => 'required|array|min:1',
                'from_.*' => 'required',
                'message' => 'required'
            ]);
            $inbox = new Inbox();
            $inbox->from_ = $request->input('from_');
            $inbox->message = $request->input('message');

            $active_ay = AcademicYear::where('active', true)->first();
            $inbox->academic_year_id = $active_ay->id;

            if ($inbox->save()) {
                // $name = $request->input('from_')[0]['name'];
                // AuditLog::create([
                //     'user_id' => $request->user()->id ?? 0,
                //     'action' => 'CREATE_INBOX_MESSAGE',
                //     'auditable_type' => Inbox::class,
                //     'auditable_id' => $inbox->id,
                //     'description' => 'Inbox message created',
                //     'old_values' => null,
                //     'new_values' => json_encode(['name' => $name, 'message' => $inbox->message]),
                //     'ip_address' => $request->ip(),
                //     'academic_year_id' => $inbox->academic_year_id
                // ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $inbox->refresh()
                ]);
            }
        });   
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $inbox = Inbox::where('id', $id)->first();
            if ($inbox->delete()) {
                AuditLog::create([
                    'user_id' => request()->user()->id ?? 0,
                    'action' => 'DELETE_INBOX_MESSAGE',
                    'auditable_type' => Inbox::class,
                    'auditable_id' => $id,
                    'description' => 'Inbox message deleted',
                    'old_values' => json_encode(['name' => $inbox->from_[0]['name'], 'message' => $inbox->message]),
                    'new_values' => null,
                    'ip_address' => request()->ip(),
                    'academic_year_id' => $inbox->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success'
                ]);
            }
        });   
    }


    public function setSeen($id)
    {
        return DB::transaction(function () use ($id) {
            $inbox = Inbox::find($id);
            if ($inbox) {
                $old_inbox = $inbox->replicate();
                $inbox->seen = true;
                if ($inbox->save()) {
                    // AuditLog::create([
                    //     'user_id' => request()->user()->id ?? 0,
                    //     'action' => 'MARK_INBOX_SEEN',
                    //     'auditable_type' => Inbox::class,
                    //     'auditable_id' => $id,
                    //     'description' => 'Inbox message marked as seen',
                    //     'old_values' => json_encode(['seen' => false]),
                    //     'new_values' => json_encode(['seen' => true]),
                    //     'ip_address' => request()->ip(),
                    //     'academic_year_id' => $inbox->academic_year_id
                    // ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $inbox->refresh()
                    ]);

                }

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Inbox record not found!'
                ], 522);
            }
        });   
    }


}
