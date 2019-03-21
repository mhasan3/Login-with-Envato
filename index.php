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
    }

    function filter_the_content_in_the_main_loop($content){
        if(is_user_logged_in()){
            return $content;
        }else{
            $current_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            return '<a href="https://api.envato.com/authorization?response_type=code&client_id=rocketweb-bhsbsyiw&redirect_uri=http://dev.rocketweb/&scope=*&state='.$current_url.'">Login</a>';
        }
    }

    function envato_redirect(){
        if (isset($_GET['code']) && !empty($_GET['code'])) {
            $url = 'https://api.envato.com/token';
            $_SESSION['theUriRequestSentFrom'] = $_GET['state'];
            $fields = array(
                'grant_type' => 'authorization_code',
                'code' => $_GET['code'],
                'client_id' => 'rocketweb-bhsbsyiw',
                'client_secret' => '4LIwH1lr3PmsM95y1NVjCBAUWwvoQupv'
            );

            //url-ify the data for the POST
            foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
            rtrim($fields_string, '&');

            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

            //execute post
            $result = curl_exec($ch);

//            echo '<pre>';
//            print_r($result);

            //close connection
            curl_close($ch);
            $data = json_decode($result, true);
            $data['access_token'];
            if(!isset($_COOKIE['user_access_token']) && empty($_COOKIE['user_access_token'])){
                setcookie( 'user_access_token', $data['access_token'], strtotime( '+30 days' ), '/' );
            }else{

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

                $user_email = $user_details['email'];
                $user = get_user_by( 'email', $user_email );
//                print_r($user);
                if($user_email && !$user){
                    $user_id = username_exists( $user->user_login );
                    if ( !$user_id and email_exists($user_email) == false ) {
                        $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                        $user_id = wp_create_user( $user_email, $random_password, $user_email );
                        wp_set_auth_cookie($user_id);
                        echo '<script>window.location.reload();</script>';
                    }
                }else{
                    wp_set_auth_cookie($user->ID);
                    $current_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    echo '<script>window.location="http://dev.rocketweb/hello-world/"</script>';
                }

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
                $user_details1 = json_decode($resp, true);
//                echo '<pre>';
//                print_r($user_details1);
            }

        }
    }




}

$en = new Envato_API();