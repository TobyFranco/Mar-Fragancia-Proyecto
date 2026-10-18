<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'required' => 'Completá este campo.',
            'email.email' => 'Revisá el email, ej. nombre@correo.com.',
            'password.string' => 'Completá la contraseña con texto.',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $usuario = Auth::user();

            if ($usuario->rol->nombre === 'administrador') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('cliente.home');
        }

        return back()->withErrors([
            'email' => 'Credenciales incorrectas.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:usuarios,email'],
            'password' => ['required', 'string', 'confirmed', 'min:6'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ], [
            'required' => 'Completá este campo.',
            'string' => 'Completá este campo con texto.',
            'max' => 'Usá hasta :max caracteres.',
            'email.email' => 'Revisá el email, ej. nombre@correo.com.',
            'email.unique' => 'Ese email ya tiene una cuenta. Ingresá con tus datos.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'Usá al menos 6 caracteres.',
        ]);

        $rolCliente = Rol::where('nombre', 'cliente')->first();

        $usuario = Usuario::create([
            'rol_id' => $rolCliente->id,
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Cliente::create([
            'usuario_id' => $usuario->id,
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
        ]);

        Auth::login($usuario);

        return redirect()->route('cliente.home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
