<?php
/**
 * User Module Privilege Model
 *
 * PHP version 5.6
 *
 * @category  User Module Privilege Model
 * @package   Users
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */

namespace App\Modules\Users\Models;

/**
 * User Module Privilege Model
 *
 * @category User Module Privilege Model
 * @package  Users
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class UserModulePrivilegeRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_module_privilege_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'user_id', 'role_id', 'role_type', 'privilege_id'
    ];
    public $timestamps = false;
}