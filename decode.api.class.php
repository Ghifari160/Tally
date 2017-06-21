<?php
require_once "api.class.php";

class DecodeAPI extends API
{
	public function __construct($request, $origin)
	{
		parent::__construct($request);
	}

	protected function tally($argument)
	{
		if($argument[0] == "encodedData")
		{
			$payload = "";
			if($this->method == "POST")
			{
				if(strlen($this->rawInput) > 0)
					$payload = $this->rawInput;
				else
					return parent::ERROR_PREFIX."400"."Invalid payload.";
			}
			else if($this->method == "GET")
			{
				if(count($argument) > 1 && strlen($argument[1]) > 0)
					$payload = $argument[1];
				else
					return parent::ERROR_PREFIX."400"."Invalid payload.";
			}
			else
			{
				return parent::ERROR_PREFIX."405".$this->method.
					" request is not allowed.";
			}

			// Get dynamic data from ID
			if(file_exists(createGSPath("permalinks/".$payload.".csv")))
			{
				$payload = file_get_contents(
					createGSPath("permalinks/".$payload.".csv"));
			}

			// Decode the payload
			$payload = urldecode(base64_decode($payload));

			// Verify payload integrity
			if(strpos($payload, ":") === false || strlen($payload) < 1)
				return parent::ERROR_PREFIX."400"."Invalid payload.";
			else
				return $payload;
		}
		else
			return parent::ERROR_PREFIX."404"."Invalid argument: ".$argument[0];
	}
}
?>
