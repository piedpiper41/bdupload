<?php
class bdupload {
	public $err;
	public $server_id = 'u3';
	private $ch, $location;
	private $debug = 0;

	function __construct ($user, $pass) {
		$ch = curl_init();
    $cookie= 'bdupload.txt';
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("upgrade-insecure-requests:1","referer:https://bdupload.info/"));
		if ($this->debug) {
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$this->verbose = fopen('php://temp', 'rw+');
			curl_setopt($ch, CURLOPT_STDERR, $this->verbose);
		}
		$this->ch = $ch;

		$params = array(
			'login'     => $user,
			'password'     => $pass,
			'op' => 'login',
      'redirect' => 'https://bdupload.info/'
		);
		$query = http_build_query($params);
		$content = $this->request('https://bdupload.info/',$query);
	}


	public function upload ($video) {
    $content = $this->request("https://bdupload.info/?op=upload");
		list($action, $params) = $this->rip_form($content);
		$fname = basename($video);
		$fsize = filesize($video);
    $videopath = realpath($video);
    $params['file_0'] = "@$videopath";
    $params['file_name'] = $fname;
    $params['cat_id'] = 3;
    $params['fakefilepc'] = $fname;
    $params['fld_id'] = 0;
    $params['file_descr'] = "description";
    $params['file_public'] = "";
    $upload_url = $action;
		$options = array(
			CURLOPT_URL => $upload_url,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => array("Content-Type:multipart/form-data"),
			CURLOPT_POSTFIELDS => $params,
			CURLOPT_INFILESIZE => $fsize,
			CURLOPT_RETURNTRANSFER => true
		);
		curl_setopt_array($this->ch, $options);
		$content = curl_exec($this->ch);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array());
		$info = curl_getinfo($this->ch);
    $datas = json_decode($content,1);
    $edit = $this->request('https://bdupload.info/?op=file_edit&file_code='.$datas[0]["file_code"]);
    list($e_action, $e_params) = $this->rip_form($edit);
    $e_params["file_name"] = $fname;
    $e_params["file_descr"] = "description";
    curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Type:application/x-www-form-urlencoded","upgrade-insecure-requests:1","referer:https://bdupload.info/?op=file_edit&file_code=".$datas[0]["file_code"],"user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36"));
    $this->request('https://bdupload.info/?op=file_edit&file_code='.$datas[0]["file_code"],http_build_query($e_params));
    if(!empty($datas[0]["file_code"])){
      return $datas[0]["file_code"];
    }

	}


	private function request($url, $post_data = '') {
		$post = false;
		if ($post_data) {
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_data);
			$post = true;
		}
		curl_setopt($this->ch, CURLOPT_POST, $post);
		curl_setopt($this->ch, CURLOPT_URL, $url);
		$content = curl_exec($this->ch);

		$info = curl_getinfo($this->ch);
		$this->location = $info['url'];
		$this->info = $info;
		return $content;
	}

	private function rip_form($content, $base = '') {
		$action = (preg_match('/action="([^"]*)"/si', $content, $matches)) ? $base.$matches[1] : '';
		$params = array();
		if (preg_match_all('/(<(?:input|select)[^>]*?>)/si', $content, $matches)) {
			$inputs = $matches[1];
			foreach ($inputs as $input) {
				$name = (preg_match('/name="([^"]*)"/si', $input, $matches)) ? $matches[1] : '';
				$value = (preg_match('/value="([^"]*)"/si', $input, $matches)) ?  html_entity_decode($matches[1], ENT_COMPAT, 'UTF-8') : '';
				$params[$name] = $value;
			}
		}
		return array($action, $params);
	}
}

?>
