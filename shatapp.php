<?php

function getStatusCodeMessage($status)
{
    $codes = Array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    return (isset($codes[$status])) ? $codes[$status] : '';
}

// Helper method to send a HTTP response code/message
function sendResponse($status = 200, $body = '', $content_type = 'text/html')
{
    $status_header = 'HTTP/1.1 ' . $status . ' ' . getStatusCodeMessage($status);
    header($status_header);
    header('Content-type: ' . $content_type);
    echo $body;
}

class ShatappAPI {

    private $db;

    // Constructor - open DB connection
    function __construct() {
        $this->db = new mysqli(a, b, c, d);
        $this->db->autocommit(true);
    }

    // Destructor - close DB connection
    function __destruct() {
        $this->db->close();
    }
	
	function createUser() {
		if(isset($_GET["username"]) && isset($_GET["password"]) && isset($_GET["email"])) {
			$p_username = $_GET["username"];
			$p_password = $_GET["password"];
			$p_email = $_GET["email"];
			
			//First look up to check if username exists
			$stmt = $this->db->prepare('SELECT * FROM shatapp_users WHERE username=?');
			$stmt->bind_param("s", $p_username);
			$stmt->execute();
			$stmt->bind_result($r_id, $r_username, $r_password, $r_email);
			while($stmt->fetch()) {
				break;
			}
			$stmt->close();
			
			//If id is over 0, there is a line fetched with the username request. We bail.
			if($r_id>0) {
				sendResponse(400, 'Username already taken');
				return false;
			} else {
				//If id is no above 0, we want to insert the user to the database
				$stmt = $this->db->prepare("INSERT INTO shatapp_users (username, password, email) VALUES (?, ?, ?)");
				$stmt->bind_param("sss", $p_username, $p_password, $p_email);
				$stmt->execute();
				
				$stmt = $this->db->prepare("SELECT id FROM shatapp_users WHERE username = ? AND password = ? ");
				$stmt->bind_param("ss", $p_username, $p_password);
				$stmt->execute();
				
				$stmt->bind_result($user_id);
				while($stmt->fetch()) {
					break;
				}
				sendResponse(200, $user_id);
			}
			
			$stmt->close();
		} else {
			sendResponse(400, 'Missing parameters');
		}
	}
	
	function internalLogin($username, $password) {
		//First look up to check if username and password combination exists
		$stmt = $this->db->prepare('SELECT * FROM shatapp_users WHERE username=? AND password=?');
		$stmt->bind_param("ss", $username, $password);
		$stmt->execute();
		$stmt->bind_result($r_id, $r_username, $r_password, $r_email);
		while($stmt->fetch()) {
			break;
		}
		$stmt->close();
		
		//If id is over 0, then the username/password combination was correct and we're good
		if($r_id>0) {
			return true;
		} else {
			//If id is not above 0, then the user doesnt exist and login was faulty
			return false;
		}
		$stmt->close();
	}
	
	function login() {
		if(isset($_GET["username"]) && isset($_GET["password"])) {
			$p_username = $_GET["username"];
			$p_password = $_GET["password"];
			
			//First look up to check if username and password combination exists
			$stmt = $this->db->prepare('SELECT * FROM shatapp_users WHERE username=? AND password=?');
			$stmt->bind_param("ss", $p_username, $p_password);
			$stmt->execute();
			$stmt->bind_result($r_id, $r_username, $r_password, $r_email);
			while($stmt->fetch()) {
				break;
			}
			$stmt->close();
			
			//If id is over 0, then the username/password combination was correct and we're good
			if($r_id>0) {
				sendResponse(200, $r_id);
				return true;
			} else {
				//If id is not above 0, then the user doesnt exist and login was faulty
				sendResponse(400, 'No such username/password combination');
				return false;
			}
			
			$stmt->close();
		} else {
			sendResponse(400, 'Missing parameters');
		}
	}
	
	function addFriend() {
		if(isset($_GET["username"]) && isset($_GET["password"]) && isset($_GET["user_id"]) && isset($_GET["friend_id"]) && isset($_GET["friend_name"])) {
			if($this->internalLogin($_GET["username"], $_GET["password"])) {
				$p_user_id = $_GET["user_id"];
				$p_friend_id = $_GET["friend_id"];
				$p_friend_name = $_GET["friend_name"];
				$alreadyFriends = false;
				
				//First check if this friend has already been added
				$stmt = $this->db->prepare('SELECT * FROM shatapp_userfriends WHERE user_id=?');
				$stmt->bind_param("i", $p_user_id);
				$stmt->execute();
				$stmt->bind_result($r_id, $r_user_id, $r_friend_id, $r_friend_name);
				while($stmt->fetch()) {
					if($r_friend_id == $p_friend_id) {
						$alreadyFriends = true;
					}
					break;
				}
				$stmt->close();
				
				//Check if friend is already added
				if($alreadyFriends) {
					sendResponse(400, 'Friend already exists');
					return false;
				} else {
					//Add the friend
					$stmt = $this->db->prepare("INSERT INTO shatapp_userfriends (user_id, friend_id, friend_name) VALUES (?, ?, ?)");
					$stmt->bind_param("iis", $p_user_id, $p_friend_id, $p_friend_name);
					$stmt->execute();
					sendResponse(200, 'Friend has been added');
				}
				
				$stmt->close();
			}
		} else {
			sendResponse(400, 'Missing parameters');
		}
	}
	
	function getFriends() {
		if(isset($_GET["username"]) && isset($_GET["password"]) && isset($_GET["user_id"])) {
			if($this->internalLogin($_GET["username"], $_GET["password"])) {
				$p_user_id = $_GET["user_id"];
				$friends = array();
				
				//Simply retrieve all friend ids and names
				$stmt = $this->db->prepare('SELECT * FROM shatapp_userfriends WHERE user_id=?');
				$stmt->bind_param("i", $p_user_id);
				$stmt->execute();
				$stmt->bind_result($r_id, $r_user_id, $r_friend_id, $r_friend_name);
				
				while($stmt->fetch()) {
					$f = new Friend();
					$f->id = $r_friend_id;
					$f->name = $r_friend_name;
					$friends[] = $f;
				}
				sendResponse(200, json_encode($friends));
				$stmt->close();
			}
		} else {
			sendResponse(400, 'Missing parameters');
		}
	}
	
	function searchUsers() {
		if(isset($_GET["username"]) && isset($_GET["password"]) && isset($_GET["friend_username"])) {
			if($this->internalLogin($_GET["username"], $_GET["password"])) {
				$p_friend_username = $_GET["friend_username"];
				$users = array();
				
				//Simply retrieve all friend ids and names
				$stmt = $this->db->prepare('SELECT * FROM shatapp_users WHERE username LIKE CONCAT("%", ?, "%")');
				$stmt->bind_param("s", $p_friend_username);
				$stmt->execute();
				$stmt->bind_result($r_id, $r_username, $r_password, $r_email);
				while($stmt->fetch()) {
					$u = new User();
					$u->id = $r_id;
					$u->username = $r_username;
					$u->email = $r_email;
					$users[] = $u;
				}
				sendResponse(200, json_encode($users));
				$stmt->close();
			}
		} else {
			sendResponse(400, 'Missing parameters');
		}
	}
	
	function getShatMapPointsByUserId() {
		if(isset($_GET["username"]) && isset($_GET["password"]) && isset($_GET["user_id"])) {
			if($this->internalLogin($_GET["username"], $_GET["password"])) {
				$p_user_id = $_GET["user_id"];
				$mapPoints = array();
				
				$stmt = $this->db->prepare('SELECT * FROM shatapp_mappoints WHERE user_id = ?');
				$stmt->bind_param("i", $p_user_id);
				$stmt->execute();
				$stmt->bind_result($r_id, $r_user_id, $r_lat, $r_lng, $r_title, $r_text, $r_datetime);
				while($stmt->fetch()) {
					$mp = new MapPoint();
					$mp->id = $r_id;
					$mp->user_id = $r_user_id;
					$mp->lat = $r_lat;
					$mp->lng = $r_lng;
					$mp->title = $r_title;
					$mp->text = $r_text;
					$mp->datetime = $r_datetime;
					$mapPoints[] = $mp;
				}
				sendResponse(200, json_encode($mapPoints));
				$stmt->close();
			}
		} else {
			sendResponse(400, 'Missing parameters');
		}
	}
	
	function createShatMapPoint() {
		if(isset($_GET["username"]) && isset($_GET["password"]) && isset($_GET["user_id"]) && isset($_GET["shatmap"])) {
			if($this->internalLogin($_GET["username"], $_GET["password"])) {
				$p_user_id = $_GET["user_id"];
				$p_shatmap_str = $_GET["shatmap"];
				$q_shatmappoint_decoded = json_decode($p_shatmap_str, true);
				$text = $q_shatmappoint_decoded->{text};
				$mPoint = new MapPoint();
				foreach($q_shatmappoint_decoded AS $prop => $val) {
					if($prop == 'title') $mPoint->title = $val;
					else if($prop == 'text') $mPoint->text = $val;
					else if($prop == 'lat') $mPoint->lat = $val;
					else if($prop == 'lng') $mPoint->lng = $val;
					else if($prop == 'user_id') $mPoint->user_id = $val;
					else if($prop == 'datetime') $mPoint->datetime = $val;
					else if($prop == 'id') $mPoint->id = $val;
				}
				
				if($mPoint->id != null) {
					//If we do have an id, then it's an old map point that was updated, and so we should update it in the db
					$stmt = $this->db->prepare('UPDATE shatapp_mappoints SET text=?, title=? WHERE id=?');
					$stmt->bind_param("ssi", $mPoint->text, $mPoint->title, $mPoint->id);
					$stmt->execute();
					sendResponse(200, 'MapPoint updated');
					$stmt->close();
				} else {
					//If we don't have an id, then it's a new map point and should be inserted
					$stmt = $this->db->prepare('INSERT INTO shatapp_mappoints (USER_ID, LAT, LNG, TITLE, TEXT, DATETIME) VALUES (?, ?, ?, ?, ?, ?)');
					$stmt->bind_param("isssss", $mPoint->user_id, $mPoint->lat, $mPoint->lng, $mPoint->title, $mPoint->text, $mPoint->datetime);
					$stmt->execute();
					sendResponse(200, 'MapPoint created');
					$stmt->close();
				}
			}
		} else {
			sendResponse(400, 'Missing parameters');
		}
	}
}

class Friend {
	public $id;
	public $name;
}

class User {
	public $id;
	public $username;
	public $email;
}

class MapPoint {
	public $title;
	public $text;
	public $lat;
	public $lng;
	public $datetime;
	public $id;
	public $user_id;
}

function displayWelcomePage() {
	echo '<h1>ShatApp API v0.0.1</h1><h3>by Patrick W Larsen</h3><br/>';
	echo '<hr>';
	echo 'Methods available:<br/><ul><li>createUser</li><li>login</li><li>addFriend</li><li>getFriends</li><li>createShatMapPoint</li><li>getShatMapPointsByUserId</li><li>searchUsers</li></ul>';
}

if(isset($_GET["method"])) {
	$api = new ShatappAPI;

	$method = $_GET["method"];
	if($method == 'createUser') {
		$api->createUser();	
	} else if($method == 'login') {
		$api->login();
	} else if($method == 'addFriend') {
		$api->addFriend();
	} else if($method == 'getFriends') {
		$api->getFriends();
	} else if($method == 'createShatMapPoint') {
		$api->createShatMapPoint();
	} else if($method == 'getShatMapPointsByUserId') {
		$api->getShatMapPointsByUserId();
	} else if($method == 'searchUsers') {
		$api->searchUsers();
	} else {
		echo 'ERROR: Unknown method';
		displayWelcomePage();
	}
} else {
	displayWelcomePage();
}

?>