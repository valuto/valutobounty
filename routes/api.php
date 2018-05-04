<?php

use Illuminate\Http\Request;

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

/**
 * Version 1 routes.
 */
Route::group([
    'prefix' => 'v1',
    'namespace' => 'API\v1'
], function () {

    Route::post('new', 'UserController@register');

    //Route::get('verify/{confirmationCode}/{userId}', 'UserController@verify')->name('verify');

});