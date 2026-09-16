<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\ReportSheet;
use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportSheetController extends Controller
{
    public function index()
    {
        $data = ReportSheet::with(['student', 'result'])->get();
        $rss = [];
        foreach ($data as $rs) {
            $decompressed_rs = $this->_decompressHtmlString($rs->sheet);
            if ($decompressed_rs) {
                $rs->sheet = $decompressed_rs;
            }
            $rss[] = $rs;
        }
        return response()->json($rss);
    }

    public function getReportSheets($ay_id)
    {
        $rss = ReportSheet::with(['student', 'result'])
                    ->where('academic_year_id', $ay_id)
                    ->get();
        $filtered_rss = [];
        foreach ($rss as $rs) {
            $decompressed_rs = $this->_decompressHtmlString($rs->sheet);
            if ($decompressed_rs) {
                $rs->sheet = $decompressed_rs;
            }
            $filtered_rss[] = $rs;
        }
        return response()->json($filtered_rss);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'student_id' => 'required',
                'result_id' => 'required',
                'sheet' => 'required'
            ]);

            $rs = new ReportSheet();
            $rs->student_id = $request->input('student_id');
            $rs->result_id = $request->input('result_id');

            $compressed_rs = $this->_compressHtmlString($request->input('sheet'));
            if ($compressed_rs) {
                $rs->sheet = $compressed_rs;
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to process sheet'
                ], 522);
            }

            $active_term = Term::where('active', true)->first();
            if (count((array)$active_term) == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active term'
                ], 522);
            }
            $rs->term_id = $active_term->id;

            $active_ay = AcademicYear::where('active', true)->first();
            $rs->academic_year_id = $active_ay->id;

            $rs->save();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'CREATE_REPORT_SHEET',
                'auditable_type' => ReportSheet::class,
                'auditable_id' => $rs->id,
                'description' => 'Report sheet created for student ID ' . $rs->student_id,
                'old_values' => null,
                'new_values' => json_encode(['student_id' => $rs->student_id, 'result_id' => $rs->result_id]),
                'ip_address' => $request->ip(),
                'academic_year_id' => $rs->academic_year_id
            ]);

            $rs->sheet = $this->_decompressHtmlString($rs->sheet);

            return response()->json([
                'status' => 'success',
                'data' => $rs//->refresh()
            ]);
        });
        
    }

    public function storeList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $errors = [];
                if (!isset($record['student_id'])) {
                    $errors[] = 'Student id field is required';
                }
                if (!isset($record['result_id'])) {
                    $errors[] = 'Result id field is required';
                }
                if (!isset($record['sheet'])) {
                    $errors[] = 'Sheet field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }
                $rs = new ReportSheet();
                $rs->student_id = $record['student_id'];
                $rs->result_id = $record['result_id'];
                $compressed_rs = $this->_compressHtmlString($record['sheet']);
                if ($compressed_rs) {
                    $rs->sheet = $compressed_rs;
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unable to process sheet'
                    ], 522);
                }

                if (isset($record['term_id'])) {
                    $rs->term_id = $record['term_id'];
                } else {
                    $active_term = Term::where('active', true)->first();
                    if (count((array)$active_term) == 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No active term'
                        ], 522);
                    }
                    $rs->term_id = $active_term->id;
                }

                if (isset($record['academic_year_id'])) {
                    $rs->academic_year_id = $record['academic_year_id'];
                } else {
                    $active_ay = AcademicYear::where('active', true)->first();
                    $rs->academic_year_id = $active_ay->id;
                }

                $rs->save();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'CREATE_REPORT_SHEET_BATCH',
                    'auditable_type' => ReportSheet::class,
                    'auditable_id' => $rs->id,
                    'description' => 'Report sheet created for student ID ' . $rs->student_id . ' in batch',
                    'old_values' => null,
                    'new_values' => json_encode(['student_id' => $rs->student_id, 'result_id' => $rs->result_id]),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $rs->academic_year_id
                ]);
            }
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function update($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $this->validate($request, [
                'student_id' => 'required',
                'result_id' => 'required',
                'sheet' => 'required'
            ]);
            $rs = ReportSheet::where('id', $id)->first();
            $old_rs = ['student_id' => $rs->student_id, 'result_id' => $rs->result_id];
            $rs->student_id = $request->input('student_id');
            $rs->result_id = $request->input('result_id');
            $rs->update();

            AuditLog::create([
                'user_id' => $request->user()->id ?? 0,
                'action' => 'UPDATE_REPORT_SHEET',
                'auditable_type' => ReportSheet::class,
                'auditable_id' => $rs->id,
                'description' => 'Report sheet updated for student ID ' . $rs->student_id,
                'old_values' => json_encode($old_rs),
                'new_values' => json_encode(['student_id' => $rs->student_id, 'result_id' => $rs->result_id]),
                'ip_address' => $request->ip(),
                'academic_year_id' => $rs->academic_year_id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $rs->refresh()
            ]);
        });
        

    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $rs = ReportSheet::where('id', $id)->first();
            $rs->delete();

            AuditLog::create([
                'user_id' => request()->user()->id ?? 0,
                'action' => 'DELETE_REPORT_SHEET',
                'auditable_type' => ReportSheet::class,
                'auditable_id' => $id,
                'description' => 'Report sheet deleted for student ID ' . $rs->student_id,
                'old_values' => json_encode(['student_id' => $rs->student_id, 'result_id' => $rs->result_id]),
                'new_values' => null,
                'ip_address' => request()->ip(),
                'academic_year_id' => $rs->academic_year_id
            ]);

            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function destroyList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $rs = ReportSheet::find($record['id']);
                $rs->delete();

                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'DELETE_REPORT_SHEET_BATCH',
                    'auditable_type' => ReportSheet::class,
                    'auditable_id' => $record['id'],
                    'description' => 'Report sheet deleted for student ID ' . $rs->student_id . ' in batch',
                    'old_values' => json_encode(['student_id' => $rs->student_id, 'result_id' => $rs->result_id]),
                    'new_values' => null,
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $rs->academic_year_id
                ]);
            }
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }


    public function checkRSDownloadable() {
        $setting = Setting::where('slug', 'result-publishing')->first();
        if ($setting) {
            $rs_downloadable = 0;
            foreach ($setting['parameters'] as $param) {
                if ($param['slug'] == 'guardians-download-report-sheets') {
                    $rs_downloadable = $param['enabled'];
                    break;
                }
            }
            if ($rs_downloadable) {
                return response()->json([
                    'status' => 'success',
                    'enabled' => true
                ]);
            } else {
                return response()->json([
                    'status' => 'success',
                    'enabled' => false
                ], 522);
            }
        } else {
            return response()->json([
                'status' => 'success',
                'enabled' => false
            ], 522);
        }     
    }

    public function getStudentRS($student_id, Request $request)
    {
        $data = ReportSheet::with(['student', 'result'])
                    ->where('academic_year_id', $request->academic_year_id)
                    ->where('term_id', $request->term_id)
                    ->where('student_id', $student_id)
                    ->get();
        $student_rs = [];
        if (count($data) > 0) {
            foreach ($data as $rs) {
                $rs->sheet = $this->_decompressHtmlString($rs->sheet);
                $student_rs[] = $rs;
            }
            if (count($student_rs) > 0) {
                if ($student_rs[0]->result != null) {
                    if ($student_rs[0]->result["published"]) {
                        return response()->json([
                            'status' => 'success',
                            'data' => $student_rs[0]
                        ]);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Result not published'
                        ], 522);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Result not published'
                    ], 522);
                }
            } else {
                return response()->json([
                    'status' => 'error 1',
                    'message' => 'Report sheet not found'
                ], 404);
            }
        } else {
            return response()->json([
                'status' => 'error 2',
                'message' => 'No report sheets'
            ], 404);
        }
    }


    
    public function _compressHtmlString($html_string) {
        try {
            $compressed_html_string = gzcompress($html_string, 9);
            return $compressed_html_string;
        } catch (Exception $e) {
            return false;
        }
    }

    public function _decompressHtmlString($html_string) {
        try {
            $decompressed_html_string = zlib_decode($html_string, 0);
            return $decompressed_html_string;
        } catch (Exception $e) {
            return false;
        }
    }




}
