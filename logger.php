<?php
Class Logger{
  private $file;

  public function __construct($file = ""){
    $this->set_log_path($file);
  }

  public function set_log_path($file = ""){
    $default_path = "/home/odin/test/log/".date("Ymd").".log";

    if(!empty($file)){
      $this->file = $file;
    }else{
      $this->file = $default_path;
    }
  }

  public function vardump($data){
    ob_start(array(&$this,'log'));
    var_dump($data);
    ob_get_clean();
  }

  public function log($msg){
    error_log("[".date("Y-m-d H:i:s")."][".$_SERVER["REMOTE_ADDR"]."] ".$msg."\n", 3, $this->get_log_path());
  }

  private function get_log_path(){
    return $this->file;
  }

}
?>
