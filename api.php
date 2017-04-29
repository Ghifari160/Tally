<?php
require_once "config.php";

$path = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 5);

$namespace = explode('.', $path);

if($namespace[0] == "generate")
{
	if($namespace[1] == "id")
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
		echo $id;
	}
}
else if($namespace[0] == "decode")
{
	if($namespace[1] == "tally" && $namespace[2] == "encodedData"
			&& strlen($namespace[3]) > 0)
	{
		$payload = urldecode(base64_decode($namespace[3]));

		if(strpos($payload, ":") === false || strlen($payload) < 1)
			echo "Invalid payload.";
		else
				echo $payload;
	}
}
else if($namespace[0] == "environment")
{
	if($namespace[1] == "time")
		echo microtime(true);
	else if($namespace[1] == "tally")
	{
		if($namespace[2] == "name")
			echo "Tally".((APP_DEV) ? ".Dev" : "")." by Ghifari160";
		else if($namespace[2] == "forcedUpdate")
			echo (($_POST['g16_tally_commit'] != APP_COMMIT) ? "true" : "false");
		else if($namespace[2] == "SRV_ENG")
			echo $_SERVER['SRV_ENG'];
		else if($namespace[2] == "debug" && $namespace[3] == "timeout")
		{
			while(true){}
		}
	}
}
?>
