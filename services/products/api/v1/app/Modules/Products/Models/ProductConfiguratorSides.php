<?php
/**
 * Product SVG Configurator Sides
 *
 * PHP version 5.6
 *
 * @category  Product_SVG_Configurator_Sides
 * @package   Products
 * @author    Mukesh <mukeshp@riaxe.com>
 * @copyright 2020-2021 Imprintnext
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Product Configurator Image Class
 *
 * @category Product_SVG_Configurator_Sides
 * @package  Products
 * @author   Mukesh <mukeshp@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductConfiguratorSides extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'product_configurator_sides';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
    protected $appends = ['preview_file_original', 'svg_file_original'];

    /**
     * Regenerate File Full URL for front-end
     *
     * @author mukeshp@riaxe.com
     * @date   20 Feb 2020
     * @return string file url
     */
    public function getPreviewFileAttribute()
    {
        $url = "";
        if (isset($this->attributes['preview_file']) 
            && $this->attributes['preview_file'] != ""
        ) {
            $url .= path('read', 'section');
            $url .= $this->attributes['preview_file'];
        }

        return $url;
    }

    /**
     * Regenerate File Full URL for front-end
     *
     * @author mukeshp@riaxe.com
     * @date   20 Feb 2020
     * @return string file url
     */
    public function getSvgFileAttribute()
    {
        $url = "";
        if (isset($this->attributes['svg_file']) 
            && $this->attributes['svg_file'] != ""
        ) {
            $url .= path('read', 'section');
            $url .= $this->attributes['svg_file'];
        }

        return $url;
    }

    /**
     * Getting full thumb url from file_name by manipulate file_name value
     *
     * @author satyabratap@riaxe.com
     * @date   25 oct 2020
     * @return relationship object of category
     */
    public function getPreviewFileOriginalAttribute()
    {
        return $this->attributes['preview_file'];
    }

    /**
     * Getting type of the thumb_value
     *
     * @author satyabratap@riaxe.com
     * @date   25 oct 2020
     * @return relationship object of category
     */
    public function getSvgFileOriginalAttribute()
    {
        return $this->attributes['svg_file'];
    }
}
