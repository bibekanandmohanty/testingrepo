<?php
/**
 * Fonts Model
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

use App\Components\Controllers\Component as ParentController;

/**
 * Fonts
 *
 * @category Fonts
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class Font extends \Illuminate\Database\Eloquent\Model
{

    protected $primaryKey = 'xe_id';
    protected $fillable = ['name', 'price', 'store_id', 'file_name', 'font_family'];
    protected $appends = ['category_names'];
    public $timestamps = false;

    /**
     * Create relationship between Font and
     * Distress-Category-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function fontCategory()
    {
        return $this->hasMany(
            'App\Modules\Fonts\Models\FontCategoryRelation', 'font_id'
        );
    }

    /**
     * Create relationship between Font and Category
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function categories()
    {
        return $this->belongsToMany(
            'App\Modules\Fonts\Models\FontCategoryRelation', 
            'font_category_rel', 'font_id', 'category_id'
        );
    }

    /**
     * Create relationship between Font and
     * Font-Tags-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tags
     */
    public function fontTags()
    {
        return $this->hasMany('App\Modules\Fonts\Models\FontTagRelation', 'font_id');
    }

    /**
     * Create relationship between Font and Tag
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tag
     */
    public function tags()
    {
        return $this->belongsToMany(
            'App\Modules\Fonts\Models\FontTagRelation', 
            'font_tag_rel', 'font_id', 'tag_id'
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
        return path('read', 'font') . $this->attributes['file_name'];
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
            'Fonts', 'FontCategoryRelation',
            'font_id', $this->attributes['xe_id'],
            'name'
        );
        if (!empty($getData) && count($getData) > 0) {
            $categoryList = implode(', ', $getData);
            $categoryList = trim(ltrim(rtrim($categoryList, ','), ','));
        }
        return $categoryList;
    }
}
