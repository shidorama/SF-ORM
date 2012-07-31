<?php
class OrmConfig {
	static public $config;
	
	private function __construct() {
		//throw new Exception('Instantiation is not allowed!', -78);
	}
	
	static function getDbConfig() {
		$dbc = &self::$config['db'];
		$dbc['host'] = 'localhost';
		$dbc['port'] = '3306';
		$dbc['user'] = 'shido';
		$dbc['pass'] = 'hardcore';
		$dbc['database'] = 'CTS';
		$dbc['prefix'] = '';
		$dbc['names'] = 'UTF8';
		return $dbc;
	}
	
	static function getClassesConfig() {
		$cc = &self::$config['classes'];
		$cc = array ( 
			'user' => array (
				'fields' => array(
					'userId'	=> 'id',
					'userPassword'		=> '',
					'fullname'	=> '',
					'state'		=> '',
					'privLevel'		=> '',
					),
				'table' => 'user',
				),
			'test' => array(
					'fields' => array(
						'testId' 		=> 'id',
						'testName' 		=> '',
						'testDescr' 	=> '',
						'creationDate' 	=> 'skip',
						'timeLimit' 	=> '',
						'passBorder'	=> '',
						),
					'table' => 'test',
					),
			'tier' => array(
					'fields' => array(
							'testTierId'			=> 'id',
							'testId'				=> '',
							'testTierDescription'	=> '',
							'testTierReqLevel'		=> '',
							'testTierVolume'		=> '',
							'tierQuestionCost'		=> '',
							),
					'table' => 'testTier',
					),
			'question' => array(
					'fields' => array(
							'questionId' 	=> 'id',
							'testTierId'	=> '',
							'questionText'	=> '',
							'questionType'	=> '',
							'distance'		=> '',
							),
					'table' => 'question',
					),
			'answer' => array(
					'fields' => array(
							'answerId' 		=> 'id',
							'questionId'	=> '',
							'correct' 		=> '',
							'answerText' 	=> '',
							),
					'table' => 'answer',
					),
			'group' => array(
					'fields' => array(
							'groupId'	=> 'id',
							'groupName'	=> ''
							),
					'table' => 'group',
					),
			'assignment' => array(
					'fields' => array(
							'assignmentId' 	=> 'id',
							'groupId'		=> '',
							'testId'		=> '',
							'startsOn'		=> '',
							'validTill'		=> '',
							),
					'table' => 'testAssignment',
					),
			'session' => array(
					'fields' => array(
							'userId' 	=> 'id',
							'testId' 	=> '',
							'started'	=> 'skip',
							'activeIp'	=> '',
							'paused'	=> '',
							'data'		=> '',
							),
					'table' => 'testSession',
					),
			'userGroup' => array(
					'fields' => array(
							'userId'	=> 'id',
							'groupId'	=> 'id',
							),
					'table' => 'userGroup',
					),
			'testResult' => array(
					'fields' => array(
								'resultId'			=> 'id',
								'testId'			=> '',
								'userId'			=> '',
								'assignmentId'		=> '',
								'tiersPassed'		=> '',
								'result'			=> '',
								'correctAnswers'	=> '',
								'totalAnswers'		=> '',
								'timeTaken'		=> '',
								'testPassStatus'	=> '',
								'testChecked'		=> '',
								'dateTaken'		=> '',
							),
					'table' => 'testResult',
					),
			'testResultArchive' => array(
					'fields' => array(
								'resultId'	=> 'id',
								'testData'		=> '',	
							),
					'table' => 'testResultArchive',
					),
			);
		
		return $cc;
	}
}