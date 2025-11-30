# PukiWiki 1.5.4 Markdown対応版

自由にページを追加・削除・編集できるWebページ構築スクリプトである**PukiWiki 1.5.4**をベースに、Markdown記法での記載ができるように修正を加えました。

PukiWikiではどうしても書き方が馴染めないという人が少なくない上に、Markdownエディターはいろいろな優れたアプリがどんどん増えていて自分に合った使いやすいものがかなり選べる状態になっています。その考えからPukiWiki 1.5.4を無理やりMarkdown記法とPukiWiki記法の両方に対応させたものが今回のものになります。

しかし、以前に公開したPukiWiki-mdは全てのページがMarkdownでしか書けないために不便を感じることもありました。従来型の書き方を使いたいということもあるからです。そこで、ページによって2種類の書き方を使い分けることができるような改造をしました。さらに、チェックボックスで編集からそのいずれかを採用するかを選ぶことができます。

---

## ベースとしたPukiWiki

- **Version**: 1.5.4
- **Copyright**:
  - 2001-2022 PukiWiki Development Team
  - 2001-2002 yu-ji (Based on PukiWiki 1.3 by yu-ji)
- **License**: GPL version 2 or (at your option) any later version
- **URL**:
  - https://pukiwiki.osdn.jp/
  - https://pukiwiki.osdn.jp/dev/
  - https://osdn.jp/projects/pukiwiki/

---

## 特徴

### ページ毎にPukiWiki記法かMarkdown記法を選択可能
- 編集画面のチェックボックスでどちらの記法を使うかを選べます
- 新規ページの初期設定はMarkdownに設定しています（変更可能です）
- これまでに書いたページはPukiWiki記法で残しつつ、今後のページはMarkdown記法で書き足してゆくということもできます

### Markdown記法でもプラグインが使用可能
- ブロックプラグインでは `#plugin` の代わりに `!plugin` の表記を用います

### リンク記法の柔軟性
- PukiWiki記法（`[[リンク>URL]]`）でもMarkdown記法（`[リンク](URL)`）でも使用可能です

### ビジュアルMarkdownエディタ「EasyMDE」搭載
- CSSとJavaScriptを各1行ずつ読み込むだけで簡単にビジュアルMarkdownエディタが使用可能
- 書いたMarkdown書式はリアルタイムに反映されます
- PukiWiki記法で記載しているときは書式は反映されません
- 不要な場合は `pukiwiki.ini.php` で `$use_simplemde = 0;` を設定してください

---

## 使用しているMarkdownパーサー

このバージョンは **league/commonmark 2.x 専用**です。

### league/commonmark 2.x
- **GitHub Flavored Markdown（GFM）完全対応**
- 打ち消し線（`~~text~~`）、タスクリスト（`- [ ]`）、オートリンク、テーブルなど
- **Pandocスタイルのインライン脚注**（`^[text]`）と通常の脚注（`[^1]`）の両方に対応
- **PukiWikiスタイルのインライン脚注**（`((text))`）も併用可能
- 継続的にメンテナンスされている最新のMarkdownパーサー
- **ライセンス**: BSD-3-Clause

### システム要件
- **PHP 7.4以上**（必須）
- **Composer経由でleague/commonmarkをインストール済み**（vendorディレクトリに含まれています）

**Note**: Parsedown系パーサーは使用できません。PukiWiki本体はPHP 5.6以降で動作しますが、このMarkdown対応版はPHP 7.4以上が必要です。

---

## 設定項目（pukiwiki.ini.php）

### `$use_markdown_cache = 1;`
Markdownキャッシュ機能
- `1`: 有効（推奨） - Markdown変換結果をキャッシュして高速化
- `0`: 無効 - 毎回変換（開発・デバッグ時のみ）

**Note**: キャッシュを有効にすると、Markdown変換が初回の1/10～1/20の時間で完了します（例: 20ms → 1ms）。ページ内容が変更された場合、自動的にキャッシュは更新されます。キャッシュの再生成タイミング: (1)ページ編集・保存時、(2)有効期限切れ時（デフォルト7日）、(3)キャッシュファイル不在時。期限切れキャッシュは1%の確率で自動削除されます。

### `$default_notemd = 1;`
新規ページのデフォルトモード設定
- `1`: Markdown - 新規ページ作成時、デフォルトでMarkdownモードを有効にする
- `0`: PukiWiki - 新規ページ作成時、デフォルトでPukiWiki記法モードにする

**Note**: この設定は新規ページ作成時のみ適用されます。既存ページの編集には影響しません。ページ編集画面で「Markdown」チェックボックスを使って切り替えることも可能です。

### `$use_simplemde = 1;`
EasyMDEエディタの使用設定（SimpleMDEの後継）
- `1`: 有効 - リアルタイムプレビュー機能付きエディタを使用（ローカルファイル）
- `0`: 無効 - 標準のテキストエリアを使用

**Note**: EasyMDE v2.20.0がローカルに配置されています（`skin/js/easymde.min.{css,js}`）。SimpleMDEは2017年から開発停止のため、EasyMDEに移行しました。

### `$markdown_debug_mode = 0;`
Markdownデバッグモード
- `1`: 有効 - HTMLコメントとして詳細なデバッグ情報を出力（パーサー名、キャッシュヒット/ミスなど）
- `0`: 無効（推奨） - 本番環境での使用時

### `$markdown_support_hash_plugin = 0;`
Markdownブロックプラグイン構文設定
- `0`: `!plugin` のみサポート（デフォルト、後方互換性重視）
- `1`: `#plugin` と `!plugin` の両方をサポート（PukiWiki記法との統一性重視）

**Note**: `1`を選択した場合、Markdown見出しは必ず「`# `」（`#`の後にスペース必須）となります。これはCommonMark仕様に準拠しています。`#`の後にスペースがない場合（例: `#plugin(args)`）はプラグイン呼び出しとして認識され、スペースがある場合（例: `# 見出し`）は見出しとして認識されます。

---

## 拡張Markdown記法

このバージョンは **league/commonmark (GitHub Flavored Markdown)** を使用しています。以下の拡張記法が使用できます。

### テーブル（GitHub Flavored Markdown形式）

```markdown
| ヘッダー1 | ヘッダー2 |
|----------|----------|
| セル1    | セル2    |
```

### 脚注（3種類の記法をサポート）

#### 1. 参照スタイル脚注（Reference-style footnotes）

```markdown
本文中に脚注[^1]を挿入できます。

[^1]: これが脚注の内容です
```

#### 2. インライン脚注（Inline footnotes）- Pandocスタイル

```markdown
本文中にインライン脚注^[これがインライン脚注です]を挿入できます。
```

#### 3. インライン脚注（PukiWikiスタイル）

```markdown
本文中にインライン脚注((これがPukiWiki脚注です))を挿入できます。
```

**重要**: Markdown記法モード（`#notemd`指定時）でも、**PukiWikiスタイルの脚注 `((text))` が使えます**。Pandoc脚注 `^[text]` と併用可能です。PukiWikiユーザーが慣れ親しんだ記法をそのまま使えます。

**Note**: 脚注の内容が短い場合はインライン脚注が便利です。参照スタイル脚注とインライン脚注は同時に使用できます。

### 打ち消し線（Strikethrough）

```markdown
~~打ち消し線~~
```

### タスクリスト（Task Lists）

```markdown
- [x] 完了したタスク
- [ ] 未完了のタスク
```

### オートリンク

```markdown
https://example.com （自動的にリンクになります）
user@example.com （メールアドレスも自動的にリンクになります）
```

### Fenced Code Blocks

````markdown
```言語名
コード
```
````

### その他のGitHub Flavored Markdown機能
- テーブル内のパイプエスケープ（`\|`）
- HTMLブロックの埋め込み（安全なもののみ）
- オートリンクの拡張（www.example.comも自動リンク化）

---

## エラーハンドリング

- プラグイン呼び出し時のエラーを詳細に表示
- デバッグモード有効時は、エラーの原因を具体的に表示
- Markdownパーサーのエラーも適切にキャッチして表示

---

## EasyMDEエディタ（ローカル配置）

EasyMDE v2.20.0が既にローカルに配置されています。SimpleMDEの後継プロジェクトとして、アクティブなメンテナンスとセキュリティアップデートが継続されています。

### 配置済みファイル
- `skin/js/easymde.min.css` (v2.20.0, 13KB)
- `skin/js/easymde.min.js` (v2.20.0, 320KB)

### 移行理由
- SimpleMDEは2017年から開発停止
- EasyMDEは継続的にメンテナンスされている
- API互換性を維持（移行が容易）
- セキュリティアップデートとバグ修正

### EasyMDEの更新手順（将来的に必要な場合）

#### 1. 最新版をダウンロード
```bash
npm install easymde
```
または
```
https://github.com/Ionaru/easy-markdown-editor/releases
```

#### 2. ファイルを置き換え
```
node_modules/easymde/dist/easymde.min.css → skin/js/easymde.min.css
node_modules/easymde/dist/easymde.min.js → skin/js/easymde.min.js
```

**Note**: ローカル配置の場合、integrity属性とcrossorigin属性は不要なため削除してください。

### メリット
- オフライン環境での使用が可能
- CDN障害の影響を受けない
- プライバシー保護（外部サーバーへの接続なし）
- ページ読み込み速度の向上（LAN内配信の場合）

---

## プラグイン互換性

Markdown記法モードでも、PukiWikiのほぼ全てのプラグインが使用可能です。

### ブロックプラグイン（#pluginname / !pluginname）

Markdown記法では、デフォルトでプラグイン呼び出しの先頭記号を `#` から `!` に変更します。

**PukiWiki記法の例:**
```
#calendar
```

**Markdown記法の例（デフォルト）:**
```
!calendar
```

**Markdown記法の例（`$markdown_support_hash_plugin = 1`設定時）:**

`pukiwiki.ini.php`で`$markdown_support_hash_plugin = 1;`を設定すると、PukiWiki記法と同じ`#`記号も使用できます：

```
#calendar
```

**CommonMark仕様による自動判別:**
- `# 見出し`（`#`の後にスペース） → Markdown見出しとして処理
- `#calendar`（`#`の後にスペースなし） → プラグイン呼び出しとして処理
- `!calendar`（設定に関わらず常に動作） → プラグイン呼び出しとして処理

**マルチライン（複数行）対応:**
```
!article{{
タイトル
本文内容...
}}
```

または、`$markdown_support_hash_plugin = 1`設定時：
```
#article{{
タイトル
本文内容...
}}
```

**エラーハンドリング:**
- プラグインが存在しない場合は警告メッセージを表示
- プラグイン実行時の例外を適切にキャッチ
- デバッグモード有効時は詳細なエラー情報を表示

### インラインプラグイン（&pluginname）

通常のPukiWiki記法と同じ構文で使用できます。

**使用例:**
```
&br;
&color(red){赤い文字};
&size(20){大きな文字};
```

**エラーハンドリング:**
- プラグインが存在しない場合は元のテキストを表示
- プラグイン実行時の例外を適切にキャッチ
- デバッグモード有効時は詳細なエラー情報を表示

### セキュリティ

全てのプラグイン呼び出しは、try-catch構文で保護されています。プラグインの実行エラーが発生しても、ページ全体がクラッシュすることはありません。

---

## 既存のPukiWikiからの引っ越しも可能

この、ページ毎に従来のPukiWiki記法とMarkdown記法のいずれかを選んで使い分けることができるという方式で何が有利かというと、**既存ページのwikiフォルダはそのままに設定ファイルなどを書き換えれば、今まで使っていたPukiWikiがそのままMarkdownも併用できるようになる**という点です。

全てのページがMarkdownしか使えないのは全ページの書き換えが必要だったので、新規に設置するPukiWikiにはよくても既存の稼働しているPukiWikiからの引っ越しには不向きでした。

今回の改造では、PukiWiki記法で使用しているページの保存ファイルは手を加えず、一方でMarkdown記法を指定した保存ファイルには `#notemd` の偽装プラグインを書き込むようにしています。

---

## 修正履歴

> **※ 2025-11-18**: SimpleMDEからEasyMDE v2.20.0への完全移行を実施しました。
> 以下の履歴中のSimpleMDE参照は、移行前の記録です。

### 2025-11-30 (ブロックプラグイン構文拡張)

#### [機能追加]
- Markdownモードで`#plugin`構文をサポート
  - 新設定項目 `$markdown_support_hash_plugin` を追加
  - デフォルト値 `0`: 従来通り`!plugin`のみサポート（後方互換性重視）
  - 値 `1`: `#plugin`と`!plugin`の両方をサポート（PukiWiki記法との統一性重視）
- CommonMark仕様による見出しとプラグインの自動判別
  - `# 見出し`（`#`の後にスペース） → Markdown見出しとして処理
  - `#plugin(args)`（`#`の後にスペースなし） → プラグイン呼び出しとして処理
  - league/commonmark 2.xのCommonMark準拠仕様を活用
- マルチラインプラグインも`#plugin{{ ... }}`構文に対応
- キャッシュ機構を拡張
  - 設定に応じて異なるキャッシュキーを生成（`commonmark` / `commonmark-hashplugin`）
  - 設定変更時に自動的に新しいキャッシュを使用
- デバッグ情報に`hash_plugin_support`の状態を追加
- エラーメッセージを改善
  - ユーザーが入力した接頭辞（`#`または`!`）を表示

#### [利点]
- PukiWiki記法からの移行が容易に
  - プラグイン呼び出しを`#plugin`のまま使用可能
  - 既存の知識をそのまま活用
- Markdown見出しとの競合なし
  - CommonMark仕様により自動判別
  - `#`の後のスペースの有無で判定
- 後方互換性を維持
  - デフォルト設定で既存ページへの影響なし
  - `!plugin`構文は設定に関わらず常に動作

### 2025-11-16 (バグ修正と機能追加)

#### [バグ修正]
- Markdownエディタが表示されない問題を修正
  - `$simplemde`変数の初期化漏れを解消
  - DOMContentLoadedイベントで確実に初期化
  - Markdownチェックボックスの状態を監視し、動的にエディタをオン/オフ
- 新規ページ初期テキストの問題を修正
  - Markdownモードの新規ページで`[[リンク元]]`が不要なため削除
  - PukiWikiモードの既存ページは従来通り動作

#### [機能追加]
- 新規ページのデフォルトモード設定を追加（`$default_notemd`）
  - `1`: 新規ページはMarkdownモード（デフォルト）
  - `0`: 新規ページはPukiWiki記法モード
  - 既存ページの編集には影響なし
  - ユーザーの好みに応じてデフォルトモードを選択可能

### 2025-11-16 (Phase 4改良 - Markdownエディタ改善、プラグイン互換性強化、コード品質向上)

#### [セキュリティ強化]
- Markdownエディタをバージョン固定（1.11.2）に変更
  - CDN URLを "latest" から明示的なバージョン指定に変更
  - 予期しないバージョン更新による互換性問題を防止
- SRI (Subresource Integrity) 対応
  - CSSとJavaScriptにintegrity属性を追加
  - CDN改ざん検知によるセキュリティ向上
  - crossorigin属性の追加
- インラインプラグインに例外処理を追加
  - try-catch構文でプラグイン実行を保護
  - ブロックプラグインと同等のエラーハンドリング
  - デバッグモード有効時の詳細エラー表示

#### [改善]
- Markdownエディタ初期化にエラーハンドリングを追加
  - try-catch構文で例外をキャッチ
  - CDN読み込み失敗時のフォールバック処理
  - コンソールへの警告/エラーメッセージ出力
- プラグイン互換性の完全確保
  - ブロックプラグイン（`!plugin`）の動作確認
  - インラインプラグイン（`&plugin`）の動作確認
  - マルチラインプラグインのサポート確認
  - エラーハンドリングの統一化

#### [コード品質向上]
- `convert_html()`関数のリファクタリング
  - 188行の長大な関数を87行に短縮
  - 6つの責務別ヘルパー関数に分割
  - PHPDocコメントによる詳細なドキュメント化
  - 保守性とテスタビリティの向上
- 例外処理の統一化
  - `format_markdown_error()`関数による統一的なエラー処理
  - HTMLエスケープ処理の標準化
  - CSSクラスの統一（alert alert-warning/danger）
  - デバッグモード対応の一元化
  - ブロックプラグイン、インラインプラグイン、パーサーで統一フォーマット

#### [ドキュメント追加]
- オフライン環境対応のドキュメント追加
  - Markdownエディタローカル化手順の詳細説明
  - ファイル配置とlib/html.php編集方法
  - オフライン使用のメリット説明
- プラグイン互換性セクションを追加
  - ブロックプラグインの使用方法と構文
  - インラインプラグインの使用方法と構文
  - エラーハンドリングの説明
  - セキュリティに関する説明

### 2025-11-16 (Phase 3改良 - セキュリティ強化)

#### [セキュリティ強化]
- Markdown内の画像・リンクURLスキーム検証機能を追加
  - `is_safe_markdown_url()`関数を実装
  - ホワイトリスト方式（http/httpsのみ許可）
  - `javascript:`, `data:`, `file:`などの危険なスキームをブロック
  - 不正なURLはエラーメッセージを表示
- Safemodeのデフォルト動作を安全側に変更
  - 未設定時や不正値の場合は自動的に有効化
  - セキュリティ優先の設計に改善
- デバッグモードにセキュリティ警告を追加
  - ブロックされたURLを追跡
  - HTMLコメントで詳細情報を出力

#### [ドキュメント改善]
- pukiwiki.ini.phpの設定コメントを強化
  - セキュリティリスクに関する警告を追加
  - 各設定項目の推奨値を明記
  - デバッグモードの本番環境使用に関する注意事項

### 2025-11-16 (Phase 2改良)

#### [機能追加]
- ParsedownExtra 0.8.1を導入し、拡張Markdown記法に対応
  - GitHub Flavored Markdown形式のテーブル
  - 脚注機能
  - 定義リスト
  - Fenced Code Blocks
  - その他多数の拡張機能
- デバッグモード機能を追加（`$markdown_debug_mode`設定）
  - プラグイン呼び出しの追跡
  - パーサーエラーの詳細表示
  - HTMLコメントでのデバッグ情報出力

#### [改善]
- エラーハンドリングを大幅に強化
  - プラグイン呼び出し時の例外処理を追加
  - 詳細なエラーメッセージの表示
  - HTMLエスケープ処理によるセキュリティ向上
- リンク処理を改善
  - RFC 3986準拠のURL処理に対応
  - より広範なURLフォーマットに対応
  - 日本語URLや特殊文字への対応強化
- セーフモードのデフォルト値を安全側に設定

#### [更新]
- Parsedownを1.8.0-beta-7から1.7.4（最新安定版）にアップデート
- ParsedownExtraを0.8.0から0.8.1（最新安定版）にアップデート
  - セキュリティ修正とバグ修正を含む
  - Parsedown 1.7.4との互換性を確保

#### [追加設定項目]
- `$use_parsedown_extra`: ParsedownExtraの有効/無効を切り替え
- `$markdown_debug_mode`: デバッグモードの有効/無効を切り替え

---

## 開発者向け情報

このセクションは、コードベースの開発・保守を行う開発者向けの技術情報です。

### システム要件

- **PHP**: 7.4以上（推奨: 8.0以上）
- **Composer**: league/commonmark 2.xのインストールに使用
- **Markdownパーサー**: league/commonmark 2.x（GitHub Flavored Markdown完全対応）

### 開発用コマンド

#### PHP構文チェック
```bash
# 個別ファイルのチェック
php -l lib/convert_html.php
php -l lib/html.php
php -l plugin/edit.inc.php

# 複数ファイルの一括チェック
php -l lib/*.php
```

#### ローカルでの動作確認
```bash
# PHPビルトインサーバーで起動
php -S localhost:8080

# ブラウザで http://localhost:8080 にアクセス
```

### アーキテクチャ概要

#### コア処理フロー

```
index.php
  ↓ (Commonmarkオートローダー読み込み)
lib/init.php
  ↓ (グローバル設定読み込み)
pukiwiki.ini.php (657-720行目: Markdown設定)
  ↓
lib/convert_html.php::convert_html()
  ↓ (記法判定: get_notemd())
  ├─ Markdown → process_markdown() → league/commonmark
  │    ↓ (ブロックプラグイン処理)
  │    └─ process_block_plugin() → do_plugin_convert()
  │
  └─ PukiWiki → make_str_rules() → PukiWiki標準パーサー
```

#### モード切替機構

**中核**: `lib/file.php`の3関数がページのMarkdown/PukiWiki状態を管理

- `add_notemd($page)`: ページ保存時に`#notemd\n`を先頭に挿入
- `remove_notemd($page)`: ページ保存時に`#notemd\n`を先頭から削除
- `get_notemd($page)`: ページ読み込み時に`#notemd`の有無を判定

**偽装プラグインとしての`#notemd`**:
- 実体のプラグインファイル（`plugin/notemd.inc.php`）は存在しない
- ファイル保存形式のマーカーとして機能
- PukiWikiパーサーからは無視される（プラグインが存在しないため）

### 主要ファイルと機能

#### 1. `lib/convert_html.php` (281-450行目)
Markdown処理のメインロジック

**主要関数**:
- `convert_html($lines, $page, $mode='default')` - メインエントリーポイント
- `process_markdown($text, $page)` - Markdown→HTML変換
- `process_block_plugin($html, $page)` - `!plugin` / `#plugin` 構文処理
- `process_inline_plugin($html, $page)` - `&plugin` 構文処理
- `is_safe_markdown_url($url)` - URLスキーム検証（http/httpsのみ許可）
- `format_markdown_error($message, $level='warning')` - 統一エラー表示

#### 2. `lib/html.php` (334-450行目)
編集UI（EasyMDE統合）

- Markdownチェックボックスの状態を監視してリアルタイム切替
- PukiWiki記法選択時は通常のtextareaに戻る

#### 3. `lib/file.php`
`notemd`関連関数（上記参照）

#### 4. `pukiwiki.ini.php` (657-720行目)
Markdown設定項目の定義

### プラグイン構文の判定ロジック

#### `$markdown_support_hash_plugin = 0`（デフォルト）
- `!plugin(args)` のみをブロックプラグインとして認識
- `#` は常にMarkdown見出しとして処理

#### `$markdown_support_hash_plugin = 1`
- CommonMark仕様により自動判別:
  - `# 見出し` （`#`の後にスペース） → Markdown見出し
  - `#plugin(args)` （`#`の後にスペースなし） → プラグイン呼び出し
- `!plugin(args)` も常に動作（後方互換性）

### キャッシュ機構

**配置**: `cache/markdown/`

**キャッシュキーの生成**:
```php
$parser_id = ($markdown_support_hash_plugin == 1)
    ? 'commonmark-hashplugin'
    : 'commonmark';
$cache_key = md5($text) . '-' . $parser_id;
```

設定変更時に自動的に新しいキャッシュを使用する仕組み。

**有効期限**: デフォルト7日（`$markdown_cache_lifetime`）

### セキュリティ機能

#### URL検証（`is_safe_markdown_url()`）
- `javascript:`, `data:`, `file:` 等をブロック
- http/https のみ許可

#### 例外処理
- 全プラグイン呼び出しをtry-catchで保護
- エラーが発生してもページ全体がクラッシュしない

#### HTMLエスケープ
- 全エラーメッセージは`htmlspecialchars()`でエスケープ

### デバッグ方法

#### デバッグモード有効化
```php
// pukiwiki.ini.php
$markdown_debug_mode = 1;
```

#### 確認すべきHTMLコメント
- `<!-- Markdown Parser: commonmark or commonmark-hashplugin -->`
- `<!-- Cache: HIT or MISS -->`
- `<!-- Block Plugin Called: plugin_name -->`
- `<!-- Inline Plugin Called: plugin_name -->`

### よくある問題と対処法

#### プラグインが動作しない
- `plugin/プラグイン名.inc.php`が存在するか確認
- try-catchでキャッチされたエラーメッセージを確認
- デバッグモードでHTMLコメントを確認

#### キャッシュが更新されない
- `cache/markdown/`ディレクトリの書き込み権限を確認
- `$use_markdown_cache = 0`で一時的に無効化してテスト

#### 見出しがプラグインとして認識される
- `$markdown_support_hash_plugin = 1`の場合は`#`の後に必ずスペースを入れる
- `# 見出し` ← OK
- `#見出し` ← プラグインとして認識される

### Git運用ルール

#### リリース前の必須チェック
1. `git pull`で最新状態を取得
2. `php -l`で構文エラー検証
3. README.mdの更新（変更点を記載）
4. コミット & プッシュ
5. タグ作成 & プッシュ

```bash
# リリース作業例
git pull
php -l lib/*.php
git add .
git commit -m "バージョン v0.x.x リリース"
git push origin master
git tag v0.x.x
git push origin --tags
```

### 過去の教訓

- **switch文の構文**: if文とswitch文を混在させない（lib/html.phpで過去にエラー発生）
- **ドキュメント鮮度管理**: 古い情報を定期的に削除
- **バージョンタグ**: リリース時は必ずREADME更新とタグ作成をセットで行う
