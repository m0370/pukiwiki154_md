名前
自由にページを追加・削除・編集できるWebページ構築スクリプトであるPukiwiki 1.5.4をベースに、Markdown記法での記載ができるように修正を加えました。
Pukiwiki ではどうしても書き方が馴染めないと言う人が少なくない上にMarkdownエディターはいろいろな優れたアプリがどんどん増えていて自分に合った使いやすいものがかなり選べる状態になっています。その考えからPukiwiki 1.5.4を無理やりMarkdown記法とPukiwiki記法の両方に対応させたものが今回のものになります。

しかし、以前に公開したPukiwiki-mdは全てのページがMarkdownでしか書けないために不便を感じることもありました。 従来型の書き方を使いたいと言うこともあるからです。 そこで、ページによって2種類の書き方を使い分けることができるような改造しました。さらに、チェックボックスで編集からそのいずれかを採用するかを選ぶことができます。

----

ベースとしたPukiwiki

    Version 1.5.4
    Copyright
      2001-2022 PukiWiki Development Team
      2001-2002 yu-ji (Based on PukiWiki 1.3 by yu-ji)
    License: GPL version 2 or (at your option) any later version

    URL:
      https://pukiwiki.osdn.jp/
      https://pukiwiki.osdn.jp/dev/
      https://osdn.jp/projects/pukiwiki/

----

特徴

    ページ毎にPukiwiki記法かMarkdown記法を選択可能になっています。
        編集画面のチェックボックスでどちらの記法を使うかを選べます。
        新規ページの初期設定はMarkdownに設定しています（変更可能です）。これまでに書いたページはPukiwiki記法で残しつつ、今後のページはMarkdown記法で書き足してゆくということもできます。
    Markdown記法を使っているときでもプラグインが使用可能です。
        ブロックプラグインでは #plugin の代わりに !plugin の表記を用います。
    リンクはPukiwiki記法（[[リンク>URL]]）でもMarkdown記法（[リンク](URL)）でも使用可能です。
    Pukiwiki記法の場合はCSSとjavascriptを各1行ずつ読み込むだけで簡単にビジュアルMarkdownエディタ「SimpleMDE」が使用可能で、書いたマークダウン書式はリアルタイムに反映されます。
        Pukiwiki記法で記載しているときは書式は反映されません。
        不要な場合は下記のpukiwiki.ini.phpで$use_simplemde = 0;を設定してください。

使用しているMarkdownパーサー

    Parsedown 1.7.4 (最新安定版)
        高速で軽量なMarkdownパーサー
        GitHub Flavored Markdown対応
        ライセンス: MIT

    ParsedownExtra 0.8.1 (最新安定版)
        Parsedownの拡張機能
        テーブル、脚注、定義リストなどの拡張記法に対応
        ライセンス: MIT

設定項目（pukiwiki.ini.php）

    $markdown_safemode = 1;
        Markdownのセーフモード設定
        1: 有効（推奨） - XSS攻撃などを防ぐ
        0: 無効 - 生HTMLの埋め込みが可能（セキュリティリスクあり）

    $use_simplemde = 1;
        SimpleMDEエディタの使用設定
        1: 有効 - リアルタイムプレビュー機能付きエディタを使用（CDN経由）
        0: 無効 - 標準のテキストエリアを使用

        Note: デフォルトではCDN（https://cdn.jsdelivr.net）経由でSimpleMDEを読み込みます。
              オフライン環境で使用する場合は、下記「SimpleMDEのローカル化」を参照してください。

    $use_parsedown_extra = 1;
        ParsedownExtraの使用設定（拡張Markdown記法）
        1: 有効 - テーブルや脚注などの拡張記法が使用可能
        0: 無効 - 基本的なMarkdown記法のみ

    $markdown_debug_mode = 0;
        Markdownデバッグモード
        1: 有効 - HTMLコメントとして詳細なデバッグ情報を出力
        0: 無効（推奨） - 本番環境での使用時

ParsedownExtraで使える拡張Markdown記法

    テーブル（GitHub Flavored Markdown形式）
        | ヘッダー1 | ヘッダー2 |
        |----------|----------|
        | セル1    | セル2    |

    脚注
        本文中に脚注[^1]を挿入できます。
        [^1]: 脚注の内容

    定義リスト
        用語
        : 定義

    Fenced Code Blocks
        ```言語名
        コード
        ```

    省略語、特殊属性など、多数の拡張機能

エラーハンドリング

    プラグイン呼び出し時のエラーを詳細に表示
    デバッグモード有効時は、エラーの原因を具体的に表示
    Markdownパーサーのエラーも適切にキャッチして表示

SimpleMDEのローカル化

SimpleMDEエディタはデフォルトでCDN（https://cdn.jsdelivr.net）経由で読み込まれますが、
オフライン環境で使用する場合や、CDN依存を避けたい場合は、ローカルにファイルを配置できます。

    手順:

    1. SimpleMDE 1.11.2をダウンロード
        https://github.com/sparksuite/simplemde-markdown-editor/releases/tag/1.11.2
        から以下のファイルをダウンロード:
        - simplemde.min.css
        - simplemde.min.js

    2. ファイルを配置
        ダウンロードしたファイルをskin/simplemde/ディレクトリなどに配置
        例: skin/simplemde/simplemde.min.css
            skin/simplemde/simplemde.min.js

    3. lib/html.phpを編集
        415行目付近のSimpleMDE読み込み部分を以下のように変更:

        変更前:
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplemde@1.11.2/dist/simplemde.min.css" ...>
        <script src="https://cdn.jsdelivr.net/npm/simplemde@1.11.2/dist/simplemde.min.js" ...>

        変更後:
        <link rel="stylesheet" href="skin/simplemde/simplemde.min.css">
        <script src="skin/simplemde/simplemde.min.js">

        Note: ローカル配置の場合、integrity属性とcrossorigin属性は不要なため削除してください。

    メリット:
    - オフライン環境での使用が可能
    - CDN障害の影響を受けない
    - プライバシー保護（外部サーバーへの接続なし）
    - ページ読み込み速度の向上（LAN内配信の場合）

プラグイン互換性

Markdown記法モードでも、PukiWikiの全てのプラグインが使用可能です。

    ブロックプラグイン（!pluginname）

        Markdown記法では、プラグイン呼び出しの先頭記号を # から ! に変更します。

        PukiWiki記法の例:
            #calendar

        Markdown記法の例:
            !calendar

        マルチライン（複数行）対応:
            !article{{
            タイトル
            本文内容...
            }}

        エラーハンドリング:
            - プラグインが存在しない場合は警告メッセージを表示
            - プラグイン実行時の例外を適切にキャッチ
            - デバッグモード有効時は詳細なエラー情報を表示

    インラインプラグイン（&pluginname）

        通常のPukiWiki記法と同じ構文で使用できます。

        使用例:
            &br;
            &color(red){赤い文字};
            &size(20){大きな文字};

        エラーハンドリング:
            - プラグインが存在しない場合は元のテキストを表示
            - プラグイン実行時の例外を適切にキャッチ
            - デバッグモード有効時は詳細なエラー情報を表示

    セキュリティ

        全てのプラグイン呼び出しは、try-catch構文で保護されています。
        プラグインの実行エラーが発生しても、ページ全体がクラッシュすることはありません。

既存のPukiwikiからの引っ越しも可能

この、ページ毎に従来のPukiwiki記法とMarkdown記法のいずれかを選んで使い分けることができるという方式で何が有利かというと、既存ページのwikiフォルダはそのままに設定ファイルなどを書き換えれば、今まで使っていたPukiwikiがそのままMarkdownも併用できるようになるという点です。全てのページがMarkdownしか使えないのは全ページの書き換えが必要だったので、新規に設置するPukiwikiにはよくても既存の稼働しているPukiwikiからの引っ越しには不向きでした。

今回の改造では、Pukiwiki記法で使用しているページの保存ファイルは手を加えず、一方でMarkdown記法を指定した保存ファイルには #notemd の偽装プラグインを書き込むようにしています。

----

修正履歴

2025-11-16 (Phase 4改良 - SimpleMDE改善、プラグイン互換性強化、コード品質向上)
    [セキュリティ強化]
    - SimpleMDEをバージョン固定（1.11.2）に変更
        * CDN URLを "latest" から明示的なバージョン指定に変更
        * 予期しないバージョン更新による互換性問題を防止
    - SRI (Subresource Integrity) 対応
        * CSSとJavaScriptにintegrity属性を追加
        * CDN改ざん検知によるセキュリティ向上
        * crossorigin属性の追加
    - インラインプラグインに例外処理を追加
        * try-catch構文でプラグイン実行を保護
        * ブロックプラグインと同等のエラーハンドリング
        * デバッグモード有効時の詳細エラー表示

    [改善]
    - SimpleMDE初期化にエラーハンドリングを追加
        * try-catch構文で例外をキャッチ
        * CDN読み込み失敗時のフォールバック処理
        * コンソールへの警告/エラーメッセージ出力
    - プラグイン互換性の完全確保
        * ブロックプラグイン（!plugin）の動作確認
        * インラインプラグイン（&plugin）の動作確認
        * マルチラインプラグインのサポート確認
        * エラーハンドリングの統一化

    [コード品質向上]
    - convert_html()関数のリファクタリング
        * 188行の長大な関数を87行に短縮
        * 6つの責務別ヘルパー関数に分割
        * PHPDocコメントによる詳細なドキュメント化
        * 保守性とテスタビリティの向上
    - 例外処理の統一化
        * format_markdown_error()関数による統一的なエラー処理
        * HTMLエスケープ処理の標準化
        * CSSクラスの統一（alert alert-warning/danger）
        * デバッグモード対応の一元化
        * ブロックプラグイン、インラインプラグイン、パーサーで統一フォーマット

    [ドキュメント追加]
    - オフライン環境対応のドキュメント追加
        * SimpleMDEローカル化手順の詳細説明
        * ファイル配置とlib/html.php編集方法
        * オフライン使用のメリット説明
    - プラグイン互換性セクションを追加
        * ブロックプラグインの使用方法と構文
        * インラインプラグインの使用方法と構文
        * エラーハンドリングの説明
        * セキュリティに関する説明

2025-11-16 (Phase 3改良 - セキュリティ強化)
    [セキュリティ強化]
    - Markdown内の画像・リンクURLスキーム検証機能を追加
        * is_safe_markdown_url()関数を実装
        * ホワイトリスト方式（http/httpsのみ許可）
        * javascript:, data:, file:などの危険なスキームをブロック
        * 不正なURLはエラーメッセージを表示
    - Safemodeのデフォルト動作を安全側に変更
        * 未設定時や不正値の場合は自動的に有効化
        * セキュリティ優先の設計に改善
    - デバッグモードにセキュリティ警告を追加
        * ブロックされたURLを追跡
        * HTMLコメントで詳細情報を出力

    [ドキュメント改善]
    - pukiwiki.ini.phpの設定コメントを強化
        * セキュリティリスクに関する警告を追加
        * 各設定項目の推奨値を明記
        * デバッグモードの本番環境使用に関する注意事項

2025-11-16 (Phase 2改良)
    [機能追加]
    - ParsedownExtra 0.8.1を導入し、拡張Markdown記法に対応
        * GitHub Flavored Markdown形式のテーブル
        * 脚注機能
        * 定義リスト
        * Fenced Code Blocks
        * その他多数の拡張機能
    - デバッグモード機能を追加（$markdown_debug_mode設定）
        * プラグイン呼び出しの追跡
        * パーサーエラーの詳細表示
        * HTMLコメントでのデバッグ情報出力

    [改善]
    - エラーハンドリングを大幅に強化
        * プラグイン呼び出し時の例外処理を追加
        * 詳細なエラーメッセージの表示
        * HTMLエスケープ処理によるセキュリティ向上
    - リンク処理を改善
        * RFC 3986準拠のURL処理に対応
        * より広範なURLフォーマットに対応
        * 日本語URLや特殊文字への対応強化
    - セーフモードのデフォルト値を安全側に設定

    [更新]
    - Parsedownを1.8.0-beta-7から1.7.4（最新安定版）にアップデート
    - ParsedownExtraを0.8.0から0.8.1（最新安定版）にアップデート
        * セキュリティ修正とバグ修正を含む
        * Parsedown 1.7.4との互換性を確保

    [追加設定項目]
    - $use_parsedown_extra: ParsedownExtraの有効/無効を切り替え
    - $markdown_debug_mode: デバッグモードの有効/無効を切り替え
