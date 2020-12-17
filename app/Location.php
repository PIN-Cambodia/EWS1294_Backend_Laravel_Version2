<?php

namespace App;
use TCG\Voyager\Traits\Translatable;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use Translatable;
    protected $translatable = ['name', 'comment'];
    protected $fillable = ['pre_warning_last_issued'];

    public function Sensor(){
    	return $this->belongsTo('App\Sensor');
    }

      public function Datapoint(){
    	return $this->hasMany('App\Datapoint');
    }

}
