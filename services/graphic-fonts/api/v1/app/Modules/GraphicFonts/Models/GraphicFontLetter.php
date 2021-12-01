<?php
/**
 * Graphic Fonts Model
 *
 * PHP version 5.6
 *
 * @category  Graphic_Fonts
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\GraphicFonts\Models;

/**
 * Graphic Fonts
 *
 * @category Graphic_Fonts
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class GraphicFontLetter extends \Illuminate\Database\Eloquent\Model
{

    protected $table = 'graphic_font_letters';
    protected $primaryKey = 'xe_id';
    protected $fillable = ['name', 'graphic_font_id', 'file_name', 'font_type'];
    public $timestamps = false;
    protected $appends = ['raw_file_name', 'is_trash'];

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
            return path('read', 'graphicfont') . $this->attributes['file_name'];
        }
        return "";
    }
    /**
     * Get Raw File Name
     *
     * @author satyabratap@riaxe.com
     * @date   5 Oct 2019
     * @return string raw file name
     */
    public function getRawFileNameAttribute()
    {
        return $this->attributes['file_name'];
    }
    
    /**
     * Append Thrash key in response
     *
     * @author satyabratap@riaxe.com
     * @date   13 Feb 2020
     * @return int 0
     */
    public function getIsTrashAttribute()
    {
        return 0;
    }
}