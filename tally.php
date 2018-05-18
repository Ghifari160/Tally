<?php require_once "app.php"; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Tally.Dev</title>
<?php
tally_meta();
recall_styles();
recall_scripts();
?>
</head>

<body>
<header class="app-header">
<?php tally_header(); ?>
</header>

<div class="app-body">
  <div class="tally-input">
    <input class="item-input" placeholder="Enter an item, press enter/return..."
        autocomplete="off" autocorrect="off" type="text">
  </div>

  <div class="tally-list">
    <div class="ui top">
      <div>Click on each item to decrease its count</div>
      <div>
        <ul>
          <li><a href="#" class="options">Options</a></li>
          <li class="hidden"><a href="#" class="export">Export</a></li>
        </ul>
      </div>
    </div>
    <div class="list"></div>
  </div>
</div>

<div class="app-footer">
<?php tally_footer(); ?>
</div>
</body>
</html>
