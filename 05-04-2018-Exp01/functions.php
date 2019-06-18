<?php

//Prepare Arrays

$mal = $mam = $mas = [];

function prepareArrays() {
  global $mal, $mam, $mas;

  $filename = "ieee8802/oui.csv";
  if (($handle = fopen($filename, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        @$mal[$data[1]] = ['type' => $data[0], 'org' => $data[2]];
      }
  }

  //var_dump($mal);

  $filename = "ieee8802/mam.csv";
  if (($handle = fopen($filename, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $mam[$data[1]] = ['type' => $data[0], 'org' => $data[2]];
      }
  }

  $filename = "ieee8802/oui36.csv";
  if (($handle = fopen($filename, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $mas[$data[1]] = ['type' => $data[0], 'org' => $data[2]];
      }
  }
}


function isLicensedMAC($mac) {
  global $mal, $mam, $mas;

  //Clean the MAC Address
  $mac = strtoupper(str_replace(":", "", $mac));

  if (isset($mas[substr($mac, 0,9)])) return $mas[substr($mac, 0,9)];
  else if (isset($mam[substr($mac, 0,7)])) return $mam[substr($mac, 0,7)];
       else if (isset($mal[substr($mac, 0,6)])) return $mal[substr($mac, 0,6)];
            else return false;

}


function exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}

function secondsToTime($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    //return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
    return $dtF->diff($dtT)->format('%h hours, %i minutes and %s seconds');
}

/*function intdiv($n, $m) {
  return (int)floor($n/$m);
}*/

?>
