<?php
/**
 * Purchase order
 *
 * PHP version 5.6
 *
 * @category  Production_Hub
 * @package   Purchase order Line Item Status
 * @author    Soumya Swain <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\PurchaseOrder\Models;
/**
 * Purchase order
 *
 * @category Production Hub
 * @package  Purchase order Line Item Status 
 * @author   Soumya Swain <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class PurchaseOrderInternalNote extends \Illuminate\Database\Eloquent\Model {
	protected $primaryKey = 'xe_id';
	protected $table = 'po_internal_note';
	protected $fillable = ['po_id','user_type','user_id','note','seen_flag','created_date'];
	public $timestamps = false;
}