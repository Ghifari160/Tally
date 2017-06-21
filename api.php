<?php
require_once "core.php";
require_once "generate.api.class.php";
require_once "decode.api.class.php";
require_once "environment.api.class.php";

if(!array_key_exists("HTTP_ORIGIN", $_SERVER))
	$_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];

$path = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 5);
$namespace = explode(".", $path);
$endpoint = array_shift($namespace);

try
{
	if($endpoint == "generate")
	{
		$api = new GenerateAPI(implode(".", $namespace), $_SERVER['HTTP_ORIGIN']);
		echo $api->processAPI();
	}
	else if($endpoint == "decode")
	{
		$api = new DecodeAPI(implode(".", $namespace), $_SERVER['HTTP_ORIGIN']);
		echo $api->processAPI();
	}
	else if($endpoint == "environment")
	{
		$api = new EnvironmentAPI(implode(".", $namespace), $_SERVER['HTTP_ORIGIN']);
		echo $api->processAPI();
	}
	else
	{
		header("HTTP/1.1 404 Not Found");
		echo "No endpoint: ".$endpoint;
	}
}
catch (Exception $e)
{
	echo $e->getMessage();
}
?>
