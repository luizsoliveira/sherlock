<?php

include_once "functions.php";
prepareArrays();

set_error_handler('exceptions_error_handler');

$filename = "wlp2s0.log";

$lines = explode("\n", file_get_contents($filename));

if (is_array($lines) && count($lines) > 1) {

} else {
  exit("ERRO: Ocorreu um erro ao ler o arquivo.\n");
}

/* Stats */
$p = 0; //probes
$pWSSID = 0; // probes with SSID
$probesPerSrc = [];
$probesPerSrcPerSlot = [];
$inicio = $final = false;
/* Fim - Stats */


foreach ($lines as $key => $probe) {

  $fields = explode(" ", $probe);

  try {
    $date = $fields[0];
    $time = $fields[1];
    $rssi = $fields[4];
    $channel = $fields[7];
    $dstMac = $fields[10];
    $srcMac = $fields[13];
    $bssidMac = $fields[16];
    $ssid = $fields[19];
  } catch(Exception $e) {
    echo "Erro ao decodificar a linha " . ($key+1) . " [$probe]\n";
    break;
  }


  if (!$inicio) $inicio = strtotime("$date $time");
  $final = strtotime("$date $time");
  $timestamp = strtotime("$date $time");

  $slot = intdiv($timestamp - $inicio, 60);

  $p++;
  if (!empty($ssid)) $pWSSID++;

  if (isset($probesPerSrc[$srcMac])) $probesPerSrc[$srcMac]++;
  else $probesPerSrc[$srcMac] = 1;

  if (isset($probesPerSrcPerSlot[$srcMac][$slot])) $probesPerSrcPerSlot[$srcMac][$slot]++;
  else $probesPerSrcPerSlot[$srcMac][$slot] = 1;

}

$duration = $final - $inicio;
$durationH = $duration / 3600;
$maxSlots = intdiv($final - $inicio, 60);
echo "Experimento realizado em " . secondsToTime($duration) . " ($duration seconds)\n";
echo "Estatísticas:\n";
echo "Total de probes: $p\n";
echo "Probes com menção de SSID: $pWSSID (" . ($pWSSID*100)/$p . "%)\n"; 
echo "Probes sem menção de SSID: " . ($p-$pWSSID) . " (" . (($p-$pWSSID)*100)/$p . "%)\n"; 

arsort($probesPerSrc);

$fp = fopen('ranking-SINGLE.csv', 'w');
$fpSLOTS = fopen('ranking-SINGLE-SLOTS.csv', 'w');

foreach ($probesPerSrc as $src => $quantity) {

  if (is_array(isLicensedMAC($src))) $situation = "LICENSED";
  else $situation = "UNLICENSED";

  fputcsv($fp, [$src, $quantity, $situation], ";", chr(0));

  $fields = [$src, $quantity, $situation];

  //Preenchendo slots na planilha
  for($i=0;$i<=$maxSlots;$i++) {
    if (isset($probesPerSrcPerSlot[$src][$i])) $fields[] = $probesPerSrcPerSlot[$src][$i];
    else $fields[] = 0;
  }

  fputcsv($fpSLOTS, $fields, ";", chr(0));
}


?>
