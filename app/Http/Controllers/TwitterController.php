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
            
            return redirect()->to('/profile');


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
    
        
        return redirect()->to('/profile');
    

      }


    }


    public function menu(Request $request){
        
        $user = $this->connection->get("account/verify_credentials", ['include_email' => 'true']);
     
        return view('menu')->with(['user'=>$user]);

    }

    public function get_users(Request $request){

        $users=TwitterUsers::all();
        $response=array('titleResponse'=>'Ok','textResponse'=>'Usuarios consultados exitosamente', 'errors'=>array(),'data'=>$users);

        return new JsonResponse($response,200);

    }

    public function profile(Request $request){


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

     public function read_messages(Request $request){

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

    public function post_direct_message_create(Request $request){

        $id=$request->get('user_id',0); 
        $recipient_id=$request->get('recipient_id','');
        $text=urlencode($request->get('text',"")); 

        if($recipient_id==""){
            $response=array('titleResponse'=>'Error','textResponse'=>'recipiente_id error', 'errors'=>array('errorCode'=>01,'errorMessage'=>'El campo recipiente_id es requerido'));
        }
        if($text==""){
            $response=array('titleResponse'=>'Error','textResponse'=>'text error', 'errors'=>array('errorCode'=>01,'errorMessage'=>'El campo text es requerido'));
        }

        if($id>0 && $recipient_id!=""){

        $twiiter_bd=TwitterUsers::where('id_str','=',$id)->get()->first();
       
        if($twiiter_bd){
            
            $access_token=(array) json_decode($twiiter_bd->access_token);
            $this->connection = new TwitterOAuth(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'), $access_token['oauth_token'], $access_token['oauth_token_secret']);
        
            
            $path='direct_messages/events/new';
            $send_data=array (
                                  'event' => 
                                  array (
                                    'type' => 'message_create',
                                    'message_create' => 
                                    array (
                                      'target' => 
                                      array (
                                        'recipient_id' =>$recipient_id,
                                        'sender_id'=>$twiiter_bd->id_str,
                                      ),
                                      'message_data' => 
                                      array (
                                        'text' =>$text,
                                      ),
                                    ),
                                  ),
                                );
           
           $message=$this->connection->post($path,$send_data,true);

            $response=['titleResponse'=>'Ok','textResponse'=>'Mensaje creado exitosamente','data'=>$message,'errors'=>[]];

            return new JsonResponse($response,200);

          
        }else{
             $response=array('titleResponse'=>'Error','textResponse'=>'usuario no existe', 'errors'=>array('errorCode'=>02,'errorMessage'=>'El id de usuario no existe en el sistema'));

             return new JsonResponse($response,401);

        }
     }else{

        if($recipient_id!=""){

            $response=array('errors'=>array('errorCode'=>01,'errorMessage'=>'El id de usuario es requerido'));
        }
       
       
     }

        return new Response($response,401);

    }


    public function get_direct_messages(Request $request){

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
             $response=array('titleResponse'=>'Error','textResponse'=>'usuario no existe', 'errors'=>array('errorCode'=>02,'errorMessage'=>'El id de usuario no existe en el sistema'));

             return new JsonResponse($response,401);

        }
     }else{
        $response=array('errors'=>array('errorCode'=>01,'errorMessage'=>'El id de usuario es requerido'));

        return new Response($response,401);
     }


    }

    public function read_mentions(Request $request){
       
        $users=TwitterUsers::all();
        $mentions=array();
        foreach ($users as $key => $user) {
           
            var_dump($mentions);
            die();
        }

    }

    public function get_mentions(Request $request){

        $id=$request->get('user_id',0); 
        $is_new=$request->get('is_new',1); 

        if($id>0){

        $twiiter_bd=TwitterUsers::where('id_str','=',$id)->get()->first();
       
        if($twiiter_bd){
            
            $id=$twiiter_bd->id_str;
            $this->connection->setBearer(env('TWITTER_BEARER_TOKEN'));
            $this->connection->setApiVersion(2);

            $query=array('expansions'=>'author_id',
                        'tweet.fields'=>'conversation_id,lang',
                        'user.fields'=>'created_at,entities',
                        'max_results'=>100
            );



            $listmentions = $this->connection->get("users/$id/mentions", $query);
            if(isset($listmentions->errors)){

            }else{

                $mentions=$listmentions->data;
                foreach ($mentions as $key => $mention) {
                    $existe=TwitterMentions::where('id_str_mention',$mention->id)->get()->first();
                    if($existe){
                        $existe->is_new=0;
                        $existe->message_json=json_encode($mention);
                        $existe->message=$mention->text;
                        $existe->save();
                    }else{
                        $new_mention=new TwitterMentions();
                        $new_mention->id_str_user=$id;
                        $new_mention->id_str_mention=$mention->id;
                        $new_mention->message=$mention->text;
                        $new_mention->is_new=1;
                        $new_mention->message_json=json_encode($mention);
                        $new_mention->save();
                    }
                }

            }

            $mensajesDb=TwitterMentions::where('is_new','=',$is_new)->orderBy('id','desc')->get();

            $response=['titleResponse'=>'Ok','textResponse'=>'Mensiones consultadas exitosamente','data'=>$mensajesDb,'errors'=>[]];

            return new JsonResponse($response,200);

          
        }else{
             $response=array('titleResponse'=>'Error','textResponse'=>'usuario no existe', 'errors'=>array('errorCode'=>02,'errorMessage'=>'El id de usuario no existe en el sistema'));

             return new JsonResponse($response,401);

        }
     }else{
        $response=array('errors'=>array('errorCode'=>01,'errorMessage'=>'El id de usuario es requerido'));

        return new Response($response,401);
     }



    }


    public function mention_response(Request $request){

        $id=$request->get('user_id',0); 
        $conversation_id=$request->get('conversation_id','');
        $text=urlencode($request->get('text',"")); 

        if($conversation_id==""){
            $response=array('titleResponse'=>'Error','textResponse'=>'conversation_id error', 'errors'=>array('errorCode'=>01,'errorMessage'=>'El campo conversation_id es requerido'));
        }
        if($text==""){
            $response=array('titleResponse'=>'Error','textResponse'=>'text error', 'errors'=>array('errorCode'=>01,'errorMessage'=>'El campo text es requerido'));
        }


        if($id>0 && $conversation_id!=""){

            $twitter_bd=TwitterUsers::where('id_str','=',$id)->get()->first();
       
        if($twitter_bd){
            
            $access_token=(array) json_decode($twitter_bd->access_token);
            $this->connection = new TwitterOAuth(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'), $access_token['oauth_token'], $access_token['oauth_token_secret']);



            //Necesito sacarme el username del que me menciono con el conversation_id
            $twitter_data=$this->connection->get('statuses/show',['id'=>$conversation_id]);
            
            $username="@".($twitter_data->user->screen_name);
            $text=$username.' '.$text;
            
            $path='statuses/update';
            $send_data=array (
                                  'in_reply_to_status_id' => $conversation_id,
                                  'status'=>$text
                                );
           
           $message=$this->connection->post($path,$send_data);

            $response=['titleResponse'=>'Ok','textResponse'=>'Mension respondida exitosamente','data'=>$message,'errors'=>[]];

            return new JsonResponse($response,200);

          
        }else{
             $response=array('titleResponse'=>'Error','textResponse'=>'usuario no existe', 'errors'=>array('errorCode'=>02,'errorMessage'=>'El id de usuario no existe en el sistema'));

             return new JsonResponse($response,401);

        }
     }else{

        if($recipient_id!=""){

            $response=array('errors'=>array('errorCode'=>01,'errorMessage'=>'El id de usuario es requerido'));
        }
       
       
     }

        return new Response($response,401);




    }

    public function delete_user(Request $request){

        $id=$request->get('user_id',0); 
        if($id>0){
        $twitter_bd=TwitterUsers::where('id_str','=',$id)->get()->first();
        
        if($twitter_bd){
            
            $twitter_bd->delete();

            $response=['titleResponse'=>'Ok','textResponse'=>'usuario eliminado exitosamente','data'=>[],'errors'=>[]];

            return new JsonResponse($response,200);

          
        }else{

             $response=array('titleResponse'=>'Error','textResponse'=>'usuario no existe', 'errors'=>array('errorCode'=>02,'errorMessage'=>'El id de usuario no existe en el sistema'));

             return new JsonResponse($response,401);

        }
     }else{
        $response=array('errors'=>array('errorCode'=>01,'errorMessage'=>'El id de usuario es requerido'));

        return new JsonResponse($response,401);
     }

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
        $response=array('errorCode'=>01,'errorMessage'=>'El id de usuario es requerido');
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
                        $existe->message_json=json_encode($mensaje);
                        $existe->is_new=0;
                        $existe->save();

                }
                else{
                    
                    if($existe){
                         $existe->message_json=json_encode($mensaje);
                         $existe->is_new=0;
                         $existe->save();

                    }else{

                    $msg=new TwitterMessages();
                    $msg->id_str_user=$user->id_str;
                    $msg->id_str_message=$mensaje->id;
                    $msg->type=$mensaje->type;
                    $msg->created_timestamp=$mensaje->created_timestamp;
                    $msg->message_json=json_encode($mensaje);
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
