<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Route;




//
$router->get('/', function () use ($router) {
    return response()->json([
        'status' => 200,
        'data' => [
            'App Name' => env('APP_NAME'),
            'App Version' => env('APP_VERSION'),
            'Company Name' => env('APP_COMPANY'),
            'Owner' => env('APP_OWNER'),
            '*' => '*****',
            'Client Name' => env('CLIENT_NAME'),
            'Client Number' => env('CLIENT_NUMBER')
        ]
    ]);
});



// *** API ***
$router->group(['prefix' => 'api'], function () use ($router) {

    //
    $router->get('/', function () use ($router) {
        return 'Cilantro client: ' . env('CLIENT_NAME');
    });
    
    $router->post('ping', function () use ($router) {
        return response()->json(['status' => 200, 'data' => 'pong!']);
    });


    // config routes
    Route::group(['prefix' => 'config', 'middleware' => 'secret_key'], function () { // 'middleware' => ['secret_key', 'auth:api']
        Route::get('/get', 'ConfigController@getConfig');
        Route::post('/setup', 'ConfigController@setConfig');
    });

    // tricorder routes
    Route::group(['prefix' => 'tricorder', 'middleware' => 'secret_key'], function () {
        Route::get('/verify', 'TricorderController@verify_tricorder');
        Route::post('/setup', 'TricorderController@setup_tricorder');
    });

    // license routes
    Route::group(['prefix' => 'license', 'middleware' => 'secret_key'], function () {
        Route::get('/setup', 'LicenseController@setup_license');
        Route::get('/verify', 'LicenseController@verify_license');
    });

    // school routes
    Route::group(['prefix' => 'school', 'middleware' => 'secret_key'], function () {
        Route::post('/setup', 'SchoolController@setup_school');
        Route::get('/verify', 'SchoolController@verify_school');
        Route::get('/get-version', 'SchoolController@getVersion');
        Route::get('/get-api', 'SchoolController@getServerAPI');
        Route::get('/get-settings', 'SchoolController@getSettings');
        Route::get('/get-academic-years', 'SchoolController@getAcademicYears');
        Route::get('/get-currency-details', 'SchoolController@getCurrencyDetails');
        Route::get('/get-printer-details', 'SchoolController@getPrinterDetails');
        Route::get('/get-mobile-app-download-links', 'SchoolController@getMobileAppDownloadLinks');
    });

    // academic year routes
    Route::group(['prefix' => 'academic-years', 'middleware' => 'secret_key'], function () {
        Route::group(['prefix' => 'list'], function () {
            Route::post('/change-temp_active-status', 'AcademicYearController@changeTempActiveStatusList');
            Route::post('/set-general-conf-status', 'AcademicYearController@setGeneralConfStatusList');
        });
        Route::get('/', 'AcademicYearController@index');
        Route::post('set-general-conf-status/{id}', 'AcademicYearController@setGeneralConfStatus');
        Route::post('change-temp_active-status/{id}', 'AcademicYearController@changeTempActiveStatus');
    });

    // term routes
    Route::group(['prefix' => 'terms', 'middleware' => 'secret_key'], function () {
        Route::group(['prefix' => 'list'], function () {
            Route::post('/change-active-status', 'TermController@changeActiveStatusList');
        });
        Route::get('/', 'TermController@index');
        Route::post('change-active-status/{id}', 'TermController@changeActiveStatus');
    });

    // auth routes
    $router->group(['prefix' => 'auth', 'middleware' => 'secret_key'], function () {
        Route::post('login', 'AuthController@login');
        Route::post('me', 'AuthController@me');
        Route::post('logout', 'AuthController@logout');
        Route::post('password/confirm', 'AuthController@verifyPassword');
        Route::group(['middleware' => 'auth:api'], function () {
            Route::post('logout', 'AuthController@logout');
            Route::post('refresh', 'AuthController@refresh');
            Route::post('me', 'AuthController@me');
            Route::post('password', 'AuthController@changePassword');
            Route::post('language', 'AuthController@changeLanguage');
        });
    });

    // statistics route
    $router->group(['prefix' => 'statistics', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'StatisticController@getStats');
    });

    // codes routes
    $router->group(['prefix' => 'codes', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'teachers'], function () use ($router) {
            $router->get('/', 'TeacherCodeController@index');
            $router->get('/{id:[0-9]+}', 'TeacherCodeController@show');
            $router->get('validate/{id}', 'TeacherCodeController@validateTeacher');
        });
        $router->group(['prefix' => 'guardians'], function () use ($router) {
            $router->get('/', 'GuardianCodeController@index');
            $router->get('/{id:[0-9]+}', 'GuardianCodeController@show');
            $router->get('validate/{id}', 'GuardianCodeController@validateGuardian');
        });
        $router->group(['prefix' => 'students'], function () use ($router) {
            $router->get('/', 'StudentCodeController@index');
            $router->get('/{id:[0-9]+}', 'StudentCodeController@show');
        });

    });

    // teachers routes
    $router->group(['prefix' => 'teachers', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'list'], function () use ($router) {
            $router->post('/update', 'TeacherController@updateList');
        });
        $router->group(['prefix' => 'mobile'], function () use ($router) {
            $router->post('login', 'TeacherController@mobileLogin');
            $router->get('messages/{code}', 'TeacherController@getMessages');
            $router->get('subjects-mod/{id}', 'TeacherController@getSubjectsWithPeriods');
            $router->get('teacher-subjects/current/{id}', 'TeacherController@getCurrentSubjects');
            $router->post('subject-submission-marks/{id}', 'TeacherController@getSubjectSubmissionMarks');
        });
        $router->group(['prefix' => 'subjects'], function () use ($router) {
            $router->group(['prefix' => 'list'], function () use ($router) {
                $router->post('/create', 'TeacherSubjectController@storeList');
            });
            $router->get('/', 'TeacherSubjectController@index');
            $router->get('/current', 'TeacherSubjectController@getCurrentAYTeacherSubjects');
            $router->get('academic-year/{ay_id}', 'TeacherSubjectController@getTeacherSubjects');
            $router->get('/teacher/{id}', 'TeacherSubjectController@getSingleTeacherSubjects');
            $router->post('create', 'TeacherSubjectController@store');
            $router->post('update/{id}', 'TeacherSubjectController@update');
            $router->delete('delete/{id}', 'TeacherSubjectController@destroy');
        });
        $router->get('/', 'TeacherController@index');
        $router->get('/current/{ay_id}', 'TeacherController@getTeachers');
        $router->get('/{id}', 'TeacherController@show');
        $router->post('create', 'TeacherController@store');
        $router->post('update/{id}', 'TeacherController@update');
        $router->delete('delete/{id}', 'TeacherController@destroy');
        $router->post('change-status/{id}', 'TeacherController@changeStatus');
        $router->post('submissions/{id}', 'TeacherController@getSubmissions');
        $router->post('search', 'TeacherController@search');
    });

    // guardians routes
    $router->group(['prefix' => 'guardians', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'list'], function () use ($router) {
            $router->post('/update', 'GuardianController@updateList');
        });
        $router->group(['prefix' => 'mobile'], function () use ($router) {
            $router->post('login', 'GuardianController@mobileLogin');
            $router->post('children/{id}', 'GuardianController@getChildren');
            $router->get('messages/{code}', 'GuardianController@getMessages');
        });
        $router->get('/', 'GuardianController@index');
        $router->get('/current/{ay_id}', 'GuardianController@getGuardians');
        $router->get('/{id}', 'GuardianController@show');
        $router->post('create', 'GuardianController@store');
        $router->post('update/{id}', 'GuardianController@update');
        $router->delete('delete/{id}', 'GuardianController@destroy');
        $router->post('change-status/{id}', 'GuardianController@changeStatus');
        $router->post('students/{id}', 'GuardianController@getGuardianStudents');
        $router->post('search', 'GuardianController@search');
    });

    // students routes
    $router->group(['prefix' => 'students', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'list'], function () use ($router) {
            $router->post('/create', 'StudentController@storeList');
            $router->post('/is-repeater', 'StudentController@isRepeaterList');
        });
        $router->group(['prefix' => 'discipline-records'], function () use ($router) {
            $router->get('/', 'StudentController@getDisciplineRecords');
            $router->get('academic-year/{ay_id}', 'StudentController@getRecords');
            $router->post('create', 'StudentController@storeDisciplineRecord');
            $router->post('update/{id}', 'StudentController@updateDisciplineRecord');
            $router->delete('delete/{id}', 'StudentController@destroyDisciplineRecord');
        });
        $router->group(['prefix' => 'subjects'], function () use ($router) {
            $router->group(['prefix' => 'list'], function () use ($router) {
                $router->post('/create', 'StudentSubjectController@storeList');
            });
            $router->get('/', 'StudentSubjectController@index');
            $router->get('/current', 'StudentSubjectController@getCurrentAYStudentSubjects');
            $router->get('academic-year/{ay_id}', 'StudentSubjectController@getStudentSubjects');
            $router->get('/student/{id}', 'StudentSubjectController@getSingleStudentSubjects');
            $router->post('create', 'StudentSubjectController@store');
            $router->post('update/{id}', 'StudentSubjectController@update');
            $router->delete('delete/{id}', 'StudentSubjectController@destroy');
        });
        $router->group(['prefix' => 'marks'], function () use ($router) {
            $router->group(['prefix' => 'list'], function () use ($router) {
                $router->post('/create', 'StudentMarkController@storeList');
                $router->post('/update', 'StudentMarkController@updateList');
            });
            $router->get('/', 'StudentMarkController@index');
            $router->get('academic-year/{ay_id}', 'StudentMarkController@getMarks');
            $router->get('/student/{id}', 'StudentMarkController@getSingleStudentMarks');
            $router->post('create', 'StudentMarkController@store');
            $router->post('update/{id}', 'StudentMarkController@update');
            $router->delete('delete/{id}', 'StudentMarkController@destroy');
            $router->post('student-marks/{student_id}', 'StudentMarkController@getStudentMarks');
        });
        //
        $router->get('/', 'StudentController@index');
        $router->get('/current', 'StudentController@getCurrentAYStudents');
        $router->get('academic-year/{ay_id}', 'StudentController@getStudents');
        $router->get('/{id}', 'StudentController@show');
        $router->post('create', 'StudentController@store');
        $router->post('update/{id}', 'StudentController@update');
        $router->delete('delete/{id}', 'StudentController@destroy');
        $router->post('change-status/{id}', 'StudentController@changeStatus');
        $router->post('change-fees-status/{id}', 'StudentController@changeFeesStatus');
        $router->get('is-repeater/{id}', 'StudentController@isRepeater');
        $router->get('student-subjects/{id}', 'StudentController@getSubjects');
    });

    // classes routes
    $router->group(['prefix' => 'classes', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'sub-classes'], function () use ($router) {
            $router->get('/', 'SubCategoryController@index');
            $router->post('/create', 'SubCategoryController@store');
            $router->post('/update/{id}', 'SubCategoryController@update');
            $router->delete('/delete/{id}', 'SubCategoryController@destroy');
        });
        $router->get('/', 'CategoryController@index');
        $router->get('/{id}', 'CategoryController@show');
        $router->post('create', 'CategoryController@store');
        $router->post('update/{id}', 'CategoryController@update');
        $router->delete('delete/{id}', 'CategoryController@destroy');
        $router->get('students/current/{id}', 'CategoryController@getStudents');
        $router->get('student-subjects/current/{id}', 'CategoryController@getStudentSubjects');
        $router->get('submission-types/{id}', 'CategoryController@getSubmissionTypes');
    });

    // subjects routes
    $router->group(['prefix' => 'subjects', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'periods'], function () use ($router) {
            $router->group(['prefix' => 'list'], function () use ($router) {
                $router->post('/create', 'SubjectPeriodController@storeList');
                $router->post('/delete', 'SubjectPeriodController@destroyList');
            });
            $router->get('/', 'SubjectPeriodController@index');
            $router->post('create', 'SubjectPeriodController@store');
            $router->post('update/{id}', 'SubjectPeriodController@update');
            $router->delete('delete/{id}', 'SubjectPeriodController@destroy');
        });
        $router->get('/', 'SubjectController@index');
        $router->post('create', 'SubjectController@store');
        $router->post('update/{id}', 'SubjectController@update');
        $router->delete('delete/{id}', 'SubjectController@destroy');
    });

    // activities routes
    $router->group(['prefix' => 'activities', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'ActivityController@index');
        $router->post('create', 'ActivityController@store');
        $router->post('update/{id}', 'ActivityController@update');
        $router->delete('delete/{id}', 'ActivityController@destroy');
    });

    // income routes
    $router->group(['prefix' => 'income-records', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'IncomeRecordController@index');
        $router->get('academic-year/{ay_id}', 'IncomeRecordController@getRecords');
        $router->post('create', 'IncomeRecordController@store');
        $router->post('update/{id}', 'IncomeRecordController@update');
        $router->delete('delete/{id}', 'IncomeRecordController@destroy');
    });

    // expenses routes
    $router->group(['prefix' => 'expenses', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'ExpenseController@index');
        $router->get('academic-year/{ay_id}', 'ExpenseController@getExpenses');
        $router->post('create', 'ExpenseController@store');
        $router->post('update/{id}', 'ExpenseController@update');
        $router->delete('delete/{id}', 'ExpenseController@destroy');
    });

    // attendance routes
    $router->group(['prefix' => 'attendance', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'students'], function () use ($router) {
            $router->get('/', 'StudentAttendanceController@index');
            $router->get('academic-year/{ay_id}', 'StudentAttendanceController@getAttendances');
            $router->post('create', 'StudentAttendanceController@store');
        });
        $router->group(['prefix' => 'teachers'], function () use ($router) {
            $router->get('/', 'TeacherAttendanceController@index');
            $router->get('academic-year/{ay_id}', 'TeacherAttendanceController@getAttendances');
            $router->post('create', 'TeacherAttendanceController@store');
        });
        $router->get('/', 'AttendanceController@index');
        $router->get('academic-year/{ay_id}', 'AttendanceController@getAttendances');
        $router->get('/{id}', 'AttendanceController@show');
        $router->delete('delete/{id}', 'AttendanceController@destroy');
        $router->get('teacher-attendances/academic-year/{ay_id}', 'AttendanceController@getTeacherAttendances');
        $router->get('student-attendances/academic-year/{ay_id}', 'AttendanceController@getStudentAttendances');
    });

    // timetables routes
    $router->group(['prefix' => 'timetables', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'TimetableController@index');
        $router->get('/{id}', 'TimetableController@show');
        $router->post('create', 'TimetableController@store');
        $router->post('update/{id}', 'TimetableController@update');
        $router->delete('delete/{id}', 'TimetableController@destroy');
    });

    // submissions routes
    $router->group(['prefix' => 'submissions', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'list'], function () use ($router) {
            $router->post('/change-status', 'SubmissionController@changeStatusList');
        });
        $router->group(['prefix' => 'types'], function () use ($router) {
            $router->get('/', 'SubmissionTypeController@index');
            $router->post('create', 'SubmissionTypeController@store');
            $router->post('update/{id}', 'SubmissionTypeController@update');
            $router->delete('delete/{id}', 'SubmissionTypeController@destroy');
            $router->post('change-status/{id}', 'SubmissionTypeController@changeStatus');
        });
        $router->get('/', 'SubmissionController@index');
        $router->get('academic-year/{ay_id}', 'SubmissionController@getSubmissions');
        $router->post('create', 'SubmissionController@store');
        $router->post('update/{id}', 'SubmissionController@update');
        $router->delete('delete/{id}', 'SubmissionController@destroy');
        $router->post('change-status/{id}', 'SubmissionController@changeStatus');
    });

    // templates routes
    $router->group(['prefix' => 'templates', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'TemplateController@index');
        $router->post('create', 'TemplateController@store');
        $router->post('update/{id}', 'TemplateController@update');
        $router->delete('delete/{id}', 'TemplateController@destroy');
        $router->get('types/', 'TemplateTypeController@index');
        $router->get('placeholders/', 'TemplatePlaceholderController@index');
        $router->post('placeholders/create', 'TemplatePlaceholderController@store');
        $router->get('placeholder/non-system-plcs/{template}', 'TemplatePlaceholderController@getNonSystemPlaceholders');
        $router->delete('placeholders/delete/{id}', 'TemplatePlaceholderController@destroy');
    });

    // results routes
    $router->group(['prefix' => 'results', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'list'], function () use ($router) {
            $router->post('/create', 'ResultController@storeList');
            $router->post('/update', 'ResultController@updateList');
            $router->post('/delete', 'ResultController@destroyList');
        });
        $router->get('/', 'ResultController@index');
        $router->get('academic-year/{ay_id}', 'ResultController@getResults');
        $router->post('create', 'ResultController@store');
        $router->post('update/{id}', 'ResultController@update');
        $router->delete('delete/{id}', 'ResultController@destroy');
    });

    // report sheets routes
    $router->group(['prefix' => 'report-sheets', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'list'], function () use ($router) {
            $router->post('/create', 'ReportSheetController@storeList');
            $router->post('/delete', 'ReportSheetController@destroyList');
        });
        $router->group(['prefix' => 'check'], function () use ($router) {
            $router->get('rs-downloadable/', 'ReportSheetController@checkRSDownloadable');
        });
        $router->get('/', 'ReportSheetController@index');
        $router->get('academic-year/{ay_id}', 'ReportSheetController@getReportSheets');
        $router->post('create', 'ReportSheetController@store');
        $router->post('update/{id}', 'ReportSheetController@update');
        $router->delete('delete/{id}', 'ReportSheetController@destroy');
        $router->post('student-rs/{student_id}', 'ReportSheetController@getStudentRS');
    });

    // inboxes routes
    $router->group(['prefix' => 'inboxes', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'InboxController@index');
        $router->get('academic-year/{ay_id}', 'InboxController@getInboxes');
	    $router->post('/create', 'InboxController@store');
        $router->delete('delete/{id}', 'InboxController@destroy');
        $router->post('set-seen/{id}', 'InboxController@setSeen');
    });

    // messages routes
    $router->group(['prefix' => 'messages', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'MessageController@index');
        $router->delete('delete/{id}', 'MessageController@destroy');
        $router->post('set-seen/{id}', 'MessageController@setSeen');
    });

    // communication routes
    $router->group(['prefix' => 'communication', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'mail'], function () use ($router) {
            $router->group(['prefix' => 'list'], function () use ($router) {
                $router->post('/send-email' ,'MailController@sendEmailList');
            });
            $router->post('send-email/' ,'MailController@sendEmail');
        });
        $router->group(['prefix' => 'message'], function () use ($router) {
            $router->group(['prefix' => 'list'], function () use ($router) {
                $router->post('/send-message' ,'MessageController@sendMessageList');
            });
            $router->get('listen/' ,'MessageController@listenTC');
            $router->post('send-message/' ,'MessageController@sendMessage');
            $router->post('send-mqtt-message/' ,'MessageController@sendMQTTMessage');
        });
        $router->group(['prefix' => 'history'], function () use ($router) {
            $router->get('/', 'CommunicationHistoryController@index');
            $router->get('academic-year/{ay_id}', 'CommunicationHistoryController@getHistory');
            $router->post('create', 'CommunicationHistoryController@store');
            $router->delete('delete/{id}', 'CommunicationHistoryController@destroy');
        });
    });

    // users routes
    $router->group(['prefix' => 'users', 'middleware' => 'secret_key'], function () use ($router) {
        $router->group(['prefix' => 'cde'], function () use ($router) {
            $router->post('login', 'UserController@cdeLogin');
        });
        $router->group(['prefix' => 'c0'], function () use ($router) {
            $router->post('login', 'UserController@c0Login');
        });
        $router->get('/', 'UserController@index');
        $router->get('/{id}', 'UserController@show');
        $router->post('create', 'UserController@store');
        $router->post('update/{id}', 'UserController@update');
        $router->delete('delete/{id}', 'UserController@destroy');
        $router->post('change-status/{id}', 'UserController@changeStatus');
        $router->post('change-password/{id}', 'UserController@changePassword');
        $router->post('reset-password/{id}', 'UserController@resetPassword');
        $router->post('change-language/{id}', 'UserController@changeLanguage');
        $router->get('permissions/{id}', 'RoleController@userRolePermissions');
        $router->get('roles/{id}', 'RoleController@userRoles');
        $router->post('assign/{id}', 'RoleController@assignUserRole');
    });

    // roles routes
    $router->group(['prefix' => 'roles', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'RoleController@index');
        $router->post('create', 'RoleController@store');
        $router->post('update/{id}', 'RoleController@update');
        $router->delete('delete/{id}', 'RoleController@destroy');
        $router->post('status', 'RoleController@changeStatus');
        $router->get('permissions', 'RoleController@rolePermissions');
        $router->post('permissions/create', 'RoleController@storeRolePermission');
        $router->delete('permissions/delete/{id}', 'RoleController@destroyRolePermission');
    });

    // permissions routes
    $router->group(['prefix' => 'permissions', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'RoleController@permissions');
    });

    // audit logs
    $router->group(['prefix' => 'audit-logs', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'AuditLogController@index');
        $router->get('academic-year/{ay_id}', 'AuditLogController@getAuditLogs');
        $router->post('search', 'AuditLogController@search');
    });

    // settings routes
    $router->group(['prefix' => 'settings', 'middleware' => 'secret_key'], function () use ($router) {
        $router->get('/', 'SettingController@index');
        $router->post('create', 'SettingController@store');
        $router->post('update/{id}', 'SettingController@update');
        $router->delete('delete/{id}', 'SettingController@destroy');
    });


});



// *** Master ***
$router->group(['prefix' => '__/rc', 'middleware' => 'secret_key'], function () use ($router) {
    Route::group(['prefix' => '@master'], function () {
        Route::get('/', '_MasterController@info');
        Route::group(['prefix' => '_tricorder'], function () {
            Route::post('/change-status', '_MasterController@change_tricorder_status');
        });
        Route::group(['prefix' => '_school'], function () {
            Route::post('/info', '_MasterController@get_school_info');
        });
        Route::group(['prefix' => '_academic-years'], function () {
            Route::post('/new', '_MasterController@create_new_ay');
        });
        Route::group(['prefix' => '_license'], function () {
            Route::post('/renew', '_MasterController@renew_license');
            Route::post('/update-date/{date}', '_MasterController@update_license');
            Route::post('/block', '_MasterController@block_license');
        });
    });

});




