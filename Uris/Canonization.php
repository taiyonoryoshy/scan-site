<?php

class Model_Scan_Uris_Canonization extends Model_Scan_Uris_Uris {

	private $_uri;
	private $_currentUri;
	private $_baseUri;
	private $_parseUri;
	private $_parseCurrentUri;

	public function canonization($uri, $currentUri = '', $baseUri = null) {
		$this->_uri = trim($uri);
		$this->_parseUri = parse_url($this->_uri);
		$this->_currentUri = $currentUri;
		$this->_baseUri = $baseUri;
		$this->_parseCurrentUri = parse_url($currentUri);

		$_parseUri = parse_url($this->_fetchAbsoluteUri());
		$_parseUri['path'] = isset($_parseUri['path']) ? $this->_getCanonizedPath($_parseUri['path']) : '';

		return $this->_pasteUri($_parseUri);
	}

	private function _fetchAbsoluteUri() {
		if (isset($this->_parseUri['host']) && !empty($this->_parseUri['host'])) {
			if (isset($this->_parseUri['scheme']) && !empty($this->_parseUri['scheme'])) {
				return $this->_uri;
			} else {
				return $this->_parseCurrentUri['scheme'] . ':' . $this->_uri;
			}
		} else {
			if (empty($this->_uri)) {
				return $this->_currentUri;
			} else {
				if ($this->_uri[0] == '/') {
					if (isset($this->_uri[1]) && $this->_uri[1] == '/') {
						exit;
						return $this->_parseCurrentUri['scheme'] . ':' . $this->_uri;
					} else {
						return $this->_targetUri . $this->_uri;
					}
				} else {
					if ($this->_baseUri != null) {
						return $this->_baseUri . $this->_uri;
					} else {

						if (!isset($this->_parseCurrentUri['path']) || $this->_currentUri[mb_strlen($this->_currentUri, self::ENCODING) - 1] == '/') {
							return $this->_pasteUri($this->_currentUri, true, false) . '/' . $this->_uri;
						} else {
							$parsePath = mb_split('[/]', $this->_parseCurrentUri['path']);
							unset($parsePath[count($parsePath) - 1]);
							$this->_parseCurrentUri['path'] = $this->_pastePath($parsePath);
							return $this->_pasteUri($this->_pasteUri($this->_parseCurrentUri), true, false) . $this->_uri;
						}
					}
				}
			}
		}
	}

	private function _getCanonizedPath($path) {
		$path = mb_substr($path, 1, mb_strlen($path, self::ENCODING));
		while (mb_strlen($path, self::ENCODING) >= 2) {
			if ((mb_strlen($path, self::ENCODING) >= 3) && ($path[0] == '.' && $path[1] == '.' && $path[2] == '/')) {
				$path = mb_substr($path, 3, mb_strlen($path, self::ENCODING), self::ENCODING);
			} else {
				if ($path[0] == '.' && $path[1] == '/') {
					$path = mb_substr($path, 2, mb_strlen($path, self::ENCODING), self::ENCODING);
				}
			}
			break;
		}
		$parsePath = $this->_clearArrayOfEmptyElements(mb_split('[/]', $path));
		foreach ($parsePath as $key => $value) {
			if ($parsePath[$key] == '.') {
				unset($parsePath[$key]);
			}
		}
		foreach ($parsePath as $key => $value) {
			if ($parsePath[$key] == '..') {
				unset($parsePath[$key]);
				for ($m = 1; $key - $m >= 0; $m++) {
					if (isset($parsePath[$key - $m])) {
						unset($parsePath[$key - $m]);
						break;
					}
				}
			}
		}
		$canonization = '/';
		foreach ($parsePath as $value) {
			$canonization.=$value . '/';
		}

		if ($this->_isPathToFile($path)) {
			return mb_substr($canonization, 0, mb_strlen($canonization, self::ENCODING) - 1, self::ENCODING);
		} else {
			return $canonization;
		}
	}

	private function _isPathToFile($path) {
		if (!empty($path)) {
			$parsePath = mb_split('[/]', $path);
			if (mb_strpos($parsePath[count($parsePath) - 1], '.', 0, self::ENCODING)) {
				return true;
			}
		}
		return false;
	}

	private function _pastePath($parsePath) {
		$canonization = '/';
		foreach ($parsePath as $value) {
			$canonization.=$value . '/';
		}
		return $canonization;
	}

}
