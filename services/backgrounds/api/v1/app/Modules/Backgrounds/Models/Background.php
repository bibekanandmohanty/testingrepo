<?php
/**
 * Backgrounds Model
 *
 * PHP version 5.6
 *
 * @category  Backgrounds
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Backgrounds\Models;

use App\Components\Controllers\Component as ParentController;

/**
 * Backgrounds
 *
 * @category Backgrounds
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class Background extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'backgrounds';
    protected $primaryKey = 'xe_id';
    protected $fillable = ['name', 'value', 'price', 'store_id', 'type'];
    protected $appends = ['thumbnail', 'category_names'];
    public $timestamps = false;

    /**
     * Create relationship between Background and
     * Background-Category-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function backgroundCategory()
    {
        return $this->hasMany(
            'App\Modules\Backgrounds\Models\BackgroundCategoryRelation', 
            'background_id'
        );
    }

    /**
     * Create relationship between Background and Category
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function categories()
    {
        return $this->belongsToMany(
            'App\Modules\Backgrounds\Models\BackgroundCategoryRelation', 
            'background_category_rel', 'background_id', 'category_id'
        );
    }

    /**
     * Create relationship between Backgrounds and
     * Backgrounds-Tag-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tags
     */
    public function backgroundTags()
    {
        return $this->hasMany(
            'App\Modules\Backgrounds\Models\BackgroundTagRelation', 'background_id'
        );
    }

    /**
     * Create relationship between Backgrounds and Tag
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tag
     */
    public function tags()
    {
        return $this->belongsToMany(
            'App\Modules\Backgrounds\Models\BackgroundTagRelation', 
            'background_tag_rel', 'background_id', 'tag_id'
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
    public function getValueAttribute()
    {
        if (isset($this->attributes['value']) 
            && $this->attributes['value'] != "" 
            && $this->attributes['type'] == 1
        ) {
            return path('read', 'background') . $this->attributes['value'];
        }
        return $this->attributes['value'];
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
        if (isset($this->attributes['value']) 
            && $this->attributes['value'] != "" 
            && $this->attributes['type'] == 1
        ) {
            return path('read', 'background') 
                . 'thumb_' . $this->attributes['value'];
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
            'Backgrounds', 'BackgroundCategoryRelation',
            'background_id', $this->attributes['xe_id'],
            'name'
        );
        if (!empty($getData) && count($getData) > 0) {
            $categoryList = implode(', ', $getData);
            $categoryList = trim(ltrim(rtrim($categoryList, ','), ','));
        }
        return $categoryList;
    }
}
