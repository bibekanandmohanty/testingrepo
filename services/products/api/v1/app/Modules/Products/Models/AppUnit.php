<?php
/**
 * App Unit Model
 *
 * PHP version 5.6
 *
 * @category  App_Unit
 * @package   Products
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * App Unit Class
 *
 * @category App_Unit
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class AppUnit extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $primaryKey = 'xe_id';
    protected $fillable = ['name', 'is_default'];
    protected $guarded = ['xe_id'];
}
