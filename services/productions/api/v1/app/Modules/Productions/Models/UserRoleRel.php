<?php
/**
 * User Role Relation Model
 *
 * PHP version 5.6
 *
 * @category  UserRoleRel
 * @package   Users
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */
namespace App\Modules\Productions\Models;
/**
 * User Role Relation
 *
 * @category UserRoleRel
 * @package  Users
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class UserRoleRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_role_rel';
    protected $fillable = [
        'user_id',
        'role_id'
    ];
    public $timestamps = false;
}
