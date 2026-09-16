<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Inbox;
use App\Models\Message;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use \PhpMqtt\Client\MqttClient;
use \PhpMqtt\Client\ConnectionSettings;
use Salman\Mqtt\MqttClass\Mqtt;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index()
    {
        $messages = Message::all();
        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $this->validate($request, [
                'to' => 'required|array|min:1',
                'to.*' => 'required',
                'message' => 'required'
            ]);
            $msg = new Message();
            $msg->to = $request->input('to');
            $msg->message = $request->input('message');
            $msg->important = $request->input('important');

            $active_ay = AcademicYear::where('active', true)->first();
            $msg->academic_year_id = $active_ay->id;

            if ($msg->save()) {
                $name = $request->input('to')[1];
                AuditLog::create([
                    'user_id' => $request->user()->id ?? 0,
                    'action' => 'SEND_MESSAGE',
                    'auditable_type' => Message::class,
                    'auditable_id' => $msg->id,
                    'description' => "Message sent to {$name}",
                    'old_values' => null,
                    'new_values' => json_encode(['name' => $name, 'message' => $msg->message, 'important' => $msg->important]),
                    'ip_address' => $request->ip(),
                    'academic_year_id' => $msg->academic_year_id
                ]);

                return response()->json([
                    'status' => 'success',
                    'data' => $msg->refresh()
                ]);

            }

        });
        
    }

    public function sendMessageList(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $records = $request->records;
            foreach ($records as $record) {
                $errors = [];
                if (!isset($record['to'])) {
                    $errors[] = 'To field is required';
                } else if (!isset($record['message'])) {
                    $errors[] = 'Message field is required';
                }
                //
                if (count($errors) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errors[count($errors) - 1]
                    ], 522);
                }
                $msg = new Message();
                $msg->to = $record['to'];
                $msg->message = $record['message'];
                $msg->important = $record['important'];
                $active_ay = AcademicYear::where('active', true)->first();
                $msg->academic_year_id = $active_ay->id;
                if ($msg->save()) {
                    $name = $record['to'][1];
                    AuditLog::create([
                        'user_id' => $request->user()->id ?? 0,
                        'action' => 'SEND_MESSAGE_BATCH',
                        'auditable_type' => Message::class,
                        'auditable_id' => $msg->id,
                        'description' => "Message sent to {$name} in batch",
                        'old_values' => null,
                        'new_values' => json_encode(['name' => $name, 'message' => $msg->message, 'important' => $msg->important]),
                        'ip_address' => $request->ip(),
                        'academic_year_id' => $msg->academic_year_id
                    ]);
                }
            }
            return response()->json([
                'status' => 'success'
            ]);
        });
        
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $message = Message::where('id', $id)->first();
            $active_ay = AcademicYear::where('active', true)->first();
            if ($message->important && $message->academic_year_id == $active_ay->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Important messages cannot be deleted.'
                ], 422);
            }
            if ($message->delete()) {
                AuditLog::create([
                    'user_id' => request()->user()->id ?? 0,
                    'action' => 'DELETE_MESSAGE',
                    'auditable_type' => Message::class,
                    'auditable_id' => $id,
                    'description' => 'Message deleted',
                    'old_values' => json_encode(['name' => $message->to[1], 'message' => $message->message, 'important' => $message->important]),
                    'new_values' => null,
                    'ip_address' => request()->ip(),
                    'academic_year_id' => $message->academic_year_id
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
            $message = Message::find($id);
            if ($message) {
                $old_message = $message->replicate();
                $active_ay = AcademicYear::where('active', true)->first();
                if ($message->academic_year_id == $active_ay->id && ($message->to[0] == 'T0X' || $message->to[0] == 'G0X')) {
                    return response()->json([
                        'status' => 'success',
                        'data' => $message
                    ]);
                }
                $message->seen = true;
                if ($message->save()) {
                    // AuditLog::create([
                    //     'user_id' => request()->user()->id ?? 0,
                    //     'action' => 'MARK_MESSAGE_SEEN',
                    //     'auditable_type' => Message::class,
                    //     'auditable_id' => $id,
                    //     'description' => 'Message marked as seen',
                    //     'old_values' => json_encode(['seen' => false]),
                    //     'new_values' => json_encode(['seen' => true]),
                    //     'ip_address' => request()->ip(),
                    //     'academic_year_id' => $message->academic_year_id
                    // ]);

                    return response()->json([
                        'status' => 'success',
                        'data' => $message->refresh()
                    ]);
                    
                }

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message record not found!'
                ], 522);
            }

        });   
    }

    public function listenTC() {
        $clean_session = true;
        $mqtt_version = MqttClient::MQTT_3_1_1;
        $connectionSettings = (new ConnectionSettings)
            ->setUsername(env('MQTT_USERNAME'))
            ->setPassword(env('MQTT_PASSWORD'))
            ->setKeepAliveInterval(1)
            ->setLastWillQualityOfService(1);

        $mqtt = new MqttClient(getenv('MQTT_HOST'), getenv('MQTT_PORT'), rand(5, 1000000), $mqtt_version);
        $mqtt->connect($connectionSettings, $clean_session);

        $school_code = env('MQTT_FROM_CODE');

        $mqtt->subscribe($school_code, function ($code, $payload) use ($mqtt) {
            $mqtt->interrupt();
            // ---
            $decoded_payload = json_decode($payload, $assoc=false);
            $type = $decoded_payload->type;
            // c-message(inbox)
            if ($type == 'c-message') {
                $inbox = new Inbox();
                $inbox->from_ = [$decoded_payload->from];
                $inbox->message = $decoded_payload->data;
                $inbox->save();
            // http-request
            } else if ($type == 'http-request') {
                echo '1';
            } else {
                echo '2';
            }

        }, 0);
        $mqtt->loop(false);

    }

    public function sendMQTTMessage(Request $request)
    {
        $this->validate($request, [
            'to' => 'required|array|min:1',
            'to.*' => 'required',
            'message' => 'required'
        ]);

        $clean_session = true;
        $mqtt_version = MqttClient::MQTT_3_1_1;
        $connectionSettings = (new ConnectionSettings)
            ->setUsername(env('MQTT_USERNAME'))
            ->setPassword(env('MQTT_PASSWORD'))
            ->setKeepAliveInterval(1)
            ->setLastWillQualityOfService(1);
        $mqtt = new MqttClient(env('MQTT_HOST'), env('MQTT_PORT'), rand(5, 1000000), $mqtt_version);
        $mqtt->connect($connectionSettings, $clean_session);

        $receiver_code = $request->input('to')[0];
        $receiver_name = $request->input('to')[1];
        $message = $request->input('message');
        $payload = array(
            'type' => 'msg',
            'message' => $message
        );
        $mqtt->publish($receiver_code, json_encode($payload), 0);
        sleep(1);

        return response()->json([
            'status' => 'success',
            'message' => 'Message sent to ' . $receiver_name . '.'
        ]);

    }



    public function SendMsgViaMqtt($listener_code, $message)
    {
        $mqtt = new Mqtt();
        $output = $mqtt->ConnectAndPublish($listener_code, $message, rand(0, 1000000), true);
        if ($output === true)
        {
            return "published";
        }
        return "Failed";
    }

    public function SubscribetoTopic($sender_code)
    {
        $mqtt = new Mqtt();
        $mqtt->ConnectAndSubscribe($sender_code, function($sender, $msg) {
            echo "Msg Received: \n";
            echo "Topic: {$sender}\n\n";
            echo "\t$msg\n\n";
        }, 'test');

//        $mqtt = new Mqtt();
//        $school_code = env('MQTT_FROM_CODE');
//
//        $mqtt->ConnectAndSubscribe($school_code, function($code, $payload) {
//            $decoded_payload = json_decode($payload, $assoc=false);
//            $type = $decoded_payload->type;
//            // ---
//            if ($type == 'c-message') { // c-message(inbox)
//                $inbox = new Inbox();
//                $inbox->from_ = [$decoded_payload->from];
//                $inbox->message = $decoded_payload->data;
//                $inbox->save();
//
//            } else if ($type == 'http-request') { // http-request
//                echo '1';
//            } else {
//                echo '2';
//            }
//
//        }, rand(5, 999));


    }



}
