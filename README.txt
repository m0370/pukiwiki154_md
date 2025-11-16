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
        1: 有効 - リアルタイムプレビュー機能付きエディタを使用
        0: 無効 - 標準のテキストエリアを使用

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

既存のPukiwikiからの引っ越しも可能

この、ページ毎に従来のPukiwiki記法とMarkdown記法のいずれかを選んで使い分けることができるという方式で何が有利かというと、既存ページのwikiフォルダはそのままに設定ファイルなどを書き換えれば、今まで使っていたPukiwikiがそのままMarkdownも併用できるようになるという点です。全てのページがMarkdownしか使えないのは全ページの書き換えが必要だったので、新規に設置するPukiwikiにはよくても既存の稼働しているPukiwikiからの引っ越しには不向きでした。

今回の改造では、Pukiwiki記法で使用しているページの保存ファイルは手を加えず、一方でMarkdown記法を指定した保存ファイルには #notemd の偽装プラグインを書き込むようにしています。

----

修正履歴

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
