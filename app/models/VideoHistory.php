<?php

use Phalcon\Mvc\Model;

class VideoHistory extends Model{
  
  public $time_stored;
  public $student;
  public $vidpercentage; 

  function getSource(){
  	return 'video_history';
  }
}
