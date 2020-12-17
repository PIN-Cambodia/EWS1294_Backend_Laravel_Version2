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

/*Users authentication */
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1')->namespace('Api')->group(function(){
	/*Generate json data from sensor location to geojson */
	Route::get('location.geojson','v1\SensorInfoController@getLocation');
	// Get data from Sensor Datapoint(no use)
	Route::get('sensors/{external_id}/datapoints/{n_record}','v1\SensorInfoController@getSensorDatapoint');
	/*get json api datapoint dispay on sensor graph on mapping*/
	Route::get('sensors/sensor_event','v1\SensorInfoController@sensor_event');

	Route::get('sensors/datapoints','v1\SensorInfoController@getSensorDatapointTest');

});

/*Group data authentication */
Route::group(['namespace'=>'Api','prefix'=>'v1','middleware'=>['auth:api']],function(){
	Route::post('sensors/{external_id}/datapoints','v1\SensorInfoController@createDatapoint');
	Route::post('sensors/{external_id}','v1\SensorInfoController@updateSensor');

});


