<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\IncomeRecord;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeRecordController extends Controller
{
    public function index()
    {
        $income_records = IncomeRecord::all();
        return response()->json($income_records);
    }

    public function getRecords($ay_id)
    {
        $income_records = IncomeRecord::where('academic_year_id', $ay_id)->get();
        return response()->json(array_reverse($income_records->toArray()));
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required|string',
                'description' => 'required',
                'amount' => 'required'
            ]);
            $income_record = new IncomeRecord();
            $income_record->name = $request->input('name');
            $income_record->description = $request->input('description');
            $income_record->amount = $request->input('amount');

            $active_ay = AcademicYear::where('active', true)->first();
            $income_record->academic_year_id = $active_ay->id;

            $income_record->save();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'CREATE_INCOME_RECORD',
                'auditable_type' => IncomeRecord::class,
                'auditable_id' => $income_record->id,
                'description' => 'Income record ' . $income_record->name . ' created',
                'old_values' => null,
                'new_values' => json_encode($income_record),
                'ip_address' => $request->ip(),
                'academic_year_id' => $income_record->academic_year_id
            ]);

            return response()->json($income_record);
        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'name' => 'required|string',
                'description' => 'required',
                'amount' => 'required'
            ]);
            $income_record = IncomeRecord::where('id', $id)->first();
            $old_income_record = $income_record->replicate();
            $income_record->name = $request->input('name');
            $income_record->description = $request->input('description');
            $income_record->amount = $request->input('amount');
            $income_record->update();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'UPDATE_INCOME_RECORD',
                'auditable_type' => IncomeRecord::class,
                'auditable_id' => $income_record->id,
                'description' => 'Income record ' . $income_record->name . ' updated',
                'old_values' => json_encode($old_income_record),
                'new_values' => json_encode($income_record),
                'ip_address' => $request->ip(),
                'academic_year_id' => $income_record->academic_year_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $income_record->refresh()
            ]);
        });
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $income_record = IncomeRecord::where('id', $id)->first();
                $income_record->delete();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'DELETE_INCOME_RECORD',
                    'auditable_type' => IncomeRecord::class,
                    'auditable_id' => $id,
                    'description' => 'Income record ' . $income_record->name . ' deleted',
                    'old_values' => json_encode($income_record),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $income_record->academic_year_id
                ]);

            return response()->json([
                'status' => 'success'
            ]);
        });    
    }



}
