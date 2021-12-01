<?php
/**
 * Print Profile Model
 *
 * PHP version 5.6
 *
 * @category  Print_Profile_Attribute_Rel
 * @package   Print Profiles
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\PrintProfiles\Models;
/**
 * Print Profile
 *
 * @category Print_Profile_Attribute_Rel
 * @package  Print Profiles
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintProfileAttributeRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_profile_attribute_rel';
    public $timestamps = false;
    protected $fillable = [
        'attribute_term_id',
        'tier_range_id',
        'print_profile_id'
    ];
}
