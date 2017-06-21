<?php
require_once "config.php";

// Parses PHP script
// @param $str script
function process_php($str)
{
	$php_start = 0;

	$bTag = "<?php";
	$eTag = "?>";

	while(is_long($php_start = strpos($str, $bTag, $php_start)))
	{
		$startPos = $php_start + strlen($bTag);
		$endPos = strpos($str, $eTag, $startPos);

		if(!$endPos)
		{
			echo "ERROR: parameter \$str requires end tags.";
			exit;
		}

		$php_end = $endPos + strlen($eTag);

		$code = substr($str, $startPos, $endPos - $startPos);
		if(strtolower(substr($code, 0, 3)) == "php")
			$code = substr($code, 3);

		$fragment1 = substr($str, 0, $php_start);
		$fragment2 = substr($str, $php_end, strlen($str));

		// Execute PHP
		ob_start();
		eval($code);
		$output = ob_get_contents();
		ob_end_clean();

		$str = $fragment1.$output.$fragment2;
	}

	return $str;
}

// Replaces the assets URL to a specific build
// @param $str script
function replaceAssets($str)
{
	return str_replace("\"/static/", "\"/".APP_COMMIT."/", $str);
}

// Optimizes Javascript assets
// @param $str Javascript
function optimizeJS($str)
{
	$str = urlencode($str);

	$c = curl_init();

	curl_setopt($c, CURLOPT_URL, "http://closure-compiler.appspot.com/compile");
	curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_POSTFIELDS,
		"js_code=".$str."&compilation_level=ADVANCED_OPTIMIZATIONS&output_info="
		."compiled_code&output_format=text");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

	$r = curl_exec($c);
	curl_close($c);

	return $r;
}

// Optimizes inline javascript
// @param $str HTML with inline javascript
// @return HTML with optimized inline javascript
function optimizeInlineJS($str)
{
	$sTag = "<script>";
	$eTag = "</script>";

	$start = 0;

	// Parse HTML
	while(is_long($start = strpos($str, $sTag, $start)))
	{
		// Find JS boundaries
		$startPos = $start + strlen($sTag);
		$endPos = strpos($str, $eTag, $startPos);

		// Ignore incomplete javascript
		if(!$endPos)
			exit;

		$end = $endPos + strlen($eTag);

		$code = substr($str, $startPos, $endPos - $startPos);

		// Non JS strings
		$fragment1 = substr($str, 0, $start);
		$fragment2 = substr($str, $end, strlen($str));

		// Replace $str with optimized JS
		$str = $fragment1."<TMP>".optimizeJS($code)."</TMP>".$fragment2;
	}

	// Normalize $str before returning
	return str_replace("TMP", "script", $str);
}

// Optimizes CSS string
// @param $str CSS string
// @return optimized CSS string
function optimizeCSS($str)
{
	// Remove whitespaces
	$str = str_replace(" ", "", $str);
	$str = str_replace("\t", "", $str);
	$str = preg_replace("/[\r\n]+/", "", $str);
	$str = preg_replace("/[\n]+/", "", $str);

	return $str;
}

// Optimizes inline CSS string
// @param $str HTML string with inline CSS
// @return HTML string with optimized inline CSS
function optimizeInlineCSS($str)
{
	$sTag = "<style>";
	$eTag = "</style>";

	$start = 0;

	// Parse HTML
	while(is_long($start = strpos($str, $sTag, $start)))
	{
		// Find CSS boundaries
		$startPos = $start + strlen($sTag);
		$endPos = strpos($str, $eTag, $startPos);

		// Ignore incomplete CSS
		if(!$endPos)
			exit;

		$end = $endPos + strlen($eTag);

		$code = substr($str, $startPos, $endPos - $startPos);

		// Non CSS strings
		$fragment1 = substr($str, 0, $start);
		$fragment2 = substr($str, $end, strlen($str));

		// Replace $str with optimized CSS
		$str = $fragment1."<TMP>".optimizeCSS($code)."</TMP>".$fragment2;
	}

	// Normalize $str before returning
	return str_replace("TMP", "style", $str);
}

// Optimizes HTML string
// @param $str HTML
// @return optimized HTML
function optimizeHtml($str)
{
	// Replaces tabs with spaces
	$str = str_replace("\t", "", $str);
	// Replaces new lines with spaces
	$str = preg_replace("/[\r\n]+/", "", $str);
	$str = preg_replace("/[\n]+/", "", $str);

	return $str;
}

header("Content-type: text/plain");

// Build steps
$staticAssets = array();
$ignoredAssets = array();

if(APP_DEV)
	echo "Building in dev mode...";
else
	echo "Building in deploy mode...";
echo "\n\n";

echo "[1/2] Building error pages...\n";
file_put_contents("error_pages/default_error.html", str_replace("{ERROR_MSG}",
		"The developer has been notified of this error",
		process_php(file_get_contents("error_pages/template.php"))));

echo "[2/2] Building error pages...\n";
file_put_contents("error_pages/dos_api_denial.html", str_replace("{ERROR_MSG}",
		"You have been disconnected due to suspicion of DOS.",
		process_php(file_get_contents("error_pages/template.php"))));
array_push($staticAssets, "error_pages/default_error.html",
	"error_pages/dos_api_denial.html");

echo "\n";

echo "Scanning static assets...\n";
$dirContents = scandir("static/");
foreach($dirContents as $content)
{
	$namespace = explode(".", $content);

	if(($namespace[count($namespace) - 1] == "js" ||
			$namespace[count($namespace) - 1] == "css") &&
			$namespace[count($namespace) - 2] != "ignore")
		array_push($staticAssets, "static/".$content);
	else
		array_push($ignoredAssets, "static/".$content);
}

echo "Ignoring ".count($ignoredAssets)." static assets.\n";

echo "\n";

if(!APP_DEV)
{
	for($i = 0; $i < count($staticAssets); $i++)
	{
		echo "[".($i + 1)."/".count($staticAssets)."] Optimizing static asset: "
			.$staticAssets[$i];

		if(pathinfo($staticAssets[$i], PATHINFO_EXTENSION) == "js")
		{
			file_put_contents($staticAssets[$i],
				optimizeJS(file_get_contents($staticAssets[$i])));
		}
		else if(pathinfo($staticAssets[$i], PATHINFO_EXTENSION) == "css")
		{
			file_put_contents($staticAssets[$i],
				optimizeCSS(file_get_contents($staticAssets[$i])));
		}
		else if(pathinfo($staticAssets[$i], PATHINFO_EXTENSION) == "html")
		{
			$html = file_get_contents($staticAssets[$i]);

			// Optimize inline javascript
			$html = optimizeInlineJS($html);
			// Optimize inline CSS
			$html = optimizeInlineCSS($html);
			// Optimize HTML
			$html = optimizeHtml($html);

			file_put_contents($staticAssets[$i], $html);
		}
		else
			echo ". Ignoring asset.";

		echo "\n";
	}
}
else
	echo "[DEV_MODE] Ignoring static assets.";

echo "\n";

$configTarget = array();

echo "Scanning for configuration targets...\n";

// Scan root dir
$dirContents = scandir(".");
foreach($dirContents as $content)
{
	$namespace = explode(".", $content);

	if(($namespace[count($namespace) - 1] == "php" ||
			$namespace[count($namespace) - 1] == "yaml" ||
			$namespace[count($namespace) - 1] == "html") &&
			$namespace[count($namespace) - 2] != "ignore")
	{
		array_push($configTarget, $content);
	}
}

// Scan error_pages dir
$dirContents = scandir("error_pages/");
foreach($dirContents as $content)
{
	$namespace = explode(".", $content);

	if(($namespace[count($namespace) - 1] == "php" ||
			$namespace[count($namespace) - 1] == "yaml" ||
			$namespace[count($namespace) - 1] == "html") &&
			$namespace[count($namespace) - 2] != "ignore")
	{
		array_push($configTarget, "error_pages/".$content);
	}
}

echo "Found ".count($configTarget)." configuration targets.\n";

echo "\n";

if(!APP_DEV)
{
	for($i = 0; $i < count($configTarget); $i++)
	{
		echo "[".($i + 1)."/".count($configTarget)."] Configuring target: "
			.$configTarget[$i]."\n";

		$content = file_get_contents($configTarget[$i]);

		// Replace /static/ with .{COMMIT}/
		$content = str_replace("/static", "/".APP_COMMIT, $content);

		file_put_contents($configTarget[$i], $content);
	}
}
else
	echo "[DEV_MODE] Ignoring configuration targets.";

echo "\n";

echo "Done!";
?>
