<?php
namespace ZKLib;

use \DateTime;
use \RuntimeException;

class ZKLib {
	const USHRT_MAX = 65535;
	const CMD_CONNECT = 1000;
	const CMD_EXIT = 1001;
	const CMD_ENABLEDEVICE = 1002;
	const CMD_DISABLEDEVICE = 1003;
	const CMD_TESTVOICE = 1017;
	const CMD_ACK_OK = 2000;
	const CMD_ACK_ERROR = 2001;
	const CMD_ACK_DATA = 2002;
	const CMD_ACK_UNAUTH = 2005;
	const CMD_PREPARE_DATA = 1500;
	const CMD_DATA = 1501;
	const CMD_USER_WRQ = 8;
	const CMD_USERTEMP_RRQ = 9;
	const CMD_DEVICE = 11;
	const CMD_ATTLOG_RRQ = 13;
	const CMD_CLEAR_DATA = 14;
	const CMD_CLEAR_ATTLOG = 15;
	const CMD_DELETE_USER = 18;
	const CMD_CLEAR_ADMIN = 20;
	const CMD_GET_TIME = 201;
	const CMD_SET_TIME = 202;
	const CMD_VERSION = 1100;
	const CMD_GET_FREE_SIZES = 50;
	const CMD_ENABLE_CLOCK = 57;
	const CMD_WRITE_LCD = 66;
	const CMD_CLEAR_LCD = 67;
	const LEVEL_USER = 0;
	const LEVEL_ADMIN = 14;
	const DEVICE_GENERAL_INFO_STRING_LENGTH = 184;

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
	private $timeout = array('sec'=>30,'usec'=>500000);

	/** @var  string */
	private $data;

	/** @var  integer */
	private $session_id;

	/** @var integer */
	private $reply_id;

	private $response_code;
	private $checksum;

	public function __construct($ip = '', $port = 4370)
	{
		$this->ip = $ip;
		$this->port = $port;
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
		$this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $this->timeout);

		$this->reply_id = (-1 + self::USHRT_MAX);
		return $this->execute(self::CMD_CONNECT, null, [self::CMD_ACK_UNAUTH]);
	}

	public function disconnect()
	{
		if($this->socket) {
			$this->execute(self::CMD_EXIT);
			socket_close($this->socket);
		}
	}

	private function unpackResponse(){
		foreach ($r = unpack('vresponse_code/vchecksum/vsession_id/vreply_id', $this->data) as $key => $value){
			$this->{$key} = $value;
		}
	}

	private function execute($command, $command_string = null, $extraResponses = array())
	{
		$buf = $this->createHeader($command, $command_string);

		socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
		$bytes = socket_recvfrom($this->socket, $this->data, 1024, 0, $this->ip, $this->port);
		if ($bytes === false) {
			throw new RuntimeException(socket_strerror(socket_last_error()));
		}

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
	}

	public function getDeviceName()
	{
		return strstr($this->execute(self::CMD_DEVICE, '~DeviceName'), "\0", TRUE);
	}

	public function enable()
	{
		return $this->execute(self::CMD_ENABLEDEVICE);
	}

	public function testVoice()
	{
		return $this->execute(self::CMD_TESTVOICE);
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
		$encodedTime = current(unpack('V', $data));
		return $this->decodeTime($encodedTime);
	}

	public function setTime(DateTime $dateTime)
	{
		return $this->execute(self::CMD_SET_TIME, pack('V', $this->encodeTime($dateTime)));
	}

	public function clearAttendances(){
		return $this->execute(self::CMD_CLEAR_ATTLOG);
	}

	public function clearUsers(){
		return $this->execute(self::CMD_CLEAR_DATA);
	}

	public function clearAdmins(){
		return $this->execute(self::CMD_CLEAR_ADMIN);
	}

	/**
	 * writeLcd
	 * @param integer $line Display line to write 0-3
	 * @param string $message Message to display. Max len 16
	 */
	public function writeLcd($line, $message){
		$message = str_pad($message, 16);
		$message = utf8_decode(substr($message, 0, 16));
		return $this->execute(self::CMD_WRITE_LCD, pack('vCa' . strlen($message), $line, 0x0, $message));
	}

	public function clearLcd(){
		return $this->execute(self::CMD_CLEAR_LCD);
	}

	public function enableClock($flashSeconds){
		return $this->execute(self::CMD_ENABLE_CLOCK, pack('C', $flashSeconds ? 0x01 : 0x00));
	}

	/**
	 *
	 * @return \ZKLib\Attendance[]
	 */
	public function getAttendances()
	{
		if (($free = $this->getFreeSize()) && !$free->getAttLogsStored()){
			return array();
		}

		$this->execute(self::CMD_ATTLOG_RRQ);

		$attData = '';
		do {
			$size = socket_recvfrom($this->socket, $data, 1032, MSG_WAITALL, $this->ip, $this->port);
			if ($size === false) {
				throw new RuntimeException(socket_strerror(socket_last_error()));
			}
			$attData .= substr($data, 8);
		} while ($size > 0 && $size != 8);
		$attData = substr($attData, 4);

		$result = array();
		if ($attData){
			foreach (str_split($attData, 8) as $attInfo){
				if (strlen($attInfo) < 8) {
					continue;
				}
				$data = unpack('vuserId/Ctype/Cstatus/Vtime', $attInfo);
				$dateTime = $this->decodeTime($data['time']);
				$result[] = new Attendance(
					$data['userId'],
					$dateTime,
					$data['type'],
					$data['status']
				);
			}
		}

		return $result;
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
	 * @param integer $userRecordId
	 */
	public function deleteUser($userRecordId){
		return $this->execute(self::CMD_DELETE_USER, pack('v', $userRecordId));
	}

	/**
	 * @param \ZKLib\User $user
	 */
	public function setUser(User $user){
		return $this->execute(self::CMD_USER_WRQ, pack('vCa5a8a5CsV',
			$user->getRecordId(),
			$user->getRole(),
			$user->getPassword(),
			$this->func_removeAccents($user->getName()),
			$user->getCardNo(),
			$user->getGroupId(),
			$user->getTimeZone(),
			$user->getUserId()
		));
	}

	/**
	 * @return \ZKLib\User[]
	 */
	public function getUsers(){
		if (($free = $this->getFreeSize()) && !$free->getUsersStored()){
			return array();
		}

		$this->execute(self::CMD_USERTEMP_RRQ);

		$usersData = '';
		do {
			$size = socket_recvfrom($this->socket, $data, 1032, 0, $this->ip, $this->port);
			if ($size === false) {
				throw new RuntimeException(socket_strerror(socket_last_error()));
			}
			$usersData .= substr($data, 8);
		} while($size > 0 && $size != 8);
		$usersData = substr($usersData, 4);

		$result = array();
		if ($usersData){
			foreach (str_split($usersData, 28) as $userInfo){
				if (strlen($userInfo) < 28) {
					continue;
				}
				$user = unpack('vrecordId/Crole/a5password/a8name/a5cardNo/CgroupId/stimeZone/VuserId', $userInfo);
				$result[$user['recordId']] = new User(
					$user['recordId'],
					$user['role'],
					$user['password'],
					$user['name'],
					$user['cardNo'],
					$user['groupId'],
					$user['timeZone'],
					$user['userId']
				);
			}
		}
		return $result;
	}

	/**
	 * @return \ZKLib\Capacity|boolean
	 */
	public function getFreeSize()
	{
		if (($free_sizes_info = $this->execute(self::CMD_GET_FREE_SIZES)) && is_string($free_sizes_info)) {
			return new Capacity(unpack('x16/Vusers_stored/x4/Vtemplates_stored/x4/Vatt_logs_stored/x12/Vadmins_stored/Vpasswords_stored/Vtemplates_capacity/Vusers_capacity/Vatt_logs_capacity/Vtemplates_available/Vusers_available/Vatt_logs_available', $free_sizes_info));
		}
		return false;
	}

	/**
	 * @return \DateTime
	 */
	public function decodeTime($encodedTime)
	{
		$sec = $encodedTime % 60;
		$encodedTime /= 60;

		$min = $encodedTime % 60;
		$encodedTime /= 60;

		$hour = $encodedTime % 24;
		$encodedTime /= 24;

		$day = ($encodedTime % 31) + 1;
		$encodedTime /= 31;

		$month = ($encodedTime % 12) + 1;
		$encodedTime /= 12;

		$year = $encodedTime + 2000;

		$decoded = new DateTime();
		$decoded->setDate($year, $month, $day)
			->setTime($hour, $min, $sec);

		return $decoded;
	}

	/**
	 * @return integer
	 */
	public function encodeTime(DateTime $t)
	{
		return
			(($t->format('Y') % 100) * 12 * 31 + (($t->format('n') - 1) * 31) + $t->format('j') - 1) * (24 * 60 * 60) +
			($t->format('G') * 60 + $t->format('i')) * 60 + $t->format('s');
	}
}
