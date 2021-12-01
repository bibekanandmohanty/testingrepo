<?php
/**
 * Print Profile Product Setting Relation
 *
 * PHP version 5.6
 *
 * @category  Print_Profile_Product_Setting_Relation
 * @package   Products
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Print Profile Product Setting Relation Class
 *
 * @category Print_Profile_Product_Setting_Relation
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintProfileProductSettingRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_profile_product_setting_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['print_profile_id', 'product_setting_id'];
    public $timestamps = false;

    /**
     * Create One-to-Many relationship between Print Profile Product Setting
     * Relation and Print Profile
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function profile()
    {
        return $this->hasOne(
            'App\Modules\PrintProfiles\Models\PrintProfile', 
            'xe_id', 
            'print_profile_id'
        );
    }
}
