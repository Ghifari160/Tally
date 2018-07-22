<?php
// Tally
// core-dbops Store REST API

require_once "api.class.php";

class Store_API extends API
{
  // TODO: error codes organization

  // @ref Store_API:BACKEND_VER
  const BACKEND_VER = "0.1";
  // @ref Store_API:ERROR:PAYLOAD:MISSING_META
  const ERROR_PAYLOAD_MISSING_META = 0xE2;
  // @ref Store_API:ERROR:PAYLOAD:WRONG_VERSION
  const ERROR_PAYLOAD_WRONG_VERSION = 0xE3;
  // @ref Store_API:ERROR:PAYLOAD:LIST:NOT_INTEGRAL
  const ERROR_PAYLOAD_LIST_NOT_INTEGRAL = 0xE4;
  // @ref Store_API:ERROR:CORE_DBOPS
  const ERROR_CORE_DBOPS = 0xE5;

  // @param   string    $request_method     HTTP method of the request.
  // @param   string    $request_backend    Backend to handle the request.
  // @param   array     $request_params     Array of request parameters.
  function __construct($request_method, $request_backend,
      $request_params = array())
  {
    parent::__construct(get_app_version(), ENG_VER, Store_API::BACKEND_VER,
        $request_method, $request_backend, $request_params);
  }

  protected function core_dbops_store($params)
  {
    $resp = new stdClass;

    // Decode JSON request input
    $obj = json_decode($params[0]);

    // Verify payload contains metadata
    $list_meta_missing = array();
    if($obj->request->list_data->version == NULL)
      array_push($list_meta_missing, "version");
    if($obj->request->list_data->namespace == NULL)
      array_push($list_meta_missing, "namespace");
    if($obj->request->list_data->tally == NULL)
      array_push($list_meta_missing, "tally");
    else if($obj->request->list_data->tally->version == NULL)
      array_push($list_meta_missing, "tally.version");

    if(count($list_meta_missing) == 1 && ($list_meta_missing[0] == "tally"
        || $list_meta_missing[0] == "tally.version"))
    {}
    else if(count($list_meta_missing) > 1 || (count($list_meta_missing) == 1
        && $list_meta_missing[0] != "tally"
        && $list_meta_missing[0] != "tally.version"))
    {
      $list_meta_missing_str = "\'".implode("\',\'", $list_meta_missing)."\'";
      return array($list_meta_missing_str,
          Store_API::ERROR_PAYLOAD_MISSING_META);
    }

    // Parse list metadata
    $list_meta = new stdClass;
    $list_meta->version = $obj->request->list_data->version;
    $list_meta->namespace = $obj->request->list_data->namespace;
    $list_meta->tally = $obj->request->list_data->tally;
    // Set the default list name
    $list_meta->name = APP_NAME;

    // Verify that the payload is valid by checking namespace and version
    // metadata
    if($list_meta->namespace != "com.ghifari160.tally.list.json" ||
        compare_versions($list_meta->version, "2.0") < 0)
    {
      return array($list_meta->namespace."@".$list_meta->version,
          Store_API::ERROR_PAYLOAD_WRONG_VERSION);
    }

    // Parse list data
    $list_data = $obj->request->list_data->list;

    // Verify the integrity of the list
    for($i = 0; $i < count($list_data); $i++)
    {
      $data = $list_data[$i];

      if($data->identifier == NULL || $data->value == NULL)
      {
        return array($data->identifier.":".$data->value,
            Store_API::ERROR_PAYLOAD_LIST_NOT_INTEGRAL);
      }
      // Parse name metadata
      else if($data->identifier == "com.ghifari160.tally.name")
        $list_meta->name = $data->value;
    }

    $id = tally_generate_id();

    // Build DBOps instruction object
    $cs = array();

    $c = new CORE_DBOPS_COLUMN("id", CORE_DBOPS_COLUMN::TYPE_VARCHAR, $id);
    array_push($cs, $c);

    $c = new CORE_DBOPS_COLUMN("list_name", CORE_DBOPS_COLUMN::TYPE_TEXT,
        $list_meta->name);
    array_push($cs, $c);

    $c = new CORE_DBOPS_COLUMN("list_content", CORE_DBOPS_COLUMN::TYPE_TEXT,
        base64_encode(json_encode($obj->request->list_data)));
    array_push($cs, $c);

    $op = new CORE_DBOPS_OPERATION(OPT_DB_TBLPREFIX."lists", $cs,
        CORE_DBOPS_OPERATION::MODE_INSERT);

    // Attempt to store the list in the database
    $stat = tally_dbops_execute($op);

    // Handle errors
    if($stat instanceof ERROR)
    {
      // Build error string
      $e_str = "(".$stat->error_code.") @ ".$stat->module." --> "
              .$stat->error_message;

      // core-dbops SQL error handler
      if($stat->module == "core-dbops" && $stat->core_dbops->error != NULL)
        $e_str .= " (".$stat->core_dbops->errno.") ".$stat->core_dbops->error;

      // Verbose error reporting for debug mode
      if(G16_DEBUG)
        $e_str = json_encode($stat);

      return array($e_str, Store_API::ERROR_CORE_DBOPS);
    }

    $resp->id = $id;
    $resp->list_name = $list_meta->name;
    $resp->method = $this->request_method;

    return array($resp, API::OK);
  }

  // Handle backend API response
  // @internal
  // @param   string    $response   Response message.
  // @param   int       $code       Response code.
  protected function _backend_response($response, $code)
  {
    $resp = new stdClass;

    // Parse known error code
    switch($code)
    {
      case Store_API::ERROR_PAYLOAD_MISSING_META:
        $resp->error_code = $code;
        $resp->error_message = "Payload missing metadata: ".$response;
        break;

      case Store_API::ERROR_PAYLOAD_WRONG_VERSION:
        $resp->error_code = $code;
        $resp->error_message = "Payload version is wrong: ".$response;
        break;

      case Store_API::ERROR_PAYLOAD_LIST_NOT_INTEGRAL:
        $resp->error_code = $code;
        $resp->error_message = "Payload list is not intact: ".$response;
        break;

      case Store_API::ERROR_CORE_DBOPS:
        $resp->error_code = $code;
        $resp->error_message = "core-dbops error: ".$response;
        break;

      // If the error code is unknown, return the response object
      default:
        $resp = $response;
    }

    return $resp;
  }
}
?>
