<?php

defined('SCAN_SITE_PATH') || define('SCAN_SITE_PATH', realpath(dirname(__FILE__)));

set_include_path(implode(PATH_SEPARATOR, array(
	get_include_path(),
	SCAN_SITE_PATH . "/library"
)));

require_once(SCAN_SITE_PATH . "/library/Zend/Http/Client.php");
require_once(SCAN_SITE_PATH . "/library/idna_convert/idna_convert.class.php");
require_once(SCAN_SITE_PATH . "/Uris/Uris.php");
require_once(SCAN_SITE_PATH . "/Uris/Canonization.php");
require_once(SCAN_SITE_PATH . "/Uris/Filter.php");
require_once(SCAN_SITE_PATH . "/Uris/Encoding.php");

class ScanSite_Scan extends ScanSite_Uris_Uris {

	const XHTML_NS = "http://www.w3.org/1999/xhtml";
	const ERROR_PARSE = "Error parse xml";

	private $_allowContentTypePage = array('text/html', 'application/xml', 'application/xhtml+xml');
	private $_canonization;
	private $_filter;
	private $_encoding;
	private $_configHttpClient = array('maxredirects' => 2, 'keepalive' => true, 'timeout' => 30);
	private $_uri;

	public function __construct($uri) {

		$this->_encoding = new ScanSite_Uris_Encoding();

		$this->_uri = $this->_encoding->encoding(trim($uri));

		$this->_targetUri = $this->_pasteUri($this->_uri, false, false);
		$this->_parseTargetUri = parse_url($this->_targetUri);
		$this->_targetHost = $this->_parseTargetUri['host'];

		$canonization = new ScanSite_Uris_Canonization();
		$canonization->setTargetUri($this->_targetUri);
		$this->_canonization = $canonization;

		$filter = new ScanSite_Uris_Filter();
		$filter->setTargetHost($this->_targetHost);
		$this->_filter = $filter;
	}

	public function scan() {
		$result = array();
		if ($this->_isAllowContentType($this->_uri)) {

			$client = new Zend_Http_Client($this->_uri, $this->_configHttpClient);

			$response = $client->request();

			$fromCharset = $this->_getCharsetHtml($this->_getContentType($this->_uri));
			if (!empty($fromCharset) && mb_strtoupper($fromCharset, self::ENCODING) != self::ENCODING) {
				$body = iconv($fromCharset, self::ENCODING, $response->getBody());
			} else {
				$body = $response->getBody();
			}

			$html = $this->_getValidHTML($body);

			if ($html) {
				$links = $this->_getLinksFromPage($html, $this->_uri);
				$forms = $this->_getFormsFromPage($html, $this->_uri);
				$result['links'] = $links;
				$result['forms'] = $forms;
				return $result;
			}
		}
		return array();
	}

	public function scanLinks() {
		if ($this->_isAllowContentType($this->_uri)) {

			$client = new Zend_Http_Client($this->_uri, $this->_configHttpClient);

			$response = $client->request();

			$fromCharset = $this->_getCharsetHtml($this->_getContentType($this->_uri));
			if (!empty($fromCharset) && mb_strtoupper($fromCharset, self::ENCODING) != self::ENCODING) {
				$body = iconv($fromCharset, self::ENCODING, $response->getBody());
			} else {
				$body = $response->getBody();
			}

			$html = $this->_getValidHTML($body);

			if ($html) {
				return $this->_getLinksFromPage($html, $this->_uri);
			}
		}
		return array();
	}

	public function scanForms() {
		if ($this->_isAllowContentType($this->_uri)) {

			$client = new Zend_Http_Client($this->_uri, $this->_configHttpClient);

			$response = $client->request();

			$fromCharset = $this->_getCharsetHtml($this->_getContentType($this->_uri));
			if (!empty($fromCharset) && mb_strtoupper($fromCharset, self::ENCODING) != self::ENCODING) {
				$body = iconv($fromCharset, self::ENCODING, $response->getBody());
			} else {
				$body = $response->getBody();
			}

			$html = $this->_getValidHTML($body);

			if ($html) {
				return $this->_getFormsFromPage($html, $this->_uri);
			}
		}
		return array();
	}

	private function _getCharsetHtml($contentTypeHeader) {
		if (isset($contentTypeHeader[1])) {
			$charset = mb_split("[=]", $contentTypeHeader[1]);
			$fromCharset = $charset[1];
			return $fromCharset;
		}
		return null;
	}

	private function _getContentType($uri) {
		$client = new Zend_Http_Client($uri, $this->_configHttpClient);
		$client->setEncType(Zend_Http_Client::ENC_URLENCODED);
		$response = $client->request(Zend_Http_Client::HEAD);
		$contentType = $response->getHeader('Content-Type');
		$contentTypeToArray = mb_split('[;]', $contentType);
		return $contentTypeToArray;
	}

	private function _isAllowContentType($uri) {
		$contentTypeHeader = $this->_getContentType($uri);
		if (in_array($contentTypeHeader[0], $this->_allowContentTypePage)) {
			return true;
		}
		return false;
	}

	private function _getValidHTML($html) {
		$config = array(
			'output-xml' => true,
			'add-xml-decl' => true,
			'fix-uri' => false,
			'preserve-entities' => false,
			'quote-nbsp' => false,
			'char-encoding' => 'utf8',
			'output-encoding' => 'utf8',
		);

		$tidy = new tidy();
		$tidy->parseString($html, $config, 'utf8');
		$tidy->cleanRepair();
		return $this->_removeControlSequences($tidy->html()->value);
	}

	private function _getHrefBaseElement($domNodeListBase) {
		$item = $domNodeListBase->item(0);
		if (isset($item) && !empty($item)) {
			return $domNodeListBase->item(0)->nodeValue;
		}
		return null;
	}

	private function _getDomNodeListEl($xml, $elName, $nodePath = null) {
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		if ($dom->loadXML($xml)) {
			$xpath = new DOMXPath($dom);
			$xpath->registerNamespace('ns', self::XHTML_NS);
			$query = ($nodePath) ? "$nodePath//ns:$elName" : "//ns:$elName";
			$elNS = $xpath->query($query);
			if ($elNS->length === 0) {
				$query = ($nodePath) ? "$nodePath//$elName" : "//$elName";
				$el = $xpath->query($query);
				return $el;
			}
			return $elNS;
		} else {
			throw new Exception(self::ERROR_PARSE);
		}
	}

	private function _getLinksFromPage($html, $currentUri) {
		$a = $this->_getDomNodeListEl($html, 'a/@href');
		$arr = array();
		for ($i = 0; $i < $a->length; $i++) {
			$uri = $this->_canonization->canonization($a->item($i)->nodeValue, $currentUri, $this->_getHrefBaseElement($this->_getDomNodeListEl($html, 'base/@href')));
			if ($this->_filter->filter($uri)) {
				$arr[] = $this->_encoding->encoding($uri);
			}
		}
		return array_unique($arr);
	}

	private function _getFormsFromPage($html, $currentUri) {

		$forms = $this->_getDomNodeListEl($html, 'form');
		$result = array();
		for ($i = 0; $i < $forms->length; $i++) {

			$attributes = array();
			for ($j = 0; $j < $forms->item($i)->attributes->length; $j++) {
				if ($forms->item($i)->attributes->item($j)->nodeName == "action") {
					$action = $this->_canonization->canonization($forms->item($i)->attributes->item($j)->nodeValue, $currentUri, $this->_getHrefBaseElement($this->_getDomNodeListEl($html, 'base/@href')));
					if ($this->_filter->filter($action)) {
						$attributes[$forms->item($i)->attributes->item($j)->nodeName] = $this->_encoding->encoding($action);
					} else {
						$attributes = null;
						break;
					}
				} else {
					$attributes[$forms->item($i)->attributes->item($j)->nodeName] = $forms->item($i)->attributes->item($j)->nodeValue;
				}
			}
			if ($attributes != null) {
				$result[$i]['attributes'] = $attributes;

				$inputs = $this->_getDomNodeListEl($html, 'input', $forms->item($i)->getNodePath());
				$arr = array();
				for ($j = 0; $j < $inputs->length; $j++) {
					for ($k = 0; $k < $inputs->item($j)->attributes->length; $k++) {
						$arr[$j][$inputs->item($j)->attributes->item($k)->nodeName] = $inputs->item($j)->attributes->item($k)->nodeValue;
					}
				}
				$result[$i]['inputs'] = $arr;

				$textareas = $this->_getDomNodeListEl($html, 'textarea', $forms->item($i)->getNodePath());
				$arr2 = array();
				for ($j = 0; $j < $textareas->length; $j++) {
					for ($k = 0; $k < $textareas->item($j)->attributes->length; $k++) {
						$arr2[$j][$textareas->item($j)->attributes->item($k)->nodeName] = $textareas->item($j)->attributes->item($k)->nodeValue;
					}
				}
				$result[$i]['textareas'] = $arr2;


				$selects = $this->_getDomNodeListEl($html, 'select', $forms->item($i)->getNodePath());
				$arr3 = array();
				for ($j = 0; $j < $selects->length; $j++) {
					for ($k = 0; $k < $selects->item($j)->attributes->length; $k++) {
						$arr3[$j][$selects->item($j)->attributes->item($k)->nodeName] = $selects->item($j)->attributes->item($k)->nodeValue;
					}

					$options = $this->_getDomNodeListEl($html, 'option', $selects->item($j)->getNodePath());
					$arr4 = array();
					for ($m = 0; $m < $options->length; $m++) {
						for ($n = 0; $n < $options->item($m)->attributes->length; $n++) {
							$arr4[$m][$options->item($m)->attributes->item($n)->nodeName] = $options->item($m)->attributes->item($n)->nodeValue;
						}
						$arr4[$m]['text'] = $options->item($m)->nodeValue;
					}
					$arr3[$j]['options'] = $arr4;
				}
				$result[$i]['selects'] = $arr3;
			}
		}
		return $result;
	}

	private function _removeControlSequences($str) {
		$result = preg_replace('[\x00|\x01|\x02|\x03|\x04|\x05|\x06|\x07|\x08|\x09|\x0B|\x0C|\x0E|\x10|\x11|\x12|\x13|\x14|\x15|\x16|\x17|\x18|\x19|\x1A|\x1B|\x1C\x1D|\x1E|\x1F]', '', $str);
		return $result;
	}

	public function getCanonization() {
		return $this->_canonization;
	}

	public function getFilter() {
		return $this->_filter;
	}

	public function getEncoding() {
		return $this->_encoding;
	}

}
