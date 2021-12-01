<?php
/**
 * Template Tag Relation Model
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
 * Template Tag Relation Model Class
 *
 * @category Template
 * @package  Template
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class TemplateTagRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'template_tag_rel';
    protected $fillable = ['template_id', 'tag_id'];
    public $timestamps = false;

    /**
     * Reverse One-to-one relationship between Template and Template-Tag
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function tag()
    {
        return $this->hasOne('App\Components\Models\Tag', 'xe_id', 'tag_id')
            ->select('xe_id', 'name');
    }
}
