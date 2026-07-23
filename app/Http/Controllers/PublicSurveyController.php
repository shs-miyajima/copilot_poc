<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateResponseException;
use App\Http\Requests\StoreResponseRequest;
use App\Models\Survey;
use App\Services\ResponseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicSurveyController extends Controller
{
    public function __construct(private ResponseService $responseService) {}

    /**
     * 回答フォームを表示する
     */
    public function show(Request $request, string $token): View
    {
        /** @var Survey $survey */
        $survey = $request->route('survey');
        $survey->load(['questions.options']);

        return view('surveys.show', compact('survey'));
    }

    /**
     * 回答を送信・保存する
     */
    public function submit(StoreResponseRequest $request, string $token): RedirectResponse
    {
        /** @var Survey $survey */
        $survey = $request->route('survey');

        try {
            $response = $this->responseService->store(
                $survey,
                $request->input('answers', []),
                $request
            );
        } catch (DuplicateResponseException $e) {
            return back()
                ->withInput()
                ->withErrors(['duplicate' => $e->getMessage()]);
        }

        // Cookie ベースの場合は HttpOnly・Secure Cookie を発行
        $redirect = redirect()->route('survey.thanks', ['token' => $token]);

        if ($survey->response_limit_type === 'cookie') {
            $cookieToken = $this->responseService->getCookieToken($survey, $request);
            $redirect    = $redirect->withCookie(
                cookie(
                    name:     'survey_token_' . $survey->id,
                    value:    $cookieToken,
                    minutes:  60 * 24 * 365, // 1年
                    secure:   app()->isProduction(),
                    httpOnly: true,
                    sameSite: 'Lax',
                )
            );
        }

        return $redirect;
    }

    /**
     * 送信完了ページ
     */
    public function thanks(Request $request, string $token): View
    {
        $survey = Survey::where('public_token', $token)->firstOrFail();

        return view('surveys.thanks', compact('survey'));
    }

    /**
     * 受付終了ページ
     */
    public function closed(Request $request, string $token): View
    {
        $survey = Survey::where('public_token', $token)->firstOrFail();

        return view('surveys.closed', compact('survey'));
    }
}
