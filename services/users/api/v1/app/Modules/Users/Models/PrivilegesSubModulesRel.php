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
class PrivilegesSubModulesRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'privileges_sub_modules_rel';
    protected $fillable = [
        'privilege_rel_id',
        'privileges_sub_module_id'
    ];
    public $timestamps = false;

}