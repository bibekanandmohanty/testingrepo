<?php
/**
 * Distress Model
 *
 * PHP version 5.6
 *
 * @category  Distress
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Distresses\Models;

use App\Components\Controllers\Component as ParentController;

/**
 * Distress
 *
 * @category Distress
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Distress extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $fillable = ['name', 'file_name', 'store_id'];
    protected $appends = ['thumbnail', 'category_names'];
    public $timestamps = false;

    /**
     * Create relationship between Distress and
     * Distress-Category-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function distressCategory()
    {
        return $this->hasMany(
            'App\Modules\Distresses\Models\DistressCategoryRelation', 'distress_id'
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
            'App\Modules\Distresses\Models\DistressCategoryRelation', 
            'distress_category_rel', 'distress_id', 'category_id'
        );
    }

    /**
     * Create relationship between Distress and
     * Distress-Tag-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tags
     */
    public function distressTags()
    {
        return $this->hasMany(
            'App\Modules\Distresses\Models\DistressTagRelation', 'distress_id'
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
            'App\Modules\Distresses\Models\DistressTagRelation', 
            'distress_tag_rel', 'distress_id', 'tag_id'
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
            return  path('read', 'distress') . $this->attributes['file_name'];
        }
        return "";
    }

    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the thumbnail of the file before sending the response
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return file path url
     */
    public function getThumbnailAttribute()
    {
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            return  path('read', 'distress') 
                . 'thumb_' . $this->attributes['file_name'];
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
            'Distresses', 'DistressCategoryRelation', 
            'distress_id', $this->attributes['xe_id'], 
            'name'
        );
        if (!empty($getData) && count($getData) > 0) {
            $categoryList = implode(', ', $getData);
            $categoryList = trim(ltrim(rtrim($categoryList, ','), ','));
        }
        return $categoryList;
    }
}
