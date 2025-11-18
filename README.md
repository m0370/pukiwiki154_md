# PukiWiki 1.5.4 + Markdown

PukiWiki 1.5.4をベースに、ページ単位でPukiWiki記法とMarkdown記法を併用できるように改造したプロジェクトです。

## 概要

PukiWiki記法に馴染めないユーザーや、Markdownエディタの利便性を求めるユーザーに向けて、ページごとに2つの記法を使い分けられる柔軟なシステムを実装しました。

従来のPukiWiki記法で書かれた既存ページはそのままに、新規ページをMarkdown記法で作成・編集できます。編集画面のチェックボックスで、各ページの記法を自由に切り替え可能です。

## 主な特徴

### 📝 ページごとの記法切り替え
- 編集画面のチェックボックスで、PukiWiki記法またはMarkdown記法を選択可能
- 新規ページのデフォルトモードは設定で制御（デフォルト: Markdown）
- 既存ページはPukiWiki記法を保持したまま、新規ページはMarkdownで追加可能

### 🎨 ビジュアルMarkdownエディタ
- **EasyMDE v2.20.0** をローカルに配置
  - SimpleMDEの後継で、アクティブなメンテナンスを受けている
  - リアルタイムプレビュー機能
  - 自動保存機能（LocalStorage利用）
  - スペルチェック機能

### 🔌 プラグイン互換性
- Markdown記法モードでも、PukiWikiの全てのプラグインが使用可能
- ブロックプラグイン: `#plugin` → `!plugin`
- インラインプラグイン: `&plugin` (PukiWiki記法と同じ)
- マルチライン対応: `!plugin{{ ... }}` // v0.4以降

### 🛡️ セキュリティ
- SafeMode: 生HTML埋め込み防止（デフォルト: 有効）
- URLホワイトリスト: javascript:, data:, file: などの危険なスキームをブロック
- 全プラグイン実行を例外処理で保護
- HTTPSリンクのみをデフォルトで許可

### 📋 拡張Markdown記法
ParsedownExtra 0.8.1により、以下の拡張記法に対応:
- GitHub Flavored Markdown形式のテーブル
- 脚注機能
- 定義リスト
- Fenced Code Blocks
- その他多数の拡張機能

## ベースプロジェクト

```
PukiWiki 1.5.4
Copyright 2001-2022 PukiWiki Development Team
ライセンス: GPL version 2 or (at your option) any later version

公式サイト:
  https://pukiwiki.osdn.jp/
  https://osdn.jp/projects/pukiwiki/
```

## インストール

### 必要要件
- PHP 5.4以上
- Webサーバー (Apache, Nginx など)

### セットアップ

1. リポジトリをクローン
```bash
git clone https://github.com/m0370/pukiwiki154_md.git
cd pukiwiki154_md
```

2. サーバーにアップロード

3. `pukiwiki.ini.php` で設定を調整

4. ブラウザでアクセス

詳細なセットアップ手順については [LOCAL_SETUP.md](LOCAL_SETUP.md) を参照してください。

## 設定項目 (pukiwiki.ini.php)

### Markdownモード関連

| 設定変数 | デフォルト | 説明 |
|---------|----------|------|
| `$markdown_safemode` | 1 | SafeMode（生HTML埋め込み防止） |
| `$default_notemd` | 1 | 新規ページのデフォルトモード（1=Markdown, 0=PukiWiki） |
| `$markdown_editor` | 'easymde' | 使用するMarkdownエディタの選択 |
| `$use_parsedown_extra` | 1 | ParsedownExtra拡張記法の有効化 |
| `$markdown_debug_mode` | 0 | デバッグ情報の出力（HTMLコメント） |

### Markdownエディタの選択

`$markdown_editor` に以下の値を設定できます:

```php
$markdown_editor = 'easymde';   // EasyMDE v2.20.0 (デフォルト)
$markdown_editor = 'simplemde'; // SimpleMDE 1.11.2 (開発停止だが安定)
$markdown_editor = 'tinymde';   // TinyMDE (軽量版)
$markdown_editor = 'none';      // エディタなし
```

**後方互換性**: `$use_simplemde` も引き続き動作します。`$markdown_editor` が設定されている場合はそちらが優先されます。

### 推奨設定

```php
// セキュリティ重視
$markdown_safemode = 1;      // SafeMode有効
$default_notemd = 1;          // 新規ページはMarkdown
$markdown_editor = 'easymde'; // EasyMDE使用
$use_parsedown_extra = 1;    // 拡張記法有効
$markdown_debug_mode = 0;    // 本番環境ではデバッグ無効
```

## 使用方法

### Markdown記法でページを書く

1. ページを新規作成または編集を開く
2. 編集画面の「Markdown」チェックボックスがONになっていることを確認
3. EasyMDEエディタでMarkdown記法で記載
4. プレビューで確認後、保存

### Markdown記法で使えるプラグイン

#### ブロックプラグイン
PukiWiki記法の `#plugin` を `!plugin` に変更します:

```markdown
!calendar
!article{{
テキスト内容
}}
```

#### インラインプラグイン
PukiWiki記法と同じ構文で使用できます:

```markdown
&br;
&color(red){赤い文字};
&size(20){大きな文字};
```

### 拡張Markdown記法の例

#### テーブル
```markdown
| ヘッダー1 | ヘッダー2 |
|----------|----------|
| セル1    | セル2    |
```

#### 脚注
```markdown
本文中に脚注[^1]を挿入できます。
[^1]: 脚注の内容
```

#### 定義リスト
```markdown
用語
: 定義
```

#### コードブロック
```markdown
```python
def hello():
    print("Hello, World!")
```
```

## Parsedownライブラリ

このプロジェクトで使用しているMarkdownパーサー:

- **Parsedown 1.7.4** - 高速で軽量なMarkdownパーサー
  - GitHub Flavored Markdown対応
  - ライセンス: MIT

- **ParsedownExtra 0.8.1** - Parsedownの拡張機能
  - テーブル、脚注、定義リストなどの拡張記法に対応
  - ライセンス: MIT

配置位置: `plugin/vendor/erusev/parsedown/`

## EasyMDEエディタについて

EasyMDE v2.20.0がローカルに配置されています。

### ローカルファイル配置
```
skin/js/easymde.min.css (v2.20.0, 13KB)
skin/js/easymde.min.js (v2.20.0, 320KB)
```

### 特徴
- SimpleMDEの後継プロジェクト
- 継続的にメンテナンス・セキュリティアップデート
- API互換性を維持（SimpleMDEからの移行が容易）
- オフライン環境での使用が可能
- CDN障害の影響を受けない
- プライバシー保護（外部サーバーへの接続なし）

### 将来的な更新手順

最新版を使用する場合:

1. 最新版をダウンロード
```bash
npm install easymde
```

2. ファイルを置き換え
```
node_modules/easymde/dist/easymde.min.css → skin/js/easymde.min.css
node_modules/easymde/dist/easymde.min.js → skin/js/easymde.min.js
```

3. `lib/html.php` で必要に応じてURLを調整

## エラーハンドリング

- プラグイン呼び出し時のエラーを詳細に表示
- `$markdown_debug_mode = 1` でデバッグモードを有効化
- 例外が発生してもページ全体はクラッシュしない
- エラーメッセージはHTMLエスケープで安全に処理

## 既存PukiWikiからの移行

### 強み
- 既存ページを全く修正せずにそのまま使用可能
- 新規ページのみMarkdown記法で作成
- 段階的なMarkdown化が可能

### 実装方法
- Markdown記法のページに `#notemd` 偽装プラグインを埋め込み
- PukiWiki記法のページは従来通り動作
- システムが自動的に記法を判定して処理

## トラブルシューティング

### EasyMDEが表示されない
1. `$markdown_editor = 'easymde'` に設定されているか確認
2. `skin/js/easymde.min.js` が存在するか確認
3. ブラウザのコンソールでエラーメッセージを確認
4. `$markdown_debug_mode = 1` でデバッグモードを有効化

### Markdownが正しくレンダリングされない
1. ページの「Markdown」チェックボックスがONになっているか確認
2. Markdown記法に構文エラーがないか確認
3. `$use_parsedown_extra = 1` が有効になっているか確認

### セキュリティエラー
1. `$markdown_safemode = 1` が有効になっているか確認
2. URLスキームがhttp/httpsであるか確認
3. 生HTMLの埋め込みがないか確認（SafeMode有効時は不可）

## ライセンス

このプロジェクトはPukiWiki 1.5.4をベースにしており、GPLライセンスに従います。

**GPL version 2 or (at your option) any later version**

## 更新履歴

詳細な更新履歴は [UPDATES.md](UPDATES.md) を参照してください。

### v0.4 (2025-11-18)
- **改善**: SimpleMDEからEasyMDE v2.20.0への移行
- **機能**: Markdownエディタ選択機能の実装（easymde/simplemde/tinymde/none）
- **修正**: lib/html.phpのswitch文構文エラーを修正
- **ドキュメント**: README.mdを新規作成し、最新情報に更新

### v0.3以前
詳細は [UPDATES.md](UPDATES.md) を参照

## 開発情報

### 主要ファイル
- `lib/convert_html.php` - Markdown処理のメインロジック
- `lib/html.php` - EasyMDE統合、edit_form関数
- `lib/file.php` - notemd関連関数
- `pukiwiki.ini.php` - 設定ファイル

### テスト方法
各ページで以下をテストしてください:
- MarkdownチェックボックスのON/OFF
- エディタの動的な切り替え
- プラグイン呼び出し（ブロック/インライン）
- リンク処理（内部リンク/外部リンク）

## 貢献

改善案や不具合報告は、GitHubのIssueで受け付けています。

## サポート

問題が発生した場合:
1. [UPDATES.md](UPDATES.md) で更新履歴を確認
2. [LOCAL_SETUP.md](LOCAL_SETUP.md) でセットアップを確認
3. `$markdown_debug_mode = 1` でデバッグ情報を確認

---

**最終更新**: 2025-11-18
**バージョン**: v0.4
**ベースバージョン**: PukiWiki 1.5.4
**Parsedownバージョン**: 1.7.4 / ParsedownExtra 0.8.1
**EasyMDEバージョン**: 2.20.0
