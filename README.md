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

## 仕様駆動開発（GitHub Copilot + Spec Kit）

[GitHub Spec Kit](https://github.com/github/spec-kit) 方式の仕様駆動開発（Spec-Driven Development）ワークフローを、`specify` CLI（uv/Python）を使わずに手動で組み込み済みです（社内ネットワークで uv/Python のインストールがSSL制約によりブロックされたため、GitHub API経由でspec-kit本体のソースを取得し、Copilot向けの出力を再現）。

VS Code の Copilot Chat で `/` を入力すると以下のスラッシュコマンド（エージェント）が使えます。上から順に進めるのが基本の流れです。

| コマンド | 役割 |
|----------|------|
| `/speckit.constitution` | プロジェクト憲章（開発原則）を作成・更新 |
| `/speckit.specify` | 自然言語の要望から仕様書（`specs/<feature>/spec.md`）を作成 |
| `/speckit.clarify` | 仕様の曖昧点を質問形式で明確化（任意） |
| `/speckit.plan` | 技術スタックを踏まえた実装計画（`plan.md`）を作成 |
| `/speckit.tasks` | 計画をタスクリスト（`tasks.md`）に分解 |
| `/speckit.analyze` | 仕様・計画・タスク間の整合性チェック（任意） |
| `/speckit.checklist` | 品質チェックリストを作成（任意） |
| `/speckit.implement` | タスクリストに沿って実装を実行 |

構成ファイル:

- `.specify/memory/constitution.md` — プロジェクト憲章（`/speckit.constitution` で編集）
- `.specify/templates/` — spec/plan/tasks/checklist の雛形
- `.specify/scripts/bash/` — 各コマンドが内部で呼び出す前提条件チェック・雛形コピー用スクリプト
- `.github/agents/speckit.*.agent.md` — 各コマンドの実体（VS Code custom agent）
- `.github/prompts/speckit.*.prompt.md` — Copilot Chat のスラッシュコマンド登録用
- `specs/<番号>-<機能名>/` — `/speckit.specify` 実行時に自動生成される機能ごとの仕様ディレクトリ

将来 uv が使えるようになった場合は、公式CLIで最新化・アップグレードできます:

```bash
uv tool install specify-cli --from git+https://github.com/github/spec-kit.git
specify upgrade
```

## 注意（社内ネットワーク）

- コンテナ内 `composer install` は SSL 制約で失敗する場合あり
- 初回 vendor 準備: `scripts\init-vendor-volume.bat`（LLax27 の vendor をコピーするだけ）
- 新規 Composer パッケージ追加時は IT 部門に CA 証明書設定を相談
