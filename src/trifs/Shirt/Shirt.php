<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 3fs d.o.o.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace trifs\Shirt;

/**
 * Simple HTTP Impromptu Request Trait
 *
 * @author   Martin Štepic <martin.stepic@3fs.si>
 * @license  http://opensource.org/licenses/MIT The MIT License (MIT)
 */
trait Shirt
{
    /**
     * Static constant for get calls.
     *
     * @var string
     */
    public static $get = 'GET';

    /**
     * Static constant for post calls.
     *
     * @var string
     */
    public static $post = 'POST';

    /**
     * Static constant for delete calls.
     *
     * @var string
     */
    public static $delete = 'DELETE';

    /**
     * Static constant for put calls.
     *
     * @var string
     */
    public static $put = 'PUT';

    /**
     * Holds http response code.
     *
     * @var int
     */
    public static $httpCode;

    /**
     * Holds http response string.
     *
     * @var string
     */
    public static $response;

    /**
     * Holds parsed http headers.
     * 
     * @var array
     */
    public $headers = [];

    /**
     * Holds parsed cookies.
     *
     * @var array
     */
    public $cookies = [];

    /**
     * Holds cookie strings.
     *
     * @var array
     */
    public $cookieString = [];

    /**
     * Function creates GET request to url with appointed parameters.
     *
     * @param   string $url
     * @param   array  $content
     * @param   mixed  $header
     * @return  self
     */
    public function get($url, $content = [], $header = null)
    {
        return $this->call($url, Shirt::$get, $header, $content);
    }

    /**
     * Function creates POST request to url with appointed parameters.
     *
     * @param   string $url
     * @param   array  $content
     * @param   mixed  $header
     * @return  string
     */
    public function post($url, $content = [], $header = null)
    {
        return $this->call($url, Shirt::$post, $header, $content);
    }

    /**
     * Function creates PUT request to url with appointed parameters.
     *
     * @param   string $url
     * @param   array  $content
     * @param   mixed  $header
     * @return  string
     */
    public function put($url, $content = [], $header = null)
    {
        return $this->call($url, Shirt::$put, $header, $content);
    }

    /**
     * Function creates DELETE request to url with appointed parameters.
     *
     * @param   string $url
     * @param   array  $content
     * @param   mixed  $header
     * @return  self
     */
    public function delete($url, $content = [], $header = null)
    {
        return $this->call($url, Shirt::$delete, $header, $content);
    }

    /**
     * Returns JSON decoded response.
     *
     * @return mixed
     */
    public function toJson()
    {
        return json_decode(self::$response, true);
    }

    /**
     * Function returns response.
     *
     * @return mixed
     */
    public function response()
    {
        return self::$response;
    }

    /**
     * Function returns parsed cookies array.
     *
     * @return array
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Function returns cookies as string.
     *
     * @return array
     */
    public function cookieHeaders()
    {
        return $this->cookieString;
    }

    /**
     * Function returns all resopnse headers or only one specified by name.
     *
     * @param mixed $name
     * @return mixed
     */
    public function headers($name = false)
    {
        if ($name && isset($this->headers[$name])) {
            return $this->headers[$name];
        }

        return $this->headers;
    }

    /**
     * Function returns response code.
     *
     * @return int
     */
    public function responseCode()
    {
        return self::$httpCode;
    }

    /**
     * Function sets httpCode, headers and cookie for response array.
     *
     * @param array $httpHeaders    http response headers
     * @return void
     */
    public function httpHeaderParser(array $httpHeaders = [])
    {
        $cookies = [];
        // parse headers
        foreach ($httpHeaders as $h) {
            if (false === strpos($h, 'Set-Cookie')) {
                $this->headers = array_merge($this->headers, $this->parseHttpHeaderString($h));
            } else {
                $cookies[] = $this->parseHttpHeaderString($h);
            }
        }

        // checking for cookie(s)
        foreach (array_values($cookies) as $cookie) {
            $this->cookies[]      = $this->parseCookieString($cookie['Set-Cookie']);
            $this->cookieString[] = $cookie['Set-Cookie'];
        }
    }

    /**
     * Function parses cookie string.
     *
     * @param  string  $cookieHeader
     * @return array
     */
    private function parseCookieString($cookieHeader)
    {
        // init
        $cookie = [];

        // checks if cookie value is not a name or value
        $checkValid = function ($string) {
            $validProperties = [
                'domain',
                'path',
                'max-age',
                'expires',
                'httponly',
                'secure',
            ];
            return (bool)in_array(trim(strtolower($string)), $validProperties);
        };

        foreach (explode(';', $cookieHeader) as $name) {
            if (false !== strpos($name, '=')) {
                $handle = explode('=', $name, 2);
                if (false !== $checkValid($handle[0])) {
                    // appoint valid properties to cookie
                    $cookie[trim($handle[0])] = $handle[1];
                } else {
                    // appoint name and value to cookie
                    $cookie = [
                        'name'  => trim($handle[0]),
                        'value' => trim($handle[1]),
                    ];
                }
            } else {
                $cookie[trim($name)] = true;
            }
        }

        return $cookie;
    }

    /**
     * Function parses http header string and code
     *
     * @param  string $headerString
     * @return array
     */
    private function parseHttpHeaderString($headerString)
    {
        $headers = [];

        if (false !== strpos($headerString, ':')) {
            $handle = explode(':', $headerString, 2);
            $headers[$handle[0]] = trim($handle[1]);
        } else {
            self::$httpCode = explode(' ', $headerString)[1];
        }

        return $headers;
    }

    /**
     * Initiates a request and returns the response.
     *
     * @param  string      $url
     * @param  string      $method
     * @param  array|void  $header
     * @param  array|void  $content
     * @return this
     */
    private function call($url, $method, $header = null, $content = null)
    {
        // http request build
        $options = [
            'http' => [
                'method'          => $method,
                'header'          => $header  ?: 'Content-Type: application/json; charset=utf-8',
                'content'         => $content ?: [],
                'follow_location' => 0,
                'ignore_errors'   => 1,
            ],
        ];

        // get response data
        self::$response = file_get_contents(
            $url,
            false,
            stream_context_create($options)
        );

        // parse headers
        $this->httpHeaderParser($http_response_header);

        return $this;
    }

    /**
     * Curl file upload helper for curlPost (curlPut?) functions.
     *
     * @var string $filePath
     * @var string $fileMIME
     * @var string $fileDescription
     * @return string
     */
    public function imageUploadParam(
        $filePath,
        $fileMIME = 'image/jpeg',
        $fileDescription = 'image'
    ) {
        return curl_file_create(
            realpath(__DIR__ . $filePath),
            $fileMIME,
            $fileDescription
        );
    }

    /**
     * Function creates curl POST request to url with appointed parameters.
     *
     * @param   string $url
     * @param   array  $content
     * @param   mixed  $header
     * @return  self
     */
    public static function curlPost($url, $content = [], $header = false)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$header]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output         = curl_exec($ch);
        self::$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // return
        self::$response = $output;
        return self;
    }

    /**
     * Function creates PUT request to url with appointed parameters.
     *
     * @param   string $url
     * @param   array  $content
     * @param   mixed  $header
     * @return  self
     */
    public static function curlPut($url, $content = [], $header = null)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, Shirt::$put);

        if (!empty($content)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [$header]);

        $output         = curl_exec($ch);
        self::$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // return
        self::$response = $output;
        return self;
    }
}
