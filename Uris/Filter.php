<?php

class ScanSite_Uris_Filter extends ScanSite_Uris_Uris
{

	private $_parseUri;

	public function filter($uri)
	{
		$this->_parseUri = parse_url($uri);
		if ($this->_isHttpLink() && $this->_isUriOfTargetHost())
		{
			return true;
		}
		return false;
	}

	private function _clearWWW($host)
	{
		if ($host[0] == 'w' && $host[2] == 'w' && $host[2] == 'w')
		{
			return mb_substr($host, 4, mb_strlen($host, self::ENCODING), self::ENCODING);
		}
		return $host;
	}

	private function _isUriOfTargetHost()
	{
		$this->_targetHost = $this->_clearWWW($this->_getIdnEncodeHost($this->_targetHost));
		$parseUri = $this->_parseUri;
		$host = $this->_clearWWW($this->_getIdnEncodeHost($parseUri['host']));
		if (mb_strlen($this->_targetHost, self::ENCODING) > mb_strlen($host, self::ENCODING))
		{
			return false;
		}
		$parseHost = array_reverse(mb_split('[.]', $host));
		$parseTargetHost = array_reverse(mb_split('[.]', $this->_targetHost));
		for ($i = 0; $i < count($parseTargetHost); $i++)
		{
			if ($parseTargetHost[$i] != $parseHost[$i])
			{
				return false;
			}
		}
		return true;
	}

	private function _isHttpLink()
	{
		$parseUri = $this->_parseUri;
		if ($parseUri['scheme'] == 'http' || $parseUri['scheme'] == 'https')
		{
			return true;
		}
		return false;
	}

	

	

}
