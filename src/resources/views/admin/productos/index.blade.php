@extends('layouts.admin')

@section('title', 'Productos')

@section('content')
    <h1>Productos</h1>
    <p style="margin:1rem 0"><a href="{{ route('admin.productos.create') }}" class="btn">+ Nuevo producto</a></p>

    <table>
        <tr><th>Imagen</th><th>Nombre</th><th>Marca</th><th>Categoría</th><th>Estado</th><th>Acciones</th></tr>
        @foreach ($productos as $producto)
            <tr>
                <td>
                    @if ($producto->imagen)
                        <img src="{{ asset('storage/' . $producto->imagen) }}" width="50">
                    @else
                        —
                    @endif
                </td>
                <td>{{ $producto->nombre }}</td>
                <td>{{ $producto->marca->nombre }}</td>
                <td>{{ $producto->categoria->nombre }}</td>
                <td>{{ $producto->activo ? 'Activo' : 'Inactivo' }}</td>
                <td>
                    <a href="{{ route('admin.productos.edit', $producto) }}">Editar</a>
                    <form action="{{ route('admin.productos.destroy', $producto) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('¿Eliminar este producto?')" style="background:none;border:none;color:#b91c1c;cursor:pointer">Eliminar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>

    <div style="margin-top:1rem">{{ $productos->links() }}</div>
@endsection