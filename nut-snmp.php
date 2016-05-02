<?php
$BASEOID = ".1.3.6.1.4.1.6375";

// Check SNMP mode
if($argv[1] != '-n' && $argv[1] != '-g' ) {
  exit(1);
}

// Check $BASEOID
if(strpos($argv[2], $BASEOID) === 0) {
	$OID = explode('.', substr($argv[2], strlen($BASEOID) + 1));
}
else {
  exit(2);
}

exec('/bin/upsc -L 2>&1', $upscls);
array_shift($upscls);

if(sizeof($upscls)) {
	$upses = [];
	foreach($upscls as $upscl) {
		$upsclarr = explode(':', $upscl);
		$upses[] = array('name' => $upsclarr[0]);
	}
}

if(sizeof($upses)) {
	foreach($upses as &$ups) {
		exec('/bin/upsc ' . $ups['name'] . ' 2>&1', $raw_stats);
		array_shift($raw_stats);

		if(sizeof($raw_stats)) {
			$ups['stats'] = [];
			foreach($raw_stats as $raw_stat) {
				$stat_arr = explode(':', $raw_stat);
				$stat_arr[1] = trim($stat_arr[1]);

				if($stat_arr[1] == 'yes' || $stat_arr[1] == 'enabled') {
					$stat_arr[1] = 1;
				}
                                if($stat_arr[1] == 'no' || $stat_arr[1] == 'disabled') {
                                        $stat_arr[1] = 0;
                                }

				if(is_numeric($stat_arr[1]) && intval($stat_arr[1])) {
					$stat_arr[1] = intval($stat_arr[1]);
					$stat_type = 'integer';
				}
				else {
					$stat_type = 'string';	
				}

				$ups['stats'][] = array(
					'name' => $stat_arr[0],
					'value' => $stat_arr[1],
					'type' => $stat_type,
				);
			}
		}
	}

	// Return exact OID on GET request
	if($argv[1] == '-g') {
		if(sizeof($OID) == 1 && @$ups = $upses[$OID[0]]) {
			echo $argv[2] . "\n";
			echo 'string' . "\n";
			echo $ups['name'] . "\n";

			exit(0);
		}

		if(sizeof($OID) == 2) {
			$ups = $upses[$OID[0]];
			$stat = $ups['stats'][$OID[1]];

			echo $argv[2] . "\n";
			echo $stat['type'] . "\n";
			echo $stat['value'] . "\n";

			exit(0);
		}
		else {
			exit(3);
		}
	}

	// Determine next valid OID and return on NEXT request
	if($argv[1] == '-n') {
		// BASEOID requested, determine first UPS
		if(sizeof($OID) == 1 && $OID[0] === '') {
                        if($ups = $upses[0]) {
	                        echo $argv[2] . '.0' . "\n";
        	                echo 'string' . "\n";
                	        echo $ups['name'] . "\n";

                        	exit(0);
			}
			else {
				exit(3);
			}
		}

		// UPS requested, determine first stat fot this UPS
		if(sizeof($OID) == 1) {
			$ups = $upses[$OID[0]];
			
			if($stat = $ups['stats'][0]) {
				echo $argv[2] . '.0' . "\n";
				echo $stat['type'] . "\n";
				echo $stat['value'] . "\n";
			}
			else {
				exit(3);
			}
		}

		// Stat requested, determine next stat
		if(sizeof($OID) == 2) {
			$ups = $upses[$OID[0]];
			$stat_last = sizeof($ups['stats']) - 1;

			// If not the last stat for this UPS return next stat
			if($OID[1] < $stat_last) {
				$stat = $ups['stats'][$OID[1] + 1];

				echo $BASEOID  . '.' . $OID[0]  . '.' . ($OID[1] + 1) . "\n";
				echo $stat['type'] . "\n";
				echo $stat['value'] . "\n";

				exit(0);
			}
			// If last stat for this UPS return next UPS  
			else {
				if($ups = @$upses[$OID[0] + 1]) {
	                        	echo $BASEOID . '.' . ($OID[0] + 1) . "\n";
	        	                echo 'string' . "\n";
        	        	        echo $ups['name'] . "\n";
				
                        		exit(0);
				}
			}
		}
	}
	
	exit(3);
}
?>
