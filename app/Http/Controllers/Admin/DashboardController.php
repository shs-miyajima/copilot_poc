<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * 管理画面ダッシュボードを表示する
     */
    public function index()
    {
        $query = Survey::query();

        // editor は自分のアンケートのみ
        if (Auth::user()->role === 'editor') {
            $query->where('user_id', Auth::id());
        }

        $totalSurveys     = (clone $query)->count();
        $publishedSurveys = (clone $query)->where('status', 'published')->count();
        $totalResponses   = (clone $query)->withCount('responses')
            ->get()->sum('responses_count');

        $recentSurveys = (clone $query)
            ->with('user')->withCount('responses')
            ->latest()->limit(5)->get();

        return view('admin.dashboard.index', compact(
            'totalSurveys',
            'publishedSurveys',
            'totalResponses',
            'recentSurveys'
        ));
    }
}
