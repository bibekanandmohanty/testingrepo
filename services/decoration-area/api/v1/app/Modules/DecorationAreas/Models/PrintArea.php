<?php
/**
 * Manage Print Area
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

use App\Modules\DecorationAreas\Models\PrintAreaType;

/**
 * Print Area Controller
 *
 * @category Class
 * @package  Print_Area
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintArea extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_areas';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'store_id',
        'name',
        'print_area_type_id',
        'file_name',
        'width',
        'height',
        'is_user_defined',
        'is_default',
        'price',
        'bleed_width',
        'bleed_height',
        'safe_height',
        'safe_width',
    ];
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
        $fileName = null;
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            $fileName = path('read', 'print_area') . $this->attributes['file_name'];
        } else {
            $printAreaTypeId = $this->attributes['print_area_type_id'];
            $printAreaTypeInit = new PrintAreaType();
            $getPrintAreaDetails = $printAreaTypeInit->where(
                'xe_id', $printAreaTypeId
            )
                ->select('file_name')
                ->first();
            if (!empty($getPrintAreaDetails->file_name) 
                && $getPrintAreaDetails->file_name != ""
            ) {
                $fileName = $getPrintAreaDetails->file_name;
            }
        }
        return $fileName;
    }

    /**
     * Create One-to-One relationship between Print Area
     * and Print Area Type
     *
     * @author mukeshp@riaxe.com
     * @date   16 Jan 2020
     * @return relationship object of category
     */
    public function print_area_type()
    {
        return $this->hasOne(
            'App\Modules\DecorationAreas\Models\PrintAreaType', 
            'xe_id', 'print_area_type_id'
        );
    }
}
