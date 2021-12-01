<?php
/**
 * Product Image Sides
 *
 * PHP version 5.6
 *
 * @category  Product_Image_Sides
 * @package   Products
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Product Image Sides Class
 *
 * @category Product_Image
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductImageSides extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'product_image_sides';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
    protected $appends = ['raw_file_name', 'thumbnail'];

    /**
     * Regenerate File Full URL for front-end
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return string file url
     */
    public function getFileNameAttribute()
    {
        $url = "";
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            $url .= path('read', 'product');
            $url .= $this->attributes['file_name'];
        }

        return $url;
    }
    /**
     * Regenerate Thumbnail URL
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return string file url
     */
    public function getThumbnailAttribute()
    {
        $url = "";
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            $url .= path('read', 'product');
            $url .= 'thumb_' . $this->attributes['file_name'];
        }

        return $url;
    }
    /**
     * Get Raw File Name
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return string raw file name
     */
    public function getRawFileNameAttribute()
    {
        return $this->attributes['file_name'];
    }
}
