<?php
require_once "g16_core.php";

g16_init();

function tally_add_styles()
{
  enqueue_style("app.css", g16_asset_uri("css", "app.css"));
  enqueue_style("font-open-sans.css", "https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i");
  enqueue_style("font-roboto.css", "https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i");
}
add_action("enqueue_styles", "tally_add_styles");

function tally_add_scripts()
{
  enqueue_script("js-base64", g16_asset_uri("", "base64.js", "js-base64"));
  enqueue_script("punycode", g16_asset_uri("", "punycode.bundle.js", "punycode"), "2.1.0");
  enqueue_script("tally.js", g16_asset_uri("js", "tally.js"));
}
add_action("enqueue_scripts", "tally_add_scripts");

$footerMenu_options = array(
  array(
    "text" => "Home",
    "uri" => "/"
  ),
  array(
    "text" => "About",
    "uri" => "/about"
  )
  // array(
  //   "text" => "Report Bugs",
  //   "uri" => "/reports"
  // )
);

function tally_footer_menu()
{
  global $footerMenu_options;

  echo "\t<div class=\"menu\">"
      ."\t\t<ul>\n";

  for($i = 0; $i < count($footerMenu_options); $i++)
  {
    echo "\t\t\t<li><a href=\"".$footerMenu_options[$i]["uri"]."\">"
        .$footerMenu_options[$i]["text"]."</a></li>\n";
  }

  echo "\t\t</ul>\n"
      ."\t</div>";
}

function tally_footer()
{
  tally_footer_menu();

  echo "\t<div class=\"app-copyright\">";
  app_copyright(true);
  echo "</div>\n";

  echo "\t<div class=\"app-version\">";
  app_version();
  echo "</div>\n";
}

function tally_header()
{
  echo "\t<div class=\"app-logo\"></div>\n";
  echo "\t<div class=\"app-name\"><a href=\"/\">".APP_NAME."</a></div>\n";
}

function tally_meta()
{
  echo "<meta name=\"application-name\" content=\"".APP_NAME."\">\n";
  echo "<meta name=\"author\" content=\"".APP_AUTHOR_NAME."\">\n";
}
?>
