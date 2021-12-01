<?php
/**
 * Ship address
 *
 * PHP version 5.6
 *
 * @category  Ship Address
 * @package   Production_Hub
 * @author    Soumya Swain <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\ShipAddress\Models;
/**
 * Vendor
 *
 * @category Vendor
 * @package  Production_Hub
 * @author   Soumya Swain <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class ShipAddress extends \Illuminate\Database\Eloquent\Model {
	protected $primaryKey = 'xe_id';
	protected $table = 'ship_to_address';
	protected $fillable = ['name', 'email', 'phone', 'company_name', 'country_code', 'state_code', 'zip_code', 'ship_address', 'city', 'store_id'];
	public $timestamps = false;
}