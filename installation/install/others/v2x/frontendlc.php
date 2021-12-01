<?php

class Login
{

    private $email;
    private $password;
    public $customer_id = 0;
    public $data;
    public $context = '';
    public function setSignin($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }
    public function setSignup($fName, $lName)
    {
        $this->firstName = $fName;
        $this->lastName = $lName;
    }
    //authenticate user login

    /**
     * Process login
     */
    public function userLogin()
    {
        $this->data = array('status' => 0, 'customerId' => 0);
    }
    //user sign up
    public function userSignUp()
    {
        
        return  $this->data = array('status' => 0, 'customerId' => 0);
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
} else {

    $customer_id = 0;
    $data = array('status' => 1, 'customerId' => $customer_id);
}
header('Content-Type: application/json');
echo json_encode($data);
