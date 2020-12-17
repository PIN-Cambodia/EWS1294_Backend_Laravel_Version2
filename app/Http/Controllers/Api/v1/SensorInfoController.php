<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use Validator;
use \DateTime;
use App\Location;
use App\Datapoint;
use App\Sensor;
class SensorInfoController extends Controller {
  public function getLocation(Request $request) {
      if(!$request->query->has('type'))
          $location = Location::with('Sensor')->get();
      else
          $location = Location::with('Sensor')->where('type', $request->query->get('type'))->get();

      $features = [];
      /* Generate json format follow  by http://geojson.org/ */
      foreach($location as $key => $value) {

          /* get translation in khmer for feild name and comment by location*/
          $translatekh = Location::withTranslations(['kh'])->where('id',(int) $value['id'])->get();
          $translatekh = $translatekh->translate('kh', 'fallbackLocale');
          $namekh= $translatekh[0]->name;
          $commenkh=$translatekh[0]->comment;
          /* end translation */

          /*get sensor information in location*/
          $sensor=$value['sensor'];

          /* get the last information of datapoint in the location */
          $datapoint=Datapoint::where('location_id',$value['id'])->orderby('id','desc')->first();
         // formate geojson
         //return $datapoint;
          $status='';
          $createDate=$datapoint!=null ? $datapoint->created_at : date("Y-m-d H:i:s");

/*switch condition change icon sensor color  it work with javascript in page map.js*/
          switch ($value['status']) {
            case 'Operational':
              $datetime1 = new DateTime(date("Y-m-d H:i:s"));
              $datetime2 = new DateTime($createDate);
              //call function GetHourFromDate
              $hours=$this->GetHourFromDate($datetime1,$datetime2);

                if($hours<24 && $datapoint!=null ) {
                      $status='active';

                      if($sensor->type == 'River') {
                          if($datapoint->water_height>=$value['watch_level'] && $datapoint->water_height<$value['warning_level'])
                               $status = 'watch';
                          else if($datapoint->water_height>=$value['warning_level'] && $datapoint->water_height<$value['severe_level'])
                               $status = 'warning';
                          else if($datapoint->water_height>=$value['severe_level'])
                              $status ='severe_warning';
                      } elseif($sensor->type == 'Ground water') {
                          if($datapoint->water_height <= $value['severe_level'])
                              $status = 'severe_warning';
                          else if($datapoint->water_height <= $value['warning_level'])
                              $status = 'warning';
                          else if($datapoint->water_height <= $value['watch_level'])
                              $status = 'watch';
                      }
                  }
                else
                    $status = 'inactive';
              break;
            case 'planed':
               $status = $value['status'];
              break;
            default:
               $status = 'inactive';
              break;
          }
/*end switch*/



/*json format mapping geojson*/
          $features[] = array(
              'type' => 'Feature',
              'geometry' => array('type' => 'Point', 'coordinates' =>array((double) $value['longitude'],(double)$value['latitude']) ),
              'properties' => array(
                                    'id'=> $value['id'],
                                    'name' => $value['name'],
                                    'namekh'=>$namekh,
                                    'external_id'=>$sensor['external_id'],
                                    'water_height'=>($datapoint!=null ? $datapoint->water_height : NULL),
                                    'status1'=>$value['status'],
                                    'status'=>$status,
                                    'type'=>$value['Type'],
                                    'comment'=>$value['comment'],
                                     'commentkh'=>$commenkh,
                                    'trigger_levels'=>array('severe_warning'=>$value['severe_level'],'warning'=>$value['warning_level'],'watch_level'=>$value['watch_level']),
                                     )
                              );
      }
      /*close foreach location*/

       $new_data = array(
        'type' => 'FeatureCollection',
          'features' => $features,
      );

      // $final_data = json_encode($new_data, JSON_PRETTY_PRINT);
      //use JSON_UNESCAPED_UNICODE when json has khmer values
      $final_data = json_encode($new_data, JSON_UNESCAPED_UNICODE);
        $headers = array (
            'Content-Type' => 'application/json; charset=UTF-8',
            'charset' => 'utf-8'
        );

        return response()->make($final_data , 200, $headers);
    }


 /* Get information of Sensor Datapoint */
 /*Grap desplay sensor api*/
 public function sensor_event(Request $request){
    /*this if use prevent when users access to sensor_event api without sensor id*/
    if(!$request->query->has('external_id') && !$request->query->has('location')){
        return "Please provide either sensor or location id";
    }
    /*end if*/

    $start = strtotime('-6 hours');
    $end = time();
    if($request->query->has('start') && strtotime($request->query->get('start')))
        $start = strtotime($request->query->get('start'));

    if($request->query->has('end') && strtotime($request->query->get('end')))
        $end = strtotime($request->query->get('end'));

    $start = date('Y-m-d H:i:s', $start);
    $end = date('Y-m-d H:i:s', $end);

    if($request->query->has('external_id')) {
        $sensor = Sensor::with('Location')->where('external_id', $request->query->get('external_id'))->first();
        $data = Datapoint::where('sensor_id', $sensor->id)->whereBetween('created_at', [$start, $end])->get();
    } else {
        $data = Datapoint::where('location_id', $request->query->get('location'))->whereBetween('created_at', [$start, $end])->get();
    }

    $records = [];

    /*foreach data only datatime with water height only for json format display graph sensors on map*/
    foreach($data as $key => $value) {
        $time = $value['created_at'];
        //add 7 hours for Cambodia format timezone
        $time = $time->modify("+7 hours");
        $time = $time->format('Y-m-d\TH:i:s');
        $records[] = array('value' => array($time, $value['water_height']));
    }

    return response()->json($records);
}
/*end graph sensor api on map*/


// /////This function getsensor datapoint for desplay on mapping/////////////////////
  /*This is old format json api but we don't use it this website*/
  public function getSensorDatapoint(Request $request){

         $fromdate=date($request->fromdate);
         $todate=date($request->todate);
         $sensor=Sensor::with('Location')->where('external_id',$request->external_id)->first();
          //return response()->json($sensor);
         $data=Datapoint::where('sensor_id', $sensor->id);
          //  validation date format
         if($this->validateDate($fromdate) && $this->validateDate($todate))
               $data=$data->whereBetween('created_at',[$fromdate,$todate]);
               $data=$data->orderby('created_at','desc')->take($request->n_record);
               $data=$data->get();

                foreach($data as $key => $value) {

                                      $record[]=array(
                                                      'type'=>'record',
                                                      'id'=>$value['id'],
                                                      'timestamp'=>$value['created_at'],
                                                      'height'=>'302',
                                                      'voltage'=>'5000'

                                                      );
                }

      $features_datapoint= array(
                            'type'=>'from_my_database',
                            'sensorID'=>$sensor->external_id,
                            'sensorName'=>$sensor->location[0]->name,
                            'warningThreshold'=>'330',
                            'alertThreshold'=>'350',
                            'record'=>$record,
                                    );

       // return response()->json($data);
     $result = json_encode($features_datapoint, JSON_UNESCAPED_UNICODE);
     return  $result;

          //$data=Datapoint::find(1)->Sensor()->get();
         //$data=Datapoint::with('Sensor')->get();
         // $data=Datapoint::with('Sensor')->get();
         /* no get detail Sensor */
         //$data=Sensor::where('external_id',$request->external_id)->firstOrFail()->Datapoint()->get();
  }

    function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }



    /* Create datapoint from users authorization*/
  public function createDatapoint(Request $request)
  {
    //check authorization users on create Datapoint
    // add_datapoints is field name in permissions table
     if(!Gate::allows('add_datapoints')){
         return response()->json(['status'=>false,'message'=>'You have no permission']);
     }
     //end

    $sensor=Sensor::with('Location')->where('external_id',$request->external_id)->first();

    $arrdata =
              [
               'sensor_id'=>$sensor->id,
               'location_id'=>$sensor->location[0]->id,
               'data'=>$request->data,
               ];

    if($request->has('water_height')) { // Generation 2 and 3 Tepmachcha
        $arrdata['water_height'] = $request->water_height;
    } elseif ($request->has('Water_level')) { // Generation 1 AAC Ground water sensor
        $arrdata['water_height'] = $request->Water_level;
    } else { // Generation 1 Tepmachcha
        $arrdata['water_height'] = $sensor->location[0]->sensor_height - $request->distance_report;
    }


       $validator = Validator::make($request->all(), [
            /*'sensor_id' => 'required',
            'location_id' => 'required', */

              ]);


        if ($validator->fails()) {
            return response()->json(['status'=>false,'message'=>$validator->messages()]);
        }

        else{
                $datapoint = Datapoint::create($arrdata);

                // For river sensors, initiate automatic alert to phone numbers if the prewarning trigger has been reached 3 times in a row
                if($sensor->type == 'River' && $sensor->location[0]->status == 'Operational' && $sensor->location[0]->pre_warning_level > 0) {
                    $datapoints = Datapoint::where('location_id', $sensor->location[0]->id)->orderby('id','desc')->take(3)->get();
                    $sendAlert = false;
                    foreach($datapoints as $datapoint) {
                        if($datapoint->water_height >= $sensor->location[0]->pre_warning_level) {
                            $sendAlert = true;
                        }
                        // If just one of the points is below the level, don't send warning
                        else {
                            $sendAlert = false;
                            break;
                        }
                    }
                    // If we should send an alert, and it's been more than 24 hours since the last alert, send one now.
                    if($sendAlert && $sensor->location[0]->pre_warning_last_issued < time()-60*60*24) {
                        $sensor->location[0]->update(['pre_warning_last_issued' => time()]);
                        file_get_contents('http://ews1294.info/wp-admin/admin-ajax.php?action=sensor_alert&phonenumbers=' . urlencode($sensor->location[0]->pre_warning_phone_numbers));
                    }
                }

                return response()->json(['status'=>true,'message'=>"Record created successfully",'data'=>$arrdata]);
        }

  }


  /**
   * Update sensors from users authorization
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function updateSensor(Request $request, $id)
  {
      /* check authorization users on update sensors
      edit_sensors is field name in permissions table*/
     if(!Gate::allows('edit_sensors')){
         return response()->json(['status'=>false,'message'=>'You have no permission']);
     }
      //end
       $arrdata=
                [
                 'external_id'=>$request->external_id,
                 'type'=>$request->type,
                 'parameters'=>$request->parameters
                 ];

        $validator = Validator::make($request->all(), [
              'external_id' => 'required',
              'type' => 'required'
                ]);

          if ($validator->fails()) {
              return response()->json(['status'=>false,'message'=>$validator->messages()]);
          }

          else
          {
                $sensor=Sensor::find($id);
                $sensor->update($arrdata);
                return response()->json(['status'=>true,'message'=>"Record updated successfully",'data'=>$arrdata]);
          }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /* for display sensor in select box in page location */
    public function getSensor($type,$sensor_id=0){
      //$sensor=\DB::table("sensors")
      $sensor=Sensor::where("type",$type)
      ->whereNotIn('id',function($query){
                            $query->select('sensor_id')->where('sensor_id','!=','NULL')->from('locations');}
                  );
      if($sensor_id!=0)//check for when you click on edit location, the sensor will be exist
          $sensor=$sensor->orWhere('id',$sensor_id);
      $sensor=$sensor->pluck("external_id","id");
      return json_encode($sensor);
    }

/*GetHourFromDate use to compare date hours for motify sensor display with active or inactive by less or more 24 hours*/
  public  function GetHourFromDate($datetime1,$datetime2){

      $datetime1->modify("+7 hours");
      $datetime1->format("Y-m-d H:i");
      $interval = $datetime1->diff($datetime2);
      $hours = $interval->h;
      $hours = $hours + ($interval->days*24);
      return $hours;
    }

/*end GetHourFromDate */
}
