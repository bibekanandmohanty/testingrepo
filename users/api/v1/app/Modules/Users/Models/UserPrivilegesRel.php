<?php
/**
 * User Privileges Relation Model
 *
 * PHP version 5.6
 *
 * @category  UserPrivilegesRel
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
 * @category UserPrivilegesRel
 * @package  Users
 * @author   Ramasankar <ramasankarm@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class UserPrivilegesRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_privileges_rel';
    // protected $visible = [
    //     'privilege_id',
    //     'privilege_type'
    // ];
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'user_id',
        'privilege_id',
        'privilege_type'
    ];
    public $timestamps = false;

    /**
     * Create One-to-Many relationship between User-Privilege-Relationship and
     * User-Privilege
     *
     * @author ramasankar@riaxe.com
     * @date   22 Jan 2020
     * @return relationship object of User Privilege
     */
    // public function hasPrivileges() 
    // {
    //     return $this->hasMany(
    //         'App\Modules\Users\Models\Privileges', 'xe_id', 'privilege_id'
    //     );
    // }

    public function privileges() 
    {
        return $this->hasOne(
            'App\Modules\Users\Models\Privileges', 'xe_id', 'privilege_id'
        );
    }
}