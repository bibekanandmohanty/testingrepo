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

 namespace App\Modules\UserDesigns\Models;

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
class UserDesign extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'user_designs';
    protected $primaryKey = 'xe_id';
    protected $fillable = ['customer_id', 'design_id'];
    protected $appends = ['capture_image','thumbnail'];

    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the file_name before sending the response
     *
     * @author mukeshp@riaxe.com
     * @date   6th Feb 2020
     * @return file path url
     */
    public function getCaptureImageAttribute()
    {
        if (isset($this->attributes['capture_image'])
            && $this->attributes['capture_image'] != ""
        ) {
            return path('read', 'user_design_preview') . $this->attributes['capture_image'];
        }
        return "";
    }

    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the thumbnail of the file before sending the response
     *
     * @author mukeshp@riaxe.com
     * @date   7th Feb 2020
     * @return file path url
     */
    public function getThumbnailAttribute()
    {
        if (isset($this->attributes['capture_image'])
            && $this->attributes['capture_image'] != ""
        ) {
            return path('read', 'user_design_preview')
                . 'thumb_' . $this->attributes['capture_image'];
        }
        return "";
    }
}
