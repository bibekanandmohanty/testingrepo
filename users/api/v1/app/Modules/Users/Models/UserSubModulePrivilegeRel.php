<?php
/**
 * User Privileges Type Relation Model
 *
 * PHP version 5.6
 *
 * @category  PrivilegesTypeRel
 * @package   Users
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */

namespace App\Modules\Users\Models;

/**
 * User Role Privileges Type Relation
 *
 * @category PrivilegesTypeRel
 * @package  Users
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class UserSubModulePrivilegeRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_sub_module_privilege_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'user_module_privilege_id',
        'action_id'
    ];
    public $timestamps = false;

}