<?php
// %HOST%/core-dbops/store

require_once "store_api.class.php";
$path = cdo_get_path();
$api = new Store_API("POST", $path);
$api->execute();
// echo phpversion();
?>
