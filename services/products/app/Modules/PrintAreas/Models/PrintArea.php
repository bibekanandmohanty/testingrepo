<?php
/**
 * 
 * @category   Print Area
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\PrintAreas\Models;

class PrintArea extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'print_areas';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['name','print_area_type_id','file_name','width','height','is_user_defined','is_default','price'];
    public $timestamps = false;

    // Re-create Full url from image's raw name
    public function getFileNameAttribute() {
        if(isset($this->attributes['file_name']) && $this->attributes['file_name'] != "") {
            return BASE_URL . 'uploads/print_area/' . $this->attributes['file_name'];
        } else {
            return "";
        }
    }
}
