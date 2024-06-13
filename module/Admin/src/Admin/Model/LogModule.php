<?php
namespace Admin\Model;

class LogModule {
	/** save log in public/log.txt
	 * @param array $logData ["reason", "message"]
	 * @return string $log
	 */
	public function SaveLog($logData) {
		$logString = "";
		$logString .= date("Y-m-d H:i:s") . " >> ";
		$logString .=  $logData["reason"] . "\n";
		if (isset($logData["message"])) {
			$logString .= "error message:\n";
			$logString .= $logData["message"] . "\n";
		}

		$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
		$fp = fopen($DOCUMENT_ROOT . "/log.txt", "a");
		fwrite($fp, $logString);
		fclose($fp);

		return $logString;
	}
}