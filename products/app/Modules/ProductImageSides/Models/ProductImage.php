<?php
/**
 * 
 * @category   Product Image
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\ProductImageSides\Models;

class ProductImage extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'product_images';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['name'];
    public $timestamps = false;

    // Get Product Side details
    public function sides() {
        return $this->hasMany('App\Modules\ProductImageSides\Models\ProductImageSides', 'product_image_id');
    }
}
