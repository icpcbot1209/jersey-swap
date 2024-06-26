<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = 'dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function login(Request $request)

    {   

        $input = $request->all();

  

        $this->validate($request, [

            'email' => 'required',

            'password' => 'required',

        ]);

  

        $fieldType = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if(auth()->attempt([$fieldType => $input['email'], 'password' => $input['password']]))
        {
            $request->session()->regenerate();
            if(auth()->user()->banned==1){
                $message = auth()->user()->username."! Your account has been banned. For more details contact the Jersey Swap Support Team!";
                Session::flush();
                return redirect('banned')->with('error',$message);
            }
            return redirect()->intended('/exchange');

        }else{
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);

        }

    }
}
