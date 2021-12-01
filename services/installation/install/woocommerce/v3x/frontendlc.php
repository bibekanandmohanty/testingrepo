<?php
require_once 'wp-load.php';
session_start();
class Login {
	private $email;
	private $password;
	public $sessionId = 0;
	public $data;
	public function setSignin($email, $password) {
		$this->email = $email;
		$this->password = $password;
	}
	public function setSignup($fName, $lName) {
		$this->firstName = $fName;
		$this->lastName = $lName;
	}
	//user logout
	public function userLogout() {
		if (is_user_logged_in()) {
			wp_logout();
		}
	}
	//authenticate user login
	public function userLogin() {
		if (is_user_logged_in()) {
			wp_logout();
		}
		if (!empty($this->email) && !empty($this->password)) {
			try {
				$userinfo = get_user_by('email', $this->email);
				$user = wp_signon(array('user_login' => $userinfo->user_login, 'user_password' => $this->password), false);
				if (is_a($user, 'WP_User')) {
					$user_id = $user->ID;

					if ($user) {
						wp_set_current_user($user_id, $user->user_login);
						wp_set_auth_cookie($user_id);
						do_action('wp_login', $user->user_login, $user);
					}
					if (is_user_logged_in()) {
						$sessionId = get_current_user_id();
						$this->data = array('status' => 1, 'customerId' => $sessionId, 'msg' => 'SUCCESS');
					} else {
						$this->data = array('status' => 1, 'customerId' => '0', 'msg' => 'NA');
					}
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
	public function userSignUp() {
		$userinfo = get_user_by('email', $this->email);
		if (!$userinfo) {
			$userName = explode("@", $this->email)[0];
			$newUserId = wc_create_new_customer($this->email, $userName, $this->password);
			$newUser = get_user_by('email', $this->email);
			update_user_meta($newUserId, "billing_first_name", $this->firstName);
			update_user_meta($newUserId, "billing_last_name", $this->lastName);
			update_user_meta($newUserId, "first_name", $this->firstName);
			update_user_meta($newUserId, "last_name", $this->lastName);
			update_user_meta($newUserId, "billing_email", $this->email);
			$user = wp_signon(array('user_login' => $newUser->user_login, 'user_password' => $this->password), false);
			if (is_a($user, 'WP_User')) {
				$user_id = $user->ID;

				if ($user) {
					wp_set_current_user($user_id, $user->user_login);
					wp_set_auth_cookie($user_id);
					do_action('wp_login', $user->user_login, $user);
				}
			}
			if (is_user_logged_in()) {
				$sessionId = get_current_user_id();
				$this->data = array('status' => 1, 'customerId' => $sessionId, 'msg' => 'SUCCESS');
			} else {
				$this->data = array('status' => 1, 'customerId' => '0', 'msg' => 'NA');
			}
		} else {
			$this->data = array('status' => 1, 'customerId' => '0', 'msg' => 'AE');
		}
		return $this->data;
	}
}
$login = new Login();

if (isset($_POST['email']) && isset($_POST['password'])) {
	$login->setSignin($_POST['email'], $_POST['password']);
	if (isset($_POST['firstName']) && isset($_POST['lastName'])) {
		$login->setSignup($_POST['firstName'], $_POST['lastName']);
		$data = $login->userSignUp();
	} else {
		$data = $login->userLogin();
	}
} elseif (isset($_POST['logout'])) {
	if ($_POST['logout'] == 'true') {
		session_destroy();
		$data = array('status' => 1, 'logout' => 'true');
	} else {
		$data = array('status' => 1, 'logout' => 'false');
	}
} else {
	$sessionId = get_current_user_id();
	$msg = $sessionId ? 'SUCCESS' : '';
	$data = array('status' => 1, 'customerId' => $sessionId, 'msg' => $msg);
}
header('Content-Type: application/json');
echo json_encode($data);