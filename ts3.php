<?php
	ini_set("log_errors", 1);
	ini_set("error_log", "/customers/3/a/9/xp-de.com/httpd.www/ts3/php-error_live.log");

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
	// load framework files
	require_once("libraries/TeamSpeak3/TeamSpeak3.php");
	// connect to local server, authenticate and spawn an object for the virtual server on port 9987
	$ts3_VirtualServer = TeamSpeak3::factory("serverquery://felix_bot:YBslnXcx@5.230.4.187:10011/?server_port=9987");
	// build and display HTML treeview using custom image paths (remote icons will be embedded using data URI sheme)
	echo $ts3_VirtualServer->getViewer(new TeamSpeak3_Viewer_Html("images/viewericons/", "images/countryflags/", "data:image"));
*/

if(isset($_GET['task'])) {
	$task = $_GET['task'];
} else {
	$task = 'functionlist';
}

$api = new api();

switch($task) {
	case 'functionlist': 
		$api->functionlist();
		break;
		
	case 'register': 
		if(isset($_REQUEST['username']) && isset($_REQUEST['password'])){
			$api->register();
		}else{
			// Error
		}
		break;
		
	case 'login': 
		break;
		
	case 'logout': 
		break;

	case 'deleteUser': 
		break;
				
	case 'getUsername': 
		break;
		
	case 'setUsername': 
		break;
		
	case 'setPassword': 
		break;
		
	default: 
		//-- not implemented
		break;
}

echo json_encode($api->return);

class api {
	
	public $return = array(
		'status' => array(
			'statuscode'=> '200',
			'message' => 'ok'
		),
		'data' => array()
	);

	private function connectToDb() {
		$servername = "localhost";
		$username = "username";
		$password = "password";
		$dbname = "myDB";

		// Create connection
		$conn = new mysqli($servername, $username, $password, $dbname);
		// Check connection
		if ($conn->connect_error) {
			$this->return['status']['statuscode'] = '???';
			$this->return['status']['message'] = "DB-Connection failed: " . $conn->connect_error;
		} 
		
		return $conn;
	}
	
	public function functionlist() {
			
		$this->return['data'] = array(
									array('functionname' => 'functionlist', 'param' => '', 'return' => 'array(functionname array(param array(name: type), raturn array())'),
									array('functionname' => 'register', 'param' => 'username: string, password: string', 'return' => 'success: boolean')
								);
	}
	
	public function register($username, $password) {
		
		$dbConnection = $this->connectToDb();
		if($this->return['status']['statuscode'] != '200') {
			return;
		}
		
		$sql = "INSERT INTO user (username, password) VALUES ('" . $username . "', '" . $password . "')";
		
		if ($conn->query($sql) === TRUE) {
			$this->return['data'] = array('success' => true);
		} else {
			$this->return['data'] = array('success' => false);
			$this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $conn->error;
		}

		$conn->close();
	}
	
	public function login($username, $password) {
		
		$dbConnection = $this->connectToDb();
		if($this->return['status']['statuscode'] != '200') {
			return;
		}
		
		//-- Check Login-Daten
		//	 genrate Session-ID
		//	 Insert Into Session-DB
		
		$sql = "SELECT * FROM user WHERE username = '" . $username . "' AND password = '" . $password . "'";
		$result = $conn->query($sql);

		if ($result->num_rows == 1) {
			$row = $result->fetch_assoc();
			$userId = $row["id"];
		} else {
			$this->return['data'] = array('success' => false);
			$this->return['status']['message'] = "Datenbank-Fehler!";
			return;
		}
		
		$sessionId = uniqid('', true);
		
		$sql = "INSERT INTO session (id, user_id, expire) VALUES (" . $userId . ", '" . $sessionId . "', now() + 5 minutes)";
		
		if ($conn->query($sql) === TRUE) {
			$this->return['data'] = array('sessionId' => $sessionId);
		} else {
			$this->return['data'] = array('success' => false);
			$this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $conn->error;
		}

		$conn->close();
	}
	
	public function logout($sessionId) {
		
		$sql = "DELETE FROM session WHERE id = '" . $sessionId . "'";
		
		if ($conn->query($sql) === TRUE) {
			echo "Record deleted successfully";
		} else {
			echo "Error deleting record: " . $conn->error;
		}
		if ($conn->query($sql) === TRUE) {
			$this->return['data'] = array('sessionId' => $sessionId);
		} else {
			$this->return['data'] = array('success' => false);
			$this->return['status']['message'] = "Ln: " . __FILE__ . ";" . __LINE__ . " - " . __FUNCTION__ . "; Error: " . $sql . "; " . $conn->error;
		}

		$conn->close();
	}

	
}
