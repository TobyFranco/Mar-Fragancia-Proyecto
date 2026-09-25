@extends('layouts.admin')

@section('title', 'Categorías')

@section('content')
    <h1>Categorías</h1>
    <p style="margin:1rem 0"><a href="{{ route('admin.categorias.create') }}" class="btn">+ Nueva categoría</a></p>

    <table>
        <tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>
        @foreach ($categorias as $categoria)
            <tr>
                <td>{{ $categoria->id }}</td>
                <td>{{ $categoria->nombre }}</td>
                <td>
                    <a href="{{ route('admin.categorias.edit', $categoria) }}">Editar</a>
                    <form action="{{ route('admin.categorias.destroy', $categoria) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('¿Eliminar esta categoría?')" style="background:none;border:none;color:#b91c1c;cursor:pointer">Eliminar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>

    <div style="margin-top:1rem">{{ $categorias->links() }}</div>
@endsection