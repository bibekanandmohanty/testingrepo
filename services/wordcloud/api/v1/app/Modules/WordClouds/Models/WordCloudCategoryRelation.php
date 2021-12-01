<?php
/**
 * This Model used for Word Cloud & Category relationship
 *
 * PHP version 5.6
 *
 * @category  Word_Clouds
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\WordClouds\Models;

/**
 * Word Cloud Category Relation
 *
 * @category Word_Cloud_Category_Relation
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class WordCloudCategoryRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    public $timestamps = false;
    protected $table = 'word_cloud_category_rel';
    protected $fillable = ['word_cloud_id', 'category_id'];
    
    /**
     * Create a relationship of Word Cloud with Word Cloud Category Relation Model
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function category()
    {
        return $this->hasOne(
            'App\Modules\WordClouds\Models\WordCloudCategory', 'xe_id', 'category_id'
        )
            ->select('xe_id', 'name', 'is_disable', 'parent_id');
    }

}
