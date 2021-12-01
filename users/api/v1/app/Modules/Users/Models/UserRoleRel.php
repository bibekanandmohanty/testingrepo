<?php
/**
 * User Role Relation Model
 *
 * PHP version 5.6
 *
 * @category  UserRoleRel
 * @package   Users
 * @author    Ramasankar <ramasankarm@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Users\Models;

/**
 * User Role Relation
 *
 * @category UserRoleRel
 * @package  Users
 * @author   Ramasankar <ramasankarm@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class UserRoleRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_role_rel';
    protected $fillable = [
        'user_id',
        'role_id'
    ];
    public $timestamps = false;

    /**
     * Create One-to-Many relationship between User-Role-Relationship and
     * User-Role
     *
     * @author ramasankar@riaxe.com
     * @date   22 Jan 2020
     * @return relationship object of User Role
     */
    public function userRoles() 
    {
        return $this->hasMany(
            'App\Modules\Users\Models\UserRole', 'xe_id', 'role_id'
        );
    }
    /**
     * Create One-to-Many relationship between User-Role-Relationship and
     * User Role Privileges Relation
     *
     * @author tanmayap@riaxe.com
     * @date   22 Jan 2020
     * @return relationship object of User Role
     */
    public function user_role_privi_rel()
    {
        return $this->hasMany(
            'App\Modules\Users\Models\UserRolePrivilegesRel', 'role_id', 'role_id'
        );
    }
}