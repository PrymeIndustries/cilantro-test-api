<?php


return [

    'host'     => env('MQTT_HOST'),
    'port'     => env('MQTT_PORT'),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'certfile' => env('mqtt_cert_file',''),
    'debug'    => env('mqtt_debug',false), //Optional Parameter to enable debugging set it to True
    'qos'      => env('mqtt_qos', 0), // set quality of service here
    'retain'   => env('mqtt_retain', 0) // it should be 0 or 1 Whether the message should be retained.- Retain Flag


];
