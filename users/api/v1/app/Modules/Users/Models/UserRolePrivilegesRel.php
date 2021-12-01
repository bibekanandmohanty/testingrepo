<?php
/**
 * User Role Privileges Relation Model
 *
 * PHP version 5.6
 *
 * @category  UserRolePrivilegesRel
 * @package   Users
 * @author    Ramasankar <ramasankarm@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Users\Models;

/**
 * User Role Privileges Relation
 *
 * @category UserRolePrivilegesRel
 * @package  Users
 * @author   Ramasankar <ramasankarm@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class UserRolePrivilegesRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_role_privileges_rel';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'role_id',
        'privilege_id',
        'privilege_type'
    ];
    protected $guarded = ['xe_id'];
    public $timestamps = false;

    /**
     * Create One-to-Many relationship between User-Role-Privilege-Relation and
     * User-Privilege
     *
     * @author ramasankar@riaxe.com
     * @date   22 Jan 2020
     * @return relationship object of User Privilege
     */
    public function privileges() 
    {
        return $this->hasOne(
            'App\Modules\Users\Models\Privileges', 'xe_id', 'privilege_id'
        );
    }
}