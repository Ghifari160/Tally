<?php
define('APP_VERSION', 'v0.1');
define('APP_COMMIT', '662eaeba6ebef35d547150367f478fcd8092355b'); // Reflects
// previous commit
define('APP_DEV', true);

define('GS_BUCKET', 'tally-dev.appspot.com');

if($_SERVER['SRV_ENG'] == "apache")
{
	set_error_handler("error_handler");
	register_shutdown_function("shutdown_handler");
}

function getAppName()
{
	echo "Tally".((APP_DEV) ? ".Dev" : "");
}

function getAppVersion()
{
	echo APP_VERSION."_".substr(APP_COMMIT, 0, 20).((APP_DEV) ? "_dev" : "");
}

function getAppIcons()
{
	echo "<meta name=\"apple-mobile-web-app-title\" content=\"Tally"
			.((APP_DEV) ? ".Dev" : "")."\">\n"
		// ."<meta name=\"apple-mobile-web-app-capable\" content=\"yes\">\n"
		."<link rel=\"apple-touch-icon\" href=\"/static/logo-120.png\">\n"
		."<link rel=\"apple-touch-icon\" sizes=\"152x152\" href=\"/static/logo-152.png\">\n"
		."<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/static/logo-180.png\">\n"
		."<link rel=\"apple-touch-icon\" sizes=\"76x76\" href=\"/static/logo-76.png\">\n"
		."<link rel=\"icon\" sizes=\"192x192\" href=\"/static/logo-192.png\">\n"
		."<link rel=\"icon\" sizes=\"128x128\" href=\"/static/logo-128.png\">\n"
		."<link rel=\"icon\" sizes=\"32x32\" href=\"/static/logo-32.png\">\n";
}

function createGSPath($path)
{
	return "gs://".GS_BUCKET."/".$path;
}

function error_handler($e_lvl, $e_msg)
{
	include("/error_pages/default_error.html");
	die();
}

function shutdown_handler()
{
	$error = error_get_last();
	if($error['type'] == E_ERROR)
		error_handler("", "");
}
?>
