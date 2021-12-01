<?php
/**
 * Product Configurator Image
 *
 * PHP version 5.6
 *
 * @category  Product_Configurator_Image
 * @package   Products
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Product Configurator Image Class
 *
 * @category Product_Configurator_Image
 * @package  Products
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductSectionImage extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'product_section_images';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
    protected $appends = ['thumbnail', 'type'];

    /**
     * Regenerate File Full URL for front-end
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return string file url
     */
    public function getFileNameAttribute()
    {
        $url = "";
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            $url .= path('read', 'section');
            $url .= $this->attributes['file_name'];
        }

        return $url;
    }

    /**
     * Regenerate File Full URL for front-end
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return string file url
     */
    public function getThumbValueAttribute()
    {
        if (isset($this->attributes['thumb_value']) 
            && $this->attributes['thumb_value'] != "" 
            && pathinfo($this->attributes['thumb_value'], PATHINFO_EXTENSION) != ""
        ) {
            return path('read', 'section') . $this->attributes['thumb_value'];
        }
        return $this->attributes['thumb_value'];
    }

    /**
     * Getting full thumb url from file_name by manipulate file_name value
     *
     * @author satyabratap@riaxe.com
     * @date   25 oct 2020
     * @return relationship object of category
     */
    public function getThumbnailAttribute()
    {
        if (!empty($this->attributes['file_name']) 
            && file_exists(path('abs', 'section') . 'thumb_' . $this->attributes['file_name'])
        ) {
            return path('read', 'section') . 'thumb_' . $this->attributes['file_name'];
        } else {
            return path('read', 'section') . $this->attributes['file_name'];
        }
        return null;
    }

    /**
     * Getting type of the thumb_value
     *
     * @author satyabratap@riaxe.com
     * @date   25 oct 2020
     * @return relationship object of category
     */
    public function getTypeAttribute()
    {
        if (isset($this->attributes['thumb_value']) 
            && $this->attributes['thumb_value'] != "" 
            && pathinfo($this->attributes['thumb_value'], PATHINFO_EXTENSION) != ""
        ) {
            return 1;
        }
        return 0;
    }
}
