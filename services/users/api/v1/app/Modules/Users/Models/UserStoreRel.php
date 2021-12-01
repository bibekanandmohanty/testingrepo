<?php
/**
 * Store Model
 *
 * PHP version 5.6
 *
 * @category  Store
 * @package   Users
 * @author    Soumya <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Users\Models;

/**
 * User Store
 *
 * @category Store
 * @package  Users
 * @author   Soumya <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class UserStoreRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_store_rel';
    protected $fillable = [
        'user_id',
        'store_id',
    ];
    public $timestamps = false;
}