<?php
/**
 * User Model
 *
 * PHP version 5.6
 *
 * @category  Users
 * @package   Users
 * @author    Ramasankar <ramasankarm@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Users\Models;

/**
 * Users
 *
 * @category Users
 * @package  Users
 * @author   Ramasankar <ramasankarm@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class User extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'admin_users';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'store_id',
        'name',
        'email',
        'password',
        'first_question_id',
        'first_answer',
        'second_question_id',
        'second_answer',
        'avatar',
        'created_at'
    ];
    public $timestamps = false;

    /**
     * Create One-to-Many relationship between User and
     * User-Role-Relationship
     *
     * @author ramasankar@riaxe.com
     * @date   22 Jan 2020
     * @return relationship object of user_role
     */
    public function user_roles() 
    {
        return $this->hasMany(
            'App\Modules\Users\Models\UserRoleRel', 'user_id', 'xe_id'
        );
    }

    /**
     * Create One-to-Many relationship between User and
     * User-Privilege-Relationship
     *
     * @author ramasankar@riaxe.com
     * @date   22 Jan 2020
     * @return relationship object of User Privilege Relation
     */
    public function hasPrivileges() 
    {
        return $this->hasMany(
            'App\Modules\Users\Models\UserPrivilegesRel', 'user_id', 'xe_id'
        );
    }

    /**
     * Create One-to-Many relationship between User and
     * First Security Question
     *
     * @author satyabratap@riaxe.com
     * @date   22 Jan 2020
     * @return relationship object of User Privilege Relation
     */
    public function hasQuestionOne() 
    {
        return $this->hasOne(
            'App\Modules\Users\Models\SecurityQuestion', 'xe_id', 'first_question_id'
        );
    }

    /**
     * Create One-to-Many relationship between User and
     * Second Security Question
     *
     * @author satyabratap@riaxe.com
     * @date   22 Jan 2020
     * @return relationship object of User Privilege Relation
     */
    public function hasQuestionTwo() 
    {
        return $this->hasOne(
            'App\Modules\Users\Models\SecurityQuestion', 'xe_id', 'second_question_id'
        );
    }
}
