<?php
class CurlWrapperException extends RecoverableException {}

class CurlWrapper
{
  const DEFAULT_USER_AGENT = 'Ruten CurlWrapper';
  //const COOKIE_TMP_DIR = '/tmp/';

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
    $this->_init_opts();
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
  private function _init_opts()
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
      'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
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
  public function set_opts_array($opts)
  {
    foreach($opts as $key => $val){
      $this->set_opts($key, $val);
    }
  }

  /**
   * 設定Curl參數
   *
   * @param int $key integer or CURLOPT_* const
   * @param int $val value to set
   * @return void
   */
  public function set_opts($key, $val)
  {
    $this->_opts[$key] = $val;
  }

  /**
   * 取得 CURLOPT_* 設定值
   *
   * @param int $key integer or CURLOPT_* const
   * @return bool|int|string 回傳指定參數的值
   */
  public function get_opts($key = null)
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
  public function set_user_agent($user_agent)
  {
    $this->set_opts(CURLOPT_USERAGENT, $user_agent);
  }

  /**
   * 取得 CURLOPT_* 設定值
   *
   * @param int $key integer or CURLOPT_* const
   * @return bool|int|string 回傳指定參數的值
   */
  public function get_user_agent()
  {
    return $this->get_opts(CURLOPT_USERAGENT);
  }

  /**
   * 設定 curl 的 request url
   *
   * @param string $url
   * @return void
   */
  public function set_url($url)
  {
    $this->set_opts(CURLOPT_URL, $url);
  }

  /**
   * 取得目前設定的 url
   *
   * @param void
   * @return string 目前設定的url
   */
  public function get_url()
  {
    return $this->get_opts(CURLOPT_URL);
  }

  /**
   * 檢查url是否已經設定
   *
   * @param void
   * @return bool return true if url set, else false
   */
  private function _is_url_set()
  {
    $url = $this->get_url();
    return !empty($url);
  }

  /**
   * 設定 http header
   * 可傳入array()
   *
   * @param array $header
   * @return bool return true if url set, else false
   */
  public function set_http_headers($headers, $clean_default = false)
  {
    if($clean_default == true){
      $this->_headers = $headers;
    }else{
      foreach($headers as $header){
        list($key, $val) = $header;
        $this->set_header($key, $val);
      }
    }
  }


  public function set_header($key, $val)
  {
    $this->_headers[$key] = $val;
  }

  public function verbose_on()
  {
    $this->set_opts(CURLOPT_VERBOSE, true);
  }

  /**
   * Turn off host SSL verify
   *
   * @param void
   * @return void
   */
  public function verbose_off()
  {
    $this->set_opts(CURLOPT_VERBOSE, false);
  }

  /**
   * Turn on http request header output
   *
   * @param void
   * @return void
   */
  public function request_header_on()
  {
    $this->set_opts(CURLINFO_HEADER_OUT, true);
  }

  /**
   * Turn off request header
   *
   * @param void
   * @return void
   */
  public function request_header_off()
  {
    $this->set_opts(CURLINFO_HEADER_OUT, false);
  }

  public function response_header_on()
  {
    $this->set_opts(CURLOPT_HEADER, true);
  }

  /**
   * Turn off SSL verify
   *
   * @param void
   * @return void
   */
  public function ssl_verify_off()
  {
    $this->ssl_verify_host_off();
    $this->ssl_verify_peer_off();
  }

  /**
   * Turn off host SSL verify
   *
   * @param void
   * @return void
   */
  public function ssl_verify_host_off()
  {
    $this->set_opts(CURLOPT_SSL_VERIFYHOST, false);
  }

  /**
   * Turn off peer SSL verify
   *
   * @param void
   * @return void
   */
  public function ssl_verify_peer_off()
  {
    $this->set_opts(CURLOPT_SSL_VERIFYPEER, false);
  }

  /**
   * Turn on curl follow location (http header redirect)
   *
   * @param void
   * @return void
   */
  public function follow_location_on()
  {
    $this->set_opts(CURLOPT_FOLLOWLOCATION, true);
  }

  /**
   * Turn off curl follow location (http header redirect)
   *
   * @param void
   * @return void
   */
  public function follow_location_off()
  {
    $this->set_opts(CURLOPT_FOLLOWLOCATION, false);
  }

  /**
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
  public function set_timeouts($second)
  {
    $this->set_opts( CURLOPT_TIMEOUT, $second);
    $this->set_opts( CURLOPT_CONNECTTIMEOUT, $second);
  }

  /**
   * Set timeout for CURLOPT_TIMEOUT
   *
   * @param int $second timeout seconds
   * @return void
   */
  public function set_timeout($second)
  {
    $this->set_opts( CURLOPT_TIMEOUT, $second);
  }

  /**
   * Set timeout for CURLOPT_CONNECTTIMEOUT
   *
   * @param int $second timeout seconds
   * @return void
   */
  public function set_connect_timeout($second)
  {
    $this->set_opts( CURLOPT_CONNECTTIMEOUT, $second);
  }

  public function set_referer($refer)
  {
    $this->set_opts( CURLOPT_REFERER, $refer );
  }

  /**
   * 轉換 'hash' 格式 array 成 array (for CURLOPT_HTTPHEADER)
   * 將 array("Accept" => "text/html", "Accept-Encoding" => "compress, gzip") 形式
   * 轉換成 array("Accept: text/html", "Accept-Encoding: compress, gzip")
   *
   * @param  array $headers
   * @return array
   */
  private static function HeaderArrayToString($headers)
  {
    $formated_headers = array();

    if(is_array($headers) and count($headers) > 0){
      foreach($headers as $key => $val){
        if(gettype($key) == 'integer'){
          $formated_headers[] = $val;
        }else{
          $formated_headers[] = "{$key}: {$val}";
        }
      }
    }else{
      #Todo: Error handle here?
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
  public function enable_cookie()
  {
    #already enabled
    if (!empty($this->cookie_file)) {
      return true;
    }

    if (empty($this->cookie_file)) {
      throw new CurlWrapperException("Assign cookie file first.");
    }

    $this->set_receive_cookie_file($this->cookie_file);
    $this->set_send_cookie_file($this->cookie_file);
  }

  public function disable_cookie()
  {
    $this->set_cookie_file(null);
    $this->set_receive_cookie_file(null);
    $this->set_send_cookie_file(null);
  }

  public function cleanup_cookie()
  {
    if (!empty($this->cookie_file) and is_file($this->cookie_file)) {
      unlink($this->cookie_file);
    }
  }

  protected function set_cookie_file($cookie_file)
  {
    if (!is_file($cookie_file)) {
      throw new CurlWrapperException("The given path is not a valid file path.");
    }

    $this->cookie_file = $cookie_file;
  }

  public function get_cookie_file()
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
  public function set_receive_cookie_file($path)
  {
    $this->set_opts(CURLOPT_COOKIEJAR, $path);
  }

  /**
   * 啟用 CURLOPT_COOKIEFILE
   * 發送 request 時夾帶指定檔案中的 cookie 資訊
   *
   * @param string $path cookie的存放路徑
   * @return void
   */
  public function set_send_cookie_file($path)
  {
    $this->set_opts(CURLOPT_COOKIEFILE, $path);
  }

  /**
   * 取得 request headers
   * 取得 request headers ，curl_exec前須開啟(request_header_on())
   *
   * @param void
   * @return array
   */
  public function get_request_headers()
  {
    return curl_getinfo($this->_curl_handler, CURLINFO_HEADER_OUT);
  }

  /**
   * 取得 curl_exec 後的 http status code
   *
   * @param void
   * @return int
   */
  public function get_http_code()
  {
    return curl_getinfo($this->_curl_handler, CURLINFO_HTTP_CODE);
  }

  public function get_curl_info()
  {
    return curl_getinfo($this->_curl_handler);
  }

  /**
   * 取得 curl_error
   *
   * @param void
   * @return string return empty string when no error occur, otherwise return error msg from curl_erro
   */
  public function get_curl_error()
  {
    return curl_error($this->_curl_handler);
  }

  /**
   * 取得目前的http request method
   *
   * @param void
   * @return string post/get/etc (put,delete 目前沒有用到，沒有實作)
   */
  public function get_http_method()
  {
    if($this->get_opts(CURLOPT_POST) === 1){
      return "POST";
    }elseif($this->get_opts(CURLOPT_HTTPGET) === 1){
      return "GET";
    }else{
      return "ETC";
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
    $this->set_url($url);

    if(!empty($params)){
      $url = $this->get_url();
      $url_parts = explode("?",$url);
      $url = $url_parts[0];

      if(!empty($url_parts[1])){
        $url .= "?".http_build_query($params)."&".$url_parts[1];
      }else{
        $url .= "?".http_build_query($params);
      }

      $this->set_url($url);
    }

    //$this->set_opts(CURLOPT_CUSTOMREQUEST, null);
    $this->set_opts(CURLOPT_POST, null);
    $this->set_opts(CURLOPT_POSTFIELDS, null);
    unset($this->_opts[CURLOPT_POST]);
    unset($this->_opts[CURLOPT_POSTFIELDS]);
    $this->set_opts(CURLOPT_HTTPGET, true);

    return $this->exec_curl();
  }

  /**
   * make a POST request
   *
   * @param string $url 目標網址
   * @param array  $params 需要傳遞的參數
   * @return mixed 目標網址回傳的資料
   */
  public function post($url, $params = array())
  {
    if (is_array($params) or is_object($params)) {
      $post_data = http_build_query($params);
    } else {
      $post_data = $params;
    }

    //$this->set_opts(CURLOPT_CUSTOMREQUEST, null);
    $this->set_opts(CURLOPT_HTTPGET, null);
    unset($this->_opts[ CURLOPT_HTTPGET ]);
    $this->set_opts(CURLOPT_POST, true);
    $this->set_opts(CURLOPT_POSTFIELDS, $post_data);
    $this->set_url($url);

    return $this->exec_curl();
  }

  public function exec_curl()
  {
    assert($this->_is_url_set());
    $this->set_opts(CURLOPT_HTTPHEADER, self::HeaderArrayToString($this->_headers));
    curl_setopt_array($this->get_curl_handler(), $this->_opts);
    $result = curl_exec($this->get_curl_handler());

    return $result;
  }

  protected function get_curl_handler()
  {
    if (empty($this->_curl_handler)) {
      $this->_curl_handler = curl_init();
    }

    return $this->_curl_handler;
  }
}
