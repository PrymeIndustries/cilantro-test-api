<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\AcademicYear;
use App\Models\SubCategory;
use App\Models\SubjectPeriod;
use App\Models\Teacher;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function index()
    {
        $timetables = Timetable::with(['category', 'subCategory'])->get();
        return response()->json($timetables);
    }

    public function show($id)
    {
        $timetable = Timetable::with(['category', 'subCategory', 'subjectPeriods.subject', 'subjectPeriods.teacher', 'subjectPeriods.subCategory'])
            ->find($id);
        if (!$timetable) {
            return response()->json(['status' => 'error', 'message' => 'Timetable not found'], 500);
        }
        return response()->json($timetable);
    }

    public function store(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $this->validate($request, [
                    'title'            => 'required|string',
                    'params'           => 'required|string',
                    'value'            => 'required|string',
                    'status'           => 'required|in:draft,active,archived',
                ]);

                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : 0;

                $timetable = new Timetable();
                $timetable->title            = $request->input('title');
                $timetable->params           = $request->input('params');
                $timetable->value            = $request->input('value');
                $timetable->category_id      = $request->input('category_id');
                $timetable->sub_category_id  = $request->input('sub_category_id');
                $timetable->is_general       = $request->input('is_general', false);
                $timetable->status           = $request->input('status', 'draft');
                $timetable->term_id          = $request->input('term_id');
                $timetable->save();

                // Run sync engine — throws ValidationException on conflict (rolls back transaction)
                $payloadData = is_string($request->input('params')) ? json_decode($request->params, true) : $request->input('params');
                $syncResult = $this->syncSubjectPeriods($timetable, $payloadData);
                if ($syncResult !== true) {
                    return $syncResult;
                }

                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : ($timetable->academic_year_id ?? 0);
                AuditLog::create([
                    'user_id'          => $request->user() ? $request->user()->id : 0,
                    'action'           => 'CREATE_TIMETABLE',
                    'auditable_type'   => Timetable::class,
                    'auditable_id'     => $timetable->id,
                    'description'      => 'Timetable "' . $timetable->title . '" created with status: ' . $timetable->status,
                    'old_values'       => null,
                    'new_values'       => json_encode($timetable->makeHidden('value')),
                    'ip_address'       => $request->ip(),
                    'academic_year_id' => $ay_id,
                ]);

                return response()->json([
                    'status' => 'success',
                    'data'   => $timetable->refresh()->load(['category', 'subCategory', 'subjectPeriods']),
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return conflict errors as a clean 422 JSON (Lumen does not auto-render ValidationException)
            return response()->json([
                'conflicts' => $e->errors()['conflicts'] ?? [],
            ], 422);
        }
    }

    public function update($id, Request $request)
    {
        try {
            return DB::transaction(function () use ($id, $request) {
                $this->validate($request, [
                    'title'  => 'required|string',
                    'params' => 'required|string',
                    'value'  => 'required|string',
                    'status' => 'required|in:draft,active,archived',
                ]);

                $timetable = Timetable::findOrFail($id);
                $old_timetable = $timetable->replicate();

                $timetable->title            = $request->input('title');
                $timetable->params           = $request->input('params');
                $timetable->value            = $request->input('value');
                $timetable->category_id      = $request->input('category_id');
                $timetable->sub_category_id  = $request->input('sub_category_id');
                $timetable->is_general       = $request->input('is_general', false);
                $timetable->status           = $request->input('status', 'draft');
                $timetable->term_id          = $request->input('term_id');

                $timetable->save();

                // Run sync engine — throws ValidationException on conflict (rolls back transaction)
                $payloadData = is_string($request->input('params')) ? json_decode($request->params, true) : $request->input('params');
                $syncResult = $this->syncSubjectPeriods($timetable, $payloadData);
                if ($syncResult !== true) {
                    return $syncResult;
                }

                $ay = AcademicYear::where('active', true)->first();
                $ay_id = $ay ? $ay->id : ($timetable->academic_year_id ?? 0);
                AuditLog::create([
                    'user_id'          => $request->user() ? $request->user()->id : 0,
                    'action'           => 'UPDATE_TIMETABLE',
                    'auditable_type'   => Timetable::class,
                    'auditable_id'     => $timetable->id,
                    'description'      => 'Timetable "' . $timetable->title . '" updated. Status: ' . $timetable->status,
                    'old_values'       => json_encode($old_timetable->makeHidden('value')),
                    'new_values'       => json_encode($timetable->makeHidden('value')),
                    'ip_address'       => $request->ip(),
                    'academic_year_id' => $ay_id,
                ]);

                return response()->json([
                    'status' => 'success',
                    'data'   => $timetable->refresh()->load(['category', 'subCategory', 'subjectPeriods']),
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return conflict errors as a clean 422 JSON (Lumen does not auto-render ValidationException)
            return response()->json([
                'conflicts' => $e->errors()['conflicts'] ?? [],
            ], 422);
        }
    }

    public function destroy($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $timetable = Timetable::find($id);
            if (!$timetable) {
                return response()->json(['status' => 'error', 'message' => 'Timetable not found'], 500);
            }

            $title = $timetable->title;

            $subjectPeriods = SubjectPeriod::where('timetable_id', $id)->get();
            foreach ($subjectPeriods as $subjectPeriod) {
                $subjectPeriod->delete();
            }
            $timetable->delete();

            $ay = AcademicYear::where('active', true)->first();
            $ay_id = $ay ? $ay->id : 0;
            AuditLog::create([
                'user_id'        => $request->user() ? $request->user()->id : 0,
                'action'         => 'DELETE_TIMETABLE',
                'auditable_type' => Timetable::class,
                'auditable_id'   => $id,
                'description'    => 'Timetable "' . $title . '" deleted',
                'old_values'     => json_encode($timetable->makeHidden('value')),
                'new_values'     => null,
                'ip_address'     => $request->ip(),
                'academic_year_id' => $ay_id,
            ]);

            return response()->json(['status' => 'success']);
        });
    }

    // =========================================================================
    // SYNC ENGINE
    // =========================================================================
    /**
     * Synchronises subject_periods with the timetable grid value.
     *
     * Returns true on success, or an HTTP error response on conflict.
     */
    private function syncSubjectPeriods(Timetable $timetable, array|string $params)
    {
        // 1. Purge existing periods for this timetable first
        SubjectPeriod::where('timetable_id', $timetable->id)->delete();

        if (in_array($timetable->status, ['draft', 'archived'])) {
            return true;
        }

        // 2. Parse grid data
        $grid = is_string($params) ? json_decode($params, true) : $params;

        if (!$grid || !isset($grid['cells']) || !is_array($grid['cells'])) {
            return true;
        }

        // Filter subject-type cells
        $subjectCells = array_values(array_filter($grid['cells'], fn($c) => ($c['type'] ?? '') === 'subject'));

        if (empty($subjectCells)) {
            return true;
        }

        // 3. Pre-load data without expanding 'null' into multiple streams
        $timetableSubCategoryId = $timetable->sub_category_id ?? null;

        $allStreamIds = array_filter(array_unique(array_merge(
            [$timetableSubCategoryId],
            array_column($subjectCells, 'sub_category_id')
        )));

        $scCodeMap = empty($allStreamIds) ? [] : SubCategory::whereIn('id', $allStreamIds)
            ->pluck('code', 'id')
            ->toArray();

        // Pre-load teacher names
        $teacherNameMap = [];
        $teacherIds = array_filter(array_column($subjectCells, 'teacher_id'));
        if (!empty($teacherIds)) {
            $teachers = Teacher::whereIn('id', array_unique($teacherIds))->get();
            foreach ($teachers as $t) {
                $teacherNameMap[$t->id] = trim($t->first_name . ' ' . $t->last_name);
            }
        }

        $toInsert = [];
        $conflicts = [];
        $now = Carbon::now()->toDateTimeString();

        $inMemoryTeacherSchedules = [];
        $inMemoryStreamSchedules = [];

        foreach ($subjectCells as $cell) {
            $day       = $cell['day']        ?? null;
            $startTime = $cell['start']      ?? null;
            $endTime   = $cell['end']        ?? null;
            $subjectId = $cell['subject_id'] ?? null;
            $teacherId = $cell['teacher_id'] ?? null;

            if (!$day || !$startTime || !$endTime || !$subjectId) {
                continue;
            }

            // If missing, fallback to the timetable's default. If that is also null, keep it null (Entire Class).
            $scId = $cell['sub_category_id'] ?? $timetableSubCategoryId ?? null;

            $startTimeSql = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
            $endTimeSql   = strlen($endTime)   === 5 ? $endTime   . ':00' : $endTime;

            // --- Teacher Overlap Check ---
            if ($teacherId) {
                $teacherConflict = SubjectPeriod::where('teacher_id', $teacherId)
                    ->where('day', $day)
                    ->where('start_time', '<', $endTimeSql)
                    ->where('end_time',   '>', $startTimeSql)
                    ->exists();

                if (!$teacherConflict) {
                    foreach ($inMemoryTeacherSchedules as $schedule) {
                        if ($schedule['teacher_id'] == $teacherId && $schedule['day'] == $day && 
                            $schedule['start_time'] < $endTimeSql && $schedule['end_time'] > $startTimeSql) {
                            $teacherConflict = true;
                            break;
                        }
                    }
                }

                if ($teacherConflict) {
                    $teacherName = $teacherNameMap[$teacherId] ?? 'ID:' . $teacherId;
                    $conflicts[] = "Teacher '{$teacherName}' has a scheduling conflict on {$day} ({$startTime} - {$endTime}).";
                } else {
                    $inMemoryTeacherSchedules[] = [
                        'teacher_id' => $teacherId,
                        'day' => $day,
                        'start_time' => $startTimeSql,
                        'end_time' => $endTimeSql
                    ];
                }
            }

            // --- Class Stream Overlap Check ---
            $streamQuery = SubjectPeriod::where('day', $day)
                ->where('start_time', '<', $endTimeSql)
                ->where('end_time',   '>', $startTimeSql);
            
            // Check specifically for null or the exact ID to avoid SQL errors
            if ($scId) {
                $streamQuery->where('sub_category_id', $scId);
            } else {
                $streamQuery->whereNull('sub_category_id');
            }

            $streamConflict = $streamQuery->exists();

            if (!$streamConflict) {
                foreach ($inMemoryStreamSchedules as $schedule) {
                    if ($schedule['sub_category_id'] == $scId && $schedule['day'] == $day && 
                        $schedule['start_time'] < $endTimeSql && $schedule['end_time'] > $startTimeSql) {
                        $streamConflict = true;
                        break;
                    }
                }
            }

            if ($streamConflict) {
                $scCode = $scId && isset($scCodeMap[$scId]) ? $scCodeMap[$scId] : 'Entire Class';
                $conflicts[] = "Class stream '{$scCode}' has a scheduling conflict on {$day} ({$startTime} - {$endTime}).";
            } else {
                $inMemoryStreamSchedules[] = [
                    'sub_category_id' => $scId,
                    'day' => $day,
                    'start_time' => $startTimeSql,
                    'end_time' => $endTimeSql
                ];
            }

            // --- Build row ---
            $timeStr = $this->formatTimeString($startTime) . ' - ' . $this->formatTimeString($endTime);
            if ($scId && isset($scCodeMap[$scId])) {
                $timeStr .= ' (' . $scCodeMap[$scId] . ')';
            }

            $toInsert[] = [
                'timetable_id'    => $timetable->id,
                'sub_category_id' => $scId,
                'subject_id'      => $subjectId,
                'teacher_id'      => $teacherId ?: null,
                'day'             => $day,
                'start_time'      => $startTimeSql,
                'end_time'        => $endTimeSql,
                'time'            => $timeStr,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        // 4. Handle validation errors
        if (!empty($conflicts)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'conflicts' => array_values(array_unique($conflicts)),
            ]);
        }

        // 5. DEDUPLICATE prior to insert
        if (!empty($toInsert)) {
            // Collect unique key combinations to guard against double insertion
            $uniqueToInsert = [];
            foreach ($toInsert as $row) {
                $key = implode('-', [
                    $row['timetable_id'],
                    $row['sub_category_id'] ?? 'null', // Fixed key generation for null streams
                    $row['subject_id'],
                    $row['day'],
                    $row['start_time'],
                    $row['end_time']
                ]);
                $uniqueToInsert[$key] = $row;
            }

            SubjectPeriod::insert(array_values($uniqueToInsert));
        }

        return true;
    }

    /**
     * Converts "HH:MM" 24-hour time to "h:mm am/pm" display format.
     * e.g. "13:00" → "1:00 pm", "07:30" → "7:30 am"
     */
    private function formatTimeString(string $time): string
    {
        $parts = explode(':', $time);
        $hour   = (int) ($parts[0] ?? 0);
        $minute = $parts[1] ?? '00';
        $ampm   = $hour >= 12 ? 'pm' : 'am';
        $hour12 = $hour % 12;
        if ($hour12 === 0) $hour12 = 12;
        return $hour12 . ':' . $minute . ' ' . $ampm;
    }
}
