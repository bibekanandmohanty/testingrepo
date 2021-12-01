<?php
/**
 * This Model used for Font & Category relationship
 *
 * PHP version 5.6
 *
 * @category  Fonts
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Fonts\Models;

/**
 * Fonts Category Relation
 *
 * @category Fonts_Category_Relation
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class FontCategoryRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    public $timestamps = false;
    protected $table = 'font_category_rel';
    protected $fillable = ['font_id', 'category_id'];

    /**
     * Create a relationship of Fonts with Font Category Relation Model
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function category()
    {
        return $this->hasOne(
            'App\Modules\Fonts\Models\FontCategory', 'xe_id', 'category_id'
        )
            ->select('xe_id', 'name', 'is_disable', 'parent_id');
    }
    /**
     * Create a relationship of Fonts Category Relation With Fonts Model
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function font()
    {
        return $this->hasOne(
            'App\Modules\Fonts\Models\Font', 'xe_id', 'font_id'
        );
    }
}
