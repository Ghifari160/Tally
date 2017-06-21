<?php
abstract class API
{
	const ERROR_PREFIX = "g16.error.";

	// @property $method the HTTP method of the request
	protected $method;
	// @property $endpoint the endpoit of the API
	protected $endpoint;
	// @property $argument endpoint argument
	protected $argument;
	// @property $rawInput raw input
	protected $rawInput;
	// @property $input input
	protected $input;

	public function __construct($request)
	{
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: *");
		header("Content-Type: text/plain");

		$this->argument = explode(".", $request);
		$this->endpoint = array_shift($this->argument);
		$this->method = $_SERVER['REQUEST_METHOD'];

		// Handle non-standard request method
		if($this->method == "POST" && array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER))
		{
			// DELETE request
			if($_SERVER['HTTP_X_HTTP_METHOD'] == "DELETE")
				$this->method = "DELETE";
			// PUT request
			else if($_SERVER['HTTP_X_HTTP_METHOD'])
				$this->method = "PUT";
			else
				throw new Exception("Unexpected header");
		}

		switch($this->method)
		{
			case "DELETE":
			case "POST":
				$this->input = $this->_cleanInputs($_POST);
				$this->rawInput = file_get_contents("php://input");
				$this->_parseRawInputData();
				break;

			case "GET":
				$this->input = $this->_cleanInputs($_GET);
				break;

			case "PUT":
				$this->input = $this->_cleanInputs($_GET);
				$this->rawInput = file_get_contents("php://input");
				break;

			default:
				$this->_response("Invalid Method", 405);
				break;
		}
	}

	public function processAPI()
	{
		if(method_exists($this, $this->endpoint))
		{
			$r = $this->{$this->endpoint}($this->argument);

			if(substr($r, 0, strlen(self::ERROR_PREFIX)) == self::ERROR_PREFIX)
			{
				return $this->_response(substr($r, strlen(self::ERROR_PREFIX) + 3),
					substr($r, strlen(self::ERROR_PREFIX), 3));
			}
			else
				return $this->_response($r);
			// return $this->_response($this->{$this->endpoint}($this->argument));
			// return $this->_response($this->{$this->endpoint}($this->argument));
		}

		return $this->_response("No endpoint: ".$this->endpoint, 404);
	}

	private function _response($message, $status = 200)
	{
		header("HTTP/1.1 ".$status." ".$this->_requestStatus($status));
		return $message;
	}

	private function _cleanInputs($data)
	{
		$clean = array();

		if(is_array($data))
		{
			foreach($data as $k => $v)
				$clean[$k] = $this->_cleanInputs($v);
		}
		else
			$clean = trim(strip_tags($data));

		return $clean;
	}

	private function _parseRawInputData()
	{
		// Only parse non-empty text/plain request
		if($_SERVER['CONTENT_TYPE'] == "text/plain" && strlen($this->rawInput) > 0)
		{
			$raw = $this->_cleanInputs($this->rawInput);
			$input = array();

			$fragmentX = explode("&", $raw);
			foreach($fragmentX as $x)
			{
				$fragmentY = explode("=", $x);

				if(count($fragmentY) > 1)
					$input[$fragmentY[0]] = $fragmentY[1];
			}

			$this->input = array_merge($this->input, $input);
		}
	}

	private function _requestStatus($code)
	{
		$status = array(
			200 => "OK",
			400 => "Bad Request",
			402 => "Payment Required",
			403 => "Forbidden",
			404 => "Not Found",
			405 => "Method Not Allowed",
			415 => "Unsupported Media Type",
			500 => "Internal Server Error"
		);

		return ($status[$code]) ? $status[$code] : $status[500];
	}
}
?>
