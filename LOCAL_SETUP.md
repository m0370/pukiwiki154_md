# PukiWiki 1.5.4 Markdown対応版 - ローカルMac環境セットアップガイド

## 環境確認（既に完了）

- **PHP**: 8.4.14（Homebrewでインストール済み）
- **ファイルパーミッション**: wiki/等の書き込みディレクトリは正常に設定
- **Parsedownライブラリ**: 1.7.4 + ParsedownExtra 0.8.1
- **EasyMDE**: v2.20.0（ローカル配置済み）

## セットアップ手順

### 1. PHPサーバーの起動

PukiWikiプロジェクトディレクトリで以下のコマンドを実行します：

```bash
cd "/Users/tgoto/Library/Mobile Documents/com~apple~CloudDocs/my web site/Pukiwiki実験/pukiwiki154_md"
php -S localhost:8000
```

### 2. ブラウザでアクセス

```
http://localhost:8000
```

FrontPageが表示されます。

### 3. 動作確認項目

#### 3.1 基本的なページ表示
- http://localhost:8000 にアクセス
- PukiWiki標準ページが表示されることを確認

#### 3.2 ページの編集
- メニューから「編集」をクリック
- または http://localhost:8000/?cmd=edit&page=TestPage にアクセス
- 以下の要素が表示されることを確認：
  - **Markdownチェックボックス**: 編集フォームの下部に「Markdown」というラベル付きチェックボックス
  - **プレビューボタン**: クリックして動作確認

#### 3.3 Markdown記法のテスト
新しいページを作成して、Markdown記法をテストします：

```bash
# ブラウザから以下を実行
1. ページ名「MarkdownTest」で新規作成
2. 「Markdown」チェックボックスをONにする
3. 以下のMarkdown記法を入力：

# Markdown見出し

これは**太字**、これは*斜体*です。

## リスト

- アイテム1
- アイテム2
- アイテム3

## コードブロック

```python
def hello():
    print("Hello, World!")
```

## テーブル

| 名前 | 説明 |
|-----|------|
| A   | アイテムA |
| B   | アイテムB |
```

4. 「プレビュー」ボタンでMarkdown形式で表示されることを確認
5. 「ページの更新」で保存

#### 3.4 EasyMDEエディタの動作確認
- Markdown有効なページを編集
- EasyMDEエディタが表示される（ツールバーが見える）
- チェックボックスをON/OFFすると、エディタが切り替わることを確認
- ブラウザコンソール（F12）でエラーがないか確認

### 4. ローカルMac環境での注意事項

#### ファイルパーミッション
以下のディレクトリは自動的に書き込み可能に設定されています：
- `wiki/` - ページコンテンツ
- `backup/` - バックアップ
- `diff/` - 差分情報
- `cache/` - キャッシュ
- `attach/` - 添付ファイル
- `counter/` - カウンター

#### Markdown設定
`pukiwiki.ini.php`の以下の設定を確認（デフォルト有効）：

```php
$markdown_safemode = 1;        // XSS防止（有効）
$default_notemd = 1;           // 新規ページはMarkdown（有効）
$use_simplemde = 1;            // EasyMDE統合（有効）
$use_parsedown_extra = 1;      // 拡張記法（有効）
$markdown_debug_mode = 0;      // デバッグ出力（無効）
```

#### キャッシュクリア
ページの更新が反映されない場合：

```bash
rm -rf cache/*
```

その後、ブラウザをリロード（Cmd+R）してください。

### 5. トラブルシューティング

#### ポート8000が既に使用されている場合
```bash
php -S localhost:8080
```
別のポート番号を指定してください。

#### Markdownチェックボックスが表示されない場合
- PHPサーバーを再起動（Ctrl+Cで停止後、再実行）
- `lib/html.php`が最新版であることを確認

#### EasyMDEが読み込まれない場合
- `skin/js/easymde.min.css` と `easymde.min.js` が存在することを確認
- ブラウザコンソール（F12）でJavaScriptエラーを確認
- ファイルパーミッションを確認（読み取り可能か）

#### Parsedownがエラーを出している場合
- `plugin/vendor/erusev/parsedown/` が存在することを確認
- `$use_parsedown_extra = 0` に設定して、拡張機能を無効化

### 6. Markdown機能の詳細

#### サポート記法（ParsedownExtra）
- **見出し**: `# 見出し1`, `## 見出し2`, etc.
- **太字**: `**太字**` or `__太字__`
- **斜体**: `*斜体*` or `_斜体_`
- **リスト**: `- アイテム` or `1. 番号付きリスト`
- **コードブロック**: ````python ... ```（言語名指定可）
- **テーブル**: GitHub Flavored Markdown形式
- **脚注**: `[^1]` で参照、`[^1]: ...` で定義
- **定義リスト**: `用語\n:   説明`

#### プラグイン呼び出し（Markdown内）
- **ブロックプラグイン**: `!plugin(args)` ← PukiWikiの`#plugin`に相当
- **マルチライン**: `!plugin{{ ... }}`
- **インラインプラグイン**: `&plugin(args)`

#### リンク処理
- `[text](url)` → 外部リンク（http/httpsのみ許可）
- `[text](PageName)` → PukiWiki内部リンクに自動変換

### 7. セキュリティ

#### SafeMode
- デフォルトで有効
- 生HTML埋め込みを防止
- `<script>`, `<iframe>` などの危険なタグをフィルタリング

#### URLホワイトリスト
- http:// と https:// のみ許可
- javascript:, data:, file: などは拒否

### 8. ローカルテスト完了後

テストが完了したら、実際のWebサーバー環境への移行を検討してください：

- **Apache + mod_php**: 従来のPHP実行環境
- **Nginx + PHP-FPM**: 高性能なセットアップ
- **Docker**: 本番環境との同期が容易

### 9. ファイル構成（Mac環境確認済み）

```
pukiwiki154_md/
├── index.php                    # エントリーポイント
├── pukiwiki.ini.php             # Markdown設定項目（657-687行目）
├── lib/
│   ├── html.php                 # SimpleMDE統合（修正済み）
│   ├── convert_html.php         # Markdown処理
│   ├── file.php                 # notemd関連関数
│   └── ...
├── plugin/
│   ├── edit.inc.php             # エディタープラグイン
│   └── vendor/
│       └── erusev/parsedown/    # Parsedownライブラリ
├── wiki/                        # ページコンテンツ（書き込み可）
├── cache/                       # キャッシュ（書き込み可）
└── ...
```

### 10. 関連ドキュメント

- `.claude/CLAUDE.md` - プロジェクト開発ログ
- `README.txt` - 機能説明と変更履歴
- `AGENT.md` - 実装概要

---

## 修正履歴

### 2025-11-18
- **修正**: `lib/html.php`において、`$notemd_on`変数の初期化不足により、Markdownチェックボックスが表示されないバグを修正
- **テスト**: ローカルMac環境でMarkdown機能が正常に動作することを確認

---

**最終更新**: 2025-11-18
**ベースバージョン**: PukiWiki 1.5.4 Markdown対応版
**Parsedownバージョン**: 1.7.4 / ParsedownExtra 0.8.1
**テスト環境**: PHP 8.4.14 on macOS
