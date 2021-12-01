<?php
/**
 * 
 * @category   Product Image Settings Relations
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\ProductDecorations\Models;

class ProductImageSettingsRel extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'product_image_settings_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['product_setting_id', 'product_image_id'];
    public $timestamps = false;

    public function product_image()
    {
        return $this->hasOne('App\Modules\ProductImageSides\Models\ProductImage', 'xe_id', 'product_image_id');
    }
}
