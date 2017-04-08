<?php
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
			$id .= $dictionary[rand(0, strlen($dictionary)-1)];

		echo $id;
	}
}
else if($namespace[0] == "decode")
{
	if($namespace[1] == "tally" && $namespace[2] == "encodedData"
		&& strlen($namespace[3]) > 0)
		echo urldecode(base64_decode($namespace[3]));
}
?>
