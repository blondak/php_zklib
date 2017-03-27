<?php
namespace ZKLib;

if (!defined('__ZKLib_Capacity')){

	define('__ZKLib_Capacity', true);

	class Capacity {
		private $att_logs_available;
		private $att_logs_capacity;
		private $att_logs_stored;
		private $templates_available;
		private $templates_capacity;
		private $templates_stored;
		private $users_stored;
		private $users_available;
		private $users_capacity;
		private $admins_stored;
		private $passwords_stored;
		
		public function getAttLogsAvailable(){
			return $this->att_logs_available;
		}
		
		public function getAttLogsCapacity(){
			return $this->att_logs_capacity;
		}

		public function getAttLogsStored(){
			return $this->att_logs_stored;
		}

		public function getTemplatesAvailable(){
			return $this->templates_available;
		}
		
		public function getTemplatesCapacity(){
			return $this->templates_capacity;
		}
		
		public function getTemplatesStored(){
			return $this->templates_stored;
		}

		public function getUsersAvailable(){
			return $this->users_available;
		}
		
		public function getUsersCapacity(){
			return $this->users_capacity;
		}
		
		public function getUsersStored(){
			return $this->users_stored;
		}
		
		public function getAdminsStoredy(){
			return $this->admins_stored;
		}
		
		public function getPasswordsStored(){
			return $this->passwords_stored;
		}
		
		public static function construct($data){
			$instance = new self();
			
			foreach ($data as $key => $value){
				if (property_exists($instance, $key)){
					$instance->{$key} = $value;
				} else {
					var_dump($key);
				}
			}
			return $instance;
		}
	}
}