<?php
require_once "config.php";
$path = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php getAppName(); ?> by Ghifari160</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php getAppIcons(); ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js" defer></script>
<script src="/base64.js" defer></script>
<script src="/app.js" defer></script>
<script src="https://apis.google.com/js/platform.js" async defer></script>
<style>
#loading
{
	position: fixed;
	top: 0;
	left: 0;
	display: block;
	width: 100%;
	height: 100%;
	background: #333;
	z-index: 40;
}
</style>
</head>

<body>
<div id="loading"></div>
<noscript id="deferred-styles">
	<link rel="stylesheet" href="/g16.css">
	<link rel="stylesheet" href="/app.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i|Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i">
</noscript>
<div id="modal"></div>
<div id="modal-dialog"></div>

<header class="g16-header">
	<div class="g16-logo-container">
		<div class="g16-logo"><a href="/"></a></div>
		<div class="g16-text"><a href="/"><?php getAppName(); ?></a></div>
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
		<div><a href="#" class="config">Options...</a></div>
		<div class="hidden"><a href="#" class="export">Export...</a></div>
	</div>
	<div class="list"></div>
	<div class="ui">
		<div class="hidden"><a href="#" class="export">Export...</a></div>
	</div>
</div>

<footer class="tally-footer">
	<div class="copyright">&copy; <a href="https://github.com/Ghifari160">Ghifari160</a>, all rights reserved.</div>
	<div class="version"><?php getAppVersion(); ?></div>
</footer>

<form id="export_form" method="POST" target="export">
	<input id="export_encodedData" name="encodedData" type="hidden">
	<input id="export_btnClass" name="btnClass" type="hidden">
</form>

<script>
var loadDeferredStyles = function()
{
	var addStylesNode = document.getElementById("deferred-styles"),
		replacement = document.createElement("div"),
		loadingModal = document.getElementById("loading");

	replacement.innerHTML = addStylesNode.textContent;
	document.body.appendChild(replacement);
	addStylesNode.parentElement.removeChild(addStylesNode);

	// setTimeout(function()
	// {
	// 	removeLoadingModal(50);
	// }, 500);
};
// 	removeLoadingModal = function(duration)
// {
// 	var t0 = performance.now();
//
// 	var interval, count = Math.round(duration / 4), it,
// 		loadingModal = document.getElementById("loading");
//
// 	it = count;
// 	interval = window.setInterval(function()
// 	{
// 		if(it > 1)
// 			loadingModal.style.opacity = 1 / count * it;
// 		else
// 		{
// 			loadingModal.parentElement.removeChild(loadingModal);
// 			clearInterval(interval);
//
// 			var t1 = performance.now();
// 			console.log("removeLoadingModal(): ", (t1-t0), " ms");
// 		}
//
// 		it--;
// 	}, 1);
// };

var raf = requestAnimationFrame || mozRequestAnimationFrame ||
	webkitRequestAnimationFrame || msRequestAnimationFrame;
if (raf)
{
	raf(function()
	{
		window.setTimeout(loadDeferredStyles, 0);
	});
}
else
	window.addEventListener('load', loadDeferredStyles);
</script>
</body>
</html>
