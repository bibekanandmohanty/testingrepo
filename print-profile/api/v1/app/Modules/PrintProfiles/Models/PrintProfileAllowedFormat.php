<?php
/**
 * This Model used for Print Profile Allowed Format
 *
 * PHP version 5.6
 *
 * @category  Print_Profile_Allowed_Format
 * @package   Print_Profile
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\PrintProfiles\Models;

/**
 * Print Profile Allowed Format Class
 *
 * @category Print_Profile_Allowed_Format
 * @package  Print_Profile
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintProfileAllowedFormat extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_profile_allowed_formats';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
}
