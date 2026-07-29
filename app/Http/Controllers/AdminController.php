<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('admin/Login', [
            'adminPath' => config('admin.path'),
        ]);
    }

    public function login(Request $request)
    {
        $email = $request->input('email', '');
        $password = $request->input('password', '');

        if (
            $email !== config('admin.email') ||
            ! Hash::check($password, config('admin.password'))
        ) {
            return back()->withErrors(['message' => 'Invalid credentials.']);
        }

        session(['admin_authenticated' => true]);

        return redirect()->route('admin.media.index');
    }

    public function logout()
    {
        session()->forget('admin_authenticated');

        return redirect()->route('admin.login');
    }
}
