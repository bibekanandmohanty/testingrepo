<?php
/**
 * This Model used for Print Profile Feature Relation
 *
 * PHP version 5.6
 *
 * @category  Print_Profile_Feature_Relation
 * @package   Print_Profile
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\PrintProfiles\Models;
/**
 * Print Profile Allowed Format Class
 *
 * @category Print_Profile_Feature_Relation
 * @package  Print_Profile
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintProfileFeatureRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_profile_feature_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
    /**
     * Create a relationship of Print_Profile_Feature_Relation with Feature
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function features()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\Feature', 
            'xe_id', 
            'feature_id'
        );
    }
}
