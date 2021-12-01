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

use App\Components\Controllers\Component as ParentController;

/**
 * Graphic Fonts
 *
 * @category Graphic_Fonts
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class GraphicFont extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'name', 'price', 'store_id', 'is_letter_style', 
        'is_number_style', 'is_special_character_style'
    ];
    public $timestamps = false;
    protected $appends = ['file_name'];


    /**
     * Create relationship between Graphic Fonts and
     * Graphic-Fonts-Tag-Relationship
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tags
     */
    public function graphicFontTags()
    {
        return $this->hasMany(
            'App\Modules\GraphicFonts\Models\GraphicFontTagRelation', 
            'graphic_font_id'
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
            'App\Modules\GraphicFonts\Models\GraphicFontTagRelation', 
            'graphic_font_tag_rel', 'graphic_font_id', 'tag_id'
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
        $fileName =  $this->hasMany(
            'App\Modules\GraphicFonts\Models\GraphicFontLetter', 'graphic_font_id'
        )
            ->select('file_name')
            ->first();

        if ($fileName != "") {
            return $fileName['file_name'];
        }
        return "";
    }

    /**
     * Create relationship between Graphic Fonts and characters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tag
     */
    public function characters()
    {
        return $this->hasMany(
            'App\Modules\GraphicFonts\Models\GraphicFontLetter', 'graphic_font_id'
        );
    }

    /**
     * Create relationship between Graphic Fonts and characters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tag
     */
    public function letter_style()
    {
        return $this->hasMany(
            'App\Modules\GraphicFonts\Models\GraphicFontLetter', 'graphic_font_id'
        )
            ->select('graphic_font_id', 'xe_id', 'name', 'file_name')
            ->where('font_type', 'letter');
    }

    /**
     * Create relationship between Graphic Fonts and characters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tag
     */
    public function number_style()
    {
        return $this->hasMany(
            'App\Modules\GraphicFonts\Models\GraphicFontLetter', 'graphic_font_id'
        )
            ->select('xe_id', 'graphic_font_id', 'name', 'file_name')   
            ->where('font_type', 'number');
    }

    /**
     * Create relationship between Graphic Fonts and characters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of tag
     */
    public function special_character_style()
    {
        return $this->hasMany(
            'App\Modules\GraphicFonts\Models\GraphicFontLetter', 'graphic_font_id'
        )
            ->select('xe_id', 'graphic_font_id', 'name', 'file_name') 
            ->where('font_type', 'special_character');
    }

}
