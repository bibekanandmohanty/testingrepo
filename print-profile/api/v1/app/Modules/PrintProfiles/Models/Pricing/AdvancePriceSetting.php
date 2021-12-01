<?php
/**
 * Manage Print Profile Pricing
 *
 * PHP version 5.6
 *
 * @category  Advance_Price_Setting
 * @package   Pricing
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\PrintProfiles\Models\Pricing;

/**
 * Advance Price Setting Controller
 *
 * @category Advance_Price_Setting
 * @package  Pricing
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class AdvancePriceSetting extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'price_advanced_price_settings';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'advanced_price_type',
        'no_of_colors_allowed',
        'is_full_color',
        'area_calculation_type',
        'min_price',
    ];
    public $timestamps = false;
}
