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

なお、今回の改良を有効にするためにpukiwiki.ini.phpに$markdown_safemodeと$use_simplemdeの設定項目が必要です。

既存のPukiwikiからの引っ越しも可能

この、ページ毎に従来のPukiwiki記法とMarkdown記法のいずれかを選んで使い分けることができるという方式で何が有利かというと、既存ページのwikiフォルダはそのままに設定ファイルなどを書き換えれば、今まで使っていたPukiwikiがそのままMarkdownも併用できるようになるという点です。全てのページがMarkdownしか使えないのは全ページの書き換えが必要だったので、新規に設置するPukiwikiにはよくても既存の稼働しているPukiwikiからの引っ越しには不向きでした。

今回の改造では、Pukiwiki記法で使用しているページの保存ファイルは手を加えず、一方でMarkdown記法を指定した保存ファイルには #notemd の偽装プラグインを書き込むようにしています。
