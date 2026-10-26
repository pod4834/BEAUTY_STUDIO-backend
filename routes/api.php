<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
use App\Http\Controllers\Api\SimulationController; 

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

// CELLVIA 시뮬레이터 API 라우트
Route::prefix('simulation')->group(function () {
    Route::get('/scenarios', [SimulationController::class, 'getScenarios']);
    Route::post('/scenarios', [SimulationController::class, 'saveScenario']);
    Route::delete('/scenarios/{id}', [SimulationController::class, 'deleteScenario']);
    Route::post('/scenarios/{id}/memo', [SimulationController::class, 'updateMemo']);

    Route::get('/placements', [SimulationController::class, 'getPlacements']);
    Route::post('/placements', [SimulationController::class, 'savePlacements']);

    // 개월수 선택형 주기 가입 API 라우트 (2, 3, 4개월 개별 생성)
    Route::post('/seed-period', [SimulationController::class, 'seedPeriod']);
    
    // 💡 [추가] 2·3·4개월 전체 시나리오 DB 일괄 생성 라우트
    Route::post('/seed-all', [SimulationController::class, 'seedAllPresets']);

    // 💡 [추가] 회원 기수 변경 라우트
    Route::post('/node', [SimulationController::class, 'updateNode']);
    
    Route::get('/calculate-simulation', [SimulationController::class, 'calculateSimulation']);
});

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