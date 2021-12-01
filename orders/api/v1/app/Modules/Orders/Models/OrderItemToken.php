<?php
/**
 * This Model used for Order Artwork status
 * corresponding models
 *
 * PHP version 5.6
 *
 * @category  Orders
 * @package   Orders
 * @author    Soumya Swain <soumyas@riaxe.com>
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
 * @author   Soumya Swain <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class OrderItemToken extends \Illuminate\Database\Eloquent\Model {
	protected $table = 'order_item_token';
	protected $fillable = [
		'order_id', 'order_item_id', 'token',
	];
	public $timestamps = false;
}
