<?php
define('APP_VERSION', 'v0.1');
define('APP_COMMIT', '{APP_COMMIT}'); // Reflects previous commit
define('APP_DEV', true);

define('APP_STATIC_ASSETS', '/static/');
define('G16_COMMON_ASSETS', '//assets.ghifari160.com/assets/');

if(APP_DEV)
	define('GS_BUCKET', 'tally-dev.appspot.com');
else
	define('GS_BUCKET', 'tally.appspot.com');
?>
