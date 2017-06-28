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

		public static function construct($recordId, $role, $password, $name, $cardNo, $groupId, $userId) {

			$instance = new self();
			$instance->recordId = $recordId;
			$instance->role = $role;
			$instance->password = $password;
			$instance->name = $name;
			$instance->cardNo = $cardNo;
			$instance->groupId = $groupId;
			$instance->userId = $userId;

			return $instance;
		}

	}
