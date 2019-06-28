<?php
// Tally
// core-dbops REST API

abstract class API
{
  private $tally_version;
  private $engine_version;
  private $backend_version;

  protected $request_method;
  protected $request_backend;
  protected $request_params;
  protected $request_input;

  // @ref API:OK
  const OK = 0x00;
  // @ref API:ERROR:METHOD_DOES_NOT_EXISTS
  const ERROR_METHOD_DOES_NOT_EXISTS = 0x03;

  // @param   string    $tally_version      Tally version.
  // @param   string    $engine_version     Engine version.
  // @param   string    $backend_version    API backend version.
  // @param   string    $request_method     HTTP method of the request.
  // @param   string    $request_backend    Backend to handle the request.
  // @param   array     $request_params     Array of request parameters.
  function __construct($tally_version, $engine_version, $backend_version,
      $request_method, $request_backend, $request_params = array())
  {
    // Set version metadata
    $this->tally_version = $tally_version;
    $this->engine_version = $engine_version;
    $this->backend_version = $backend_version;

    $this->request_method = $request_method;
    $this->request_backend = preg_replace("(\-|\/)", "_", $request_backend);
    $this->request_params = $request_params;
    $this->request_input = file_get_contents("php://input");

    // Prepend request input into the parameters stack
    array_unshift($this->request_params, file_get_contents("php://input"));
  }

  // Executes the backend request
  function execute()
  {
    // If the method exists, execute it
    if(method_exists($this, $this->request_backend))
    {
      $resp = $this->{$this->request_backend}($this->request_params);
      $this->_response($resp[0], $resp[1]);
    }
    // Otherwise, return an error
    else
      $this->_response($this->request_backend, API::ERROR_METHOD_DOES_NOT_EXISTS);
  }

  // Handle API response
  // @internal
  // @param   string    $response   Response message.
  // @param   int       $code       Response code.
  private function _response($response, $code)
  {
    $obj = new stdClass;
    $resp = new stdClass;

    // Set the object metadata
    $obj->tally_version = $this->tally_version;
    $obj->engine_version = $this->engine_version;
    $obj->backend_version = $this->backend_version;

    // Parse known error code
    switch($code)
    {
      case API::ERROR_METHOD_DOES_NOT_EXISTS:
        $resp->error_code = $code;
        $resp->error_message = $response." method does not exists (".$code.")";
        break;

      // If error code is unknown, pass down to subclass handler
      default:
        if(method_exists($this, "_backend_response"))
          $resp = $this->_backend_response($response, $code);
        else
          $resp = $response;
    }

    $obj->response = $resp;

    // Set MIME type to JSON
    header("Content-type: application/json");
    // Return JSON response
    echo json_encode($obj);
  }
}
?>
