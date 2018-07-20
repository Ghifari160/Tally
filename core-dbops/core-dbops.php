<?php
require_once "../app.php";

// Add additional functions here

// Get current path
function cdo_get_path()
{
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  $path = substr($path, 1);

  return $path;
}

$path = explode("/", cdo_get_path());
// Switch between API backends
switch($path[1])
{
  case "store":
    include "store.php";
    break;

  default:
    header("Location: /");
}
?>
