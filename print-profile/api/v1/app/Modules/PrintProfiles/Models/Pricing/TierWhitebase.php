<?php
/**
 * Manage Print Profile Pricing
 *
 * PHP version 5.6
 *
 * @category  Tier_Whitebase
 * @package   Pricing
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\PrintProfiles\Models\Pricing;
/**
 * Price Default Setting Controller
 *
 * @category Tier_Whitebase
 * @package  Pricing
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class TierWhitebase extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'price_tier_whitebases';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'price_tier_value_id',
        'white_base_type',
        'price',
    ];
    public $timestamps = false;
}
