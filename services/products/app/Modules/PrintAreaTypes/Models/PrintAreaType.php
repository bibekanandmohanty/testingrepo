<?php
/**
 *
 * This Model used for Print Area 
 * with other corresponding models
 * 
 * @category   Clipart-Category
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\PrintAreaTypes\Models;

class PrintAreaType extends \Illuminate\Database\Eloquent\Model {

    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['name', 'file_name', 'is_custom'];
    public $timestamps = false;

    public function getFileNameAttribute() {
        if(isset($this->attributes['file_name']) && $this->attributes['file_name'] != "") {
            return BASE_URL . 'uploads/print_area_type/' . $this->attributes['file_name'];
        } else {
            return "";
        }
    }
}
