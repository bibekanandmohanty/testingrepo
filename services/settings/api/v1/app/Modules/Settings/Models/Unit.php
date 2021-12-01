<?php
/**
 * Unit Model
 *
 * PHP version 5.6
 *
 * @category  Units
 * @package   Settings
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Settings\Models;

/**
 * Unit
 *
 * @category Unit
 * @package  Settings
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Unit extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'app_units';
    protected $primaryKey = 'xe_id';
    public $timestamps = false;
    protected $fillable = ['name', 'name', 'is_default'];
}
