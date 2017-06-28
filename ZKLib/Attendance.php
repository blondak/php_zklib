<?php
namespace ZKLib;

if (!defined('__ZKLib_Attendance')){

	define('__ZKLib_Attendance', true);

	class Attendance {
		private $recordId;
		private $userId;
		private $type;
		private $status;
		private $time;

		public function getType(){
			return $this->type;
		}

		public function getRecordId(){
			return $this->recordId;
		}

		public function getUserId(){
			return $this->userId;
		}

		public function getStatus(){
			return $this->status;
		}

		/**
		 * @return \DateTime
		 */
		public function getDateTime(){
			return $this->time;
		}

		public static function construct($recordId, $userId, \DateTime $dateTime, $type, $status){
			$instance = new self();
			$instance->recordId = $recordId;
			$instance->userId = $userId;
			$instance->type = $type;
			$instance->time = $dateTime;
			$instance->status = $status;
			return $instance;
		}
	}
}
