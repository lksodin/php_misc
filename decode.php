<?php
$input_dir = "/home/odin/temp/StaticData/";
$ouput_dir = "/home/odin/temp/decoded_data/";

mkdir($ouput_dir, '0777', true);

$ih = dir($input_dir);

while( $list= $ih->read() ){
  if(in_array($list, array('.','..'))){
    continue;
  }

  $ifh = fopen($input_dir.$list, 'r');
  $ofh = fopen($ouput_dir.$list, 'w+');
  fputs($ofh, base64_decode(fgets($ifh)));
  fclose($ifh);
  fclose($ofh);

  echo "decode: ".$list."\n";
}

$ih->close();
