<?php
require_once "config.php";

if(isset($_SERVER['SRV_ENG']) && $_SERVER['SRV_ENG'] == "apache")
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
		."<link rel=\"apple-touch-icon\" href=\"".G16_COMMON_ASSETS."images/logo-120.png\">\n"
		."<link rel=\"apple-touch-icon\" sizes=\"152x152\" href=\"".G16_COMMON_ASSETS."images/logo-152.png\">\n"
		."<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"".G16_COMMON_ASSETS."images/logo-180.png\">\n"
		."<link rel=\"apple-touch-icon\" sizes=\"76x76\" href=\"".G16_COMMON_ASSETS."images/logo-76.png\">\n"
		."<link rel=\"icon\" sizes=\"192x192\" href=\"".G16_COMMON_ASSETS."images/logo-192.png\">\n"
		."<link rel=\"icon\" sizes=\"128x128\" href=\"".G16_COMMON_ASSETS."images/logo-128.png\">\n"
		."<link rel=\"icon\" sizes=\"32x32\" href=\"".G16_COMMON_ASSETS."images/logo-32.png\">\n";
}

function getAppJS()
{
	echo "<script src=\""
			."https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js\" "
			."defer></script>\n<script src=\"".APP_STATIC_ASSETS."base64.js\" defer>"
			."</script>\n<script src=\"".APP_STATIC_ASSETS."app.js\" defer></script>"
			."\n<script src=\"https://apis.google.com/js/platform.js\" async defer>"
			."</script>\n";
}

function getAppCSS()
{
	echo "\n\t<link rel=\"stylesheet\" href=\"".APP_STATIC_ASSETS."g16.css\">\n\t"
			."<link rel=\"stylesheet\" href=\"".APP_STATIC_ASSETS."app.css\">\n\t<link"
			." rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css?family=Open+"
			."Sans:300,300i,400,400i,600,600i,700,700i,800,800i|Roboto:100,100i,300,"
			."300i,400,400i,500,500i,700,700i,900,900i\">\n";
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
