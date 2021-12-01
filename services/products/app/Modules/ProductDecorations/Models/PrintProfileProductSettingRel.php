<?php
/**
 *
 * @category   Print Profile Product Setting Relations
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\ProductDecorations\Models;

class PrintProfileProductSettingRel extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'print_profile_product_setting_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['print_profile_id', 'product_setting_id'];
    public $timestamps = false;
    
    // Get profile table details
    public function profile() {
        return $this->hasOne('App\Modules\PrintProfiles\Models\PrintProfile', 'xe_id', 'print_profile_id');
    }
}
