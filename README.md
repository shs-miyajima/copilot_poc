# kiro_poc

Laravel 12 + Docker（PostgreSQL）のローカル開発環境 PoC。

## クイックスタート

```bat
scripts\init-vendor-volume.bat   # 初回のみ（LLax27 から vendor コピー）
run_debug.bat                    # Docker 起動 + 起動確認
```

- アプリ: http://localhost:8000
- PostgreSQL: localhost:5433

起動確認のみ:

```bat
run_debug.bat verify
```

停止 / ログ:

```bat
run_debug.bat down
run_debug.bat logs
```

## Docker 構成

| サービス | コンテナ名 | 内容 |
|----------|------------|------|
| app | `kiro_poc_app` | PHP-FPM（`laravel_app:1.0`） |
| nginx | `kiro_poc_nginx` | localhost:8000 |
| db | `kiro_poc_postgres` | PostgreSQL 18（localhost:5433） |

vendor ボリューム: `kiro_poc_laravel_vendor`（LLax27 とは独立）

## 開発

| 用途 | コマンド |
|------|---------|
| artisan | `docker compose exec app php artisan <command>` |
| PHPUnit | `docker compose exec app php artisan test` |
| Vite | `npm run dev`（ホスト） |
| Vitest | `npm run test`（ホスト） |
| Playwright | `cd tests/e2e_tests && npx playwright test`（ホスト） |

フロント初回:

```bat
npm install
npm run dev
```

## 注意（社内ネットワーク）

- コンテナ内 `composer install` は SSL 制約で失敗する場合あり
- 初回 vendor 準備: `scripts\init-vendor-volume.bat`（LLax27 の vendor をコピーするだけ）
- 新規 Composer パッケージ追加時は IT 部門に CA 証明書設定を相談
