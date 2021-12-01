<?php 
/**
 * Initialize Store API connection
 * 
 * @category   Slim-Component
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\Products\Controllers\Stores\Woocommerce\v3x\Controllers;
use Automattic\WooCommerce\Client;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client as GuzzleClient;

class Api {
    /**
     * Connect Slim application with Woo-Commerce
     * @author: tanmayap@riaxe.com
     * @date: 17 sept 2019 
     * @input: None
     * @return: An instance of the Woocommerce Connection
     */
    public function initWcApi() {
        $woocommerce = new Client(
            WC_API_URL, 
            WC_API_CK, 
            WC_API_CS,
            [
                'wp_api'     => true,
                'version'    => WC_API_VER,
                'verify_ssl' => WC_API_SECURE
            ]
        );

        return $woocommerce;
    }

    public function recursiveProduct($page = 0) {
        $wcApi = $this->initWcApi();
        $products = [];
        $count = 0;
        $endPoint = 'products';
        $getProducts = $wcApi->get($endPoint, ['page' => $page, 'per_page' => 5 ]);
        if(isset($getProducts) && count($getProducts) > 0) {
            $count += count($getProducts);
            $nextPage = $page + 1;
            $this->recursiveProduct($nextPage);
        }

        return $count;
    }
}
