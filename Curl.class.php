<?php
class Curl
{
    const DEFAULT_USER_AGENT = 'curl';
    //const DEFAULT_CONTENT_TYPE = 'application/json; charset=utf-8';
    //const COOKIE_TMP_DIR = '/tmp/';

    #Todo: to object
    public $response = null;
    public $error = null;

    private $_opts = array();
    private $_headers = array();
    private $_curl_handler;
    protected $cookie_file = null;

    /**
     * 初始化Curl預設設定
     * Todo: remove url init in constructor
     *
     * @param  string $url 網址
     * @param  array  $opts 設定s
     */
    public function __construct()
    {
        $this->_initOpts();
    }

    /**
     * release curl resource when destruct
     *
     * @param  void
     * @return void
     */
    public function __destruct()
    {
        if (!empty($this->_curl_handler)) {
            curl_close($this->_curl_handler);
        }
    }

    /**
     * 初始化Curl設定
     *
     * @param void
     * @return void
     */
    private function _initOpts()
    {
        $this->_opts = array(
            CURLOPT_USERAGENT      => self::DEFAULT_USER_AGENT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_ENCODING       => 'gzip,deflate',
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_FOLLOWLOCATION => true
        );

        self::_setDefaultHeaders();
    }

    private function unsetOpt($opt_key)
    {
        if (is_array($opt_key)) {
            foreach ($opt_key as $key) {
                unset($this->_opts[$opt_key]);
            }
        } else {
            unset($this->_opts[$opt_key]);
        }
    }

    /**
     * 設定預設headers
     * (根據瀏覽器會不同，目前使用 chrome 32.0.1700.72 的預設)
     *
     * @param void
     * @return void
     */
    private function _setDefaultHeaders()
    {
        $this->_headers = array(
            'Connection'      => 'keep-alive',
            'Accept'          => 'text/html,application/json,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5'
        );
    }

    /**
     * 大量設定Curl參數
     * 可傳入array大量設定參數, array範例參考 _init_opts
     *
     * @param array $opts curl options
     * @return void
     */
    public function setOpts($opts)
    {
        foreach($opts as $key => $val){
            $this->setOpt($key, $val);
        }
    }

    /**
     * 設定Curl參數
     *
     * @param int $key integer or CURLOPT_* const
     * @param int $val value to set
     * @return void
     */
    public function setOpt($key, $val)
    {
        $this->_opts[$key] = $val;
    }

    /**
     * 取得 CURLOPT_* 設定值
     *
     * @param int $key integer or CURLOPT_* const
     * @return bool|int|string 回傳指定參數的值
     */
    public function getOpt($key = null)
    {
        if (empty($key)) {
            return $this->_opts;
        } elseif (isset($this->_opts[$key])) {
            return $this->_opts[$key];
        } else {
            #Todo: customeize this exception
            throw new Exception("Unknown opt key :{$key}");
        }
    }

    /**
     * 設定 user_agent
     *
     * @param string $user_agent User agent string
     * @return void
     */
    public function setUserAgent($user_agent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $user_agent);
    }

    /**
      * 取得 CURLOPT_* 設定值
     *
     * @param int $key integer or CURLOPT_* const
     * @return bool|int|string 回傳指定參數的值
     */
    public function getUserAgent()
    {
        return $this->getOpt(CURLOPT_USERAGENT);
    }

    /**
     * 設定 curl 的 request url
     *
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        $this->setOpt(CURLOPT_URL, $url);
    }

    /**
     * 取得目前設定的 url
     *
     * @param void
     * @return string 目前設定的url
     */
    public function getUrl()
    {
        return $this->getOpt(CURLOPT_URL);
    }

    /**
     * 檢查url是否已經設定
     *
     * @param void
     * @return bool return true if url set, else false
     */
    private function isUrlSet()
    {
        $url = $this->getUrl();
        return !empty($url);
    }

    /**
     * 設定 http header
     * 可傳入array()
     *
     * @param array $header
     * @return bool return true if url set, else false
     */
    public function setHeaders($headers, $clean_default = false)
    {
        if($clean_default == true){
            $this->_headers = $headers;
        }else{
            foreach($headers as $header){
                list($key, $val) = $header;
                $this->setHeader($key, $val);
            }
        }
    }

    public function setHeader($key, $val)
    {
        $this->_headers[$key] = $val;
    }

    public function verboseOn()
    {
        $this->setOpt(CURLOPT_VERBOSE, true);
    }

    /**
     * Turn off host SSL verify
     *
     * @param void
     * @return void
     */
    public function verboseOff()
    {
        $this->setOpt(CURLOPT_VERBOSE, false);
    }

    /**
     * Todo: deprecate
     * Turn on http request header output
     *
     * @param void
     * @return void
     */
    public function requestHeaderOn()
    {
        $this->setOpt(CURLINFO_HEADER_OUT, true);
    }

    /**
     * Turn off request header
     *
     * @param void
     * @return void
     */
    public function requestHeaderOff()
    {
        $this->setOpt(CURLINFO_HEADER_OUT, false);
    }

    public function responseHeaderOn()
    {
        $this->setOpt(CURLOPT_HEADER, true);
    }

    /**
     * Turn off SSL verify
     *
     * @param void
     * @return void
     */
    public function sslVerifyOff()
    {
        $this->sslVerifyHostOff();
        $this->sslVerifyPeerOff();
    }

    /**
     * Turn off host SSL verify
     *
     * @param void
     * @return void
     */
    public function sslVerifyHostOff()
    {
        $this->setOpt(CURLOPT_SSL_VERIFYHOST, false);
    }

    /**
     * Turn off peer SSL verify
     *
     * @param void
     * @return void
     */
    public function sslVerifyPeerOff()
    {
        $this->setOpt(CURLOPT_SSL_VERIFYPEER, false);
    }

    /**
     * Turn on curl follow location (http header redirect)
     *
     * @param void
     * @return void
     */
    public function followLocationOon()
    {
        $this->setOpt(CURLOPT_FOLLOWLOCATION, true);
    }

    /**
     * Turn off curl follow location (http header redirect)
     *
     * @param void
     * @return void
     */
    public function followLocationOoff()
    {
        $this->setOpt(CURLOPT_FOLLOWLOCATION, false);
    }

    /**
     * #Todo: fix naming
     * Set timeout for CURLOPT_TIMEOUT, CURLOPT_CONNECTTIMEOUT
     *
     * Difference between CURLOPT_TIMEOUT and CURLOPT_CONNECTTIMEOUT
     * |------------------------ CURLOPT_TIMEOUT 20s -----------------------|
     * |-- CURLOPT_CONNECTTIMEOUT 10s --|
     * 若要設定 timeout,記得 CURLOPT_TIMEOUT 需 > CURLOPT_CONNECTTIMEOUT 計算方式如上圖
     *
     * @param int $second timeout seconds
     * @return void
     */
    public function setTimeouts($second)
    {
        $this->setOpt( CURLOPT_TIMEOUT, $second);
        $this->setOpt( CURLOPT_CONNECTTIMEOUT, $second);
    }

    /**
     * Set timeout for CURLOPT_TIMEOUT
     *
     * @param int $second timeout seconds
     * @return void
     */
    public function setTimeout($second)
    {
        $this->setOpt( CURLOPT_TIMEOUT, $second);
    }

    /**
     * Set timeout for CURLOPT_CONNECTTIMEOUT
     *
     * @param int $second timeout seconds
     * @return void
     */
    public function setConnectTimeout($second)
    {
        $this->setOpt( CURLOPT_CONNECTTIMEOUT, $second);
    }

    public function setReferer($refer)
    {
        $this->setOpt( CURLOPT_REFERER, $refer );
    }

    /**
     * 轉換 'hash' 格式 array 成 array (for CURLOPT_HTTPHEADER)
     * 將 array("Accept" => "text/html", "Accept-Encoding" => "compress, gzip") 形式
     * 轉換成 array("Accept: text/html", "Accept-Encoding: compress, gzip")
     *
     * @param  array $headers
     * @return array
     */
    private static function headerArrayToString($headers)
    {
        $formated_headers = array();

        if (is_array($headers) > 0){
            foreach($headers as $key => $val){
                if(gettype($key) == 'integer'){
                    $formated_headers[] = $val;
                }else{
                    $formated_headers[] = "{$key}: {$val}";
                }
            }
        }else{
            throw new Exception("Input argument expect to be an array");
        }

        return $formated_headers;
    }

    /**
     * 啟用 cookie (request + response)
     * 若有傳入路徑則將發送及儲存cookie的檔案設定為路徑檔案,若傳入路徑為空則不進行動作
     *
     * @param string $cookie_path
     * @return void
     */
    public function enableCookie()
    {
        #already enabled
        if (!empty($this->cookie_file)) {
            return true;
        }

        if (empty($this->cookie_file)) {
            throw new Exception("Assign cookie file first.");
        }

        $this->setReceiveCookieFile($this->cookie_file);
        $this->setSendCookieFile($this->cookie_file);
    }

    public function disableCookie()
    {
        $this->setCookieFile(null);
        $this->setReceiveCookieFile(null);
        $this->setSendCookieFile(null);
    }

    public function cleanupCookie()
    {
        if (!empty($this->cookie_file) and is_file($this->cookie_file)) {
            unlink($this->cookie_file);
        }
    }

    protected function setCookieFile($cookie_file)
    {
        if (!is_file($cookie_file)) {
            throw new Exception("The given path is not a valid file path.");
        }

        $this->cookie_file = $cookie_file;
    }

    public function getCookieFile()
    {
        return $this->cookie_file;
    }

    //protected function get_default_cookie_path()
    //{
    //return self::COOKIE_TMP_DIR.sprintf('%s_%s.jar', time(), md5(rand(10000,99999)));
    //}

    /**
     * 指定檔案以儲存 response 中 set-cookie 的值
     *
     * @param string $path cookie的存放路徑
     * @return void
     */
    public function setReceiveCookieFile($path)
    {
        $this->setOpt(CURLOPT_COOKIEJAR, $path);
    }

    /**
     * 啟用 CURLOPT_COOKIEFILE
     * 發送 request 時夾帶指定檔案中的 cookie 資訊
     *
     * @param string $path cookie的存放路徑
     * @return void
     */
    public function setSendCookieFile($path)
    {
        $this->setOpt(CURLOPT_COOKIEFILE, $path);
    }

    /**
     * 取得 request headers
     * 取得 request headers ，curl_exec前須開啟(requestHeaderOn())
     *
     * @param void
     * @return array
     */
    public function getRequestHeaders()
    {
        return curl_getinfo($this->_curl_handler, CURLINFO_HEADER_OUT);
    }

    /**
     * 取得 curl_exec 後的 http status code
     *
     * @param void
     * @return int
     */
    public function getHttpCode()
    {
        return curl_getinfo($this->_curl_handler, CURLINFO_HTTP_CODE);
    }

    public function getCurlInfo()
    {
        return curl_getinfo($this->_curl_handler);
    }

    /**
     * 取得 curl_error
     *
     * @param void
     * @return string return empty string when no error occur, otherwise return error msg from curl_erro
     */
    public function getCurlError()
    {
        return curl_error($this->_curl_handler);
    }

    /**
     * 取得目前的http request method
     *
     * @param void
     * @return string post/get/etc (put,delete 目前沒有用到，沒有實作)
     */
    public function getHttpMethod()
    {
        if($this->getOpt(CURLOPT_POST) === 1){
            return "POST";
        }elseif($this->getOpt(CURLOPT_HTTPGET) === 1){
            return "GET";
        }else{
            return $this->getOpt(CURLOPT_CUSTOMREQUEST);
        }
    }

    /**
     * make a GET request
     *
     * @param string $url 目標網址
     * @param array  $params 需要傳遞的參數(還沒實作..)
     * @return mixed 目標網址回傳的資料
     */
    public function get($url , $params = array())
    {
        $this->resetHttpMethodOpt();
        $this->setUrl($url);

        if(!empty($params)){
            $url = $this->getUrl();
            $url_parts = explode("?",$url);
            $url = $url_parts[0];

            if(!empty($url_parts[1])){
                $url .= "?".http_build_query($params)."&".$url_parts[1];
            }else{
                $url .= "?".http_build_query($params);
            }

            $this->setUrl($url);
        }

        $this->setOpt(CURLOPT_HTTPGET, true);

        return $this->exec();
    }

    private function resetHttpMethodOpt()
    {
        $this->unsetOpt(CURLOPT_HTTPGET);
        $this->unsetOpt(CURLOPT_POST);
        $this->unsetOpt(CURLOPT_POSTFIELDS);
        $this->unsetOpt(CURLOPT_CUSTOMREQUEST);
    }

    /**
     * make a POST request
     *
     * @param string $url 目標網址
     * @param array  $params 需要傳遞的參數
     * @return mixed 目標網址回傳的資料
     */
    public function post($url, $payload = array())
    {
        $this->resetHttpMethodOpt();
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $payload);
        $this->setUrl($url);

        return $this->exec();
    }

    public function put($url, $payload = array())
    {
        $this->resetHttpMethodOpt();
        $this->setOpt(CURLOPT_POSTFIELDS, $payload);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, "PUT");
        $this->setUrl($url);

        return $this->exec();
    }

    private function exec()
    {
        assert($this->isUrlSet());
        $this->setOpt(CURLOPT_HTTPHEADER, self::headerArrayToString($this->_headers));
        curl_setopt_array($this->getCurlHandler(), $this->_opts);
        $this->response = curl_exec($this->getCurlHandler());

        return $this->getCurlHandler();
    }

    protected function getCurlHandler()
    {
        if (empty($this->_curl_handler)) {
            $this->_curl_handler = curl_init();
        }

        return $this->_curl_handler;
    }

    public function setContentType(String $content_type)
    {
        $this->setHeader('Content-Type', $content_type);
    }
}
