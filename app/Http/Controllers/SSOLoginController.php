<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;

class SSOLoginController extends Controller
{
    private $key = 'INTERBAT_SSO_SECRET_KEY';

    public function loginWithToken(Request $req)
    {


        if (!$req->has('token')) {
            return "Token tidak ada di URL";
        }

        try {
            $data = JWT::decode($req->token, new Key($this->key, 'HS256'));

            $user = User::find($data->sub);

            if (!$user) {
                return "User tidak ditemukan";
            }

            auth()->login($user);

            return redirect('/login');
        } catch (\Exception $e) {
            return "Token error: " . $e->getMessage();
        }
    }


    public function ssoAuth(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $url = env('APP_URL') . '/sso-login';

        $payload = json_encode([
            'email' => $request->email,
            'password' => $request->password
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return back()->withErrors(['email' => 'CURL Error: ' . curl_error($ch)]);
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['email' => 'JSON Error: ' . json_last_error_msg()]);
        }

        if (!empty($result['status']) && $result['status'] === true) {
            // Simpan token di session
            session(['sso_token' => $result['token']]);

            $token = urlencode($result['token']);
            return redirect('../interbat-main/apps.php?token=' . $token);
        }

        return back()->withErrors(['email' => 'Email atau password salah']);
    }
}
