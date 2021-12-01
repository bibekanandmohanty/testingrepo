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
 * @link      http://imprintnext.io
 */
namespace App\Modules\Productions\Models;
/**
 * Orders Controller
 *
 * @category Orders
 * @package  Orders
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class Orders extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'orders';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'store_id', 'order_id', 'artwork_status', 'order_status','production_status', 'production_percentage', 'customer_id'
    ];
    public $timestamps = false;
}
