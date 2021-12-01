<?php
/*
 * 2007-2013 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2013 PrestaShop SA
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 * PrestaShop Webservice Library
 * @package PrestaShopWebservice
 */

/**
 * @package PrestaShopWebservice
 */

require_once dirname(__FILE__) . '/../config/config.inc.php';
require_once dirname(__FILE__) . '/../init.php';

class PrestaShopWebservice
{

    /**
     * @var string Shop URL
     */
    protected $url;

    /**
     * @var string Authentification key
     */
    protected $key;

    /**
     * @var boolean is debug activated
     */
    protected $debug;

    /**
     * @var string PS version
     */
    protected $version;

    /**
     * @var array compatible versions of PrestaShop Webservice
     */
    const psCompatibleVersionsMin = '1.4.0.0';
    const psCompatibleVersionsMax = '1.7.99.99';

    /**
     * PrestaShopWebservice constructor. Throw an exception when CURL is not installed/activated
     * <code>
     * <?php
     * require_once('./PrestaShopWebservice.php');
     * try
     * {
     *     $ws = new PrestaShopWebservice('http://mystore.com/', 'ZQ88PRJX5VWQHCWE4EE7SQ7HPNX00RAJ', false);
     *     // Now we have a webservice object to play with
     * }
     * catch (PrestaShopWebserviceException $ex)
     * {
     *     echo 'Error : '.$ex->getMessage();
     * }
     * ?>
     * </code>
     *
     * @param string $url   Root URL for the shop
     * @param string $key   Authentification key
     * @param mixed  $debug Debug mode Activated (true) or deactivated (false)
     */
    public function __construct($url, $key, $debug = true)
    {
        if (!extension_loaded('curl')) {
            throw new PrestaShopWebserviceException('Please activate the PHP extension \'curl\' to allow use of PrestaShop webservice library');
        }

        $this->url = $url;
        $this->key = $key;
        $this->debug = $debug;
        $this->version = 'unknown';
    }

    /**
     * Take the status code and throw an exception if the server didn't return 200 or 201 code
     *
     * @param int $status_code Status code of an HTTP return
     */
    protected function checkStatusCode($status_code)
    {
        $error_label = 'This call to PrestaShop Web Services failed and returned an HTTP status of %d. That means: %s.';
        switch ($status_code) {
            case 200:case 201:
                break;
            case 204:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'No content'));
                break;
            case 400:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Bad Request'));
                break;
            case 401:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Unauthorized'));
                break;
            case 404:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Not Found'));
                break;
            case 405:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Method Not Allowed'));
                break;
            case 500:
                throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Internal Server Error'));
                break;
            default:
                throw new PrestaShopWebserviceException('This call to PrestaShop Web Services returned an unexpected HTTP status of:' . $status_code);
        }
    }
    /**
     * Handles a CURL request to PrestaShop Webservice. Can throw exception.
     *
     * @param  string $url         Resource name
     * @param  mixed  $curl_params CURL parameters (sent to curl_set_opt)
     * @return array status_code, response
     */
    protected function executeRequest($url, $curl_params = array())
    {
        $defaultParams = array(
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->key . ':',
            CURLOPT_HTTPHEADER => array('Expect:'),
        );

        $session = curl_init($url);

        $curl_options = array();
        foreach ($defaultParams as $defkey => $defval) {
            if (isset($curl_params[$defkey])) {
                $curl_options[$defkey] = $curl_params[$defkey];
            } else {
                $curl_options[$defkey] = $defaultParams[$defkey];
            }

        }
        foreach ($curl_params as $defkey => $defval) {
            if (!isset($curl_options[$defkey])) {
                $curl_options[$defkey] = $curl_params[$defkey];
            }
        }

        curl_setopt_array($session, $curl_options);
        $response = curl_exec($session);

        $index = strpos($response, "\r\n\r\n");
        if ($index === false && $curl_params[CURLOPT_CUSTOMREQUEST] != 'HEAD') {
            throw new PrestaShopWebserviceException('Bad HTTP response');
        }

        $header = substr($response, 0, $index);
        $body = substr($response, $index + 4);

        $headerArrayTmp = explode("\n", $header);

        $headerArray = array();
        foreach ($headerArrayTmp as &$headerItem) {
            $tmp = explode(':', $headerItem);
            $tmp = array_map('trim', $tmp);
            if (count($tmp) == 2) {
                $headerArray[$tmp[0]] = $tmp[1];
            }

        }

        if (array_key_exists('PSWS-Version', $headerArray)) {
            $this->version = $headerArray['PSWS-Version'];
            if (version_compare(PrestaShopWebservice::psCompatibleVersionsMin, $headerArray['PSWS-Version']) == 1
                || version_compare(PrestaShopWebservice::psCompatibleVersionsMax, $headerArray['PSWS-Version']) == -1
            ) {
                throw new PrestaShopWebserviceException('This library is not compatible with this version of PrestaShop. Please upgrade/downgrade this library');
            }

        }

        if ($this->debug) {
            $this->printDebug('HTTP REQUEST HEADER', curl_getinfo($session, CURLINFO_HEADER_OUT));
            $this->printDebug('HTTP RESPONSE HEADER', $header);

        }
        $status_code = curl_getinfo($session, CURLINFO_HTTP_CODE);
        if ($status_code === 0) {
            throw new PrestaShopWebserviceException('CURL Error: ' . curl_error($session));
        }

        curl_close($session);
        if ($this->debug) {
            if ($curl_params[CURLOPT_CUSTOMREQUEST] == 'PUT' || $curl_params[CURLOPT_CUSTOMREQUEST] == 'POST') {
                $this->printDebug('XML SENT', urldecode($curl_params[CURLOPT_POSTFIELDS]));
            }

            if ($curl_params[CURLOPT_CUSTOMREQUEST] != 'DELETE' && $curl_params[CURLOPT_CUSTOMREQUEST] != 'HEAD') {
                $this->printDebug('RETURN HTTP BODY', $body);
            }

        }
        return array('status_code' => $status_code, 'response' => $body, 'header' => $header);
    }

    public function printDebug($title, $content)
    {
        echo '<div style="display:table;background:#CCC;font-size:8pt;padding:7px"><h6 style="font-size:9pt;margin:0">' . $title . '</h6><pre>' . htmlentities($content) . '</pre></div>';
    }

    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Load XML from string. Can throw exception
     *
     * @param  string $response String from a CURL response
     * @return SimpleXMLElement status_code, response
     */
    protected function parseXML($response)
    {
        if ($response != '') {
            libxml_clear_errors();
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (libxml_get_errors()) {
                $msg = var_export(libxml_get_errors(), true);
                libxml_clear_errors();
                throw new PrestaShopWebserviceException('HTTP XML response is not parsable: ' . $msg);
            }
            return $xml;
        } else {
            throw new PrestaShopWebserviceException('HTTP response is empty');
        }

    }

    /**
     * Add (POST) a resource
     * <p>Unique parameter must take : <br><br>
     * 'resource' => Resource name<br>
     * 'postXml' => Full XML string to add resource<br><br>
     * Examples are given in the tutorial</p>
     *
     * @param  array $options
     * @return SimpleXMLElement status_code, response
     */
    public function add($options)
    {
        $xml = '';

        if (isset($options['resource'], $options['postXml']) || isset($options['url'], $options['postXml'])) {
            $url = (isset($options['resource']) ? $this->url . '/api/' . $options['resource'] : $options['url']);
            $xml = $options['postXml'];
            if (isset($options['id_shop'])) {
                $url .= '&id_shop=' . $options['id_shop'];
            }

            if (isset($options['id_group_shop'])) {
                $url .= '&id_group_shop=' . $options['id_group_shop'];
            }

        } else {
            throw new PrestaShopWebserviceException('Bad parameters given');
        }

        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'POST', CURLOPT_POSTFIELDS => $xml));

        self::checkStatusCode($request['status_code']);
        return self::parseXML($request['response']);
    }

    /**
     * Retrieve (GET) a resource
     * <p>Unique parameter must take : <br><br>
     * 'url' => Full URL for a GET request of Webservice (ex: http://mystore.com/api/customers/1/)<br>
     * OR<br>
     * 'resource' => Resource name,<br>
     * 'id' => ID of a resource you want to get<br><br>
     * </p>
     * <code>
     * <?php
     * require_once('./PrestaShopWebservice.php');
     * try
     * {
     * $ws = new PrestaShopWebservice('http://mystore.com/', 'ZQ88PRJX5VWQHCWE4EE7SQ7HPNX00RAJ', false);
     * $xml = $ws->get(array('resource' => 'orders', 'id' => 1));
     *    // Here in $xml, a SimpleXMLElement object you can parse
     * foreach ($xml->children()->children() as $attName => $attValue)
     *     echo $attName.' = '.$attValue.'<br />';
     * }
     * catch (PrestaShopWebserviceException $ex)
     * {
     *     echo 'Error : '.$ex->getMessage();
     * }
     * ?>
     * </code>
     *
     * @param  array $options Array representing resource to get.
     * @return SimpleXMLElement status_code, response
     */
    public function get($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource'])) {
            $url = $this->url . '/api/' . $options['resource'];
            $url_params = array();
            if (isset($options['id'])) {
                $url .= '/' . $options['id'];
            }

            $params = array('filter', 'display', 'sort', 'limit', 'id_shop', 'id_group_shop', 'output_format', 'language');
            foreach ($params as $p) {
                foreach ($options as $k => $o) {
                    if (strpos($k, $p) !== false) {
                        $url_params[$k] = $options[$k];
                    }
                }
            }

            if (count($url_params) > 0) {
                $url .= '?' . http_build_query($url_params);
            }

        } else {
            throw new PrestaShopWebserviceException('Bad parameters given');
        }

        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'GET'));
        self::checkStatusCode($request['status_code']); // check the response validity
        return $request['response'];
    }

    public function getXml($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource'])) {
            $url = $this->url . '/api/' . $options['resource'];
            $url_params = array();
            if (isset($options['id'])) {
                $url .= '/' . $options['id'];
            }

            $params = array('filter', 'display', 'sort', 'limit', 'id_shop', 'id_group_shop');
            foreach ($params as $p) {
                foreach ($options as $k => $o) {
                    if (strpos($k, $p) !== false) {
                        $url_params[$k] = $options[$k];
                    }
                }
            }

            if (count($url_params) > 0) {
                $url .= '?' . http_build_query($url_params);
            }

        } else {
            throw new PrestaShopWebserviceException('Bad parameters given');
        }

        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'GET'));

        self::checkStatusCode($request['status_code']); // check the response validity
        return self::parseXML($request['response']);
    }
    /**
     * Head method (HEAD) a resource
     *
     * @param  array $options Array representing resource for head request.
     * @return SimpleXMLElement status_code, response
     */
    public function head($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource'])) {
            $url = $this->url . '/api/' . $options['resource'];
            $url_params = array();
            if (isset($options['id'])) {
                $url .= '/' . $options['id'];
            }

            $params = array('filter', 'display', 'sort', 'limit');
            foreach ($params as $p) {
                foreach ($options as $k => $o) {
                    if (strpos($k, $p) !== false) {
                        $url_params[$k] = $options[$k];
                    }
                }
            }

            if (count($url_params) > 0) {
                $url .= '?' . http_build_query($url_params);
            }

        } else {
            throw new PrestaShopWebserviceException('Bad parameters given');
        }

        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'HEAD', CURLOPT_NOBODY => true));
        self::checkStatusCode($request['status_code']); // check the response validity
        return $request['header'];
    }
    /**
     * Edit (PUT) a resource
     * <p>Unique parameter must take : <br><br>
     * 'resource' => Resource name ,<br>
     * 'id' => ID of a resource you want to edit,<br>
     * 'putXml' => Modified XML string of a resource<br><br>
     * Examples are given in the tutorial</p>
     *
     * @param array $options Array representing resource to edit.
     */
    public function edit($options)
    {
        $xml = '';
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif ((isset($options['resource'], $options['id']) || isset($options['url'])) && $options['putXml']) {
            $url = (isset($options['url']) ? $options['url'] : $this->url . '/api/' . $options['resource'] . '/' . $options['id']);
            $xml = $options['putXml'];
            if (isset($options['id_shop'])) {
                $url .= '&id_shop=' . $options['id_shop'];
            }

            if (isset($options['id_group_shop'])) {
                $url .= '&id_group_shop=' . $options['id_group_shop'];
            }

        } else {
            throw new PrestaShopWebserviceException('Bad parameters given');
        }

        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_POSTFIELDS => $xml));
        self::checkStatusCode($request['status_code']); // check the response validity
        return self::parseXML($request['response']);
    }

    /**
     * Delete (DELETE) a resource.
     * Unique parameter must take : <br><br>
     * 'resource' => Resource name<br>
     * 'id' => ID or array which contains IDs of a resource(s) you want to delete<br><br>
     * <code>
     * <?php
     * require_once('./PrestaShopWebservice.php');
     * try
     * {
     * $ws = new PrestaShopWebservice('http://mystore.com/', 'ZQ88PRJX5VWQHCWE4EE7SQ7HPNX00RAJ', false);
     * $xml = $ws->delete(array('resource' => 'orders', 'id' => 1));
     *    // Following code will not be executed if an exception is thrown.
     *     echo 'Successfully deleted.';
     * }
     * catch (PrestaShopWebserviceException $ex)
     * {
     *     echo 'Error : '.$ex->getMessage();
     * }
     * ?>
     * </code>
     *
     * @param array $options Array representing resource to delete.
     */
    public function delete($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource']) && isset($options['id'])) {
            if (is_array($options['id'])) {
                $url = $this->url . '/api/' . $options['resource'] . '/?id=[' . implode(',', $options['id']) . ']';
            } else {
                $url = $this->url . '/api/' . $options['resource'] . '/' . $options['id'];
            }
        }

        if (isset($options['id_shop'])) {
            $url .= '&id_shop=' . $options['id_shop'];
        }

        if (isset($options['id_group_shop'])) {
            $url .= '&id_group_shop=' . $options['id_group_shop'];
        }

        $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'DELETE'));
        self::checkStatusCode($request['status_code']); // check the response validity
        return true;
    }

    //ImprintNext code start here//

    /**
     * Get Product combination
     *
     * @param $option Product filter
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return combinations array
     */
    public function getAttributeCombinations($option)
    {
        $langId = $this->getLaguageId();
        $product = new \Product($option['product_id'], false, $langId);
        return $combinations = $product->getAttributeCombinations((int) ($langId));
    }

    /**
     * Get Product combination by product and variant id
     *
     * @param $option Product filter
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return combinations array
     */
    public function getAttributeCombinationsById($option)
    {
        $langId = $this->getLaguageId();
        $product = new \Product($option['product_id'], false, $langId);
        return $combinations = $product->getAttributeCombinationsById($option['variation_id'], (int) ($langId));
    }

    /**
     * Get Product price by product id
     *
     * @param $pid Product id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return product price
     */
    public function getProductPriceByPid($pid)
    {
        $langId = $this->getLaguageId();
        $product = new \Product($pid, false, $langId);
        return $product->price;
    }

    /**
     * Add product to Cart
     *
     * @param $data Product data array
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return cart url
     */
    public function addToCart($data = [])
    {
        global $cookie;
        $context = \Context::getContext();
        $cart = null;
        $sql = "SELECT id_product_attribute FROM " . _DB_PREFIX_ . "product_attribute WHERE id_product='" . $data['id'] . "' ";
        $exist = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (empty($exist[0]['id_product_attribute'])) {
            $data['id_product_attribute'] = '';
        }
        // Initialize Cart //
        $cartObj = new \CartCore();
        $cartObj->id = (int) $context->cookie->id_cart;
        $prodetails = $cartObj->getProducts();

        if (!isset($data['id'])) {
            return ["status" => "error", "message" => "Missing required arguments."];
        }

        if ($context->cookie->id_cart) {
            $cart = new \Cart((int) $context->cookie->id_cart);
        }
        // Initialize Cart //
        if (!is_object($cart)) {
            $cart = new \Cart();
            $cart->id_customer = (int) $context->cookie->id_customer;
            $cart->id_guest = (int) $context->cookie->id_guest;
            $cart->id_address_delivery = (int) (\Address::getFirstCustomerAddressId($cart->id_customer));
            $cart->id_address_invoice = $cart->id_address_delivery;
            $cart->id_lang = (int) ($context->cookie->id_lang);
            $cart->id_currency = (int) ($context->cookie->id_currency);
            $cart->id_carrier = 1;
            $cart->recyclable = 0;
            $cart->gift = 0;
            $cart->add();
            $context->cookie->__set('id_cart', (int) $cart->id);
            $cart->update();
        }
        if ($cart->id) {
            $product = new \Product((int) $data['id']);
            $customization_id = false;
            if (!$product->id) {
                return ["status" => "error", "message" => "Cannot find data in database."];
            }

            /*Initialize cart variables */
            $idAddressDelivery = (int) (\Address::getFirstCustomerAddressId($cart->id_customer));
            $idProductAttribute = ($data['id_product_attribute'] != '') ? $data['id_product_attribute'] : null;
            $quantity = ($data['quantity'] != '') ? $data['quantity'] : 1;
            if (_PS_VERSION_ <= '1.7.4.4') {
                $cart->updateQty(
                    $quantity, (int) $product->id, $idProductAttribute,
                    $customization_id, 'up', $idAddressDelivery,
                    null, true, $data['ref_id']
                );
            } else {
                $cart->updateQty(
                    $quantity, (int) $product->id, $idProductAttribute,
                    $customization_id, 'up', $idAddressDelivery,
                    null, true, false, $data['ref_id']
                );
            }
            $cart->update();

            $shopId = 1; // Assuming single Store //
            if (!is_null(\Shop::getContextShopID())) {
                $shopId = \Shop::getContextShopID();
            }
            if ($product->price == 0 && $idProductAttribute) {
                $price = $this->getCombinationPrice($idProductAttribute);
            } else {
                $price = $product->price;
            }
            $productPrice = $price;
            if ($data['is_variable_decoration']) {
                $customPrice = $data['added_price'];
            } else {
                $customPrice = $productPrice + $data['added_price'];
            }

            // Insert the custom price to "imprintnext_cart_custom_price" table //
            $priceSql = "INSERT INTO " . _DB_PREFIX_ . "imprintnext_cart_custom_price SET id_cart = '" . $cart->id . "', id_product = '" . (int) $product->id . "', id_product_attribute = '" . $idProductAttribute . "', id_shop = '" . $shopId . "', custom_price = '" . $customPrice . "', ref_id = '" . $data['ref_id'] . "'";
            $return = \Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($priceSql);
            $msg['status'] = 1;
        } else {
            $msg['status'] = 0;
            $msg['url'] = '';
        }
        $context = \Context::getContext();
        $rest = substr(_PS_VERSION_, 0, 3);
        if ($rest > 1.6) {
            $cartUrl = $this->getCartSummaryURLS();
        } else {
            $cartUrl = $context->link->getPageLink($order_process, true);
        }
        $msg['url'] = $cartUrl;
        return $msg;
    }

    /**
     * GET: Get cart page url from prestashop store
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return string
     */
    public function getCartSummaryURLS()
    {
        $context = \Context::getContext();
        return $context->link->getPageLink(
            'cart',
            null,
            $context->language->id,
            ['action' => 'show']
        );
    }

    /**
     * GET: Get cureent store language id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return int
     */
    public function getLaguageId()
    {
        return Context::getContext()->language->id;
    }

    /**
     * GET: Get current store id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return int
     */
    public function getStoreId()
    {
        return Context::getContext()->shop->id;
    }

    /**
     * Get Product thumbnail image
     *
     * @param $productId Product id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Image id array
     */
    public function getProductImageByPid($productId, $idShop = 1)
    {
        $imageArr = array();
        $sql = "SELECT image_shop.id_image FROM " . _DB_PREFIX_ . "image i INNER JOIN " . _DB_PREFIX_ . "image_shop image_shop ON (image_shop.id_image = i.id_image AND image_shop.id_shop = " . $idShop . ") WHERE i.id_product = " . $productId;

        return $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }
    /**
     * Get Product thumbnail image
     *
     * @param $imageId image id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Image path
     */
    public function getProductThumbnail($imageId)
    {
        $image = new \Image($imageId);
        return $thumbnail = $this->getBaseUrl() . _THEME_PROD_DIR_ . $image->getExistingImgPath() . "-small_default.jpg";
    }

    /**
     * Get Store base url
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return String
     */
    public function getBaseUrl()
    {
        $custom_ssl_var = 0;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $custom_ssl_var = 1;
        }
        if ((bool) \Configuration::get('PS_SSL_ENABLED') && $custom_ssl_var == 1) {
            $baseUrl = _PS_BASE_URL_SSL_;
        } else {
            $baseUrl = _PS_BASE_URL_;
        }
        return $baseUrl;
    }

    /**
     * Get Product cover image
     *
     * @param $id_product Product id
     * @param $context    class
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Image path
     */
    public function getProductCoverImageId($id_product, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        $cache_id = 'Product::getCover_' . (int) $id_product . '-' . (int) $context->shop->id;
        if (!Cache::isStored($cache_id)) {
            $sql = 'SELECT image_shop.`id_image`
                    FROM `' . _DB_PREFIX_ . 'image` i
                    ' . Shop::addSqlAssociation('image', 'i') . '
                    WHERE i.`id_product` = ' . (int) $id_product . '
                    AND image_shop.`cover` = 1';
            $result = Db::getInstance()->getRow($sql);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }

    /**
     * Get All resource countable
     *
     * @param $resource Product, Order
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function countResource($resource)
    {
        $parameter = array(
            'resource' => '' . $resource . '',
            'display' => '[id]',
            'output_format' => 'JSON',
            'language' => '' . $this->getLaguageId() . '',
        );

        $json = $this->get($parameter);
        return json_decode($json, true);
    }

    /**
     * Get product count with filter
     *
     * @param $resource Product, Order
     *
     * @author tapasranjanp@riaxe.com
     * @date   12 Feb 2021
     * @return Array
     */
    public function countProducts($parameter, $search = '')
    {
        $lang_id = $this->getLaguageId();
        // Get predecorated product count with search
        if ($parameter == 'predeco' && $search != '') {
            $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_lang  pl JOIN ' . _DB_PREFIX_ . 'product p WHERE name LIKE "%' . $search . '%" AND p.id_product = pl.id_product AND p.active = 1 AND id_lang = ' . $lang_id . ' AND p.xe_is_temp != 0';
        } elseif ($parameter == 'predeco') {
            $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product WHERE xe_is_temp != 0 AND active = 1';
        }
        // Get catalog product count with search
        if ($parameter == 'catalog' && $search != '') {
            $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_lang  pl JOIN ' . _DB_PREFIX_ . 'product p WHERE name LIKE "%' . $search . '%" AND p.id_product = pl.id_product AND p.active = 1 AND id_lang = ' . $lang_id . ' AND p.is_catalog != 0';
        } elseif ($parameter == 'catalog') {
            $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product WHERE is_catalog != 0 AND active = 1 ';
        }
        // Get all product count
        if ($parameter == 'all' && $search != '') {
            $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product_lang  pl JOIN ' . _DB_PREFIX_ . 'product p WHERE name LIKE "%' . $search . '%" AND p.id_product = pl.id_product AND p.active = 1 AND id_lang = ' . $lang_id;
        } elseif ($parameter == 'all') {
            $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'product WHERE  active = 1';
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Get product stock from store by product id
     *
     * @param $pid Product id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return int
     */
    public function getProductStock($pid,$id_product_attribute)
    {
        $idShop = (int) Context::getContext()->shop->id;
        $query = new \DbQuery();
        $query->select('SUM(quantity)');
        $query->from('stock_available');
        // if null, it's a product without attributes
        if ($pid) {
            $query->where('id_product = ' . (int)$pid);
        } else {
            $query->where('id_product_attribute = ' . (int) $id_product_attribute);
        }
        $query = \StockAvailable::addSqlShopRestriction($query, $idShop);
        $result = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        return $result;
    }

    /**
     * Generate thumb images from store product images by using store end image urls
     *
     * @param $imageId Product image id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Image path
     */
    public function getProductImage($imageId)
    {
        $image = new \Image($imageId);
        return $thumbnail = $this->getBaseUrl() . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
    }

    /**
     * Get product stock from store by product id
     *
     * @param $productCombinationId Product combination id
     * @param $productId            Product  id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return int
     */
    public function getProducImage($productCombinationId, $productId)
    {
        $idLang = $this->getLaguageId();
        $images = array();
        $product = new \Product($productId);
        if ($productCombinationId) {
            $imageArr = $product->getCombinationImages($idLang);
            if (!empty($imageArr)) {
                $images = $imageArr[$productCombinationId];
            }
        } else {
            $images = $product->getImages($idLang);
        }
        $itemImageArr = array();
        if (!empty($images)) {
            $i = 0;
            foreach ($images as $v) {
                $itemImageArr[$i]['id'] = $v['id_image'];
                $imageObj = new \Image($v['id_image']);
                // get image full URL
                $sideIamgeUrl = $this->getBaseUrl() . _THEME_PROD_DIR_ . $imageObj->getExistingImgPath() . ".jpg"; //for product thumbnail
                $thumbnail = $this->getBaseUrl() . _THEME_PROD_DIR_ . $imageObj->getExistingImgPath() . "-small_default.jpg";
                $itemImageArr[$i]['src'] = $sideIamgeUrl;
                $itemImageArr[$i]['thumbnail'] = $thumbnail;
                $i++;
            }
        }
        return $itemImageArr;
    }

    /**
     * GET: Get Color hexa code value
     *
     * @param $idAttribute Attribute id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getColorHexValue($idAttribute)
    {
        $sql_fetch = "SELECT color FROM " . _DB_PREFIX_ . "attribute WHERE id_attribute = " . $idAttribute . "";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_fetch);
        if (@file_exists(_PS_COL_IMG_DIR_ . $idAttribute . '.jpg')) {
            $color = $this->getBaseUrl() . _THEME_COL_DIR_ . (int) $idAttribute . '.jpg';
        } else {
            $color = $result[0]['color'];
        }
        return $color;
    }

    /**
     * GET: Get PrestaShop version
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return String
     */
    public function getPrestaShopVersion()
    {
        return _PS_VERSION_;
    }

    /**
     * Get required informations on best sales products.
     *
     * @param $option Product parameters
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return from Product::getProductProperties
     *                    `false` if failure
     */
    public function getPopularProducts($option)
    {
        $productArray = [];
        $langId = (int) Context::getContext()->cookie->id_lang;
        $category = new Category((int) Configuration::get('HOME_FEATURED_CAT'));
        $nProducts = Configuration::get('HOME_FEATURED_NBR');
        if ($option['nb_products'] < $nProducts) {
            $nProducts = $option['nb_products'];
        }
        if (Configuration::get('HOME_FEATURED_RANDOMIZE') == 1) {
            $products = $category->getProducts(
                $langId, 0, $nProducts, $option['order_by'],
                $option['order_way'], null, true, true, $nProducts
            );
        } else {
            $products = $category->getProducts(
                $langId, 0, $nProducts, $option['order_by'], $option['order_way']
            );
        }
        if (is_array($products)
            && count($products) > 0
        ) {
            $i = 0;
            foreach ($products as $v) {
                $productId = $v['id_product'];
                $imageIdArr = $this->getProductImageByPid(
                    $productId
                );
                // get Image by id
                if (sizeof($imageIdArr) > 0) {
                    foreach ($imageIdArr as $imageId) {
                        $thumbnail = $this->getProductThumbnail(
                            $imageId['id_image']
                        );
                        $productArray[$i]['image'][] = $thumbnail;
                    }
                }
                $productArray[$i]['id'] = $productId;
                $variationId = ($v['cache_default_attribute'] == 0
                    ? $productId : $v['cache_default_attribute']);
                $productArray[$i]['variation_id'] = $variationId;
                $productArray[$i]['name'] = $v['name'];
                $productArray[$i]['type'] = $v['cache_default_attribute'] == 0
                ? 'simple' : 'variable';
                $productArray[$i]['sku'] = $v['reference'];
                $productArray[$i]['price'] = $v['price'];
                $i++;
            }
            $productArray = array_values($productArray);
        }
        return $productArray;
    }

    /**
     * GET: Get All attribute list from store
     *
     * @param $option Attribute parameter
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function storeAttributeList($option)
    {
        $attributeId = $attributeList = [];
        $attr = new \AttributeCore();
        $list = $attr->getAttributes($this->getLaguageId());
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $attribute = $attributeValues = [];
                if (!in_array($value['id_attribute_group'], $attributeId)) {
                    $attribute['id'] = $value['id_attribute_group'];
                    $attribute['slug'] = $value['public_name'];
                    $attribute['name'] = $value['public_name'];
                    $attribute['type'] = $value['group_type'];
                    array_push($attributeList, $attribute);
                    array_push($attributeId, $value['id_attribute_group']);
                } else {
                    $key = array_search($value['id_attribute_group'], $attributeId);
                    $attributeValues['id'] = $value['id_attribute'];
                    $attributeValues['name'] = $value['name'];
                    $attributeValues['slug'] = $value['name'];
                    $attributeList[$key]['terms'][] = $attributeValues;
                }
            }
        }
        return $attributeList;
    }

    /**
     * GET: Get Customer details
     *
     * @param $idCustomer Customer id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getAttributeGroups()
    {
        try {
            $attributeObj = new \AttributeGroupCore();
            $attributeList = $attributeObj->getAttributesGroups($this->getLaguageId());
            $attributes = array();
            if (!empty($attributeList)) {
                foreach ($attributeList as $k => $value) {
                    $attributes[$k]['id'] = $value['id_attribute_group'];
                    $attributes[$k]['name'] = strtolower($value['name']);
                }
            }
        } catch (PrestaShopDatabaseException $e) {
            echo 'Database error: <br />' . $e->displayMessage();
        }
        return $attributes;
    }

    /**
     * GET: Get Customer details
     *
     * @param $idCustomer Customer id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getCustomerName($idCustomer)
    {
        $customerArr = array();
        // Load customer object
        $customer = new \Customer((int) $idCustomer);
        // Validate customer object
        if (\Validate::isLoadedObject($customer)) {
            $customerArr['first_name'] = $customer->firstname;
            $customerArr['last_name'] = $customer->lastname;
            $customerArr['email'] = $customer->email;
        } else {
            $customerArr['first_name'] = '';
            $customerArr['last_name'] = '';
            $customerArr['email'] = '';
        }
        return $customerArr;
    }

    /**
     * GET: Get currency ISO code
     *
     * @param $idCurrency Currency id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getCurrencyIsoCode($idCurrency)
    {
        $isoCode = '';
        $currency = \Currency::getCurrency($idCurrency);
        if (!empty($currency)) {
            $isoCode = $currency['iso_code'];
        }
        return $isoCode;
    }

    /**
     * GET: Get order status by order id
     *
     * @param $orderId Order id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getOrderStatus($orderId)
    {
        $orders = new \Order($orderId);
        $orderStates = $orders->getCurrentStateFull($this->getLaguageId());
        return $orderStates['name'] ? $orderStates['name'] : '';
    }

    /**
     * GET: Get Line Item of Orders
     *
     * @param $orderId Order id
     * @param $shopId Shop id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getOrderByOrderId($orderId, $shopId)
    {
        $jsonResponse = [];
        $parameterl = array(
            'resource' => 'orders',
            'display' => 'full',
            'filter[id]' => '[' . $orderId . ']',
            'id_shop' => $shopId,
            'output_format' => 'JSON',
        );
        $jsonData = $this->get($parameterl);
        //return json format
        $ordersArr = json_decode($jsonData, true);
        $singleOrderDetails = $ordersArr['orders'][0];
        $customerId = $singleOrderDetails['id_customer'];
        $parameter = array(
            'resource' => 'orders',
            'display' => '[id]',
            'filter[id_customer]' => '[' . $customerId . ']', 'output_format' => 'JSON',
        );
        $orderJonData = $this->get($parameter);
        $orderData = json_decode($orderJonData, true);
        $countOrder = sizeof($orderData['orders']);
        $customer = $this->getCustomerName($customerId);
        if (!empty($customer) && $customer['email'] != '') {
            $address = new \Address(intval($singleOrderDetails['id_address_invoice']));

            $state = \State::getNameById($address->id_state);
            $billing['first_name'] = $address->firstname;
            $billing['last_name'] = $address->lastname;
            $billing['company'] = $address->company;
            $billing['address_1'] = $address->address1;
            $billing['address_2'] = $address->address2;
            $billing['city'] = $address->city;
            $billing['state'] = $state ? $state : '';
            $billing['postcode'] = $address->postcode;
            $billing['country'] = $address->country;
            $billing['email'] = $customer['email'];
            $billing['phone'] = $address->phone;

            $addressInvoice = new \Address(intval($singleOrderDetails['id_address_delivery']));

            $stateAddressInvoice = \State::getNameById($addressInvoice->id_state);
            $shipping['first_name'] = $addressInvoice->firstname;
            $shipping['last_name'] = $addressInvoice->lastname;
            $shipping['company'] = $addressInvoice->company;
            $shipping['address_1'] = $addressInvoice->address1;
            $shipping['address_2'] = $addressInvoice->address2;
            $shipping['city'] = $addressInvoice->city;
            $shipping['state'] = $stateAddressInvoice ? $stateAddressInvoice : '';
            $shipping['postcode'] = $addressInvoice->postcode;
            $shipping['country'] = $addressInvoice->country;
            $shipping['email'] = $customer['email'];
            $shipping['phone'] = $addressInvoice->phone;
        } else {
            $shipping = $billing = ['first_name' => '', 'last_name' => '', 'company' => '', 'address_1' => '', 'city' => '', 'state' => '', 'postcode' => '', 'country' => '', 'email' => '', 'phone' => ''];
        }

        $idShop = $singleOrderDetails['id_shop'];
        $shopObj = new \Shop($idShop);
        $storeUrl = $shopObj->getBaseURL();

        $lineOrders = array();
        $i = 0;
        $customDesignId = 0;
        foreach ($singleOrderDetails['associations']['order_rows'] as $v) {
            $lineOrders[$i]['id'] = $v['id'];
            $lineOrders[$i]['product_id'] = $v['product_id'];
            $lineOrders[$i]['variant_id'] = $v['product_attribute_id']
            ? $v['product_attribute_id'] : $v['product_id'];
            $lineOrders[$i]['name'] = $v['product_name'];
            $lineOrders[$i]['price'] = $v['unit_price_tax_excl'];
            $lineOrders[$i]['quantity'] = $v['product_quantity'];
            $lineOrders[$i]['total'] = $v['unit_price_tax_excl'] * $v['product_quantity'];
            $lineOrders[$i]['sku'] = $v['product_reference'];
            $lineOrders[$i]['custom_design_id'] = ($v['ref_id']) ? $v['ref_id'] : '';
            $lineOrders[$i]['images'] = $this->getProducImage(
                $v['product_attribute_id'], $v['product_id']
            );
            $i++;
        }
        $totalPaidTaxExc = $singleOrderDetails['total_paid_tax_excl'];
        $totalPaidTaxInc = $singleOrderDetails['total_paid_tax_incl'];
        $totalShippingExc = $singleOrderDetails['total_shipping_tax_excl'];
        $totalShippingInc = $singleOrderDetails['total_shipping_tax_incl'];
        $discount = $singleOrderDetails['total_discounts'];
        $shippingCost = 0;
        if ($totalShippingInc > $totalShippingExc) {
            $shippingCost = $totalShippingInc;
        } else {
            $shippingCost = $totalShippingExc;
        }
        $totalPaid = $singleOrderDetails['total_paid'] + $discount;
        $totalTax = $totalPaidTaxInc - $totalPaidTaxExc;
        $totalAmount = $totalPaid - $totalTax - $shippingCost;
        $orders = [
            'id' => $singleOrderDetails['id'],
            'order_number' => $singleOrderDetails['id'],
            'customer_first_name' => $address->firstname,
            'customer_last_name' => $address->lastname,
            'customer_email' => $customer['email'],
            'customer_id' => $singleOrderDetails['id_customer'],
            'created_date' => date(
                'Y-m-d h:i:s', strtotime(
                    $singleOrderDetails['date_add']
                )
            ),
            'total_amount' => $this->convertToDecimal($totalAmount, 2),
            'total_tax' => $this->convertToDecimal($totalTax, 2),
            'total_shipping' => $this->convertToDecimal($shippingCost, 2),
            'total_discounts' => $this->convertToDecimal($discount, 2),
            'currency' => $this->getCurrencyIsoCode(
                $singleOrderDetails['id_currency']
            ),
            'note' => '',
            'status' => $this->getOrderStatus($orderId),
            'total_orders' => $countOrder,
            'billing' => $billing,
            'shipping' => $shipping,
            'orders' => isset($lineOrders) ? $lineOrders : [],
            'payment' => $singleOrderDetails['payment'],
            'store_url' => $storeUrl,

        ];
        $jsonResponse = [
            'data' => $orders,
        ];
        return $jsonResponse;
    }

    /**
     * GET: Get customer address details
     *
     * @param $customerId Customer id
     * @param $email      Customer email
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getAddressByCutsomerId($customerId, $email)
    {
        $idLang = $this->getLaguageId();
        $parameter = array(
            'resource' => 'addresses',
            'display' => 'full',
            'filter[id_customer]' => '[' . $customerId . ']', 'filter[deleted]' => '[0]',
            'output_format' => 'JSON',
        );
        $jsonData = $this->get($parameter);
        $addressJson = json_decode($jsonData, true);
        $addressArr = $addressJson['addresses'];
        $resultArr = $billingArr = $shippingArr = array();
        if (!empty($addressArr)) {
            $addressId = $addressArr[0]['id'];
            $billingArr['address_1'] = $addressArr[0]['address1'];
            $billingArr['address_2'] = $addressArr[0]['address2'];
            $billingArr['city'] = $addressArr[0]['city'];
            $state = \State::getNameById($addressArr[0]['id_state']);
            $billingArr['state'] = $state ? $state : '';
            $billingArr['postcode'] = $addressArr[0]['postcode'];
            $billingArr['phone'] = (string) $addressArr[0]['phone'];
            $billingArr['email'] = $email;
            $countryName = \Country::getNameById(
                $idLang, $addressArr[0]['id_country']
            );
            $billingArr['country'] = $countryName ? $countryName : '';
            $i = 0;
            foreach ($addressArr as $key => $value) {
                if ($addressId != $value['id']) {
                    $shippingArr[$i]['address_1'] = $value['address1'];
                    $shippingArr[$i]['address_2'] = $value['address2'];
                    $shippingArr[$i]['city'] = $value['city'];
                    $state = \State::getNameById($value['id_state']);

                    $shippingArr[$i]['postcode'] = $value['postcode'];
                    $shippingArr[$i]['phone'] = (string) $value['phone'];
                    $isoStateCode = '';
                    $isoStateCode = $this->getSateIsoById($value['id_state'], $value['id_country']);
                    $isoCountryCode = '';
                    $isoCountryCode = $this->getCountryIsoById($value['id_country']);
                    $shippingArr[$i]['state'] = $isoStateCode;
                    $countryName = \Country::getNameById(
                        $idLang, $value['id_country']
                    );
                    $shippingArr[$i]['country'] = $isoCountryCode ? $isoCountryCode : '';
                    $shippingArr[$i]['mobile_no'] = (string) $value['phone'];
                    $shippingArr[$i]['country_name'] = $countryName ? $countryName : '';
                    $shippingArr[$i]['state_name'] = $state ? $state : '';
                    $shippingArr[$i]['id'] = $value['id'];
                    if ($i == 0) {
                        $is_default = 1;
                    } else {
                        $is_default = 0;
                    }
                    $shippingArr[$i]['is_default'] = $is_default;
                    $i++;
                }
            }
        }
        if (empty($shippingArr) && !empty($billingArr)) {
            $shippingArr[0] = $billingArr;
        }
        $resultArr['shipping_address'] = $shippingArr;
        $resultArr['billing_address'] = $billingArr;
        return $resultArr;
    }

    /**
     * GET: Get Order details of customer
     *
     * @param $customerId Customer id
     * @param $isOrder is order
     * @param $storeId Shop id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getOrderDetailsByCustomerId($customerId, $isOrder, $storeId)
    {
        $orderDetails = $items = [];
        $parameter = array(
            'resource' => 'orders', 'display' => 'full',
            'filter[id_customer]' => '[' . $customerId . ']',
            'sort' => '[id_DESC]',
            'output_format' => 'JSON',
            'id_shop' => $storeId,
        );
        $jsonData = $this->get($parameter);
        $orderArr = json_decode($jsonData, true);
        if (!empty($orderArr)) {
            $totalOrderAmount = $averageAmount = 0;
            $i = 0;
            foreach ($orderArr['orders'] as $order) {
                $totalOrderAmount += $order['total_paid'];
                $items[$i]['id'] = $order['id'];
                $items[$i]['created_date'] = $order['date_add'];
                $items[$i]['currency'] = $this->getCurrencyIsoCode(
                    $order['id_currency']
                );
                $items[$i]['total_amount'] = $order['total_paid'];
                $productQuantity = 0;
                foreach ($order['associations']['order_rows'] as $item) {
                    $productQuantity += $item['product_quantity'];
                }
                $items[$i]['quantity'] = $productQuantity;
                if ($isOrder) {
                    $items[$i]['lineItems'] = $this->getOrderByOrderItemDetails($order['id']);
                }
                $i++;
            }
            if (!empty($items)) {
                $averageAmount = $totalOrderAmount / count($items);
            }
            $orderDetails['order_item'] = $items;
            $orderDetails['total_order_amount'] = $totalOrderAmount;
            $orderDetails['average_order_amount'] = $averageAmount;
        }
        return $orderDetails;
    }

    /**
     * GET: Get customer last order date
     *
     * @param $customerId Customer id
     * @param $storeId Customer id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return String date
     */
    public function getLastOrderDateByCustomerId($customerId, $storeId)
    {
        $lastOrderDate = '';
        $parameter = array(
            'resource' => 'orders', 'display' => '[date_add]',
            'sort' => '[id_DESC]', 'filter[id_customer]' => '[' . $customerId . ']',
            'limit' => '1', 'output_format' => 'JSON',
            'id_shop' => $storeId,
        );
        $jsonData = $this->get($parameter);
        $orderArr = json_decode($jsonData, true);
        if (!empty($orderArr)) {
            $lastOrderDate = $orderArr['orders'][0]['date_add'];
        }
        return $lastOrderDate;
    }

    /**
     * GET: Get customer last order id
     *
     * @param $customerId Customer id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Int order id
     */
    public function getLastOrderIdByCustomerId($customerId, $idShop)
    {
        $parameter = array(
            'resource' => 'orders', 'display' => 'full',
            'sort' => '[id_DESC]', 'filter[id_customer]' => '[' . $customerId . ']',
            'id_shop' => $idShop,
            'limit' => '1', 'output_format' => 'JSON',
        );
        $jsonData = $this->get($parameter);
        $orderArr = json_decode($jsonData, true);
        if (!empty($orderArr)) {
            return $orderArr['orders'][0]['id'];
        } else {
            return 0;
        }
    }

    /**
     * GET: Get customer total order countable
     *
     * @param $customerId Customer id
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Int
     */
    public function getTotalOrderCountByCustomerId($customerId, $idShop)
    {
        $parameter = array(
            'resource' => 'orders', 'display' => '[id]',
            'id_shop' => $idShop,
            'filter[id_customer]' => '[' . $customerId . ']', 'output_format' => 'JSON',
        );
        $jsonData = $this->get($parameter);
        $orderArr = json_decode($jsonData, true);
        if (!empty($orderArr)) {
            return count($orderArr['orders']);
        } else {
            return 0;
        }
    }

    /**
     * GET: Get product list by category id
     *
     * @param $option product parameter
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getProductsByCategoryId($option)
    {
        $productArray = [];
        $categoryId = $option['category_id'];
        $langId = (int) Context::getContext()->cookie->id_lang;
        $category = new Category((int) $categoryId);
        $nProducts = $option['nb_products'];
        $products = $category->getProducts(
            $langId, 0, $nProducts, $option['order_by'], $option['order_way']
        );
        if (is_array($products)
            && count($products) > 0
        ) {
            $i = 0;
            foreach ($products as $v) {
                $productId = $v['id_product'];
                $imageIdArr = $this->getProductImageByPid(
                    $productId
                );
                // get Image by id
                if (sizeof($imageIdArr) > 0) {
                    foreach ($imageIdArr as $imageId) {
                        $thumbnail = $this->getProductThumbnail(
                            $imageId['id_image']
                        );
                        $productArray[$i]['image'][] = $thumbnail;
                    }
                }
                $productArray[$i]['id'] = $productId;
                $variationId = ($v['cache_default_attribute'] == 0
                    ? $productId : $v['cache_default_attribute']);
                $productArray[$i]['variation_id'] = $variationId;
                $productArray[$i]['name'] = $v['name'];
                $productArray[$i]['type'] = $v['cache_default_attribute'] == 0
                ? 'simple' : 'variable';
                $productArray[$i]['sku'] = $v['reference'];
                $productArray[$i]['price'] = $v['price'];
                $i++;
            }
            $productArray = array_values($productArray);
        }
        return $productArray;
    }

    /**
     * GET: Count product list by category id
     *
     * @param $option product parameter
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Int
     */
    public function getProductCountByCategoryId($option)
    {
        $productCount = 0;
        $categoryId = $option['category_id'];
        $langId = (int) Context::getContext()->cookie->id_lang;
        $category = new Category((int) $categoryId);
        $nProducts = $option['nb_products'];
        $productCount = $category->getProducts(
            $langId, 0, $nProducts, $option['order_by'], $option['order_way'], true
        );
        return $productCount;
    }

    /**
     * POST:Add color value or image
     *
     * @param $param Color attributes array
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Boolean
     */
    public function saveColorValue($param)
    {
        $attributeId = 0;
        $attributeId = isset($param['attribute_id']) ? $param['attribute_id'] : 0;
        if ($attributeId > 0) {
            if (is_array($_FILES) && !empty($_FILES)) {
                if (!empty($_FILES['upload']['tmp_name'])) {
                    $uploadedFile = $_FILES['upload']['tmp_name'];
                    $filePath = _PS_COL_IMG_DIR_ . $param['attribute_id'] . '.jpg';
                    if (@file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $image = imagecreatefrompng($uploadedFile);
                    imagejpeg($image, $filePath, 70);
                    imagedestroy($image);
                    chmod($filePath, 0755);
                }
            } else {
                $filePath = _PS_COL_IMG_DIR_ . $param['attribute_id'] . '.jpg';
                if (@file_exists($filePath)) {
                    unlink($filePath);
                }
                $hexCode = $param['hex_code'];
                $query = "UPDATE " . _DB_PREFIX_ . "attribute SET color= '" . $hexCode . "' WHERE id_attribute = '" . $attributeId . "'";
                Db::getInstance()->Execute($query);
            }
            return true;
        } else {
            $colorName = $param['name'];
            $colorGroupId = $param['color_id'];
            $idLang = Context::getContext()->language->id;
            if (is_array($_FILES) && !empty($_FILES)) {
                if (!empty($_FILES['upload']['tmp_name'])) {
                    $colorHexaValue = '';
                    $checkExit = $this->isAttributeExit($colorGroupId, $colorName, $idLang);
                    if (empty($checkExit)) {
                        $newAttribute = new Attribute();
                        $newAttribute->name = $this->createMultiLangFields($colorName);
                        $newAttribute->id_attribute_group = $colorGroupId;
                        $newAttribute->color = $colorHexaValue;
                        $newAttribute->position = 0;
                        $status = $newAttribute->add();
                    }
                    $colorSql = "SELECT al.id_attribute from " . _DB_PREFIX_ . "attribute_lang as al,
                    " . _DB_PREFIX_ . "attribute as atr
                    where id_attribute_group =" . $colorGroupId . " and atr.id_attribute = al.id_attribute and al.name='" . $colorName . "' and al.id_lang=" . intval($idLang) . "";
                    $rowColor = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($colorSql);
                    if (!empty($rowColor)) {
                        $attributeId = $rowColor[0]['id_attribute'];
                        $uploadedFile = $_FILES['upload']['tmp_name'];
                        $filePath = _PS_COL_IMG_DIR_ . $attributeId . '.jpg';
                        if (@file_exists($filePath)) {
                            unlink($filePath);
                        }
                        $image = imagecreatefrompng($uploadedFile);
                        imagejpeg($image, $filePath, 70);
                        imagedestroy($image);
                        chmod($filePath, 0755);
                    }
                }
            } else {
                $colorHexaValue = $param['hex_code'];
                $checkExit = $this->isAttributeExit($colorGroupId, $colorName, $idLang);
                if (empty($checkExit) && $colorHexaValue != '') {
                    $newAttribute = new Attribute();
                    $newAttribute->name = $this->createMultiLangFields($colorName);
                    $newAttribute->id_attribute_group = $colorGroupId;
                    $newAttribute->color = $colorHexaValue;
                    $newAttribute->position = 0;
                    $status = $newAttribute->add();
                }
                $colorSql = "SELECT al.id_attribute from " . _DB_PREFIX_ . "attribute_lang as al,
                " . _DB_PREFIX_ . "attribute as atr
                where id_attribute_group =" . $colorGroupId . " and atr.id_attribute = al.id_attribute and al.name='" . $colorName . "' and al.id_lang=" . intval($idLang) . "";
                $rowColor = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($colorSql);
                if (!empty($rowColor)) {
                    $attributeId = $rowColor[0]['id_attribute'];
                }
            }
            $attribute['id'] = $attributeId;
            return $attribute;
        }
    }

    /**
     * GET: Get color attribute value list
     *
     * @param $option Color name
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array
     */
    public function getColors($option)
    {
        $attribute = [];
        $groupName = $option['color'];
        $list = $this->getAttributes($this->getLaguageId());
        if (!empty($list)) {
            $attributeGroupId = 0;
            $attributeGroupName = '';
            $i = 0;
            foreach ($list as $key => $value) {
                if ($value['public_name'] == $groupName) {
                    $attributeGroupId = $value['id_attribute_group'];
                    $attributeGroupName = $value['public_name'];
                    $attribute['data'][$i]['id'] = $value['id_attribute'];
                    $attribute['data'][$i]['slug'] = $value['name'];
                    $attribute['data'][$i]['name'] = $value['name'];
                    if (@file_exists(_PS_COL_IMG_DIR_ . $value['id_attribute'] . '.jpg')) {
                        $attribute['data'][$i]['file_name'] = $this->getBaseUrl() . _THEME_COL_DIR_ . (int) $value['id_attribute'] . '.jpg';
                    } else {
                        $attribute['data'][$i]['hex_code'] = $value['color'];
                    }
                    $i++;
                }
            }
            $attribute['colorId'] = $attributeGroupId;
            $attribute['group_name'] = $attributeGroupName;
        }
        return $attribute;
    }

    /**
     * Get all attributes for a given language.
     *
     * @param int $idLang Language ID
     * @param bool $notNull Get only not null fields if true
     *
     * @return array Attributes
     */
    public static function getAttributes($idLang, $notNull = false)
    {
        if (!Combination::isFeatureActive()) {
            return array();
        }

        return Db::getInstance()->executeS('
            SELECT DISTINCT ag.*, agl.*, a.`id_attribute`,a.`color`, al.`name`, agl.`name` AS `attribute_group`
            FROM `' . _DB_PREFIX_ . 'attribute_group` ag
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $idLang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a
                ON a.`id_attribute_group` = ag.`id_attribute_group`
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $idLang . ')
            ' . Shop::addSqlAssociation('attribute_group', 'ag') . '
            ' . Shop::addSqlAssociation('attribute', 'a') . '
            ' . ($notNull ? 'WHERE a.`id_attribute` IS NOT NULL AND al.`name` IS NOT NULL AND agl.`id_attribute_group` IS NOT NULL' : '') . '
            ORDER BY agl.`name` ASC, a.`position` ASC
        ');
    }

    /**
     * Post: Validate SKU or Name at Store end
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   10 March 2020
     * @return Validate response Array
     */
    public function checkDuplicateNameAndSku($param)
    {
        $productId = 0;
        if (!empty($param['name'])) {
            $sql = "SELECT id_product FROM " . _DB_PREFIX_ . "product_lang WHERE AND name='" . $param['name'] . "' AND id_lang=" . $this->getLaguageId() . "";
        }
        if (!empty($param['sku'])) {
            $sql = "SELECT id_product FROM " . _DB_PREFIX_ . "product WHERE reference='" . $param['sku'] . "'";
        }
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($row)) {
            $productId = $row[0]['id_product'];
        }
        return $productId;
    }

    /**
     * Get: get old product images
     *
     * @param $productCombinationId  Product combination Id
     * @param $productId product id
     *
     * @author radhanatham@riaxe.com
     * @date   10 March 2020
     * @return Array records
     */
    public function getOldProductImages($productCombinationId, $productId)
    {
        $idLang = $this->getLaguageId();
        $images = array();
        $product = new \Product($productId);
        if ($productCombinationId) {
            $imageArr = $product->getCombinationImages($idLang);
            if (!empty($imageArr)) {
                $images = $imageArr[$productCombinationId];
            } else {
                $images = $product->getImages($idLang);
            }
        } else {
            $images = $product->getImages($idLang);
        }
        $itemImageArr = array();
        if (!empty($images)) {
            $i = 0;
            foreach ($images as $v) {
                $imageObj = new \Image($v['id_image']);
                // get image full URL
                $sideIamgeUrl = $this->getBaseUrl() . _THEME_PROD_DIR_ . $imageObj->getExistingImgPath() . ".jpg"; //for product thumbnail
                $thumbnail = $this->getBaseUrl() . _THEME_PROD_DIR_ . $imageObj->getExistingImgPath() . "-small_default.jpg";
                $itemImageArr[] = $sideIamgeUrl;
                $i++;
            }
        }
        return $itemImageArr;
    }

    /**
     * Get: get product images
     *
     * @param $combinationIdArr  Product combination Array
     * @param $productId product id
     *
     * @author radhanatham@riaxe.com
     * @date   10 March 2020
     * @return Array records
     */
    public function getProductCombinationId($combinationIdArr, $productId)
    {
        $itemImageArr = array();
        foreach ($combinationIdArr as $key => $v) {
            if ($attributesId == $v['id_attribute']) {
                $itemImageArr = $this->getOldProductImages($v['variant_id'], $productId);
            }
        }
        return $itemImageArr;
    }

    /**
     * Get: get product images
     *
     * @param $combinationIdArr  Product combination Array
     * @param $productId product id
     * @param $attributeId product attribute id
     *
     * @author radhanatham@riaxe.com
     * @date   10 March 2020
     * @return Array records
     */
    public function getProductAttributeId($combinationIdArr, $productId, $attributeId)
    {
        $itemImageArr = array();
        foreach ($combinationIdArr as $key => $v) {
            if ($attributeId == $v['id_attribute']) {
                $itemImageArr = $this->getOldProductImages($v['variant_id'], $productId);
                break;
            }
        }
        return $itemImageArr;
    }

    /**
     * Post: Save predecorated products into the store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   10 March 2020
     * @return Array records and server status
     */
    public function createPredecoProduct($param)
    {
        $parameters = $param['data'];
        $parentProductId = $parameters['parentProductId'];
        $preDecoProductPrice = $parameters['regularPrice'];
        $productName = $parameters['name'];
        $images = $parameters['images'];
        $qty = $parameters['stockQuantity'];
        $sku = $parameters['sku'];
        $description = $parameters['description'];
        $shortDescription = $parameters['shortDescription'];
        $categories = $parameters['categories'];
        $isCustomized = $parameters['isRedesign'];
        $designId = $parameters['designId'];
        $attributes = $parameters['attributes'];
        $idLang = $this->getLaguageId();

        if ($designId == 0) {
            $designId = -2;
        }
        $productImageArr = array();

        $isAddTocart = 0;
        $result = array();
        $now = date('Y-m-d H:i:s', time());
        $sqlLang = "SELECT id_lang FROM " . _DB_PREFIX_ . "lang";
        $langId = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlLang);
        $idShop = (int) Context::getContext()->shop->id;
        if (!empty($images)) {
            $sizeCount = 1;
            $quantity = $totalQantity = $sizeCount ? $qty * $sizeCount : $qty;
            $productName = pSQL($productName);
            $sku = pSQL($sku);
            $description = pSQL($description);
            $shortDescription = pSQL($shortDescription);
            //added new predecoproduct
            $productSql = "INSERT INTO " . _DB_PREFIX_ . "product(id_supplier,id_manufacturer,id_category_default,id_tax_rules_group,price,reference,active,redirect_type,indexed,cache_default_attribute,date_add,date_upd,customize,xe_is_temp,is_addtocart)
            VALUES('1','1','$categories[0]','1','$preDecoProductPrice','$sku','1','404','1','','$now','$now','$isCustomized'," . $designId . "," . $isAddTocart . ")";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($productSql);
            $productId = Db::getInstance()->Insert_ID();
            //ps_product_shop
            if (_PS_VERSION_ >= '1.7.2.2') {
                $productShopSql = "INSERT INTO `" . _DB_PREFIX_ . "product_shop` (`id_product`, `id_shop`, `id_category_default`, `id_tax_rules_group`,
                 `minimal_quantity`, `price`, `active`, `redirect_type`, `available_for_order`, `condition`, `show_price`, `indexed`, `visibility`,
                    `cache_default_attribute`, `advanced_stock_management`, `date_add`, `date_upd`, `pack_stock_type`)
                 VALUES ('$productId', '$idShop', '$categories[0]', '1', '1', '$preDecoProductPrice', '1', '404', '1', 'new', '1', '1', 'both', '0', '0', '$now', '$now', '3')";
            } else {
                $productShopSql = "INSERT INTO `" . _DB_PREFIX_ . "product_shop` (`id_product`, `id_shop`, `id_category_default`, `id_tax_rules_group`,
                 `minimal_quantity`, `price`, `active`, `redirect_type`, `id_product_redirected`, `available_for_order`, `condition`, `show_price`, `indexed`, `visibility`,
                    `cache_default_attribute`, `advanced_stock_management`, `date_add`, `date_upd`, `pack_stock_type`)
                 VALUES ('$productId', '$idShop', '$categories[0]', '1', '1', '$preDecoProductPrice', '1', '404', '0', '1', 'new', '1', '1', 'both', '0', '0', '$now', '$now', '3')";
            }
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($productShopSql);
            $linkRewrite = Tools::str2url($productName);
            foreach ($langId as $v) {
                $productLangSql = "INSERT INTO " . _DB_PREFIX_ . "product_lang(id_product,id_shop,id_lang,description,description_short,link_rewrite,
                    meta_description,meta_keywords,meta_title,name,available_now,available_later)
                VALUES('$productId','$idShop','" . $v['id_lang'] . "','$description','$shortDescription','$linkRewrite','','','','$productName','','')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($productLangSql);
            }
            if ($productId) {
                //add category to product //
                if (!empty($categories)) {
                    $this->addToCategoriesToProduct($categories, $productId);
                }

                if (!empty($attributes)) {
                    $product = new \Product($parentProductId);
                    $combinations = $product->getAttributeCombinations((int) ($idLang));
                    $combinationIdArr = array();
                    foreach ($combinations as $k => $v) {
                        $combinationIdArr[$v['id_product_attribute']]['variant_id'] = $v['id_product_attribute'];
                        $combinationIdArr[$v['id_product_attribute']]['id_attribute'][] = $v['id_attribute'];
                    }

                    $image = array();
                    foreach ($attributes as $key => $attributesId) {
                        $productImageArr = $this->getProductCombinationId($combinationIdArr, $parentProductId);
                        if (empty($productImageArr)) {
                            $attributeId = $attributesId[0];
                            $productImageArr = $this->getProductAttributeId($combinations, $parentProductId, $attributeId);
                        }
                        if ($key == 0) {
                            $unsetProductImageArr = $productImageArr;
                            foreach ($unsetProductImageArr as $unsetKey => $value) {
                                unset($productImageArr[$unsetKey]);
                            }
                            $image = array_merge($images, $productImageArr);
                        } else {
                            $image = $productImageArr;
                        }
                        $imageId = $this->addImageByProductId($image, $productId, $productName, $langId, $idShop, $key);
                        $attrId = $this->addProductAttributesByProductId($attributesId, $productId, $sku, $idShop, $key);
                        if ($attrId) {
                            $this->addProductStock($attrId, $productId, $totalQantity, $qty);
                            if ($imageId) {
                                $this->addImageAttributes($attrId, $imageId);
                            }
                            $this->updateTotalQuantityByPid($productId);
                        }
                    }
                } else {
                    $imageId = $this->addImageByProductId($images, $productId, $productName, $langId, $idShop, 0);
                    $this->addProductStock(0, $productId, $totalQantity, $qty);
                }
                $result['id'] = $productId;
            }
        }
        return $result;
    }

    /**
     *Add product combination/atrribute image
     *
     * @param (Int)productId
     * @param (Int)totalQantity
     * @param (Int)qty
     * @return nothing
     *
     */
    public function addStock($productId, $totalQantity, $qty)
    {
        $idShop = (int) Context::getContext()->shop->id;
        $sql = "INSERT INTO " . _DB_PREFIX_ . "stock_available (id_product,id_product_attribute,id_shop,id_shop_group,quantity,out_of_stock) VALUES(" . $productId . ",'0'," . $idShop . ",''," . $totalQantity . ",'2')";
    }

    /**
     *Update total quantity by product id after predeco product successfully added
     *
     * @param (Int)productId
     * @return nothing
     *
     */
    public function updateTotalQuantityByPid($productId)
    {
        $sql = "SELECT quantity FROM " . _DB_PREFIX_ . "stock_available WHERE id_product = " . $productId . " AND id_product_attribute !='0'";
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        foreach ($rows as $k => $v) {
            $totalQantity += $v['quantity'];
        }
        $query = "UPDATE " . _DB_PREFIX_ . "stock_available SET quantity= '" . $totalQantity . "' WHERE id_product = " . $productId . " AND id_product_attribute='0'";
        return Db::getInstance()->Execute($query);
    }

    /**
     *Add product combination/atrribute image
     *
     * @param (Int)attrId
     * @param (Int)imageId
     * @return nothing
     *
     */
    public function addImageAttributes($attrId, $imageId = array())
    {
        if (!is_array($imageId)) {
            $imageId = array($imageId);
        }
        if (!empty($imageId)) {
            foreach ($imageId as $v) {
                $sql = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_image (id_product_attribute,id_image) VALUES(" . intval($attrId) . "," . intval($v) . ")";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
            }
        }
    }

    /**
     *add product attributes by product id
     *
     * @param (Int)productId
     * @param (Array)attributes
     * @param (String)sku
     * @param (Int)idShop
     * @return  int
     *
     */
    public function addProductAttributesByProductId($attributes, $productId, $sku, $idShop, $key)
    {
        $attrId = 0;
        if (!empty($attributes)) {
            if ($key == 0) {
                $attr_sql = "INSERT INTO " . _DB_PREFIX_ . "product_attribute(id_product,reference,default_on,xe_is_temp) VALUES('$productId','$sku','1','1')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($attr_sql);
                $attrId = Db::getInstance()->Insert_ID();
                //ps_product_atrribute_shop
                $sql_pashop = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_shop(id_product,id_product_attribute,id_shop,default_on)
                VALUES('$productId','$attrId','$idShop','1')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_pashop);
            } else {
                $attr_sql1 = "INSERT INTO " . _DB_PREFIX_ . "product_attribute(id_product,reference,xe_is_temp) VALUES('$productId','$sku','1')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($attr_sql1);
                $attrId = Db::getInstance()->Insert_ID();
                //ps_product_atrribute_shop
                $sql_pashop = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_shop(id_product,id_product_attribute,id_shop)
                VALUES('$productId','$attrId','$idShop')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_pashop);
            }
            foreach ($attributes as $k => $v) {
                //add product atttribute size and color
                $sql_insert = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_combination(id_attribute,id_product_attribute,xe_is_temp) VALUES('$v','$attrId','1')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_insert);
            }
        }
        return $attrId;
    }

    /**
     *Add product combination/atrribute image
     *
     * @param (Int)attrId
     * @param (Int)productId
     * @param (Int)totalQantity
     * @param (Int)qty
     * @return nothing
     *
     */
    public function addProductStock($attrId, $productId, $totalQantity, $qty)
    {
        $idShop = (int) Context::getContext()->shop->id;
        $sql = "INSERT INTO " . _DB_PREFIX_ . "stock_available (id_product,id_product_attribute,id_shop,id_shop_group,quantity,
                    out_of_stock) VALUES(" . $productId . ",'0'," . $idShop . ",''," . $totalQantity . ",'2')";
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
        $sqlShop = "INSERT INTO " . _DB_PREFIX_ . "stock_available (id_product,id_product_attribute,id_shop,id_shop_group,quantity,
            out_of_stock) VALUES(" . $productId . "," . $attrId . "," . $idShop . ",''," . $qty . ",'2')";
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sqlShop);
    }

    /**
     *add pre decorated  product iamge by product id
     *
     * @param (String)images
     * @param (Int)productId
     * @param (String)productName
     * @param (Int)lang_id
     * @param (Int)id_shop
     * @return  array
     *
     */
    public function addImageByProductId($images, $productId, $product_name, $lang_id, $id_shop, $key)
    {

        $i = 0;
        if (!empty($images)) {
            foreach ($images as $imageUrl) {
                $position = Image::getHighestPosition($productId) + 1;
                $cover = true; // or false;
                if ($i == 0 && $key == 0) {
                    $image_sql = "INSERT INTO " . _DB_PREFIX_ . "image(id_product,position,cover) VALUES('$productId','$position','1')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_sql);
                    $image_id[] = $id_iamge = Db::getInstance()->Insert_ID();
                    //image_lang
                    foreach ($lang_id as $v1) {
                        $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_lang(id_image,id_lang,legend) VALUES('$id_iamge','" . $v1['id_lang'] . "','$product_name')";
                        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                    }
                    //image_shop
                    $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_shop(id_product,id_image,id_shop,cover) VALUES('$productId','$id_iamge','$id_shop','1')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                    //copy product image
                    self::copyImg($productId, $id_iamge, $imageUrl, 'products', true);
                } else {
                    $image_sql1 = "INSERT INTO " . _DB_PREFIX_ . "image(id_product,position) VALUES('$productId','$position')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_sql1);
                    $image_id[] = $id_iamge = Db::getInstance()->Insert_ID();
                    //image_shop
                    $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_shop(id_product,id_image,id_shop) VALUES('$productId','$id_iamge','$id_shop')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                    //copy product image
                    self::copyImg($productId, $id_iamge, $imageUrl, 'products', true);
                }
                //image_lang
                foreach ($lang_id as $v2) {
                    $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_lang(id_image,id_lang,legend) VALUES('$id_iamge','" . $v2['id_lang'] . "','$product_name')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                }
                $i++;
            }
            return $image_id;
        } else {
            return 0;
        }
    }

    /**
     *copy product iamge
     *
     * @param (int)id_entity
     * @param (String)url
     * @param (int)id_image
     * @param (String)entity
     * @param (boolean)regenerate
     * @return boolean
     *
     */
    public function copyImg($id_entity, $id_image = null, $url, $entity = 'products', $regenerate = true)
    {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
        switch ($entity) {
            default:
            case 'products':
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int) $id_entity;
                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int) $id_entity;
                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int) $id_entity;
                break;
        }
        $url = urldecode(trim($url));
        $parced_url = parse_url($url);
        if (isset($parced_url['path'])) {
            $uri = ltrim($parced_url['path'], '/');
            $parts = explode('/', $uri);
            foreach ($parts as &$part) {
                $part = rawurlencode($part);
            }
            unset($part);
            $parced_url['path'] = '/' . implode('/', $parts);
        }
        if (isset($parced_url['query'])) {
            $query_parts = array();
            parse_str($parced_url['query'], $query_parts);
            $parced_url['query'] = http_build_query($query_parts);
        }
        if (!function_exists('http_build_url')) {
            require_once _PS_TOOL_DIR_ . 'http_build_url/http_build_url.php';
        }
        $url = http_build_url('', $parced_url);
        $orig_tmpfile = $tmpfile;
        if (Tools::copy($url, $tmpfile)) {
            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($tmpfile)) {
                @unlink($tmpfile);
                return false;
            }
            $tgt_width = $tgt_height = 0;
            $src_width = $src_height = 0;
            $error = 0;
            ImageManager::resize($tmpfile, $path . '.jpg', null, null, 'jpg', false, $error, $tgt_width, $tgt_height, 5,
                $src_width, $src_height);
            $images_types = ImageType::getImagesTypes($entity, true);
            if ($regenerate) {
                $previous_path = null;
                $path_infos = array();
                $path_infos[] = array($tgt_width, $tgt_height, $path . '.jpg');
                foreach ($images_types as $image_type) {
                    $tmpfile = self::get_best_paths($image_type['width'], $image_type['height'], $path_infos);

                    if (ImageManager::resize($tmpfile, $path . '-' . stripslashes($image_type['name']) . '.jpg', $image_type['width'],
                        $image_type['height'], 'jpg', false, $error, $tgt_width, $tgt_height, 5,
                        $src_width, $src_height)) {
                        // the last image should not be added in the candidate list if it's bigger than the original image
                        if ($tgt_width <= $src_width && $tgt_height <= $src_height) {
                            $path_infos[] = array($tgt_width, $tgt_height, $path . '-' . stripslashes($image_type['name']) . '.jpg');
                        }
                        if ($entity == 'products') {
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg');
                            }
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg');
                            }
                        }
                    }
                    if (in_array($image_type['id_image_type'], $watermark_types)) {
                        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                    }
                }
            }
        } else {
            @unlink($orig_tmpfile);
            return false;
        }
        unlink($orig_tmpfile);
        return true;
    }

    /**
     *Get product image path from store
     *
     * @param Int($tgt_width)
     * @param Int($tgt_height)
     * @param Array($path_infos)
     * @return String
     *
     */
    public function get_best_paths($tgt_width, $tgt_height, $path_infos)
    {
        $path_infos = array_reverse($path_infos);
        $path = '';
        foreach ($path_infos as $path_info) {
            list($width, $height, $path) = $path_info;
            if ($width >= $tgt_width && $height >= $tgt_height) {
                return $path;
            }
        }
        return $path;
    }

    /**
     * addToCategories add this product to the category/ies if not exists.
     *
     * @param mixed $categories id_category or array of id_category
     * @return bool true if succeed
     */
    public function addToCategoriesToProduct($categories = array(), $productId)
    {
        if (!is_array($categories)) {
            $categories = array($categories);
        }
        $categories = array_map('intval', $categories);
        $current_categories = $this->getCategories();
        $current_categories = array_map('intval', $current_categories);
        // for new categ, put product at last position
        $res_categ_new_pos = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT id_category, MAX(position)+1 newPos
            FROM `' . _DB_PREFIX_ . 'category_product`
            WHERE `id_category` IN(' . implode(',', $categories) . ')
            GROUP BY id_category');
        foreach ($res_categ_new_pos as $array) {
            $new_categories[(int) $array['id_category']] = (int) $array['newPos'];
        }
        $new_categ_pos = array();
        foreach ($categories as $id_category) {
            $new_categ_pos[$id_category] = isset($new_categories[$id_category]) ? $new_categories[$id_category] : 0;
        }
        $product_cats = array();
        foreach ($categories as $new_id_categ) {
            if (!in_array($new_id_categ, $current_categories)) {
                $product_cats[] = array(
                    'id_category' => (int) $new_id_categ,
                    'id_product' => (int) $productId,
                    'position' => (int) $new_categ_pos[$new_id_categ],
                );
            }
        }
        Db::getInstance()->insert('category_product', $product_cats);
    }

    /**
     *Get all available category
     *
     *@param nothing
     *@return array category details
     */
    public function getCategories()
    {
        try {
            $id_lang = Context::getContext()->language->id;
            $shop_id = Context::getContext()->shop->id;
            $sql = "SELECT DISTINCT c.id_category,cl.name FROM " . _DB_PREFIX_ . "category AS c," . _DB_PREFIX_ . "category_lang AS cl
            WHERE c.id_category = cl.id_category AND cl.id_lang='$id_lang' AND cl.id_shop='$shop_id' AND cl.name !='ROOT' AND c.id_parent = 2 order by c.id_category asc";
            $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            $result = array();
            if (!empty($rows)) {
                foreach ($rows as $key => $value) {
                    $result[$key]['id'] = $value['id_category'];
                    $result[$key]['name'] = $value['name'];
                }
            }
            return json_encode(array('categories' => array_values($result)));
        } catch (PrestaShopDatabaseException $ex) {
            echo 'Other error: <br />' . $ex->getMessage();
        }
    }

    /**
     *Add new custom attribute for predeco product
     *
     * @param Array($langIds)
     * @return Array
     *
     */
    public function addNewCustomAttribute($langIds)
    {
        //add size attribue
        $id_lang = Context::getContext()->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        $attributeName = 'Pdp';
        $sqlZize = "SELECT id_attribute_group from " . _DB_PREFIX_ . "attribute_group_lang where name='" . $attributeName . "' and id_lang=" . $id_lang . "";
        $resultSize = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlZize);
        if (empty($resultSize[0]['id_attribute_group'])) {
            $sql = "SELECT position FROM " . _DB_PREFIX_ . "attribute_group order by position desc limit 1";
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            $insert_sql = "INSERT INTO `" . _DB_PREFIX_ . "attribute_group` (`group_type`,`position`) VALUES('select','" . intval($row['0']['position'] + 1) . "')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql);
            $groupId = Db::getInstance()->Insert_ID();
            foreach ($langIds as $v) {
                $insert_sql1 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_lang (id_attribute_group,id_lang,name,public_name) VALUES(" . $groupId . "," . $v['id_lang'] . ",'$attributeName','pdp')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql1);
            }
            $insert_sql2 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_shop (id_attribute_group,id_shop) VALUES(" . $groupId . "," . $id_shop . ")";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql2);

            $attribute = new Attribute();
            $attribute->name = $this->createMultiLangFields('active');
            $attribute->id_attribute_group = $groupId;
            $attribute->color = '';
            $attribute->position = 0;
            $attribute->add();

            $attribute1 = new Attribute();
            $attribute1->name = $this->createMultiLangFields('inactive');
            $attribute1->id_attribute_group = $groupId;
            $attribute1->color = '';
            $attribute1->position = 0;
            $attribute1->add();

        } else {
            $exitResult = $this->isAttributeExit($resultSize[0]['id_attribute_group'], 'active', $id_lang);
            if (empty($exitResult)) {
                $attribute = new Attribute();
                $attribute->name = $this->createMultiLangFields('active');
                $attribute->id_attribute_group = $resultSize[0]['id_attribute_group'];
                $attribute->color = '';
                $attribute->position = 0;
                $attribute->add();
            }
            $exitResult = $this->isAttributeExit($resultSize[0]['id_attribute_group'], 'inactive', $id_lang);
            if (empty($exitResult)) {
                $attribute = new Attribute();
                $attribute->name = $this->createMultiLangFields('inactive');
                $attribute->id_attribute_group = $resultSize[0]['id_attribute_group'];
                $attribute->color = '';
                $attribute->position = 0;
                $attribute->add();
            }
        }
        $sql = "SELECT id_attribute from " . _DB_PREFIX_ . "attribute_lang where name='active' and id_lang=" . intval($id_lang) . "";
        $row_active = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        $resultArr['activeId'] = $row_active[0]['id_attribute'];

        $sql_attr = "SELECT id_attribute from " . _DB_PREFIX_ . "attribute_lang where name='inactive' and id_lang=" . intval($id_lang) . "";
        $row_inactive = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_attr);
        $resultArr['inActiveId'] = $row_inactive[0]['id_attribute'];
        return $resultArr;
    }

    /**
     *To check attribute is exist or not
     *
     * @param (Int)id_attribute_group
     * @param (String)name
     * @param (Int)id_lang
     * @return integer
     *
     */
    public function isAttributeExit($id_attribute_group, $name, $id_lang)
    {
        $result = Db::getInstance()->getValue('
            SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'attribute_group` ag
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a
                ON a.`id_attribute_group` = ag.`id_attribute_group`
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang . ')
            ' . Shop::addSqlAssociation('attribute_group', 'ag') . '
            ' . Shop::addSqlAssociation('attribute', 'a') . '
            WHERE al.`name` = \'' . pSQL($name) . '\' AND ag.`id_attribute_group` = ' . (int) $id_attribute_group . '
            ORDER BY agl.`name` ASC, a.`position` ASC
        ');
        return ((int) $result > 0);
    }

    /**
     *Create multi language in prestasho store
     *
     * @param (String)field
     * @return Boolean
     *
     */
    public function createMultiLangFields($field)
    {
        $res = array();
        foreach (Language::getIDs(false) as $id_lang) {
            $res[$id_lang] = $field;
        }

        return $res;
    }

    /**
     * GET : Default order statuses
     *
     * @author radhanatham@riaxe.com
     * @date   25 June 2020
     * @return Array
     */
    public function getOrderStates()
    {
        $idLang = Context::getContext()->language->id;
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT osl.name ,osl.id_order_state FROM `' . _DB_PREFIX_ . 'order_state` os
            LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = ' . (int) $idLang . ')
            WHERE deleted = 0
            ORDER BY `name` ASC');
        $statusArr = array();
        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $statusArr[$k]['value'] = $v['name'];
                $statusArr[$k]['key'] = $v['name'];
            }
        }
        return $statusArr;
    }

    /**
     * GET : Default order statuses
     *
     * @author radhanatham@riaxe.com
     * @date   25 June 2020
     * @return Array
     */
    public function getOrderStatusIdByStatus($status)
    {
        $idOrderState = 0;
        $sql = "SELECT id_order_state FROM  " . _DB_PREFIX_ . "order_state_lang
            WHERE  name  =  '" . $status . "' LIMIT 1";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($result)) {
            $idOrderState = $result[0]['id_order_state'];
        }
        return $idOrderState;
    }

    /**
     * POST : Order Status changed
     *
     * @param orderId
     * @param orderData
     *
     * @author radhanatham@riaxe.com
     * @date   25 June 2020
     * @return Array
     */
    public function updateStoreOrderStatus($orderId, $statusKey)
    {
        $idOrderState = $this->getOrderStatusIdByStatus($statusKey);
        $order = new Order($orderId);
        $order_state = new OrderState($idOrderState);
        $current_order_state = $order->getCurrentOrderState();
        if ($current_order_state->id != $order_state->id) {
            // Create new OrderHistory
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $use_existings_payment = false;
            if (!$order->hasInvoice()) {
                $use_existings_payment = true;
            }
            $history->changeIdOrderState((int) $order_state->id, $order, $use_existings_payment);

        }
        return $orderStatus = $orderId ? true : false;
    }

    /**
     * Remove product from Cart
     *
     * @param $cartData Product data array
     *
     * @author radhanatham@riaxe.com
     * @date   30th June 2020
     * @return cart url
     */
    public function removeCartItem($cartData = [])
    {
        if ($cartData['id_product_attribute'] == $cartData['id']) {
            $cartData['id_product_attribute'] = 0;
        }
        $refId = $cartData['cart_item_id'];
        $context = \Context::getContext();
        $cart = new \Cart((int) $context->cookie->id_cart);
        $customizationId = 0;
        $idAddressDelivery = (int) (\Address::getFirstCustomerAddressId($cart->id_customer));
        $data = array(
            'id_cart' => (int) $context->cart->id,
            'id_product' => (int) $cartData['id'],
            'id_product_attribute' => (int) $cartData['id_product_attribute'],
            'customization_id' => (int) $customizationId,
            'id_address_delivery' => (int) $idAddressDelivery,
            'ref_id' => (int) $refId,
        );

        Hook::exec('actionObjectProductInCartDeleteBefore', $data, null, true);

        if ($context->cart->deleteProduct(
            $cartData['id'],
            $cartData['id_product_attribute'],
            $customizationId,
            $idAddressDelivery,
            $refId
        )) {
            Hook::exec('actionObjectProductInCartDeleteAfter', $data);

            if (!Cart::getNbProducts((int) $context->cart->id)) {
                $context->cart->setDeliveryOption(null);
                $context->cart->gift = 0;
                $context->cart->gift_message = '';
                $context->cart->update();
            }

        }

        CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
        $msg['status'] = 1;
        $context = \Context::getContext();
        $rest = substr(_PS_VERSION_, 0, 3);
        if ($rest > 1.6) {
            $cartUrl = $this->getCartSummaryURLS();
        } else {
            $cartUrl = $context->link->getPageLink($order_process, true);
        }
        $msg['url'] = $cartUrl;
        return $msg;
    }

    /**
     * Get product combination price
     *
     * @param $combinationId Product combination id
     * @author radhanatham@riaxe.com
     * @date   30th June 2020
     * @return float price
     */
    public function getCombinationPrice($combinationId)
    {
        $price = '0.00';
        $sql = "SELECT price from " . _DB_PREFIX_ . "product_attribute where id_product_attribute=" . $combinationId . "";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($row)) {
            $price = $row[0]['price'];
        }
        return $this->convertToDecimal($price, 2);
    }

    /**
     * Convert to decimal
     *
     * @param $decimal Price
     * @author radhanatham@riaxe.com
     * @date   30th June 2020
     * @return float price
     */
    private function to_decimal($decimal = 0, $decimalpoint = 2)
    {
        if (!empty($decimal) && $decimal > 0) {
            return number_format($decimal, $decimalpoint);
        }
        return 0;
    }

    /**
     * GET: Get order status key by order id
     *
     * @param $orderId Order id
     *
     * @author radhanatham@riaxe.com
     * @date   03 July 2020
     * @return Array
     */
    public function getOrderStatusKey($orderId)
    {
        $orders = new \Order($orderId);
        $orderStates = $orders->getCurrentStateFull($this->getLaguageId());
        return $orderStates['id_order_state'] ? $orderStates['id_order_state'] : 0;
    }

    /**
     * GET: Get product discount/tier price by product id
     *
     * @param $productId product id
     * @param $productPrice product price
     *
     * @author radhanatham@riaxe.com
     * @date   09 July 2020
     * @return Array
     */
    public function getDiscountPrice($productId, $productPrice)
    {
        $tierPrice = array();
        $tirePriceSql = "SELECT reduction,from_quantity,reduction_type FROM " . _DB_PREFIX_ . "specific_price WHERE id_product=" . $productId . ""; //Tier price for all country
        $resultTirePrice = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($tirePriceSql);
        foreach ($resultTirePrice as $k => $v) {
            if ($v['reduction_type'] == 'percentage') {
                $reduct = number_format($v['reduction'], 2);
                $number = $percentage = substr($reduct, strpos($reduct, ".") + 1);
            } else {
                $number = $v['reduction'];
                $percentage = 100 / ($productPrice / $v['reduction']);
                $percentage = number_format($percentage, 2);
            }
            $tierPrice[$k]['quantity'] = intval($v['from_quantity']);
            $tierPrice[$k]['percentage'] = floatval($percentage);
            $reducePrice = $v['reduction_type'] == 'percentage' ? $productPrice * ($number / 100) : $number;
            $tierPrice[$k]['price'] = number_format($productPrice - $reducePrice, 5);
        }
        return $tierPrice;
    }

    /**
     * GET: Get product tax by product id
     *
     * @param $productId product id
     *
     * @author radhanatham@riaxe.com
     * @date   09 July 2020
     * @return int
     */
    public function getTaxRate($productId)
    {
        $context = \Context::getContext();
        $langId = $this->getLaguageId();
        $tax = 0;
        $idShop = (int) Context::getContext()->shop->id;
        $productSql = "SELECT p.id_product,p.id_tax_rules_group,p.price,pl.name,pl.description_short,pa.minimal_quantity FROM " . _DB_PREFIX_ . "product as p," . _DB_PREFIX_ . "product_lang as pl," . _DB_PREFIX_ . "product_attribute as pa
        WHERE p.id_product =" . $productId . " AND
        p.id_product = pl.id_product AND pl.id_lang =" . $langId . " AND pl.id_shop =" . $idShop . "";
        $rowsData = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($productSql);
        if (!empty($rowsData)) {
            $taxId = $rowsData[0]['id_tax_rules_group'];
        } else {
            $taxId = 0;
        }

        /*fetch extra tax */
        if ($taxId) {
            $sql = "SELECT price_display_method from " . _DB_PREFIX_ . "group WHERE id_group='" . $context->customer->id_default_group . "'";
            $resultPrice = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            if ($resultPrice['0']['price_display_method'] == 0) {
                $taxSql = "SELECT t.rate FROM " . _DB_PREFIX_ . "tax AS t," . _DB_PREFIX_ . "tax_rule AS tr WHERE tr.id_tax_rules_group=" . $taxId . "
                AND tr.id_country = " . $context->country->id . " AND tr.id_tax = t.id_tax";
                $resultTax = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($taxSql);
                $tax = $resultTax ? $resultTax[0]['rate'] : 0;
            } else {
                $tax = 0;
            }
        } else {
            $tax = 0;
        }
        return $tax;
    }

    /**
     * GET:  Get a state id with its iso code.
     *
     * @param $idState State Id
     * @param $idCountry Iso code
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return int state iso code
     */
    private function getSateIsoById($idState, $idCountry = null)
    {
        return Db::getInstance()->getValue('
        SELECT `iso_code`
        FROM `' . _DB_PREFIX_ . 'state`
        WHERE `id_state` = \'' . pSQL($idState) . '\'
        ' . ($idCountry ? 'AND `id_country` = ' . (int) $idCountry : ''));
    }

    /**
     * GET:  Get a country with its iso code.
     *
     * @param $idCountry Iso code
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return int country iso code
     */
    private function getCountryIsoById($idCountry = null)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
        SELECT `iso_code`
        FROM `' . _DB_PREFIX_ . 'country`
        WHERE `id_country` = ' . (int) $idCountry);
    }

    /**
     * GET:  Get all country details from store
     *
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return json array
     */
    public function getStoreCountries()
    {
        try {
            $context = \Context::getContext();
            $id_lang = $context->cookie->id_lang;
            $countryList = Country::getCountries($id_lang, $active = active, $containStates = false, $listStates = false);
            $result = array();
            if (!empty($countryList)) {
                $k = 0;
                foreach ($countryList as $key => $country) {
                    $result[$k]['countries_code'] = $country['iso_code'];
                    $result[$k]['countries_name'] = $country['name'];
                    $k++;
                }
            }
        } catch (PrestaShopDatabaseException $e) {
            echo 'Database error: <br />' . $e->displayMessage();exit();
        }
        return $result;
    }

    /**
     * GET:  Get all country details from store
     *
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return son array
     */
    public function getStoreStates($countryCode)
    {
        try {
            $countryId = Country::getByIso($countryCode, $active = true);
            $stateList = State::getStatesByIdCountry($countryId, $active = true);
            $result = array();
            if (!empty($stateList)) {
                $k = 0;
                foreach ($stateList as $key => $state) {
                    $result[$k]['state_code'] = $state['iso_code'];
                    $result[$k]['state_name'] = $state['name'];
                    $k++;
                }
            }
        } catch (PrestaShopDatabaseException $e) {
            echo 'Database error: <br />' . $e->displayMessage();exit();
        }
        return $result;
    }

    /**
     * GET:  Create new addares for ustomer.
     *
     * @param $argArr Customer details
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return  Array result
     */
    public function addCustomerNewShippingaddress($argArr)
    {
        $customer_id = $argArr['user_id'];
        $firstname = $argArr['first_name'] ? $argArr['first_name'] : '';
        $lastname = $argArr['last_name'] ? $argArr['last_name'] : '';
        $company = $argArr['company'] ? $argArr['company'] : '';
        $phone = $argArr['mobile_no'] ? $argArr['mobile_no'] : '';
        $country_code = $argArr['country'];
        $billing_state = $argArr['state'];
        $city = $argArr['city'] ? $argArr['city'] : '';
        $billing_zip = $argArr['post_code'] ? $argArr['post_code'] : '';
        $address1 = $argArr['address_1'] ? $argArr['address_1'] : '';
        $address2 = $argArr['address_2'] ? $argArr['address_2'] : '';
        $country_id = Country::getByIso($country_code, $active = true);
        $sate_id = State::getIdByIso($billing_state, $country_id);
        $date_add = date('Y-m-d H:i:s', time());
        $address_id = 0;
        $insert_address_sql = "INSERT INTO " . _DB_PREFIX_ . "address (id_country,id_state,id_customer,company,lastname,firstname,address1,address2,postcode,city,phone,date_add) VALUES(" . $country_id . "," . $sate_id . "," . $customer_id . ",'" . $company . "','" . $lastname . "','" . $firstname . "','" . $address1 . "','" . $address2 . "'," . $billing_zip . ",'" . $city . "','" . $phone . "','" . $date_add . "')";
        $address_id = Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_address_sql);
        if ($address_id) {
            $status = 1;
            $message = 'Created Successfully';
        } else {
            $status = 0;
            $message = 'Created Failed';
        }

        return $jsonResponse = [
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * GET:  Create new addares for ustomer.
     *
     * @param $argArr Customer address details
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return  Array result
     */
    public function updateShippingAddress($argArr, $id)
    {
        $result = 0;
        $customer_id = $argArr['user_id'];
        $firstname = $argArr['first_name'] ? $argArr['first_name'] : '';
        $lastname = $argArr['last_name'] ? $argArr['last_name'] : '';
        $company = $argArr['company'] ? $argArr['company'] : '';
        $phone = $argArr['mobile_no'] ? $argArr['mobile_no'] : '';
        $country_code = $argArr['country'];
        $billing_state = $argArr['state'];
        $city = $argArr['city'] ? $argArr['city'] : '';
        $billing_zip = $argArr['post_code'] ? $argArr['post_code'] : '';
        $address1 = $argArr['address_1'] ? $argArr['address_1'] : '';
        $address2 = $argArr['address_2'] ? $argArr['address_2'] : '';
        $country_id = Country::getByIso($country_code, $active = true);
        $sate_id = State::getIdByIso($billing_state, $country_id);
        $customer_add_update_sql = "UPDATE " . _DB_PREFIX_ . "address SET phone='" . $phone . "',company='" . $company . "',city='" . $city . "',postcode=" . $billing_zip . ",id_country=" . $country_id . ",id_state=" . $sate_id . ",address1 ='" . $address1 . "',address2='" . $address2 . "',firstname='" . $firstname . "',lastname='" . $lastname . "'  WHERE id_address = " . $id;
        $result = Db::getInstance()->Execute($customer_add_update_sql);
        if ($result) {
            $status = 1;
            $message = 'Updated Successfully';
        } else {
            $status = 0;
            $message = 'Updated Failed';
        }

        return $jsonResponse = [
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Check if an address is owned by a customer.
     *
     * @param int $idCustomer Customer ID
     *
     * @return bool result
     */
    public function customerHasAddres($idCustomer)
    {
        $customerHasAddress =
        Db::getInstance()->ExecuteS('
            SELECT `id_address`
            FROM `' . _DB_PREFIX_ . 'address`
            WHERE `id_customer` = ' . (int) $idCustomer . '
            AND `deleted` = 0');
        if (!empty($customerHasAddress)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * GET:  Create new ustomer.
     *
     * @param $argArr Customer details
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return  Array result
     */
    public function createCustomer($argArr)
    {
        $email = $argArr['user_email'];
        $password = $argArr['user_password'];
        $firstname = $argArr['first_name'] ? $argArr['first_name'] : '';
        $lastname = $argArr['last_name'] ? $argArr['last_name'] : '';
        $company = $argArr['company_name'] ? $argArr['company_name'] : '';
        $shipping_phone = $argArr['billing_phone'] ? $argArr['billing_phone'] : '';
        $shipping_country_code = $argArr['shipping_country_code'];
        $shipping_state = $argArr['shipping_state_code'];
        $shipping_city = $argArr['shipping_city'] ? $argArr['billing_city'] : '';
        $shipping_zip = $argArr['shipping_postcode'] ? $argArr['shipping_postcode'] : '';
        $shipping_address1 = $argArr['shipping_address_1'] ? $argArr['shipping_address_1'] : '';
        $shipping_address2 = $argArr['shipping_address_2'] ? $argArr['shipping_address_2'] : '';
        $shipping_country_id = Country::getByIso($shipping_country_code, $active = true);
        $shipping_sate_id = State::getIdByIso($shipping_state, $shipping_country_id);
        if (!$shipping_sate_id) {
            $shipping_sate_id = 0;
        }
        $billing_phone = $argArr['billing_phone'] ? $argArr['billing_phone'] : '';
        $billing_country_code = $argArr['billing_country_code'];
        $billing_state = $argArr['billing_state_code'];
        $billing_city = $argArr['billing_city'] ? $argArr['billing_city'] : '';
        $billing_zip = $argArr['billing_postcode'] ? $argArr['billing_postcode'] : '';
        $billing_address1 = $argArr['billing_address_1'] ? $argArr['billing_address_1'] : '';
        $billing_address2 = $argArr['billing_address_2'] ? $argArr['billing_address_2'] : '';
        $billing_country_id = Country::getByIso($billing_country_code, $active = true);
        $billing_sate_id = State::getIdByIso($billing_state, $billing_country_id);
        if (!$billing_sate_id) {
            $billing_sate_id = 0;
        }

        $date_add = date('Y-m-d H:i:s', time());
        $customer_id = 0;
        $id_shop_group = Context::getContext()->shop->id_shop_group;
        $id_lang = Context::getContext()->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        if(isset($argArr['store_id'])){
            $id_shop = $argArr['store_id'];
        }
        $secure_key = md5(uniqid(rand(), true));
        $password = Tools::encrypt($password);
        $last_passwd_gen = date('Y-m-d H:i:s', strtotime('-' . Configuration::get('PS_PASSWD_TIME_FRONT') . 'minutes'));
        $birthday = '0000-00-00';
        $newsletter_date_add = '0000-00-00 00:00:00';
        $status = 0;
        if ($email != '' && $password != '') {
            if (Validate::isEmail($email) && !empty($email)) {
                if (Customer::customerExists($email, false, true)) {
                    $status = 0; //if email is already exit error msg
                    $message = "Email is already exit";
                } else {
                    $sql = "INSERT INTO " . _DB_PREFIX_ . "customer(id_shop_group,id_shop,id_gender,id_default_group,id_lang,id_risk,company,siret,ape,firstname,lastname,
                    email,passwd,last_passwd_gen,birthday,ip_registration_newsletter,newsletter_date_add,max_payment_days,secure_key,active,date_add,date_upd,reset_password_token,reset_password_validity)
                    VALUES(" . $id_shop_group . "," . $id_shop . ",0,3," . $id_lang . ",0,'','','','" . pSQL($firstname) . "','" . pSQL($lastname) . "','" . $email . "',
                    '" . $password . "','" . $last_passwd_gen . "','" . $birthday . "','','" . $newsletter_date_add . "',0,'" . $secure_key . "',1,'" . $date_add . "','" . $date_add . "','','" . $newsletter_date_add . "')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
                    $customer_id = Db::getInstance()->Insert_ID();
                    if ($customer_id) {
                        $insert_sql2 = "INSERT INTO " . _DB_PREFIX_ . "customer_group (id_customer,id_group) VALUES(" . $customer_id . ",3)";
                        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql2);
                        $shipping_address_sql = "INSERT INTO " . _DB_PREFIX_ . "address (id_country,id_state,id_customer,company,lastname,firstname,address1,address2,postcode,city,phone,date_add) VALUES(" . $shipping_country_id . "," . $shipping_sate_id . "," . $customer_id . ",'" . $company . "','" . $lastname . "','" . $firstname . "','" . $shipping_address1 . "','" . $shipping_address2 . "'," . $shipping_zip . ",'" . $shipping_city . "','" . $shipping_phone . "','" . $date_add . "')";
                        $shipping_address_id = Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($shipping_address_sql);

                        $billing_address_sql = "INSERT INTO " . _DB_PREFIX_ . "address (id_country,id_state,id_customer,company,lastname,firstname,address1,address2,postcode,city,phone,date_add) VALUES(" . $billing_country_id . "," . $billing_sate_id . "," . $customer_id . ",'" . $company . "','" . $lastname . "','" . $firstname . "','" . $billing_address1 . "','" . $billing_address2 . "'," . $billing_zip . ",'" . $billing_city . "','" . $billing_phone . "','" . $date_add . "')";
                        $billing_address_id = Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($billing_address_sql);
                        $status = 1;
                        $message = "Customer created successfully";
                    } else {
                        $status = 0;
                        $message = "Customer created failed";
                    }
                }
            } else {
                $status = 0;
                $message = "Invalid customer email";
            }
        } else {
            $status = 0;
            $message = "Invalid customer details";
        }
        return $jsonResponse = [
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * POST:  Create new order.
     *
     * @param $productData product details
     * @param $cartId cart id
     * @param $customerId Customer id
     * @param $productTotalPrice product total price
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return  Int order id
     */
    public function createOrderByCustomerId($productData, $cartId, $customerId, $productTotalPrice)
    {
        try {
            $id_order = 0;
            $ref_id = $this->getRefId($productData, 'item');
            $refId = $this->getRefId($productData, 'order');
            $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
            $randStr = substr(str_shuffle($permitted_chars), 0, 5);
            $product_name = $productData[0]['product_name'];
            $variant_id = $productData[0]['variant_id'];
            $product_id = $productData[0]['product_id'];
            $unit_price = $productData[0]['unit_price'];
            $context = \Context::getContext();
            $id_address_delivery = (int) (\Address::getFirstCustomerAddressId($customerId));
            $id_address_invoice = $id_address_delivery;
            $id_lang = (int) ($context->cookie->id_lang);
            $id_currency = (int) ($context->cookie->id_currency);
            $order_module = 'ps_checkpayment';
            $order_payment = 'Payment by check';
            $id_carrier = 1;
            $total_paid = $productTotalPrice;
            $total_paid_real = 0;
            $total_products_wt = $productTotalPrice;
            $total_products = $productTotalPrice;
            $id_status = 1;
            $total_discounts = 0;
            $total_discounts_tax_incl = 0;
            $total_discounts_tax_excl = 0;
            $total_paid_tax_incl = 0;
            $total_paid_tax_excl = 0;
            $total_shipping = 2;
            $total_shipping_tax_incl = 2;
            $total_shipping_tax_excl = 2;

            $opt = array('resource' => 'orders');
            $xml = $this->getXml(array('url' => PS_SHOP_PATH . '/api/orders?schema=blank'));
            $xml->order->id_address_delivery = $id_address_delivery; // Customer address
            $xml->order->id_address_invoice = $id_address_invoice;
            $xml->order->id_cart = $cartId;
            $xml->order->id_currency = $id_currency;
            $xml->order->id_lang = $id_lang;
            $xml->order->id_customer = $customerId;
            $xml->order->id_carrier = $id_carrier;
            $xml->order->module = $order_module;
            $xml->order->payment = $order_payment;
            $xml->order->total_paid = $total_paid;
            $xml->order->total_paid_real = $total_paid_real;
            $xml->order->total_products = $total_products;
            $xml->order->total_products_wt = $total_products_wt;
            $xml->order->conversion_rate = 1;
            // Others
            $xml->order->valid = 1;
            $xml->order->current_state = $id_status;
            $xml->order->total_discounts = $total_discounts;
            $xml->order->total_discounts_tax_incl = $total_discounts_tax_incl;
            $xml->order->total_discounts_tax_excl = $total_discounts_tax_excl;
            $xml->order->total_paid_tax_incl = $total_paid_tax_incl;
            $xml->order->total_paid_tax_excl = $total_paid_tax_excl;
            $xml->order->total_shipping = $total_shipping;
            $xml->order->total_shipping_tax_incl = $total_shipping_tax_incl;
            $xml->order->total_shipping_tax_excl = $total_shipping_tax_excl;
            $xml->order->ref_id = $refId;
            // Order Row. Required
            $xml->order->associations->order_rows->order_row[0]->product_id = $product_id;
            $xml->order->associations->order_rows->order_row[0]->product_attribute_id = $variant_id;
            $xml->order->associations->order_rows->order_row[0]->product_quantity = 1;
            // Order Row. Others
            $xml->order->associations->order_rows->order_row[0]->product_name = $product_name;
            $xml->order->associations->order_rows->order_row[0]->product_reference = $randStr;
            $xml->order->associations->order_rows->order_row[0]->product_price = $unit_price;
            $xml->order->associations->order_rows->order_row[0]->unit_price_tax_incl = $unit_price;
            $xml->order->associations->order_rows->order_row[0]->unit_price_tax_excl = $unit_price;
            $xml->order->associations->order_rows->order_row[0]->ref_id = $ref_id;
            // Creating the order
            $opt = array('resource' => 'orders');
            $opt['postXml'] = $xml->asXML();
            $opt['id_shop'] = (int) Context::getContext()->shop->id;
            $xmls = $this->add($opt);
            $resources = $xmls->order->children();
            $order = json_decode(json_encode((array) $resources), true);
            $id_order = $order['id'];
            if ($id_order) {
                $this->updateCustomOrderByOrderId($id_order, $refId);
                $this->updateCustomOrderPaymentByOrderId($id_order);
            }
            return $id_order;
        } catch (PrestaShopWebserviceException $ex) {
            // Here we are dealing with errors
            $trace = $ex->getTrace();
            if ($trace[0]['args'][0] == 404) {
                echo 'Bad ID';
            } else if ($trace[0]['args'][0] == 401) {
                echo 'Bad auth key';
            } else {
                echo 'Other error<br />' . $ex->getMessage();
            }

        }
    }

    /**
     * PUT: Update order.
     *
     * @param $orderId Order id
     * @param $refId design id
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return  nothing
     */
    private function updateCustomOrderByOrderId($orderId, $refId)
    {
        $query = "UPDATE " . _DB_PREFIX_ . "orders SET ref_id= " . $refId . ", current_state=1 WHERE id_order = '" . $orderId . "'";
        Db::getInstance()->Execute($query);
    }

    /**
     * PUT: Update order payment.
     *
     * @param $orderId Order id
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return  nothing
     */
    private function updateCustomOrderPaymentByOrderId($orderId)
    {
        $sql = "SELECT total_paid,reference FROM " . _DB_PREFIX_ . "orders WHERE id_order = '" . $orderId . "'";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        $totalPrice = $result[0]['total_paid'];
        $reference = $result[0]['reference'];

        $updateQuery = "UPDATE " . _DB_PREFIX_ . "order_payment SET amount= '" . $totalPrice . "' WHERE order_reference = '" . $reference . "'";
        Db::getInstance()->Execute($updateQuery);
    }

    /**
     * Add product to Cart
     *
     * @param $data Product data array
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return cart id
     */
    public function addToCartProduct($data = [])
    {
        $cartId = 0;
        global $cookie;
        $context = \Context::getContext();
        $cart = null;
        $sql = "SELECT id_product_attribute FROM " . _DB_PREFIX_ . "product_attribute WHERE id_product='" . $data['id'] . "' ";
        $exist = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (empty($exist[0]['id_product_attribute'])) {
            $data['id_product_attribute'] = '';
        }

        if ($context->cookie->id_cart) {
            $cart = new \Cart((int) $context->cookie->id_cart);
        }
        // Initialize Cart //
        if (!is_object($cart)) {
            $cart = new \Cart();
            $cart->id_customer = (int) $data['id_customer'];
            $cart->id_guest = (int) $context->cookie->id_guest;
            $cart->id_address_delivery = (int) (\Address::getFirstCustomerAddressId($data['id_customer']));
            $cart->id_address_invoice = $cart->id_address_delivery;
            $cart->id_lang = (int) ($context->cookie->id_lang);
            $cart->id_currency = (int) ($context->cookie->id_currency);
            $cart->id_carrier = 1;
            $cart->recyclable = 0;
            $cart->gift = 0;
            $cart->add();
            $context->cookie->__set('id_cart', (int) $cart->id);
            $cart->update();
        }
        if ($cart->id) {
            $product = new \Product((int) $data['id']);
            $customization_id = false;
            if (!$product->id) {
                return ["status" => "error", "message" => "Cannot find data in database."];
            }

            /*Initialize cart variables */
            $idAddressDelivery = (int) (\Address::getFirstCustomerAddressId($data['id_customer']));
            $idProductAttribute = ($data['id_product_attribute'] != '') ? $data['id_product_attribute'] : null;
            $quantity = ($data['quantity'] != '') ? $data['quantity'] : 1;
            if (_PS_VERSION_ <= '1.7.4.4') {
                $cart->updateQty(
                    $quantity, (int) $product->id, $idProductAttribute,
                    $customization_id, 'up', $idAddressDelivery,
                    null, true, $data['ref_id']
                );
            } else {
                $cart->updateQty(
                    $quantity, (int) $product->id, $idProductAttribute,
                    $customization_id, 'up', $idAddressDelivery,
                    null, true, false, $data['ref_id']
                );
            }
            $cart->update();

            $shopId = 1; // Assuming single Store //
            if (!is_null(\Shop::getContextShopID())) {
                $shopId = \Shop::getContextShopID();
            }
            if ($product->price == 0 && $idProductAttribute) {
                $price = $this->getCombinationPrice($idProductAttribute);
            } else {
                $price = $product->price;
            }
            $productPrice = $price;
            $addedPrice = 0;
            if ($data['added_price'] > 0) {
                $addedPrice = $data['added_price'] / $quantity;
            } else {
                $addedPrice = $data['added_price'];
            }
            $customPrice = $productPrice + $addedPrice;
            $priceSql = "INSERT INTO " . _DB_PREFIX_ . "imprintnext_cart_custom_price SET id_cart = '" . $cart->id . "', id_product = '" . (int) $product->id . "', id_product_attribute = '" . $idProductAttribute . "', id_shop = '" . $shopId . "', custom_price = '" . $customPrice . "', ref_id = '" . $data['ref_id'] . "'";
            $return = \Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($priceSql);
            $cartId = $cart->id;
        }
        return $cartId;
    }

    /**
     * PUT: Remove customer by customer id.
     *
     * @param $customer_id Customer id
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return  true/false
     */
    public function deleteCustomer($customer_id)
    {
        $rsult = 0;
        if ($customer_id) {
            $sql = "UPDATE " . _DB_PREFIX_ . "customer SET deleted= 1 WHERE id_customer = " . $customer_id;
            $rsult = Db::getInstance()->Execute($sql);
        }
        return $rsult;
    }

    /**
     * GET: Get ref id
     *
     * @param $productArr Product list
     *
     * @author radhanatham@riaxe.com
     * @date   17 Aug 2020
     * @return  Int
     */
    private function getRefId($productArr, $action)
    {
        $refId = 0;
        foreach ($productArr as $key => $v) {
            if ($v['custom_design_id'] >= 1 || $v['custom_design_id'] == -1) {
                if ($action == 'order') {
                    $refId = 1;
                } else {
                    $refId = $v['custom_design_id'];
                }
            }
        }
        return $refId;
    }

    /**
     * GET: Alter able.
     *
     * @author radhanatham@riaxe.com
     * @date   17 Aug 2020
     * @return  true/false
     */
    public function alterTableInStore()
    {
        $status = 0;
        $sql = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "cart_product LIKE 'ref_id'";
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($rows)) {
            //Alter column
            $sqlAlter = "ALTER TABLE `" . _DB_PREFIX_ . "cart_product` CHANGE `ref_id` `ref_id` VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlAlter);
        } else {
            $sqlAlter = "ALTER TABLE " . _DB_PREFIX_ . "cart_product ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlAlter);
        }

        $sqlPrice = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "imprintnext_cart_custom_price LIKE 'ref_id'";
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($rows)) {
            $sqlPriceAlter = "ALTER TABLE `" . _DB_PREFIX_ . "imprintnext_cart_custom_price` CHANGE `ref_id` `ref_id` VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlPriceAlter);
        } else {
            $sqlPriceAlter = "ALTER TABLE " . _DB_PREFIX_ . "imprintnext_cart_custom_price ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlPriceAlter);
        }

        $sqlPrice = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "order_detail LIKE 'ref_id'";
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($rows)) {
            //Alter column
            $sqlOdAlter = "ALTER TABLE `" . _DB_PREFIX_ . "order_detail` CHANGE `ref_id` `ref_id` INT(10) NOT NULL";
            $status = Db::getInstance()->Execute($sqlOdAlter);
            $sqOdsAlter = "ALTER TABLE `" . _DB_PREFIX_ . "order_detail` CHANGE `ref_id` `ref_id` VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqOdsAlter);
        } else {
            $sqlOdAlter = "ALTER TABLE " . _DB_PREFIX_ . "order_detail ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlOdAlter);
        }
        return $status;
    }

    /**
     * Convert to decimal
     *
     * @param $decimal Price
     * @author radhanatham@riaxe.com
     * @date   17 Aug 2020
     * @return float price
     */
    public function convertToDecimal($number, $digit)
    {
        if ($number > 0) {
            return number_format(floor($number * 100) / 100, $digit, '.', '');
        } else {
            return $number;
        }
    }

    /**
     * POST: Create size and color attribute in store
     *
     * @param $language language list
     * @author radhanatham@riaxe.com
     * @date   17 Aug 2020
     * @return Array attrinute id
     */
    private function createAttributeGroup($language)
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        //add color attribute
        $colornName = 'Color';
        $sql = "SELECT id_attribute_group from " . _DB_PREFIX_ . "attribute_group_lang where name='" . $colornName . "' and id_lang=" . $id_lang . "";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (empty($result[0]['id_attribute_group'])) {
            $sql = "SELECT position FROM " . _DB_PREFIX_ . "attribute_group order by position desc limit 1";
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            $insert_sql = "INSERT INTO `" . _DB_PREFIX_ . "attribute_group` (`group_type`,`is_color_group`,`position`) VALUES('color','1','" . intval($row['0']['position'] + 1) . "')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql);
            $colorGroupId = $groupId = Db::getInstance()->Insert_ID();
            foreach ($language as $v) {
                $insert_sql1 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_lang (id_attribute_group,id_lang,name,public_name) VALUES(" . $groupId . ",'" . $v['id_lang'] . "','$colornName','color')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql1);
            }
            $insert_sql2 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_shop (id_attribute_group,id_shop) VALUES(" . $groupId . "," . $id_shop . ")";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql2);
        } else {
            $colorGroupId = $result[0]['id_attribute_group'];

        }
        //add size attribue
        $sizeName = 'Size';
        $sqlZize = "SELECT id_attribute_group from " . _DB_PREFIX_ . "attribute_group_lang where name='" . $sizeName . "' and id_lang=" . $id_lang . "";
        $resultSize = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlZize);
        if (empty($resultSize[0]['id_attribute_group'])) {
            $sql = "SELECT position FROM " . _DB_PREFIX_ . "attribute_group order by position desc limit 1";
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            $insert_sql = "INSERT INTO `" . _DB_PREFIX_ . "attribute_group` (`group_type`,`position`) VALUES('select','" . intval($row['0']['position'] + 1) . "')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql);
            $sizeGroupId = $groupId = Db::getInstance()->Insert_ID();
            foreach ($lang_ids as $v1) {
                $insert_sql1 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_lang (id_attribute_group,id_lang,name,public_name) VALUES(" . $groupId . ",'" . $v1['id_lang'] . "','$sizeName','size')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql1);
            }
            $insert_sql2 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_shop (id_attribute_group,id_shop) VALUES(" . $groupId . "," . $id_shop . ")";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql2);
        } else {
            $sizeGroupId = $resultSize[0]['id_attribute_group'];

        }
        $attributeGroup['color_group_id'] = $colorGroupId;
        $attributeGroup['size_group_id'] = $sizeGroupId;
        return $attributeGroup;
    }

    /**
     * POST: Create category in store
     *
     * @param $category language list
     * @param $language language list
     * @author radhanatham@riaxe.com
     * @date   17 Aug 2020
     * @return Array attrinute id
     */
    public function addCategory($filters)
    {
        $id_category = 0;
        $categoryName = $filters['catName'];
        $linkRewrite = Tools::str2url($filters['catName']);
        $description = '';
        $sqlLang = "SELECT id_lang FROM " . _DB_PREFIX_ . "lang";
        $language = $langId = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlLang);
        $data['id_parent'] = Configuration::get('PS_HOME_CATEGORY');
        $meta_title = '';
        $meta_keywords = '';
        $meta_description = '';
        if (!empty($filters['catId'])) {
            $data['id_parent'] = $filters['catId'];
        }
        $data['level_depth'] = $this->calcLevelDepth($data['id_parent']);
        $idShop = (int) Context::getContext()->shop->id;
        $data['id_shop_default'] = $idShop;
        if (!empty($filters['store'])) {
            $data['id_shop_default'] = $filters['store'];
        }
        $position = (int) Category::getLastPosition((int) $data['id_parent'], $idShop);
        $data['active'] = 1;
        $now = date('Y-m-d H:i:s', time());
        $data['date_add'] = $now;
        $data['date_upd'] = $now;
        $data['position'] = $position ? $position : 1;
        $id_lang = Context::getContext()->language->id;
        $sql = "SELECT id_category from " . _DB_PREFIX_ . "category_lang where name='" . $categoryName . "' and id_shop=" . $idShop . " and id_lang =" . intval($id_lang) . "";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if ($result[0]['id_category']) {
            //return $id_category = $result[0]['id_category'];
            return [];
        } else {
            if (DB::getInstance()->insert('category', $data)) {
                $id_category = Db::getInstance()->Insert_ID();
                foreach ($language as $v) {
                    $datal['id_category'] = $id_category;
                    $datal['id_shop'] = (int) $idShop;
                    $datal['id_lang'] = $v['id_lang'];
                    $datal['name'] = $filters['catName'];
                    $datal['description'] = pSQL($description);
                    $datal['link_rewrite'] = pSQL($linkRewrite);
                    $datal['meta_title'] = pSQL($meta_title);
                    $datal['meta_keywords'] = pSQL($meta_keywords);
                    $datal['meta_description'] = pSQL($meta_description);
                    if (!DB::getInstance()->insert('category_lang', $datal)) {
                        die('Error in category lang insert : ' . $id_category);
                    }

                }
                $dataShop['id_category'] = $id_category;
                $dataShop['id_shop'] = (int) $idShop;
                $dataShop['position'] = $position;
                if (!DB::getInstance()->insert('category_shop', $dataShop)) {
                    die('Error in category shop insert : ' . $id_category);
                }
                $this->regenerateEntireNtreeCategory();
                $this->updateGroup($this->groupBox, $id_category);
            } else {
                die('Error in category insert : ' . $data['id_parent']);
            }
            return $id_category;
        }
    }

    /**
     *Generate entity tree category
     *
     * @param nothing
     * @return nothing
     *
     */
    public function regenerateEntireNtreeCategory()
    {
        $id = Context::getContext()->shop->id;
        $id_shop = $id ? $id : Configuration::get('PS_SHOP_DEFAULT');
        $categories = Db::getInstance()->executeS('
        SELECT c.`id_category`, c.`id_parent`
        FROM `' . _DB_PREFIX_ . 'category` c
        LEFT JOIN `' . _DB_PREFIX_ . 'category_shop` cs
        ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . (int) $id_shop . ')
        ORDER BY c.`id_parent`, cs.`position` ASC');
        $categories_array = array();
        foreach ($categories as $category) {
            $categories_array[$category['id_parent']]['subcategories'][] = $category['id_category'];
        }
        $n = 1;
        if (isset($categories_array[0]) && $categories_array[0]['subcategories']) {
            $this->subTree($categories_array, $categories_array[0]['subcategories'][0], $n);
        }
    }

    /**
     *Assaign category under a category
     *
     * @param (Array)categories_array
     * @param (Int)id_category
     * @param (Int)n
     * @return nothing
     *
     */
    public function subTree(&$categories, $id_category, &$n)
    {
        $left = $n++;
        if (isset($categories[(int) $id_category]['subcategories'])) {
            foreach ($categories[(int) $id_category]['subcategories'] as $id_subcategory) {
                $this->subTree($categories, (int) $id_subcategory, $n);
            }
        }
        $right = (int) $n++;
        Db::getInstance()->execute('
        UPDATE ' . _DB_PREFIX_ . 'category
        SET nleft = ' . (int) $left . ', nright = ' . (int) $right . '
        WHERE id_category = ' . (int) $id_category . ' LIMIT 1');
    }

    public function deleteCategory($catId)
    {
        return Db::getInstance()->delete('category', 'id_category = ' . (int) $catId);

    }


    /**
     *Update category id group
     *
     * @param (Array)list
     * @param (Int)id_category
     * @param (Int)n
     * @return nothing
     *
     */
    public function updateGroup($list, $id_category)
    {
        $this->cleanGroups($id_category);
        if (empty($list)) {
            $list = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'), Configuration::get('PS_CUSTOMER_GROUP'));
        }
        $this->addGroups($list, $id_category);
    }

    /**
     *Add category group
     *
     * @param (string)groups
     * @param (int)id_category
     * @return nothing
     *
     */
    public function addGroups($groups, $id_category)
    {
        foreach ($groups as $group) {
            if ($group !== false) {
                Db::getInstance()->insert('category_group', array('id_category' => (int) $id_category, 'id_group' => (int) $group));
            }
        }
    }

    public function cleanGroups($id_category)
    {
        return Db::getInstance()->delete('category_group', 'id_category = ' . (int) $id_category);
    }
    /**
     * Get the depth level for the category
     *
     * @return int Depth level
     */
    public function calcLevelDepth($id_parent)
    {
        /* Root category */
        if (!$id_parent) {
            return 0;
        }
        $parent_category = new Category((int) $id_parent);
        if (!Validate::isLoadedObject($parent_category)) {
            throw new PrestaShopException('Parent category does not exist');
        }
        return (int) $parent_category->level_depth + 1;
    }

    /**
     * POST: Create product attribute
     *
     * @param $attributeGroup Product attributes
     * @param $color size name
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return Int
     */
    public function createSizeAttributeValue($sizeGroupId, $size)
    {
        $sizeId = 0;
        $id_lang = Context::getContext()->language->id;
        $exitResult = $this->isAttributeExit($sizeGroupId, $size, $id_lang);
        if (empty($exitResult) && $size != '') {
            $attributeData['id_attribute_group'] =  $sizeGroupId;
            $attributeData['color'] = '';
            $attributeData['position'] = 0;
            DB::getInstance()->insert('attribute', $attributeData);
            $idAttribute = Db::getInstance()->Insert_ID();
            if($idAttribute){
                $attributeDataShop['id_attribute'] =   $idAttribute;
                $attributeDataShop['id_shop'] = $idShop;
                DB::getInstance()->insert('attribute_shop', $attributeDataShop);
                $sqlLang = "SELECT id_lang FROM " . _DB_PREFIX_ . "lang";
                $language = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlLang);
                foreach ($language as $v) {
                    $attributeLang['id_attribute'] =  $idAttribute;
                    $attributeLang['id_lang'] = $v['id_lang'];
                    $attributeLang['name'] = pSQL($size);
                    if (!DB::getInstance()->insert('attribute_lang', $attributeLang)) {
                        die('Error in attribute lang insert : ' . $size);
                    }
                }
            }
        }
        $size_sql = "SELECT al.id_attribute from " . _DB_PREFIX_ . "attribute_lang as al,
        " . _DB_PREFIX_ . "attribute as atr
        where id_attribute_group =" . $sizeGroupId . " and atr.id_attribute = al.id_attribute and al.name='" . $size . "' and al.id_lang=" . intval($id_lang) . "";
        $row_size = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($size_sql);
        $sizeId = $row_size[0]['id_attribute'];
        return $sizeId;
    }

    /**
     * POST: Create product attribute
     *
     * @param $colorGroupId Product attributes
     * @param $colorName Color name
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return Int
     */
    public function createColorAttributeValue($colorGroupId, $colorName)
    {
        $colorId = 0;
        $id_lang = Context::getContext()->language->id;
        $colorHexaValue = '#ffffff';
        $checkExit = $this->isAttributeExit($colorGroupId, $colorName, $id_lang);
        if (empty($checkExit) && $colorHexaValue != '') {
            $attributeData['id_attribute_group'] =  $colorGroupId;
            $attributeData['color'] = $colorHexaValue;
            $attributeData['position'] = 0;
            DB::getInstance()->insert('attribute', $attributeData);
            $idAttribute = Db::getInstance()->Insert_ID();
            if($idAttribute){
                $attributeDataShop['id_attribute'] =   $idAttribute;
                $attributeDataShop['id_shop'] = $idShop;
                DB::getInstance()->insert('attribute_shop', $attributeDataShop);
                $sqlLang = "SELECT id_lang FROM " . _DB_PREFIX_ . "lang";
                $language = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlLang);
                foreach ($language as $v) {
                    $attributeLang['id_attribute'] =  $idAttribute;
                    $attributeLang['id_lang'] = $v['id_lang'];
                    $attributeLang['name'] = pSQL($colorName);
                    if (!DB::getInstance()->insert('attribute_lang', $attributeLang)) {
                        die('Error in attribute lang insert : ' . $colorName);
                    }
                }
            }
        }
        $colorSql = "SELECT al.id_attribute from " . _DB_PREFIX_ . "attribute_lang as al,
        " . _DB_PREFIX_ . "attribute as atr
        where id_attribute_group =" . $colorGroupId . " and atr.id_attribute = al.id_attribute and al.name='" . $colorName . "' and al.id_lang=" . intval($id_lang) . "";
        $rowColor = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($colorSql);
        if (!empty($rowColor)) {
            $colorId = $rowColor[0]['id_attribute'];
        }
        return $colorId;
    }

    /**
     * POST: Product add to store
     *
     * @param $parameters Product data
     * @param $maxprice Catalog product max price
     * @param $catalog_price Catalog product price
     * @param $old_product_id Store old product id
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return array json
     */
    public function addCatalogProductToStore($parameters, $maxprice, $catalog_price, $categories, $storeId, $old_product_id)
    {
        $variations = $parameters['variations'];
        if (empty($variations)) {
            $preDecoProductPrice = $maxprice;
        } else {
            $preDecoProductPrice = '0.0';
        }
        $sqlLang = "SELECT id_lang FROM " . _DB_PREFIX_ . "lang";
        $langId = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlLang);
        $attributeGroup = $this->createAttributeGroup($langId);
        
        if (!empty($categories)) {
            $categories[] = $categories;
        } else {
            $categories[] = "2";
        }
        $colorId = array();
        $sizeId = array();
        $attributes = array();
        $productNameStr = $parameters['name'];
        $productName = str_replace(array('<', ">", ";", "=", "#", "{", "}"), "", $productNameStr);
        $images = array($parameters['images']['src']);
        $totalQantity = $parameters['total_qty'];
        $sku = $parameters['sku'];
        $description = $parameters['description'];
        $shortDescription = $parameters['description'];
        $isCustomized = '1';
        $designId = '0';
        $variations = $parameters['variations'];
        if (!empty($variations)) {
            $preDecoProductPrice = $maxprice;
        } else {
            $preDecoProductPrice = '0.0';
        }
        $productImageArr = array();
        $idLang = $this->getLaguageId();
        $isAddTocart = 0;
        $result = array();
        $now = date('Y-m-d H:i:s', time());
        $idShop = $storeId;
        $productId = 0;
         //Deleting old product
        if($old_product_id > 0){
            $sql_1 = "DELETE FROM `" . _DB_PREFIX_ . "product` WHERE `id_product`= '$old_product_id' AND `id_shop`='$idShop'";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_1);
            $sql_2 = "DELETE FROM `" . _DB_PREFIX_ . "product_shop` WHERE `id_product`= '$old_product_id' AND `id_shop`='$idShop'";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_2);

            foreach ($langId as $v) {
                $Sql_3 = "DELETE FROM `" . _DB_PREFIX_ . "product_lang` WHERE `id_product`='$old_product_id' AND `id_shop`='$idShop' AND `id_lang`='" . $v['id_lang'] . "'";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($Sql_3);
            }
        }
        if (!empty($images)) {
            $sizeCount = 1;
            $productName = pSQL($productName);
            $sku = pSQL($sku);
            $description = pSQL($description);
            $shortDescription = pSQL($shortDescription);
            //added new predecoproduct
            $productSql = "INSERT INTO " . _DB_PREFIX_ . "product(id_supplier,id_manufacturer,id_category_default,id_tax_rules_group,price,reference,active,redirect_type,indexed,cache_default_attribute,date_add,date_upd,customize,xe_is_temp,is_addtocart,is_catalog)
            VALUES('1','1','$categories[0]','0','$preDecoProductPrice','$sku','1','404','1','','$now','$now','$isCustomized'," . $designId . "," . $isAddTocart . ",1)";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($productSql);
            $productId = Db::getInstance()->Insert_ID();
            //ps_product_shop
            if (_PS_VERSION_ >= '1.7.2.2') {
                $productShopSql = "INSERT INTO `" . _DB_PREFIX_ . "product_shop` (`id_product`, `id_shop`, `id_category_default`, `id_tax_rules_group`,
                 `minimal_quantity`, `price`,`active`,`redirect_type`,`available_for_order`, `condition`, `show_price`, `indexed`, `visibility`,`cache_default_attribute`, `advanced_stock_management`, `date_add`, `date_upd`, `pack_stock_type`)
                 VALUES ('$productId', '$idShop', '$categories[0]', '1', '1', '$preDecoProductPrice', '1', '404', '1', 'new', '1', '1', 'both', '0', '0', '$now', '$now', '3')";
            } else {
                $productShopSql = "INSERT INTO `" . _DB_PREFIX_ . "product_shop` (`id_product`, `id_shop`, `id_category_default`, `id_tax_rules_group`,
                 `minimal_quantity`, `price`, `active`, `redirect_type`, `id_product_redirected`, `available_for_order`, `condition`, `show_price`, `indexed`, `visibility`,
                    `cache_default_attribute`, `advanced_stock_management`, `date_add`, `date_upd`, `pack_stock_type`)
                 VALUES ('$productId', '$idShop', '$categories[0]', '0', '1', '$preDecoProductPrice', '1', '404', '0', '1', 'new', '1', '1', 'both', '0', '0', '$now', '$now', '3')";
            }
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($productShopSql);
            //ps_product_lang
            $linkRewrite = Tools::str2url($productName);
            foreach ($langId as $v) {
                $productLangSql = "INSERT INTO " . _DB_PREFIX_ . "product_lang(id_product,id_shop,id_lang,description,description_short,link_rewrite,
                    meta_description,meta_keywords,meta_title,name,available_now,available_later)
                VALUES('$productId','$idShop','" . $v['id_lang'] . "','$description','$shortDescription','$linkRewrite','','','','$productName','','')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($productLangSql);
            }
            if ($productId) {
                //add category to product //
                if (!empty($categories)) {
                    $this->addToCategoriesToProduct($categories, $productId);
                }

                if (!empty($variations)) {
                    $image = array();
                    foreach ($variations as $key => $comb) {
                        $size = $comb['attributes']['size'] ? $comb['attributes']['size'] : '';
                        $color = $comb['attributes']['color'] ? $comb['attributes']['color'] : '';
                        $sizeAttributeId = $this->createSizeAttributeValue($attributeGroup['size_group_id'], $size);
                        $colorAttributeId = $this->createColorAttributeValue($attributeGroup['color_group_id'], $color);
                        $productImageArr = array_filter($comb['image_path']);
                        if ($key == 0) {
                            $image = array_merge($images, $productImageArr);
                        } else {
                            $image = $productImageArr;
                        }
                        $imageId = $this->addImageByProductId($image, $productId, $productName, $langId, $idShop, $key);
                        $price = 0;
                        if ($comb['piece_price'] > 0) {
                            $diffPrice = $maxprice - $catalog_price;
                            $price = $comb['piece_price'] + $diffPrice;
                        } else {
                            $price = $maxprice;
                        }
                        $price = 0; // edited
                        if ($sizeAttributeId && $colorAttributeId) {
                            $attrId = $this->addCatalogProductAttributesByProductId($sizeAttributeId, $colorAttributeId, $productId, $sku, $idShop, $key, $price);
                            if ($attrId) {
                                $qty = $comb['quantity'];
                                $this->addProductStock($attrId, $productId, $totalQantity, $qty);
                                if ($imageId) {
                                    $this->addImageAttributes($attrId, $imageId);
                                }
                                $this->updateTotalQuantityByPid($productId);
                            }
                        }
                    }
                } else {
                    $imageId = $this->addImageByProductId($images, $productId, $productName, $langId, $idShop, 0);
                    $this->addProductStock($productId, $totalQantity, $totalQantity);
                }
                $result['id'] = $productId;
            }
        }
        return $productId;
    }

    /**
     *add product attributes by product id
     *
     * @param (Int)sizeAttributeId
     * @param (Int)colorAttributeId
     * @param (Int)productId
     * @param (String)sku
     * @param (Int)idShop
     * @return  int
     *
     */
    public function addCatalogProductAttributesByProductId($sizeAttributeId, $colorAttributeId, $productId, $sku, $idShop, $key, $price)
    {
        $attrId = 0;
        if ($sizeAttributeId && $colorAttributeId) {
            if ($key == 0) {
                $attr_sql = "INSERT INTO " . _DB_PREFIX_ . "product_attribute(id_product,reference,price,default_on) VALUES('$productId','$sku','$price','1')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($attr_sql);
                $attrId = Db::getInstance()->Insert_ID();
                //ps_product_atrribute_shop
                $sql_pashop = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_shop(id_product,id_product_attribute,id_shop,price,default_on)
                VALUES('$productId','$attrId','$idShop','$price','1')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_pashop);
                $this->updateDefalutProductCombination($productId, $attrId);
            } else {
                $attr_sql1 = "INSERT INTO " . _DB_PREFIX_ . "product_attribute(id_product,reference,price) VALUES('$productId','$sku','$price')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($attr_sql1);
                $attrId = Db::getInstance()->Insert_ID();
                //ps_product_atrribute_shop
                $sql_pashop = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_shop(id_product,id_product_attribute,id_shop,price)
                VALUES('$productId','$attrId','$idShop','$price')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_pashop);
            }
            $sql_size_insert = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_combination(id_attribute,id_product_attribute) VALUES('$sizeAttributeId','$attrId')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_size_insert);
            $sql_color_insert = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_combination(id_attribute,id_product_attribute) VALUES('$colorAttributeId','$attrId')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_color_insert);
        }
        return $attrId;
    }

    /**
     *Update product default combination
     *
     * @param (Int)productId
     * @param (Int)attrId
     * @return  nothing
     *
     */
    private function updateDefalutProductCombination($productId, $attrId)
    {
        $queryProduct = "UPDATE " . _DB_PREFIX_ . "product SET cache_default_attribute= " . $attrId . " WHERE id_product = " . $productId;
        Db::getInstance()->Execute($queryProduct);
        $queryProductShop = "UPDATE " . _DB_PREFIX_ . "product_shop SET cache_default_attribute= " . $attrId . " WHERE id_product = " . $productId;
        Db::getInstance()->Execute($queryProductShop);
    }

    /**
     * GET: Get Line Item of Order
     *
     * @param $orderId Order id
     *
     * @author radhanatham@riaxe.com
     * @date   25 Aug 2020
     * @return Array
     */
    public function getOrderByOrderItemDetails($orderId)
    {
        $jsonResponse = [];
        $parameterl = array(
            'resource' => 'orders',
            'display' => 'full',
            'filter[id]' => '[' . $orderId . ']', 'output_format' => 'JSON',
        );
        $jsonData = $this->get($parameterl);
        //return json format
        $ordersArr = json_decode($jsonData, true);
        $singleOrderDetails = $ordersArr['orders'][0];
        $lineOrders = array();
        $i = 0;
        foreach ($singleOrderDetails['associations']['order_rows'] as $v) {
            $lineOrders[$i]['id'] = $v['id'];
            $lineOrders[$i]['product_id'] = $v['product_id'];
            $lineOrders[$i]['variant_id'] = $v['product_attribute_id']
            ? $v['product_attribute_id'] : $v['product_id'];
            $lineOrders[$i]['name'] = $v['product_name'];
            $lineOrders[$i]['price'] = $v['unit_price_tax_excl'];
            $lineOrders[$i]['quantity'] = $v['product_quantity'];
            $lineOrders[$i]['total'] = $v['unit_price_tax_excl'] * $v['product_quantity'];
            $lineOrders[$i]['sku'] = $v['product_reference'];
            $lineOrders[$i]['custom_design_id'] = $v['ref_id'];
            $lineOrders[$i]['images'] = $this->getProducImage(
                $v['product_attribute_id'], $v['product_id']
            );
            $i++;
        }
        return $lineOrders;
    }

    /**
     * GET: Get color hexa code only
     *
     * @param $idAttribute Attribute id
     *
     * @author radhanatham@riaxe.com
     * @date   25 Aug 2020
     * @return String
     */
    public function getColorHex($idAttribute)
    {
        $sql_fetch = "SELECT color FROM " . _DB_PREFIX_ . "attribute WHERE id_attribute = " . $idAttribute . "";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_fetch);
        return $result[0]['color'];
    }

    /**
     * Get product categories
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   07 Sept 2020
     * @return array
     */
    public function productCategories($productId)
    {
        $categoryArr = [];
        try {
            $idLang = Context::getContext()->language->id;
            $shopId = Context::getContext()->shop->id;
            $sql = "SELECT DISTINCT c.id_category as id,c.id_parent as parent_id,cl.name FROM " . _DB_PREFIX_ . "category AS c JOIN " . _DB_PREFIX_ . "category_lang AS cl ON c.id_category = cl.id_category LEFT JOIN " . _DB_PREFIX_ . "category_product as pc on cl.id_category = pc.id_category WHERE pc.id_product=" . $productId . " AND cl.id_lang=" . $idLang . " AND cl.id_shop=" . $shopId . " ORDER BY c.id_category asc";
            $categoryArr = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            return $categoryArr;
        } catch (PrestaShopDatabaseException $ex) {
            echo 'Other error: <br />' . $ex->getMessage();
        }
    }

    /**
     * Get total order product quantity
     *
     * @param $orderId  Order Id
     *
     * @author radhanatham@riaxe.com
     * @date   23 Oct 2020
     * @return Int
     */
    public function getOrderTotalQuantity($orderId)
    {
        $totalQty = 0;
        try {
            $sql = "SELECT DISTINCT sum(product_quantity) as total_qty FROM " . _DB_PREFIX_ . "order_detail WHERE id_order =" . $orderId;
            $order = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            if (!empty($order)) {
                $totalQty = $order[0]['total_qty'];
            }
        } catch (PrestaShopDatabaseException $ex) {
            echo 'Other error: <br />' . $ex->getMessage();
        }
        return $totalQty;
    }

    /**
     * Get list of customer from the PrestaShop store
     *
     * @param $filters Customer filter
     *
     * @author radhanatham@riaxe.com
     * @date   27 Oct 2020
     * @return Array of list/one customer(s)
     **/
    public function getCutomers($filters)
    {
        if ($filters['store']) {
            $shopId = $filters['store'];
        } else {
            $shopId = Context::getContext()->shop->id;
        }

        $customerNoOrder = $filters['customer_no_order'];
        $fetch = $filters['fetch'];
        $type = $filters['type'];
        $sortBy = $filters['orderby'];
        if ($sortBy == 'name') {
            $sortBy = 'firstname';
        } else {
            $sortBy = 'id_customer';
        }
        $order = strtolower($filters['order']) == 'asc' ? 'ASC' : 'DESC';
        $name = $filters['name'];

        if ($fetch == 'all') {
            $sql = "SELECT DISTINCT c.id_customer as id,c.firstname,c.email,c.lastname,c.date_add FROM `" . _DB_PREFIX_ . "customer` c LEFT JOIN `" . _DB_PREFIX_ . "orders` o ON c.`id_customer` = o.`id_customer` WHERE c.id_shop = " . (int) $shopId . " AND deleted=0 ";
        } elseif ($customerNoOrder == 'true') {
            $sql = "SELECT DISTINCT c.id_customer as id,c.firstname,c.email,c.lastname,c.date_add FROM `" . _DB_PREFIX_ . "customer` c LEFT JOIN `" . _DB_PREFIX_ . "orders` o ON c.`id_customer` = o.`id_customer` WHERE c.id_shop = " . (int) $shopId . " AND deleted=0 AND o.`id_customer`IS NULL ";
        } elseif ($type == 'quote') {
            $sql = "SELECT DISTINCT c.id_customer as id,c.firstname,c.email,c.lastname,c.date_add FROM `" . _DB_PREFIX_ . "customer` c LEFT JOIN `" . _DB_PREFIX_ . "orders` o ON c.`id_customer` = o.`id_customer` WHERE c.id_shop = " . (int) $shopId . " AND deleted=0 ";
        } else {
            $sql = "SELECT DISTINCT c.id_customer as id,c.firstname,c.email,c.lastname,c.date_add FROM `" . _DB_PREFIX_ . "customer` c JOIN `" . _DB_PREFIX_ . "orders` o ON c.`id_customer` = o.`id_customer` WHERE c.id_shop = " . (int) $shopId . " AND deleted=0 ";
        }
        if ($name != '') {
            $sql .= "AND (c.firstname like '%" . ($name) . "%' or c.lastname like '%" . ($name) . "%') ";
        }
        $sql .= "ORDER BY c." . $sortBy . " " . $order . " ";
        return Db::getInstance()->executeS($sql);
    }


    /**
     * GET: Get customer address details
     *
     * @param $customerId Customer id
     * @param $storeId      Stor Id
     * @param $isAddress    Is Address
     *
     * @author radhanatham@riaxe.com
     * @date   04 Jan 2021
     * @return Array
     */
    public function getCutsomerAddress($customerId, $storeId, $isAddress)
    {
        $customerDetails = [];
        $parameter = array(
            'resource' => 'customers',
            'display' => 'full',
            'filter[id]' => '%[' . $customerId . ']%', 
            'filter[deleted]' => '[0]',
            'limit' => '1', 'output_format' => 'JSON',
            'id_shop' => $storeId,
        );
        $jsonData = $this->get($parameter);
        $customerArr = json_decode($jsonData, true);

        if (!empty($customerArr)) {
            $getCustomers = $customerArr['customers'][0];
            $customerDetails['customer']['id'] = $getCustomers['id'];
            $email = $customerDetails['customer']['email'] = $getCustomers['email'];
            $first_name = $getCustomers['firstname'] ? $getCustomers['firstname'] : '';
            $last_name = $getCustomers['lastname'] ? $getCustomers['lastname'] : '';
            $customerDetails['customer']['name'] = $first_name . ' ' . $last_name;
            $customerDetails['customer']['phone'] =$getCustomers['company'] ? $getCustomers['company'] : '';
            if ($isAddress == true) {
                $idLang = $this->getLaguageId();
                $parameterAddress = array(
                    'resource' => 'addresses',
                    'display' => 'full',
                    'filter[id_customer]' => '[' . $customerId . ']', 'filter[deleted]' => '[0]',
                    'output_format' => 'JSON',
                );
                $jsonData = $this->get($parameterAddress);
                //return json format
                $addressJson = json_decode($jsonData, true);
                $addressArr = $addressJson['addresses'];
                $resultArr = $billingArr = $shippingArr = array();
                if (!empty($addressArr)) {
                    $addressId = $addressArr[0]['id'];
                    $countryName = \Country::getNameById(
                        $idLang, $addressArr[0]['id_country']
                    );
                    $state = \State::getNameById($addressArr[0]['id_state']);
                    $customerDetails['customer']['billing_address']['first_name'] = !empty($addressArr[0]['firstname']) ? $addressArr[0]['firstname'] : $first_name;
                    $customerDetails['customer']['billing_address']['last_name'] = !empty($addressArr[0]['lastname']) ? $addressArr[0]['lastname'] : $last_name;
                    $customerDetails['customer']['billing_address']['address_1'] = $addressArr[0]['address1'];
                    $customerDetails['customer']['billing_address']['address_2'] = $addressArr[0]['address2'];
                    $customerDetails['customer']['billing_address']['city'] = $addressArr[0]['city'];
                    $customerDetails['customer']['billing_address']['state'] = $state ? $state : '';
                    $customerDetails['customer']['billing_address']['postcode'] = $addressArr[0]['postcode'];
                    $customerDetails['customer']['billing_address']['country'] = $countryName ? $countryName : '';
                    $customerDetails['customer']['billing_address']['email'] = $email;
                    $customerDetails['customer']['billing_address']['phone'] = (string) $addressArr[0]['phone'];
                    $customerDetails['customer']['billing_address']['company'] = $addressArr[0]['company'];


                    $customerDetails['customer']['shipping_address'][0]['id'] = $addressArr[1]['id'];
                    $customerDetails['customer']['shipping_address'][0]['first_name'] = !empty($addressArr[1]['firstname']) ? $addressArr[1]['firstname'] : $first_name;
                    $customerDetails['customer']['shipping_address'][0]['last_name'] = !empty($addressArr[1]['lastname']) ? $addressArr[1]['lastname'] :  $last_name;
                    $customerDetails['customer']['shipping_address'][0]['company'] = $addressArr[1]['company'];
                    $customerDetails['customer']['shipping_address'][0]['address_1'] = $addressArr[1]['address1'];
                    $customerDetails['customer']['shipping_address'][0]['address_2'] = $addressArr[1]['address2'];
                    $customerDetails['customer']['shipping_address'][0]['city'] = $addressArr[1]['city'];
                    $stateName = \State::getNameById($addressArr[1]['id_state']);
                    $customerDetails['customer']['shipping_address'][0]['postcode'] = $addressArr[1]['postcode'];
                    $isoStateCode = '';
                    $isoStateCode = $this->getSateIsoById($addressArr[1]['id_state'], $addressArr[1]['id_country']);
                    $isoCountryCode = '';
                    $isoCountryCode = $this->getCountryIsoById($addressArr[1]['id_country']);
                    $customerDetails['customer']['shipping_address'][0]['state'] = $isoStateCode;
                    $countryName = \Country::getNameById(
                        $idLang, $addressArr[1]['id_country']
                    );
                    $customerDetails['customer']['shipping_address'][0]['country'] = $isoCountryCode ? $isoCountryCode : '';
                    $customerDetails['customer']['shipping_address'][0]['is_default'] = 1;
                    $customerDetails['customer']['shipping_address'][0]['country_name'] = $countryName;
                    $customerDetails['customer']['shipping_address'][0]['state_name'] = $stateName;
                }
            }
        }
        return $customerDetails;
    }

    /**
     * GET: Order details
     *
     * @param $order_id
     * @param $orderItemId
     * @param $is_customer
     * @param $store_id
     *
     * @author radhanatham@riaxe.com
     * @date   04 Jan 2021
     * @return Array
     */
    public function getStoreOrderLineItemDetails($order_id, $orderItemId, $is_customer, $store_id)
    {
        $parameterl = array(
            'resource' => 'orders',
            'display' => 'full',
            'filter[id]' => '[' . $order_id . ']', 'output_format' => 'JSON',
        );
        $jsonData = $this->get($parameterl);
        $order = json_decode($jsonData, true);
        $singleOrderDetails = $order['orders'][0];
        $attribute = $jsonResponse = [];
        foreach ($singleOrderDetails['associations']['order_rows'] as $v) {
            if ($orderItemId == $v['id']) {
                $jsonResponse['item_id'] = $v['id'];
                $jsonResponse['product_id'] = $v['product_id'];
                $jsonResponse['name'] = $v['product_name'];
                $jsonResponse['quantity'] = $v['product_quantity'];
                $jsonResponse['variant_id'] = $v['product_attribute_id'] == 0 ? $v['product_id'] : $v['product_attribute_id'];
                $jsonResponse['sku'] = $v['product_reference'];
                if ($v['product_attribute_id']) {
                    $option['product_id'] = $v['product_id'];
                    $option['variation_id'] = $v['product_attribute_id'];
                    $combination = $this->getAttributeCombinationsById($option);
                    foreach ($combination as $key => $value) {
                        $attrName = $value['group_name'];
                        $attrValId = $value['id_attribute_group'];
                        $attrValName = $value['attribute_name'];
                        $idAttribute = $value['id_attribute'];
                        $attribute[$attrName]['id'] = $attrValId;
                        $attribute[$attrName]['name'] = $attrValName;
                        $attribute[$attrName]['attribute_id'] = $idAttribute;
                        $hexCode = '';
                        if ($value['is_color_group']) {
                            $hexCode = $this->getColorHexValue($idAttribute);
                        }
                        $attribute[$attrName]['hex-code'] = $hexCode;
                    }
                }
                $jsonResponse['images'] = $this->getProducImage(
                    $v['product_attribute_id'], $v['product_id']
                );

                if ($is_customer == true) {
                    $jsonResponse['price'] =  $v['product_price'];
                    $jsonResponse['total'] =  $v['product_price'];
                }
            }
        }
        $jsonResponse['attributes'] = $attribute;
        $jsonResponse['order_id'] = $order_id;
        $jsonResponse['order_number'] = $order_id;
        if ($is_customer == true) {
            $jsonResponse['customer_id'] = $singleOrderDetails['id_customer'];
            $jsonResponse['custom_design_id'] = $singleOrderDetails['ref_id'];
            $customerId = $singleOrderDetails['id_customer'];
            $customer = $this->getCustomerName($customerId);
            $jsonResponse['customer_email'] = $customer['email'];
            $jsonResponse['customer_first_name'] = $customer['first_name'];
            $jsonResponse['customer_last_name'] = $customer['last_name'];
            if (!empty($customer) && $customer['email'] != '') {
                $address = new \Address(intval($singleOrderDetails['id_address_invoice']));
                $state = \State::getNameById($address->id_state);
                $billing['first_name'] = $address->firstname;
                $billing['last_name'] = $address->lastname;
                $billing['company'] = $address->company;
                $billing['address_1'] = $address->address1;
                $billing['address_2'] = $address->address2;
                $billing['city'] = $address->city;
                $billing['state'] = $state ? $state : '';
                $billing['postcode'] = $address->postcode;
                $billing['country'] = $address->country;
                $billing['email'] = $customer['email'];
                $billing['phone'] = $address->phone;
                $addressInvoice = new \Address(intval($singleOrderDetails['id_address_delivery']));
                $stateAddressInvoice = \State::getNameById($addressInvoice->id_state);
                $shipping['first_name'] = $addressInvoice->firstname;
                $shipping['last_name'] = $addressInvoice->lastname;
                $shipping['company'] = $addressInvoice->company;
                $shipping['address_1'] = $addressInvoice->address1;
                $shipping['address_2'] = $addressInvoice->address2;
                $shipping['city'] = $addressInvoice->city;
                $shipping['state'] = $stateAddressInvoice ? $stateAddressInvoice : '';
                $shipping['postcode'] = $addressInvoice->postcode;
                $shipping['country'] = $addressInvoice->country;
                $shipping['email'] = $customer['email'];
                $shipping['phone'] = $addressInvoice->phone;
            } else {
                $shipping = $billing = ['first_name' => '', 'last_name' => '', 'company' => '', 'address_1' => '', 'city' => '', 'state' => '', 'postcode' => '', 'country' => '', 'email' => '', 'phone' => ''];
            }
            $jsonResponse['billing'] = $billing;
            $jsonResponse['shipping'] = $shipping;
        }
        return $jsonResponse;
    }
    /**
     *GET product categories
     *
     * @param  Int( $product_id);
     * @return String
     */
    public function getCategoryByPid($product_id) {
        $new_categories = array();
        $res_categ_new_pos = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
        SELECT id_category
        FROM ' . _DB_PREFIX_ . 'category_product
        WHERE id_product = '.$product_id.'
        ORDER BY id_category ASC');
        if (!empty($res_categ_new_pos)) {
            foreach ($res_categ_new_pos as $array) {
                $new_categories[] = $array['id_category'];
            }
        }
        return $new_categories;
    }

    public function getMultiStore()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'shop  s   JOIN ' . _DB_PREFIX_ . 'shop_url su WHERE s.id_shop = su.id_shop '; //AND s.id_shop != 1
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

}

/**
 * @package PrestaShopWebservice
 */
class PrestaShopWebserviceException extends Exception
{

}
