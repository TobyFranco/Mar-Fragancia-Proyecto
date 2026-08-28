<h1>Bienvenido, {{ auth()->user()->nombre }}</h1>
<form method="POST" action="{{ route('logout') }}">@csrf<button>Salir</button></form>