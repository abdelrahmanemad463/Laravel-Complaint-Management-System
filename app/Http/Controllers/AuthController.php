<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    public function showLogin() { if (Auth::check()) { return $this->redirectToHome(Auth::user()); } return view('auth.login'); }
    public function login(Request $request) { if (Auth::check()) { return $this->redirectToHome(Auth::user()); } $credentials = $request->validate(['email'=>'required|email','password'=>'required|string']); if (Auth::attempt($credentials, $request->boolean('remember'))) { $request->session()->regenerate(); return $this->redirectToHome(Auth::user()); } return back()->withErrors(['email'=>__('auth.failed')])->onlyInput('email'); }
    private function redirectToHome($user) { $home = $user->default_home ?? null; if ($home === 'complaints' && $user->can('complaint.view')) { return redirect()->intended(route('complaints.index')); } if ($home === 'visitors' && $user->can('visit.view')) { return redirect()->intended(route('visitors.home')); } if ($home === 'visitors.dashboard' && $user->can('dashboard.visitors.view')) { return redirect()->intended(route('visitors.reports.dashboard')); } if ($home === 'dashboard' && $user->can('dashboard.view')) { return redirect()->intended(route('dashboard')); } if ($user->can('dashboard.view')) { return redirect()->intended(route('dashboard')); } if ($user->can('dashboard.visitors.view')) { return redirect()->intended(route('visitors.reports.dashboard')); } if ($user->can('complaint.view')) { return redirect()->intended(route('complaints.index')); } if ($user->can('visit.view')) { return redirect()->intended(route('visitors.home')); } return redirect()->intended(route('dashboard')); }
    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login'); }
}
