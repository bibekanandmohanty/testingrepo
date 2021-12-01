<?php
/**
 * Vendor
 *
 * PHP version 5.6
 *
 * @category  Vendor
 * @package   Production_Hub
 * @author    Soumya Swain <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Vendors\Models;
/**
 * Vendor
 *
 * @category Vendor Category
 * @package  Production_Hub
 * @author   Soumya Swain <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class VendorCategory extends \Illuminate\Database\Eloquent\Model {
	protected $table = 'vendor_category_rel';
	protected $fillable = ['vendor_id', 'category_id'];
	public $timestamps = false;
}