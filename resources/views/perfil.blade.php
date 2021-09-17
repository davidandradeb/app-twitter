 
 <p>
 	<h1>Hola {{$user->name}}</h1>
 	<img src="{{$user->profile_image_url_https}}"><br/>		
    <p><b>Name:</b>{{$user->name}} <br/>							
    <b>Username:</b>{{$user->screen_name}}<br/>							
    <b>Created At:</b>{{$user->created_at}}<br/><br/>						
	<a href="/menu">Menu</a>
</p>