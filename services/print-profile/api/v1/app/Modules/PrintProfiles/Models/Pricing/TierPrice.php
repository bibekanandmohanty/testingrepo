<?php
/**
 * Manage Print Profile Pricing
 *
 * PHP version 5.6
 *
 * @category  Tier_Price
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
 * @category Tier_Price
 * @package  Pricing
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class TierPrice extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'price_tier_values';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'attribute_type',
        'price_module_setting_id',
        'print_area_index',
        'color_index',
        'print_area_id',
        'range_from',
        'range_to',
        'key_name',
        'screen_cost'
    ];
    public $timestamps = false;

    /**
     * Relationship between Tier Whitebase and Tier Price
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return relationship object of category
     */
    public function whitebase()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\Pricing\TierWhitebase', 
            'price_tier_value_id', 
            'xe_id'
        );
    }
}
