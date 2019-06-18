<?php

include_once "functions.php";

$filename = "app.log";
$inicio = $final = false;

$json = file_get_contents($filename);
$json = json_decode($json, TRUE);

$fp = fopen('app.csv', 'w');


$slots = [];
foreach ($json as $key => $value) {
  $time = $value['timestamp'];
  $timestamp = strtotime($time);
  $quantity = $value['quantity'];

  if (!$inicio) $inicio = $timestamp;
  $slot = intdiv($timestamp - $inicio, 60);
  //Persiste apenas o último report de quantity de cada slot
  $slots[$slot] = $quantity;

  //$formated_time = date('y-m-d H:i:s', strtotime($time));
  $formated_time = date('H:i:s', strtotime($time));
  fputcsv($fp, [$formated_time, $quantity], ";", chr(0));
}


$fps = fopen('app-SLOTS.csv', 'w');

for ($s=0; $s<103 ; $s++) { 
	if (isset($slots[$s])) $q = $slots[$s];
	fputcsv($fps, [$s+1, $q], ";", chr(0));	
}


 ?>
