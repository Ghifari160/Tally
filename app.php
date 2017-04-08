<?php $path = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1); ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Ghifari160 | Tally Counter</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/g16.css">
<link rel="stylesheet" href="/app.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="/base64.js"></script>
<script src="app.js"></script>
<script src="https://apis.google.com/js/platform.js" async defer></script>
</head>

<body>
<div id="modal"></div>
<div id="modal-dialog">
	<div class="title">Export</div>
	<div class="body">
		<div class="btn csv">CSV</div>
		<div class="btn tsv">TSV</div>
		<div class="btn sql">SQL</div>
		<!-- <div class="btn xlsx">Excel Spreadsheet</div>
		<div class="btn pdf">PDF</div> -->
		<div id="g-btn" class="btn gdoc">Save to Google Drive</div>
	</div>
</div>

<header class="g16-header">
	<div class="g16-logo-container">
		<div class="g16-logo"><a href="/"></a></div>
		<div class="g16-text"><a href="/">Tally Counter</a></div>
	</div>
</header>

<div class="tally-input">
	<input class="item-input"
		placeholder="Enter an item, press enter/return..."
		autocomplete="off" autocorrect="off" type="text">
</div>

<div class="tally-list">
	<div class="ui">
		<div>Click on each item to decrease its count</div>
		<div class="hidden"><a href="#" class="export">Export...</a></div>
	</div>
	<div class="list"></div>
	<div class="ui">
		<div class="hidden"><a href="#" class="export">Export...</a></div>
	</div>
</div>

<form id="export_form" method="POST" target="export">
	<input id="export_encodedData" name="encodedData" type="hidden">
	<input id="export_btnClass" name="btnClass" type="hidden">
</form>
</body>
</html>
