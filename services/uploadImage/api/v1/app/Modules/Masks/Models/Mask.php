<?php
/**
 * Mask Model
 *
 * PHP version 5.6
 *
 * @category  Mask
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Masks\Models;

/**
 * Mask
 *
 * @category Mask
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Mask extends \Illuminate\Database\Eloquent\Model
{

    protected $primaryKey = 'xe_id';
    protected $fillable = ['name', 'mask_name', 'file_name', 'store_id'];
    protected $appends = ['thumbnail'];
    public $timestamps = false;

    /**
     * Create relationship between Mask and
     * Mask-Tag-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tags
     */
    public function maskTags()
    {
        return $this->hasMany('App\Modules\Masks\Models\MaskTagRelation', 'mask_id');
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
            'App\Modules\Masks\Models\MaskTagRelation', 
            'mask_tag_rel', 'mask_id', 'tag_id'
        );
    }
     /**
      * This is a method from Eloquent. The basic functionality of this method is
      * to modify the mask_file_name before sending the response
      *
      * @author satyabratap@riaxe.com
      * @date   4th Nov 2019
      * @return file path url
      */
    public function getMaskNameAttribute()
    {
        if (isset($this->attributes['mask_name']) 
            && $this->attributes['mask_name'] != ""
        ) {
            return path('read', 'mask') . $this->attributes['mask_name'];
        }
        return "";
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
            return path('read', 'mask') . $this->attributes['file_name'];
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
            return path('read', 'mask') . 'thumb_' . $this->attributes['file_name'];
        }
        return "";
    }
    

}
