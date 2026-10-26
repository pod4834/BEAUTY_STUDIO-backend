<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordText;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'lastName'      => 'required|string|max:255',
            'firstName'     => 'required|string|max:255',
            'lastNameKana'  => 'required|string|max:255',
            'firstNameKana' => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users',
            'password'      => 'required|string|min:8',
        ]);

        $user = User::create([
            'last_name'       => $validated['lastName'],
            'first_name'      => $validated['firstName'],
            'last_name_kana'  => $validated['lastNameKana'],
            'first_name_kana' => $validated['firstNameKana'],
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),
        ]);

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            Log::error('メール送信失敗: ' . $e->getMessage());
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => '会員登録が完了しました。',
            'user'    => [
                'id'            => $user->id,
                'lastName'      => $user->last_name,
                'firstName'     => $user->first_name,
                'lastNameKana'  => $user->last_name_kana,
                'firstNameKana' => $user->first_name_kana,
                'email'         => $user->email,
            ],
            'token'   => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'メールアドレス、またはパスワードが正しくありません。'], 401);
        }

        $user->tokens()->delete(); 
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'ログインに成功しました。',
            'user'    => [
                'id'            => $user->id,
                'lastName'      => $user->last_name,
                'firstName'     => $user->first_name,
                'lastNameKana'  => $user->last_name_kana,
                'firstNameKana' => $user->first_name_kana,
                'email'         => $user->email,
            ],
            'token'   => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'ログアウトしました。'], 200);
    }

    // 💡 비밀번호 재설정 메일 발송 기능
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => '登録されていないメールアドレスです。'], 404);
        }

        try {
            $token = Str::random(60);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            $url = "http://localhost:5173/reset-password?token=" . $token . "&email=" . urlencode($user->email);
            Mail::to($user->email)->send(new ResetPasswordText($user, $url));

            return response()->json(['message' => 'パスワード再設定メールを送信しました。'], 200);

        } catch (\Exception $e) {
            Log::error('비밀번호 찾기 메일 에러: ' . $e->getMessage());
            return response()->json(['message' => 'メールの送信に失敗しました。'], 500);
        }
    }

    // 🔴 [여기가 추가되었습니다!] 진짜로 DB의 비밀번호를 바꿔주는 함수
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed', // confirmed는 프론트엔드의 password_confirmation과 짝꿍입니다.
        ]);

        // 1. DB(password_reset_tokens)에서 해당 이메일로 발급된 토큰이 있는지 찾기
        $resetRecord = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        // 2. 토큰이 없거나 다르면 쫓아내기
        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json(['message' => '無効なトークン、または有効期限が切れています。'], 400);
        }

        // 3. 유저 찾아서 비밀번호 업데이트!
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // 4. 다 쓴 토큰은 찌꺼기가 남지 않게 DB에서 삭제
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'パスワードが正常にリセットされました。'], 200);
    }
}