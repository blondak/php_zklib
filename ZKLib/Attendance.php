<?php
namespace ZKLib;

if (!defined('__ZKLib_Attendance')){

	define('__ZKLib_Attendance', true);	
	
	class Attendance {
		private $recordId;
		private $userId;
		private $type;
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
		
		/**
		 * @return \DateTime
		 */
		public function getDateTime(){
			return $this->time;
		}
		
		public static function construct(/*$recordId, $userId, $timestamp, $type*/){
			if ((func_num_args() == 1) && is_array(func_get_arg(0))){
				$args = func_get_arg(0);
			} else {
				$args = func_get_args();
			}
			list($recordId, $userId, $timestamp, $type) = array_values($args);
			$instance = new self();
			$instance->recordId = $recordId;
			$instance->userId = $userId;
			$instance->type = $type;
			$instance->time = \DateTime::createFromFormat('U', $timestamp);
			return $instance;
		}
	}
}