<?php
	namespace ZKLib;
	
	class User {
		const PRIVILEGE_COMMON_USER = 0b0000;
		const PRIVILEGE_ENROLLER    = 0b0010;
		const PRIVILEGE_MANAGER     = 0b1100;
		const PRIVILEGE_SUPERADMIN  = 0b1110;
		
		private $recordId;
		private $userId;
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
		
		public static function construct(/*$recordId, $role, $password, $name, $cardNo, $userId*/){
			if ((func_num_args() == 1) && is_array(func_get_arg(0))){
				$args = func_get_arg(0);
			} else {
				$args = func_get_args();
			}
			list($recordId, $role, $password, $name, $cardNo, $userId) = array_values($args);
			
			$instance = new self();
			$instance->recordId = $recordId;
			$instance->role = $role;
			$instance->password = $password;
			$instance->name = $name;
			$instance->cardNo = $cardNo;
			$instance->userId = $userId;
			
			return $instance;
		}
		
	}