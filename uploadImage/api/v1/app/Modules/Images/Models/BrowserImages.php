<?php
/**
 * Image Upload Model
 *
 * PHP version 5.6
 *
 * @category  Browser Images
 * @package   Images
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */
namespace App\Modules\Images\Models;

use App\Components\Controllers\Component as ParentController;
/**
 * Browser Image Upload Model
 *
 * @category Browser Images
 * @package  Images
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class BrowserImages extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'browser_images';
    protected $primaryKey = 'xe_id';
    protected $fillable = ['user_id', 'browser_id', 'file_name'];
    protected $appends = ['thumbnail'];
    public $timestamps = false;
    
    /**
     * Regenerate File Full URL for front-end
     *
     * @author satyabratap@riaxe.com
     * @date   4 May 2019
     * @return string file url
     */
    public function getFileNameAttribute()
    {
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            return  path('read', 'browser_image') . $this->attributes['file_name'];
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
        $url = "";
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            if (in_array(strtoupper(pathinfo($this->attributes['file_name'], PATHINFO_EXTENSION)), ['JPEG', 'JPG', 'GIF', 'WEBP', 'PNG'])) {
                $url .= path('read', 'browser_image');
                $url .= 'thumb_' . $this->attributes['file_name'];
            } else {
                $url .= path('read', 'browser_image');
                $url .= $this->attributes['file_name'];
            }
        }

        return $url;
    }
}
