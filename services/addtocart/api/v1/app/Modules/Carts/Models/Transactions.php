<?php
/**
 * Transactions Type Model
 *
 * PHP version 5.6
 *
 * @category  Transactions
 * @package   Transactions
 * @author    Radhnatha <radhanatham@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Carts\Models;

/**
 * Transactions Type Class
 *
 * @category Transactions
 * @package  Transactions
 * @author   Radhanatha <radhanatham@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Transactions extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'transactions';
    protected $guarded = ['xe_id'];
}
