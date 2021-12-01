<?php
/**
 * Obj Decoration Model
 *
 * PHP version 5.6
 *
 * @category  3D_Obj_Decorations
 * @package   Products
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;
/**
 * Product
 *
 * @category 3D_Obj_Decorations
 * @package  Products
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class DecorationObjects extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'decoration_objects';
    public $timestamps = false;
    protected $fillable = [
        'product_id',
        '3d_object_file',
        'uv_file'
    ];

    /**
     * Regenerate File Full URL for front-end
     *
     * @author satyabratap@riaxe.com
     * @date   17 Mar 2019
     * @return string file url
     */
    public function get3dObjectFileAttribute()
    {
        $url = "";
        if (isset($this->attributes['3d_object_file']) 
            && $this->attributes['3d_object_file'] != ""
        ) {
            $url .= path('read', '3d_object');
            $url .= $this->attributes['3d_object_file'];
        }
        return $url;
    }
    /**
     * Regenerate File Full URL for front-end
     *
     * @author satyabratap@riaxe.com
     * @date   17 Mar 2019
     * @return string file url
     */
    public function getUvFileAttribute()
    {
        $url = "";
        if (isset($this->attributes['uv_file']) 
            && $this->attributes['uv_file'] != ""
        ) {
            $url .= path('read', '3d_object');
            $url .= $this->attributes['uv_file'];
        }
        return $url;
    }
}
