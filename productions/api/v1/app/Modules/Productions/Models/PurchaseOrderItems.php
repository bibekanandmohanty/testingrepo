<?php
/**
 * This Model used for Order Artwork status
 * corresponding models
 *
 * PHP version 5.6
 *
 * @category  Purchase Order Items
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Productions\Models;
/**
 * Purchase Order Items Controller
 *
 * @category Purchase Order Items
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PurchaseOrderItems extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'purchase_order_items';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'purchase_order_id', 'order_id', 'order_item_id', 'status_id'
    ];
    public $timestamps = false;
}
