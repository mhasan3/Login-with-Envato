<?php
/**
 * Plugin Name: Login with Envato
 * Plugin URI: https://mhasan3.github.io/
 * Description: Login register using envato account
 * Version: 1.0
 * Author: mahmud
 * Author URI: https://mhasan3.github.io/
 * Text Domain: rocketweb
 * Domain Path: /languages/
 *
 */

//api key:4LIwH1lr3PmsM95y1NVjCBAUWwvoQupv

class Envato_API{
    public function __construct()
    {
        add_filter( 'the_content', [$this, 'filter_the_content_in_the_main_loop'] );
        add_action('init', [$this, 'envato_redirect']);
        add_filter( 'show_admin_bar', [$this, 'hide_admin_bar'] );
    }

    function hide_admin_bar(){ return false; }

    function filter_the_content_in_the_main_loop($content){
        if(is_user_logged_in()){
            return $content;
        }else{
            $current_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            return '<a class="set--cookie" href="https://api.envato.com/authorization?response_type=code&client_id=rocketweb-bhsbsyiw&redirect_uri=http://dev.rocketweb/&scope=*&state='.$current_url.'">Login</a>
                    <form action="" method="post">
                        <label>Purchase Code: </label>
                        <input type="text" name="purchase_code" placeholder="Type or paste the buyer\'s purchase code here"><br>
                        <input type="submit">
                    </form>
             ';
        }
    }

    function envato_redirect(){
        include 'EnvatoPurchaseCodeVerifier.php';

        $access_token = 'eAgPE7gtBInttKUdYtVdbQjUiWCXayeq';

        $purchase = new EnvatoPurchaseCodeVerifier($access_token);

        if($_POST['purchase_code']){
            $verified = $purchase->verified($_POST['purchase_code']);
            if ( $verified ) {
                $buyer = $verified['buyer'];
                $is_registered_user = get_user_by('login', $buyer);
                $user_id = $is_registered_user->ID;
                if($is_registered_user){
                    wp_set_auth_cookie($user_id);
                }else{
                    $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                    $user_id = wp_create_user( $buyer, $random_password );
                    wp_set_auth_cookie($user_id);
                }
            }
        }


        if (isset($_GET['code']) && !empty($_GET['code'])) {
            $url = 'https://api.envato.com/token';
            $fields = array(
                'grant_type' => 'authorization_code',
                'code' => $_GET['code'],
                'client_id' => 'rocketweb-bhsbsyiw',  // change it
                'client_secret' => '4LIwH1lr3PmsM95y1NVjCBAUWwvoQupv' // change it
            );

            //url-ify the data for the POST
            foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
            rtrim($fields_string, '&');

            //open connection
            $ch = curl_init();

            /**
             * gets access toke from envato
             *
             */

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($result, true);
            $data['access_token'];

            if(!isset($_COOKIE['user_access_token']) && empty($_COOKIE['user_access_token'])){
                setcookie( 'user_access_token', $data['access_token'], strtotime( '+3 days' ), '/' );
            }else{

                /**
                 * checks if user has purchased the item
                 *
                 */
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://api.envato.com/v3/market/buyer/list-purchases?include_all_item_details=false',
                    CURLOPT_USERAGENT => 'Codular Sample cURL Request',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '.$_COOKIE['user_access_token']
                    )
                ]);

                $resp = curl_exec($curl);
                // Close request to clear up some resources
                curl_close($curl);
                $user_details = json_decode($resp, true);

                $itemIdArray = array();
                $item_results= $user_details['results'];
                foreach ($item_results as $item_result) {
                    $itemIdArray[] = $item_result['item']['id'];
                }
                $product_item_id = 2833226; // change it
                $has_purchased = 0;
                if(in_array($product_item_id,$itemIdArray)){
                    $has_purchased = 1;
                }

                /**
                 * gets username
                 *
                 */

                $curl = curl_init();
                // Set some options - we are passing in a useragent too here
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://api.envato.com/v1/market/private/user/username.json',
                    CURLOPT_USERAGENT => 'Codular Sample cURL Request',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '.$_COOKIE['user_access_token']
                    )
                ]);
                // Send the request & save response to $resp
                $resp = curl_exec($curl);
                // Close request to clear up some resources
                curl_close($curl);
                $user = json_decode($resp, true);
                if(!isset($_COOKIE['logged_in_username']) && empty($_COOKIE['logged_in_username'])) {
                    setcookie('logged_in_username', $user['username'], strtotime('+3 days'), '/');
                }

                /**
                 * gets user email
                 *
                 */

                $curl = curl_init();
                // Set some options - we are passing in a useragent too here
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://api.envato.com/v1/market/private/user/email.json',
                    CURLOPT_USERAGENT => 'Codular Sample cURL Request',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '.$_COOKIE['user_access_token']
                    )
                ]);
                // Send the request & save response to $resp
                $resp = curl_exec($curl);
                // Close request to clear up some resources
                curl_close($curl);
                $user_details = json_decode($resp, true);

                /**
                 * register or login users
                 *
                 */

                $user_email = $user_details['email'];
                $user = get_user_by( 'email', $user_email );
                if($has_purchased && $user_email && !$user ){
                    $user_id = username_exists( $user->user_login );
                    if ( !$user_id and email_exists($user_email) == false ) {
                        $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                        $user_id = wp_create_user( $user['username'], $random_password, $user_email );
                        wp_set_auth_cookie($user_id);
                        if($_GET['state'])
                            echo '<script>window.location="'.$_GET['state'].'"</script>';
                    }
                }else{
                    wp_set_auth_cookie($user->ID);
                    if($_GET['state'])
                        echo '<script>window.location="'.$_GET['state'].'"</script>';
                }
            }

        }
    }
}

$en = new Envato_API();