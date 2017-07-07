<?php
namespace ZKLib;

class User {
	const PRIVILEGE_COMMON_USER = 0b0000;
	const PRIVILEGE_ENROLLER    = 0b0010;
	const PRIVILEGE_MANAGER     = 0b1100;
	const PRIVILEGE_SUPERADMIN  = 0b1110;

	private $recordId;
	private $userId;
	private $groupId;
	private $name;
	private $password;
	private $role;
	private $cardNo;
	private $timeZone;

	public function getRecordId(){
		return $this->recordId;
	}

	public function getRole(){
		return $this->role;
	}

	public function getPassword(){
		return $this->password;
	}

	public function getName(){
		return $this->name;
	}

	public function getCardNo(){
		return $this->cardNo;
	}

	public function getUserId(){
		return $this->userId;
	}

	public function getGroupId(){
		return $this->groupId;
	}

	public function getTimeZone(){
		return $this->timeZone;
	}

	public function __construct($recordId, $role, $password, $name, $cardNo, $groupId, $timeZone, $userId) {
		$this->recordId = $recordId;
		$this->role = $role;
		$this->password = $password;
		$this->name = $name;
		$this->cardNo = $cardNo;
		$this->groupId = $groupId;
		$this->timeZone = $timeZone;
		$this->userId = $userId;
	}
}
