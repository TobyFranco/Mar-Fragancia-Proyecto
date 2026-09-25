@extends('layouts.admin')

@section('title', 'Marcas')

@section('content')
    <h1>Marcas</h1>
    <p style="margin:1rem 0"><a href="{{ route('admin.marcas.create') }}" class="btn">+ Nueva marca</a></p>

    <table>
        <tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>
        @foreach ($marcas as $marca)
            <tr>
                <td>{{ $marca->id }}</td>
                <td>{{ $marca->nombre }}</td>
                <td>
                    <a href="{{ route('admin.marcas.edit', $marca) }}">Editar</a>
                    <form action="{{ route('admin.marcas.destroy', $marca) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('¿Eliminar esta marca?')" style="background:none;border:none;color:#b91c1c;cursor:pointer">Eliminar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>

    <div style="margin-top:1rem">{{ $marcas->links() }}</div>
@endsection