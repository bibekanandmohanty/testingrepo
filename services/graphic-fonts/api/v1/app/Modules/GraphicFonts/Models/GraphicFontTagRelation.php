<?php
/**
 * This Model used for Graphic Font's Tag and their Relationship
 *
 * PHP version 5.6
 *
 * @category  Graphic Fonts
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\GraphicFonts\Models;
 
/**
 * Graphic Font Tag Relation
 *
 * @category Graphic_Font_Tag
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class GraphicFontTagRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'graphic_font_tag_rel';
    protected $fillable = ['graphic_font_id', 'tag_id'];
    public $timestamps = false;

    /**
     * Create a relationship bridge with GraphicFont and Tags
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function tag()
    {
        return $this->hasOne('App\Components\Models\Tag', 'xe_id', 'tag_id')
            ->select('xe_id', 'name');
    }
    
}
