<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\DefaultMessage;
use App\Models\AuditLog;
use App\Models\AcademicYear;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigController extends Controller
{
    public function __construct()
    {
    }

    public function getDefaultMessages()
    {
        $messages = DefaultMessage::all();
        return response()->json([
            'status' => 'success',
            'data' => $messages
        ]);
    }

    public function getConfig()
    {
        $config = Config::all();
        return response()->json([
            'status' => 'success',
            'data' => $config
        ]);
    }

    public function setDefaultMessage($id, Request $request)
    {
        return DB::transaction(function () use ($id, $request) {
            $message = DefaultMessage::find($id);
            if ($message) {
                $old_message = $message->replicate();
                $message->message = $request->input('use_default') ? $message->default : $request->input('message');
                if ($message->update()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'UPDATE_DEFAULT_MESSAGE',
                        'auditable_type' => DefaultMessage::class,
                        'auditable_id' => $message->id,
                        'description' => 'Default message ' . $message->slug . ' updated',
                        'old_values' => json_encode($old_message),
                        'new_values' => json_encode($message),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $message
                    ]);
                }
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Message not found'
            ], 404);
        });
        
    }

    public function setConfig(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'server_url' => 'required|string'
            ]);
            $configs = Config::all();
            if (count($configs) > 0) {
                $config = $configs[0];
                $old_config = $config->replicate();
                if ($config->update($request->all())) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'UPDATE_SYSTEM_CONFIG',
                        'auditable_type' => Config::class,
                        'auditable_id' => $config->id,
                        'description' => 'System config updated',
                        'old_values' => json_encode($old_config),
                        'new_values' => json_encode($config),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);
                }
            } else {
                $config = new Config();
                $config->server_url = $request->input('server_url');
                if ($config->save()) {
                    $active_ay = AcademicYear::where('active', true)->first();
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'CREATE_SYSTEM_CONFIG',
                        'auditable_type' => Config::class,
                        'auditable_id' => $config->id,
                        'description' => 'System config created',
                        'old_values' => null,
                        'new_values' => json_encode($config),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $active_ay ? $active_ay->id : 0
                    ]);
                }
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $config->refresh()
            ]);
        });
        

    }



}
