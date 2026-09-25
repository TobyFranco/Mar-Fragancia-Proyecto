@extends('layouts.admin')

@section('title', 'Tamaños')

@section('content')
    <h1>Tamaños</h1>
    <p style="margin:1rem 0"><a href="{{ route('admin.tamanos.create') }}" class="btn">+ Nuevo tamaño</a></p>

    <table>
        <tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr>
        @foreach ($tamanos as $tamano)
            <tr>
                <td>{{ $tamano->id }}</td>
                <td>{{ $tamano->nombre }}</td>
                <td>{{ $tamano->descripcion }}</td>
                <td>
                    <a href="{{ route('admin.tamanos.edit', $tamano) }}">Editar</a>
                    <form action="{{ route('admin.tamanos.destroy', $tamano) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('¿Eliminar este tamaño?')" style="background:none;border:none;color:#b91c1c;cursor:pointer">Eliminar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>

    <div style="margin-top:1rem">{{ $tamanos->links() }}</div>
@endsection