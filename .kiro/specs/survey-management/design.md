# 設計書: アンケート管理システム

## アーキテクチャ概要

Laravel MVC パターンをベースとし、既存プロジェクトの構成に自然に統合できるアーキテクチャを採用します。

```
┌─────────────────────────────────────────────────────────────────┐
│                         ブラウザ / クライアント                       │
│              (管理者・編集者・閲覧者 / 回答者)                          │
└──────────────────────────┬──────────────────────────────────────┘
                           │ HTTPS
┌──────────────────────────▼──────────────────────────────────────┐
│                      Nginx (リバースプロキシ)                        │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│                     Laravel アプリケーション                         │
│                                                                  │
│  ┌─────────────────┐   ┌─────────────────┐   ┌───────────────┐  │
│  │   Routes        │   │   Middleware     │   │  Policies /   │  │
│  │   web.php       │──▶│  Auth, Role,     │──▶│  Gates        │  │
│  │   (管理 / 回答)  │   │  CSRF, Session  │   │  (RBAC)       │  │
│  └─────────────────┘   └─────────────────┘   └───────┬───────┘  │
│                                                        │          │
│  ┌─────────────────────────────────────────────────────▼───────┐ │
│  │                      Controllers                             │ │
│  │  SurveyController  QuestionController  ResponseController   │ │
│  │  ResultController  UserController      AuthController       │ │
│  └─────────────────────────────────────────────────────┬───────┘ │
│                                                         │         │
│  ┌──────────────────────────────────────────────────────▼──────┐ │
│  │                      Models (Eloquent ORM)                   │ │
│  │  Survey  Question  QuestionOption  Response  Answer  User   │ │
│  └──────────────────────────────────────────────────────┬──────┘ │
│                                                          │        │
│  ┌───────────────────────┐   ┌──────────────────────────▼──────┐ │
│  │   Blade Templates     │   │      Services / Helpers          │ │
│  │   (管理画面 / 回答フォーム) │   │  ExportService  StatService     │ │
│  └───────────────────────┘   └─────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│                    PostgreSQL データベース                           │
└─────────────────────────────────────────────────────────────────┘
```

### 主要な設計方針

- **管理側と回答側を明確に分離**: 管理機能は `/admin` プレフィックスで認証必須、回答フォームは `/s/{token}` で認証不要
- **ロールベースアクセス制御**: Laravel の Gate / Policy を使って Admin・Editor・Viewer の権限を管理
- **論理削除**: アンケート・回答は `SoftDeletes` を使い、データを保持したまま削除
- **一意トークン**: 各アンケートに UUID ベースの公開トークンを付与

---

## テクノロジースタック

| 分類 | 技術 | 選定理由 |
|------|------|----------|
| フレームワーク | Laravel 12 (PHP 8.2+) | 既存プロジェクトに合わせる |
| ORM | Eloquent ORM | Laravel 標準、関連管理が容易 |
| 認証 | 手動実装（LoginController + セッション） | Breezeなどの外部パッケージ不要、id/passのシンプルなログインのみ |
| DB | PostgreSQL | 既存 docker-compose に合わせる |
| テンプレート | Blade | 既存プロジェクトに合わせる |
| CSS | Tailwind CSS | Breeze のデフォルト、レスポンシブ対応が容易 |
| JS | Alpine.js (CDN) + Vanilla JS | ドラッグ&ドロップ、動的フォーム（Breezeなしで独立導入）|
| グラフ | Chart.js (CDN) | 軽量、Bar/Pie グラフ対応 |
| エクスポート | league/csv + PhpSpreadsheet | CSV・Excel 出力 |
| ソフト削除 | Eloquent SoftDeletes | 論理削除 |
| バリデーション | Laravel FormRequest | コントローラを薄く保つ |

---

## データモデル

### ER 図

```
users
  id, name, email, password, role, is_active, last_login_at,
  created_at, updated_at, deleted_at

surveys
  id, user_id (FK→users), title, description, status,
  starts_at, ends_at, max_responses, response_limit_type,
  thanks_message, public_token (unique), is_duplicate_check,
  created_at, updated_at, deleted_at

questions
  id, survey_id (FK→surveys), type, label, hint,
  is_required, order, scale_min, scale_max,
  created_at, updated_at

question_options
  id, question_id (FK→questions), label, order,
  created_at, updated_at

question_logic (スキップロジック)
  id, question_id (FK→questions), option_id (FK→question_options),
  target_question_id (FK→questions), action (skip|jump),
  created_at, updated_at

responses
  id, survey_id (FK→surveys), respondent_token, ip_address,
  cookie_token, submitted_at, created_at, updated_at

answers
  id, response_id (FK→responses), question_id (FK→questions),
  value (text), created_at, updated_at

answer_options (複数選択の場合の中間テーブル)
  id, answer_id (FK→answers), option_id (FK→question_options),
  created_at, updated_at
```

### テーブル定義

#### users テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint PK | 主キー |
| name | varchar(255) | ユーザー名 |
| email | varchar(255) UNIQUE | メールアドレス |
| password | varchar(255) | bcrypt ハッシュ |
| role | enum('admin','editor','viewer') | ロール |
| is_active | boolean DEFAULT true | アカウント有効フラグ |
| last_login_at | timestamp NULL | 最終ログイン日時 |
| remember_token | varchar(100) NULL | ログイン保持トークン |
| created_at / updated_at | timestamp | タイムスタンプ |
| deleted_at | timestamp NULL | 論理削除 |

#### surveys テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint PK | 主キー |
| user_id | bigint FK | 作成者 (users) |
| title | varchar(255) | タイトル（必須） |
| description | text NULL | 説明文 |
| status | enum('draft','published','closed') DEFAULT 'draft' | ステータス |
| starts_at | timestamp NULL | 公開開始日時 |
| ends_at | timestamp NULL | 公開終了日時 |
| max_responses | integer NULL | 回答上限 |
| response_limit_type | enum('cookie','ip','none') DEFAULT 'none' | 重複制御方法 |
| thanks_message | text NULL | サンクスメッセージ |
| public_token | varchar(64) UNIQUE | 公開 URL トークン (UUID) |
| created_at / updated_at | timestamp | タイムスタンプ |
| deleted_at | timestamp NULL | 論理削除 |

#### questions テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint PK | 主キー |
| survey_id | bigint FK | 所属アンケート |
| type | enum('radio','checkbox','text','textarea','rating','number','date','dropdown') | 質問タイプ |
| label | text | 質問文 |
| hint | text NULL | 補足説明 |
| is_required | boolean DEFAULT false | 必須フラグ |
| order | integer DEFAULT 0 | 表示順序 |
| scale_min | integer NULL | 評価スケール最小値 |
| scale_max | integer NULL | 評価スケール最大値 |
| created_at / updated_at | timestamp | タイムスタンプ |

#### question_options テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint PK | 主キー |
| question_id | bigint FK | 所属質問 |
| label | varchar(255) | 選択肢テキスト |
| order | integer DEFAULT 0 | 表示順序 |
| created_at / updated_at | timestamp | タイムスタンプ |

#### question_logic テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint PK | 主キー |
| question_id | bigint FK | 条件元の質問 |
| option_id | bigint FK | 条件となる選択肢 |
| target_question_id | bigint FK | ジャンプ先の質問 |
| action | enum('skip','jump') | 動作 |
| created_at / updated_at | timestamp | タイムスタンプ |

#### responses テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint PK | 主キー |
| survey_id | bigint FK | 対象アンケート |
| respondent_token | varchar(64) NULL | 回答者識別トークン (UUID) |
| ip_address | varchar(45) NULL | 回答者 IP アドレス |
| cookie_token | varchar(64) NULL | Cookie 識別トークン |
| submitted_at | timestamp NULL | 送信完了日時 |
| created_at / updated_at | timestamp | タイムスタンプ |

#### answers テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint PK | 主キー |
| response_id | bigint FK | 所属回答セット |
| question_id | bigint FK | 対象質問 |
| value | text NULL | 回答値（テキスト・数値・日付など） |
| created_at / updated_at | timestamp | タイムスタンプ |

#### answer_options テーブル

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint PK | 主キー |
| answer_id | bigint FK | 所属回答 |
| option_id | bigint FK | 選択された選択肢 |
| created_at / updated_at | timestamp | タイムスタンプ |

---

## API 設計

### ルーティング構造

```
web.php

# 認証（手動 LoginController）
GET  /login                         → Auth\LoginController@showForm
POST /login                         → Auth\LoginController@login
POST /logout                        → Auth\LoginController@logout

# 公開回答フォーム (認証不要)
GET  /s/{token}                     → PublicSurveyController@show
POST /s/{token}                     → PublicSurveyController@submit
GET  /s/{token}/thanks              → PublicSurveyController@thanks

# 管理画面 (認証必須 middleware: auth)
Route::prefix('admin')->middleware(['auth'])->group(function () {

    # ダッシュボード
    GET  /admin                     → Admin\DashboardController@index

    # アンケート管理 (Admin・Editor)
    GET  /admin/surveys             → Admin\SurveyController@index
    GET  /admin/surveys/create      → Admin\SurveyController@create
    POST /admin/surveys             → Admin\SurveyController@store
    GET  /admin/surveys/{id}        → Admin\SurveyController@show
    GET  /admin/surveys/{id}/edit   → Admin\SurveyController@edit
    PUT  /admin/surveys/{id}        → Admin\SurveyController@update
    DELETE /admin/surveys/{id}      → Admin\SurveyController@destroy
    POST /admin/surveys/{id}/duplicate → Admin\SurveyController@duplicate
    PATCH /admin/surveys/{id}/status   → Admin\SurveyController@updateStatus

    # 質問管理 (Admin・Editor、アンケート所有者)
    POST   /admin/surveys/{id}/questions            → Admin\QuestionController@store
    PUT    /admin/surveys/{id}/questions/{qid}      → Admin\QuestionController@update
    DELETE /admin/surveys/{id}/questions/{qid}      → Admin\QuestionController@destroy
    POST   /admin/surveys/{id}/questions/reorder    → Admin\QuestionController@reorder

    # 結果・集計
    GET  /admin/surveys/{id}/results          → Admin\ResultController@index
    GET  /admin/surveys/{id}/results/export/csv   → Admin\ResultController@exportCsv
    GET  /admin/surveys/{id}/results/export/excel → Admin\ResultController@exportExcel
    DELETE /admin/surveys/{id}/responses/{rid}    → Admin\ResultController@destroyResponse

    # ユーザー管理 (Admin のみ)
    GET  /admin/users               → Admin\UserController@index
    GET  /admin/users/create        → Admin\UserController@create
    POST /admin/users               → Admin\UserController@store
    GET  /admin/users/{id}/edit     → Admin\UserController@edit
    PUT  /admin/users/{id}          → Admin\UserController@update
    PATCH /admin/users/{id}/toggle  → Admin\UserController@toggleActive
});
```

### FormRequest バリデーション

| クラス | 対象 | 主なルール |
|--------|------|-----------|
| `StoreSurveyRequest` | アンケート作成 | title: required\|string\|max:255 |
| `UpdateSurveyRequest` | アンケート編集 | title: required, ends_at: after:starts_at |
| `StoreQuestionRequest` | 質問追加 | type: required\|in:[types], label: required |
| `StoreResponseRequest` | 回答送信 | 動的バリデーション（質問の is_required に基づく） |
| `StoreUserRequest` | ユーザー作成 | email: unique, password: min:8|confirmed |

---

## コンポーネント設計

### Models（Eloquent）

```
app/Models/
├── User.php          # ロール管理、認証
├── Survey.php        # SoftDeletes, スコープ(published), publicTokenの自動生成
├── Question.php      # リレーション(options, logics), 順序管理
├── QuestionOption.php
├── QuestionLogic.php
├── Response.php      # 回答セット
├── Answer.php        # 個別回答値
└── AnswerOption.php  # 複数選択の中間
```

**Survey モデルの主要メソッド・スコープ**
- `scopePublished()`: status='published' かつ期間内のみ
- `isAcceptingResponses()`: 公開中・期間内・上限未達を一括判定
- `getPublicUrlAttribute()`: `url('/s/' . $this->public_token)` を返す

### Controllers

```
app/Http/Controllers/
├── Auth/
│   └── LoginController.php          # 手動実装：ログイン・ログアウトのみ
├── PublicSurveyController.php   # 回答者向け（認証不要）
└── Admin/
    ├── DashboardController.php
    ├── SurveyController.php
    ├── QuestionController.php
    ├── ResultController.php
    └── UserController.php
```

### Services

```
app/Services/
├── SurveyPublishService.php   # ステータス変更・バリデーション
├── ResponseService.php        # 回答保存、重複チェックロジック
├── StatisticsService.php      # 集計データ計算（選択肢ごとの件数・割合）
└── ExportService.php          # CSV・Excel エクスポート生成
```

### Middleware

```
app/Http/Middleware/
├── RoleMiddleware.php         # role:admin, role:editor などのチェック
└── CheckSurveyAvailable.php   # 回答フォーム表示前の公開状態チェック
```

### Policies

```
app/Policies/
├── SurveyPolicy.php   # viewAny, view, create, update, delete, duplicate
└── UserPolicy.php     # Admin のみ操作可能
```

### Blade ビュー構成

```
resources/views/
├── layouts/
│   ├── admin.blade.php          # 管理画面レイアウト（サイドバー・ヘッダー）
│   └── survey.blade.php         # 回答フォームレイアウト（シンプル）
├── auth/
│   └── login.blade.php          # ログインフォーム（手動実装）
├── admin/
│   ├── dashboard/
│   │   └── index.blade.php      # ダッシュボード
│   ├── surveys/
│   │   ├── index.blade.php      # アンケート一覧
│   │   ├── create.blade.php     # 新規作成
│   │   ├── edit.blade.php       # 編集（質問ビルダー含む）
│   │   └── show.blade.php       # 詳細・公開URL表示
│   ├── results/
│   │   └── index.blade.php      # 集計結果・グラフ
│   └── users/
│       ├── index.blade.php      # ユーザー一覧
│       ├── create.blade.php
│       └── edit.blade.php
└── surveys/
    ├── show.blade.php           # 回答フォーム
    ├── thanks.blade.php         # 送信完了ページ
    └── closed.blade.php         # 受付終了ページ
```

### JavaScript コンポーネント

| 機能 | 実装方法 |
|------|---------|
| 質問の並べ替え | Sortable.js（CDN）+ Alpine.js で order 値を AJAX 送信 |
| 選択肢の動的追加 | Alpine.js の x-data でリアクティブ管理 |
| スキップロジック設定UI | Alpine.js + Blade コンポーネント |
| 集計グラフ | Chart.js（Bar・Pie）、集計データを JSON で埋め込み |
| 回答フォームのバリデーション | HTML5 required + JS でリッチなエラー表示 |

---

## セキュリティ考慮事項

### 認証・認可

- **手動 LoginController** による認証（メール＋パスワード、`Auth::attempt()` + セッション）
- Laravel Breeze は使用しない。`LoginController` を手動実装し、ログイン・ログアウトのみ提供
- 認証ビューは `resources/views/auth/login.blade.php` を独自作成
- **セッションタイムアウト**: `config/session.php` の `lifetime` を 30 分に設定、非アクティブ検出は `SESSION_LIFETIME` 環境変数で管理
- **ロールチェック**: `RoleMiddleware` + `Policy` の二層構造
  - `role:admin` → 管理者のみ
  - `role:admin,editor` → 管理者・編集者
  - Policy でリソースレベルの権限（他ユーザーのアンケートへの Editor アクセスを制限）
- **パスワードポリシー**: `min:8|regex:/^(?=.*[A-Za-z])(?=.*\d).+$/` でバリデーション

### CSRF / XSS / SQLインジェクション対策

- **CSRF**: 全フォームに `@csrf` ディレクティブ（Laravel デフォルト）
- **XSS**: Blade の `{{ }}` による自動エスケープ、`{!! !!}` の使用を最小化
- **SQLインジェクション**: Eloquent ORM・クエリビルダのプリペアドステートメントのみ使用、生クエリ禁止
- **CSP ヘッダー**: `middleware` で `Content-Security-Policy` を設定

### 個人情報保護

- 回答者の IP アドレスは重複チェック目的のみに使用し、`ip_address` カラムはハッシュ化（`hash('sha256', $ip)` など）を検討
- `Cookie` トークンは HttpOnly・Secure 属性付きで発行
- ログには個人情報を含めない（`config/logging.php` で除外設定）

### その他

- HTTPS 強制: `AppServiceProvider` で `URL::forceScheme('https')` を本番環境のみ適用
- `public_token` は UUIDv4 で生成し、推測困難にする
- アップロードファイルは今バージョンではなし（スコープ外）

---

## エラーハンドリング

### HTTP エラーレスポンス

| ケース | 対応 |
|--------|------|
| 404 Not Found | `resources/views/errors/404.blade.php` でカスタムページ表示 |
| 403 Forbidden | `resources/views/errors/403.blade.php` で権限エラーページ表示 |
| 422 Validation Error | FormRequest により自動的に前ページにエラーと入力値を返す |
| 500 Server Error | `resources/views/errors/500.blade.php` + ログ記録 |
| アンケート受付終了 | `CheckSurveyAvailable` middleware で `surveys/closed.blade.php` にリダイレクト |

### バリデーションエラー

- `FormRequest` クラスを使用し、コントローラを薄く保つ
- エラーメッセージは `lang/ja/validation.php` で日本語化
- 回答フォームは `$errors->has()` と `@error` ディレクティブで各フィールドにインラインエラーを表示

### サービス層のエラー

- `ExportService` や `StatisticsService` での例外は `try-catch` でキャッチし、ユーザーフレンドリーなメッセージにラップして再スロー
- 重複回答チェック失敗は専用の例外クラス `DuplicateResponseException` を定義してコントローラで処理

### ログ

- `Log::error()` / `Log::warning()` で Laravel のログチャネルに記録
- 本番環境では `daily` ドライバで日次ローテーション（`logs/` ディレクトリ）
- 機密情報（IP、Cookie トークン）はログに含めない

---

## 実装フェーズ

実装は以下の順序で進めることを推奨します:

1. **フェーズ1 - 基盤構築**: 手動 LoginController 実装、User モデルへの role カラム追加、RoleMiddleware、管理画面レイアウト
2. **フェーズ2 - アンケートCRUD**: surveys・questions・question_options テーブルとマイグレーション、SurveyController・QuestionController、Blade 編集画面（質問ビルダーUI）
3. **フェーズ3 - 回答収集**: responses・answers テーブル、PublicSurveyController、回答フォーム、重複チェック
4. **フェーズ4 - 集計・エクスポート**: StatisticsService、Chart.js グラフ、ExportService（CSV/Excel）
5. **フェーズ5 - ユーザー管理・仕上げ**: UserController、Policy 完成、スキップロジック、パフォーマンスチューニング（DB インデックス等）

---

## パフォーマンス考慮事項

- **DB インデックス**: `surveys.public_token`（UNIQUE）、`responses.survey_id`、`answers.question_id`、`surveys.status` + `starts_at` + `ends_at` の複合インデックス
- **Eager Loading**: `Survey::with(['questions.options', 'responses'])` で N+1 問題を防ぐ
- **集計クエリの最適化**: `StatisticsService` では `DB::table()->groupBy()->count()` などの集約クエリを使い、PHP 側でのループ集計を避ける
- **ページネーション**: 回答一覧は `paginate(50)` でページング
- **キャッシュ**: 集計結果は `Cache::remember()` で短時間キャッシュ（集計ページのパフォーマンス要件 10 秒以内を満たすため）
