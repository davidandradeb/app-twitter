 
@extends('layouts.app')

@section('content')

<a href="/twitter/direct/messages?user_id={{$user->id_str}}&is_new=0">Listar Ultimos Mensajes</a><br/>
<a href="/profile">Listar Perfil</a>

@endsection