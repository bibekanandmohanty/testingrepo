<?php
/**
 * This Model used for Customer Internal Note Files
 *
 * PHP version 5.6
 *
 * @category  Customer_Internal_Note_Files
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */
namespace App\Modules\Customers\Models;
/**
 * Customer Internal Notes Files
 *
 * @category Customer_Internal_Note_Files
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class CustomerInternalNoteFiles extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'customer_internal_note_files';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'note_id','file'
    ];
    public $timestamps = false;
    protected $appends = ['thumbnail', 'file_name'];

    /**
     * Regenerate File Full URL for front-end
     *
     * @author debashrib@riaxe.com
     * @date   03 Oct 2019
     * @return file relation object
     */
    public function getFileNameAttribute()
    {
        if (isset($this->attributes['file']) 
            && $this->attributes['file'] != ""
        ) {
            return path('read', 'customer') .'internal-note/'. $this->attributes['file'];
        }
        return null;
    }
    /**
     * Regenerate Thumb File Full URL for front-end
     *
     * @author debashrib@riaxe.com
     * @date   03 Oct 2019
     * @return file relation object
     */
    public function getThumbnailAttribute()
    {
        if (isset($this->attributes['file'])
            && $this->attributes['file'] != ""
        ) {
            $pathInfo = pathinfo($this->attributes['file']);
            $fileExt = $pathInfo['extension'];
            $displayLogo = strtolower($fileExt) . '-logo.png';
            if ($fileExt == 'zip' || $fileExt == 'pdf' || $fileExt == 'PDF' || $fileExt == 'ZIP' || $fileExt == 'txt' || $fileExt == 'TXT' || $fileExt == 'doc' || $fileExt == 'DOC') {
                return path('read', 'common') . $displayLogo;
            } else if ($fileExt == 'svg' || $fileExt == 'SVG') {
                return path('read', 'customer') .'internal-note/'
                . $this->attributes['file'];
            }
            return path('read', 'customer') .'internal-note/'. 'thumb_' 
                . $this->attributes['file'];
        }
        return null;
    }
}
