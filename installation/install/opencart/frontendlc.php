<?php
require_once("config.php");
class Login{
	private $email;
	private $password;
	public $sessionId = 0;
	public $data;
	public $conn;
	public $sessionKey;
	private $directory;
	public function __construct(){		
		$this->conn = mysqli_connect(DB_HOSTNAME,DB_USERNAME,DB_PASSWORD,DB_DATABASE);
		//get session path for opencart 3
		$pos = strrpos(session_save_path(), ';');
		if ($pos === false) {
			$this->directory = session_save_path();
		} else {
			$this->directory = substr(session_save_path(), $pos + 1);
		}	
	}
	/**
	 * date of created 08-07-2020(dd-mm-yy)
	 * date of Modified (dd-mm-yy)
	 * Get all session value
	 *
	 * @param (int)session_id
	 * @return array session details
	 */
    public function sessionRead($session_id) {

        $sql = "SELECT `data` FROM `" . DB_PREFIX . "session` WHERE session_id = ? AND expire > " . (int)time();
        $params = array();
        $params[] = 's';
        $params[] = &$session_id;
        $this->conn->set_charset("utf8");
        $this->conn->query("SET SQL_MODE = ''");
        $stmt = $this->conn->prepare($sql);
        call_user_func_array([$stmt, 'bind_param'], $params);
        $stmt->execute();
        $query = $stmt->get_result();
        $row = mysqli_fetch_assoc($query);
        if (!empty($row['data'])) {
            return json_decode($row['data'], true);
        } else {
            return false;
        }
    }

    /**
     * date of created 08-07-2020(dd-mm-yy)
     * date of Modified (dd-mm-yy)
     * Write all session value
     *
     * @param (int)session_id
     * @param (String)data
     * @return boolean
     */
    public function sessionWrite($session_id, $data) {
        if ($session_id) {
            $expire = ini_get('session.gc_maxlifetime');
            $encodData = json_encode($data);
            $date = date('Y-m-d H:i:s', time() + $expire);
            $sql = "REPLACE INTO `" . DB_PREFIX . "session` SET session_id = ?, `data` = ?, expire = ?";
                    
            $params = array();
            $params[] = 'sss';
            $params[] = &$session_id;
            $params[] = &$encodData;
            $params[] = &$date;
            $stmt = $this->conn->prepare($sql);
            call_user_func_array([$stmt, 'bind_param'], $params);
            $status = $stmt->execute();
            $stmt->get_result();
        }
        
        return true;
    }
	public function setSignin($email,$password,$session_id){
		$this->email = $email;
		$this->password = $password;
		$this->password = $password;
		$this->session = $session_id;
	}
	public function setSignup($fName,$lName,$session_id){
		$this->firstName = $fName;
		$this->lastName = $lName;
		$this->session = $session_id;
	}
	//user logout
	public function userLogout(){
		unset($_SESSION[$this->session]['customer_id']);
	}
	
	private function token($length = 32) {
		// Create random token
		$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		
		$max = strlen($string) - 1;
		
		$token = '';
		
		for ($i = 0; $i < $length; $i++) {
			$token .= $string[mt_rand(0, $max)];
		}	
		
		return $token;
	}
	//authenticate user login
	public function userLogin(){
		$this->userLogout();
		$version = $this->getVersion();
		if (!empty($this->email) && !empty($this->password)) {
			try {
				if ($version >= '3.0.0.0') {
					$customer_query = mysqli_query($this->conn, "SELECT * FROM " . DB_PREFIX . "customer WHERE LOWER(email) = '" . $this->email . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->password . "'))))) OR password = '" . md5($this->password) . "') AND status = '1'");
				}else{
					$customer_query = mysqli_query($this->conn, "SELECT * FROM " . DB_PREFIX . "customer WHERE LOWER(email) = '" . $this->email . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->password . "'))))) OR password = '" . md5($this->password) . "') AND status = '1' AND approved = '1' ");
				}
				$row = mysqli_fetch_assoc($customer_query);
				if (mysqli_num_rows($customer_query)>0) {
					if ($version >= '3.0.0.0') {
						$cookieObj = $_COOKIE;
						$session_id = $cookieObj['OCSESSID'];
						if(isset($cookieObj['OCSESSID'])){
							$sessionArr = $this->sessionRead($session_id);
							$sessionArr['customer_id'] = $row['customer_id'];
							$sessionArr = $this->sessionWrite($session_id,$sessionArr);
						}
					}else{
						if($this->sessionKey!='')
							$_SESSION[$this->sessionKey]['customer_id'] = $row['customer_id'];
						else
							$_SESSION[$this->session]['customer_id'] = $row['customer_id'];
					}
					$this->data = array('status' => 1,'customerId' => $row['customer_id'], 'msg' => 'SUCCESS');
				} else {
					$this->data = array('status' => 1, 'customerId' => '0', 'msg' => 'NA');
				}
				return $this->data;
			} catch (Exception $e) {
					return $this->data = array('status' => 0, 'customerId' => '0', 'msg' => 'SE');
			}
		}
	}
	
	//user sign up
	public function userSignUp(){			
		$existing_user = mysqli_query($this->conn, "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer WHERE LOWER(email) = '" . $this->email . "'");
		$query =  mysqli_query($this->conn, "SELECT language_id FROM `" . DB_PREFIX . "language` WHERE status=1");
		$row = mysqli_fetch_array($query,MYSQL_ASSOC);
		$version = $this->getVersion();
		$salt = $this->token(9);
		if (mysqli_num_rows($existing_user)>0) {
		
			$result = mysqli_query($this->conn, "SHOW COLUMNS FROM " . DB_PREFIX . "customer LIKE 'language_id'");
			$insertLanguage = (mysqli_num_rows($result))?" language_id = '" . (int)$row['language_id'] . "',":"";
			if ($version >= '3.0.0.0') {
				$query = "INSERT INTO " . DB_PREFIX . "customer SET customer_group_id = '" . 1 . "', store_id = '" . 0 . "',".$insertLanguage." firstname = '" . $this->firstName . "', lastname = '" . $this->lastName . "', email = '" . $this->email . "', telephone = '', fax = '', custom_field = '', salt = '" . $salt . "', password = '" . sha1($salt . sha1($salt . sha1($this->password))) . "', newsletter = '" . 0 . "', ip = '" . $_SERVER['REMOTE_ADDR'] . "', status = '1', date_added = NOW()";
			}else{
				$query = "INSERT INTO " . DB_PREFIX . "customer SET customer_group_id = '" . 1 . "', store_id = '" . 0 . "',".$insertLanguage." firstname = '" . $this->firstName . "', lastname = '" . $this->lastName . "', email = '" . $this->email . "', telephone = '', fax = '', custom_field = '', salt = '" . $salt . "', password = '" . sha1($salt . sha1($salt . sha1($this->password))) . "', newsletter = '" . 0 . "', ip = '" . $_SERVER['REMOTE_ADDR'] . "', status = '1', approved = '" . 1 . "', date_added = NOW()";
			}
			$sql = mysqli_query($this->conn, $query);
			if($sql){
				$customer_id = mysqli_insert_id($this->conn);
				$cookieObj = $_COOKIE;
				$session_id = $cookieObj['OCSESSID'];
				if(isset($cookieObj['OCSESSID'])){
					$sessionArr = $this->sessionRead($session_id);
					$sessionArr['customer_id'] = $customer_id;
					$sessionArr = $this->sessionWrite($session_id,$sessionArr);
				}
				$this->data = array('status' => 1,'customerId' => $customer_id, 'msg' => 'SUCCESS');
			} else{
				$this->data = array('status' => 1, 'customerId' => '0', 'msg' => 'NA');
			}
			
		} else {
			$this->data = array('status' => 1, 'customerId' => '0', 'msg' => 'AE');
		}						
		return $this->data;
	}
	//Get opencart current version
	public function getVersion(){
		$opencartPath = HTTPS_SERVER;
		$url = $opencartPath . 'vcheck.php';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return $version = curl_exec($ch);
	}
	//session start
	public function sessionStart($session_id = '') {
		if (!$session_id) {
			if (function_exists('random_bytes')) {
				$session_id = substr(bin2hex(random_bytes(26)), 0, 26);
			} else {
				$session_id = substr(bin2hex(openssl_random_pseudo_bytes(26)), 0, 26);
			}
		}
		if (preg_match('/^[a-zA-Z0-9,\-]{22,52}$/', $session_id)) {
			$session_id = $session_id;
		} else {
			exit('Error: Invalid session ID!');
		}
		
		$sesionString = $this->sessionRead($session_id);
		$this->sessionWrite($session_id,$sesionString);
		$session_name = 'OCSESSID';
		setcookie($session_name, $session_id, ini_get('session.cookie_lifetime'), ini_get('session.cookie_path'), ini_get('session.cookie_domain'));	
		return $session_id;
	}
}
$login = new Login();
$session_id ='';
$default = '';
$version = $login->getVersion();
session_start();
if(isset($_SESSION))
{
	foreach($_SESSION as $key=>$value)
	{
		if($key == 'default')
			$default = $key;
		
		if($session_id=='' && $default=='')
			$session_id = $key;
		else
			$session_id = session_id();
	}
}
if(isset($_POST['email']) && isset($_POST['password']) ){
	$login->sessionKey = ($default!='')?$default:'';
	if ($version >= '3.0.0.0') {
		$cookieObj = $_COOKIE;
		$session_id = $cookieObj['OCSESSID'];
	}
	$login->setSignin($_POST['email'],$_POST['password'],$session_id);
	if(isset($_POST['firstName']) && isset($_POST['lastName'])){
		$login->setSignup($_POST['firstName'],$_POST['lastName'],$session_id);
		$data = $login->userSignUp();			
	}else{
		$data =$login->userLogin();
	}		
}else{		
	$key = ($default=='')?$session_id:$default;
	if ($version >= '3.0.0.0') {
		if(isset($_COOKIE['OCSESSID'])){
			$cookieObj = $_COOKIE;
			$session_id = $cookieObj['OCSESSID'];
			$sessionArr = $login->sessionRead($session_id);
			$sessionId = isset($sessionArr['customer_id'])?$sessionArr['customer_id']:0;
		}else{
			$session_id = $login->sessionStart($session_id);
			$sessionId = 0;
		}
	}else{
		$sessionId = isset($_SESSION[$key]['customer_id'])?$_SESSION[$key]['customer_id']:0;
	}
	$data = array('status' => 1, 'customerId'=>$sessionId, 'msg' => 'SUCCESS');
}
header('Content-Type: application/json');
echo json_encode($data); 

?>