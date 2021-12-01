<?php
/**
 * This Model used for Order Log and creating relationship with other
 * corresponding models
 *
 * PHP version 5.6
 *
 * @category  Orders_Log
 * @package   Orders
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Orders\Models;
/**
 * Order Log Controller
 *
 * @category Orders_Log
 * @package  Orders
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class OrderLog extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'order_logs';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'order_id', 'agent_id', 'agent_type', 'message', 
        'status', 'artwork_status', 'is_file', 'log_type', 'store_id'
    ];

    /**
     * Get Order Log Files
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return file relation object
     */
    public function files()
    {
        return $this->hasMany(
            'App\Modules\Orders\Models\OrderLogFiles', 'order_log_id'
        );
    }
}
