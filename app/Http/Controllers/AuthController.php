<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectToRoleDashboard();
        }
        
        return view('auth.login');
    }
    
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectToRoleDashboard();
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }

    private function redirectToRoleDashboard()
    {
        $user = Auth::user();
        
        if ($user->hasRole('administrateur')) {
            return redirect('/admin');
        }
        
        if ($user->hasRole('responsable-service')) {
            return redirect('/admin/executive-dashboard');
        }
        
        if ($user->hasRole('agent-service')) {
            return redirect('/admin/service-dashboard');
        }
        
        // Fallback pour autres rôles
        return redirect('/admin');
    }
}