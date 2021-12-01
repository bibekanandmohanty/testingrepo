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
 * @link      http://imprintnext.io
 */

namespace App\Modules\Productions\Models;

/**
 * Users
 *
 * @category Users
 * @package  Users
 * @author   Ramasankar <ramasankarm@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
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
    
}
