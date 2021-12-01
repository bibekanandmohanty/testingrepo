<?php
/**
 * This Model used for Order Artwork status
 * corresponding models
 *
 * PHP version 5.6
 *
 * @category  Orders
 * @package   Orders
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Orders\Models;
/**
 * Orders Controller
 *
 * @category Orders
 * @package  Orders
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Orders extends \Illuminate\Database\Eloquent\Model {
	protected $table = 'orders';
	protected $primaryKey = 'xe_id';
	protected $fillable = [
		'order_id', 'artwork_status', 'order_status', 'po_status', 'production_status', 'production_percentage', 'store_id', 'customer_id', 'order_number'
	];
	public $timestamps = false;
}
