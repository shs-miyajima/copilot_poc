<?php

namespace App\Http\Middleware;

use App\Models\Survey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSurveyAvailable
{
    /**
     * public_token でアンケートを検索し、回答受付中か確認する
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token  = $request->route('token');
        $survey = Survey::where('public_token', $token)->first();

        if (! $survey) {
            abort(404);
        }

        if (! $survey->isAcceptingResponses()) {
            return redirect()->route('survey.closed', ['token' => $token]);
        }

        // ルートパラメータとして survey を共有する
        $request->route()->setParameter('survey', $survey);

        return $next($request);
    }
}
