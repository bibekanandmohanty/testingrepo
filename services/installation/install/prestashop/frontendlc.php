<?php
require_once './config/config.inc.php';
require_once 'init.php';
global $cookie;
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
        $this->context = \Context::getContext();
        if (!empty($this->email) && !empty($this->password) && (strlen($this->password) >= 5)) {
            $customer = new Customer();
            $authentication = $customer->getByEmail(
                $this->email,
                $this->password
            );
            if (isset($authentication->active) && !$authentication->active) {
                $this->context->updateCustomer($customer);
                // Login information have changed, so we check if the cart rules still apply
                CartRule::autoRemoveFromCart($this->context);
                CartRule::autoAddToCart($this->context);
                $this->data = array('status' => 1, 'customerId' => $authentication->id);
            } elseif (!$authentication || !$customer->id || $customer->is_guest) {
                $this->data = array('status' => 1, 'customerId' => 0);
            } else {
                $this->context->updateCustomer($customer);
                // Login information have changed, so we check if the cart rules still apply
                CartRule::autoRemoveFromCart($this->context);
                CartRule::autoAddToCart($this->context);
                $customer_id = $this->context->cart->id_customer;
                $this->data = array('status' => 1, 'customerId' => $customer_id);
            }
        } else {
            $this->data = array('status' => 0, 'customerId' => 0);
        }
        return $this->data;
    }
    //user sign up
    public function userSignUp()
    {
        $id_shop_group = Context::getContext()->shop->id_shop_group;
        $id_lang = Context::getContext()->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        $secure_key = md5(uniqid(rand(), true));
        $this->password = Tools::encrypt($this->password);
        $last_passwd_gen = date('Y-m-d H:i:s', strtotime('-' . Configuration::get('PS_PASSWD_TIME_FRONT') . 'minutes'));
        $now = date('Y-m-d H:i:s', time());
        $birthday = '0000-00-00';
        $newsletter_date_add = '0000-00-00 00:00:00';
        if (Validate::isEmail($this->email) && !empty($this->email)) {
            if (Customer::customerExists($this->email, false, true)) {
                $this->data = array('status' => 0, 'customerId' => 0); //if email is already exit error msg
            } else {
                $sql = "INSERT INTO " . _DB_PREFIX_ . "customer(id_shop_group,id_shop,id_gender,id_default_group,id_lang,id_risk,company,siret,ape,firstname,lastname,
				   	email,passwd,last_passwd_gen,birthday,ip_registration_newsletter,newsletter_date_add,max_payment_days,secure_key,active,date_add,date_upd,reset_password_token,reset_password_validity)
				   	VALUES(" . $id_shop_group . "," . $id_shop . ",0,3," . $id_lang . ",0,'','','','" . pSQL($this->firstName) . "','" . pSQL($this->lastName) . "','" . $this->email . "',
				   	'" . $this->password . "','" . $last_passwd_gen . "','" . $birthday . "','','" . $newsletter_date_add . "',0,'" . $secure_key . "',1,'" . $now . "','" . $now . "','','" . $newsletter_date_add . "')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
                $customer_id = Db::getInstance()->Insert_ID();
                if ($customer_id) {
                    $insert_sql2 = "INSERT INTO " . _DB_PREFIX_ . "customer_group (id_customer,id_group) VALUES(" . $customer_id . ",3)";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql2);
                    $sl_select = "SELECT * FROM " . _DB_PREFIX_ . "customer WHERE id_customer = " . $customer_id . "";
                    $customer = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sl_select);
                    if (!empty($customer)) {
                        $this->updateContexts($customer);
                        $this->data = array('status' => 1, 'customerId' => $customer_id);
                    } else {
                        $this->data = array('status' => 1, 'customerId' => $customer_id);
                    }
                } else {
                    $this->data = array('status' => 0, 'customerId' => 0);
                }

            }
        }
        return $this->data;
    }
    /**
     * Update context after customer creation
     * @param Customer $customer Created customer
     */
    public function updateContexts($customer)
    {
        $context = \Context::getContext();
        $customer[0]['force_id'] = false;
        $customer[0]['id_shop_list'] = '';
        $customer[0]['groupBox'] = '';
        $customer[0]['logged'] = 1;
        $customer[0]['geoloc_postcode'] = '';
        $customer[0]['geoloc_id_state'] = '';
        $customer[0]['geoloc_id_country'] = '';
        $customer[0]['years'] = '';
        $customer[0]['days'] = '';
        $customer[0]['months'] = '';
        $customer[0]['id'] = (int) $customer[0]['id_customer'];
        $context->customer = $customer;
        $context->cookie->id_customer = (int) $customer[0]['id'];
        $context->cookie->customer_lastname = $customer[0]['lastname'];
        $context->cookie->customer_firstname = $customer[0]['firstname'];
        $context->cookie->passwd = $customer[0]['passwd'];
        $context->cookie->logged = 1;
        $cart = new Cart();
        $context->cookie->id_cart = (int) $cart->id;
        $context->cookie->write();
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
    $context = \Context::getContext();
    $customer_id = $context->cookie->id_customer;
    $customer_id = $customer_id ? $customer_id : 0;
    $data = array('status' => 1, 'customerId' => $customer_id);
}
header('Content-Type: application/json');
echo json_encode($data);
