<?php

class ZKLib {
	const USHRT_MAX = 65535;
	const CMD_CONNECT = 1000;
	const CMD_EXIT = 1001;
	const CMD_ENABLEDEVICE = 1002;
	const CMD_DISABLEDEVICE = 1003;
	const CMD_ACK_OK = 2000;
	const CMD_ACK_ERROR = 2001;
	const CMD_ACK_DATA = 2002;
	const CMD_ACK_UNAUTH = 2005;
	const CMD_PREPARE_DATA = 1500;
	const CMD_DATA = 1501;
	const CMD_SET_USER = 8;
	const CMD_USERTEMP_RRQ = 9;
	const CMD_DEVICE = 11;
	const CMD_ATTLOG_RRQ = 13;
	const CMD_CLEAR_DATA = 14;
	const CMD_CLEAR_ATTLOG = 15;
	const CMD_DEL_USER = 18;
	const CMD_CLEAR_ADMIN = 20;
	const CMD_WRITE_LCD = 66;
	const CMD_GET_TIME = 201;
	const CMD_SET_TIME = 202;
	const CMD_VERSION = 1100;
	const CMD_GET_FREE_SIZES = 50;
	const LEVEL_USER = 0;
	const LEVEL_ADMIN = 14;
	const DEVICE_GENERAL_INFO_STRING_LENGTH = 184;
	
	const TIME_OFFSSET = 936572400;
	
	/**
	 * @var $socket
	 */
	private $socket;

	/**
	 * @var string
	 */
	private $ip;

	/**
	 * @var integer
	 */
	private $port;

	/**
	 * @var array
	 */
	private $timeout = array('sec'=>60,'usec'=>500000);

	/** @var  string */
	private $data;

	/** @var  integer */
	private $session_id;

	/** @var integer */
	private $reply_id;

	/**
	 * @var null
	 */
	private $result = null;

	public function __construct($ip = '', $port = 4370)
	{
		$this->port = $port;
		$this->ip = $ip;
	}

	/**
	 * @return string
	 */
	public function getIp()
	{
		return $this->ip;
	}

	/**
	 * @param string $ip
	 * @return ZkSocket
	 */
	public function setIp($ip)
	{
		$this->ip = $ip;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * @param int $port
	 * @return ZkSocket
	 */
	public function setPort($port)
	{
		$this->port = $port;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getTimeout()
	{
		return $this->timeout;
	}

	/**
	 * @param array $timeout
	 * @return ZkSocket
	 */
	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
		return $this;
	}

	/**
	 * @return null
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * @param null $result
	 * @return ZkSocket
	 */
	public function setResult($result)
	{
		$this->result = $result;
		return $this;
	}

	private function createHeader($command, $command_string, $chksum=0) {
		$buf = pack('SSSS', $command, $chksum, $this->session_id, $this->reply_id).$command_string;
		$this->reply_id += 1;
		if ($this->reply_id >= self::USHRT_MAX) {
			$this->reply_id -= self::USHRT_MAX;
		}
		$buf = pack('SSSS', $command, $this->createCheckSum($buf), $this->session_id, $this->reply_id);
		return $buf.$command_string;
	}

	protected function createCheckSum($buffer){
		$checksum = 0;
		if (strlen($buffer)%2){
			$buffer .=chr(0);
		}
		foreach (unpack('v*', $buffer) as $data){
			$checksum += $data;
			if ($checksum > self::USHRT_MAX){
				$checksum -= self::USHRT_MAX;
			}
		}
		$checksum = -$checksum - 1;
		while ($checksum < 0){
			$checksum += self::USHRT_MAX;
		}
		return ($checksum & self::USHRT_MAX);
	}
	
	function checkValid($reply, $extraResponses = null) {
		/*Checks a returned packet to see if it returned CMD_ACK_OK, indicating success*/
		if ($extraResponses){
			return in_array($this->response_code, array_merge([self::CMD_ACK_OK], $extraResponses));
		}
		return $this->response_code == self::CMD_ACK_OK;
	}

	public function connect()
	{
		try {
			$this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $this->timeout);

			$this->reply_id = (-1 + self::USHRT_MAX);
			return $this->execute(self::CMD_CONNECT, null, [self::CMD_ACK_UNAUTH]);

		} catch (\Exception $ex) {
			return false;
		}
	}

	public function disconnect()
	{
		if($this->socket) {
			$this->execute(self::CMD_EXIT);
			socket_close($this->socket);
		}
	}

	private $response_code;
	private $checksum;
	private function unpackResponse(){
		foreach ($r = unpack('vresponse_code/vchecksum/vsession_id/vreply_id', $this->data) as $key => $value){
			$this->{$key} = $value;
		}
	}

	private function execute($command, $command_string = null, $extraResponses = array())
	{
		try {
			$buf = $this->createHeader($command, $command_string);

			socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
			@socket_recvfrom($this->socket, $this->data, 1024, 0, $this->ip, $this->port);

			if ( strlen( $this->data ) > 0 ) {
				$this->unpackResponse();

				if ($this->checkValid($this->data, $extraResponses) ) {
					if (strlen($this->data) > 8){
						if ($command_string){
							return preg_replace('/^'.preg_quote($command_string, '/').'=/', '', substr( $this->data, 8 ));
						}
						return substr( $this->data, 8 );
					}
					return true;
				}
			}
		} catch (\Exception $ex) {
			var_dump($ex->getMessage());
		}

		return false;
	}

	public function getDeviceName()
	{
		return strstr($this->execute(self::CMD_DEVICE, '~DeviceName'), "\0", TRUE);
	}

	public function enable()
	{
		return $this->execute(self::CMD_ENABLEDEVICE);
	}

	public function disable()
	{
		return $this->execute(self::CMD_DISABLEDEVICE,  chr(0).chr(0));
	}

	public function getOs()
	{
		return strstr($this->execute(self::CMD_DEVICE,  '~OS'), "\0", TRUE);
	}

	public function getPlatform()
	{
		return strstr($this->execute(self::CMD_DEVICE,  '~Platform'), "\0", TRUE);
	}

	public function getPlatformVersion()
	{
		return strstr($this->execute(self::CMD_DEVICE,  '~ZKFPVersion'), "\0", TRUE);
	}

	public function getSerialNumber()
	{
		return strstr($this->execute(self::CMD_DEVICE,  '~SerialNumber'), "\0", TRUE);
	}

	public function getSsr()
	{
		return strstr($this->execute(self::CMD_DEVICE,  '~SSR'), "\0", TRUE);
	}

	public function getWorkCode()
	{
		return strstr($this->execute(self::CMD_DEVICE,  'WorkCode'), "\0", TRUE);
	}

	public function getPinWidth()
	{
		return strstr($this->execute(self::CMD_DEVICE,  '~PIN2Width'), "\0", TRUE);
	}

	public function getFaceOn()
	{
		return strstr($this->execute(self::CMD_DEVICE,  'FaceFunOn'), "\0", TRUE);
	}

	public function getVersion()
	{
		return strstr($this->execute(self::CMD_VERSION), "\0", TRUE);
	}

	/**
	 * @return \DateTime
	 */
	public function getTime()
	{
		$data = $this->execute(self::CMD_GET_TIME);
		return \DateTime::createFromFormat('U', current(unpack('V', $data)) + self::TIME_OFFSSET);
	}

	private function reverseHex($hexstr) {
		$tmp = '';

		for ( $i=strlen($hexstr); $i>=0; $i-- ) {
			$tmp .= substr($hexstr, $i, 2);
			$i--;
		}

		return $tmp;
	}

	public function setTime(\DateTime $dateTime)
	{
		return $this->execute(self::CMD_SET_TIME,  pack('I', $dateTime->getTimestamp() - self::TIME_OFFSSET));
	}

	public function clearAttendance(){
		try {
			return $this->execute(self::CMD_CLEAR_ATTLOG);
		} catch (\Exception $ex) {
			error_log($ex->getMessage());
		}
	}
	
	public function clearUsers(){
		try {
			return $this->execute(self::CMD_CLEAR_DATA);
		} catch (\Exception $ex) {
			error_log($ex->getMessage());
		}
	}

	public function clearAdmins(){
		try {
			return $this->execute(self::CMD_CLEAR_ADMIN);
		} catch (\Exception $ex) {
			error_log($ex->getMessage());
		}
	}
	
	/**
	 * 
	 * @return \ZKLib\Attendance[]
	 */
	public function getAttendance()
	{
		if (($free = $this->getFreeSize()) && !$free->getAttLogsStored()){
			return array();
		}
		if (!defined('__ZKLib_Attendance')){
			require_once __DIR__.'/ZKLib/Attendance.php';
		}
		try {
			$this->execute(self::CMD_ATTLOG_RRQ);
			
			$attData = '';
			if($size = $this->getPrepareDataSize()) {
				@socket_recvfrom($this->socket, $attData, $size, MSG_WAITALL, $this->ip, $this->port);
			}
			@socket_recvfrom($this->socket, $data, 1024, 0, $this->ip, $this->port);
			$result = array();
			if ($attData){
				foreach (str_split(substr($attData, 10), 40) as $attInfo){
					$data = unpack('x2/vrecordId/Z16userId/@29/Vtime/ctype', $attInfo);
					$data['time'] += self::TIME_OFFSSET;
					$result[$data['recordId']] = \ZKLib\Attendance::construct($data);
				}
			}
			return $result;
						
		} catch (\Exception $ex) {
			error_log($ex->getMessage());
		}
	}

	private function getPrepareDataSize()
	{
		$response = unpack('vcommand/vchecksum/vsession_id/vreply_id/vsize', $this->data);
		return ( $response['command'] == self::CMD_PREPARE_DATA ) ? $response['size'] : false;
	}

	protected function func_removeAccents($s){
		$s = preg_replace('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u', '', $s);
		$s = strtr($s, '`\'"^~', "\x01\x02\x03\x04\x05");
		if (ICONV_IMPL === 'glibc') {
			$s = @iconv('UTF-8', 'WINDOWS-1250//TRANSLIT', $s); // intentionally @
			$s = strtr($s, "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe\x9c\x9a\xba\x9d\x9f\x9e\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2"
					."\xd3\xd4\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe",
					"ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYTsraaaalccceeeeiiddnnooooruuuuyt");
		} else {
			$s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s); // intentionally @
		}
		$s = str_replace(array('`', "'", '"', '^', '~'), '', $s);
		return strtr($s, "\x01\x02\x03\x04\x05", '`\'"^~');
	}
	
	
	/**
	 * @param \ZKLib\User $user
	 */
	public function setUser($user){
		return $this->execute(self::CMD_SET_USER, pack('vCa8a24VCx8a9x15', 
			$user->getRecordId(),
			$user->getRole(),
			$user->getPassword(),
			$this->func_removeAccents($user->getName()),
			$user->getCardNo(),
			1,
			$user->getUserId()
		));
	}
	
	/**
	 * @return \ZKLib\User[]
	 */
	public function getUser(){
		if (($free = $this->getFreeSize()) && !$free->getUsersStored()){
			return array();
		}
		
		if (!defined('__ZKLib_User')){
			require_once __DIR__.'/ZKLib/User.php';
		}
		try {
			$this->execute(self::CMD_USERTEMP_RRQ);
		
			$usersData = '';
			if($size = $this->getPrepareDataSize()) {
				do {
					$size -= @socket_recvfrom($this->socket, $data, $size, 0, $this->ip, $this->port);
					$usersData .= substr($data, 8);
				} while($size > 0);
			}
			@socket_recvfrom($this->socket, $data, 1024, 0, $this->ip, $this->port);
			$result = array();
			if ($usersData){
				foreach (str_split(substr($usersData, 4), 72) as $userInfo){
					$user = unpack('vrecordId/Crole/Z8password/Z24name/VcardNo/x9/Z9userId', str_pad($userInfo, 72, '\0'));
					$user['name'] = $user['name'];
					$result[$user['recordId']] = \ZKLib\User::construct($user); 
				}
			}
			return $result;
		} catch (\Exception $ex) {
			error_log($ex->getMessage());
		}
		
	}
	
	/**
	 * @return \ZKLib\Capacity|boolean
	 */
	public function getFreeSize()
	{
		if (($free_sizes_info = $this->execute(self::CMD_GET_FREE_SIZES)) && is_string($free_sizes_info)) {
			if (!defined('__ZKLib_Capacity')){
				require_once __DIR__.'/ZKLib/Capacity.php';
			}
			return \ZKLib\Capacity::construct(unpack('x16/Vusers_stored/x4/Vtemplates_stored/x4/Vatt_logs_stored/x12/Vadmins_stored/Vpasswords_stored/Vtemplates_capacity/Vusers_capacity/Vatt_logs_capacity/Vtemplates_available/Vusers_available/Vatt_logs_available', $free_sizes_info));
		}
		return false;
	}
}