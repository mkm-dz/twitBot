<?php
require 'globals.php';
require 'oauth_helper.php';
require 'utils.php';
require 'main_helper.php';


//this constant represent the maximu limit of petitions that the script will do (use
//it to avoid getting banned)
define("CONST_LIMITEPETICIONES", 1000);

//$retarr = follow_followers($myconsumerkey, $myconsumersecret,$access_token, $access_token_secret,$user_whose_followers_to_follow,$user,$mensajito);
$retarr = unfollower($myconsumerkey, $myconsumersecret, $access_token, $access_token_secret, $user, true);
//$retarr =topicFollow($myconsumerkey, $myconsumersecret,$access_token, $access_token_secret,'dichosmexicanos',$user,$mensajito);
//$retarr=followUser($consumer_key, $consumer_secret, $access_token, $access_token_secret,'123456',$user,$mensajito);

exit(0);

/*
 *Sigue a todos los usuarios que siga el usuario enviado como parametro
 *
 *@param $consumer_key Llave que identifica la aplicacion dentro de twitter
 *@param $consumer_secret password de la $consumer_key
 *@param $access_token Representa al usuario, es el token que genero al dar permisos a la aplicacion
 *@param $access_token_secret Representa el password del access_token
 */
function follow_followers($consumer_key, $consumer_secret, $access_token, $access_token_secret, $user_whose_followers_to_follow, $your_screen_name, $mensaje)
{
    $check_valores[0]          = 'cursor';
    $check_valor_parametros[0] = '-1';
    $check_valores[1]          = 'screen_name';
    $check_valor_parametros[1] = $user_whose_followers_to_follow;
    $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'http://api.twitter.com/1/followers/ids.json', $check_valores, $check_valor_parametros);
    list($info, $header, $body) = $responseCheck;
    $ids = json_decode($body, true);
    
    $available_total = 0;
    foreach ($ids['ids'] as $temp_user) {
        $resultado = followUser($consumer_key, $consumer_secret, $access_token, $access_token_secret, $temp_user, $your_screen_name, $mensaje);
        if ($resultado) {
            $available_total++;
        }
        
        if ($available_total > 150)
            break;
    }
}

function function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, $usePost, $passOAuthInHeader, $url, $valores, $valor_parametro, $useSSL)
{
    $retarr   = array();
    $puerto   = 80;
    // return value
    $response = array();
    for ($i = 0; $i < count($valores); $i++) {
        $params[$valores[$i]] = $valor_parametro[$i];
    }
    
    $params['oauth_version']          = '1.0';
    $params['oauth_nonce']            = mt_rand();
    $params['oauth_timestamp']        = time();
    $params['oauth_consumer_key']     = $consumer_key;
    $params['oauth_token']            = $access_token;
    // compute hmac-sha1 signature and add it to the params list
    $params['oauth_signature_method'] = 'HMAC-SHA1';
    $params['oauth_signature']        = oauth_compute_hmac_sig($usePost ? 'POST' : 'GET', $url, $params, $consumer_secret, $access_token_secret);
    // Pass OAuth credentials in a separate header or in the query string
    
    if ($useSSL) {
        $puerto = 443;
    }
    
    if ($passOAuthInHeader) {
        $query_parameter_string = oauth_http_build_query($params, true);
        $header                 = build_oauth_header($params, "Twitter API");
        $headers[]              = $header;
    } else {
        $query_parameter_string = oauth_http_build_query($params);
    }
    
    // POST or GET the request
    
    if ($usePost) {
        $request_url = $url;
        logit("tweet:INFO:request_url:$request_url");
        logit("tweet:INFO:post_body:$query_parameter_string");
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $response  = do_post($request_url, $query_parameter_string, $puerto, $headers);
    } else {
        $request_url = $url . ($query_parameter_string ? ('?' . $query_parameter_string) : '');
        logit("tweet:INFO:request_url:$request_url");
        $response = do_get($request_url, $puerto, $headers);
    }
    
    // extract successful response
    
    if (!empty($response)) {
        list($info, $header, $body) = $response;
        
        if ($body) {
            logit("tweet:INFO:response:");
            //	print(json_pretty_print($body));
        }
        
        $retarr = $response;
    }
    
    return $retarr;
}


/*
 *Hace unfollow a un grupo de usuarios
 *
 *@param $consumer_key Llave que identifica la aplicacion dentro de twitter
 *@param $consumer_secret password de la $consumer_key
 *@param $access_token Representa al usuario, es el token que genero al dar permisos a la aplicacion
 *@param $access_token_secret Representa el password del access_token
 *@param $user_whose Nombre de usuario que dejara de seguir a sus usuarios
 *@param $only_nonfollowers Si es verdadero, se dejara de seguir solo a quien no te siga, de lo contrario se dejara de seguir a todos
 */
function Unfollower($consumer_key, $consumer_secret, $access_token, $access_token_secret, $user_name, $only_nonfollowers)
{
    //arreglo de parametros
    $check_valores[0] = 'cursor';
    $check_valores[1] = 'screen_name';
    
    //arreglo de valores de los parametros del arreglo anterior
    $check_valor_parametros[0] = '-1';
    $check_valor_parametros[1] = $user_name;
    
    //obtenemos a las personas que seguimos
    
    $responseCheck = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'https://api.twitter.com/1.1/friends/ids.json', $check_valores, $check_valor_parametros, true);
    
    //La respuesta consta de 3 partes, con la funcion list metemos esas partes en diferentes variables
    list($info, $header, $body) = $responseCheck;
    
    //decodificamos la parte de body, al poner el segundo argumento como true decimos que lo convierta a un arreglo
    //el cual guardamos y llamamos ids.
    $ids = json_decode($body, true);
    
    
    //Repetimos para obtener los followers
    $responseCheck = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'https://api.twitter.com/1.1/followers/ids.json', $check_valores, $check_valor_parametros, true);
    list($info, $header, $body) = $responseCheck;
    $idsFollowers = json_decode($body, true);
    
    //ordenamos a los followers para poder hacer la busqueda
    sort($idsFollowers['ids']);
    $limitePeticiones = 0;
    
    
    //buscamos que cada uno de nuestros sea un follower de lo contrario lo dejamos de seguir
    foreach ($ids['ids'] as $amigoActual) {
        
        if ($limitePeticiones < CONST_LIMITEPETICIONES) {
            $check_valores2[0]          = 'user_id';
            $check_valor_parametros2[0] = $amigoActual;
            
            if ($only_nonfollowers) {
                if (FALSE == binarySearch($idsFollowers['ids'], $amigoActual)) {
                    print("Dejando de seguir al amigo " . $limitePeticiones . " de " . CONST_LIMITEPETICIONES);
                    $responseCheck              = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, true, 'https://api.twitter.com/1.1/friendships/destroy.json', $check_valores2, $check_valor_parametros2,true);
                    $limitePeticiones++;
                }
                
            } else {
                $responseCheck              = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, true, 'https://api.twitter.com/1.1/friendships/destroy.json', $check_valores2, $check_valor_parametros2,true);
                $limitePeticiones++;
            }

        } else {
            break;
        }
        
    }
    
    
}

function topicFollow($consumer_key, $consumer_secret, $access_token, $access_token_secret, $topic, $your_screen_name, $mensaje)
{
    $check_valores[0]          = 'q';
    $check_valor_parametros[0] = $topic;
    $check_valores[1]          = 'rpp';
    $check_valor_parametros[1] = '15';
    $check_valores[2]          = 'result_type';
    $check_valor_parametros[2] = 'recent';
    $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'http://search.twitter.com/search.json', $check_valores, $check_valor_parametros);
    list($info, $header, $body) = $responseCheck;
    $ids           = json_decode($body, true);
    $id_especifico = $ids['results'][0]['from_user_id'];
    
    followUser($consumer_key, $consumer_secret, $access_token, $access_token_secret, $id_especifico, $your_screen_name, $mensaje);
}

function followUser($consumer_key, $consumer_secret, $access_token, $access_token_secret, $user_whom_follow, $your_screen_name, $mensaje)
{
    $check_valores[1]          = 'user_id_b';
    $check_valor_parametros[1] = $user_whom_follow;
    $check_valores[0]          = 'screen_name_a';
    $check_valor_parametros[0] = $your_screen_name;
    $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'http://api.twitter.com/1/friendships/exists.json', $check_valores, $check_valor_parametros);
    
    if ($responseCheck[2] == 'false') {
        $valores[0]         = 'user_id';
        $valor_parametro[0] = $user_whom_follow;
        $responseT          = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, true, 'http://api.twitter.com/1/friendships/create.json', $valores, $valor_parametro);
        echo "Agregando a " . $user_whom_follow . "<br />";
        $valores2[0]         = 'user_id';
        $valor_parametro2[0] = $user_whom_follow;
        $postMention         = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'http://api.twitter.com/1/users/show.json', $valores2, $valor_parametro2);
        list($info, $header, $body) = $postMention;
        $screen_name = json_decode($body, true);
        
        $valores3[0]         = 'screen_name';
        $valor_parametro3[0] = $your_screen_name;
        $tweetLine           = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'http://api.twitter.com/1/statuses/user_timeline.json', $valores3, $valor_parametro3);
        list($info, $header, $body) = $tweetLine;
        $tweet_id = json_decode($body, true);
        
        echo $tweet_id[0]['text'];
        
        $valores4[0]         = 'id';
        $valor_parametro4[0] = $tweet_id[0]['id_str'];
        $tweetDelete         = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, true, 'http://api.twitter.com/1/statuses/destroy/' . $tweet_id[0]['id_str'] . '.json', $valores4, $valor_parametro4);
        
        
        $valores2[0]         = 'status';
        $valor_parametro2[0] = '@' . $screen_name['screen_name'] . $mensaje;
        $postMention         = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, true, 'http://api.twitter.com/1/statuses/update.json', $valores2, $valor_parametro2);
        
        
        /*******Mensajes directos
        $valores2[0]='user_id';
        $valor_parametro2[0]=$user_whom_follow;
        $valores2[1]='text';	
        $valor_parametro2[1]=$mensaje;		
        $directMessage=function_post($consumer_key,$consumer_secret, $access_token, $access_token_secret,true,true,'http://api.twitter.com/1/direct_messages/new.json',$valores2,$valor_parametro2);
        ********/
        return true;
    } else {
        echo "Ya es amigo " . $user_whom_follow . "<br />";
        return false;
    }
    
    
}

?>