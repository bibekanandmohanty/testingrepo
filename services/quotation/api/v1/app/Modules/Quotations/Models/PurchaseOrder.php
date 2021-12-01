<?php
/**
 * Purchase order status
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

namespace App\Modules\Quotations\Models;
/**
 * Vendor
 *
 * @category Vendor
 * @package  Production_Hub
 * @author   Soumya Swain <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class PurchaseOrder extends \Illuminate\Database\Eloquent\Model {
	protected $primaryKey = 'xe_id';
	protected $table = 'purchase_order';
	protected $fillable = ['po_id', 'vendor_id', 'order_id', 'po_status_id', 'store_id','po_note','created_at'];
	public $timestamps = false;
}