<?php

namespace app\src;

use Exception;
use OutOfBoundsException;
use UnexpectedValueException;
use CURLFile;

class Base
{
    const VERSION = '0.2.5';
    const API = 'https://api.productai.cn';

    private $access_key_id;
    private $secret_key;

    public $method;
    public $headers;
    public $body;

    public $curl_opt;
    public $curl_info;
    public $curl_errno;
    public $curl_error;
    public $curl_output;

    private $tmpfile;

    public function __construct($access_key_id, $secret_key, $language='en-US')
    {
        $this->access_key_id = $access_key_id;
        $this->secret_key = $secret_key;
        $this->language = $language;

        $this->initialize();

        $this->curl_opt = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CAINFO => __DIR__.'/ca.pem',
        ];
    }

    protected function initialize()
    {
        $this->method = 'POST';

        $this->headers = [
            'x-ca-version' => 1,
            'x-ca-accesskeyid' => $this->access_key_id,
            'x-ca-timestamp' => time(),
            'x-ca-signaturenonce' => $this->generateNonce(16),
            'user-agent' => "ProductAI-SDK-PHP/{$this->version()} (+http://www.productai.cn)",
            'accept-language' => $this->language,
        ];

        $this->body = [];

        $this->batchSetProperties([
            'curl_info',
            'curl_errno',
            'curl_error',
            'curl_output',

            'tmpfile',
        ], null);
    }

    private function batchSetProperties($properties, $value)
    {
        foreach ($properties as $v) {
            $this->$v = $value;
        }
    }

    public static function version()
    {
        return static::VERSION;
    }

    public static function api()
    {
        return static::API;
    }

    private function generateNonce($len, $chars = '')
    {
        if (!$chars) {
            $chars = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9));
        } elseif (!is_array($chars)) {
            $chars = str_split($chars);
        }

        $index = count($chars) - 1;

        $nonce = '';
        for ($i = 0; $i < $len; ++$i) {
            $nonce .= $chars[mt_rand(0, $index)];
        }

        return $nonce;
    }

    private function signRequests()
    {
        $headers = [
            'requestmethod' => $this->headers['requestmethod'],
        ];
        foreach ($this->headers as $k => $v) {
            if (substr($k, 0, 2) == 'x-') {
                $headers[$k] = $v;
            }
        }

        $body = [];
        foreach ($this->body as $k => $v) {
            if (is_string($v) || is_numeric($v)) {
                $body[$k] = $v;
            }
        }

        $requests = array_merge($headers, $body);
        ksort($requests);

        return base64_encode(hash_hmac('sha1', urldecode(http_build_query($requests)), $this->secret_key, true));
    }

    protected function curl($service_type, $service_id)
    {
        $curl = curl_init("{$this->api()}/$service_type/$service_id");

        $this->curl_opt[CURLOPT_CUSTOMREQUEST] = $this->method;

        $this->headers['requestmethod'] = $this->method;
        $this->headers['x-ca-signature'] = $this->signRequests();
        $headers = [];
        foreach ($this->headers as $k => $v) {
            $headers[] = "$k: $v";
        }

        $this->curl_opt[CURLOPT_HTTPHEADER] = $headers;
        $this->curl_opt[CURLOPT_POSTFIELDS] = $this->body ?: null;
//        echo '<pre>';
//        var_dump($this->curl_opt);die;
        curl_setopt_array($curl, $this->curl_opt);

        $this->curl_output = curl_exec($curl);

        $this->curl_info = curl_getinfo($curl);
        $this->curl_errno = curl_errno($curl);
        $this->curl_error = curl_error($curl);

        curl_close($curl);

        if (isset($this->tmpfile)) {
            fclose($this->tmpfile);
        }

        if ($this->curl_errno !== 0) {
            throw new Exception("Request failed. $this->curl_error", $this->curl_errno);
        }

        $result = json_decode($this->curl_output, true);
        if ($this->curl_info['http_code'] !== 200) {
            throw new Exception('API thrown an error. ' . (isset($result['message']) ? $result['message'] : $this->curl_output), $this->curl_info['http_code']);
        }

        if ($result === null) {
            throw new Exception("Decode result error. The original output is '$this->curl_output'.", 1);
        }

        return $result;
    }

    protected function convertArrayToCSV($array)
    {
        $this->tmpfile = tmpfile();

        if ($this->tmpfile === false) {
            throw new Exception('Can not create temporary file.', 1);
        }

        foreach ($array as $v) {
            $v = is_array($v) ? array_values($v) : [$v];

            // tags
            if (isset($v[2]) && is_array($v[2])) {
                $v[2] = implode('|', $v[2]);
            }

            fputcsv($this->tmpfile, $v);
        }

        return stream_get_meta_data($this->tmpfile)['uri'];
    }

    protected function loadImage($image)
    {
        $prefix = substr($image, 0, 1);

        switch ($prefix) {
            case '#':
            case '@':
                $image = substr($image, 1);

                if ($prefix == '#') {
                    if (!isset($_FILES[$image])) {
                        throw new OutOfBoundsException("name $image not found in forms", 1);
                    }

                    $image = $_FILES[$image]['tmp_name'];

                    if (!is_uploaded_file($image)) {
                        throw new UnexpectedValueException("possible file upload attack: $image", 1);
                    }
                }

                $this->body['search'] = new CURLFile($image);

                break;

            default:
                if (substr($image, 0, 4) == 'http' || substr($image, 0, 3) == 'ftp') {
                    $this->body['url']
                        = $image;
                } else {
                    $this->tmpfile = tmpfile();
                    fwrite($this->tmpfile, $image);
                    $this->body['search'] = new CURLFile(stream_get_meta_data($this->tmpfile)['uri']);
                }

                break;
        }
    }
}
