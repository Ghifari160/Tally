<?php
require_once "api.class.php";

class EnvironmentAPI extends API
{
	public function __construct($request, $origin)
	{
		parent::__construct($request);
	}

	protected function time()
	{
		return microtime(true);
	}

	protected function tally($argument)
	{
		if($this->method != "POST" && $this->method != "GET")
			return parent::ERROR_PREFIX."405".$this->method." request is not allowed.";

		if($argument[0] == "name")
			return "Tally".((APP_DEV) ? ".Dev" : "")." by Ghifari160";
		else if($argument[0] == "forcedUpdate")
		{
			return ((isset($_POST['g16_tally_commit']) &&
				$_POST['g16_tally_commit'] == APP_COMMIT) ? "false" : "true");
		}
		else if($argument[0] == "SRV_ENG")
			return $_SERVER['SRV_ENG'];
		else if(count($argument) > 1 && $argument[0] == "debug" &&
				$argument[1] == "timeout")
		{
			while(true){}
		}

		return parent::ERROR_PREFIX."404"."Invalid argument: ".implode(".", $argument);
	}
}
