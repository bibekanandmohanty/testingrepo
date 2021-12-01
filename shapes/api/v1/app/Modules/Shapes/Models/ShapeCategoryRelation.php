<?php
/**
 * This Model used for Shape & Category relationship
 *
 * PHP version 5.6
 *
 * @category  Shapes
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Shapes\Models;

/**
 * Shape Category Relation
 *
 * @category Shapes_Category_Relation
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ShapeCategoryRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    public $timestamps = false;
    protected $table = 'shape_category_rel';
    protected $fillable = ['shape_id', 'category_id'];
    
    /**
     * Create a relationship of Shape with Shape Category Relation Model
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function category()
    {
        return $this->hasOne(
            'App\Modules\Shapes\Models\ShapeCategory', 'xe_id', 'category_id'
        )
            ->select('xe_id', 'name', 'is_disable', 'parent_id');
    }
}
