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

namespace App\Modules\PrintProfiles\Models;

class PrintProfile extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'print_profiles';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
}
