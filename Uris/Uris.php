<?php

use Etechnika\IdnaConvert\IdnaConvert as IdnaConvert;

class Uris_Uris {

	const ENCODING = 'UTF-8';

	protected $_targetUri;
	protected $_parseTargetUri;
	protected $_targetHost;

	public function _pasteUri($parseUri, $wihtPath = true, $withQuery = true) {
		if (!is_array($parseUri)) {
			$parseUri = parse_url($parseUri);
		}
		$uri = $parseUri['scheme'] . '://';
		if ((isset($parseUri['user']) && !empty($parseUri['user'])) && (isset($parseUri['pass']) && !empty($parseUri['pass']))) {
			$uri.=$parseUri['user'] . ':' . $parseUri['pass'] . '@';
		}
		$uri.=$parseUri['host'];
		if (isset($parseUri['port']) && !empty($parseUri['port']) && $parseUri['port'] != 80) {
			$uri.=':' . $parseUri['port'];
		}
		if ($wihtPath) {
			if (isset($parseUri['path']) && !empty($parseUri['path'])) {
				$uri.=$parseUri['path'];
			}
		}
		if ($withQuery) {
			if (isset($parseUri['query']) && !empty($parseUri['query'])) {
				$uri.='?' . $parseUri['query'];
			}
		}
		return $uri;
	}

	protected function _getIdnEncodeHost($host) {
		return IdnaConvert::encodeString($host);
	}

	protected function _clearArrayOfEmptyElements($array) {
		foreach ($array as $key => $value) {
			if (empty($value)) {
				unset($array[$key]);
			}
		}
		return $array;
	}

	public function setTargetUri($targetUri) {
		$this->_targetUri = $targetUri;
	}

	public function getTargetUri() {
		return $this->_targetUri;
	}

	public function setParseTargetUri($parseTargetUri) {
		$this->_parseTargetUri = $parseTargetUri;
	}

	public function getParseTargetUri() {
		return $this->_parseTargetUri;
	}

	public function setTargetHost($targetHost) {
		$this->_targetHost = $this->_getIdnEncodeHost($targetHost);
	}

	public function getTargetHost() {
		return $this->_targetHost;
	}

}
