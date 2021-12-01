<?php
/**
 * Manage Print Profile Pricing
 *
 * PHP version 5.6
 *
 * @category  Print_Profile_Pricing
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
 * @category Print_Profile_Pricing
 * @package  Pricing
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintProfilePricing extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_profile_pricings';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'print_profile_id',
        'is_white_base',
        'white_base_type',
        'is_setup_price',
        'setup_price',
        'setup_type_product',
        'setup_type_order',
    ];
    public $timestamps = false;

    /**
     * Relationship between Print Profile Pricing and Price Module Setting
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return relationship object of category
     */
    public function price_module_settings()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\Pricing\PriceModuleSetting', 
            'print_profile_pricing_id', 
            'xe_id'
        );
    }
}
