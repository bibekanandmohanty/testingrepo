<?php
/**
 * Security Question Model
 *
 * PHP version 5.6
 *
 * @category  Security_Questions
 * @package   Users
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Users\Models;

/**
 * User Security_Questions
 *
 * @category Security_Questions
 * @package  Users
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class SecurityQuestion extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'security_questions';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
}