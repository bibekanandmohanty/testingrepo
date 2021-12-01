<?php
/**
 * This Model used for Print Profile Assets Category Rel
 *
 * PHP version 5.6
 *
 * @category  Print_Profile_Assets_Category_Rel
 * @package   Print_Profile
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\PrintProfiles\Models;

/**
 * Print Profile Assets Category Rel Class
 *
 * @category Print_Profile_Assets_Category_Rel
 * @package  Print_Profile
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintProfileAssetsCategoryRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_profile_assets_category_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
    /**
     * Create a relationship of Assets Category Relation
     * With Fonts Category Relation
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function font_category_rel()
    {
        return $this->hasOne(
            'App\Modules\Fonts\Models\FontCategoryRelation', 'category_id', 'category_id'
        );
    }
}
