<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Abraham\TwitterOAuth\TwitterOAuth;
use TwitterAPIExchange;
use App\Models\TwitterUsers;
use App\Models\TwitterMessages;
use App\Models\TwitterMentions;
use Illuminate\Http\JsonResponse;


class TwitterController extends Controller
{
    
    public $connection;
    public $accesstoken;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
        if (session_id() == "") {
             session_start();  
        }
           
        
        $this->connection = new TwitterOAuth(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'),env('TWITTER_ACCESS_TOKEN'),env('TWITTER_ACCES_TOKEN_SECRET'));

        
        if(isset($_SESSION['access_token'])){
            
            $access_token=$_SESSION['access_token'];

            $this->connection = new TwitterOAuth(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'), $access_token['oauth_token'], $access_token['oauth_token_secret']);
    
        }


    }

    public function leer_mensajes(Request $request){

        //Consultar los usuarios registrados

        $users=TwitterUsers::all();
        foreach ($users as $key => $user) {

            $access_token=(array) json_decode($user->access_token);
            $this->connection = new TwitterOAuth(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'), $access_token['oauth_token'], $access_token['oauth_token_secret']);

            $mensajes=$this->getMensajes();
            var_dump($mensajes);

        }

        echo "Ok Url";
        die();


    }

    public function leer_mensiones(Request $request){
       
        $users=TwitterUsers::all();
        $mentions=array();
        foreach ($users as $key => $user) {
            $id=$user->id_str;
            $this->connection->setBearer(env('TWITTER_BEARER_TOKEN'));
            $this->connection->setApiVersion(2);
            $listmentions = $this->connection->get("users/$id/mentions", ['max_results'=>10]);
            if(isset($listmentions->errors)){

            }else{

                $mentions=$listmentions->data;
                foreach ($mentions as $key => $mention) {
                    $existe=TwitterMentions::where('id_str_mention',$mention->id)->get()->first();
                    if($existe){

                    }else{
                        $new_mention=new TwitterMentions();
                        $new_mention->id_str_user=$id;
                        $new_mention->id_str_mention=$mention->id;
                        $new_mention->mention=$mention->text;
                        $new_mention->save();
                    }
                }

            }


            var_dump($mentions);
            die();
        }

    }

    public function oauth_twitter_callback(Request $request){

      $oauth_token=false;
      $oauth_verifier=false;

      if($request->has('oauth_token')){
        $oauth_token=$request->oauth_token;
      }
      if($request->has('oauth_verifier')){
        $oauth_verifier=$request->oauth_verifier;
      }

      if($oauth_token && $oauth_verifier){

        $request_token = [];
        $request_token['oauth_token'] = $_SESSION['oauth_token'];
        $request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];
        
        
        $connection = new TwitterOAuth(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'), $request_token['oauth_token'], $request_token['oauth_token_secret']);

        
        $access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $oauth_verifier));
    
    
        
        $_SESSION['access_token'] = $access_token;
    
        
        return redirect()->to('menu');
    

      }


    }

    public function login(){

        if (!isset($_SESSION['access_token'])) {


        $url_callback=request()->getSchemeAndHttpHost().'/twitter/callback';

        $request_token = $this->connection->oauth('oauth/request_token', array('oauth_callback' => $url_callback));

      
        $_SESSION['oauth_token'] = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
        
        $url = $this->connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
        

        return view('login')->with(['url'=>$url]);

        }
        else{
            
            return redirect()->to('/menu');


        }
        
    }

    public function menu(Request $request){
        
        return view('menu');

    }

    public function perfil(Request $request){


        $user = $this->connection->get("account/verify_credentials", ['include_email' => 'true']);
        
        if(isset($user->errors)){
            if($user->errors[0]->code==89){
                unset($_SESSION['access_token']);
                return redirect()->to('/login');

            }
        }


        //Buscamos si existe el usuario y guardamos los tokens que tengamos en sesion de ese usuario
        $twiiter_bd=TwitterUsers::where('id_str','=',$user->id_str)->get()->first();
        if($twiiter_bd){
            
            if(isset($_SESSION['access_token'])){

                $twiiter_bd->access_token=json_encode($_SESSION['access_token']);
                $twiiter_bd->updated_at=new \DateTime(date('Y-m-d H:i:s'));
                $twiiter_bd->save();
            }
          

        }else{
            $twiiter_bd=new TwitterUsers();
            $twiiter_bd->id_str=$user->id_str;
            $twiiter_bd->name=$user->name;
            $twiiter_bd->screen_name=$user->screen_name;
            $twiiter_bd->profile_image_url_https=$user->profile_image_url_https;
            $twiiter_bd->access_token=json_encode($_SESSION['access_token']);
            $twiiter_bd->save();
            
        }


        return view('perfil')->with(['user'=>$twiiter_bd]);

    }


    public function get_mensajes_directos(Request $request){

        $id=$request->get('user_id',0); 
        $is_new=$request->get('is_new',1); 

        if($id>0){

        $twiiter_bd=TwitterUsers::where('id_str','=',$id)->get()->first();
       
        if($twiiter_bd){
            
            $access_token=(array) json_decode($twiiter_bd->access_token);
            $this->connection = new TwitterOAuth(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'), $access_token['oauth_token'], $access_token['oauth_token_secret']);
        
            $mensajes=$this->getMensajes();

            $mensajesDb=TwitterMessages::where('is_new','=',$is_new)->orderBy('created_timestamp','desc')->get();

            $response=['titleResponse'=>'Ok','textResponse'=>'Mensajes consultados exitosamente','data'=>$mensajesDb,'errors'=>[]];

            return new JsonResponse($response,200);

          
        }else{
             $response=array('titleResponse'=>'Error','textResponse'=>'Usuario no existe', 'errors'=>array('errorCode'=>02,'errorMessage'=>'El id de Usuario no existe en el sistema'));

             return new JsonResponse($response,401);

        }
     }else{
        $response=array('errors'=>array('errorCode'=>01,'errorMessage'=>'El id de Usuario es requerido'));

        return new Response($response,401);
     }


    }

    public function delete_user(Request $request){

        $id=$request->get('user_id',0); 


    }

    public function recent_messages(Request $request){
     
     $id=$request->get('user_id',0); 

     if($id>0){

        $twiiter_bd=TwitterUsers::where('id_str','=',$id)->get()->first();
        if($twiiter_bd){
            
            $access_token=(array) json_decode($twiiter_bd->access_token);
            $this->connection = new TwitterOAuth(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'), $access_token['oauth_token'], $access_token['oauth_token_secret']);

            //$this->connection->setApiVersion(2);

            $params=$_GET;// All parameters
            unset($params['id']);
            $recent = $this->connection->get("statuses/user_timeline",$params);

            var_dump($recent);
            die();

        }else{

        }
     }else{
        $response=array('errorCode'=>01,'errorMessage'=>'El id de Usuario es requerido');
        return new Response($response,401);
     }


    }

    private function getMensajes(){
        

        $user = $this->connection->get("account/verify_credentials", ['include_email' => 'true']);
        
        $apimessages = $this->connection->get("direct_messages/events/list", []);
        
        if(isset($user->errors)){
            if($user->errors[0]->code==89){
                unset($_SESSION['access_token']);
                return redirect()->to('/login');

            }
        }

        if(isset($apimessages->errors)){

            return array();
        }

        $mensajes=$apimessages->events;

        if(is_array($mensajes) && count($mensajes)>0){
            foreach ($mensajes as $key => $mensaje) {
                //Buscamos que no exista el mensaje
                $existe=TwitterMessages::where('id_str_user',$user->id_str)->where('id_str_message',$mensaje->id)->get()->first();


                if($existe && empty($existe->message)){

                    $apimessage = $this->connection->get("direct_messages/events/show", ['id'=>$existe->id_str_message]);

                    $message_text=($apimessage->event->message_create->message_data->text);
                    $existe->message=$message_text;
                    $existe->save();

                }
                else{
                    
                    if($existe){
                         $existe->is_new=0;
                         $existe->save();

                    }else{

                    $msg=new TwitterMessages();
                    $msg->id_str_user=$user->id_str;
                    $msg->id_str_message=$mensaje->id;
                    $msg->type=$mensaje->type;
                    $msg->created_timestamp=$mensaje->created_timestamp;
                    $msg->is_new=1;
                    $msg->save();


                     $apimessage = $this->connection->get("direct_messages/events/show", ['id'=>$msg->id_str_message]);
                     
                    $message_text=($apimessage->event->message_create->message_data->text);
                    $msg->message=$message_text;
                    $msg->save();

                    }
                   

                }
            }
        }
       
       return $mensajes;
    }
}
