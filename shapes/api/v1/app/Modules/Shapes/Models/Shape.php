<?php
/**
 * Shape Model
 *
 * PHP version 5.6
 *
 * @category  Shape
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Shapes\Models;

use App\Components\Controllers\Component as ParentController;

/**
 * Shape
 *
 * @category Shape
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Shape extends \Illuminate\Database\Eloquent\Model
{

    protected $primaryKey = 'xe_id';
    protected $fillable = ['name', 'file_name', 'store_id'];
    protected $appends = ['category_names'];
    public $timestamps = false;

    /**
     * Create relationship between Shape and
     * Shape-Category-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function shapeCategory()
    {
        return $this->hasMany(
            'App\Modules\Shapes\Models\ShapeCategoryRelation', 'shape_id'
        );
    }
    /**
     * Create relationship between Disress and Category
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function categories()
    {
        return $this->belongsToMany(
            'App\Modules\Shapes\Models\ShapeCategoryRelation', 
            'shape_category_rel', 'shape_id', 'category_id'
        );
    }

    /**
     * Create relationship between Shape and
     * Shape-Tag-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tags
     */
    public function shapeTags()
    {
        return $this->hasMany(
            'App\Modules\Shapes\Models\ShapeTagRelation', 'shape_id'
        );
    }
    /**
     * Create relationship between Disress and Tag
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tag
     */
    public function tags()
    {
        return $this->belongsToMany(
            'App\Modules\Shapes\Models\ShapeTagRelation', 
            'shape_tag_rel', 'shape_id', 'tag_id'
        );
    }
    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the file_name before sending the response
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return file path url
     */
    public function getFileNameAttribute()
    {
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            return path('read', 'shape') . $this->attributes['file_name'];
        }
        return "";
    }

    /**
     * Get Category lists in comma separated format
     *
     * @author tanmayap@riaxe.com
     * @date   14 Jan 2020
     * @return relationship object of category
     */
    public function getCategoryNamesAttribute()
    {
        $categoryList = "";
        $parentInit = new ParentController();
        $getData = $parentInit->getCategoriesById(
            'Shapes', 'ShapeCategoryRelation', 
            'shape_id', $this->attributes['xe_id'], 
            'name'
        );
        if (!empty($getData) && count($getData) > 0) {
            $categoryList = implode(', ', $getData);
        }
        return $categoryList;
    }
}
