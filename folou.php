<?php
require 'globals.php';
require 'oauth_helper.php';
require 'utils.php';
//definimos las variables principales
require 'main_helper.php';



//this constant represent the maximum limit of petitions that the script will do (use
//it to avoid getting banned)
define("CONST_LIMITEPETICIONES", 1000);

//$retarr = follow_followers($myconsumerkey, $myconsumersecret,$access_token, $access_token_secret,$user_togetFollowersFrom,$user,$mensajito);
//$retarr = unfollower($myconsumerkey, $myconsumersecret, $access_token, $access_token_secret, $user, true);
$retarr = topicFollow($myconsumerkey, $myconsumersecret,$access_token, $access_token_secret,$trendingTopic,$user,$mensajito);
//$retarr = followUser($myconsumerkey, $myconsumersecret, $access_token, $access_token_secret,'123456',$user,$mensajito);
//$retarr = Unfollower($myconsumerkey, $myconsumersecret, $access_token, $access_token_secret, $user, true);


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
    $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'https://api.twitter.com/1.1/friends/ids.json', $check_valores, $check_valor_parametros,true);
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
 *@param $user_name Nombre de usuario que dejara de seguir a sus usuarios
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
                    print("Dejando de seguir al amigo " . $limitePeticiones . " de " . CONST_LIMITEPETICIONES . "<br />");
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

/*
 *Hace follow a un usuario que haya usado el trending topic especificado.
 *
 *@param $consumer_key Llave que identifica la aplicacion dentro de twitter
 *@param $consumer_secret password de la $consumer_key
 *@param $access_token Representa al usuario, es el token que genero al dar permisos a la aplicacion
 *@param $access_token_secret Representa el password del access_token
 *@param $topic El trending topic que se usara para determinar a que usuario seguir.
 *@param $user_name Nuestro nombre de usuario
 *@param $mensaje Representa un mensaje que se enviara al usuario antes de hacer el follow.
 */

function topicFollow($consumer_key, $consumer_secret, $access_token, $access_token_secret, $topic, $user_name, $mensaje)
{
    //establecemos la busqueda por topico y que sean los mas recientes
    $check_valores[0]          = 'q';
    $check_valor_parametros[0] = $topic;
    $check_valores[1]          = 'result_type';
    $check_valor_parametros[1] = 'recent';
    $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true,
        'https://api.twitter.com/1.1/search/tweets.json', $check_valores, $check_valor_parametros,true);
    list($info, $header, $body) = $responseCheck;
    $ids           = json_decode($body, true);
    if(sizeof($ids['statuses']) > 0)
    {
        $id_especifico = $ids['statuses'][0]['user']['id'];
        //llamamos a la funcion para seguir al usuario que obtuvimos anteriormente
        print("<table><tr><td>Trending Topic:".$topic."</td><td>");
        followUser($consumer_key, $consumer_secret, $access_token, $access_token_secret, $id_especifico, $user_name, $mensaje);
        print("</td></tr></table>");
    }
    else
    {
        print("No existe gente posteando acerca de: ".$topic);
    }
}

/*
 *Hace follow a un usuario.
 *
 *@param $consumer_key Llave que identifica la aplicacion dentro de twitter
 *@param $consumer_secret password de la $consumer_key
 *@param $access_token Representa al usuario, es el token que genero al dar permisos a la aplicacion
 *@param $access_token_secret Representa el password del access_token
 *@param $user_whom_follow El usuario al que queremos seguir.
 *@param $user_name Nuestro nombre de usuario
 *@param $mensaje Representa un mensaje que se enviara al usuario antes de hacer el follow.
 */
function followUser($consumer_key, $consumer_secret, $access_token, $access_token_secret, $id_whom_follow, $user_name, $mensaje)
{

    $check_valores[0]          = 'source_screen_name';
    $check_valor_parametros[0] =$user_name;
    $check_valores[1]          = 'target_id';
    $check_valor_parametros[1] =$id_whom_follow;
    $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'https://api.twitter.com/1.1/friendships/show.json', $check_valores, $check_valor_parametros,true);
    list($info, $header, $body) = $responseCheck;
    $datosTarget           = json_decode($body, true);
    $status = $datosTarget["relationship"]["source"]["following"];

    if($status != 1){
        
        //Obtiene el ultimo tweet
        $check_valores[0]          = 'screen_name';
        $check_valor_parametros[0] =$user_name;
        $check_valores[1]          = 'count';
        $check_valor_parametros[1] =1;
        $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, true, 'https://api.twitter.com/1.1/statuses/user_timeline.json', $check_valores, $check_valor_parametros,true);
        list($info, $header, $body) = $responseCheck;
        $tweetId           = json_decode($body, true);
        $tweetId=$tweetId[0]['id_str'];

        //Borra el tweet
        unset($check_valores[0]);
        unset($check_valor_parametros[0]);
        unset($check_valores[1]);
        unset($check_valor_parametros[1]);
        $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, true, 'https://api.twitter.com/1.1/statuses/destroy/'.$tweetId.'.json', $check_valores, $check_valor_parametros,true); 


        //Crea el tweet
       $check_valores[0]          = 'status';
       $check_valor_parametros[0] = "@".$datosTarget["relationship"]["target"]["screen_name"].$mensaje;
       unset($check_valores[1]);
       unset($check_valor_parametros[1]);
       $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, true, 'https://api.twitter.com/1.1/statuses/update.json', $check_valores, $check_valor_parametros,true); 

       //Sigue al usuario
       $check_valores[0]          = 'user_id';
       $check_valor_parametros[0] =$id_whom_follow;
       unset($check_valores[1]);
       unset($check_valor_parametros[1]);
       $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, true, 'https://api.twitter.com/1.1/friendships/create.json', $check_valores, $check_valor_parametros,true); 
       echo "Siguiendo a ".$datosTarget["relationship"]["target"]["screen_name"]."<br />";
      //TODO: Verificar que la respuesta sea 200

 

    }else{
        print ("Ya estas siguiendo al usuario: ".$datosTarget["relationship"]["target"]["screen_name"]."con id ".$datosTarget["relationship"]["source"]["id"]);
    }
}

?>
