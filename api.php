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
			$id .= $dictionary[rand(microtime(true) % (strlen(dictionary)-1),
				strlen($dictionary)-1)];
		}
		echo $id;
	}
	else if($namespace[1] == "permalink" && strlen($namespace[2]) > 0 &&
			strlen($namespace[3]) > 0)
	{
		$temp = "";
		if(!file_exists(createGSPath("permalinks_0.csv")))
		{
			$f = fopen(createGSPath("permalinks_0.csv"), "w");
			fwrite($f, "encodedData,permalink\n");
		}
		else
		{
			$temp1 = file_get_contents(createGSPath("permalinks_0.csv"));
			unlink(createGSPath("permalinks_0.csv"));

			$fX = explode("\n", substr($temp1, 0, strlen($temp1)-1));
			foreach($fX as $x)
			{
				$fY = explode(",", $x);
				if($fY[1] != $namespace[3])
					$temp .= $fY[0].",".$fY[1]."\n";
			}

			$f = fopen(createGSPath("permalinks_0.csv"), "w");
		}

		fwrite($f, $temp);
		fwrite($f, $namespace[2].",".$namespace[3]."\n");
		fclose($f);

		echo $namespace[3];
	}
}
else if($namespace[0] == "decode")
{
	if($namespace[1] == "tally" && $namespace[2] == "encodedData"
			&& strlen($namespace[3]) > 0)
	{
		$payload = "";

		if(file_exists(createGSPath("permalinks_0.csv")))
		{
			$memcache = new Memcache();
			$temp = file_get_contents(createGSPath("permalinks_0.csv"));
			$fragmentX = explode("\n", $temp);

			foreach($fragmentX as $x)
			{
				$fragmentY = explode(",", $x);

				if($fragmentY[1] == $namespace[3])
					$payload = urldecode(base64_decode($fragmentY[0]));
			}
		}

		if(strlen($payload) > 0)
			echo $payload;
		else
		{
			if(strpos($payload, ":") === false)
				echo "Invalid payload.";
			else
				echo urldecode(base64_decode($namespace[3]));
		}
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
			echo ($_POST['g16_tally_commit'] != APP_COMMIT) ? "true" : "false";
	}
}
?>
