<?PHP
/**
* Copyright 2017 Beans
*
* Licensed under the Apache License, Version 2.0 (the "License"); you may
* not use this file except in compliance with the License. You may obtain
* a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
* WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
* License for the specific language governing permissions and limitations
* under the License.
*/

namespace Beans\Error;

defined('ABSPATH') or die;

class BaseError extends \Exception
{
    public function __construct($error=array())
    {
        if (!is_array($error)){
            $error = array(
                'message' => $error
            );
        }
        if(!isset($error['code']))
            $error['code'] = -1;
        if(!isset($error['message']))
            $error['message'] = '';

        parent::__construct($error['message'], $error['code']);
    }
}

class ConnectionError extends BaseError {}
class ValidationError extends BaseError {}
class ServerError extends BaseError {}

namespace Beans;

use Beans\Error\ConnectionError;
use Beans\Error\ServerError;
use Beans\Error\ValidationError;

if (!function_exists('curl_init'))
    return;

//if (!function_exists('json_decode'))
//    trigger_error('Beans needs the JSON PHP extension.');


class Beans
{

    public $endpoint = 'https://api-3.trybeans.com/v3/';

    const VERSION = '3.0.0';

    private $secret = '';
    private $_next_page = '';
    private $_previous_page = '';
    private $_curl_handle = null;

    public function __construct($secret = null)
    {
        $this->secret = $secret;
    }
    
    public function get($path, $arg=null, $headers=null)
    {       
        return $this->make_request($path, $arg, 'GET', $headers);
    }

    public function get_next_page()
    {
        return $this->_next_page? $this->get($this->_next_page, null) : array();
    }

    public function get_previous_page()
    {
        return $this->_previous_page? $this->get($this->_previous_page, null) : array();
    }
    
    public function post($path, $arg=null, $headers=null)
    {       
        return $this->make_request($path, $arg, 'POST', $headers);
    }

    public function put($path, $arg=null, $headers=null)
    {
        return $this->make_request($path, $arg, 'PUT', $headers);
    }
        
    public function delete($path, $arg=null, $headers=null)
    {       
        return $this->make_request($path, $arg, 'DELETE', $headers);
    }
    
    public function make_request($path, $data=null, $method=null, $headers=null)
    {
        $url = $this->endpoint . $path;

        if (strpos($path,'://') !== false){
            $url = $path;
        }

        if($method === 'GET' && !empty($data)){
            $url .= '?' . http_build_query($data);
        }

        $data_string = json_encode( $data ? $data : array() );

        $ua = array(
            'bindings_version'  => self::VERSION,
            'lang'              => 'PHP',
            'lang_version'      => phpversion(),
            'publisher'         => 'Beans',
        );

        if (!is_null($headers)){
           $headers = array_merge( array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                'X-Beans-Client-User-Agent: '. json_encode($ua),
            ), $headers);
        }
        else{
            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                'X-Beans-Client-User-Agent: '. json_encode($ua),
            );
        }
        // Set Request Options
        // DO NOT: do not add CURLOPT_FOLLOWLOCATION, CURLOPT_MAXREDIRS without proper testing..
        // Theses options has been the cause of bugs in the past...
        $curlConfig = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $data_string,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => 80,
            CURLOPT_HTTPHEADER     => $headers,
        );
        if($this->secret){
            $curlConfig[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $curlConfig[CURLOPT_USERPWD] = $this->secret;
        }
        
        //Make HTTP request
        $ch = $this->_getCurlHandle();
        curl_setopt_array($ch, $curlConfig);
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        // Check for connection error
        if (!$http_status) {
            $err_code = curl_errno($ch);
            $err_msg = curl_error($ch);
            $error = array(
                'code' => $err_code,
                'message' => "Beans cURL Error $err_code: $err_msg",
            );
            throw new ConnectionError($error);
        }

        # Handle 202, 203, 204 responses
        if($http_status<300 && !$response){
            return true;
        }

        // Check for HTTP error
        if($content_type != 'application/json'){
            $error = array(
                'code' => $http_status,
                'message' => "Beans HTTP Error: $http_status",
            );
            if($http_status >= 500)
                throw new ServerError($error);
            throw new ValidationError($error);
        }
        
        // Load response
        $response = json_decode($response, TRUE);

        // Check for Beans error
        if(isset($response['error'])){
            if(isset($response['error']['code']) and $response['error']['code'] >= 500)
                throw new ServerError($response['error']);
            throw new ValidationError($response['error']);
        }

        $result = $response;

        // support pagination
        if(isset($result['data']) && isset($result['object']) && $result['object'] == 'list'){
            $this->_next_page = $result['next'];
            $this->_previous_page = $result['previous'];
            $result = $result['data'];
        }

        return $result;
    }

    protected function _getCurlHandle() 
    {
        if (!$this->_curl_handle) {
            $this->_curl_handle = curl_init();
        }

        return $this->_curl_handle;
    }

    public function __destruct() 
    {
        if ($this->_curl_handle) {
            curl_close($this->_curl_handle);
        }
    }
}
