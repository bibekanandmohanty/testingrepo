<?php
/**
 * 
 * @category   Print Profile Decoration Setting Relations
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\ProductDecorations\Models;

class PrintProfileDecorationSettingRel extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'print_profile_decoration_setting_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['print_profile_id', 'decoration_setting_id'];
    public $timestamps = false;
    
    public function print_profile() {
        return $this->hasMany('App\Modules\PrintProfiles\Models\PrintProfile', 'xe_id', 'print_profile_id')->select('xe_id', 'name');
    }
}
