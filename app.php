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
  enqueue_script("JSZip", g16_asset_uri("", "jszip.js", "jszip/dist"), "3.1.5");
  enqueue_script("core-excel", g16_asset_uri("", "core-excel.bundle.js", "core-excel"), "0.1");
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

// ===========================
// = BEGIN MODULE CORE-DBOPS =
// ===========================

class CORE_DBOPS
{
  // @ref:CORE_DBOPS:MODE:ERROR
  const MODE_ERROR = 0x00;
  // @ref:CORE_DBOPS:MODE:SUCCESS
  const MODE_SUCCESS = 0x01;

  public $operation_mode;

  // @param         ref:CORE_DBOPS_OPERATION:MODE   $opmode        Operation
  // mode.
  // @param         ref:mysqli_result               $mysqli:1      MySQLi
  // result object.
  // @param         ref:mysqli_stmt                 $mysqli:2      MySQLi
  // statement object.
  // @param         string                          $query         MySQL query
  // string.
  // @param:opt     Array                           $inputs        MySQL
  // inputs.
  // @param:opt     ref:CORE_DBOPS:MODE             $mode          Construction
  // mode.
  function __construct($opmode, &$mysqli, $query, $inputs = array(),
      $mode = CORE_DBOPS::MODE_ERROR)
  {
    $this->operation_mode = $opmode;
    // If constructed in an error
    if($mode == CORE_DBOPS::MODE_ERROR)
    {
      // Store MySQL error
      $this->connect_errno = $mysqli->connect_errno;
      $this->connect_error = $mysqli->connect_error;

      $this->errno = $mysqli->errno;
      $this->error = $mysqli->error;
      $this->error_list = $mysqli->error_list;
      $this->sqlstate = $mysqli->sqlstate;

      // Store query and inputs
      $this->query = $query;
      $this->inputs = $inputs;
    }
    // If constructed in a success
    else
    {
      // Initiate property
      $this->mysqli_result = new stdClass;
      $this->mysqli_stmt = new stdClass;

      // Store mysqli_result
      if($mysqli instanceof mysqli_result)
        $this->mysqli_result = &$mysqli;

      // Store mysqli_stmt
      if($mysqli instanceof mysqli_stmt)
        $this->mysqli_stmt = &$mysqli;
    }
  }
}

class CORE_DBOPS_SUCCESS extends SUCCESS
{
  public $core_dbops;

  // @param         ref:CORE_DBOPS_OPERATION:MODE   $opmode         Operation
  // mode.
  // @param         ref:mysqli_result               $mysqli:1       MySQLi
  // result object.
  // @param         ref:mysqli_stmt                 $mysqli:2       MySQLi
  // statement object.
  // @param         string                          $query          MySQL query
  // string.
  // @param:opt     Array                           $inputs         MySQL
  // inputs.
  // @param:opt     string                          $module         Execution
  // module.
  function __construct($opmode, &$mysqli, $query, $inputs = array(),
      $module = "core-dbops")
  {
    parent::__construct($module);
    $this->core_dbops = new CORE_DBOPS($opmode, $mysqli, $query, $inputs,
        CORE_DBOPS::MODE_SUCCESS);
  }
}

class CORE_DBOPS_ERROR extends ERROR
{
  // @ref:CORE_DBOPS_ERROR:ERROR:CONNECTION
  const ERROR_CONNECTION = 0xD0;
  // @ref:CORE_DBOPS_ERROR:ERROR:SQL
  const ERROR_SQL = 0xD1;

  public $core_dbops;

  // @param         ref:CORE_DBOPS_OPERATION:MODE   $operation_mode     Operation
  // mode.
  // @param         ref:CORE_DBOPS_ERROR:ERROR      $error_code         Error
  // code.
  // @param         ref:mysqli_result               $mysqli:1           MySQLi
  // result object.
  // @param         ref:mysqli_stmt                 $mysqli:2           MySQLi
  // statement object.
  // @param         string                          $query              MySQL
  // query string.
  // @param:opt     Array                           $inputs             MySQL
  // inputs.
  // @param:opt     string                          $module             Execution
  // module.
  // @param:opt     Array                           $attached_errors    Errors
  // caused by this execution.
  function __construct($operation_mode, $error_code, $mysqli, $query,
      $inputs = array(), $module = "core-dbops", $attached_errors = array())
  {
    parent::__construct($error_code, $module, $attached_errors);
    $this->error_message = $this->__get_module_error_message($error_code);
    $this->core_dbops = new CORE_DBOPS($operation_mode, $mysqli, $query,
        $inputs);
  }

  private function __get_module_error_message($error_code)
  {
    switch($error_code)
    {
      case CORE_DBOPS_ERROR::ERROR_CONNECTION:
        return "Connection error.";
        break;

      case CORE_DBOPS_ERROR::ERROR_SQL:
        return "SQL error.";
        break;
    }
  }
}

class CORE_DBOPS_COLUMN
{
  // @ref:CORE_DBOPS_COLUMN:TYPE:VARCHAR
  const TYPE_VARCHAR = "VARCHAR";
  // @ref:CORE_DBOPS_COLUMN:TYPE:TEXT
  const TYPE_TEXT = "TEXT";

  // @ref:CORE_DBOPS_COLUMN:TYPE:TINYINT
  const TYPE_TINYINT = "TINYINT";
  // @ref:CORE_DBOPS_COLUMN:TYPE:SMALLINT
  const TYPE_SMALLINT = "SMALLINT";
  // @ref:CORE_DBOPS_COLUMN:TYPE:MEDIUMINT
  const TYPE_MEDIUMINT = "MEDIUMINT";
  // @ref:CORE_DBOPS_COLUMN:TYPE:INT
  const TYPE_INT = "INT";
  // @ref:CORE_DBOPS_COLUMN:TYPE:BIGINT
  const TYPE_BIGINT = "BIGINT";

  public $column_name;
  public $column_type;
  public $column_length;

  public $column_primary;
  public $column_foreign;

  public $column_value;

  public $update_key;

  // @param         string                        $name         Column name.
  // @param         ref:CORE_DBOPS_COLUMN:TYPE    $type         Column type.
  // @param:opt     string                        $value:1      [ref:string]
  // value.
  // @param:opt     int                           $value:2      [ref:int]
  // value.
  // @param:opt     int                           $length       Column length.
  // @param:opt     bool                          $primary      Is column a
  // primary key.
  // @param:opt     bool                          $upkey        Is column an
  // update key.
  // @param:opt     Array                         $forkey       Foreign table
  // and key referenced by this column. The array order is table then key.
  function __construct($name, $type, $value = NULL, $length = 0,
      $primary = false, $upkey = false, $forkey = array())
  {
    $this->column_name = $name;
    $this->column_type = $type;
    $this->column_length = $length;

    $this->column_value = $value;

    $this->column_primary = $primary;
    $this->column_foreign = $forkey;

    $this->update_key = $upkey;
  }
}

class CORE_DBOPS_OPERATION
{
  // @ref:CORE_DBOPS_OPERATION:MODE:DROP
  const MODE_DROP = 0x00;
  // @ref:CORE_DBOPS_OPERATION:MODE:CREATE
  const MODE_CREATE = 0x01;
  // @ref:CORE_DBOPS_OPERATION:MODE:ALTER
  // const MODE_ALTER = 0x02;
  // @ref:CORE_DBOPS_OPERATION:MODE:VIEW
  const MODE_VIEW = 0x03;
  // @ref:CORE_DBOPS_OPERATION:MODE:TRUNCATE
  const MODE_TRUNCATE = 0x04;

  // @ref:CORE_DBOPS_OPERATION:MODE:DELETE
  const MODE_DELETE = 0x10;
  // @ref:CORE_DBOPS_OPERATION:MODE:INSERT
  const MODE_INSERT = 0x11;
  // @ref:CORE_DBOPS_OPERATION:MODE:UPDATE
  const MODE_UPDATE = 0x12;
  // @ref:CORE_DBOPS_OPERATION:MODE:SELECT
  const MODE_SELECT = 0x13;

  public $operation_mode;

  public $table_name;
  public $table_engine;
  public $table_columns;

  public $operation_sql_foreign_checks;

  // Backwards compatibility
  public $columns;

  // @param         string                            $name         Table name.
  // @param         ref:CORE_DBOPS_COLUMN             $columns      Table
  // columns.
  // @param         ref:CORE_DBOPS_OPERATION:MODE     $mode         Operation
  // mode.
  // @param:opt     string                            $engine       Table
  // storage engine.
  // @param:opt     bool                              $fc           Enable
  // foreign checks.
  function __construct($name, $columns, $mode, $engine = "INNODB", $fc = true)
  {
    $this->operation_mode = $mode;

    $this->table_name = $name;
    $this->table_engine = $engine;
    $this->table_columns = $columns;

    $this->operation_sql_foreign_checks = $fc;

    // Backwards compatibility
    $this->columns = $columns;
  }
}

// Generates unique ID using microtime() as seed and a Mersenne Twister RNG
// @param       int         $length       Length of the unique ID.
// @return      string                    Generated unique ID.
function tally_generate_id($length = 32)
{
  $dictionary = "0123456789"
               ."ABCDEFGHIJKLMNOPQRSTUVWXYZ"
               ."abcdefghijklmnopqrstuvwxyz";

  $rand = "";

  for($i = 0; $i < $length; $i++)
  {
    $rand .= $dictionary[(mt_rand() * tally_format_asdouble_microtime())
        % strlen($dictionary)];
  }

  return $rand;
}

// Formats microtime in a double format without losing precision
// @return      string                    microtime
function tally_format_asdouble_microtime()
{
  $mt = microtime();

  $e = explode(" ", $mt);

  return $e[1].substr($e[0], 1);
}

// Executes DBOps instruction
// @param         ref:CORE_DBOPS_OPERATION      $operation      DBOps operation
// object.
// @return:1      ref:CORE_DBOPS_ERROR                          Error object.
// @return:2      ref:CORE_IAM_SUCCESS                          Success object.
function tally_dbops_execute($operation)
{
  $ret = new stdClass;

  // Attempt to connect to MySQL server
  g16_create_dbConn($conn);

  // Exit if unable to connect to MySQL server
  if($conn instanceof ERROR || $conn->connect_errno != 0)
  {
    $aerrs = array();
    if($conn instanceof ERROR)
      array_push($aerrs, $conn);

    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_CONNECTION, $conn, "", array(), "core-dbops",
        $aerrs);

    if(G16_DEBUG)
      var_dump($ret);

    return $ret;
  }

  $opmode = $operation->operation_mode;

  // Table operation
  if($opmode == CORE_DBOPS_OPERATION::MODE_DROP)
    $ret = tally_drop_table($operation, $conn);
  else if($opmode == CORE_DBOPS_OPERATION::MODE_CREATE)
    $ret = tally_create_table($operation, $conn);
  else if($opmode == CORE_DBOPS_OPERATION::MODE_VIEW)
    $ret = tally_view_table($operation, $conn);
  else if($opmode == CORE_DBOPS_OPERATION::MODE_TRUNCATE)
    $ret = tally_truncate_table($operation, $conn);

  // Data operation
  else if($opmode == CORE_DBOPS_OPERATION::MODE_DELETE)
    $ret = tally_delete_table($operation, $conn);
  else if($opmode == CORE_DBOPS_OPERATION::MODE_INSERT)
    $ret = tally_insert_table($operation, $conn);
  else if($opmode == CORE_DBOPS_OPERATION::MODE_UPDATE)
    $ret = tally_update_table($operation, $conn);
  else if($opmode == CORE_DBOPS_OPERATION::MODE_SELECT)
    $ret = tally_select_table($operation, $conn);

  // Close connection to MySQL server
  $conn->close();

  return $ret;
}

// Deletes database table
// @internal      true
// @param         ref:CORE_DBOPS_OPERATION    $operation    Operation object.
// @param:opt     ref:mysqli                  $conn         MySQLi connection
// object.
// @return        ref:CORE_DBOPS_ERROR                      Error object.
// @return        ref:CORE_DBOPS_SUCCESS                    Success object.
function tally_drop_table($operation, $conn = NULL)
{
  $close_conn = true;
  $ret = new stdClass;

  // Create connection if not specified
  if($conn == NULL)
    g16_create_dbConn($conn);
  // Unflag specified connection
  else
    $close_conn = false;

  // Exit if unable to connect to MySQL server
  if($conn instanceof ERROR || $conn->connect_errno != 0)
  {
    $aerrs = array();
    // Store $conn as an attached error if it is an instance of ERROR
    if($conn instanceof ERROR)
      array_push($aerrs, $conn);

    // Generate error object
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_CONNECTION, $conn, "", array(), "core-dbops",
        $aerrs);

    // Print the error object if in debug mode
    if(G16_DEBUG)
      var_dump($ret);

    return $ret;
  }

  // Build SQL query string
  $sql = "DROP TABLE ".$operation->table_name;

  // Execute the query
  $stat = $conn->query($sql);

  if(!$stat)
  {
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_SQL, $conn, $sql);
  }
  else
    $ret = new CORE_DBOPS_SUCCESS($operation->operation_mode, $conn, $sql);

  // Close flagged connection
  if($close_conn)
    $conn->close();

  // Print the error object if on debug mode
  if(!$stat && G16_DEBUG)
    var_dump($ret);

  return $ret;
}

// @param         ref:CORE_DBOPS_OPERATION    $operation    Operation object.
// @param:opt     ref:mysqli                  $conn         MySQLi connection
// object.
// @return:1      ref:CORE_DBOPS_ERROR                      Error object.
// @return:2      ref:CORE_DBOPS_SUCCESS                    Success object.
function tally_create_table($operation, $conn = NULL)
{
  $close_conn = true;
  $ret = new stdClass;

  // Create connection if not specified
  if($conn == NULL)
    g16_create_dbConn($conn);
  else
    $close_conn = false;

  // Exit if unable to connect to MySQL server
  if($conn instanceof ERROR || $conn->connect_errno != 0)
  {
    $aerrs = array();
    if($conn instanceof ERROR)
      array_push($aerrs, $conn);

    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_CONNECTION, $conn, "", array(), "core-dbops",
        $aerrs);

    if(G16_DEBUG)
      var_dump($ret);

    return $ret;
  }

  $sql_inner_ops = array();
  $sql_add_ops = array();
  $sql_inner = "";

  // Build inner SQL query strings
  $columns = $operation->table_columns;
  for($i = 0; $i < count($columns); $i++)
  {
    $column = $columns[$i];

    $sql_inner = "\t".$column->column_name." ".$column->column_type;

    if($column->column_length > 0)
      $sql_inner .= "(".$column->column_length.")";

    if($column->column_primary)
      $sql_inner .= " PRIMARY KEY";

    array_push($sql_inner_ops, $sql_inner);

    // Handle foreign keys
    if(count($column->column_foreign) > 0)
    {
      $sql_inner = "FOREIGN KEY (".$column->column_name.") REFERENCES "
                  .$column->column_foreign[0]."(".$column->column_foreign[1]
                  .")";
      array_push($sql_add_ops, $sql_inner);
    }
  }

  $sql_inner_ops = array_merge($sql_inner_ops, $sql_add_ops);

  // Build SQL query string
  $sql = "CREATE TABLE ".$operation->table_name." (\n"
        .implode(",\n", $sql_inner_ops)."\n"
        .") ENGINE = ".$operation->table_engine;

  $stat = $conn->query($sql);

  if(!$stat)
  {
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_SQL, $conn, $sql);
  }
  else
    $ret = new CORE_DBOPS_SUCCESS($operation->operation_mode, $conn, $sql);

  // Close unspecified connection
  if($close_conn)
    $conn->close();

  if(!$stat && G16_DEBUG)
    var_dump($ret);

  return $ret;
}

// Views a database table structure
// @param         ref:CORE_DBOPS_OPERATION      $operation      Operation
// object.
// @param:opt     ref:mysqli                    $conn           MySQLi
// connection object.
// @return:1      ref:CORE_DBOPS_ERROR                          Error object.
// @return:2      ref:CORE_DBOPS_SUCCESS                        Success object.
function tally_view_table($operation, $conn = NULL)
{
  $close_conn = true;
  $ret = new stdClass;

  // Create connection to MySQL server if unspecified
  if($conn == NULL)
    g16_create_dbConn($conn);
  // Unflag specified connection
  else
    $close_conn = false;

  // Exit if unable to connect to MySQL server
  if($conn instanceof ERROR || $conn->connect_errno != 0)
  {
    $aerrs = array();
    // Store $conn as an attached error if it is an instance of ERROR
    if($conn instanceof ERROR)
      array_push($aerrs, $conn);

    // Generate error object
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_CONNECTION, $conn, "", array(), "core-dbops",
        $aerrs);

    // Print the error object if on debug mode
    if(G16_DEBUG)
      var_dump($ret);

    return $ret;
  }

  // Build SQL query string
  $sql = "DESCRIBE ".$operation->table_name;

  // Execute query
  $stat = $conn->query($sql);

  if(!$stat)
  {
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_SQL, $conn, $sql);
  }
  else
    $ret = new CORE_DBOPS_SUCCESS($operation->operation_mode, $stat, $sql);

  // Close flagged connection
  if($close_conn)
    $conn->close();

  // Print the error object if on debug mode
  if(!$stat && G16_DEBUG)
    var_dump($ret);

  return $ret;
}

// Truncates a database table
// @param         ref:CORE_DBOPS_OPERATION    $operation    Operation object.
// @param:opt     ref:mysqli                  $conn         MySQLi connection
// object.
// @return:1      ref:CORE_DBOPS_ERROR                      Error object.
// @return:2      ref:CORE_DBOPS_SUCCESS                    Success object.
function tally_truncate_table($operation, $conn = NULL)
{
  $close_conn = true;
  $ret = new stdClass;

  // Create connection to MySQL server if unspecified
  if($conn == NULL)
    g16_create_dbConn($conn);
  // Unflag connection
  else
    $close_conn = false;

  // Exit if unable to connect to MySQL server
  if($conn instanceof ERROR || $conn->connect_errno != 0)
  {
    $aerrs = array();
    // Store $conn as an attached error if it is an instance of ERROR
    if($conn instanceof ERROR)
      array_push($aerrs, $conn);

    // Generate error object
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_CONNECTION, $conn, "", array(), "core-dbops",
        $aerrs);

    // Print the error object if on debug mode
    if(G16_DEBUG)
      var_dump($ret);

    return $ret;
  }

  // Generate SQL query
  $sql = "TRUNCATE ".$operation->table_name.";\n";

  // Disable foreign checks if opted
  if(!$operation->operation_sql_foreign_checks)
  {
    $sql = "SET FOREIGN_KEY_CHECKS = 0;\n".$sql
          ."SET FOREIGN_KEY_CHECKS = 1";
  }

  // Execute the query
  $stat = $conn->multi_query($sql);

  if(!$stat)
  {
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_SQL, $conn, $sql);
  }
  else
    $ret = new CORE_DBOPS_SUCCESS($operation->operation_mode, $conn, $sql);

  // Close flagged connection
  if($close_conn)
    $conn->close();

  // Print the error object if on debug mode
  if(!$stat && G16_DEBUG)
    var_dump($ret);

  return $ret;
}

// Deletes data from a database table
// @param         ref:CORE_DBOPS_OPERATION      $operation      Operation
// object.
// @param:opt     ref:mysqli                    $conn           MySQLi
// connection object.
// @return:1      ref:CORE_DBOPS_ERROR                          Error ob.ect.
// @return:2      ref:CORE_DBOPS_SUCCESS                        Success object.
function tally_delete_table($operation, $conn = NULL)
{
  $close_conn = true;
  $ret = new stdClass;
  $stat = false;

  // Create connection if unspecified
  if($conn == NULL)
    g16_create_dbConn($conn);
  // Unflag specified connection
  else
    $close_conn = false;

  // Exit if unable to connect to MySQL server
  if($conn instanceof ERROR || $conn->connect_errno != 0)
  {
    $aerrs = array();
    // Store $conn as an attached error if it is an instance of ERROR
    if($conn instanceof ERROR)
      array_push($aerrs, $conn);

    // Generate error object
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_CONNECTION, $conn, "", array(), "core-dbops",
        $aerrs);

    // Print the error object if on debug mode
    if(G16_DEBUG)
      var_dump($ret);

    return $ret;
  }

  $sql_inner_ops = array();
  $sql_types = array();
  $inputs = array();
  $type = "";

  $columns = $operation->table_columns;
  for($i = 0; $i < count($columns); $i++)
  {
    $column = $columns[$i];

    // Convert MySQL types to PHP types
    switch(strtoupper($column->column_type))
    {
      case "VARCHAR":
      case "TEXT":
        $type = "s";
        break;

      case "TINYINT":
      case "SMALLINT":
      case "MEDIUMINT":
      case "INT":
      case "BIGINT":
        $type = "s";
        break;
    }

    array_push($sql_inner_ops, $column->column_name."=?");
    array_push($sql_types, $type);
    array_push($inputs, $column->column_value);
  }

  // Build SQL query string
  $sql = "DELETE FROM ".$operation->table_name." WHERE "
        .implode(" AND ", $sql_inner_ops);

  // Prepare statement
  $stmt = $conn->prepare($sql);

  // Exit if unable to prepare statement
  if(!$stmt)
  {
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_SQL, $conn, $sql);
  }
  else
  {
    // Build parameters array
    $sql_inner_ops = array();

    $type = implode("", $sql_types);
    $sql_inner_ops[] = &$type;

    for($i = 0; $i < count($inputs); $i++)
      $sql_inner_ops[] = &$inputs[$i];

    // Dynamically call $stmt->bind_param()
    call_user_func_array(array($stmt, "bind_param"), $sql_inner_ops);

    // Exit if unable to bind parameters
    if($stmt->errno != 0)
    {
      $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
          CORE_DBOPS_ERROR::ERROR_SQL, $stmt, $sql, $inputs);
    }
    else
    {
      // Execute the prepared statement
      $stat = $stmt->execute();

      if(!$stat)
      {
        $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
            CORE_DBOPS_ERROR::ERROR_SQL, $stmt, $sql, $inputs);
      }
      else
        $ret = new CORE_DBOPS_SUCCESS($operation->operation_mode, $stmt, $sql,
            $inputs);
    }
  }

  // Close flagged connection
  if($close_conn)
    $conn->close();

  // Print the error object if on debug mode
  if(!$stat && G16_DEBUG)
    var_dump($ret);

  return $ret;
}

// Inserts into a database table
// @param         ref:CORE_DBOPS_OPERATION      $operation      Operation
// object.
// @param:opt     ref:mysqli                    $conn           MySQLi
// connection object.
// @return:1      ref:CORE_DBOPS_ERROR                          Error object.
// @return:2      ref:CORE_DBOPS_SUCCESS                        Success object.
function tally_insert_table($operation, $conn = NULL)
{
  $close_conn = true;
  $stat = false;
  $ret = new stdClass;

  // Create connection if unspecified
  if($conn == NULL)
    g16_create_dbConn($conn);
  // Unflag specified connection
  else
    $close_conn = false;

  // Exit if unable to connect to MySQL server
  if($conn instanceof ERROR || $conn->connect_errno != 0)
  {
    $aerrs = array();
    // Store $conn as an attached error if it is an instance of ERROR
    if($conn instanceof ERROR)
      array_push($aerrs, $conn);

    // Generate error object
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_CONNECTION, $conn, "", array(), "core-dbops",
        $aerrs);

    // Print the error object if on debug mode
    if(G16_DEBUG)
      var_dump($ret);

    return $ret;
  }

  $inputs = array();
  $sql_inner_ops = array();
  $sql_types = array();
  $type = "";

  $columns = $operation->table_columns;
  for($i = 0; $i < count($columns); $i++)
  {
    $column = $columns[$i];

    // Convert MySQL types to PHP types
    switch(strtoupper($column->column_type))
    {
      case "VARCHAR":
      case "TEXT":
        $type = "s";
        break;

      case "TINYINT":
      case "SMALLINT":
      case "MEDIUMINT":
      case "INT":
      case "BIGINT":
        $type = "s";
        break;
    }

    array_push($sql_inner_ops, $column->column_name);
    array_push($sql_types, $type);
    array_push($inputs, $column->column_value);
  }

  // Build statement types
  $type = implode("", $sql_types);

  // Build SQL query string
  $sql = "INSERT INTO ".$operation->table_name." ("
        .implode(",", $sql_inner_ops).") VALUES (";

  $sql_inner_ops = array();
  for($i = 0; $i < count($columns); $i++)
    array_push($sql_inner_ops, "?");

  $sql .= implode(",", $sql_inner_ops).")";

  // Prepare statement
  $stmt = $conn->prepare($sql);

  // Exit if unable to prepare statement
  if(!$stmt)
  {
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_SQL, $conn, $sql);
  }
  else
  {
    // Build parameters array
    $sql_inner_ops = array();
    $sql_inner_ops[] = &$type;
    for($i = 0; $i < count($inputs); $i++)
      $sql_inner_ops[] = &$inputs[$i];

    // Dynamically call $stmt->bind_param();
    call_user_func_array(array($stmt, "bind_param"), $sql_inner_ops);

    // Exit if unable to bind to prepared statement
    if($stmt->errno != 0)
    {
      $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
          CORE_DBOPS_ERROR::ERROR_SQL, $stmt, $sql, $inputs);
    }
    else
    {
      $stat = $stmt->execute();

      if(!$stat)
      {
        $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
            CORE_DBOPS_ERROR::ERROR_SQL, $stmt, $sql, $inputs);
      }
      else
      {
        $ret = new CORE_DBOPS_SUCCESS($operation->operation_mode, $stmt, $sql,
            $inputs);
      }
    }
  }

  // Close flagged connection
  if($close_conn)
    $conn->close();

  // Print the error object if on debug mode
  if(!$stat && G16_DEBUG)
    var_dump($ret);

  return $ret;
}

// Updates an entry in a database table
// @param         ref:CORE_DBOPS_OPERATION      $operation      Operation
// object.
// @param:opt     ref:mysqli                    $conn           MySQLi
// connection object.
// @return:1      ref:CORE_DBOPS_ERROR                          Error object.
// @return:2      ref:CORE_DBOPS_SUCCESS                        Success object.
function tally_update_table($operation, $conn = NULL)
{
  $close_conn = true;
  $stat = false;
  $ret = new stdClass;

  // Create connection if unspecified
  if($conn == NULL)
    g16_create_dbConn($conn);
  // Unflag specified connection
  else
    $close_conn = false;

  // Exit if unable to connect to MySQL server
  if($conn instanceof ERROR || $conn->connect_errno != 0)
  {
    $aerrs = array();
    // Store $conn as an attached error if it is an instance of ERROR
    if($conn instanceof ERROR)
      array_push($aerrs, $conn);

    // Generate error object
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_CONNECTION, $conn, "", array(), "core-dbops",
        $aerrs);

    // Print the error object if on debug mode
    if(!$stat && G16_DEBUG)
      var_dump($ret);

    return $ret;
  }

  $reusable = array();
  $types = array();
  $inputs = array();
  $type = "";

  // Scan for columns to update
  $columns = $operation->table_columns;
  for($i = 0; $i < count($columns); $i++)
  {
    $column = $columns[$i];

    // Convert MySQL types to PHP types
    switch(strtoupper($column->column_type))
    {
      case "VARCHAR":
      case "TEXT":
        $type = "s";
        break;

      case "TINYINT":
      case "SMALLINT":
      case "MEDIUMINT":
      case "INT":
      case "BIGINT":
        $type = "s";
        break;
    }

    if(!$column->update_key)
    {
      array_push($types, $type);
      array_push($reusable, $column->column_name." = ?");
      array_push($inputs, $column->column_value);
    }
  }

  // Build SQL query string
  $sql = "UPDATE ".$operation->table_name." SET "
        .implode(", ", $reusable)." WHERE ";

  // Scan for update keys
  $reusable = array();
  for($i = 0; $i < count($columns); $i++)
  {
    $column = $columns[$i];

    // Convert MySQL types to PHP types
    switch(strtoupper($column->column_type))
    {
      case "VARCHAR":
      case "TEXT":
        $type = "s";
        break;

      case "TINYINT":
      case "SMALLINT":
      case "MEDIUMINT":
      case "INT":
      case "BIGINT":
        $type = "s";
        break;
    }

    if($column->update_key)
    {
      array_push($types, $type);
      array_push($reusable, $column->column_name." = ?");
      array_push($inputs, $column->column_value);
    }
  }

  // Finish building query
  $sql .= implode(" AND ", $reusable);

  // Prepare statement
  $stmt = $conn->prepare($sql);

  // Exit if unable to prepare statement
  if($conn->errno != 0)
  {
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_SQL, $conn, $sql);
  }
  else
  {
    $reusable = array();

    // Prepare types parameter
    $type = implode("", $types);
    $reusable[] = &$type;

    // Prepare input parameters
    for($i = 0; $i < count($inputs); $i++)
      $reusable[] = &$inputs[$i];

    // Dynamically call $stmt->bind_param()
    call_user_func_array(array($stmt, "bind_param"), $reusable);

    // Exit if unable to bind parameters
    if($stmt->errno != 0)
    {
      $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
          CORE_DBOPS_ERROR::ERROR_SQL, $stmt, $sql, $inputs);
    }
    else
    {
      // Execute prepared statement
      $stat = $stmt->execute();

      if(!$stat)
      {
        $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
            CORE_DBOPS_ERROR::ERROR_SQL, $stmt, $sql, $inputs);
      }
      else
      {
        $ret = new CORE_DBOPS_SUCCESS($operation->operation_mode, $stat, $sql,
            $inputs);
      }
    }
  }

  // Close flagged connection
  if($close_conn)
    $conn->close();

  // Print the error object if on debug mode
  if(!$stat && G16_DEBUG)
    var_dump($ret);

  return $ret;
}

function tally_select_table($operation, $conn = NULL)
{
  $close_conn = true;
  $stat = false;
  $ret = new stdClass;

  // Create connection if unspecified
  if($conn == NULL)
    g16_create_dbConn($conn);
  // Unflag specified connection
  else
    $close_conn = false;

  // Exit if unable to connect to MySQL server
  if($conn instanceof ERROR || $conn->connect_errno != 0)
  {
    $aerrs = array();
    // Store $conn as an attached error if it is an instance of ERROR
    if($conn instanceof ERROR)
      array_push($aerrs, $conn);

    // Generate error object
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_CONNECTION, $conn, "", array(), "core-dbops",
        $aerrs);

    // Print the error object if on debug mode
    if(!$stat && G16_DEBUG)
      var_dump($ret);

    return $ret;
  }

  $reusable = array();
  $inputs = array();
  $types = array();
  $type = "";

  // Scan for selectors
  $columns = $operation->table_columns;
  for($i = 0; $i < count($columns); $i++)
  {
    $column = $columns[$i];

    // Convert MySQL types to PHP types
    switch(strtoupper($column->column_type))
    {
      case "VARCHAR":
      case "TEXT":
        $type = "s";
        break;

      case "TINYINT":
      case "SMALLINT":
      case "MEDIUMINT":
      case "INT":
      case "BIGINT":
        $type = "s";
        break;
    }

    if(!$column->update_key)
      array_push($reusable, $column->column_name);
  }

  // Start building query
  $sql = "SELECT ".implode(", ", $reusable)." FROM ".$operation->table_name;

  // Scan for keys
  $reusable = array();
  for($i = 0; $i < count($columns); $i++)
  {
    $column = $columns[$i];

    // Convert MySQL types to PHP types
    switch(strtoupper($column->column_type))
    {
      case "VARCHAR":
      case "TEXT":
        $type = "s";
        break;

      case "TINYINT":
      case "SMALLINT":
      case "MEDIUMINT":
      case "INT":
      case "BIGINT":
        $type = "s";
        break;
    }

    if($column->update_key)
    {
      array_push($types, $type);
      array_push($reusable, $column->column_name." = ?");
      array_push($inputs, $column->column_value);
    }
  }

  // Finish building query
  $sql .= " WHERE ".implode(" AND ", $reusable);

  // Prepare statement
  $stmt = $conn->prepare($sql);

  // Exit if unable to prepare statement
  if($conn->errno != 0)
  {
    $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
        CORE_DBOPS_ERROR::ERROR_SQL, $conn, $sql);
  }
  else
  {
    $reusable = array();

    // Prepare types parameter
    $type = implode("", $types);
    $reusable[] = &$type;

    // Prepare input parameters
    for($i = 0; $i < count($inputs); $i++)
      $reusable[] = &$inputs[$i];

    // Dynamically call $stmt->bind_param()
    call_user_func_array(array($stmt, "bind_param"), $reusable);

    // Exit if unable to bind parameters
    if($stmt->errno != 0)
    {
      $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
          CORE_DBOPS_ERROR::ERROR_SQL, $stmt, $sql, $input);
    }
    else
    {
      // Execute prepared statement
      $stat = $stmt->execute();

      if(!$stat)
      {
        $ret = new CORE_DBOPS_ERROR($operation->operation_mode,
            CORE_DBOPS_ERROR::ERROR_SQL, $stmt, $sql, $input);
      }
      else
      {
        $ret = new CORE_DBOPS_SUCCESS($operation->operation_mode,
            $stmt->get_result(), $sql, $inputs);
      }
    }
  }

  // Close flagged connection
  if($close_conn)
    $conn->close();

  // Print the error object if on debug mode
  if(!$stat && G16_DEBUG)
    var_dump($ret);

  return $ret;
}

// ===========================
// =  END MODULE CORE-DBOPS  =
// ===========================
?>
