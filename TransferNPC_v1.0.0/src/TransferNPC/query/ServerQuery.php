<?php


namespace TransferNPC\query;


class ServerQuery
{
	
	protected $server = null;
	
	
	public function __construct ($host, int $port)
	{
		if (is_string ($host)) {
			$host = gethostbyname ($host);
		}
		$server = $this->UT3Query ($host, $port);
		if ($server) {
			$this->server = $server;
		} else {
			$this->server = null;
		}
	}
	
	public function check (): bool
	{
		return $this->server !== null ? true : false;
	}
	
	public function getMaxPlayer (): int
	{
		return (int) $this->server [17];
	}
	
	public function getNumPlayer (): int
	{
		return (int) $this->server [15];
	}
	
	private function UT3Query($host, $port)
    {
        $socket = @fsockopen("udp://" . $host, $port);
        if (!$socket)
            return null;
        $online = @fwrite($socket, "\xFE\xFD\x09\x10\x20\x30\x40\xFF\xFF\xFF\x01");
        if (!$online)
            return null;
        $challenge = @fread($socket, 1400);
        if (!$challenge)
            return null;
        $challenge = substr(preg_replace("/[^0-9-]/si", "", $challenge), 1);
        $query = sprintf("\xFE\xFD\x00\x10\x20\x30\x40%c%c%c%c\xFF\xFF\xFF\x01",
            $challenge >> 24, $challenge >> 16, $challenge >> 8, $challenge >> 0);
        if (!@fwrite($socket, $query))
            return null;
        $response = array();
        $response[] = @fread($socket, 2048);
        $response = implode($response);
        $response = substr($response, 16);
        $response = explode("\0", $response);
        array_pop($response);
        array_pop($response);
        return $response;
    }
}