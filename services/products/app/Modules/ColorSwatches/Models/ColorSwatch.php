<?php
    /**
     * 
     * @category   Color Swatch
     * @package    Eloquent
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     Another Author <>
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @package_version@1.0
     */

    namespace App\Modules\ColorSwatches\Models;

    class ColorSwatch extends \Illuminate\Database\Eloquent\Model {

        public $timestamps      = false;
        protected $primaryKey   = 'xe_id';
        protected $fillable     = ['attribute_id', 'hex_code', 'file_name'];
        protected $guarded      = ['xe_id'];
    }
