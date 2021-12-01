<?php
/**
 * Order Log Details Model
 *
 * PHP version 5.6
 *
 * @category  Orders_Log
 * @package   Orders
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Orders\Models;

/**
 * Order Log Files Controller
 *
 * @category Orders_Log
 * @package  Orders
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class OrderLogFiles extends \Illuminate\Database\Eloquent\Model
{

    protected $table = 'order_log_files';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
    protected $appends = ['thumbnail'];
    protected $showPredefinedLogo = ['zip', 'pdf'];

    /**
     * Regenerate File Full URL for front-end
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return file relation object
     */
    public function getFileNameAttribute()
    {
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            return path('read', 'order_log') . $this->attributes['file_name'];
        }
        return null;
    }
    /**
     * Regenerate Thumb File Full URL for front-end
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return file relation object
     */
    public function getThumbnailAttribute()
    {
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            $pathInfo = pathinfo($this->attributes['file_name']);
            $fileExt = strtolower($pathInfo['extension']);
            $displayLogo = strtolower($fileExt) . '-logo.png';
            if ($fileExt == "zip" || $fileExt == "pdf") {
                return path('read', 'common') . $displayLogo;
            } 
            if ($fileExt == "svg") {
                return path('read', 'order_log') . $this->attributes['file_name'];
            } 
            return path('read', 'order_log') . 'thumb_' 
                . $this->attributes['file_name'];
            
        }
        return null;
    }
}
