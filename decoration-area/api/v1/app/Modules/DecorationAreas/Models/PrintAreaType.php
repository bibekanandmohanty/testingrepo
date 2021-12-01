<?php
/**
 * Print Area Type
 *
 * PHP version 5.6
 *
 * @category  Print_Area
 * @package   Decoration_Area
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\DecorationAreas\Models;

/**
 * Print Area Type Controller
 *
 * @category Class
 * @package  Print_Area_Type
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintAreaType extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['store_id', 'name', 'file_name', 'is_custom'];
    public $timestamps = false;
    /**
     * Re-create Full url from image's raw name
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return URL for fetching File
     */
    public function getFileNameAttribute()
    {
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            return path('read', 'print_area_type') . $this->attributes['file_name'];
        }
        return "";
    }
}
