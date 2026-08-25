# 実装計画: [FEATURE]

**ブランチ**: `[###-feature-name]` | **日付**: [DATE] | **仕様**: [link]

**入力**: `/specs/[###-feature-name]/spec.md` からの機能仕様

**注意**: このテンプレートは `__SPECKIT_COMMAND_PLAN__` コマンドによって記入されます。実行ワークフローはそのコマンド定義に記載されています。

## 概要

[機能仕様から抽出: 主要な要件 + リサーチに基づく技術的アプローチ]

## 技術的コンテキスト

<!--
  アクション必要: このセクションの内容を、プロジェクトの技術的詳細に置き換えてください。
  ここに示す構成は反復作業を導くための参考として提示されています。
-->

**言語/バージョン**: [例: Python 3.11, Swift 5.9, Rust 1.75 または NEEDS CLARIFICATION]

**主要な依存関係**: [例: FastAPI, UIKit, LLVM または NEEDS CLARIFICATION]

**ストレージ**: [該当する場合、例: PostgreSQL, CoreData, files または N/A]

**テスト**: [例: pytest, XCTest, cargo test または NEEDS CLARIFICATION]

**対象プラットフォーム**: [例: Linux server, iOS 15+, WASM または NEEDS CLARIFICATION]

**プロジェクト種別**: [例: library/cli/web-service/mobile-app/compiler/desktop-app または NEEDS CLARIFICATION]

**パフォーマンス目標**: [ドメイン固有、例: 1000 req/s, 10k lines/sec, 60 fps または NEEDS CLARIFICATION]

**制約**: [ドメイン固有、例: <200ms p95, <100MB memory, offline-capable または NEEDS CLARIFICATION]

**規模/範囲**: [ドメイン固有、例: 10k users, 1M LOC, 50 screens または NEEDS CLARIFICATION]

## 憲章チェック

*ゲート: フェーズ 0 のリサーチ前に合格が必要。フェーズ 1 の設計後に再チェックする。*

[憲章ファイルに基づき決定されるゲート]

## プロジェクト構成

### ドキュメント（この機能）

```text
specs/[###-feature]/
├── plan.md              # このファイル (__SPECKIT_COMMAND_PLAN__ コマンドの出力)
├── research.md          # フェーズ 0 の出力 (__SPECKIT_COMMAND_PLAN__ コマンド)
├── data-model.md        # フェーズ 1 の出力 (__SPECKIT_COMMAND_PLAN__ コマンド)
├── quickstart.md        # フェーズ 1 の出力 (__SPECKIT_COMMAND_PLAN__ コマンド)
├── contracts/           # フェーズ 1 の出力 (__SPECKIT_COMMAND_PLAN__ コマンド)
└── tasks.md             # フェーズ 2 の出力 (__SPECKIT_COMMAND_TASKS__ コマンド - __SPECKIT_COMMAND_PLAN__ では作成されない)
```

### ソースコード（リポジトリルート）
<!--
  アクション必要: 以下のプレースホルダーのツリーを、この機能の具体的な構成に置き換えてください。
  使用しないオプションは削除し、選択した構成を実際のパス（例: apps/admin, packages/something）で
  展開してください。納品する計画には Option ラベルを含めないでください。
-->

```text
# [不要なら削除] オプション 1: 単一プロジェクト（デフォルト）
src/
├── models/
├── services/
├── cli/
└── lib/

tests/
├── contract/
├── integration/
└── unit/

# [不要なら削除] オプション 2: ウェブアプリケーション（「frontend」+「backend」を検出した場合）
backend/
├── src/
│   ├── models/
│   ├── services/
│   └── api/
└── tests/

frontend/
├── src/
│   ├── components/
│   ├── pages/
│   └── services/
└── tests/

# [不要なら削除] オプション 3: モバイル + API（「iOS/Android」を検出した場合）
api/
└── [上記の backend と同様]

ios/ または android/
└── [プラットフォーム固有の構成: 機能モジュール、UI フロー、プラットフォームテスト]
```

**構成の決定**: [選択した構成を文書化し、上記で記録した実際のディレクトリを参照する]

## 複雑性の追跡

> **憲章チェックに正当化が必要な違反がある場合のみ記入する**

| 違反 | 必要な理由 | より単純な代替案を却下した理由 |
|-----------|------------|-------------------------------------|
| [例: 4 つ目のプロジェクト] | [現在必要な理由] | [なぜ 3 プロジェクトでは不十分か] |
| [例: リポジトリパターン] | [具体的な問題] | [なぜ直接の DB アクセスでは不十分か] |
