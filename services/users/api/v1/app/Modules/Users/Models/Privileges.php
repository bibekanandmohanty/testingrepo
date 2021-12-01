<?php
/**
 * Privileges Model
 *
 * PHP version 5.6
 *
 * @category  Privileges
 * @package   Users
 * @author    Ramasankar <ramasankarm@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Users\Models;

/**
 * User privileges
 *
 * @category Privileges
 * @package  Users
 * @author   Ramasankar <ramasankarm@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Privileges extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_privileges';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
}