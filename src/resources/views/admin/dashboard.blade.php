@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <h1>Bienvenido, {{ auth()->user()->nombre }}</h1>
    <p>Panel de administración de Mar Fragancia.</p>
@endsection