<?php
require_once "api.class.php";

class GenerateAPI extends API
{
	public function __construct($request, $origin)
	{
		parent::__construct($request);
	}

	protected function id($args)
	{
		if($this->method == "GET" || $this->method == "POST")
		{
			$dictionary = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$dictionary .= "abcdefghijklmnopqrstuvwxyz";
			$dictionary .= "0123456789";

			$id = "";
			for($i = 0; $i < 32; $i++)
			{
				$id .= $dictionary[rand(microtime(true) % (strlen($dictionary)-1),
					strlen($dictionary)-1)];
			}
			return $id;
		}
		else
			return parent::ERROR_PREFIX."405".$this->method." request is not allowed.";
	}

	protected function permalink($args)
	{
		$id = "";

		if(count($args) > 0)
			$id = $args[0];
		else
		{
			$id = $this->id();

			if(strlen($id) > strlen(parent::ERROR_PREFIX) &&
					substr($id, 0, strlen(parent::ERROR_PREFIX)) == parent::ERROR_PREFIX)
				return parent::ERROR_PREFIX."500"."Cannot generate unique identifier.";
		}

		$options = [ "gs" => [
				"act" => "bucketOwnerRead",
				"Content-Type" => "text/csv",
				"enable_cache" => false
			]];
		$context = stream_context_create($options);
		file_put_contents(createGSPath("permalinks/".$id.".csv"), $this->rawInput,
			0, $context);

		return $id;
	}
}
?>
