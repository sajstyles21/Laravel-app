<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('documentation', function () {
    return view('vendor.l5-swagger.index');
});

Route::namespace('Api\Candidate')->group(function () {
    Route::middleware(['auth:api'])->group(function () {
        Route::post('logout', 'UserController@logout')->name('logout');
        Route::get('skills_competencies', 'UserController@skills_competencies')->name('candidate.skills_competencies');
        Route::get('search_jobs', 'JobController@searchJobs')->name('search_jobs');
        Route::get('permoffers', 'JobController@permoffers')->name('perm_offers');
        Route::get('getMessages', 'JobController@getMessages')->name('messages');
        Route::post('markRead', 'JobController@markRead')->name('markread');
        Route::get('getprofile', 'UserController@getProfile')->name('get_profile');
        Route::get('getaddress', 'UserController@getaddress')->name('getaddress');
        Route::post('profile_update', 'UserController@profileUpdate')->name('profile_update');
        Route::post('certificate_upload', 'UserController@certificateUpload')->name('certificate_upload');
        Route::post('device_tokens', 'UserController@updatedevicetokens')->name('device_tokens');
        Route::post('offer', 'JobController@offer')->name('offer');
        Route::get('job_details', 'JobController@jobDetails')->name('job_details');
        Route::get('payroll', 'InvoiceController@getPayroll')->name('candidate.payroll');
        Route::get('payroll-details', 'InvoiceController@getPayrollDetails')->name('candidate.payroll_details');
        Route::get('candidate_data', 'UserController@candidateData')->name('candidate_data');
        Route::get('allfilters', 'UserController@allfilters')->name('allfilters');
        Route::get('ratings', 'UserController@ratings')->name('ratings');
        Route::get('timesheets', 'TimesheetController@timesheets')->name('timesheets');
        Route::post('jobs', 'JobController@jobs')->name('jobs');
        Route::post('postratings', 'UserController@postratings')->name('candidate.postratings');
        Route::get('home', 'UserController@home')->name('home');
        Route::post('withdraw_job', 'JobController@withdrawJob')->name('withdraw_job');
        Route::post('save_job', 'JobController@savejob')->name('save_job');
        Route::post('apply_job', 'JobController@applyjob')->name('apply_job');
        Route::get('timesheet_details', 'TimesheetController@timesheetDetail')->name('candidate.timesheetdetail');
        Route::post('send_timesheet', 'TimesheetController@sendTimesheet')->name('candidate.send');
        Route::post('clockjob', 'JobController@clockJob')->name('candidate.clockjob');
        Route::post('job-status', 'JobController@jobStatus')->name('candidate.job-status');
        Route::post('job-saved-status', 'JobController@jobSavedStatus')->name('candidate.saved-job-status');
        Route::get('notifications', 'UserController@notifications')->name('notifications');
    });
    Route::post('login', 'UserController@login')->name('login');
    Route::post('forgot_password', 'UserController@forgot_password')->name('candidate.forgot_password');
    Route::post('change-password', 'UserController@changePassword')->name('candidate.changepassword');
});
