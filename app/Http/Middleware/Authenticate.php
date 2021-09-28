<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User;


class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    public $request;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth, Request $request)
    {
        $this->auth = $auth;
        $this->request=$request;
    }

    public function bearerToken()
    {
       $header = $this->request->header('Authorization', '');
       if (Str::startsWith($header, 'Bearer ')) {
                return Str::substr($header, 7);
       }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        
        $user=User::where('email','admin@dyalogo.com')->get()->first();
        $token_valid=false;

        try{

            $token=$this->bearerToken();
            if($user){
                if($user->password==$token){
                    $token_valid=true;
                }else{
                    $token_valid=false;
                }
            }
        }
        catch(\Exception $ex){
            $token_valid=false;
        }
        if(!$token_valid){
            return response('Unauthorized.', 401);
        }

        return $next($request);

    }
}
