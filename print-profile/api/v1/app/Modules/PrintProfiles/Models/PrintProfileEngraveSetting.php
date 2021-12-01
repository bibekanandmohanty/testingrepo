<?php
/**
 * This Model used for Print Profile Engrave Setting
 *
 * PHP version 5.6
 *
 * @category  Print_Profile_Allowed_Format
 * @package   Print_Profile
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\PrintProfiles\Models;

/**
 * Print Profile Allowed Format Class
 *
 * @category Print_Profile_Allowed_Format
 * @package  Print_Profile
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintProfileEngraveSetting extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_profile_engrave_settings';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'print_profile_id',
        'engraved_surface_id',
        'is_engraved_surface',
        'is_auto_convert',
        'auto_convert_type',
        'is_hide_color_options',
        'is_engrave_image',
        'engrave_image_path',
        'engrave_color_code',
        'engrave_shadow',
        'engrave_opacity',
        'is_engrave_preview_image',
        'engrave_preview_image_path',
        'engrave_preview_color_code',
    ];
    protected $appends = [
        'engrave_image_path_thumbnail', 
        'engrave_preview_image_path_thumbnail'
    ];
    public $timestamps = false;
    /**
     * Getting Engrave Image Raw File Name
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function engraveImagePath()
    {
        return $this->attributes['engrave_image_path'];
    }
    /**
     * Generating of Engrave Main image
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function getEngraveImagePathAttribute()
    {
        if (isset($this->attributes['engrave_image_path']) 
            && $this->attributes['engrave_image_path'] != ""
        ) {
            return path('read', 'print_profile') 
                . $this->attributes['engrave_image_path'];
        } else {
            return "";
        }
    }
    /**
     * Generating of Engrave Thumb image
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function getEngraveImagePathThumbnailAttribute()
    {
        if (isset($this->attributes['engrave_image_path']) 
            && $this->attributes['engrave_image_path'] != ""
        ) {
            return path('read', 'print_profile') . 'thumb_' 
                . $this->attributes['engrave_image_path'];
        } else {
            return "";
        }
    }
    /**
     * Generating of Engrave preview Main image
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function getEngravePreviewImagePathAttribute()
    {
        if (isset($this->attributes['engrave_preview_image_path']) 
            && $this->attributes['engrave_preview_image_path'] != ""
        ) {
            return path('read', 'print_profile') 
                . $this->attributes['engrave_preview_image_path'];
        } else {
            return "";
        }
    }
    /**
     * Generating of Engrave preview Thumb image
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function getEngravePreviewImagePathThumbnailAttribute()
    {
        if (isset($this->attributes['engrave_preview_image_path']) 
            && $this->attributes['engrave_preview_image_path'] != ""
        ) {
            return path('read', 'print_profile') 
                . 'thumb_' . $this->attributes['engrave_preview_image_path'];
        } else {
            return "";
        }
    }
}
