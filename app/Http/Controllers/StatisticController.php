<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Teacher;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Category;
use App\Models\Subject;
use App\Models\IncomeRecord;
use App\Models\Expense;
use App\Models\Result;
use Illuminate\Http\Request;

class StatisticController extends Controller
{

    public function getStats()
    {
        $academic_years = AcademicYear::where('status', '!=', 'ready')->get();
  
        $data = [];
        foreach($academic_years as $year) {
            $teachers = Teacher::where(function ($query) use ($year) {
                $query->where('academic_year_id', $year->id)
                      ->orWhere('active', true);
            })->get();

            $students = Student::where('academic_year_id', $year->id)->get();

            $guardians = [];
            $guardians_data = Guardian::all();
            foreach ($guardians_data as $guardian) {
                $children = array_filter($students->toArray(), function ($student) use ($guardian) {
                    return $student['guardian_id'] == $guardian->id;
                });
                if (count($children) > 0) {
                    $guardians[] = $guardian;
                }
            }
            
            $income_data = IncomeRecord::where('academic_year_id', $year->id)->get();
            $total_income = $income_data->sum('amount') + $students->sum('fees_amount_paid');

            $expenses_data = Expense::where('academic_year_id', $year->id)->get();
            $total_expenses = $expenses_data->sum('amount');

            $results_data = Result::where('academic_year_id', $year->id)->get();
            $results_mean_average = count($results_data) > 0 ? $results_data->sum('average') / count($results_data) : 0;


            $data[] = [
                'year' => str_replace(' / ', '-', $year->year),
                'number_of_teachers' => count($teachers),
                'number_of_guardians' => count($guardians),
                'number_of_students' => count($students),
                'total_income' => $total_income,
                'total_expenses' => $total_expenses,
                'results_mean_average' => round($results_mean_average, 2)
            ];
        }


        return response()->json([
            "status" => "success",
            "data" => $data
        ]);
    }





}
