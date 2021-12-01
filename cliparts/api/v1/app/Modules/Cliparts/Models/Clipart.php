<?php
/**
 * Cliparts Model
 *
 * PHP version 5.6
 *
 * @category  Cliparts
 * @package   Assets
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Cliparts\Models;

use App\Components\Controllers\Component as ParentController;

/**
 * Cliparts
 *
 * @category Cliparts
 * @package  Assets
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Clipart extends \Illuminate\Database\Eloquent\Model
{

    protected $table = 'cliparts';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'name', 'price', 'file_name', 'width', 'height', 
        'is_scaling', 'enable_scaling', 'store_id'
    ];
    // As we modified the value of 'file_name' attribute. So to get the only
    // file name we do this heck
    protected $appends = ['raw_file_name', 'thumbnail', 'category_names'];
    public $timestamps = false;
    
    /**
     * Create One-to-Many relationship between Clipart and
     * Clipart-Category-Relationship
     *
     * @author tanmayap@riaxe.com
     * @author debashreeb@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function clipartCategory()
    {
        return $this->hasMany(
            'App\Modules\Cliparts\Models\ClipartCategoryRelation',
            'clipart_id'
        );
    }

    /**
     * Create Many-to-Many relationship between Clipart and Category
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function categories()
    {
        return $this->belongsToMany(
            'App\Modules\Cliparts\Models\ClipartCategoryRelation', 
            'clipart_category_rel', 'clipart_id', 'category_id'
        );
    }

    /**
     * Create a relationship of Clipart with Clipart-Tags-Relationship Model
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function clipartTags()
    {
        return $this->hasMany(
            'App\Modules\Cliparts\Models\ClipartTagRelation', 
            'clipart_id'
        );
    }

    /**
     * Create Many-to-Many relationship of Clipart with Clipart-Tags
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function tags()
    {
        return $this->belongsToMany(
            'App\Modules\Cliparts\Models\ClipartTagRelation', 
            'clipart_tag_rel', 'clipart_id', 'tag_id'
        );
    }

    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the file_name before sending the response
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function getFileNameAttribute()
    {
        if (!empty($this->attributes['file_name'])) {
            return path('read', 'vector') . $this->attributes['file_name'];
        }
        return null;
    }

    /**
     * Getting full thumb url from file_name by manipulate file_name value
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function getThumbnailAttribute()
    {
        if (!empty($this->attributes['file_name']) 
            && file_exists(path('abs', 'vector') . 'thumb_' . $this->attributes['file_name'])
        ) {
            return path('read', 'vector') . 'thumb_' . $this->attributes['file_name'];
        } else {
            return path('read', 'vector') . $this->attributes['file_name'];
        }
        return null;
    }
    
    /**
     * As 'file_name' was modified so to get only file name we have to do this
     * heck
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function getRawFileNameAttribute()
    {
        return $this->attributes['file_name'];
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
            'Cliparts', 'ClipartCategoryRelation', 
            'clipart_id', $this->attributes['xe_id'], 
            'name'
        );
        if (!empty($getData) && count($getData) > 0) {
            $categoryList = implode(', ', $getData);
            $categoryList = trim(ltrim(rtrim($categoryList, ','), ','));
        }
        return $categoryList;
    }
}
