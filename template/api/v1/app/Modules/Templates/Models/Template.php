<?php
/**
 * Template Model
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
 * Template Model
 *
 * @category Template
 * @package  Template
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Template extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'templates';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;

    /**
     * Reverse One-to-one relationship between Template and Design State
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function getDesignState()
    {
        return $this->belongsTo(
            'App\Modules\Templates\Models\TemplateDesignStates', 'ref_id'
        );
    }
    /**
     * One-to-Many relationship between Template and Template-Tag-Relation
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function templateTags()
    {
        return $this->hasMany(
            'App\Modules\Templates\Models\TemplateTagRel', 'template_id'
        );
    }
    /**
     * Reverse One-to-Many relationship between Template and
     * Template-Tag-Relation
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function tags()
    {
        return $this->belongsToMany(
            'App\Modules\Templates\Models\TemplateTagRel', 
            'template_tag_rel', 'template_id', 'tag_id'
        );
    }

    /**
     * One-to-Many relationship between Template and Template-Category-Relation
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function templateCategory()
    {
        return $this->hasMany(
            'App\Modules\Templates\Models\TemplateCategoryRel', 'template_id'
        );
    }
    /**
     * Reverse One-to-Many relationship between Template and
     * Template-Category-Relation
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function categories()
    {
        return $this->belongsToMany(
            'App\Modules\Templates\Models\TemplateCategoryRel', 
            'template_category_rel', 'template_id', 'category_id'
        );
    }
    /**
     * One-to-Many relationship between Template and Print Profile
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function templatePrintProfiles()
    {
        return $this->hasMany(
            'App\Modules\Templates\Models\TemplatePrintProfileRel', 'template_id'
        );
    }
    /**
     * Color hash codes decoded always for frontend
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function getColorHashCodesAttribute()
    {
        if (isset($this->attributes['color_hash_codes']) 
            && $this->attributes['color_hash_codes'] != ""
        ) {
            return json_clean_decode($this->attributes['color_hash_codes'], true);
        }
        return [];
    }
}
