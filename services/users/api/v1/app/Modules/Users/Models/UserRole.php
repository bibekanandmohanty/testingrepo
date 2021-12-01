<?php
/**
 * User Role Model
 *
 * PHP version 5.6
 *
 * @category  UserRole
 * @package   Users
 * @author    Ramasankar <ramasankarm@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Users\Models;

/**
 * User Role
 *
 * @category UserRole
 * @package  Users
 * @author   Ramasankar <ramasankarm@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class UserRole extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_roles';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'store_id',
        'role_name'
    ];
    public $timestamps = false;

    /**
     * Create One-to-Many relationship between User-Role and
     * User-Role-Privilege-Relationship
     *
     * @author ramasankar@riaxe.com
     * @date   22 Jan 2020
     * @return relationship object of User Role Privilege Relation
     */
    public function privileges() 
    {
        return $this->hasMany(
            'App\Modules\Users\Models\UserRolePrivilegesRel', 'role_id', 'xe_id'
        );
    }
}