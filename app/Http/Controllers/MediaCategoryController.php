<?php

namespace App\Http\Controllers;

use App\Models\MediaCategory;
use App\Models\AuditLog;
use App\Models\AcademicYear;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MediaCategoryController extends Controller
{
    public function index (): JsonResponse
    {
        return response()->json([
            'data' => MediaCategory::all()
        ]);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'name' => 'required'
            ]);
            $category = new MediaCategory();
            $category->name = $request->input('name');
            $category->user_id = auth()->id();
            $category->code = uniqid('SG');
            if ($category->save()) {
                $active_ay = AcademicYear::where('active', true)->first();
                AuditLog::create([
                    'user_id' => auth()->id() ?? 0,
                    'action' => 'CREATE_MEDIA_CATEGORY',
                    'auditable_type' => MediaCategory::class,
                    'auditable_id' => $category->id,
                    'description' => 'Media category ' . $category->name . ' created',
                    'old_values' => null,
                    'new_values' => json_encode($category),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $active_ay ? $active_ay->id : 0
                ]);

                return redirect()->route('folders');
            }
        });
        
    }


}
