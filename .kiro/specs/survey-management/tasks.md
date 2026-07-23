# 実装タスク: アンケート管理システム

## フェーズ1: 基盤構築

- [ ] 1.1: ログイン認証の手動実装
  - `app/Http/Controllers/Auth/LoginController.php` を作成
    - `showForm`（ログイン画面表示）、`login`（`Auth::attempt()` によるセッション認証）、`logout` メソッドを実装
  - `resources/views/auth/login.blade.php` を作成（メールアドレス＋パスワードのシンプルなログインフォーム、`@csrf`付き）
  - `routes/web.php` に `GET /login`、`POST /login`、`POST /logout` を追加
  - Tailwind CSS でログイン画面をスタイリング
  - Laravel Breeze は使用しない
  - 対応要件: REQ-045, REQ-046

- [ ] 1.2: Userモデルへのロール・ステータスカラム追加
  - `database/migrations/xxxx_add_role_to_users_table.php` を作成
  - `role` (enum: admin/editor/viewer)、`is_active` (boolean)、`last_login_at` (timestamp)、`deleted_at` (論理削除) カラムを追加
  - `app/Models/User.php` に `role`、`is_active` の `$fillable` とキャスト設定を追加
  - SoftDeletes トレイトを適用
  - 対応要件: REQ-041, REQ-042, REQ-043, REQ-044, REQ-046

- [ ] 1.3: RoleMiddlewareの実装
  - `app/Http/Middleware/RoleMiddleware.php` を作成
  - `role:admin`、`role:admin,editor` など可変引数でロールチェックを行うロジックを実装
  - `bootstrap/app.php` にミドルウェアエイリアス (`role`) を登録
  - 対応要件: REQ-041, REQ-042, REQ-043, REQ-044

- [ ] 1.4: 管理画面レイアウトとフロントエンド依存の設定
  - `resources/views/layouts/admin.blade.php` を作成（サイドバー・ヘッダー・ナビゲーション）
  - `resources/views/layouts/survey.blade.php` を作成（回答フォーム用シンプルレイアウト）
  - Tailwind CSS（CDN または npm）と Alpine.js（CDN）をレイアウトに組み込む
  - Tailwind CSSでレスポンシブ対応（PC・スマートフォン・タブレット）
  - 対応要件: NFR-021, NFR-024

- [ ] 1.5: 管理画面ルーティングの基本構造を設定
  - `routes/web.php` に `/admin` プレフィックスのルートグループを追加
  - `auth` ミドルウェアと `role` ミドルウェアを適用
  - `Admin\DashboardController@index` を作成し `GET /admin` ルートに接続
  - 対応要件: NFR-013

- [ ] 1.6: エラーページのカスタマイズ
  - `resources/views/errors/403.blade.php`（権限エラー）を作成
  - `resources/views/errors/404.blade.php`（ページ未発見）を作成
  - `resources/views/errors/500.blade.php`（サーバーエラー）を作成
  - 日本語メッセージを表示（NFR-041対応）
  - 対応要件: NFR-041

- [ ] 1.7: バリデーションメッセージの日本語化
  - `lang/ja/validation.php` を作成し、Laravel デフォルトのバリデーションメッセージを日本語化
  - `config/app.php` の `locale` を `ja` に設定
  - 対応要件: NFR-041

---

## フェーズ2: アンケートCRUD

- [ ] 2.1: データベースマイグレーションの作成（surveys・questions・question_options・question_logic）
  - `database/migrations/xxxx_create_surveys_table.php` を作成
    - カラム: id, user_id, title, description, status, starts_at, ends_at, max_responses, response_limit_type, thanks_message, public_token, created_at, updated_at, deleted_at
  - `database/migrations/xxxx_create_questions_table.php` を作成
    - カラム: id, survey_id, type, label, hint, is_required, order, scale_min, scale_max, created_at, updated_at
  - `database/migrations/xxxx_create_question_options_table.php` を作成
    - カラム: id, question_id, label, order, created_at, updated_at
  - `database/migrations/xxxx_create_question_logic_table.php` を作成
    - カラム: id, question_id, option_id, target_question_id, action, created_at, updated_at
  - パフォーマンス用インデックス: `surveys.public_token`（UNIQUE）、`surveys.status + starts_at + ends_at`（複合）
  - `php artisan migrate` で適用
  - 対応要件: REQ-001, REQ-004, REQ-005, REQ-006, REQ-008, REQ-011, REQ-014

- [ ] 2.2: Eloquentモデルの作成（Survey・Question・QuestionOption・QuestionLogic）
  - `app/Models/Survey.php` を作成
    - SoftDeletes トレイト適用
    - `$fillable`、キャスト（status enum、dates）設定
    - リレーション: `user()`, `questions()`, `responses()`
    - スコープ: `scopePublished()`（status='published' かつ期間内）
    - `isAcceptingResponses()` メソッド実装（公開中・期間・上限チェック）
    - `getPublicUrlAttribute()` メソッド実装
    - `booted()` フックで `public_token` に UUID を自動生成
  - `app/Models/Question.php` を作成（リレーション: `survey()`, `options()`, `logics()`）
  - `app/Models/QuestionOption.php` を作成（リレーション: `question()`）
  - `app/Models/QuestionLogic.php` を作成
  - 対応要件: REQ-003, REQ-004, REQ-008

- [ ] 2.3: SurveyPolicyの実装
  - `app/Policies/SurveyPolicy.php` を作成
  - `viewAny`, `view`, `create`, `update`, `delete`, `duplicate` メソッドを実装
  - Admin は全操作可能、Editor は自分のアンケートのみ作成・編集・削除可能、Viewer は閲覧のみ
  - `AuthServiceProvider`（または `AppServiceProvider`）でポリシーを登録
  - 対応要件: REQ-042, REQ-043, REQ-044

- [ ] 2.4: アンケートCRUD用FormRequestの作成
  - `app/Http/Requests/StoreSurveyRequest.php` を作成（title: required|string|max:255, ends_at: after:starts_at など）
  - `app/Http/Requests/UpdateSurveyRequest.php` を作成
  - 対応要件: REQ-001, REQ-002, REQ-005

- [ ] 2.5: Admin\SurveyControllerの実装
  - `app/Http/Controllers/Admin/SurveyController.php` を作成
  - `index`（一覧）、`create`（作成フォーム）、`store`（保存）、`show`（詳細・公開URL表示）、`edit`（編集フォーム）、`update`（更新）、`destroy`（論理削除）、`duplicate`（複製）、`updateStatus`（ステータス変更）メソッドを実装
  - Policy による認可チェックを各メソッドに適用
  - 対応要件: REQ-001, REQ-002, REQ-003, REQ-004, REQ-007, REQ-008

- [ ] 2.6: 質問管理用FormRequestとAdmin\QuestionControllerの実装
  - `app/Http/Requests/StoreQuestionRequest.php` を作成（type: required|in:[types], label: required）
  - `app/Http/Controllers/Admin/QuestionController.php` を作成
  - `store`（質問追加）、`update`（質問編集）、`destroy`（質問削除）、`reorder`（並べ替え）メソッドを実装
  - 対応要件: REQ-011, REQ-012, REQ-013, REQ-014, REQ-015, REQ-016

- [ ] 2.7: アンケート管理Bladeビューの作成
  - `resources/views/admin/surveys/index.blade.php`（アンケート一覧・検索・ステータスバッジ）
  - `resources/views/admin/surveys/create.blade.php`（新規作成フォーム）
  - `resources/views/admin/surveys/edit.blade.php`（編集フォーム＋質問ビルダーUI）
  - `resources/views/admin/surveys/show.blade.php`（詳細・公開URL・埋め込みコード表示）
  - `resources/views/admin/dashboard/index.blade.php`（ダッシュボード）
  - 対応要件: REQ-001, REQ-002, REQ-005, REQ-006, REQ-007, REQ-008, NFR-024

- [ ] 2.8: 質問ビルダーUIの実装（Alpine.js）
  - Alpine.js の `x-data` を使った選択肢の動的追加・削除
  - Sortable.js（CDN）による質問の並べ替えDnD実装
  - 並べ替え後に `POST /admin/surveys/{id}/questions/reorder` へ AJAX 送信
  - 各質問タイプ（radio, checkbox, text, textarea, rating, number, date, dropdown）ごとの入力UI切り替え
  - 対応要件: REQ-012, REQ-014, REQ-015, REQ-016, NFR-021

- [ ] 2.9: SurveyPublishServiceの実装
  - `app/Services/SurveyPublishService.php` を作成
  - ステータス変更バリデーション（下書き→公開中に必要な質問が存在するかチェック等）を実装
  - 対応要件: REQ-004, REQ-005

---

## フェーズ3: 回答収集

- [ ] 3.1: データベースマイグレーションの作成（responses・answers・answer_options）
  - `database/migrations/xxxx_create_responses_table.php` を作成
    - カラム: id, survey_id, respondent_token, ip_address, cookie_token, submitted_at, created_at, updated_at
  - `database/migrations/xxxx_create_answers_table.php` を作成
    - カラム: id, response_id, question_id, value, created_at, updated_at
  - `database/migrations/xxxx_create_answer_options_table.php` を作成
    - カラム: id, answer_id, option_id, created_at, updated_at
  - パフォーマンス用インデックス: `responses.survey_id`、`answers.question_id`
  - `php artisan migrate` で適用
  - 対応要件: REQ-022, REQ-026

- [ ] 3.2: Eloquentモデルの作成（Response・Answer・AnswerOption）
  - `app/Models/Response.php` を作成（リレーション: `survey()`, `answers()`）
  - `app/Models/Answer.php` を作成（リレーション: `response()`, `question()`, `options()`）
  - `app/Models/AnswerOption.php` を作成（リレーション: `answer()`, `option()`）
  - 対応要件: REQ-022, REQ-026

- [ ] 3.3: CheckSurveyAvailableミドルウェアの実装
  - `app/Http/Middleware/CheckSurveyAvailable.php` を作成
  - `public_token` でアンケートを検索し、`isAcceptingResponses()` で公開状態を確認
  - 受付終了の場合は `surveys/closed.blade.php` にリダイレクト
  - ミドルウェアを `bootstrap/app.php` に登録
  - 対応要件: REQ-023

- [ ] 3.4: 回答送信用FormRequestの作成
  - `app/Http/Requests/StoreResponseRequest.php` を作成
  - アンケートの質問定義を元に動的バリデーションルールを生成（`is_required` フラグに基づく）
  - 必須チェック、質問タイプごとの型チェック（数値・日付等）を実装
  - 対応要件: REQ-013, REQ-022

- [ ] 3.5: ResponseServiceの実装
  - `app/Services/ResponseService.php` を作成
  - 回答データの保存ロジック（`responses`・`answers`・`answer_options` テーブルへの一括保存）を実装
  - 重複チェックロジックを実装（Cookie ベース・IP ベース・none の3モード）
  - IPアドレスのSHA-256ハッシュ化処理を実装（個人情報保護）
  - `DuplicateResponseException` カスタム例外クラスを `app/Exceptions/DuplicateResponseException.php` に作成
  - 対応要件: REQ-024, REQ-026, NFR-014

- [ ] 3.6: PublicSurveyControllerの実装
  - `app/Http/Controllers/PublicSurveyController.php` を作成
  - `show`（回答フォーム表示）、`submit`（回答送信・保存）、`thanks`（送信完了ページ）メソッドを実装
  - `submit` で `ResponseService` を呼び出し、`DuplicateResponseException` をキャッチして適切なエラーを表示
  - 回答者識別用 Cookie（HttpOnly・Secure 属性付き）の発行ロジックを実装
  - 対応要件: REQ-021, REQ-022, REQ-024, REQ-025

- [ ] 3.7: 公開回答フォームBladeビューの作成
  - `resources/views/surveys/show.blade.php`（回答フォーム）を作成
    - 各質問タイプ（radio, checkbox, text, textarea, rating, number, date, dropdown）に対応したフォームUI
    - HTML5 バリデーション属性（required 等）の付与
    - CSRF トークン埋め込み（`@csrf`）
  - `resources/views/surveys/thanks.blade.php`（送信完了・カスタマイズ可能なサンクスメッセージ）を作成
  - `resources/views/surveys/closed.blade.php`（受付終了メッセージ）を作成
  - レスポンシブ対応（NFR-021）、JS無効環境でも基本動作（NFR-023）
  - 対応要件: REQ-021, REQ-023, REQ-025, NFR-021, NFR-023

- [ ] 3.8: 公開回答フォームのルーティング設定
  - `routes/web.php` に以下を追加（認証不要）
    - `GET  /s/{token}` → `PublicSurveyController@show`
    - `POST /s/{token}` → `PublicSurveyController@submit`
    - `GET  /s/{token}/thanks` → `PublicSurveyController@thanks`
  - `CheckSurveyAvailable` ミドルウェアを `show`・`submit` に適用
  - 対応要件: REQ-021

---

## フェーズ4: 集計・エクスポート

- [ ] 4.1: StatisticsServiceの実装
  - `app/Services/StatisticsService.php` を作成
  - 選択系質問（radio, checkbox, dropdown, rating）の選択肢ごとの件数・割合を集計する `summarizeQuestion()` メソッドを実装
  - `DB::table()->groupBy()->count()` などの集約クエリを使用してN+1問題を回避
  - アンケート全体の回答数サマリーを返す `getSurveySummary()` メソッドを実装
  - `Cache::remember()` で集計結果をキャッシュ（5分程度）してパフォーマンス要件を満たす
  - 対応要件: REQ-031, REQ-032, REQ-033, REQ-034, NFR-003

- [ ] 4.2: Admin\ResultControllerの実装
  - `app/Http/Controllers/Admin/ResultController.php` を作成
  - `index`（集計結果・回答一覧表示）、`exportCsv`（CSVダウンロード）、`exportExcel`（Excelダウンロード）、`destroyResponse`（回答個別削除）メソッドを実装
  - `index` で `StatisticsService` を呼び出し、集計データを JSON として Blade に埋め込み
  - 回答一覧は `paginate(50)` でページング
  - 対応要件: REQ-031, REQ-033, REQ-034, REQ-035, REQ-036, REQ-037

- [ ] 4.3: ExportServiceの実装（CSV・Excel）
  - `composer require league/csv` と `composer require phpoffice/phpspreadsheet` を実行
  - `app/Services/ExportService.php` を作成
  - `exportCsv(Survey $survey)` メソッドを実装（league/csv使用、UTF-8 BOM付きで日本語対応）
  - `exportExcel(Survey $survey)` メソッドを実装（PhpSpreadsheet使用）
  - エクスポートデータには質問名・回答内容・タイムスタンプを含める
  - try-catch でエラーをラップし、ユーザーフレンドリーなメッセージを返す
  - 対応要件: REQ-035, REQ-036, AC-006

- [ ] 4.4: 集計結果Bladeビューの作成（Chart.js グラフ）
  - `resources/views/admin/results/index.blade.php` を作成
  - Chart.js（CDN）を使った棒グラフ・円グラフの実装
  - 集計データは `@json($statistics)` で JavaScript 変数に埋め込み
  - 回答数・各選択肢の割合のサマリー表示
  - テキスト回答の一覧表示セクション
  - 回答一覧テーブル（ページネーション付き）
  - CSVエクスポート・Excelエクスポートボタンの配置
  - 対応要件: REQ-031, REQ-032, REQ-033, REQ-034, REQ-035, REQ-036

---

## フェーズ5: ユーザー管理・仕上げ

- [ ] 5.1: UserPolicyの実装
  - `app/Policies/UserPolicy.php` を作成
  - `viewAny`, `view`, `create`, `update`, `toggleActive` メソッドを実装（Admin のみ操作可能）
  - `AuthServiceProvider`（または `AppServiceProvider`）でポリシーを登録
  - 対応要件: REQ-041, REQ-042, REQ-047

- [ ] 5.2: ユーザー管理用FormRequestとAdmin\UserControllerの実装
  - `app/Http/Requests/StoreUserRequest.php` を作成（email: unique, password: min:8|confirmed, パスワードポリシー regex）
  - `app/Http/Requests/UpdateUserRequest.php` を作成
  - `app/Http/Controllers/Admin/UserController.php` を作成
  - `index`（ユーザー一覧）、`create`（作成フォーム）、`store`（保存）、`edit`（編集フォーム）、`update`（更新）、`toggleActive`（有効/無効切り替え）メソッドを実装
  - 対応要件: REQ-045, REQ-046, REQ-047, NFR-015

- [ ] 5.3: ユーザー管理Bladeビューの作成
  - `resources/views/admin/users/index.blade.php`（ユーザー一覧・ロールバッジ・有効/無効表示）
  - `resources/views/admin/users/create.blade.php`（ユーザー作成フォーム）
  - `resources/views/admin/users/edit.blade.php`（ユーザー編集フォーム）
  - 対応要件: REQ-047, NFR-024

- [ ] 5.4: セッションタイムアウトの設定
  - `config/session.php` の `lifetime` を 30 分に設定
  - `.env` に `SESSION_LIFETIME=30` を追加
  - 非アクティブ時の自動ログアウト動作を確認
  - 対応要件: REQ-048

- [ ] 5.5: スキップロジック（条件分岐）の実装
  - `Admin\QuestionController` にスキップロジックの保存・更新ロジックを追加
  - `question_logic` テーブルへの CRUD 操作を実装
  - 質問ビルダーUI（`edit.blade.php`）にスキップロジック設定パネルを Alpine.js で追加
  - 回答フォーム（`surveys/show.blade.php`）でスキップロジックに基づく質問の表示/非表示を JavaScript で制御
  - 対応要件: REQ-017

- [ ] 5.6: セキュリティ設定の仕上げ
  - `app/Providers/AppServiceProvider.php` で本番環境のみ `URL::forceScheme('https')` を適用
  - `app/Http/Middleware` に CSP（Content-Security-Policy）ヘッダーを設定するミドルウェアを追加し `bootstrap/app.php` に登録
  - Blade テンプレート全体で `{!! !!}` の使用箇所を確認し、`{{ }}` に置き換え（XSS対策）
  - 全フォームに `@csrf` ディレクティブが設定されていることを確認
  - 対応要件: NFR-011, NFR-012, NFR-013

- [ ] 5.7: パフォーマンスチューニング（DBインデックス・Eager Loading）
  - マイグレーションで追加されていないインデックスを確認・追加（`responses.survey_id`, `answers.question_id`, `surveys.status + starts_at + ends_at` 複合インデックス）
  - `SurveyController` と `ResultController` で `Survey::with(['questions.options', 'responses'])` による Eager Loading を適用
  - `StatisticsService` のクエリをレビューし、N+1問題がないことを確認
  - 対応要件: NFR-001, NFR-002, NFR-003

- [ ] 5.8: ログ設定の最適化
  - `config/logging.php` で本番環境の日次ローテーション（`daily` ドライバ）設定を確認
  - IPアドレス・Cookieトークンなど機密情報がログに含まれないことを確認
  - 対応要件: NFR-033, NFR-014

- [ ] 5.9: フィーチャーテストの作成
  - `tests/Feature/SurveyTest.php` を作成（アンケートCRUD, 公開・終了ステータス変更）
  - `tests/Feature/ResponseTest.php` を作成（回答送信, 重複チェック, 受付終了チェック）
  - `tests/Feature/ResultExportTest.php` を作成（CSV・Excelエクスポート）
  - `tests/Feature/UserManagementTest.php` を作成（ユーザーCRUD, ロール制限）
  - `php artisan test` で全テスト通過を確認
  - 対応要件: AC-001, AC-002, AC-003, AC-004, AC-005, AC-006, AC-007
