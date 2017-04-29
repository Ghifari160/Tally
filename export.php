<?php
require_once "config.php";

$startTime = microtime(true);
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

$payload = "";

if($_SERVER['SRV_ENG'] == "gcloud")
{
	$memcache = new Memcache();
	$payload = $memcache->get($namespace[0].".".$namespace[1]);
}
else if($_SERVER['SRV_ENG'] == "apache")
{
	if(!file_exists("temp"))
		mkdir("temp");

	if(!file_exists("temp/".$namespace[0].".".$method))
		$memcache = fopen("temp/".$namespace[0].".".$method, "w");
	else
		$payload = file_get_contents("temp/".$namespace[0].".".$method);
}

if($method == "csv")
{
	header('Content-Type: text/csv');

	if(strlen($payload) > 0)
		echo $payload;
	else
	{
		$payload = "Item,Count\n";
		foreach($decodedData as $item=>$count)
			$payload .= $item.",".$count."\n";

		if($_SERVER['SRV_ENG'] == "gcloud")
			$memcache->set($namespace[0].".".$namespace[1], $payload);
		else if($_SERVER['SRV_ENG'] == "apache")
		{
			fwrite($memcache, $payload);
			fclose($memcache);
		}

		echo $payload;
	}
}
else if($method == "tsv")
{
	header('Content-Type: text/tsv');

	if(strlen($payload) > 0)
		echo $payload;
	else
	{
		$payload = "Item\tCount\n";
		foreach($decodedData as $item=>$count)
			$payload .= $item."\t".$count."\n";

		if($_SERVER['SRV_ENG'] == "gcloud")
			$memcache->set($namespace[0].".".$namespace[1], $payload);
		else if($_SERVER['SRV_ENG'] == "apache")
		{
			fwrite($memcache, $payload);
			fclose($memcache);
		}

		echo $payload;
	}
}
else if($method == "sql")
{
	header('Content-Type: application/sql');

	if(strlen($payload) > 0)
		echo $payload;
	else
	{
		$name = explode('.', $namespace[2]);
		$payload = "CREATE TABLE ".$name[0]." (\n\t"
			."Item text,\n\t"
			."Count int\n"
			.");\n";

		foreach($decodedData as $item=>$count)
			$payload .= "INSERT INTO ".$name[0]." VALUES ('".$item."','".$count."');\n";

		if($_SERVER['SRV_ENG'] == "gcloud")
			$memcache->set($namespace[0].".".$namespace[1], $payload);
		else if($_SERVER['SRV_ENG'] == "apache")
		{
			fwrite($memcache, $payload);
			fclose($memcache);
		}

		echo $payload;
	}
}
// Soft-disabled
else if($method == "xlsx")
{
	header('Content-Type: text/plain');
	$software = explode('/', $_SERVER['SERVER_SOFTWARE']);

	$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n";

	$xmlns = array(
		'main' => ' xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"',
		'r' => ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"',
		'mx' => ' xmlns:mx="http://schemas.microsoft.com/office/mac/excel/2008/main"',
		'mc' => ' xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"',
		'mv' => ' xmlns:mv="urn:schemas-microsoft-com:mac:vml"',
		'x14' => ' xmlns:x14="http://schemas.microsoft.com/office/spreadsheetml/2009/9/main"',
		'x14ac' => ' xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac"',
		'xm' => ' xmlns:xm="http://schemas.microsoft.com/office/excel/2006/main"',
		'pR' => " xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\"",
		'cT' => " xmlns=\"http://schemas.openxmlformats.org/package/2006/content-types\"",
		'mR' => "http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument",
		'wR' => "http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet",
		'sstR' => "http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings",
		'sR' => "http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"
	);

	$rels = $xml."<Relationships".$xmlns['pR'].">\n<Relationship Id=\"rId1\" "
		."Type=\"".$xmlns['mR']."\" Target=\"xl/workbook.xml\"/>\n</Relationships>\n";

	$workbookRels = $xml."<Relationships".$xmlns['pR'].">\n<Relationship "
		."Id=\"rId1\" Type=\"".$xmlns['wR']."\" Target=\"worksheets/sheet1.xml\"/>\n"
		."<Relationship Id=\"rId2\" Type=\"".$xmlns['sstR']."\" "
		."Target=\"sharedStrings.xml\"/>\n<Relationship Id=\"rId3\" Type=\""
		.$xmlns['sR']."\" Target=\"styles.xml\"/>\n</Relationships>\n";

	$worksheet = $xml."<worksheet".$xmlns['main'].$xmlns['r'].$xmlns['mx']
		.$xmlns['mc'].$xmlns['mv'].$xmlns['x14'].$xmlns['x14ac'].$xmlns['xm'].">\n";

	$sst = $xml."<sst".$xmlns['main'];
	$sharedStrings = "";

	$styles = $xml."<styleSheet".$xmlns['main'].$xmlns['x14ac'].$xmlns['mc'].">\n"
		."<fonts count=\"1\">\n<font><sz val=\"10.0\"/><color rgb=\"FF000000\"/>"
		."<name val=\"Arial\"/></font>\n</fonts>\n<fills count=\"1\">\n<fill>"
		."<patternFill patternType=\"none\"/></fill>\n</fills>\n"
		."<borders count=\"1\">\n<border><left/><right/><top/><bottom/></border>\n"
		."</borders>\n<cellStyleXfs count=\"1\">\n<xf borderId=\"0\" fillId=\"0\" "
		."fontId=\"0\" numFmtId=\"0\" applyAlignment=\"1\" applyFont=\"1\"/>\n"
		."</cellStyleXfs>\n<cellXfs count=\"2\">\n<xf borderId=\"0\" fillId=\"0\" "
		."fontId=\"0\" numFmtId=\"0\" xfId=\"0\" applyAlignment=\"1\" "
		."applyFont=\"1\"><alignment/></xf>\n<xf borderId=\"0\" fillId=\"0\" "
		."fontId=\"0\" numFmtId=\"0\" xfId=\"0\" applyAlignment=\"1\" "
		."applyFont=\"1\"><alignment/></xf>\n</cellXfs>\n<cellStyles count=\"1\">\n"
		."<cellStyle xfId=\"0\" name=\"Normal\" builtinId=\"0\"/>\n</cellStyles>\n"
		."<dxfs count=\"0\"/>\n</styleSheet>\n";

	$workbook = $xml."<workbook".$xmlns['main'].$xmlns['r'].$xmlns['mx']
		.$xmlns['mc'].$xmlns['mv'].$xmlns['x14'].$xmlns['x14ac'].$xmlns['xm'].">\n"
		."<workbookPr/>\n<sheets>\n<sheet state=\"visible\" name=\"tally-export\" "
		."sheetId=\"1\" r:id=\"rId1\"/>\n</sheets>\n<definedNames/>\n<calcPr/>\n"
		."</workbook>\n";

	$contentTypes = $xml."<Types".$xmlns['cT'].">\n<Default "
		."ContentType=\"application/xml\" Extension=\"xml\"/>\n<Default "
		."ContentType=\"application/vnd.openxmlformats-package.relationships+xml\" "
		."Extension=\"rels\"/>\n<Override "
		."ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml"
		.".worksheet+xml\" PartName=\"/xl/worksheets/sheet1.xml\"/>\n<Override "
		."ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml"
		.".sharedStrings+xml\" PartName=\"/xl/sharedStrings.xml\"/>\n<Override "
		."ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml"
		.".styles+xml\" PartName=\"/xl/styles.xml\"/>\n<Override ContentType=\""
		."application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
		.".main+xml\" PartName=\"/xl/workbook.xml\"/>\n</Types>\n";

	$worksheet .= "<sheetViews><sheetView workbookViewId=\"0\"/></sheetViews>\n"
		."<sheetFormatPr customHeight=\"1\" defaultColWidth=\"14.43\" defaultRowHeight=\"15.75\"/>\n"
		."<sheetData>\n<row r=\"1\"><c r=\"A1\" s=\"1\" t=\"s\"><v>0</v></c>"
		."<c r=\"B1\" s=\"1\" t=\"s\"><v>1</v></c></row>\n";

	$sharedStrings .= "<si><t>Item</t></si>\n<si><t>Count</t></si>\n";

	$string = 1;
	$i = 1;
	foreach($decodedData as $item=>$count)
	{
		$worksheet .= "<row r=\"".++$i."\"><c r=\"A".$i."\" s=\"1\" t=\"s\"><v>"
			.++$string."</v></c><c r=\"B".$i."\" s=\"1\"><v>".number_format($count, 1)
			."</v></c></row>\n";

		$sharedStrings .= "<si><t>".$item."</t></si>\n";
	}

	$worksheet .= "</sheetData>\n</worksheet>\n";

	$sst .= " count=\"".++$string."\" uniqueCount=\"".$string."\">\n"
		.$sharedStrings."</sst>\n";

	if($software[0] == "Google App Engine" || $software[0] == "Development")
	{
		$packageContents = array();
		$baseDir = "gs://".GS_BUCKET."/temp/".$namespace[0];
		if(!file_exists($baseDir))
			mkdir($baseDir);
		// if(!file_exists($baseDir."/_rels"))
		// 	mkdir($baseDir."/_rels");
		//
		// Write /_rels/.rels
		$packageContents['_rels/.rels'] = $rels;
		// if(!file_exists($baseDir."/_rels/.rels"))
		// 	file_put_contents($baseDir."/_rels/.rels", $rels);
		//
		// Write /xl/_rels/workbook.xml.rels
		$packageContents['xl/_rels/workbook.xml.rels'] = $workbookRels;
		// if(!file_exists($baseDir."/xl/_rels"))
		// 	mkdir($baseDir."/xl/_rels");
		//
		// if(!file_exists($baseDir."/xl/_rels/workbook.xml.rels"))
		// 	file_put_contents($baseDir."/xl/_rels/workbook.xml.rels", $workbookRels);
		//
		// Write /xl/worksheets/sheet1.xml
		$packageContents['xl/worksheets/sheet1.xml'] = $worksheet;
		// if(!file_exists($baseDir."/xl/worksheets"))
		// 	mkdir($baseDir."/xl/worksheets");
		//
		// if(!file_exists($baseDir."/xl/worksheets/sheet1.xml"))
		// 	file_put_contents($baseDir."/xl/worksheets/sheet1.xml", $worksheet);
		//
		// Write /xl/sharedStrings.xml
		$packageContents['xl/sharedStrings.xml'] = $sst;
		// if(!file_exists($baseDir."/xl/sharedStrings.xml"))
		// 	file_put_contents($baseDir."/xl/sharedStrings.xml", $sst);
		//
		// Write /xl/styles.xml
		$packageContents['xl/styles.xml'] = $styles;
		// if(!file_exists($baseDir."/xl/styles.xml"))
		// 	file_put_contents($baseDir."/xl/styles.xml", $styles);
		//
		// Write /xl/workbook.xml
		$packageContents['xl/workbook.xml'] = $workbook;
		// if(!file_exists($baseDir."/xl/workbook.xml"))
		// 	file_put_contents($baseDir."/xl/workbook.xml", $workbook);
		//
		// Write /[Content_Types].xml
		$packageContents['[Content_Types].xml'] = $contentTypes;
		// if(!file_exists($baseDir."/[Content_Types].xml"))
		// 	file_put_contents($baseDir."/[Content_Types].xml", $contentTypes);

		// Package Excel workbook
		$zip = new ZipArchive();
		$r = $zip->open($baseDir."/".$namespace[0].".xlsx", ZipArchive::CREATE | ZipArchive::OVERWRITE);

		if($r)
		{
			foreach($packageContents as $filePath=>$file)
			{
				$zip->addFromString($filePath, $file);
			}

			if($zip->close())
				echo "Closed successfuly\n";
			else
				echo "Failed to create archive\n";
		}
		else
			 echo $r."\n";

		print_r(error_get_last());

		// $files = new RecursiveIteratorIterator(
		// 	new RecursiveDirectoryIterator($baseDir),
		// 	RecursiveIteratorIterator::LEAVES_ONLY);
		//
		// foreach($files as $name => $file)
		// {
		// 	if(!$file->isDir())
		// 	{
		// 		$filePath = $file->getRealPath();
		//
		// 		$zip->addFile($filePath, $filePath);
		// 	}
		// }
	}
	else
	{
		$baseDir = "temp/".$namespace[0];
		if(!file_exists($baseDir))
			mkdir($baseDir, 0777, true);

		// /_rels/.rels
		if(!file_exists($baseDir."/_rels"))
			mkdir($baseDir."/_rels", 0777, true);

		if(!file_exists($baseDir."/_rels/.rels"))
		{
			$f = fopen($baseDir."/_rels/.rels", "w");
			fwrite($f, $rels);
			fclose($f);
		}

		if(!file_exists($baseDir."/xl"))
			mkdir($baseDir."/xl", 0777, true);

		// /xl/_rels/workbook.xml.rels:
		if(!file_exists($baseDir."/xl/_rels"))
			mkdir($baseDir."/xl/_rels", 0777, true);

		if(!file_exists($baseDir."/xl/_rels/workbook.xml.rels"))
		{
			$f = fopen($baseDir."/xl/_rels/workbook.xml.rels", "w");
			fwrite($f, $workbookRels);
			fclose($f);
		}

		// /xl/worksheets/sheet1.xml
		if(!file_exists($baseDir."/xl/worksheets"))
			mkdir($baseDir."/xl/worksheets", 0777, true);

		if(!file_exists($baseDir."/xl/worksheets/sheet1.xml"))
		{
			$f = fopen($baseDir."/xl/worksheets/sheet1.xml", "w");
			fwrite($f, $worksheet);
			fclose($f);
		}

		// /xl/sharedStrings.xml
		if(!file_exists($baseDir."/xl/sharedStrings.xml"))
		{
			$f = fopen($baseDir."/xl/sharedStrings.xml", "w");
			fwrite($f, $sst);
			fclose($f);
		}

		// /xl/styles.xml
		if(!file_exists($baseDir."/xl/styles.xml"))
		{
			$f = fopen($baseDir."/xl/styles.xml", "w");
			fwrite($f, $styles);
			fclose($f);
		}

		// /xl/workbook.xml
		if(!file_exists($baseDir."/xl/workbook.xml"))
		{
			$f = fopen($baseDir."/xl/workbook.xml", "w");
			fwrite($f, $workbook);
			fclose($f);
		}

		// /[Content_Types].xml
		if(!file_exists($baseDir."/[Content_Types].xml"))
		{
			$f = fopen($baseDir."/[Content_Types].xml", "w");
			fwrite($f, $contentTypes);
			fclose($f);
		}

		$root = realpath($baseDir);

		// Package the Excel workbook

		$zip = new ZipArchive();
		$zip->open($baseDir."/".$namespace[0].".xlsx", ZipArchive::CREATE | ZipArchive::OVERWRITE);

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root),
			RecursiveIteratorIterator::LEAVES_ONLY);

		foreach($files as $name => $file)
		{
			if(!$file->isDir())
			{
				$filePath = $file->getRealPath();
				$relativePath = substr($filePath, strlen($root) + 1);

				$zip->addFile($filePath, $relativePath);
			}
		}

		$zip->close();

		// Outputs the packaged workbook
		echo file_get_contents($baseDir."/".$namespace[0].".xlsx");
	}

	$endTime = microtime(true);
	echo "\nExecution time: ".($endTime - $startTime);
}
else
{
	header('Content-Type: text/plain');

	$payload = "Invalid export format.";
	echo $payload;
}
?>
