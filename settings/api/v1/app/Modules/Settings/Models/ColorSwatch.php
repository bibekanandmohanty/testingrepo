<?php
/**
 * Color Swatch Model
 *
 * PHP version 5.6
 *
 * @category  Color_Swatches
 * @package   Settings
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Settings\Models;

/**
 * Color Swatch
 *
 * @category Color_Swatch
 * @package  Settings
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class ColorSwatch extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $primaryKey = 'xe_id';
    protected $fillable = ['attribute_id', 'hex_code', 'file_name', 'color_type'];
    protected $guarded = ['xe_id'];

    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the file_name before sending the response
     *
     * @author satyabratap@riaxe.com
     * @date   6 Dec 2019
     * @return relationship object of category
     */
    public function getFileNameAttribute()
    {
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            return path('read', 'swatch') . $this->attributes['file_name'];
        }
        return "";
    }
}
