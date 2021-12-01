<?php
/**
 * This Model used for Print profile's Feature Type
 *
 * PHP version 5.6
 *
 * @category  Feature_Type
 * @package   Print_Profile
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\PrintProfiles\Models;

/**
 * Feature Type Class
 *
 * @category Feature_Type
 * @package  Print_Profile
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class FeatureType extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'feature_types';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;

    /**
     * Create a relationship of Feature Type with Feature
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function feature()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\Feature', 
            'feature_type_id', 
            'xe_id'
        );
    }
}
