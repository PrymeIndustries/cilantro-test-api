<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AcademicYear;
use App\Packages\JsonStorage\JsonStorage;
use Illuminate\Http\Request;

class TricorderController extends Controller
{
    public function setup_tricorder(Request $request)
    {
        $this->validate($request, [
            'enabled' => 'required'
        ]);

        $store = new JsonStorage();
        $data = $request->all();
        if ($data['enabled'] == 1 || $data['enabled'] == 0) {
            $enabled = $data['enabled'];
        } else {
            $enabled = 0;
        }
        $store->set('tricorder', [
            'enabled' => $enabled
        ]);
        $store->save();

        $ay = AcademicYear::where('active', true)->first();
        $ay_id = $ay ? $ay->id : 0;

        AuditLog::create([
            'user_id' => $request->user()->id ?? 0,
            'action' => 'UPDATE_TRICORDER_SETTINGS',
            'auditable_type' => '@Tricorder',
            'auditable_id' => 0,
            'description' => 'Tricorder settings updated',
            'old_values' => null,
            'new_values' => json_encode($data),
            'ip_address' => $request->ip(),
            'academic_year_id' => $ay_id
        ]);

        return [
            'status' => 'success',
            'data' => $store->get('tricorder', [])
        ];
    }


    public function verify_tricorder()
    {
        $store = new JsonStorage();
        $tc = $store->get('tricorder', false);
        return response()->json([
            'status' => 'success',
            'data' => $tc
        ]);
    }



}
