<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomegearController extends Controller {
	public function index() {
		return $this->connect();
	}

	protected function connect() {
		$start = microtime( true );

		/**** Use TCP sockets to connect to Homegear ****/

		//No SSL (recommended for "localhost")
		$host   = "10.0.0.3";
		$port   = 2001;
		$ssl    = false;
		$Client = new Client( $host, $port, $ssl );

		//SSL

		/*$host = "10.0.0.3";
		$port = 2003;
		$ssl = true;
		$username = "homegear";
		$password = "homegear";
		$Client = new Client($host, $port, $ssl);
		$Client->setSSLVerifyPeer(false);
		//!!! You should create a signed certificate and then enable "verify peer" !!!
		//$Client->setSSLVerifyPeer(true);
		//$Client->setCAFile("/path/to/ca.crt");
		$Client->setUsername($username);
		$Client->setPassword($password);*/

		//echo '<pre>';
		//var_dump("listDevices", $Client->send("listDevices", array()));
		/*var_dump("system.methodHelp", $Client->send("system.methodHelp", array("getLinks")));
		var_dump("getDeviceDescription", $Client->send("getDeviceDescription", array(23, 1)));
		var_dump("getLinkInfo", $Client->send("getLinkInfo", array(23, 1, 37, 3)));
		var_dump("getMetadata", $Client->send("getMetadata", array("54:2")));
		var_dump("getParamset", $Client->send("getParamset", array(1, -1, "MASTER")));
		var_dump("getParamset 2", $Client->send("getParamset", array(23, 1, 37, 3)));
		var_dump("getParamsetDescription", $Client->send("getParamsetDescription", array(23, 1, "LINK")));
		var_dump("getParamsetId", $Client->send("getParamsetId", array(23, 1, 37, 3)));
		var_dump("getServiceMessages", $Client->send("getServiceMessages", array()));
		var_dump("getLinkPeers", $Client->send("getLinkPeers", array(27, 1)));
		var_dump("getLinks", $Client->send("getLinks", array()));
		var_dump("getLinks 2", $Client->send("getLinks", array(27, 1, 31)));
		var_dump("putParamset", $Client->send("putParamset", array(54, 1, "MASTER", array("EVENT_DELAYTIME" => 0.0))));
		var_dump("putParamset 2", $Client->send("putParamset", array(63, 18, "MASTER", array("MESSAGE_SHOW_TIME" => 0.0))));
		var_dump("setTeam", $Client->send("setTeam", array(12, 1)));
		var_dump("setTeam 2", $Client->send("setTeam", array(12, 1, 0x80000002, 1)));
		var_dump($Client->send("setValue", array(15, 1, "ON_TIME", 787487.3)));
		var_dump($Client->send("setValue", array(15, 1, "STATE", true)));
		var_dump($Client->send("setValue", array(19, 1, "VALVE_STATE", 0)));
		var_dump($Client->send("setValue", array(21, 2, "SETPOINT", 16.0)));
		var_dump($Client->send("setValue", array(62, 18, "TEXT", "Hi")));
		var_dump($Client->send("setValue", array(63, 18, "SUBMIT", true)));*/
		//echo '</pre>';

		$list_devices = $Client->send("listDevices", array());
		$list_devices = $this->array_change_key_case_recursive($list_devices, CASE_LOWER);
		$devices = [];

		//dd($list_devices);

		foreach($list_devices as $device) {
			if(empty($device['parent'])) $devices[$device['address']] = $device;
			else $devices[$device['parent']][$device['address']] = $device;
		}

		//dd($devices);

		$time_elapsed_secs = microtime( true ) - $start;

		return view( 'device_overview', [ 'devices' => $devices, 'loading_time' => $time_elapsed_secs ] );
	}

	// converts all keys in a multidimensional array to lower or upper case
	protected function array_change_key_case_recursive($arr, $case=CASE_LOWER)
	{
		return array_map(function($item)use($case){
			if(is_array($item))
				$item = $this->array_change_key_case_recursive($item, $case);
			return $item;
		}, array_change_key_case($arr, $case));
	}
}

/**
 * XMLRPCException The XML RPC Exception class
 */
class XMLRPCException extends \Exception {
}

/**
 * Client The XML RPC client class
 *
 *
 *
 * @version 1.1
 * @author sathya
 */
class Client {
	/**
	 * IP address of the XML RPC server
	 * @var string
	 */
	private $host = "";

	/**
	 * Port number of the XML RPC server
	 * @var int
	 */
	private $port = 2001;

	/**
	 * Enable SSL
	 * @var bool
	 */
	private $ssl = false;

	/**
	 * Enable certificate verification
	 * @var bool
	 */
	private $sslVerifyPeer = true;

	/**
	 * Enable certificate verification
	 *
	 * @param $value bool
	 */
	public function setSSLVerifyPeer( $value ) {
		$this->sslVerifyPeer = $value;
	}

	/**
	 * Path to the certificate cuthority's certificate
	 * @var string
	 */
	private $caFile = "";

	/**
	 * Set the path to the certificate cuthority's certificate
	 *
	 * @param $value string
	 */
	public function setCAFile( $value ) {
		$this->caFile = $value;
	}

	/**
	 * Username for basic auth
	 * @var string
	 */
	private $username = "";

	/**
	 * Set username for basic auth
	 *
	 * @param $value string
	 */
	public function setUsername( $value ) {
		$this->username = $value;
	}

	/**
	 * Password for basic auth
	 * @var string
	 */
	private $password = "";

	/**
	 * Set password for basic auth
	 *
	 * @param $value string
	 */
	public function setPassword( $value ) {
		$this->password = $value;
	}

	/**
	 * Holds the socket connection
	 * @var socket
	 */
	private $socket = null;

	/**
	 * Default constructor
	 *
	 * @param $host string IP address of the XML RPC server
	 * @param $port int Port number of the XML RPC server
	 * @param $ssl bool Enable SSL
	 */
	public function __construct( $host, $port = 2001, $ssl = false ) {
		$this->host = $host;
		$this->port = $port;
		$this->ssl  = $ssl;

		$this->connect();
	}

	public function __destruct() {
		if ( $this->socket ) {
			@fclose( $this->socket );
		}
		$this->socket = null;
	}

	/**
	 * Connects to Homegear
	 */
	private function connect() {
		if ( ! $this->socket ) {
			$this->socket = @stream_socket_client( "tcp://" . $this->host . ":" . $this->port, $errorNumber, $errorString, 10 );
			if ( ! $this->socket ) {
				throw new XMLRPCException( "Could not open socket. Host: " . $this->host . " Port: " . $this->port . " Error: $errorString ($errorNumber)" );
			}
			if ( $this->ssl ) {
				stream_set_blocking( $this->socket, true );
				stream_context_set_option( $this->socket, 'ssl', 'SNI_enabled', true );
				if ( $this->caFile ) {
					stream_context_set_option( $this->socket, 'ssl', 'cafile', $this->caFile );
				}
				stream_context_set_option( $this->socket, 'ssl', 'verify_peer', $this->sslVerifyPeer );
				$secure = stream_socket_enable_crypto( $this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT );
				if ( ! $secure ) {
					@fclose( $this->socket );
					throw new XMLRPCException( "XMLRPC error: Failed to enable SSL." );
				}
				stream_set_blocking( $this->socket, false );
			}
		}
	}

	/**
	 * Sends a XML RPC request to XML RPC server
	 *
	 * @param $request string Request generated by xmlrpc_encode_request
	 *
	 * @return string The returned XML string
	 */
	private function sendRequest( $request ) {
		$response  = '';
		$retries   = 0;
		$startTime = time();
		while ( ! $response && $retries < 20 ) {
			if ( ! $this->socket ) {
				$this->connect();
			}
			$response = '';
			$query    = "POST / HTTP/1.1\nUser_Agent: HM-XMLRPC-Client\nHost: " . $this->host . "\nConnection: Keep-Alive\nContent-Type: text/xml\n";

			if ( $this->username ) {
				$query .= "Authorization: Basic " . base64_encode( $this->username . ":" . $this->password ) . "\n";
			}

			$query        .= "Content-Length: " . strlen( $request ) . "\n\n" . $request . "\n";
			$bytesWritten = 0;
			$continueLoop = false;
			$querySize    = strlen( $query );
			while ( $bytesWritten < $querySize ) {
				$result = @fputs( $this->socket, $query, 1024 );
				if ( ! $result ) {
					if ( $retries == 19 ) {
						throw new XMLRPCException( "Error sending data to server." );
					} else {
						@fclose( $this->socket );
						$this->socket = null;
						$retries ++;
						usleep( 50 );
						$continueLoop = true;
						break;
					}
				}
				$bytesWritten += $result;
				$query        = substr( $query, $result );
			}
			if ( $continueLoop ) {
				continue;
			}
			while ( ! feof( $this->socket ) && ( time() - $startTime ) < 30 ) {
				$response .= @fgets( $this->socket );
				//A little dirty, but it works. As the end always looks like this, I don't see a problem.
				if ( substr( $response, - 3 ) == ">\r\n" ) {
					break;
				}
			}

			if ( ! $response ) {
				@fclose( $this->socket );
				$this->socket = null;
			}
			$retries ++;
		}
		if ( strncmp( $response, "HTTP/1.1 200 OK", 15 ) === 0 ) {
			return substr( $response, strpos( $response, "<" ) );
		}
		if ( $response ) {
			throw new XMLRPCException( "XMLRPC error:\r\n" . $response );
		} else {
			throw new XMLRPCException( "XMLRPC error: Response was empty." );
		}
	}

	/**
	 * Sends an XML RPC request to the XML RPC server
	 *
	 * @param $methodName string Name of the XML RPC method to call
	 * @param $params array Array of parameters to pass to the XML RPC method. Type is detected automatically. For a struct just encode an array within $params.
	 *
	 * @return array Array of returned parameters
	 */
	public function send( $methodName, $params ) {
		$request  = \xmlrpc_encode_request( $methodName, $params );
		$response = $this->sendRequest( $request );
		$response = \xmlrpc_decode( trim( $response ) ); //Without the trim function returns null

		return $response;
	}
}
