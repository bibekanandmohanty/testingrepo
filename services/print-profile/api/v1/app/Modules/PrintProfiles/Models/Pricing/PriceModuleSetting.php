<?php
/**
 * Manage Print Profile Pricing
 *
 * PHP version 5.6
 *
 * @category  Price_Module_Setting
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
 * @category Price_Module_Setting
 * @package  Pricing
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PriceModuleSetting extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'price_module_settings';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'print_profile_pricing_id',
        'price_module_id',
        'module_status',
        'is_default_price',
        'is_quote_enabled',
        'is_advance_price',
        'advance_price_settings_id',
        'is_quantity_tier',
        'quantity_tier_type',
        'default_stitch_count_per_inch',
    ];
    public $timestamps = false;

    /**
     * Relationship between Price Module Setting and Price Module
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return relationship object of category
     */
    public function price_module()
    {
        return $this->belongsTo(
            'App\Modules\PrintProfiles\Models\Pricing\PriceModule',
            'price_module_id', 'xe_id'
        );
    }

    /**
     * Relationship between Price Module Setting and Price Default Setting
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return relationship object of category
     */
    public function price_default_settings()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\Pricing\PriceDefaultSetting',
            'price_module_setting_id', 'xe_id'
        );
    }

    /**
     * Relationship between Price Module Setting and Price Tier Quantity Range
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return relationship object of category
     */
    public function price_tier_quantity_range()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\Pricing\PriceTierQuantityRange',
            'price_module_setting_id',
            'xe_id'
        );
    }

    /**
     * Relationship between Price Module Setting and Tier Price
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return relationship object of category
     */
    public function tier_prices()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\Pricing\TierPrice',
            'price_module_setting_id',
            'xe_id'
        );
    }

    /**
     * Relationship between Price Module Setting and Price Advance Price Setting
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return relationship object of category
     */
    public function price_advance_price_settings()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\Pricing\AdvancePriceSetting',
            'xe_id',
            'price_module_setting_id'
        );
    }
}
