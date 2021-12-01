<?php
/**
 * Language Model
 *
 * PHP version 5.6
 *
 * @category  Languages
 * @package   Settings
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Settings\Models;

/**
 * Language
 *
 * @category Language
 * @package  Settings
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Language extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'languages';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['name', 'type', 'code', 'file_name',
        'is_enable', 'is_default', 'store_id', 'flag'];
    public $timestamps = false;

    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the file_name before sending the response
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function getFileNameAttribute()
    {
        if (!empty($this->attributes['file_name'])) {
            return path('read', 'language') . $this->attributes['type'] 
                . '/' .  $this->attributes['file_name'];
        }
        return null;
    }
    
    /**
     * This is a method from Eloquent. The basic functionality of this method is
     * to modify the flag_name before sending the response
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function getFlagAttribute()
    {
        if (!empty($this->attributes['flag'])) {
            return path('read', 'language') . $this->attributes['type'] 
            . '/' . $this->attributes['flag'];
        }
        return "";
    }
}
