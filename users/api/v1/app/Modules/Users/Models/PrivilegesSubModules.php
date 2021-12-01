<?php
/**
 * Privileges Model
 *
 * PHP version 5.6
 *
 * @category  Privileges Type
 * @package   Users
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */

namespace App\Modules\Users\Models;

/**
 * Privileges Type
 *
 * @category Privileges Type
 * @package  Users
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class PrivilegesSubModules extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'privileges_sub_modules';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
}