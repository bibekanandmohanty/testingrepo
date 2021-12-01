<?php
/**
 * Setting Model
 *
 * PHP version 5.6
 *
 * @category  Settings
 * @package   Settings
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Settings\Models;

/**
 * Setting
 *
 * @category Setting
 * @package  Settings
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Setting extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'settings';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
}
