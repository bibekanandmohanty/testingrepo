<?php
/**
 * Convert one currency to another
 *
 * PHP version 5.6
 *
 * @category  Currency
 * @package   Store
 * @author    Chandrakanta Haransingh <chandrakanta@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Currency\Controllers;
use App\Components\Controllers\Component as ParentController;


/**
 * Currency Controller
 *
 * @category Class
 * @package  Currency
 * @author   Chandrakanta Haransingh <chandrakanta@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class CurrencyController extends ParentController
{
    /**
     * GET: Converting currency from one base to another.
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author chandrakanta@riaxe.com
     * @date   5 Oct 2019
     * @return  JSON Response
     */
    public function getPrice($request, $response, $args){
        $allPostPutVars = $request->getParsedBody();
        $jsonResponse = [
            'status' => 0,
            'message' => message('Backgrounds', 'error'),
        ];
        $success = 0;
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.ratesapi.io/api/latest?base='.$args['base'].'&symbols='.$args['symbol'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        if($response){
            return json_encode(['data'=>1,'responce'=>$response]);
        }else{
            return $jsonResponse;
        }
        

    }
   
}
