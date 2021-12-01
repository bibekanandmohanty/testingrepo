<?php
/**
 * Manage Customer
 *
 * PHP version 5.6
 *
 * @category  Customers
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace MultiStoreStoreSpace\Controllers;

use CommonStoreSpace\Controllers\StoreController;

/**
 * Customer Controller
 *
 * @category Class
 * @package  Customer
 * @author   Satyabrata <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreMultiStoreController extends StoreController {
	/**
	 * GET: Get all blogs
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Arguments
	 *
	 * @author soumyas@riaxe.com
	 * @date   5 Nov 2019
	 * @return Array
	 */

	public function getAllStores() {
		$blogsList = $this->plugin->get('multi_store');
		return $blogsList;
	}

}
