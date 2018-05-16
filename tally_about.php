<?php require_once "app.php"; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Tally.Dev</title>
<?php
recall_styles();
recall_scripts();
?>
</head>

<body>
<header class="app-header">
<?php tally_header(); ?>
</header>

<div class="app-body">
<h1>About <?php echo APP_NAME; ?></h1>
<?php
app_details(true);
?>
</div>

<div class="app-footer">
<?php tally_footer(); ?>
</div>
</body>
</html>
