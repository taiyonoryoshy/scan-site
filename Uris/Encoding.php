<?php

class Model_Scan_Uris_Encoding extends Model_Scan_Uris_Uris
{

	public function encoding($uri)
	{
		$parseUri = parse_url($uri);
		$parseUri['host'] = $this->_getIdnEncodeHost($parseUri['host']);
		if (isset($parseUri['path']) && !empty($parseUri['path']))
		{
			if (!Zend_Uri::check($parseUri['scheme'] . '://' . $parseUri['host'] . $parseUri['path']))
			{
				$parseUri['path'] = $this->_pathEncode($parseUri['path']);
			}
		}
		if (isset($parseUri['query']) && !empty($parseUri['query']))
		{
			if (!Zend_Uri::check($parseUri['scheme'] . '://' . $parseUri['host'] . '?' . $parseUri['query']))
			{
				$parseUri['query'] = $this->_queryEncode($parseUri['query']);
			}
		}
		return $this->_pasteUri($parseUri);
	}

	private function _pathEncode($path)
	{
		$array = mb_split('[/]', $path);
		$encoding = '';
		foreach ($array as $row)
		{
			if (!empty($row))
			{
				$encoding .= '/' . urlencode($row);
			}
		}
		if ($path[mb_strlen($path, self::ENCODING) - 1] == '/')
		{
			$encoding .= '/';
		}
		return $encoding;
	}

	private function _queryEncode($query)
	{
		$array = mb_split('[&]', $query);
		$parseQuery = array();
		$parseQueryItem = array();
		$queryEncode = '';
		for ($i = 0; $i < count($array); $i++)
		{
			$parseQuery[$i] = mb_split('[=]', $array[$i]);
			if (count($parseQuery[$i]) == 2)
			{
				$parseQueryItem[$i] = urlencode($parseQuery[$i][0]) . '=' . urlencode($parseQuery[$i][1]);
			} else
			{
				$parseQueryItem[$i] = urlencode($parseQuery[$i][0]);
			}
			if (!empty($queryEncode))
			{
				$queryEncode.='&';
			}
			$queryEncode.=$parseQueryItem[$i];
		}
		return $queryEncode;
	}

}
