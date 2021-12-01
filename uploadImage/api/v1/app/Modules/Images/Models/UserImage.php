<?php
/**
 * User Image Model
 *
 * PHP version 5.6
 *
 * @category  User_Image
 * @package   Assets
 * @author    Mukesh <mukeshp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

 namespace App\Modules\Images\Models;

use App\Components\Controllers\Component as ParentController;

/**
 * Word Cloud
 *
 * @category User_Image
 * @package  Assets
 * @author   Mukesh <mukeshp@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class UserImage extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_upload_image';
    protected $primaryKey = 'xe_id';
    protected $fillable = ['customer_id', 'file_name','original_file_name'];
    protected $appends = ['thumbnail'];
    public $timestamps = false;

    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the file_name before sending the response
     *
     * @author mukeshp@riaxe.com
     * @date   6th Feb 2020
     * @return file path url
     */
    public function getFileNameAttribute()
    {
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            return path('read', 'user') . $this->attributes['file_name'];
        }
        return "";
    }

    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the thumbnail of the file before sending the response
     *
     * @author mukeshp@riaxe.com
     * @author tanmayap@riaxe.com
     * @date   7th Feb 2020
     * @return file path url
     */
    public function getThumbnailAttribute()
    {
        if (!empty($this->attributes['file_name'])) {
            $thumbPath = path('read', 'user');
            $thumbPath .= 'thumb_' . $this->attributes['file_name'];
            if (file_exists($thumbPath)) {
                return $thumbPath;
            }
        }
        return "";
    }

    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to set created attributes
     *
     * @author mukeshp@riaxe.com
     * @date   11 Feb 2020
     * @return file path url
     */
    public function setCreatedAtAttribute() { 
        $this->attributes['created_at'] = \Carbon\Carbon::now(); 
    }
}
