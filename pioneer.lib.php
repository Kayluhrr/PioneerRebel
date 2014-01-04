<?php
	/* WARNING WARNING WARNING
	 * This is *NOT* a Microsoft (or similar) C/C++ (or other) linker library!
	 * It is, in fact, a PHP file require()'d by Quinn Ebert's "Pioneer Rebel"
	 * open source software project.
	 * WARNING WARNING WARNING
	 * 
	 * pioneer.lib.php :: Pioneer VSX-822-K Telnet Functions
	 * Part of Quinn Ebert's "Pioneer Rebel" Software Project
	 * 
	 * DISCLAIMER:
	 * "Pioneer Rebel" is a software project wholly unaffiliated with Pioneer
	 * Electronics.  In no way is "Pioneer Rebel" authorized, supported,
	 * acknowledged, or endorsed by Pioneer Electronics.  FURTHERMORE, you use
	 * this software project AT YOUR OWN RISK.  The possibility indeed exists
	 * that bugs exist in this software which could lead to catastrophic
	 * failure of your Pioneer Electronics equipment.  In no event shall Quinn
	 * Ebert or Pioneer Electronics be held in any way liable for malfunction,
	 * damage, or destruction to your personal property (including but not
	 * limited to personal property created and/or manufactured by Pioneer
	 * Electronics) arising from your use of (or failure to use) this project.
	 */
	
	// Send a command, with optional prefixed or suffixed parameter, to the 822-K
	// Returns corresponding controller response on OK or false (boolean) on error
	function PioneerCtrl_SEND_CMD($address,$command='PO',$parameter=false,$param_first=true) {
		$fp = fsockopen($address, 8102, $errno, $errstr, 30);
		if (!$fp) {
			echo __FUNCTION__."() ERROR: $errstr ($errno), planned command was \"$command\"!\n";
			return false;
		} else {
			$cmd = '';
			if (! $parameter) {
				$cmd = $command;
			} else {
				if ($param_first) {
					$cmd = $parameter.$command;
				} else {
					$cmd = $command.$parameter;
				}
			}
			$cmd .= "\r\n";
			fwrite($fp, $cmd);
			$out = fgets($fp);
			fclose($fp);
			// Cool-down time (my VSX preferred 100ms between reconnects...)
			usleep(100000);
			return $out;
		}
		return false;
	}
	// Send a command to the 822-K amp unit requesting the volume level decrement
	function PioneerCtrl_setVolDec($address) {
		PioneerCtrl_SEND_CMD($address,'VD');
	}
	// Send a command to the 822-K amp unit requesting the volume level increment
	function PioneerCtrl_setVolInc($address) {
		PioneerCtrl_SEND_CMD($address,'VU');
	}
	// Send a command to the 822-K amp unit requesting the input change to $input
	// (which, for now, is the two numerals preceding "FN" in the Telnet command)
	// 
	// See PioneerCtrl_getSource for the most-handy input number cross-reference
	function PioneerCtrl_setSource($address,$fnInput) {
		PioneerCtrl_SEND_CMD($address,$fnInput.'FN');
	}
	// Send a command to the 822-K amp unit requesting the power turn on or off
	// 
	// Pass $fnPower=true for power-on (requires network over sleep enabled)
	// Pass $fnPower=false for power-off
	function PioneerCtrl_setPower($address,$fnPower) {
		$powerTo = 'F';
		if ($fnPower)
			$powerTo = 'O';
		PioneerCtrl_SEND_CMD($address,'P'.$powerTo);
	}
	// Send a command to the 822-K amp unit requesting the muting turn on or off
	// 
	// Pass $fnMuted=true for muted-on
	// Pass $fnMuted=false for muted-off
	function PioneerCtrl_setMuting($address,$fnMuted) {
		$mutedTo = 'F';
		if ($fnMuted)
			$mutedTo = 'O';
		PioneerCtrl_SEND_CMD($address,'M'.$mutedTo);
	}
	// Request the current status of audio output muting on the 822-K amp unit
	// On success returns *1* for un-muted (IE: "VOL XYZ" shown on LCD) or *0* for
	// "MUTING" -- success values are int types.  On failure, returns BOOLEAN type
	// false (remember to use === or !== to differentiate 0 from false!!!)
	function PioneerCtrl_getMuting($address) {
		$out = PioneerCtrl_SEND_CMD($address,'?M');
		if ( $out === false ) return false;
		// It would be best to have a more-rigorous failure handling for this one:
		$out = trim($out);
		if (strtoupper($out)==='R') return false;
		if (strlen($out)!=4) return false;
		$out = substr($out,3);
		if ($out!=='0'&&$out!=='1') return false;
		return intval(trim($out));
	}
	// Request percent of maximum volume currently seen set on the 822-K (this is
	// gathered by equating the 81 distinct volume levels to the closest integers
	// on a 100% scale, basically, multiplying the numeric volume setting from 0
	// to 80 by 1.25 and rounding the result to the nearest integer)
	// Returns current volume percent as string on OK or false (boolean) on error
	function PioneerCtrl_getVolPct($address) {
		$out = intval(PioneerCtrl_getVolVal($address));
		if (! $out) return false;
		$pct = strval(intval(round((floatval($out)*1.25))));
		return $pct;
	}
	// Request the current numeric volume setting seen by the 822-K's controller
	// (a value ranging 0 to 80 is the expected value on my 822-K unit)
	// Returns the current volume as int on OK or false (boolean) on error
	function PioneerCtrl_getVolVal($address) {
		$out = PioneerCtrl_SEND_CMD($address,'?V');
		if (! $out) return false;
		$val = intval((((intval(substr($out,3)))-1)/2));
		return $val;
	}
	// Request the current LCD reading (from a list of known values) that might
	// be displayed on the 822-K.  The list of known values was hand-compiled
	// by running my unit through its full set of dialable inputs and noting
	// the "FNxy" value that Telnet interface echoed back for the input change
	// Returns the string value on OK or false (boolean) on error
	function PioneerCtrl_getSource($address) {
		$inNames["FN01"] = "CD";
		$inNames["FN02"] = "Tuner";
		$inNames["FN04"] = "DVD";
		$inNames["FN05"] = "TV";
		$inNames["FN06"] = "SatCbl";
		$inNames["FN10"] = "Video";
		$inNames["FN15"] = "DVRBDR";
		$inNames["FN17"] = "iPodUSB";
		$inNames["FN25"] = "BD";
		$inNames["FN33"] = "Adapter";
		$inNames["FN38"] = "NetRadio";
		$inNames["FN41"] = "Pandora";
		$inNames["FN44"] = "MediaServer";
		$inNames["FN45"] = "Favorites";
		$inNames["FN46"] = "AirPlay";
		$inNames["FN47"] = "DMR";
		$inNames["FN49"] = "Game";
		$out = PioneerCtrl_SEND_CMD($address,'?FN');
		if (! $out) return false;
		$val = trim($out);
		return $inNames[$val];
	}
?>