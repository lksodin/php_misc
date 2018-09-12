<?
class WebPageFetcherException extends RecoverableException {}

class WebPageFetcher
{
  const DEFAULT_USER_AGENT = 'Curl Wrapper';

  private $_opts = array();
  private $_headers = array();
  private $_curl_handler = null;
  private $cookie_file = null;

  /**
   * get default curl opts
   *
   * @param void
   * @return array default curl opts
   */
  private static function getDefaultOpts()
  {
    return array(
      CURLOPT_USERAGENT      => self::DEFAULT_USER_AGENT,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 20,
      CURLOPT_TIMEOUT        => 20,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2
    );
  }

  /**
   * get default http headers
   *
   * @param void
   * @return array default http headers
   */
  private static function getDefaultHeaders()
  {
    return array(
      'Connection'      => 'keep-alive',
      'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Encoding' => 'gzip,deflate',
      'Accept-Language' => 'en-US,en;q=0.5'
    );
  }

  /**
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
      throw new WebPageFetcherException("Input expected to be an array");
    }

    return $formated_headers;
  }


  /**
   * 初始化Curl預設設定
   *
   * @param void
   * @return void
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
    $this->close_connection();
  }

  /**
   * close curl session
   *
   * @param void
   * @return string $result
   */
  public function close_connection()
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
    $this->set_opt_array(self::getDefaultOpts());
    $this->set_http_headers(self::getDefaultHeaders());
  }

  /**
   * 大量設定Curl參數
   * 可傳入array大量設定參數, array範例參考 _init_opts
   *
   * @param array $opts curl options
   * @return void
   */
  public function set_opt_array($opts)
  {
    foreach($opts as $key => $val){
      $this->set_opt($key, $val);
    }
  }

  /**
   * 設定Curl參數
   *
   * @param int $key integer or CURLOPT_* const
   * @param int $val value to set
   * @return void
   */
  private function set_opt($key, $val)
  {
    $this->_opts[$key] = $val;
  }

  /**
   * 取得 CURLOPT_* 設定值
   *
   * @param int $key integer or CURLOPT_* const
   * @return bool|int|string 回傳指定參數的值
   */
  private function get_opt($key)
  {
    if(isset($this->_opts[$key])){
      return $this->_opts[$key];
    }else{
      return null;
    }
  }

  /**
   * 移除指定opts設定
   *
   * @param int $key integer or CURLOPT_* const
   * @return bool|int|string 回傳指定參數的值
   */
  public function remove_opt($key)
  {
    unset($this->_opts[$key]);
  }


  /**
   * 設定 user_agent
   *
   * @param string $user_agent User agent string
   * @return void
   */
  public function set_user_agent($user_agent)
  {
    $this->set_opt(CURLOPT_USERAGENT, $user_agent);
  }

  /**
   * 取得 CURLOPT_* 設定值
   *
   * @param int $key integer or CURLOPT_* const
   * @return bool|int|string 回傳指定參數的值
   */
  public function get_user_agent()
  {
    return $this->get_opt(CURLOPT_USERAGENT);
  }

  /**
   * 設定 curl 的 request url
   *
   * @param string $url
   * @return void
   */
  public function set_url($url)
  {
    $this->set_opt(CURLOPT_URL, $url);
  }

  /**
   * 取得目前設定的 url
   *
   * @param void
   * @return string 目前設定的url
   */
  public function get_url()
  {
    return $this->get_opt(CURLOPT_URL);
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
      foreach($headers as $key => $val){
        $this->_headers[$key] = $val;
      }
    }
  }

  public function verbose_on()
  {
    $this->set_opt(CURLOPT_VERBOSE, true);
  }

  /**
   * Turn off host SSL verify
   *
   * @param void
   * @return void
   */
  public function verbose_off()
  {
    $this->set_opt(CURLOPT_VERBOSE, false);
  }

  /**
   * Turn on http header output
   *
   * @param void
   * @return void
   */
  public function header_ouput_on()
  {
    $this->set_opt(CURLINFO_HEADER_OUT, true);
  }

  /**
   * Turn off host SSL verify
   *
   * @param void
   * @return void
   */
  public function header_ouput_off()
  {
    $this->set_opt(CURLINFO_HEADER_OUT, false);
  }

  /**
   * Turn off host SSL verify
   *
   * @param void
   * @return void
   */
  public function ssl_verify_host_off()
  {
    $this->set_opt(CURLOPT_SSL_VERIFYHOST, false);
  }

  /**
   * Turn off peer SSL verify
   *
   * @param void
   * @return void
   */
  public function ssl_verify_peer_off()
  {
    $this->set_opt(CURLOPT_SSL_VERIFYPEER, false);
  }

  /**
   * Turn on curl follow location (http header redirect)
   *
   * @param void
   * @return void
   */
  public function follow_location_on()
  {
    $this->set_opt(CURLOPT_FOLLOWLOCATION, true);
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
    $this->set_opt( CURLOPT_TIMEOUT, $second);
    $this->set_opt( CURLOPT_CONNECTTIMEOUT, $second);
  }

  /**
   * Set timeout for CURLOPT_TIMEOUT
   *
   * @param int $second timeout seconds
   * @return void
   */
  public function set_timeout($second)
  {
    $this->set_opt( CURLOPT_TIMEOUT, $second);
  }

  /**
   * Set timeout for CURLOPT_CONNECTTIMEOUT
   *
   * @param int $second timeout seconds
   * @return void
   */
  public function set_connect_timeout($second)
  {
    $this->set_opt( CURLOPT_CONNECTTIMEOUT, $second);
  }

  /**
   * 簡易設定 cookie 路徑 (request + response) 若接收與送出的cookie檔案要分開請個別指定
   * 若有傳入路徑則將發送及儲存cookie的檔案設定為路徑檔案
   *
   * @param string $cookie_path
   * @return void
   */
  public function set_cookie_path($cookie_path)
  {
    if (!is_file($cookie_path)) {
      throw new WebPageFetcherException("Input file path is not valid file.");
    }

    $this->cookie_file = $cookie_path;
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
    if(empty($this->cookie_file)){
      throw new WebPageFetcherException("cookie_file not set. Call set_cookie_path first.");
    }

    $this->set_cookie_jar($this->cookie_file);
    $this->set_cookie_file($this->cookie_file);
  }

  /**
   * 指定檔案以儲存 response 中 set-cookie 的值
   *
   * @param string $path cookie的存放路徑
   * @return void
   */
  public function set_cookie_jar($path)
  {
    $this->set_opt(CURLOPT_COOKIEJAR, $path);
  }

  /**
   * 啟用 CURLOPT_COOKIEFILE
   * 發送 request 時夾帶指定檔案中的 cookie 資訊
   *
   * @param string $path cookie的存放路徑
   * @return void
   */
  public function set_cookie_file($path)
  {
    $this->set_opt(CURLOPT_COOKIEFILE, $path);
  }

  /**
   * Clear cookie data
   *
   * @param void
   * @return void
   */
  public function clear_cookie()
  {
    $this->close_connection();

    if (is_file($this->cookie_file)) {
      unlink($this->cookie_file);
    }
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
    if($this->get_opt(CURLOPT_POST) === 1){
      return "POST";
    }elseif($this->get_opt(CURLOPT_HTTPGET) === 1){
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
  public function get($url = null, $params = array())
  {
    if(!empty($url)){
      $this->set_url($url);
    }

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

    //$this->set_opt(CURLOPT_CUSTOMREQUEST, null);
    $this->remove_opt(CURLOPT_POST);
    $this->remove_opt(CURLOPT_POSTFIELDS);
    $this->set_opt(CURLOPT_HTTPGET, true);

    return $this->exec_curl();
  }

  /**
   * make a POST request
   *
   * @param string $url 目標網址
   * @param array  $params 需要傳遞的參數
   * @return mixed 目標網址回傳的資料
   */
  public function post($url = null, $params = array())
  {
    if (is_array($params) or is_object($params)) {
      $post_data = http_build_query($params);
    } else {
      $post_data = $params;
    }

    //$this->set_opt(CURLOPT_CUSTOMREQUEST, null);
    $this->remove_opt(CURLOPT_HTTPGET);
    $this->set_opt(CURLOPT_POST, true);
    $this->set_opt(CURLOPT_POSTFIELDS, $post_data);
    $this->set_url($url);

    return $this->exec_curl();
  }

  /**
   * execute the HTTP request
   *
   * @param void
   * @return string $result
   */
  public function exec_curl()
  {
    if(!$this->_is_url_set()){
      throw new WebPageFetcherException('Curl target uri empty!');
    }

    $this->_curl_handler = curl_init();
    $this->set_opt(CURLOPT_HTTPHEADER, self::HeaderArrayToString($this->_headers));
    curl_setopt_array($this->_curl_handler, $this->_opts);
    $result = curl_exec($this->_curl_handler);

    return $result;
  }

  /**
   * 取得 request headers
   * 取得 request headers ，curl_exec前須開啟(header_ouput_on())
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

  /**
   * 取得 curl_exec 後的 info array
   *
   * @param void
   * @return mixed return false on curl fail, return an array otherwise
   */
  public function get_info()
  {
    return curl_getinfo($this->_curl_handler);
  }

}
?>
