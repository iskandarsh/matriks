<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Illuminate\Support\Carbon;

class SSOLoginController extends Controller
{
    private $key = 'INTERBAT_SSO_SECRET_KEY';

    public function loginWithToken(Request $req)
    {
        if (!$req->has('token')) {
            return "Token tidak ada di URL";
        }

        try {
            // Decode JWT
            $data = JWT::decode($req->token, new Key($this->key, 'HS256'));

            // Ambil audience dari token
            $tokenAud = $data->aud ?? null;

            // Allowed hosts (comma separated) di .env
            $allowed = array_map('trim', explode(',', env('SSO_ALLOWED_HOSTS', '10.1.4.98,10.1.4.99,10.1.4.100')));

            // Ambil host request
            $requestHost = $req->getHost() ?? $req->server('HTTP_HOST');

            // Jika token tidak punya aud -> tolak
            if (!$tokenAud) {
                return redirect()->route('login')->withErrors(['token' => 'Token tidak memiliki audience (aud).']);
            }


            // Pastikan aud token termasuk ke allowed list
            if (!in_array($tokenAud, $allowed)) {
                return redirect()->route('login')->withErrors(['token' => 'Token ditujukan untuk host yang tidak diizinkan.']);
            }

            // Pastikan request datang dari host yang sama dengan aud (prevent reuse di host lain)
            if ($tokenAud !== $requestHost) {
                return redirect()->route('login')->withErrors(['token' => 'Token tidak boleh digunakan dari host ini.']);
            }

            // Ambil user
            $user = User::find($data->sub);

            if (!$user) {
                return "User tidak ditemukan";
            }

            // Pastikan token ini sesi aktif/terbaru (Single Session Check via JTI)
            $tokenJti = $data->jti ?? null;
            if (!$tokenJti || $user->current_session_id !== $tokenJti) {
                return redirect()->route('login')->withErrors([
                    'token' => 'Sesi ini sudah tidak berlaku karena ada login baru di device lain.'
                ]);
            }

            // Login user ke session Laravel
            auth()->login($user);

            // Simpan jti token sebagai device_session_id di session
            $req->session()->put('device_session_id', $tokenJti);
            session(['sso_token' => $req->token]);

            /**
             * 1. Jika user belum pernah ganti password (is_change = false) 
             *    atau last_password_change masih NULL → wajib ganti password
             */
            if (!$user->is_change && $user->last_password_change === null) {
                return redirect()
                    ->route('password.change.form')
                    ->with('warning', 'Silakan ubah password Anda untuk pertama kali.');
            }

            /**
             * 2. Jika sudah pernah ganti password, cek apakah sudah lebih dari 6 bulan.
             */
            if ($user->last_password_change !== null) {
                $lastChange = Carbon::parse($user->last_password_change);
                $monthsDiff = $lastChange->diffInMonths(now());

                if ($monthsDiff >= 6) {
                    return redirect()
                        ->route('password.change.form')
                        ->with('warning', 'Password Anda sudah lebih dari 6 bulan. Silakan ubah password untuk keamanan.');
                }
            }


            // Jika lolos semua validasi, arahkan kembali ke Portal Utama Interbat Workspace
            // $tokenEncoded = urlencode($req->token);
            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            Log::warning('SSO token error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['token' => 'Token tidak valid atau sudah kadaluarsa']);
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
