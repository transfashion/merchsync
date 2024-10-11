<?php namespace TransFashion\MerchSync;

class SyncBase {

	protected readonly string $TransBrowserUrl;

	function __construct() {
		$this->TransBrowserUrl = Configuration::Get('TransBrowserUrl');
	}


	protected function getDataFromUrl(string $endpoint) : array {
		try {
			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 60); //timeout in seconds
			// curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			$respond = curl_exec($ch);
			curl_close($ch);	

			$res = json_decode($respond, true);
			if (json_last_error()!=JSON_ERROR_NONE) {
				throw new \Exception(json_last_error_msg(), json_last_error());
			}

			if (!array_key_exists('code', $res)) {
				throw new \Exception('code is not exist in json response', 9);
			}

			if ($res['code']!=0) {
				throw new \Exception($res['message'], $res['code']);
			}

			$json_data = gzuncompress(base64_decode($res['data']));
			$data = json_decode($json_data, true);

			return $data;
		} catch (\Exception $ex) {
			throw $ex;
		}
	}
}