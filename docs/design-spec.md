# 開発共通仕様

> このファイルは `docs/開発共通仕様.pdf`（社内向け「開発共通仕様」文書、全38ページ）から抽出したLaravel開発コーディング規約文書です。`/speckit.plan` 実行時の憲章チェック（Constitution Check）の一部として参照される正式な規約文書であるため、内容は原文の記述をそのまま保持しています。

## ファイルエンコード

当システムはLinuxサーバーによる稼働となる為、Laravel開発資産は、全て「UTF-8」で統一する。
ただし、Excelファイル等一部のファイルについては、それぞれでLinuxで動作する事が大前提でエンコードの変更は許可する。

## システム構成について

本システムは１つのWEBサーバ、１つのドメインで３つのシステム（基本PF、企業DB、ポータル）を稼働させる。
混濁防止や保守性向上の為、それぞれのシステムごとにビュー、サービス、コントローラのソースファイルを物理的に分割して設置する。

- 基本PF　ソースファイル設置ディレクトリ
  - app\Http\Controllers\BasicPlatform\
  - app\Services\BasicPlatform\
  - resources\views\basicPlatform\
- 企業DB　ソースファイル設置ディレクトリ
  - app\Http\Controllers\CompanyDashboard\
  - app\Services\CompanyDashboard\
  - resources\views\companyDashboard\
- ポータル　ソースファイル設置ディレクトリ
  - app\Http\Controllers\Portal\
  - app\Services\Portal\
  - resources\views\portal\
- マイページ　ソースファイル設置ディレクトリ
  - app\Http\Controllers\Mypage\
  - app\Services\Mypage\
  - resources\views\mypage\

また、URLもシステムごとに分割する。

- 基本PF
  - https://【URL】/basic_platform/xxxxx
- 企業DB
  - https://【URL】/company_dashboard/xxxxx
- ポータル
  - https://【URL】/portal/xxxxx
- マイページ
  - https://【URL】/mypage/xxxxx
- URL分割
  企業ユーザ(企業管理者・従業員)が利用する企業DB、ポータルにおいて、ログインURLは企業ごとに発行する。ビューファイルは同一。
  発行するURLは以下の形式とする
  - 企業DB
    https://【URL】/company_dashboard/企業コード+ランダムに生成された8桁の半角英数字（固定）
  - ポータル
    https://【URL】/portal/企業コード+ランダムに生成された8桁の半角英数字（固定）
  - マイページ
    https://【URL】/mypage/企業コード+ランダムに生成された8桁の半角英数字（固定）

## DB操作

### 【重要】DB操作はModelクラスでのみ実行する事

企業IDチェックは継承元のModel.phpにて定義されている。
既存のテーブルを利用する機能の開発時は実装者が特に対策する必要なし。
新規でテーブルを作成した場合は、上記リンクに記載されている方法で実装すること。
SQLの関数を利用した特殊なデータ取得を行いたい場合は、WHERE句に企業IDを必ず入れている事、第三者にSQLのレビューを実施することを徹底すること。

### 論理削除について

削除フラグ(deleted_flg)を各テーブルに作成し、フラグONか否かで論理削除を実現している。
更新日時(updated_at)は、論理削除時にシステム日付を設定する。

| 画面操作 | データ状態 | updated_at | deleted_flg |
|---|---|---|---|
| 登録 | 有効 | 登録した日時 | 0 |
| 更新 | 有効 | 変更した日時 | 変更しない |
| 削除 | 無効 | 削除した日時 | 1 |

#### 【共通仕様】論理削除されたデータは取得不可

論理削除で無効となったデータは基本画面に表示させません。
WBPシステムでは、Modelクラスの共通処理で WHERE句に「deleted_flg = 0」を追加し取得不可 としている。
共通仕様は、Modelクラスの共通処理「booted()のaddGlobalScope」。
全てのModelクラスは継承している為、全てのSQL実行に直接(static)呼び出すことができる。
メインとなるテーブルに対して、Modelクラスの 共通処理でWHERE句に「deleted_flg = 0」を追加し取得不可 としている。
過去データの参照など、論理削除されているデータの名称などを特別に表示したい場合は、共通処理でWHERE句に「deleted_flg = 0」を追加させない。
また、Laravelのリレーション機能をEloquentのwith関数で、子テーブルの情報呼び出した場合も、 共通処理でWHERE句に「deleted_flg = 0」を追加し取得不可 としている。

```php
// app\Models\Model.php
protected static function booted()
{
  static::addGlobalScope(new CompanyFilterScope);
  static::addGlobalScope(new DeletedAtScope);
}
```

```php
// app\Scopes\DeletedAtScope.php
public function apply(Builder $builder, Model $model)
{
  $builder->where('deleted_flg', '=', 0);
}
```

##### 使用例

例）自社スタッフ(staff)テーブルから特定IDのデータを取得

```php
$staff = Staff::find($request->id);
```

※上記の実装のみで、実行されるSQLは以下の通りとなる
「SELCT * FROM staff where company_id = 【ログインした自社スタッフが所属する企業ID】 and delete_flg = 0 and id = 【$request->id】」

#### 【共通機能】論理削除されたデータ参照

過去の履歴など既に終了、削除したデータを参照したい場合、「deleted_flg = 1」も取得対象とする共通機能がある。
以下、Modelクラスの共通処理「scopeWithTrashed()」。
全てのModelクラスは継承している為、全てのSQL実行前に直接(static)呼び出すことで対応できる。
利用する際は、「scope」は省略され「withTrashed()」となる。

##### 使用例

例）単品：自社スタッフ(staff)テーブルから特定IDのデータを取得

```php
$model = Staff::withTrashed();
$staff = $model->find($request->id);
```

※上記の実装のみで、実行されるSQLは以下の通りとなる
「SELCT * FROM staff where company_id = 【ログインした自社スタッフが所属する企業ID】 and id = 【$request->id】」

例）副問合せ：自社スタッフ(staff)テーブルから特定IDのデータを取得

```php
$joinAssist = [
   'relation' => 'user',
   'compare_to_rel' => [ ['from_req' => 'sender',
                     'from_rel' => 'name',]
                  ],
   'date_field_on_table' => 'post_start_date',


];
$data = ['rows' => []];
$dataAc = News::search($request, $joinAssist)->get();
```

※上記の実装のみで、実行されるSQLは以下の通りとなる
「SELCT * FROM staff where company_id = 【ログインした自社スタッフが所属する企業ID】 and id = 【$request->id】」

例）with関数：企業テーブルの子テーブル「企業_部署」を取得する

```php
// withを用いて、関連するPostContentsも一緒に取得する.
$post = Company::with('contents')->find(1);
```

※上記の実装のみで、実行されるSQLは以下の通りとなる
「SELCT * FROM staff where company_id = 【ログインした自社スタッフが所属する企業ID】 and id = 【$request->id】」

#### 【共通機能】削除する

以下、Modelクラスの共通機能「delete()」。
全てのModelクラスは継承している為、モデル生成後に呼び出すことで対応できる。

```php
public function delete()
{
  if ($this->relation_names) {
      foreach ($this->relation_names as $relation) {
         $this->$relation()->delete();
      }
  }
  return $this->update(['deleted_flg' => 1]);
}
public function update($attributes = [], $options = [])
{
  try {
      parent::update(array_merge($attributes, ['version' => $this->version + 1]), $options);
      $status = [true, '更新ができました。'];
  }
  catch (\Exception $e) {
      $status = [false, $e->getMessage()];
  }
  session()->forget('version');
  return $status;
}
```

##### 使用例

例）自社スタッフ(staff)テーブルから特定IDのデータを論理削除する

```php
$model = Staff::find($request->id);
[$success, $message] = $model->delete();
if (!$success) {
    // DB更新に失敗時の処理「$message」にはエラーメッセージが入ってます
}
```

※上記の実装のみで、実行されるSQLは以下の通りとなる
「UPDATE staff set delete_flg = 1 where company_id = 【ログインした自社スタッフが所属する企業ID】 and id = 【$request->id】」

### 排他制御について

本システムでは、排他制御は楽観的ロックを採用する。
排他制御が必要な全てのテーブルにバージョン管理用カラム(物理名：version)を追加。
紐づくModelクラスにも「fillable」のプロパティーに「version」のフィールドを追加する。

```php
class Administrator {
   protected $fillable = [
      'version',
      // 他のプロパティー
   ];
}
```

共通model(Model.php)に画面を開いた時に取得したversionと更新処理実行時のバージョンを比較する機能を作成している。

・Model.php

```php
// 画面を開いた際にversionを取得してセッションに保存
public function rememberVersion() {
   $key = $this->getTable()."-".$this->id;
   if (!session()->get(config('session.keys.versions'))) {
       session([config('session.keys.versions') => []]);
   }
   $versions = session()->get(config('session.keys.versions'));
   $versions[$key] = $this->version;
   session()->put(config('session.keys.versions'), $versions);
}

// セッションに保存したバージョンと現在のバージョンを比較
public function isDifferentVersion()
{
   $key = $this->getTable() . "-" . $this->id;
   return request()->getMethod() !== 'POST'
      && session()->get(config('session.keys.versions'))
      && isset(session()->get(config('session.keys.versions'))[$key])
      && $this->version !== session()->get(config('session.keys.versions'))[$key];
}
```

各機能でupdateやdeleteを使用した場合は上記の機能が作動し、結果が配列でリターンされる
結果で対応を変えること。
【例】

```php
// 編集の場合
[$success, $message] = $admin->update($data);
// 削除の場合
[$success, $message] = $admin->delete();
```

・成功時のメッセージ内容
- success - true
- message - 成功のメッセージ

・失敗時のメッセージ内容
- success - false
- message - エラーのメッセージ

### マルチテナントについて

本システムは複数の企業が利用するが、全ての企業のデータを同一DB・同一スキーマ、同一テーブルで管理している。
その為、データやアップロードファイルの混濁を防ぐ事が最重要課題となる為、以下のルールを徹底する。
企業DB、ポータルにおいて、混濁してはならないデータを格納した全てのテーブル(関連テーブル含む)に「企業ID」カラム(企業テーブル外部キー)を入力必須で設置して、データにアクセスしようとするユーザをチェックする。
複数のテーブルを結合してデータを参照する場合も、メインテーブルだけでなく結合先の全てのテーブルのWhere条件に企業IDを追加して厳重にチェックする。

#### 【共通仕様】Modelクラスによるマルチテナントの漏洩防止

WBPシステムでは、企業ダッシュボード、従業員ポータル、マイページに ログインしたユーザが所属する企業と異なる他企業のデータは参照できません。
Modelクラスの共通処理で WHERE句に「company_id= 【ログインユーザ所属の企業ID】」を追加し取得不可としている。
共通仕様は、Modelクラスの共通処理「booted()のaddGlobalScope」。
全てのModelクラスは継承している為、全てのSQL実行に直接(static)呼び出すことができる。

```php
// app\Models\Model.php
protected static function booted()
{
  static::addGlobalScope(new CompanyFilterScope);
  static::addGlobalScope(new DeletedAtScope);
}
```

基本プラットフォームは、全企業に対してアクセス可能となる為、下記の部品から除外されている。

```php
// app\Scopes\CompanyFilterScope.php
public function apply(Builder $builder, Model $model)
{
  $target_platforms = ['company_dashboard', 'portal'];
  $uri = explode('?', request()->getRequestUri())[0];

    $is_target_platform = in_array(explode('/', $uri)[1], $target_platforms);
    if ($is_target_platform && !in_array($model->getTable(), config('scopes.tables'))) {
        $builder->where('company_id', '=', Auth::user()->company_id);
    }
}
```

上記の共通処理でメインテーブルだけでなく結合先の全てのテーブルのWhere条件に追加し、他企業のデータを参照不可とする。
基本的に全てのテーブルには、「company_id」を含めている為、副問合せ(Join)による結合先のテーブルは、他企業のデータを参照不可とする。
それ以外の「company_id」を保持していないテーブルについては、「scope.php」で管理している。

```php
// config\scopes.php
<?php
return [
   'GET' => [
      '/company_dashboard/company_offices',
      '/company_dashboard/company_departments',
      '/company_dashboard/users',
      '/company_dashboard/employees',
      '/company_dashboard/notices',
      "/company_dashboard/users/create",
      "/company_dashboard/users/confirm",
      "/company_dashboard/users/{id}/edit"
   ],
   'POST' => [
      "/company_dashboard/users"
   ],
   'PUT' => [
      "/company_dashboard/notices/{id}"
   ],
   'PATCH' => [],
   'DELETE' => [],
];
```

##### 使用例

例）企業ダッシュボードで企業_部署(company_departments)からデータを取得する場合

```php
CompanyDepartment::all();
// '/company_dashboard/company_departments'のGETでデータ取得すると
// リクエストしたURLは「scope.php」で管理されている為、以下のクエリーになる。
// SELECT * FROM company_departments WHERE company_id = ? AND deleted_flg = 0
// 現在ログインされているユーザーの企業IDは「？」の代わりに入ります。
```

#### 【共通仕様】MiddlewareでURLに直接入力の権限チェック

URLに直接入力で別テナント情報へのページアクセスできないようにMiddlewareで制御を行う
共通仕様は、Middlewareクラスの共通処理「URLDirectAccess」。
Laravelのrouteを定義する際に、利用する。
現在、全ての画面遷移には、Middlewareクラスの共通処理「URLDirectAccess」の配下に記載している。
以下、使用例をご確認ください。

##### 使用例

```php
// ログイン認証後
Route::middleware('auth:front', 'URLDirectAccess')->group(function () {
    Route::get('/', 'HomeController@index')->name('home'); // Top画面遷移
});
```

### 初期データについて

初期データは初期データ定義書で管理する。
(git)データ管理/初期データ定義書.xlsx
なお、初期データの投入はseederにて管理・実行する。(直接SQLでInsertは行わない)

## UI・ファイルなど

### セッション

当システムでは、Laravelのセッションを利用する。
cookieは、使用しない方針。

#### Laravelセッションcookieのキー名

WBPシステム内のログインは、4つ存在するため、Laravelのセッションを４つに分かれて設定している。
それぞれのセッションキーを下記の通りとする。
ログイン後、ChromeデベロッパーツールのApplicationタブからCookieを確認できる。

| システム | アクセスURL | cookieのキー名 | メモ |
|---|---|---|---|
| ①基本プラットフォーム | https://ドメイン/basic_platform | wbp_basic_platform_session | |
| ②企業ダッシュボード | https://ドメイン/company_dashboard | wbp_company_dashboard_session | |
| ③従業員ポータル | https://ドメイン/portal | wbp_portal_session | |
| ④マイページ | https://ドメイン/mypage | wbp_mypage_session | |

#### セッションの有効期限

下記ファイルに分単位で定義している。現在は、 120分 で設定。

- config\session.php

```php
'lifetime' => env('SESSION_LIFETIME', 120),
```

- .env

```
SESSION_LIFETIME=120
```

##### セッションタイムアウト時

セッション有効期限が切れた場合、セッションタイムアウトとなる。
セッションタイムアウト時は、ログイン画面に遷移し、タイムアウトエラーメッセージを表示させます。
セッションタイムアウトの判定は、Middlewareで設定する。

###### セッションタイムアウトエラーメッセージ

エラーメッセージは、ログイン画面で表示する。

###### セッションタイムアウト時の処理を実装（Authenticate）

WBPシステムは、マルチログインで認証される為、Middlewareで各ログイン毎のリダイレクト先をGuard(認証)別で指定する

src\app\Http\Middleware\Authenticate.php
Middleware設定なので、内容は省略

#### Laravelでsessionを利用する方法

セッションにはデータ（キーと値のペア）を保存できる。
Laravelでセッションを操作するには３つの方法がある。
Requestインスタンス、sessionヘルパ、Sessionファサードの３つの方法。

##### 例）セッションへデータを保存する

```php
$request->session()->put('key', 'value');
$request->session()->put(['key1' => 'value1', 'key2' =>   value2']);
```

##### 例）指定したデータをセッションから取得する

```php
// 指定したデータがセッションに存在するかを調べる
if ($request->session()->exists('key')) {
    // 存在する
}
if ($request->session()->has('key')) {
    // 存在しnullではない
}
// セッションから取得する
$value = $request->session()->get('key );
// キーが存在していない場合に返すデフォルト値を第2引数に指定できる
$value = $request->session()->get('key', 'default );
$value = $request->session()->get('key', function () {
    return 'default';
});
// セッション中の全データを取得する
$data = $request->session()->all();
```

##### 例）セッションからデータ削除

```php
// 指定したデータをセッションから取得後、そのデータを削除する
$value = $request->session()->pull('key', 'default );
// 指定したデータをセッションから削除する
$request->session()->forget('key');
// セッションから全データを削除する
$request->session()->flush()
```

例えば、ユーザーは「/company_dashboard/users/12/edit」のURLを開く場合はセッションで下記の情報は保存される。

```php
>> session->get('page_info')
>> [
     'code' => 3,
     'platform' => 'company_dashboard',
     'function_no' => '018',
     'page_no' => 102
 ]
```

#### WBPシステム内のセッションで保持している情報

「session()->get('セッションKey')」で既にプロジェクトで利用している情報。
設計書の「【設計】SESSION情報.xlsx」にWBPシステムで設定したセッション情報をまとめている。

### アップロードファイル混濁防止対策

企業ユーザがアップロードしたファイルなど、企業に紐づくファイルは全て企業ごとに作成したディレクトリ配下に格納する。
権限を持たないユーザや部外者がファイル格納先のURLを直でアクセスしようとした場合、エラーページに遷移する。
ディレクトリ構成は、以下の資料に記載している。
「基本設計/【基本設計】ディレクトリマップ.xlsx」
ソース上は、「Directory.php」の中にEnum型で用意している。
対策として、共通部品の「FileUploadTrait」、「Directory」を利用する。
共通部品内で、各プラットフォーム毎のアクセス方法を実行している。
全てのファイルアップロードの処理は上記の共通部品を利用してアップロードすること。

app\Http\Traits\FileUploadTrait.php ファイル操作共通部品
app\Http\Traits\Directory.php       ファイル格納先ディレクトリパス共通部品

| プラットフォーム | アクセス先 | アクセス方法 |
|---|---|---|
| 基本プラットフォーム | 共有ディレクトリ(shared) | 対象ファイルのID、Directoryを利用した各機能のディレクトリパスを指定する |
| 基本プラットフォーム | 企業ディレクトリ(company) | 対象の企業IDとファイルID、Directoryを利用した各機能のディレクトリパスをを指定する |
| 企業ダッシュボード | 共有ディレクトリ(shared) | 対象ファイルのID、Directoryを利用した各機能のディレクトリパスをを指定する |
| 企業ダッシュボード | 企業ディレクトリ(company) | 対象ファイルID、Directoryを利用した各機能のディレクトリパスをを指定する。※1 |
| 従業員ポータル | 共有ディレクトリ(shared) | 対象ファイルのID、Directoryを利用した各機能のディレクトリパスをを指定する |
| 従業員ポータル | 企業ディレクトリ(company) | 対象ファイルID、Directoryを利用した各機能のディレクトリパスをを指定する。※1 |
| マイページ | 共有ディレクトリ(shared) | 対象ファイルのID、Directoryを利用した各機能のディレクトリパスをを指定する |
| マイページ | 企業ディレクトリ(company) | 対象ファイルID、Directoryを利用した各機能のディレクトリパスをを指定する。※1 |

※1 共通部品の中で、セッションに保持されているログインユーザの所属する企業IDを元にアクセスするディレクトリを決めている。

#### 使用例 ■画像ファイルのアップロード

①クラスの中でFileUploadTraitとDirectoryをuseする。
②ファイルアップロードイベント(method)の中で、DB登録を行う。※2
③DB登録に成功後に、FileUploadTraitのsaveImgFilesを利用しファイルアップロードする。
④アップロードしたイメージファイルが既に存在している場合は、元ファイルを削除し、新しいファイルを配置する。
格納先ディレクトリ：資料「【基本設計】ディレクトリマップ.xlsx」を参照
※2 基本プラットフォーム以外は、他社企業の情報を閲覧禁止の為、DB取得の中で企業IDチェックが行われます、他社IDを指定している場合、404エラーとなる

例）app\Http\Controllers\BasicPlatform\MaintenanceTermsController.php

```php
use \App\Http\Traits\FileUploadTrait;
use App\Http\Traits\Directory;
public function store(Request $request) {
  $term = Term::create($request->input());
  $this->saveImgFiles($request->detail, Directory::TERMS->path(), $term->id);
  return $this->toEditPage($term->id);
}
```

#### 使用例 ■画像以外のファイルのアップロード

①クラスの中でFileUploadTraitとDirectoryをuseする。
②ファイルアップロードイベント(method)の中で、DB登録を行う。※2
③DB登録に成功後に、FileUploadTraitのsaveImgFilesを利用しファイルアップロードする。
※注意：既に同じ名前のファイルがアップロードされている場合は、元ファイルを削除し、新しいファイルを配置する。
格納先ディレクトリ：資料「【基本設計】ディレクトリマップ.xlsx」を参照
※2 基本プラットフォーム以外は、他社企業の情報を閲覧禁止の為、DB取得の中で企業IDチェックが行われます、他社IDを指定している場合、404エラーとなる

例）app\Http\Controllers\BasicPlatform\MaintenanceManualController.php

```php
use \App\Http\Traits\FileUploadTrait;
use App\Http\Traits\Directory;
public function store(Request $request) {
  $manual = Manual::create($data);
  if ($request->pdf) {
      $manual->pdf = $this->saveFile($request->file('pdf'), Directory::MANUALS->path(), $manual->id, 1);
  }
  if ($request->movie) {
      $manual->movie = $this->saveFile($request->file('movie'), Directory::MANUALS->path(), $manual->id);
  }
  $manual->save();
}
```

#### 使用例 ■画像以外のファイルの再アップロード（更新）

元ファイルを削除し、新しいファイルを配置する。
①クラスの中でFileUploadTraitとDirectoryをuseする。
②ファイルアップロードイベント(method)の中で、DB更新を行う。※2
③DB更新に成功後に、FileUploadTraitのupdateFileを利用しファイルアップロードする。
④アップロードしたイメージファイルが既に存在している場合は、元ファイルを削除し、新しいファイルを配置する。
格納先ディレクトリ：資料「【基本設計】ディレクトリマップ.xlsx」を参照
※2 基本プラットフォーム以外は、他社企業の情報を閲覧禁止の為、DB取得の中で企業IDチェックが行われます、他社IDを指定している場合、404エラーとなる

例）app\Http\Controllers\BasicPlatform\MaintenanceManualController.php

```php
use \App\Http\Traits\FileUploadTrait;
use App\Http\Traits\Directory;
public function update(Manual $manual, Request $request) {
  $request->validate($this->rules);
  [$success, $message] = $manual->update($request->input());
  if (!$success) {
      return $this->toEditPage($manual->id, $success, $message, "/basic_platform/maintenance/manuals");
  }
  if ($request->pdf) {
      $manual->pdf = $this->updateFile(
         $request->file('pdf'),
         Directory::MANUALS->path(),
         $manual->id,
         $manual->pdf,
      );
  }
  if ($request->movie) {
      $manual->movie = $this->updateFile(
         $request->file('movie'),
         Directory::MANUALS->path(),
         $manual->id,
         $manual->movie,
      );
  }
  $manual->save();
  return $this->toEditPage($manual->id, $success, $message, "/basic_platform/maintenance/manuals");
}
```

#### 使用例 ■ファイルの削除

部品の中で、ログインユーザが所属する企業ID内に一致するファイルを削除する。
他企業のファイルは削除できません。
①クラスの中でFileUploadTraitとDirectoryをuseする。
②ファイル削除イベント(method)の中で、DB更新を行う。※2
③DB更新に成功後に、FileUploadTraitのdeleteFileを利用しファイル削除する。
格納先ディレクトリ：資料「【基本設計】ディレクトリマップ.xlsx」を参照
※2 基本プラットフォーム以外は、他社企業の情報を閲覧禁止の為、DB取得の中で企業IDチェックが行われます、他社IDを指定している場合、404エラーとなる

例）app\Http\Controllers\BasicPlatform\MaintenanceManualController.php

```php
use \App\Http\Traits\FileUploadTrait;
use App\Http\Traits\Directory;
public function destroy(Manual $manual) {
  [$success, $message] = $manual->delete();
  if (!$success) {
      return $this->toEditPage($manual->id, $success, $message, "/basic_platform/maintenance/manuals");
  }
  $this->deleteFile(Directory::MANUALS->path(), $manual->id, $manual->pdf);
  $this->deleteFile(Directory::MANUALS->path(), $manual->id, $manual->movie);
  $this->deleteDirectory('manuals', $manual->id);
  return redirect('/basic_platform/maintenance/manuals');
}
```

#### 使用例 ■ファイル参照

部品の中で、ログインユーザが所属する企業ID内に一致するファイルがあれば取得する。
取得に失敗した場合、404エラーとすること。
①クラスの中でFileUploadTraitとDirectoryをuseする。
②ファイル取得イベント(method)の中で、DB取得を行う。※2
③DB取得に成功後に、FileUploadTraitのgetFullFilePathを利用しファイル取得する。
格納先ディレクトリ：資料「【基本設計】ディレクトリマップ.xlsx」を参照
※2 基本プラットフォーム以外は、他社企業の情報を閲覧禁止の為、DB取得の中で企業IDチェックが行われます、他社IDを指定している場合、404エラーとなる

例） app\Http\Controllers\CompanyDashboard\ManualController.php

```php
use \App\Http\Traits\FileUploadTrait;
use App\Http\Traits\Directory;
public function getPdf(Request $request, $id)
{
  $manual = Manual::find($id);
  if (!$manual) {
      abort(404);
  }
  // Get the path to the PDF file
  $path = $this->getFullFilePath(Directory::MANUALS->path(), $manual, 'pdf');
  // Check if the file exists
  if (!file_exists($path)) {
      abort(404);
  }
  // Return the file as a response with the appropriate headers
  return response()->file($path, [
      'Content-Type' => 'application/pdf',
      'Content-Disposition' => 'inline; filename="'.$manual->pdf. "'",
  ]);
}
```

### GET／POSTの使い分けのルール

編集画面・削除機能のURLについてはLaravelの仕様上GETで該当データのIDをリクエストするが、内部のデータは全てPOSTで統一。
POSTで対応できない場合はセッションを利用する。

### 二重リクエスト防止

リクエスト送信直後にボタンをフェードアウトし、ボタンやリンクを再度クリックできないようにする。
本システムでは、登録・更新・削除を行う際はモーダルで確認画面を表示させる為、確認画面でOKを押したタイミングでボタンをが無効となる。
処理はpublic\jsの「formControls.js」、「formControlsProd.js」に定義されている

formControls.js

```javascript
function submitForm(event) { event.preventDefault(); event.target.disabled = true; //
console.log(document.forms); document.user_form.submit();}
```

呼び出し例

```html
<button onclick="submitForm(event)" class="btn btn-primary border btn-m">確定</button>
```

実装の際に、既存で用意されている共通モーダルを利用する場合は上記処理が実装されている為、特に対策する必要なし。
機能独自の処理で共通モーダル以外を使用する必要が発生した場合は、管理者に報告したうえで、上記の要領で実装する。
※課題のコメント欄に対応内容を記載すること

### 定数について

定数は種類によってコードテーブルで管理するものとconfig配下で管理するものを分ける
・コードテーブル(codes)で管理
 ⇒セレクトボックス等、画面で使用する値を管理する。
・config配下で管理
 ⇒システムコード。複数画面に影響があるものと単体機能で使用するものでファイルを分ける

### メッセージについて

- エラーメッセージ
  エラーメッセージはLaravel既存の以下のvalidationファイルを利用する。
  resources\lang\ja\validation.php
  メッセージの内容も基本的に既存のものを利用する。
  追加したいメッセージはファイルに追記・もしくは同階層に新規ファイルを作成して対応する。
- 共通メッセージ
  Laravelではエラーメッセージ以外の共通メッセージを管理するするファイルが無い為、以下のフォルダに新規作成して管理する。
  resources\lang\ja\

### メール送信

以下のファイルにメール内容記載
(git)基本設計/メール機能.xlsx

### システログ仕様

システムログの基本的な仕様は以下を参照
(git)基本設計/システム共通機能/システムログ仕様.xlsx

### 操作ログ登録仕様

操作ログの基本的な仕様は以下を参照
システムログの基本的な仕様は以下を参照
(git)基本設計/システム共通機能/操作ログ仕様.xlsx

## セキュリティ対策

### SQLインジェクション

#### 概要

Laravelは自由度が高く、クエリビルダからだけでなくDBクラスによる操作も可能となっており、この場合は上記のSQLインジェクション対策の対象外となる。
なので、データ操作は 必ずクエリビルダを使用し、DBクラスによる操作は基本禁止とする。

#### OK例：Laravelを利用

Laravelはパラメーターバインディングによって、データがコマンドとして認識されないようになっており、DBをクエリビルダから操作する場合は対策は不要。
参考：https://readouble.com/laravel/8.x/ja/queries.html
入力パラメータ⇒'5; delete from posts'

```php
public function store(Request $request)
 {
   // リクエスト取得
   $id = $request->query('id '); ← '5; delete from posts'がidに入る
   $posts = DB::table('posts')->where("id", $id)->get();
}
```

上記の処理を実行した場合、以下のSQL文の実行となる

```sql
select * from posts where id = '5 delete from posts';
```

postsテーブルの取得SQL実行で、一致するidが無ければ0件となる。

#### NG例：文字列結合でパラメータを利用する（whereRaw）

ControllerでDBクラスでデータを操作してSQLインジェクションが発生したケースの例
入力パラメータ⇒'5; delete from posts'

```php
public function store(Request $request)
 {
   // リクエスト取得
   $id = $request->query('id '); ← '5; delete from posts'がidに入る
   $posts = DB::table('posts')->whereRaw("id = ${id}")->get();
}
```

上記の「whereRaw」を利用した処理を実行した場合、以下のSQL文の実行となる

```sql
select * from posts where id = 5;
delete from posts;
```

postsテーブルの取得SQL実行後に、postsテーブルの データが全て削除される

### クロスサイトスクリプティング（XSS）

#### 概要

XSS対策のポイントは「特殊文字(< や > や " など)をエスケープする」こと。
そうすることでスクリプトではなく、単なる文字列として扱うことになるため、意図しない処理を防ぐことができる。
エスケープするためにはhtmlspecialchars関数を用いる。
参考：https://qiita.com/4649rixxxz/items/d31ecb33fcba1bffcb90
Laravelの場合、{{ }}文を使用する事でPHPのhtmlspecialchars関数を自動的に通る。
viewファイル(.blade.php)で変数を使用する場合は、基本的に{{ }}文を使用する。

#### OK例：特別な理由が無い限りはこの形で実装する

```blade
{{ $script }} // エスケープされる
```

#### NG：基本的に使用禁止

```blade
{{!! $script !!}} // エスケープされない
```

### OSコマンドインジェクション

#### 概要

ウェブサーバーへのリクエストにOSへのコマンド（命令文）を紛れ込ませ、不正に実行させる攻撃によりサーバー内のファイルの閲覧、改ざん、削除、不正操作などの被害を防止。
本システムでは、Webアプリケーションを経由してOSコマンドを実行する処理は無い。また、バッチが実行するOSコマンドは限定されたコマンドのみである。

#### NG：脆弱性のあるスクリプトの例

1行目でユーザーが指定した送信先アドレス「$to_address」を受け取り、
2行目で本文の内容「$message_file」としてテキストファイル「test.txt」を指定し、
3行目でsendmailコマンドを利用して受け取った送信先アドレスに向けてtest.txtの内容をメールしている。

```
1：$to_address = cgi->param{'to_address'};
2：$message_file = "/app/data/test.txt";
3：system("sendmail $to_address <$message_file");
```

問題点はユーザーから受け取った送信先アドレスをチェックしていない点。
そのままsendmailコマンドのパラメータとして利用している。
送信先アドレス「$to_address」に「example@example.com </etc/passwd #」と指定した場合が問題となる。
「/etc/passwd」はサーバー内に保存されているパスワードリストファイル。
3行目のコマンドは「system("sendmail example@example.com </etc/passwd #<$message_file");」となる。
本来は「$message_file」の内容（test.txt）がメール送信されるところを、サーバー内の「/etc/」に保存されているテキストファイル「passwd」が送信され、パスワードの漏えいが発生するという仕組み。
「#」は「これ以降のコマンドは無視する」の意味なため、「<$message_file」は無効となる。
同様に、消去コマンド「rm -rf」を使用すればファイルの削除、URLを指定してファイルをダウンロードする「wget」を使用すれば、サーバーへの悪意あるファイルのダウンロードが可能。

#### NG：Webアプリケーションが外部プログラムを呼び出す関数などを使用しない

フォームなどに入力された情報をパラメータとして受け渡し、シェルや他のプログラムを呼び出して実行するよう設計している場合、攻撃パターンを含む入力データによりOSコマンドが不正に実行されてしまいる。

#### NG：入力された情報をそのままシェルや他のプログラムに受け渡さない

入力された情報をチェックせず、そのまま受け渡すような設計になっている場合、入力情報に攻撃パターンが含まれていてもそのままシェルや他のプログラムに送られ、結果としてOSコマンドが実行されてしまいる。

#### OK例：エスケープ処理、サニタイジングを行う

入力データに含まれる「;」「¦」「&」「<」等の危険な文字を無害な文字に置き換える処理も有効。
これはエスケープ処理、サニタイジングとも呼ばれる処理で、PHPではhtmlentities()、Perlではquotemeta()関数、Pythonではbleachというライブラリが用意されている。

### パス／ディレクトリトラバーサル

#### 概要

ディレクトリトラバーサルとは、Webサーバーの非公開ファイルにアクセスを行う攻撃手法。
一般に利用を許可していないファイルを勝手に呼び出して参照・利用する不正行為。
閲覧可能な公開ファイルが存在するディレクトリから、非公開ファイルのあるディレクトリ階層に「横断する（トラバーサル）」かのように移動して不正にファイルを閲覧することに由来する。

#### OK例：アプリで絶対パス指定する

```php
$path = storage_path("app/public/manuals/$manual->id/$manual->pdf");
```

#### NG：Webアプリケーションがアクセス可能なOS上のファルダを限定する。

ファイルへのアクセスに際しては固定ディレクトリを指定させ、ファイル名の指定において相対パスが入りこまない設定とすべき。

①パラメータの外部入力によるファイル指定機能の実装回避
②相対パスが入り込まないファイル参照設定

①：cgi->param{'file_path'};
②：../../deta/test

### バッファオーバーフロー

Webサーバーのメモリ上で確保されたある領域（バッファ）に対して、その大きさ以上のデータを書き込みを禁止する。
対策として、アプリケーションとしてはINPUT項目に対して、桁数チェックを行い、上限を超えたデータ書き込み防止する。

#### メモリ設定

Webサーバーのメモリ上限を確認し、サーバーの容量などを考慮してphp.iniなどで設定する。

```ini
;メモリ使用量の上限
memory_limit = 20M
;POSTデータの最大サイズ ※※1回のアップロードファイルすべての合計サイズ
post_max_size = 20M
;1ファイルあたりの最大アップロードサイズ
upload_max_filesize = 20M
```

#### INPUT項目（DBサイズ）

文字入力、数値入力の項目については、VARCHAR(10)、CHAR(10)、int(10)など桁数を必ず設定した項目となるので問題ないが
TEXT型項目については、各DBにより最大サイズが違うので、それぞれのDBの最大サイズを超えないようにチェックを入れる。

■MySQL
最大長は「65,535バイト(約64MB）」。なので「半角だと65,535字」「全角だと半分の32,767字」。
※UTF-8の場合は1字が最大3バイトなので最小で21,845字

■PostgreSQL
最大長は「1,073,741,824バイト(1024MB)」。なので「半角だと536,870,912字」「全角だと半分の268,435,456字」。 ※UTF-8の場合は1字が最大3バイトなので最小で357,913,941字

#### ファイルアップロード

### パラメータ改ざん

Webアプリケーションに対するリクエストをHTTPS化。
アプリケーションにてCookieをセキュア化している。

#### XSSの対策

対策はただひとつで、 全ての危険な変数の出力をhtmlspecialchars() 関数で、エスケープする こと。
「危険な変数」とは、$_GET や $_POST、あるいはこれらを代入した変数全てを含みます。
$_COOKIE も、ユーザが変更できる可能性があるので、同様。
「出力」とは、echo文やprint文、sprint関数などの全て。

#### OK例：htmlspecialchars() 関数を利用

「resources\views\」配下のView

```php
<h2><?php echo htmlspecialchars($_GET['keyword'], ENT_QUOTES); ?>の検索結果</h2>
```

#### NG例：直接出力

「resources\views\」配下のView

```php
<h2><?php echo $_GET['keyword']; ?>の検索結果</h2>
```

### CSRF防止

Laravelの場合、formタグに@csrfを記述する（@csrfがないformをsubmitした場合は419エラーになる為、対策は必要なし）
・記述例

```blade
<form action="" method="POST">
   @csrf
</form>
```

### 二重リクエスト防止

リクエスト送信直後に画面をフェードアウトし、ボタンやリンクを再度クリックできないようにする。

### ボットによる不正ログイン防止

2要素認証(基本P/F)、もしくはGoogleリキャプチャによる「人による認証」を通過させるようにする。

### 認可制御

ログインユーザーの権限によって、使用できる機能を制限する。
LaravelのMiddlewareで権限チェッククラスを用意してます。
権限チェッククラスで、アクセスしたユーザ情報の権限を判定して制御を行う。

#### 権限エラー時

アクセス不正のエラーページに遷移する。
エラーページには、ログイン画面に遷移できるリンクを用意する。

#### 1. Kernelクラスの「$routeMiddleware」に4システム毎の権限チェッククラスを登録

| システム | Middlewareのキー | 使用用途 |
|---|---|---|
| ①基本プラットフォーム | auth.basic | 基本プラットフォームへのアクセス時に認可制御する |
| ②企業ダッシュボード | auth.admin | 企業ダッシュボードへのアクセス時に認可制御する |
| ③従業員ポータル | auth.employee | 従業員ポータルへのアクセス時に認可制御する |
| ④マイページ | auth.maypage | マイページへのアクセス時に認可制御する |

```php
// app\Http\Kernel.php
protected $routeMiddleware = [
   'auth' => \App\Http\Middleware\Authenticate::class,
   'auth.basic' =>\App\Http\Middleware\AuthorizeBasic::class,
   'auth.admin' => \App\Http\Middleware\AuthorizeAdmin::class,
   'auth.employee' => \App\Http\Middleware\AuthorizeStaff::class,
   'auth.maypage' => \App\Http\Middleware\AuthorizeMypage::class
];
```

#### 2. Route設定クラスに、4システムアクセス時の権限チェックを付与する

```php
// routes\web.php
Route::group(['middleware' => ['auth.basic', 'can:view-basic-platform-items']],
__DIR__.'/web/adminRoutes.php');'
Route::group(['middleware' => ['auth:admin', 'can:view-company-dashboard-items']],
__DIR__.'/web/company_dashboard.php');
Route::group(['middleware' => ['auth.employee', 'can:view-employee-items']], __DIR__.'/web/portal.php');
Route::group(['middleware' => ['auth.mypage', 'can:view-mypage-items']], __DIR__.'/web/mypage.php');
```

#### 3.権限チェッククラスに、セッションのユーザ情報に保持されている権限で判定を行う

例）企業ダッシュボードにアクセスした場合

```php
// \App\Http\Middleware\AuthorizeAdmin.php
public function handle(Request $request, Closure $next)
{
  if (!Gate::allows('view-company-dashboard-items')) {
      abort(403);
  }
  return $next($request);
}
```

### セッションフィクセイション防止　※追加

ログイン直後にセッションIDを変更する。
⇒Larvelではログインごとに新規でセッションを作成する為、対策は必要なし。

### ファイル形式チェック　※追加

画像、PDFファイルアップロードの際に、拡張子のみでなく、ファイルが画像またはPDFファイルとして読み込めるかどうかをチェックし、不正なスクリプト等がアップロードされるのを防ぐ。

#### チェック１：拡張子

ファイルの拡張子に関して、画面側(view)とサーバー側(laravel)で2重で制御する。

##### 画面側(view)で制御

でファイルを選択できるが、この時選択できるファイルの種類(拡張子)を制御するには、accept 属性で拡張子もしくはMIMEを設定する。

```html
<!-- 拡張子 .pdf -->
<input type="file" accept=".pdf">
<!-- MIMEでワイルドカード指定(画像ファイル) -->
<input type="file" accept="image/*">
```

## リレーション管理

### リレーションの作成

リレーションの作成をするときループ処理で一つずつ作る考え方はよくあるけどDBとしてあまり効率じゃない方法。
Laravelのリレーションの「createMany」という関数でいくつでものリレーションは一つのクエリーで作ることができる。
「Administrator」というモデルで「administratorDepartments」というリレーションがある場合：

```php
public function store(Request $request)
{
  // 管理者を登録する
  $admin = Administrator::create($request->except(['company_department_ids']));
  // クエリーデータを準備する
  $deptartment_ids = $request->company_department_ids;
  $departments = array_map(
      fn($id) => [
          'company_department_id' => $id,
      ], $department_ids
  );
  // $department_idsは [ 1, 2 ]の場合
  // $departments = [
  // ['company_department_id' => 1],
  // ['company_department_id' => 2],
  //]
  // 「createMany」の関数に渡す
  $admin->administratorDepartments()->createMany($departments);
}
```

### リレーションの編集

リレーションを編集するときのための「updateRelations」という関数が準備されている。
引数

- relation - 文字列で入れたリレーション名
- link_id - リレーションで繋がるIDカラム名
- ids - リレーションのIDの配列
- （任意）fields - 全部のリレーションに追加する情報の配列

```php
$ids = $request->company_department_ids;
$admin->updateRelations(
   'administratorDepartments', 'company_department_id', $ids, ['updated_at' => date('Y/m/d h:i:s')]
);
```

## bat処理

Laravelのスケジューラー機能を使用する。

### 構成

処理フロー
cron → Laravelのスケジューラー → バッチ機能（PHP）

### cronの設定

laravelのスケジューラを呼び出す設定を１行のみ追加。これはbatが追加されても不変となる。

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2 > &1
```

### Laravelスケジューラー設定

以下ファイルに希望するbatを記述。
app/Console/Kernel.php

```php
protected function schedule(Schedule $schedule): void
{
  // 【 daily 09:00 】【 多重起動禁止 】 当日公開のお知らせメールを対象の従業員宛に送信する
  $schedule->command(SendInformationEmails::class)->dailyAt('9:00')->withoutOverlapping();
  // $schedule->command('inspire')->hourly();
}
```

### バッチ機能（PHP）

以下ソースに希望するbatのスケジュール追加
app/Console/Kernel.php
スケジュールオプション等は以下を参照
https://readouble.com/laravel/8.x/ja/scheduling.html

#### ■ファイル内容

クラス継承：「extends Illuminate\Console\Command」
実行メソッド：「handle()」
実際のバッチ処理は、コントローラー、または、サービス等を用意して呼び出し実行する。
実行例

```php
public function handle(): int
{
  \Log::info($this->title.' start');
  $send_email_controller = app()->make('App\Http\Controllers\Api\SendMailController');
  $send_email_controller->sendInformationEmail();
  \Log::info($this->title." end");
  return 1;
}
```

## git管理

### gitリポジトリ

本プロジェクトにおいて、GITリポジトリを2つ使用する。

| ブランチ | 用途 |
|---|---|
| mediaリポジトリ | 委託先管理用リポジトリ。media専用テストサーバーの資産管理用 |
| githubリポジトリ | 自社管理リポジトリ。本番サーバーの資産管理用 |

#### 資産受け渡し

■委託先→自社
委託先開発資産をgithubに反映する（stgブランチ）。当該作業は定期的に実施する。
■自社→委託先
自社開発資産のマージを行うため、当該者の資産をmainに反映。
委託先にてmediaリポジトリに対してマージ作業を実施する。

#### 競合が発生し得るソースの管理について

自社にて、委託先開発ソースの追加・修正が発生する場合は以下フローとする。
■単純な処理追加の場合
[自社]ソースを直接追加
[自社]gitにプッシュ。(githubリポジトリ・mainブランチ）
[自社]委託先に情報共有
[委託先]当該ブランチから取得。mediaリポジトリ内でマージ。結果報告
★弊社開発部分の既存メソッドの修正等が必要な場合は、先に内容の確認が必要となるため、内容確認＆適宜判断するものとする。

## サンプル　画面作成例

### 一覧画面

```php
use \App\Http\Traits\QuerysetTrait;
public function index(Request $request) {

 // 一覧画面を作成するとき「index-page」というテンプレートにテーブルヘッダーとボディーの情報を渡している。
 $heads = [
 // ヘッダーの情報は基本的に文字列で指定をしている。
    '企業ID',
    '企業名',
    '住所',
    '代表者名',
    '人数',
    // もっと細かく設定する必要があるとき、配列で指定することができる。
    [
       'label'=>'操作',
       // ヘッダのCSSクラスの指定
       'class'=> ['no-sort text-center'],
       // 幅
       'width' => 25,
       // 操作ボタンが必要のとき「partials.tableBtns」のパーシャルの指定をすることができる。
       // ボタンのカラムはモデルのIDが必要、ボディーの設定で入れます。
       'btnCol' => 'tripleBtns'
    ],
 ];
 // ボディー設定
 // 変数名はなんでも大丈夫けどボディーの情報は絶対「'rows'」というプロパティーに入れる。
 // 全部のDBから取得する行をループ処理で必要なデータを取るパターンになる。
 $companies = ['rows' => []];
 $companies['rows'] = array_map(
    fn($row) =>[
       $row->company_no,
       $row->name,
       $row->address,
       $row->representative_name,
       $row->staff_count,
       // ボタンのカラムのためのID設定
       $row->id,
    ],
    $this->getIndexList(Company::class)->with('administrators')->get()->all()
    );

     return view('basicPlatform.companyInfo.index', compact( 'companies', 'heads'));
}
```

テンプレートに「index-page」のエレメントに準備した情報を渡する。

```blade
<x-index-page :heads="$heads" :table-data="$companies">
   <x-slot name="pageTitle">企業情報</x-slot>
   <x-slot name="searchInputs">
      // 検索エリアのエレメント
   </x-slot>
</x-index-page>
```

### 画面の設定クラス

登録・編集・閲覧の画面の作成を行うため共通の機能とパーシャルテンプレートが準備できている。
画面の必要な情報をまとめる「UserForm」というクラスがある。この「UserForm」に機能ごとの情報のクラスを渡してパーシャルで使える形のデータをもらうことができる。
機能ごとのクラスは「App¥Forms」というディレクトリでプラットフォームごとに分けている。例えば、基本プラットフォームの代理店のためのクラスは「App¥Forms¥BasicPlatform¥Agencies.php」とのファイルパスになる。
機能情報のクラスを作るとき、「namespace」は「App」からそのファイルまでのパスに指定すること。クラス名はファイル拡張子を外したファイル名に指定すること。
クラスのなか「$form」というプロパティーにフィールドごとの情報を設定することができる。
テンプレートによって必須の情報が変わります。

#### 全部のフィールドの必須の情報

- name - DBの項目と合わせた名前
- key - フォームで表示されるのラベル
- template - パーシャルテンプレート名 (例：input)

#### 共通の任意の情報

##### classes

スタイルを調整するたCSSクラス名の配列。（例：'classes' => ['col-2'])

##### exclude

フィールドを隠したいときのための配列。
閲覧画面にフィルードを隠したい場合：'exclude' => ['show']
閲覧画面だけに表示したい場合：'exclude' => ['create', 'edit']

##### method

データ取得が複雑な場合はこのプロパティーで取得する関数を指定することができる。

```php
'method' => [
  'create' => 'getTargets',
  'show' => 'getSelectedTargetText',
  'edit' => 'getSelecedTargets',
]
```

指定した取得の関数は「App¥Services¥Elements¥Element.php」というファイルの「Element」というクラスに入れる。「UserForm」のクラスは指定した関数を呼んで下記の情報を入れます。

- fields - 現在対応しているフィールドの情報
- value - （閲覧、編集のみ）あればDBから取得した値、リレーションIDなどにまります
- row - 配列の形にされたDBから取得された行の情報
- model -（閲覧、編集のみ）DBから取得されたモデル。リレーションでデータを取得したいとき使えます。

閲覧と編集の画面の準備をするとき「$fields」のプロパティーに「value」というサブプロパティーを追加する必要がある。
取得関数を作るとき必ず「$fields」をリターンすること。

```php
public function getSelectedTargets($field, $value, $row, $model)
{
  $fields['value'] = 【ロジック】
  return $fields;
}
```

#### テンプレートごとの情報

#### input、dateField （日付）、label、textarea：

必須と任意プロパティーのみ。

#### select：

「select」のテンプレートは選択肢を取得する方法が３つある。

- choices - プルダウンで表示される選択肢。label、 value、 selectedのプロパティーは「select」のテンプレートで必須なため必ず入れる。

```php
'choices' => [
    '1' => ['label' => '様', 'value' => '1', 'selected' => ''],
    '2' => ['label' => 'さん', 'value' => '2', 'selected' => ''],
]
```

- relation - DBから選択肢を取得する必要がある場合のリレーション。
  「モデル名」.「コラム名」と形になる。例：Company.name
  この場合は企業名の選択肢が準備される。
  「relation」で取得するときは「method」のプロパティーを設定する必要がある。取得する関数はもうできているので下記の通りに入れる。

```php
'relation' => 'companies.name',
'method' => [
      'create' => 'getRelatedChoices',
      'show' => 'getRelatedLabel',
      'edit' => 'getRelatedChoices'
 ]
```

- こーどマスター取得
  「codes」のテーブルから取得する場合がある。「name」プロパティーは「type」のカラムの値と同じく入れる。取得する関数はもうできているので下記の通りに入れる。

```php
'name' => 'company_authority_code',
'method' => [
     'create' => 'getDropDownEntries',
     'show' => 'getDropDownSelection',
     'edit' => 'getDropDownSelection'
]
```

#### checkGroup（checkbox・radio）：

- type - 「radio」か「checkbox」の指定。入れない場合は「checkbox」になる。
- direction - 横浜並んで表する場合：「flex-row」。縦並んで表示する場合「flex-column」。入れない場合は「flex-column」になる。

「checkGroup」のテンプレートは選択肢を取得する方法が３つある。

- choices - プルダウンで表示される選択肢。label、 value、 checkedのプロパティーは「select」のテンプレートで必須なため必ず入れる。

```php
'choices' => [
    '1' => ['label' => '様', 'value' => '1', 'checked' => ''],
    '2' => ['label' => 'さん', 'value' => '2', 'checked' => ''],
]
```

- relation - DBから選択肢を取得する必要がある場合のリレーション。
  「モデル名」.「コラム名」と形になる。例：Company.name
  この場合は企業名の選択肢が準備される。
  「relation」で取得するときは「method」のプロパティーを設定する必要がある。取得する関数はもうできているので下記の通りに入れる。

```php
'relation' => 'companies.name',
'method' => [
      'create' => 'getRelatedChoices',
      'show' => 'getRelatedLabel',
      'edit' => 'getRelatedChoices'
 ]
```

- こーどマスター取得
  「codes」のテーブルから取得する場合がある。「name」プロパティーは「type」のカラムの値と同じく入れる。取得する関数はもうできているので下記の通りに入れる。

```php
'name' => 'company_authority_code',
'method' => [
     'create' => 'getGroupEntries',
     'show' => 'getGroupSelection',
     'edit' => 'getGroupEntries'
]
```

#### formDateRange

YYYY/MM/DD ～ YYYY/MM/DDのパターンを表示するフィールド。
こういうフィールドは大体DBで二つのカラムを下記のプロパティーで設定する。

- from_name - DBの期間開始フィールド名
- to_name - DBの期間終了フィールド名

#### カスタムテンプレート

上記のテンプレートでほとんどのフィールドの表示が対応できるけど複雑なフィールドを作るときがある。そういう時は「partials.formFields」というディレクトリのなか新しいテンプレートを作る。
テンプレート名は「$field['template']」のプロパティーに指定すること。例えば「multiInput.blade.php」というパーシャルを作る場合は'template' => 'multiInput'の指定になる。
クラスを作る方法は下記で確認すること。

1. 「UserForm」のクラスでの「$alternateBindings」というプロパティーにテンプレート名（例：multiInput）を追加する。
2. 「App¥Services¥Elements」のディレクトリで新しいファイルを作成し、ファイル名とクラス名は最初のもじが大文字にしたテンプレート名になる。

```php
<?php
namespace App\Services\Elements;
class MultiInput {
    public function getInputLabels($field, $value, $row, $model)
    {
      // ロジック
      // 取得関数のなか「template」のフィールドを調整することができる。ラベル系のテンプレートにする場合はある。
      $fields['template'] = 'label';
      return $fields;
    }
   public function getInputValues($field, $value, $row, $model)
    {
      // ロジック
      return $fields;
    }
}
```

ロジックのなか「value」のプロパティーの設定を忘れないでください。

#### 共通のフィールド

よくあるフィールド（emailや電話番号）はフィールド名の指定だけで表示させることができる。

```php
'name' => [
   'key' => '代理店名',
   'template' => 'input',
   'name' => 'name',
   'classes' => ['col-3']
],
'honorific_code',
'zip',
'address',
'tel',
'fax',
```

上記の場合は「honorific_code」、「zip」、「address」、「tel」、「fax」のフィールドは全部ほかの設定なしで表示される。
よく使っているフィールドがある時、「FormCommon.php」のクラスに追加することができる。

### コントローラー

コントローラーのクラスのファイルのなか「UserForm」と必要なフォームとモデルのクラスのインポート。

```php
use App\Services\UserForm;
use App\Forms\BasicPlatform\Agencies;
use App\Models\Agency;
```

#### 登録画面の準備

```php
public function create()
{
  // フォームのクラスオブジェクトを作成する
  $form = new UserForm(Agencies::class);
  // 登録の場合は「prepareCreate()」というメソッドを呼ぶ
  $fields = $form->prepareCreate();
  // 作成された情報をブレードのテンプレートに渡す
  return view('basicPlatform.agency.new', compact( 'fields'));
}
```

#### 閲覧画面の準備

```php
public function show($id)
{
  // モデルを取得する
  $agency = Agency::find($id);
  // フォームのクラスオブジェクトを作成する
  $form = new UserForm(Agencies::class);
  // 登録の場合は「prepareForm(【モデル】)」というメソッドを呼ぶ
  // 閲覧のときは二番めの引数に「'show'」を入れる。
  $fields = $form->prepareForm($agency, 'show');
  // 作成された情報をブレードのテンプレートに渡す
  return view('basicPlatform.agency.details', compact( 'fields'));
}
```

#### 編集画面の準備

```php
public function edit($id)
{
  // モデルを取得する
  $agency = Agency::find($id);
  // フォームのクラスオブジェクトを作成する
  $form = new UserForm(Agencies::class);
  // 登録の場合は「prepareForm(【モデル】)」というメソッドを呼ぶ
  $fields = $form->prepareForm($agency);
  // 作成された情報をブレードのテンプレートに渡す
  return view('basicPlatform.agency.update', compact( 'fields'));
}
```

### テンプレートの作成

フォームを出す方法はテンプレートのどこかで下記のコードを入れます。

```blade
<x-main-card title="登録情報">
  @include('partials.formFields.form', ['fields' => $fields])
</x-main-card>
```

「x-main-card」というエレメントはフォームのコンテナー。「title」に入れる値はコンテナーのヘッダーに表示される。
「@include('partials.formFields.form', ['fields' => $fields])」のところは全部のフィルードの表示を手掛けます。
登録画面の例文：

```blade
<x-base-temp name="代理店作成">
   <form action="/basic_platform/agencies" method="post">
      @csrf
      <x-main-card title="代理店情報">
         @include('partials.formFields.form', ['fields' => $fields])
      </x-main-card>
      @include('partials.tableBtns.confirmBtn', ['text' => '登録'])
   </form>
</x-base-temp>
```

閲覧画面の例文：

```blade
<x-base-temp name="代理店詳細">
   <x-slot name="headerSlot">
      <button onclick="history.back()" class="btn border px-5">戻る</button>
   </x-slot>
   <x-main-card title="代理店情報">
     @include('partials.formFields.form', ['fields' => $fields])
   </x-main-card>
   <button onclick="navTo(event, location.href + '/edit')" class="btn btn-primary px-5">編集</button>
</x-base-temp>
```

編集画面の例文：

```blade
<x-base-update-temp name="代理店編集" action="/basic_platform/agencies/{{ $agency->id }}">
  <x-slot name="headerSlot">
     <button onclick="history.back()" class="btn border px-5">戻る</button>
  </x-slot>
  <x-main-card title="代理店情報">
     @include('partials.formFields.form', ['fields' => $fields])
  </x-main-card>
  <x-control-button-bar />
</x-base-update-temp>
```

### 一覧画面作成（LivewireとDatatables.jsを使用）

#### Livewireの部分

##### 1. 検索項目をLivewireコンポーネントをとして作成

```
php artisan make:livewire basicplatform.contacts.searchfields
```

※ディレクトリ構成を基本的には「システム名/画面名/ファイル名」にする
上記のコードを実行すると以下のファイルが作成される

app/Http/Livewire/Basicplatform/Contacts/Searchfields.php
resources/views/livewire/basicplatform/contacts/searchfields.blade.php

##### 2. 作成したLivewireコンポーネントをレンダリングする

仕様するページ内で@livewire blade directiveを使用してレンダリング
:@livewire('basicplatform.contacts.searchfields')
他のblade directiveと同様に引数渡す場合:
@livewire('basicplatform.contacts.searchfields', ['post' => $post])
例:渡した引数をLaravelのbladeコンポーネントと同様に使います
※Livewireの場合__construct()関数の代わりにmount()関数が使われます

##### 3. 検索項目をapp/Http/Livewire/Basicplatform/Contacts/Searchfields.phpに紐付けする

最初にindex.bladeの<x-slot name="searchInputs">の中身（検索項目）を
resources/views/livewire/basicplatform/contacts/searchfields.blade.phpに移動させること。
..Searchfields.phpファイル内でそれぞれ項目に対してモデルを宣言
例：日付項目に対して宣言
コンポーネントファイルで項目をモデルと紐づける

```blade
<input type="date" value="" class="form-control " name="fromDate" id="fromDate-date"
wire:model.defer="fromDate" >
```

wire:model ※deferはオプション 基本的にこのオプションで使用することを推奨
wire:model単体で使用する場合は値が変わる瞬間ごとにAPIでモデルが更新されるのでnetwork requestが膨らみ負担がかかる恐れがあります
オプション:

* Debouncing Input : wire:model.debounce.○○ms
検索項目の値が変更後○○ms後にAPIでモデルが更新される
* Lazy Updating:wire:model.lazy
検索項目の値が変わって項目からフォーカスが外れた時にAPIでモデルを更新される
* Deferred Updating:wire:model.defer
基本的に次のnetwork requestが発生した時にAPIでモデルが更新される

##### 4.Event(click,keydown, submit)アクションについて

Livewireでは例えばボタンを押した時にバックエンドの関数を呼び出すことが可能になっている
例:検索項目サイトを選択するとそのサイトの画面データ取得して画面選択セレクトに設定

```blade
<select name="system" id="system" class="form-control " wire:model="system" wire:change="getPage">
      @foreach($codes as $code)
          <option value="{{ $code->value }}">{{ $code->name }}</option>
      @endforeach
<select/>
```

※上記の値をmount()関数内で取得している。
wire:change="getPage" :セレクトの値をが変わる瞬間にgetPage関数が呼び出される
データが取得されてモーデルにセット
コンポーネント側で

```blade
<select name="screen" id="screen" class="form-control " wire:loading.attr="disabled" wire:target="getPage">
      @foreach($pages as $page)
          <option value="{{ $page->value }}">{{ $page->name }}</option>
      @endforeach
</select>
```

$pagesが取得された瞬間にセレクトオプションが設定される
** wire:loading.attr="disabled" wire:target="getPage"**
wire:loading.attr="disabled"APIでnetwork requestが飛んでレスポンスが戻る間でセレクト項目をdisabledにする
wire:target="getPage" 上記のコードと組み合わせにして使う。getPage関数が呼ばれた時だけにセレクト項目をdisabledにする

##### 5.Validationについて

Livewire内でValidationすることも可能。
backend側のファイルで先ずはルールを宣言する
次に検索ボタンを押すとValidationを実行するように作成
今回はelementに直接wire:click書き出す代わりにjsを使用して実装している。
このコードを一覧画面全体的に共通で使用できるように作成している。
public\js\tableSearch.js ※Datatables.jsの一覧画面の実装部分もあります後に説明される
Livewire.emit('checkError');でbackend側のcheckError関数が呼び出される
コンポーネントでイベントリスナーを追加

```php
protected $listeners = ['checkError' => 'checkError'];
```

※public\js\tableSearch.jsが共通化しているためvalidationを行う場合checkErrorの名前で関数を作成すること。
Validationエラーの場合$this->validate();で止まってValidationエラーメッセージが返される
Validationエラーなしの場合次の行が実行される。
次の行では一覧画面にイベントが返されそれを拾った時に検索処理が実行される。

#### Datatables.jsの部分

##### 1. Controllerで一覧画面検索に必要な変数の準備をする

$heads:普段どうりにテーブルのを用意する
※$contacts = ['rows' => []];共通コンポーネントで使用されているため一旦残している、修正して無くす予定
$table_data['searchParams']:検索項目elementのidを渡す
$table_data['columns']:ajaxレスポンスで返される配列のキー
※一つのセル内で複数値を表示する場合はキーを**&**でつなく
$table_data['buttons']:操作欄のボタンを文字列として渡す
$table_data['route_name']: 検索処理を実行するAPIのroute名
※Route宣言が必要。

```php
Route::get('/basic_platform/contactsForDatatable', [ContactController::class, 'contactsForDatatable'])->name('acquire_contact');
```

##### 2.検索処理の関数を作成

```php
public function informationForDatatable(Request $request)
  {
   .........
   }
```

をコントローラーで作成
Datatables.jsの既存の項目
データ件数カウントやフィルターした場合のカウント、データ取得
$this->addFilter($records, $searchCond);でwhere条件をクエリーに連携を行っている
jsonでリスポンスを返す
※クエリー作成関数が長くなる場合はありますので
app\Http\Traits\SearchTrait.phpでクエリーを共通化しています

##### 3.フロント側の処理

indexページの作成

* id="datatable" を渡すこと
* @include('partials.tableData', $table_data)コンポーネントをincludeすること
コントローラーから渡される$table_dataの値をinput「hidden」項目に設定される
* <script src="{{ asset('js/tableSearch.js') }}"></script>を呼び出すこと
input「hidden」から値を取得してDatatableのconfig設定を行える

##### 4.Javascriptで検索処理の流れ

public\js\tableSearch.js
Validation後backendから発信されたeventを拾って取得処理が実行される
流れとしてDatatableのconfig設定されたajaxでAPI routeに検索項目の値を渡してデータを取得
取得されたデータでテーブルセルを埋める$table_data['column']で宣言した順番で、その後に一つ空のセルを追加してそのセルに$table_data['button']が入れられる
