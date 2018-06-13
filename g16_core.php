<?php
require_once "g16_config.php";

$APP_CONTRIBUTORS_NAMES = array("Davis Parks");
$APP_CONTRIBUTORS_URIS = array("#");

const ENG_PID = "160-713-612-7";
const ENG_INTERNAL_ID = "g16Engine";
const ENG_NAMESPACE = "com.ghifari160.g16.engine";

const ENG_NAME = "g16Engine";
const ENG_VER = "0.1";
const ENG_VER_BUILDHASH = "";
const ENG_COPYRIGHT = "© 2018 GHIFARI160, all rights reserved.";

const ENG_URI = "http://ghifari160.com";
const ENG_ISSUES_URI = "http://ghifari160.com";

const ENG_LICENSE = "MIT License";
const ENG_LICENSE_URI = "https://raw.githubusercontent.com/Ghifari160/g16Engine/master/LICENSE";

const ENG_SUPPORT_URI = "http://ghifari160.com";
const ENG_SUPPORT_EMAIL = "support@ghifari160.com";

const ENG_AUTHOR_NAME = "GHIFARI160";
const ENG_AUTHOR_URI = "http://ghifari160.com";
const ENG_AUTHOR_EMAIL = "business@ghifari160.com";

$ENG_CONTRIBUTORS_NAMES = array();
$ENG_CONTRIBUTORS_URIS = array();

// Hooks and actions array
$g16_hooks_array = array();
$g16_actions_array = array();

// Assets array
$g16_scripts_array = array();
$g16_styles_array = array();

// Compares two different version strings
// @param     string    $ver1         Version string #1.
// @param     string    $ver2         Version string #2.
// @return    int                     `-1` if version string #1 < version string
// #2, `0` if both strings are equal, `1` if version string #1 > version string
// #2.
function compare_versions($ver1, $ver2)
{
  // Explode version fragments
  $ver1_fragments = explode(".", $ver1);
  $ver2_fragments = explode(".", $ver2);

  // Return state
  $ret = 0;

  // Iterate the loop n number of times, where n is the length of the smaller
  // array
  if(count($ver_fragments) <= count($ver2_fragments))
    $loop_limit = count($ver1_fragments);
  else
    $loop_limit = count($ver2_fragments);

  // Compare each fragments independently
  $i = 0;
  for(; $i < $loop_limit; $i++)
  {
    if((int)$ver1_fragments[$i] > (int)$ver2_fragments[$i] && $ret == 0)
      $ret = 1;
    else if((int)$ver1_fragments[$i] < (int)$ver2_fragments[$i] && ret == 0)
      $ret = -1;
  }

  // Iterate the second phase loop n number of times, where n is the length of
  // the larger array
  if($i < count($ver1_fragments))
  {
    $loop_limit2 = count($ver1_fragments);
    $v = 1;
  }
  else if($i < count($ver2_fragments))
  {
    $loop_limit2 = count($ver2_fragments);
    $v = 2;
  }
  else
    $loop_limit2 = 0;

  // Second phase verification. The larger array, if it contains anything other
  // than 0, is generally the bigger version.
  // Keep the return state if it isn't 0
  for($j = $i; $j < $loop_limit2; $j++)
  {
    if($v == 1 && $ver1_fragments[$j] > 0 && $ret == 0)
      $ret = 1;
    else if($v == 2 && $ver2_fragments[$j] > 0 && $ret == 0)
      $ret = -1;
  }

  return $ret;
}

// Registers a valid hook
// @internal                          true
// @param     string    $hook         The hook id.
// @param     bool      $deprecated   ${opt} Deprecation flag of the hook.
function g16_register_hook($hook, $deprecated = false)
{
  global $g16_hooks_array;

  $hookExists = false;
  for($i = 0; $i < count($g16_hooks_array); $i++)
  {
    // If hook exists, reset the deprecation flag
    if($g16_hooks_array[$i]["hook_id"] == $hook)
    {
      $hookExists = true;
      $g16_hooks_array[$i]["deprecated"] = $deprecated;
    }
  }

  // Register the hook if it doesn't exists
  if(!$hookExists)
  {
    array_push($g16_hooks_array, array(
      "hook_id" => $hook,
      "deprecated" => $deprecated
    ));
  }
}

// Checks if hook is valid
// @param     string    $hook   The hook id.
// @return    bool              `true` if valid, `false` if invalid.
function is_hook_valid($hook)
{
  global $g16_hooks_array;

  $valid = false;

  // If the hook is registered, it's valid
  for($i = 0; $i < count($g16_hooks_array); $i++)
  {
    if($g16_hooks_array[$i]["hook_id"] == $hook)
      $valid = true;
  }

  return $valid;
}

// Checks if hook is deprecated
// @param     string      $hook     The hook id.
// @return    bool                  `true` if deprecated, `false` if not.
function is_hook_deprecated($hook)
{
  global $g16_hooks_array;

  $deprecated = false;
  for($i = 0; $i < count($g16_hooks_array); $i++)
  {
    if($g16_hooks_array[$i]["hook_id"] == $hook)
      $deprecated = $g16_hooks_array[$i][$deprecated];
  }

  return $deprecated;
}

// Registers a hook callback
// @param     string      $hook       The hook id.
// @param     string      $callback   The name of the callback function.
function add_action($hook, $callback)
{
  global $g16_actions_array;

  // Register the callback the hook is valid and the callback exists
  if(is_hook_valid($hook) && function_exists($callback))
  {
    array_push($g16_actions_array, array(
      "hook" => $hook,
      "callback" => $callback
    ));
  }
}

// Executes hook callbacks
// @param     string        $hook     The hook id.
function exec_actions($hook)
{
  global $g16_actions_array;

  // Exit if the hook is invalid
  if(!is_hook_valid($hook))
    return;

  // Go through all registered callbacks
  for($i = 0; $i < count($g16_actions_array); $i++)
  {
    // Call the callback function if the hook is valid and the function exists
    if($g16_actions_array[$i]["hook"] == $hook &&
        function_exists($g16_actions_array[$i]["callback"]))
    {
      // $g16_actions_array[$i]["callback"]();
      call_user_func($g16_actions_array[$i]["callback"]);
    }
  }
}

// Enqueues static asset while avoiding duplicates
// @internal                          true
// @param     ref:array   $array      Array of enqueued assets.
// @param     string      $asset_id   The ID of the asset.
// @param     string      $asset_uri  The URI of the asset.
// @param     string      $asset_ver  ${opt} The version of the asset. If it's
// unspecified, it will be assigned as ${APP_VER}.
function g16_enqueue_asset(&$array, $asset_id, $asset_uri, $asset_ver = APP_VER)
{
  // Asset object
  $asset = array(
    "id" => $asset_id,
    "uri" => $asset_uri,
    "version" => $asset_ver
  );

  $doesAssetExists = false;

  // Loop to avoid duplicate
  for($i = 0; $i < count($array); $i++)
  {
    if($array[$i]["id"] == $asset_id &&
        compare_versions($array[$i]["version"], $asset_ver) >= 0)
      $doesAssetExists = true;
    // Update the registered script
    else if($array[$i]["id"] == $asset_id &&
        compare_versions($array[$i]["version"], $asset_ver) < 0)
    {
      $array[$i]["uri"] = $asset_uri;
      $array[$i]["version"] = $asset_ver;
    }
  }

  // Enqueue the static asset
  if(!$doesAssetExists)
    array_push($array, $asset);
}

// Enqueues JavaScript assets while avoiding duplicates
// @param     string      $script_id   The ID of the script.
// @param     string      $script_uri  The URI of the script.
// @param     string      $script_ver  ${opt} The version of the script. If it's
// unspecified, it will be assigned as ${APP_VER}.
function enqueue_script($script_id, $script_uri, $script_ver = NULL)
{
  global $g16_scripts_array;
  g16_enqueue_asset($g16_scripts_array, $script_id, $script_uri, $script_ver);
}

// Enqueues CSS assets while avoiding duplicates
// @param     string      $style_id   The ID of the stylesheet.
// @param     string      $style_uri  The URI of the stylesheet.
// @param     string      $style_ver  ${opt} The version of the stylesheet. If
// it's unspecified, it will be assigned as ${APP_VER}.
function enqueue_style($style_id, $style_uri, $style_ver = NULL)
{
  global $g16_styles_array;
  g16_enqueue_asset($g16_styles_array, $style_id, $style_uri, $style_ver);
}

// Gets URI for a given file
// @param     string      $file       The path of the file.
// @return    string                  The URI of the given file
function g16_uri($file)
{
  $ret = $file;

  return $ret;
}

// Gets URI for a given static asset
// @param       string      $asset_type   The type of the asset. Valid types are
// `js` and `css`.
// @param       string      $asset_name   The name of the asset.
// @param       string      $library      The library of the asset. The default
// value is `root`.
// @return      string                    The URI of the asset.
function g16_asset_uri($asset_type, $asset_name, $library = "root")
{
  $path = "assets/";
  $path .= ($library == "root") ? $asset_type : $library;
  $path .= "/".$asset_name;

  return $path;
}

// Attaches alll JavaScript assets to the current page
function recall_scripts()
{
  global $g16_scripts_array;

  exec_actions("enqueue_scripts");

  $ech = "";
  for($i = 0; $i < count($g16_scripts_array); $i++)
    $ech .= "<script src=\"".$g16_scripts_array[$i]["uri"]."\"></script>\n";

  echo $ech;
}

// Attaches all CSS assets to the current page
function recall_styles()
{
  global $g16_styles_array;

  exec_actions("enqueue_styles");

  $ech = "";
  for($i = 0; $i < count($g16_styles_array); $i++)
  {
    $ech .= "<link rel=\"stylesheet\" href=\"".$g16_styles_array[$i]["uri"]
         ."\">\n";
  }

  echo $ech;
}

// Call custom footer menu
function app_footer_menu()
{
  exec_actions("footer_menu");
}

// Registers all JavaScript assets
// @internal  true
function g16_init_scripts()
{
  enqueue_script("jquery", g16_asset_uri("", "jquery.js", "jquery"), "3.3.1");
  enqueue_script("zip.js", g16_asset_uri("", "zip.js", "zip.js"), ENG_VER);
}

// Registers all CSS assets
// @internal  true
function g16_init_styles()
{
  enqueue_style("engine.css", g16_asset_uri("css", "engine.css"), ENG_VER);
  enqueue_style("dev.css", g16_asset_uri("css", "dev.css"), ENG_VER);
}

// Registers all valid hooks
// @internal  true
function g16_init_hooks()
{
  g16_register_hook("enqueue_scripts");
  g16_register_hook("enqueue_styles");
  g16_register_hook("footer_menu");
}

// Initiates the engine
function g16_init()
{
  g16_init_hooks();

  // Register all static assets
  add_action("enqueue_scripts", "g16_init_scripts");
  add_action("enqueue_styles", "g16_init_styles");

  if(G16_DEBUG)
  {
    echo "<!-- g16Engine v0.1. (c) GHIFARI160, all rights reserved. "
        ."Distributed under the MIT License. -->\n";
    echo "<!-- [g16Engine] Debug mode enabled. -->\n";
  }
}

// Formats PID
function pid_formatStr($str)
{
  // Preformats the string. Replace all makeshift spacers.
  $str = preg_replace("/[^\w]|_/", "", $str);

  // If the PID is not long enough, it is not valid
  if(strlen($str) < strlen("XXXXXXXXXX"))
    return NULL;

  // Format the PID into the proper format: domain-group-project-check
  $ret = "";
  for($i = 0; $i < strlen("XXXXXXXXXX"); $i++)
  {
    $ret .= substr($str, $i, 1);

    if($i == 2 || $i == 5 || $i == 8)
      $ret .= "-";
  }

  return $ret;
}

// Filters string
// @param   string      $str        The unfiltered string.
// @param   string      $param      The filter parameters. Valid parameters:
// `t` = replace tabs with spaces
// `g` = replace copyright marks (`©`) with plain text copyright marks (`(c)`)
// `c` = replace plain text copyright marks (`(c)`) with (`&copy;`)
// `a` = replace author name with author string
// `l` = replace license name with license string
// @return  string                  The filtered string.
function apply_filters($str, $param)
{
  $ret = $str;

  for($i = 0; $i < strlen($param); $i++)
  {
    $p = substr($param, $i, 1);

    switch($p)
    {
      case "t":
        $ret = str_replace("\t", "  ", $ret);
        break;

      case "g":
        $ret = str_replace("©", "(c)", $ret);
        break;

      case "c":
        $ret = str_replace("(c)", "&copy;", $ret);
        break;

      case "a":
        $ret = str_replace(APP_AUTHOR_NAME, get_app_author(), $ret);
        break;

      case "l":
        $ret = str_replace(APP_LICENSE, get_app_license(), $ret);
        break;
    }
  }

  return $ret;
}

// Gets app contributors
// @return  array     Array of contributor objects structure:
// ```
// array {
//  array {
//    "name" => contributor name,
//    "uri" => contributor uri
//  }
// }
// ```
function get_app_contributors()
{
  $ret = array();

  global $APP_CONTRIBUTORS_URIS, $APP_CONTRIBUTORS_NAMES;

  // Iterate the first phase loop n number of times, where n is the size of the
  // smaller array
  if(count($APP_CONTRIBUTORS_URIS) <= count($APP_CONTRIBUTORS_NAMES))
    $loop_limit = count($APP_CONTRIBUTORS_URIS);
  else if(count($APP_CONTRIBUTORS_URIS) >= count($APP_CONTRIBUTORS_NAMES))
    $loop_limit = count($APP_CONTRIBUTORS_NAMES);

  // Compile contributors array from the two arrays
  $i = 0;
  for(; $i < $loop_limit; $i++)
  {
    $o = array(
      "name" => $APP_CONTRIBUTORS_NAMES[$i],
      "uri" => $APP_CONTRIBUTORS_URIS[$i]
    );

    array_push($ret, $o);
  }

  // Append the contributors array with contributors with null URIs
  if(count($APP_CONTRIBUTORS_URIS) <= count($APP_CONTRIBUTORS_NAMES))
  {
    for($j = $i; $j < count($APP_CONTRIBUTORS_NAMES); $j++)
    {
      $o = array(
        "name" => $APP_CONTRIBUTORS_NAMES[$i],
        "uri" => NULL
      );

      array_push($ret, $o);
    }
  }

  // Prepend the author to the contributors array
  $o = array(
    "name" => APP_AUTHOR_NAME,
    "uri" => APP_AUTHOR_URI
  );
  array_unshift($ret, $o);

  return $ret;
}

// Gets app author string
// @return  string   The app author string.
function get_app_author()
{
  $ret = "<a href=\"".APP_AUTHOR_URI."\">".APP_AUTHOR_NAME."</a>";

  return $ret;
}

// Echoes app author string
function app_author()
{
  echo get_app_author();
}

// Gets app version string
// @return  string         The app version string.
function get_app_version()
{
  $ret = "v".APP_VER;
  $ret .= (OPT_USE_BUILDHASH) ? "-".APP_VER_BUILDHASH : "";

  return $ret;
}

// Echoes app version string
function app_version()
{
  echo get_app_version();
}

// Gets app license string
// @return  string  The license string.
function get_app_license()
{
  $ret = "Distributed under the <a href=\"".APP_LICENSE_URI."\">".APP_LICENSE
      ."</a>.";
  return $ret;
}

// Echoes app license string
function app_license()
{
  echo $ret;
}

// Gets app copyright declaration
// @param   bool    $isFooter   Set this to true if calling this from the app
// UI footer.
// @return  string              The app copyright declaration string.
function get_app_copyright($isFooter = false)
{
  $ret = APP_COPYRIGHT;
  $ret .= ($isFooter && OPT_SHOW_LICENSE_ON_FOOTER) ? " ".get_app_license() :
      "";

  return apply_filters($ret, "a");
}

// Echoes app copyright declaration
// @param   bool    $isFooter   Set this to true if calling this from the app
// UI footer.
function app_copyright($isFooter)
{
  echo get_app_copyright($isFooter);
}

// Echoes app details
// @param     bool      $showIdentifier   Sets the verbosity of the details.
// `true` = display the identifier methods
// `false` = don't display the identifier methods
function app_details($showIdentifier)
{
  $isG16 = false;

  // Check if the app is a g16 app by checking the namespace
  $ns_fragments = explode(".", APP_NAMESPACE);
  if($ns_fragments[0] == "com" && $ns_fragments[1] == "ghifari160")
    $isG16 = true;

  // If $showIdentifier is not set, set it to true if the app is a g16 app or
  // G16_DEBUG is set to true
  if(!isset($showIdentifier) && ($isG16 || G16_DEBUG))
    $showIdentifier = true;
  else if(!isset($showIdentifier))
    $showIdentifier = false;

  $ech = "<div class=\"app-details\">\n";

  // Show the identifier group if requested
  if($showIdentifier)
  {
    $ech .= "\t<div class=\"row\">\n"
           ."\t\t<div>PID</div>\n"
           ."\t\t<div>".pid_formatStr(APP_PID)."</div>\n"
           ."\t</div>\n"
           ."\t<div class=\"row\">\n"
           ."\t\t<div>Internal ID</div>\n"
           ."\t\t<div>".APP_INTERNAL_ID."</div>\n"
           ."\t</div>\n"
           ."\t<div class=\"row\">\n"
           ."\t\t<div>Namespace</div>\n"
           ."\t\t<div>".APP_NAMESPACE."</div>\n"
           ."\t</div>\n";
  }

  $ech .= "\t<div class=\"row\">\n"
         ."\t\t<div>Version</div>\n"
         ."\t\t<div>".APP_VER."</div>\n"
         ."\t</div>\n"
         ."\t<div class=\"row\">\n"
         ."\t\t<div>Author</div>\n"
         ."\t\t<div>".get_app_author()."</div>\n"
         ."\t</div>\n"
         ."\t<div class=\"row\">\n"
         ."\t\t<div>License</div>\n"
         ."\t\t<div><a href=\"".APP_LICENSE_URI."\">".APP_LICENSE
         ."</a></div>\n"
         ."\t</div>\n"
         ."\t<div class=\"row\">\n"
         ."\t\t<div>Support</div>\n"
         ."\t\t<div>\n"
         ."\t\t\t<ul>\n"
         ."\t\t\t\t<li><a href=\"".APP_SUPPORT_URI."\">Site</a></li>\n"
         ."\t\t\t\t<li><a href=\"mailto:".APP_SUPPORT_EMAIL
         ."\">Email</a></li>\n"
         ."\t\t\t</ul>\n"
         ."\t\t</div>\n"
         ."\t</div>\n"
         ."\t<div class=\"row\">\n"
         ."\t\t<div>Contributors</div>\n"
         ."\t\t<div>\n"
         ."\t\t\t<ul>\n";

  // Append contributors to the details
  $contributors = get_app_contributors();
  for($i = 0; $i < count($contributors); $i++)
  {
    $ech .= "\t\t\t\t<li><a href=\"".$contributors[$i]["uri"]."\">"
           .$contributors[$i]["name"]."</a></li>\n";
  }

  $ech .= "\t\t\t</ul>\n"
         ."\t\t</div>\n"
         ."\t</div>\n"
         ."</div>\n"
         ."<div class=\"app-info-footer\">".APP_COPYRIGHT."</div>\n";

  echo apply_filters($ech, "tgc");
}

// Echoes the CSS style templates to the output of engine functions
// @param   string    $function   An engine function with a CSS-formatted
// output.
// @param   bool      $scss       Sets the output mode.
// `true` = SCSS
// `false` = CSS
function g16_print_css($function, $scss = false)
{
  $ret = "";

  // Normalize function key to all lowercase
  $function = strtolower($function);

  // The SCSS variant of the function stylesheet is keyed
  // under [function]=>scss
  $function .= ($scss) ? "=>scss" : "";

  switch($function)
  {
    case "app_details":
      $ret = ".app-details {\n\tdisplay: table;\n}\n.app-details .row {\n\t"
            ."display: table-row;\n}\n.app-details .row div {\n\tdisplay: "
            ."table-cell;\n}\n.app-details .row div ul {\n\tlist-style: none;"
            ."\n\tmargin: 0;\n\tpadding: 0;\n}\n.app-details .row div ul li {"
            ."\n\tdisplay: inline-block;\n\tvertical-align: top;\n\tmargin: 0 "
            ."10px 0 0;\n\tpadding: 0 10px 0 0;\n\tborder-right: 2px solid "
            ."#666;\n}\n.app-details .row div ul li:last-child {\n\tmargin: "
            ."0;\n\tpadding: 0;\n\tborder: none;\n}\n.app-details .row div:"
            ."nth-child(odd) {\n\tpadding: 0 5px 0 0;\n\tfont-weight: bolder;"
            ."\n}\n.app-details .row div:nth-child(even) {\n\tpadding: 0 0 0 "
            ."5px;\n}\n";
      break;

    case "app_details=>scss":
      $ret = "\$app-info-space-between-cells: 10px;\n\$app-info-list-margin: 0"
            ." 10px 0 0;\n\$app-info-list-padding: 0 10px 0 0;\n\$app-info-"
            ."list-border-width: 2px;\n\$app-info-list-border-style: solid;\n"
            ."\$app-info-list-border-color: #666;\n.app-details\n{\n\tdisplay:"
            ." table;\n\t.row\n\t{\n\t\tdisplay: table-row;\n\t\tdiv\n\t\t{\n"
            ."\t\t\tdisplay: table-cell;\n\t\t\tul\n\t\t\t{\n\t\t\t\tlist-"
            ."style: none;\n\t\t\t\tmargin: 0;\n\t\t\t\tpadding: 0;\n\t\t\t\t"
            ."li\n\t\t\t\t{\n\t\t\t\t\tdisplay: inline-block;\n\t\t\t\t\t"
            ."vertical-align: top;\n\t\t\t\t\tmargin: \$app-info-list-margin;"
            ."\n\t\t\t\t\tpadding: \$app-info-list-padding;\n\t\t\t\t\tborder-"
            ."right: \$app-info-list-border-width\n\t\t\t\t\t\t\$app-info-list"
            ."-border-style \$app-info-list-border-color;\n\t\t\t\t}\n\t\t\t\t"
            ."li:last-child\n\t\t\t\t{\n\t\t\t\t\tmargin: 0;\n\t\t\t\t\t"
            ."padding: 0;\n\t\t\t\t\tborder: none;\n\t\t\t\t}\n\t\t\t}\n\t\t}"
            ."\n\t\tdiv:nth-child(odd)\n\t\t{\n\t\t\tpadding: 0 (\$app-info-"
            ."space-between-cells / 2) 0 0;\n\t\t\tfont-weight: bolder;\n\t\t}"
            ."\n\t\tdiv:nth-child(even)\n\t\t{\n\t\t\tpadding: 0 0 0 (\$app-"
            ."info-space-between-cells / 2);\n\t\t}\n\t}\n}\n";
      break;

    case "footer_menu":
      $ret = ".footer-menu ul {\n\tlist-style: none;\n\tmargin: 0;\n\tpadding:"
            ." 0;\n}\n.footer-menu ul li {\n\tdisplay: inline-block;\n\tmargin"
            .": 0 10px 0 0;\n\tpadding: 0 10px 0 0;\n\tborder-right: 1px solid"
            ." #666;\n}\n.footer-menu ul li:last-child {\n\tmargin: 0;\n\t"
            ."padding: 0;\n\tborder: none;\n}\n";
      break;

    case "footer_menu=>scss":
      $ret = "\$footer-menu-list-style: none;\n\$footer-menu-list-margin: 0;\n"
            ."\$footer-menu-list-padding: 0;\n\$footer-menu-list-child-display"
            .": inline-block;\n\$footer-menu-list-child-margin: 0 10px 0 0;\n"
            ."\$footer-menu-list-child-padding: 0 10px 0 0;\n\$footer-menu-"
            ."list-child-border-right: 1px solid #666;\n.footer-menu\n{\n\tul"
            ."\n\t{\n\t\tlist-style: \$footer-menu-list-style;\n\t\tmargin: "
            ."\$footer-menu-list-margin;\n\t\tpadding: \$footer-menu-list-"
            ."padding;\n\t\tli\n\t\t{\n\t\t\tdisplay: \$footer-menu-list-child"
            ."-display;\n\t\t\tmargin: \$footer-menu-list-child-margin;\n\t\t"
            ."\tpadding: \$footer-menu-list-child-padding;\n\t\t\tborder-right"
            .": \$footer-menu-list-child-border-right;\n\t\t}\n\t\tli:last-"
            ."child\n\t\t{\n\t\t\tmargin: 0;\n\t\t\tpadding: 0;\n\t\t\tborder:"
            ." none;\n\t\t}\n\t}\n}\n";
      break;

    case "g16_print_css":
      $ret = ".g16-print-css {\n\tbackground: #dedede;\n\tborder: 1px solid "
            ."#666;\n\tborder-radius: 3px;\n}\n";
      break;

    case "g16_print_css=>scss":
      $ret = "\$g16-print-css-background: #dedede;\n\$g16-print-css-border-"
            ."width: 1px;\n\$g16-print-css-border-style: solid;\n\$g16-print-"
            ."css-border-color: #666;\n\$g16-print-css-border-radius: 3px;\n."
            ."g16-print-css\n{\n\tbackground: \$g16-print-css-background;\n\t"
            ."border: \$g16-print-css-border-width \$g16-print-css-border-"
            ."style\n\t\t\$g16-print-css-border-color;\n\tborder-radius: \$g16"
            ."-print-css-border-radius;\n}\n";
      break;

    default:
      $ret = NULL;
      break;
  }

  echo "<pre class=\"g16-print-css\">\n".apply_filters($ret, "t")."</pre>\n";
}

// Creates database connection
// @internal  true
// @param     ref:class:mysqli  $conn   Reference to where the MySQLi object
// should be stored.
// @return    bool                      The connection status.
// `true` = successful connection
// `false` = connection error. Check $conn->connect_error
function g16_create_dbConn(&$conn)
{
  $conn = NULL;

  // Disable if the database module is not enabled.
  if(!OPT_USE_DATABASE)
  {
    // Create a dummy class for error checking
    $conn = new stdClass();
    $conn->connect_error = "Database connection is not enabled.";

    return false;
  }

  // Attempt to create a connection
  $conn = new mysqli(OPT_DB_HOSTNAME, OPT_DB_USERNAME, OPT_DB_PASSWORD,
      OPT_DB_DBNAME);

  if($conn->connect_error)
    return false;

  return true;
}

// Creates database table
// @param         string          $tblName      Table name. It will be appended
// to the prefix configured on `g16_config.php`.
// @param         string          $tblObjects   Table objects. Structure:
// ```
// [
//    [
//      "column" => <column name>,
//      "type" => <column type>,
//      "length" => <0=no maximum length|any int maximum>,
//      "primary" => <true|false>
//    ]
// ]
// ```
// Example:
// ```
// [
//    [
//        "column" => "id",
//        "type" => "varchar",
//        "length" => 32,
//        "primary" => true
//    ],
//    [
//        "column" => "user_id",
//        "type" => "varchar",
//        "length" => 32,
//        "primary" => false
//    ]
// ]
// ```
// @param:opt     class:mysqli    $conn         MySQLi object. If not
// specified, the object will be created and destroyed during table creation.
// It is recommended to specify a MySQLi object if using this function in a
// loop.
// @return        Array                         Table creation status.
// Structure:
// ```
// [
//    bool      "status",
//    int       "errno",
//    string    "error",
//    string[]  "error_list"
// ]
// ```
function g16_create_table($tblName, $tblObjects, $engine = NULL, $conn = NULL)
{
  // Destruction flag
  $closeConn = true;
  // Verify engine availability, replace with INNODB if not available
  if(($engine == NULL || !g16_isAvailable_dbEngine($engine))
      && g16_isAvailable_dbEngine("innodb"))
    $engine = "INNODB";
  // Create the connection if not specified
  if($conn == NULL)
    g16_create_dbConn($conn);
  // Disable connection destroying flag
  else
    $closeConn = false;
  // SQL query
  $query = "CREATE TABLE ".$conn->escape_string(OPT_DB_TBLPREFIX.$tblName)
          ." (\n";
  for($i = 0; $i < count($tblObjects); $i++)
  {
    $tblObj = $tblObjects[$i];
    // Verify that column and type exists and not empty
    if(isset($tblObj["column"]) && strlen($tblObj["column"]) > 0
        && isset($tblObj["type"]) && strlen($tblObj["type"]) > 0)
    {
      $query .= "\t".$conn->escape_string($tblObj["column"])." "
               .strtoupper($tblObj["type"]);
      // Specify the maximum length of the column if the length key exists and
      // the value is greater than zero.
      if(isset($tblObj["length"]) && $tblObj["length"] > 0)
        $query .= "(".$conn->escape_string($tblObj["length"]).")";
      // Set primary key if the key exists and the value is true
      if(isset($tblObj["primary"]) && $tblObj["primary"])
        $query .= " PRIMARY KEY";
      $query .= ",\n";
    }
  }
  // Strip the last "," and add a new line
  $query = substr($query, 0, strlen($query) - 2)."\n";
  $query .= ") ENGINE = ".$engine;
  // Execute the query
  $r = $conn->query($query);
  // Return stack
  $ret = array(
    "status" => $r,
    "errno" => $conn->errno,
    "error" => $conn->error,
    "error_list" => $conn->error_list,
  );
  // Destroy flagged connection.
  if($closeConn)
    $conn->close();
  return $ret;
}
// Creates database tables
// @param       Array       $tableObjects       Objects of tables to create.
// Structure:
// ```
// [
//  [
//    "name": <table name>,
//    "engine": <table engine>,
//    "columns":
//    [
//      @ref:func:g16_create_table
//    ]
//  ]
// ]
// ```
// Example:
// ```
// [
//  [
//    "name": "tbl1",
//    "engine": "innodb",
//    "columns":
//    [
//      [
//        "column": "id",
//        "type": "varchar",
//        "length": 32,
//        "primary": true
//      ],
//      [
//        "column": "uid",
//        "type": "text"
//      ]
//    ]
//  ],
//  [
//    "name": "tbl2",
//    "engine": "myisam",
//    "columns":
//    [
//      [
//        "column": "id",
//        "type": "varchar",
//        "length": 32,
//        "primary": true
//      ],
//      [
//        "column": "uid",
//        "type": "text"
//      ]
//    ]
//  ]
// ]
// ```
// @return      Array                           Creation status. Structure:
// ```
// [
//  bool      "connection_status",
//  Array     "table_statuses":
//  [
//    string    "table_name",
//    bool      "status",
//    int       "errno",
//    string    "error",
//    string[]  "error_list"
//  ]
// ]
// ```
function g16_create_tables($tableObjects)
{
  $ret = array();
  $statuses = array();
  $conn_status = g16_create_dbConn($conn);
  $ret = array(
    "connection_status" => $conn_status
  );
  // Exit if connection cannot be made
  if(!$conn_status)
    return $ret;
  // Attempts to create all table
  for($i = 0; $i < count($tableObjects); $i++)
  {
    $tbl = $tableObjects[$i];
    // Only execute if the table is named
    if(isset($tbl["name"]) && strlen($tbl["name"]) > 0)
    {
      // Set the default engine parameter
      if(!isset($tbl["engine"]))
        $eng = NULL;
      else
        $eng = $tbl["engine"];
      // Create the table
      $stat = g16_create_table($tbl["name"], $tbl["columns"], $eng, $conn);
      // Add table name to the returned value
      $stat_ret = array(
        "table_name" => $tbl["name"],
        "status" => $stat["status"],
        "errno" => $stat["errno"],
        "error" => $stat["error"],
        "error_list" => $stat["error_list"]
      );
      // Store the status to the statuses array
      array_push($statuses, $stat_ret);
    }
  }
  $ret["table_statuses"] = $statuses;
  return $ret;
}
// Checks database engine availability
// @param     string      $engine     The engine to check for.
// @return    bool                    Engine availability
function g16_isAvailable_dbEngine($engine)
{
  $ret = false;
  // Connect to the database
  $conn_stat = g16_create_dbConn($conn);
  if(!$conn_stat)
    return false;
  $r = $conn->query("SHOW ENGINES");
  // Exit if unsuccessful
  if($r->num_rows <= 0)
    return false;
  // Scan for engine
  while($rows = $r->fetch_assoc())
  {
    // If engine exists and supported, flag to return true
    if(!$ret && strtolower($rows["Engine"]) == strtolower($engine)
        && (strtolower($rows["Support"]) == "default"
        || strtolower($rows["Support"]) == "yes"))
      $ret = true;
  }
  return $ret;
}
?>
