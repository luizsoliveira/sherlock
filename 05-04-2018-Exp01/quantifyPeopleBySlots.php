<?php

include_once "functions.php";
prepareArrays();

set_error_handler('exceptions_error_handler');

/* Parâmetros Entrada */
$acEntrada = 7;
$acSaida = 7;
$acCancelamento = 4;

/* Fim Parâmetros Entrada */

$filename = "ranking-SLOTS.csv";
$logFilename = "quantify-SLOTS-1M-NEW-E{$acEntrada}-S{$acSaida}-CE{$acCancelamento}.log";

//Limpa arquivo de log
file_put_contents($logFilename, "");

$nodes = array();
$events = array();


$row = 1;
if (($handle = fopen($filename, "r")) !== FALSE) {

    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        $num = count($data);

        $srcMac = $data[0];
        $situation = $data[2];

        //Criando nó na estrutura de controle
        if (!isset($nodes[$srcMac])) $nodes[$srcMac] = [];

        $row++;
        for ($c=3; $c < $num; $c++) {

        	$slot = $c - 2;
        	$qProbes = $data[$c];

        	if ($qProbes > 0) {

        		//Verificando potential-arrive
        		if (!isset($node[$srcMac]['potential-arrive'])) {
        			$node[$srcMac]['potential-arrive'] = $slot;
        			$node[$srcMac]['acumuladorChegada'] = 0;
              $node[$srcMac]['acumuladorCancelamentoChegada'] = 0;
        			info("potential-arrive slot:$slot $srcMac $situation");
        		}

        		//Gerenciando acumulador de entrada para +1
				if (!isset($node[$srcMac]['arrive']) && isset($node[$srcMac]['potential-arrive'])) {
        			$node[$srcMac]['acumuladorChegada']++;
        			info("acumulador+1 slot:$slot $srcMac $situation");

              // Zerando o acumulador que detecta ausência de probes em slots consecutivos durante
              // fase de potential arrive para fins de cancelamento de potential arrive
              $node[$srcMac]['acumuladorCancelamentoChegada'] = 0;

        			//Confirmando arrive
        			if ($node[$srcMac]['acumuladorChegada'] == $acEntrada) {
        				$arrive = $node[$srcMac]['potential-arrive'];
        				$node[$srcMac]['arrive'] = $arrive;

        				$events[$arrive][] = [
        					'type' => 'arrive',
        					'src' => $srcMac,
        					'situation' => $situation,
        					'when' => $slot
        				];

        				info("arrive detected:$arrive confirmed:$slot $srcMac $situation");
        			}
        		}

        		//Gerenciando acumulador de saída para ZERAR, quando em processo de potential-departure
        		// Derrubando potential-departure
				if (!isset($node[$srcMac]['departure']) && isset($node[$srcMac]['potential-departure'])) {
					unset($node[$srcMac]['potential-departure']);
        			unset($node[$srcMac]['acumuladorSaida']);
        			info("acumulador-0-s slot:$slot $srcMac $situation");
        		}



        	}

        	if ($qProbes == 0) {

        		//Gerenciando acumulador de entrada para ZERAR, quando em processo de potential-arrive
        		// Derrubando potential-arrive
				if (!isset($node[$srcMac]['arrive']) && isset($node[$srcMac]['potential-arrive'])) {

              // Aumentando o acumulador que detecta ausência de probes em slots consecutivos durante
              // fase de potential arrive para fins de cancelamento de potential arrive
              $node[$srcMac]['acumuladorCancelamentoChegada']++;

              if ($node[$srcMac]['acumuladorCancelamentoChegada'] == $acCancelamento) {
                unset($node[$srcMac]['potential-arrive']);
          			unset($node[$srcMac]['acumuladorChegada']);
                unset($node[$srcMac]['acumuladorCancelamentoChegada']);
          			info("acumulador-0 slot:$slot $srcMac $situation");
              }


        		}

        		//Detectando potential-departure
        		if (isset($node[$srcMac]['arrive']) && !isset($node[$srcMac]['potential-departure'])) {
        			$node[$srcMac]['potential-departure'] = $slot;
        			$node[$srcMac]['acumuladorSaida'] = 0;
        			info("potential-departure slot:$slot $srcMac $situation");

        		}

        		//Gerenciando acumulador de saída para +1
				if (isset($node[$srcMac]['arrive']) && isset($node[$srcMac]['potential-departure'])) {
        			$node[$srcMac]['acumuladorSaida']++;
        			info("acumulador-1 slot:$slot $srcMac $situation");

        			//Confirmando departure
        			if ($node[$srcMac]['acumuladorSaida'] == $acSaida) {
        				$departure = $node[$srcMac]['potential-departure'];
        				$node[$srcMac]['departure'] = $departure;

        				$events[$departure][] = [
        					'type' => 'departure',
        					'src' => $srcMac,
        					'situation' => $situation,
        					'when' => $slot
        				];

        				info("departure detected:$departure confirmed:$slot $srcMac $situation");
        				unset($node[$srcMac]);

        			}
        		}

        	}

        }
        //Next participant
        //exit;
    }
    fclose($handle);
}

//Consolidando dados
ksort($events);

//Escrevento arquivo de eventos
$fp = fopen("events-SLOTS-1M-E{$acEntrada}-S{$acSaida}-CE{$acCancelamento}.csv", 'w');
foreach ($events as $slot => $eventos) {
	foreach ($eventos as  $evento) {
		fputcsv($fp, [$slot, $evento['type'], $evento['src'], $evento['situation'], $evento['when']], ";", chr(0));
	}
}


//Escrevendo arquivo de quantify
$q = 0;
$quantidades = [];
foreach ($events as $slot => $eventos) {
	foreach ($eventos as  $evento) {
		if ($evento['type'] == 'arrive') $q++;
		if ($evento['type'] == 'departure') $q--;
	}
    $quantidades[$slot] = $q;
}

$fpq = fopen("quantify-SLOTS-1M-NEW-E{$acEntrada}-S{$acSaida}-CE{$acCancelamento}.csv", 'w');
for ($s=1; $s<=103 ; $s++) {
	if (isset($quantidades[$s])) $q = $quantidades[$s];
	fputcsv($fpq, [$s, $q], ";", chr(0));
}

fputcsv($fpq, [$slot, $q], ";", chr(0));



function trace($type, $msg) {
	global $logFilename;
	$phrase = "{$type}: $msg\n";
	echo $phrase;
	file_put_contents($logFilename, $phrase, FILE_APPEND);

}

function info($msg) {
	trace("INFO", $msg);
}


?>
