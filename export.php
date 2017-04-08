<?php
$path = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 8);
$namespace = explode('/', $path);

$data = urldecode(base64_decode($namespace[0]));
$method = $namespace[1];
$decodedData = array();

$fragmentX = explode(',', $data);
foreach($fragmentX as $fragment)
{
	$fragmentY = explode(':', $fragment);

	$decodedData[$fragmentY[0]] = $fragmentY[1];
}

// Enable CORS for Google Drive
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: POST, GET");
header('Access-Control-Allow-Headers: Range, Content-Type, Cache-Control, Content-Encoding, Content-Range');

header('Cache-Control: no-cache');

if($method == "csv")
{
	header('Content-Type: text/csv');

	echo "Item,Count\n";
	foreach($decodedData as $item=>$count)
		echo $item.",".$count."\n";
}
else if($method == "tsv")
{
	header('Content-Type: text/tsv');

	echo "Item\tCount\n";
	foreach($decodedData as $item=>$count)
		echo $item."\t".$count."\n";
}
else if($method == "sql")
{
	header('Content-Type: application/sql');
	$name = explode('.', $namespace[2]);

	echo "CREATE TABLE ".$name[0]." (\n\t"
		."Item text,\n\t"
		."Count int\n"
		.");\n";

	foreach($decodedData as $item=>$count)
		echo "INSERT INTO ".$name[0]." VALUES ('".$item."','".$count."');\n";
}
else
{
	header('Content-Type: text/plain');

	echo "Invalid export format.";
}
?>
