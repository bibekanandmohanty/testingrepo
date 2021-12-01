<?php
/**
 * Manage Print Profile Pricing
 *
 * PHP version 5.6
 *
 * @category  Price_Tier_Quantity_Range
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
 * @category Price_Tier_Quantity_Range
 * @package  Pricing
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PriceTierQuantityRange extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'price_tier_quantity_ranges';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'price_module_setting_id',
        'quantity_from',
        'quantity_to',
    ];
    public $timestamps = false;
}
