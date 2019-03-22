<?php
/**
 * PHP version 5.6+
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @author     Joseph G. <emailnotpublic@domain.tld>
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    0.0.1
 * @link       https://github.com/codehaiku/Envato-Purchase-Code-Verifier
 * @since      0.0.1
 */

// require_once __DIR__ . '/vendor/autoload.php';

// use Curl\Curl;

final class EnvatoPurchaseCodeVerifier
{
    protected $accessToken = '';

    const AUTHOR_SALES_ENDPOINT_URI = 'https://api.envato.com/v3/market/author/sale';

    /**
     * Class constructor, pass the access token.
     * @param mixed The access token <https://build.envato.com/create-token/>
     */
    public function __construct($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Supply the purchase code of the buyer as argument.
     * @param  mixed $purchaseCode The purchase code of he buyer.
     * @return mixed (Boolean) False on fail, the api (Object) $curl->response info on success.
     */
    public function verified($purchaseCode)
    {
        if (empty($purchaseCode)) {
            return false;
        }

        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => self::AUTHOR_SALES_ENDPOINT_URI.'?code='.$purchaseCode,
            CURLOPT_USERAGENT => 'Codular Sample cURL Request',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$this->accessToken
            )
        ]);
        // Send the request & save response to $resp
        $resp = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);
        $user = json_decode($resp, true);
        // echo "<pre>"; var_dump($user); exit();

        /* $curl = new Curl();

        $curl->setHeader('Authorization', 'Bearer ' . $this->accessToken);

        $curl->get(self::AUTHOR_SALES_ENDPOINT_URI, array(
            'code' => $purchaseCode
        ));

        $curl->close(); */

        if (!$user) {
            return false;
        } else {
            return $user;
        }
    }
}
