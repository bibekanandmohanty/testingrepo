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
 * @link      http://imprintnext.io
 */

namespace App\Modules\Productions\Models;

/**
 * User Role
 *
 * @category UserRole
 * @package  Users
 * @author   Ramasankar <ramasankarm@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
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
    
}