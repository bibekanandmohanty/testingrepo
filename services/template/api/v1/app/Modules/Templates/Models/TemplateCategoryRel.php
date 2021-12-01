<?php
/**
 * Template Category Relation Model
 *
 * PHP version 5.6
 *
 * @category  Template
 * @package   Template
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Templates\Models;

/**
 * Template Category Relation Model Class
 *
 * @category Template
 * @package  Template
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class TemplateCategoryRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'template_category_rel';
    protected $fillable = ['template_id', 'category_id'];
    public $timestamps = false;

    /**
     * One-to-one relationship between Template and Category
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function category()
    {
        return $this->hasOne(
            'App\Modules\Templates\Models\TemplateCategory', 'xe_id', 'category_id'
        )
            ->select('xe_id', 'name');
    }
}
