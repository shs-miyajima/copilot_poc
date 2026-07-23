<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\PublicSurveyController;
use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('todos', TodoController::class)->only(['index', 'store', 'update', 'destroy']);

/*
|--------------------------------------------------------------------------
| 認証ルート
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| 管理画面ルート（認証必須）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])
    ->group(function () {
        // ダッシュボード
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // アンケート管理
        Route::post('surveys/{survey}/duplicate', [SurveyController::class, 'duplicate'])->name('surveys.duplicate');
        Route::patch('surveys/{survey}/status', [SurveyController::class, 'updateStatus'])->name('surveys.updateStatus');
        Route::resource('surveys', SurveyController::class);

        // 質問管理
        Route::post('surveys/{survey}/questions/reorder', [QuestionController::class, 'reorder'])->name('surveys.questions.reorder');
        Route::post('surveys/{survey}/questions', [QuestionController::class, 'store'])->name('surveys.questions.store');
        Route::put('surveys/{survey}/questions/{question}', [QuestionController::class, 'update'])->name('surveys.questions.update');
        Route::delete('surveys/{survey}/questions/{question}', [QuestionController::class, 'destroy'])->name('surveys.questions.destroy');

        // 集計・エクスポート
        Route::get('surveys/{survey}/results', [ResultController::class, 'index'])->name('surveys.results.index');
        Route::get('surveys/{survey}/results/export/csv', [ResultController::class, 'exportCsv'])->name('surveys.results.export.csv');
        Route::get('surveys/{survey}/results/export/excel', [ResultController::class, 'exportExcel'])->name('surveys.results.export.excel');
        Route::delete('surveys/{survey}/results/{response}', [ResultController::class, 'destroyResponse'])->name('surveys.results.destroy');

        // ユーザー管理（admin のみ）
        Route::middleware('role:admin')->group(function () {
            Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
            Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
        });
    });

/*
|--------------------------------------------------------------------------
| 公開アンケートルート（認証不要）
|--------------------------------------------------------------------------
*/
Route::prefix('s')->name('survey.')->group(function () {
    // 受付終了ページ（ミドルウェアなし）
    Route::get('{token}/closed', [PublicSurveyController::class, 'closed'])->name('closed');
    // 送信完了ページ（ミドルウェアなし）
    Route::get('{token}/thanks', [PublicSurveyController::class, 'thanks'])->name('thanks');
    // 回答フォーム表示・送信（受付チェックミドルウェアあり）
    Route::get('{token}',  [PublicSurveyController::class, 'show'])->name('show')->middleware('survey.available');
    Route::post('{token}', [PublicSurveyController::class, 'submit'])->name('submit')->middleware('survey.available');
});
