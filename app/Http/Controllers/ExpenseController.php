<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Expense;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::all();
        return response()->json($expenses);
    }

    public function getExpenses($ay_id)
    {
        $expenses = Expense::where('academic_year_id', $ay_id)->get();
        return response()->json(array_reverse($expenses->toArray()));
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required|string',
                'description' => 'required',
                'amount' => 'required'
            ]);
            $expense = new Expense();
            $expense->name = $request->input('name');
            $expense->description = $request->input('description');
            $expense->amount = $request->input('amount');

            $active_ay = AcademicYear::where('active', true)->first();
            $expense->academic_year_id = $active_ay->id;

            $expense->save();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'CREATE_EXPENSE',
                'auditable_type' => Expense::class,
                'auditable_id' => $expense->id,
                'description' => 'Expense ' . $expense->name . ' created',
                'old_values' => null,
                'new_values' => json_encode($expense),
                'ip_address' => $request->ip(),
                'academic_year_id' => $expense->academic_year_id
            ]);

            return response()->json($expense);
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
            $expense = Expense::where('id', $id)->first();
            $old_expense = $expense->replicate();
            $expense->name = $request->input('name');
            $expense->description = $request->input('description');
            $expense->amount = $request->input('amount');
            $expense->update();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'UPDATE_EXPENSE',
                'auditable_type' => Expense::class,
                'auditable_id' => $expense->id,
                'description' => 'Expense ' . $expense->name . ' updated',
                'old_values' => json_encode($old_expense),
                'new_values' => json_encode($expense),
                'ip_address' => $request->ip(),
                'academic_year_id' => $expense->academic_year_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $expense->refresh()
            ]);
        });
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $expense = Expense::where('id', $id)->first();
                $expense->delete();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'DELETE_EXPENSE',
                    'auditable_type' => Expense::class,
                    'auditable_id' => $id,
                    'description' => 'Expense ' . $expense->name . ' deleted',
                    'old_values' => json_encode($expense),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $expense->academic_year_id
                ]);

            return response()->json([
                'status' => 'success'
            ]);
        }); 
    }



}
