<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Process;

use App\Models\User;
use Illuminate\Support\Carbon;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController; 
use App\Http\Controllers\Api\ReservationController; 
use App\Http\Controllers\GalleryController; 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    return redirect('http://localhost:5174/login?verified=true');
})->name('verification.verify');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/products', [ProductController::class, 'index']); 
Route::get('/galleries', [GalleryController::class, 'index']); 

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/reservations/booked-times', [ReservationController::class, 'getBookedTimes']);
Route::post('/reservations', [ReservationController::class, 'store']);
Route::get('/reservations/{id}', [ReservationController::class, 'show']);
Route::put('/reservations/{id}', [ReservationController::class, 'update']);
Route::put('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::get('/user/shipping-addresses', [AddressController::class, 'index']);
    Route::post('/user/shipping-addresses', [AddressController::class, 'store']);
    Route::get('/user/cart', [CartController::class, 'show']); 
    Route::post('/user/cart', [CartController::class, 'update']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/checkout', [OrderController::class, 'checkout']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    Route::post('/galleries', [GalleryController::class, 'store']);
    Route::put('/galleries/{id}', [GalleryController::class, 'update']);
    Route::delete('/galleries/{id}', [GalleryController::class, 'destroy']);

    Route::get('/admin/orders', [AdminController::class, 'getOrders']); 
    Route::put('/admin/orders/{id}/ship', [AdminController::class, 'shipOrder']); 
    Route::post('/admin/products', [AdminController::class, 'addProduct']); 
    Route::delete('/admin/products/{id}', [AdminController::class, 'deleteProduct']); 

    Route::get('/admin/reservations', [AdminController::class, 'getReservations']);
    Route::put('/admin/reservations/{id}/status', [AdminController::class, 'updateReservationStatus']);

    Route::get('/admin/email-templates', [\App\Http\Controllers\Api\AdminEmailTemplateController::class, 'index']);
    Route::put('/admin/email-templates/{id}', [\App\Http\Controllers\Api\AdminEmailTemplateController::class, 'update']);

    Route::get('/admin/users', [AdminController::class, 'getUsers']);
    Route::put('/admin/users/{id}/role', [AdminController::class, 'updateUserRole']);
    Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);

    Route::put('/admin/products/{id}/stock', [AdminController::class, 'updateStock']);
    Route::get('/admin/sales', [AdminController::class, 'getSalesSummary']);

    Route::get('/admin/stats', function () {
        $now = Carbon::now();
        $totalUsers = User::count();
        $thisMonthUsers = User::whereYear('created_at', $now->year)
                              ->whereMonth('created_at', $now->month)
                              ->count();
        return response()->json([
            'total' => $totalUsers,
            'thisMonth' => $thisMonthUsers
        ]);
    });
});

// 🚀 관리자 원클릭 GitHub 동기화 API
Route::post('/admin/git-sync', function (Request $request) {
    if (!app()->environment('local')) {
        return response()->json([
            'success' => false,
            'message' => '보안을 위해 로컬 개발 환경(내 컴퓨터)에서만 GitHub 업로드가 가능합니다.'
        ], 403);
    }

    $commitMsg = $request->input('message') ?: 'Admin: Auto-sync ' . now()->format('Y-m-d H:i:s');
    
    $paths = [
        'Backend (shop-backend)' => base_path(),
        'Frontend (shop-frontend)' => dirname(base_path()) . DIRECTORY_SEPARATOR . 'shop-frontend',
    ];

    $results = [];

    foreach ($paths as $name => $path) {
        if (!is_dir($path)) {
            $results[$name] = "폴더를 찾을 수 없습니다: {$path}";
            continue;
        }

        $status = Process::path($path)->run('git status --porcelain');
        if (empty(trim($status->output()))) {
            $results[$name] = '변경된 파일이 없습니다 (최신 상태).';
            continue;
        }

        Process::path($path)->run('git add .');
        $commit = Process::path($path)->run(['git', 'commit', '-m', $commitMsg]);
        $push = Process::path($path)->timeout(120)->run('git push -u origin HEAD');

        if ($push->successful()) {
            $results[$name] = 'GitHub 업로드(Push) 완료!';
        } else {
            $results[$name] = '업로드 실패: ' . ($push->errorOutput() ?: $commit->errorOutput());
        }
    }

    return response()->json([
        'success' => true,
        'results' => $results
    ]);
});