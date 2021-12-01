<?php
/**
 * 
 * @category   Product Image Sides
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\ProductImageSides\Models;

class ProductImageSides extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'product_image_sides';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
    protected $appends = ['raw_file_name'];

    // Regenerate File Full URL for front-end
    public function getFileNameAttribute() {
        if(isset($this->attributes['file_name']) && $this->attributes['file_name'] != "") {
            return BASE_URL . 'uploads/products/' . $this->attributes['file_name'];
        } else {
            return "";
        }
    }

    // Create a Temp column for storing raw file name
    public function getRawFileNameAttribute() {
        return $this->attributes['file_name'];
    }
}
