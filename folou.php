<?php

//definimos las variables principales
require_once('helper.php');
require_once('twitteroauth.php');


//this constant represent the maximum limit of petitions that the script will do (use
//it to avoid getting banned)
define("CONST_LIMITEPETICIONES", 200);

//$retarr = follow_followers($myconsumerkey, $myconsumersecret,$access_token, $access_token_secret, $user_whose_followers_to_follow,$user,$mensajito);
//$retarr = topicFollow($myconsumerkey, $myconsumersecret,$access_token, $access_token_secret, $trendingTopic, $user, $mensajito);
//$retarr = followUser($myconsumerkey, $myconsumersecret, $access_token, $access_token_secret,'123456',$user,$mensajito);
$retarr = Unfollower($myconsumerkey, $myconsumersecret, $access_token, $access_token_secret, $user, true);


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
    $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, 'friends/ids', $check_valores, $check_valor_parametros);
    
    $available_total = 0;
    foreach ($responseCheck->{'ids'} as $temp_user) {
    echo $temp_user;
        $resultado = followUser($consumer_key, $consumer_secret, $access_token, $access_token_secret, $temp_user, $your_screen_name, $mensaje);
        if ($resultado) {
            $available_total++;
        }
        
        if ($available_total > 150)
            break;
    }
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
    $responseCheck = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false,'friends/ids', $check_valores, $check_valor_parametros);
    
   
    //Repetimos para obtener los followers
    $responseCheck2 = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, 'followers/ids', $check_valores, $check_valor_parametros);
    
    //ordenamos a los followers para poder hacer la busqueda
    $idsFollowers = (array)$responseCheck2->{'ids'};
    sort($idsFollowers);
    $limitePeticiones = 0;
     
    //buscamos que cada uno de nuestros amigos sea un follower de lo contrario lo dejamos de seguir
    foreach ($responseCheck->{'ids'} as $amigoActual) {    
        if ($limitePeticiones < CONST_LIMITEPETICIONES) {
            $check_valores2[0]          = 'user_id';
            $check_valor_parametros2[0] = $amigoActual;
                
            if ($only_nonfollowers) {
                if (FALSE == binarySearch($idsFollowers, $amigoActual)) {
                    print("Dejando de seguir al amigo: id ".$amigoActual."--peticion: ". $limitePeticiones . " de " . CONST_LIMITEPETICIONES . "<br />");
                    $responseCheck              = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, 'friendships/destroy', $check_valores2, $check_valor_parametros2);
                    $limitePeticiones++;
                }
                
            } else {          
                $responseCheck = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, 'friendships/destroy', $check_valores2, $check_valor_parametros2);
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
    $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false,
        'search/tweets', $check_valores, $check_valor_parametros);
    if(sizeof($responseCheck->{'statuses'}) > 0)
    {
        $id_especifico = $responseCheck->{'statuses'}[0]->{'user'}->{'id'};
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
    $responseCheck             = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, 'friendships/show', $check_valores, $check_valor_parametros);
    $status = $responseCheck->{'relationship'}->{'source'}->{'following'};

    if($status != 1){
        
        //Obtiene mi ultimo tweet
        $check_valores[0]          = 'screen_name';
        $check_valor_parametros[0] = $user_name;
        $check_valores[1]          = 'count';
        $check_valor_parametros[1] =1;
        $check_valor[2] = 'exclude_replies';
        $check_valor_parametros[2] = 'false';
        $lastTweetResponseCheck = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, false, 'statuses/user_timeline', $check_valores, $check_valor_parametros);
        
        // Obtenemos el id del tweet.
        $tweetId=$lastTweetResponseCheck[0]->{'id_str'};

        //Borra el tweet
        unset($check_valores[0]);
        unset($check_valor_parametros[0]);
        unset($check_valores[1]);
        unset($check_valor_parametros[1]);
        $deleteResponseCheck = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, 'statuses/destroy/'.$tweetId, $check_valores, $check_valor_parametros); 


        //Crea el tweet hacia el usuario
       $check_valores[0]          = 'status';
       $check_valor_parametros[0] = "@".$responseCheck->{'relationship'}->{'target'}->{'screen_name'}.$mensaje;
       unset($check_valores[1]);
       unset($check_valor_parametros[1]);
       $newTweetCheck = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, 'statuses/update', $check_valores, $check_valor_parametros); 

       //Sigue al usuario
       $check_valores[0]          = 'user_id';
       $check_valor_parametros[0] =$id_whom_follow;
       unset($check_valores[1]);
       unset($check_valor_parametros[1]);
       $followResponseCheck = function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, true, 'friendships/create', $check_valores, $check_valor_parametros); 
       echo "Siguiendo a ".$responseCheck->{'relationship'}->{'target'}->{'screen_name'}."<br />";
      //TODO: Verificar que la respuesta sea 200

    }else{
        print ("Ya estas siguiendo al usuario: ".$responseCheck->{'relationship'}->{'target'}->{'screen_name'}."con id ".$responseCheck->{'relationship'}->{'source'}->{'id'});
    }
}


/*
 *
 * Hace la peticion post o get al servidor.
 *
 *
 */
function function_post($consumer_key, $consumer_secret, $access_token, $access_token_secret, $usePost, $url, $valores, $valor_parametro)
{
    $connection = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
    for ($i = 0; $i < count($valores); $i++) {
        $params[$valores[$i]] = $valor_parametro[$i];
    }
    
    //POST or GET the request
    
    if ($usePost) {
    if(!isset($params) || null == $params)
    {
        $params = array();
    }
        $response = $connection->post($url, $params);
    } else {
        $response = $connection->get($url, $params);
    }
     
    return $response;
}

?>
