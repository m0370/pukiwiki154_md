<?php

#
#
# Parsedown Extra with Inline Footnotes
# Extended version of ParsedownExtra with Pandoc-style inline footnote support
#
# Adds support for inline footnotes: ^[inline footnote text]
#
# Original ParsedownExtra:
# https://github.com/erusev/parsedown-extra
# (c) Emanuil Rusev
#
# Inline footnote extension:
# (c) 2025 PukiWiki Markdown Project
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#

class ParsedownExtraWithInlineFootnotes extends ParsedownExtra
{
    const version = '0.8.1-inline-footnotes';

    # インライン脚注のカウンター
    protected $inlineFootnoteCount = 0;
    protected $inlineFootnotes = array();

    function __construct()
    {
        parent::__construct();

        # インライン脚注のマーカーを追加（^[ で始まる）
        # 優先順位を高くするため、配列の先頭に追加
        if (!isset($this->InlineTypes['^'])) {
            $this->InlineTypes['^'] = array();
        }
        array_unshift($this->InlineTypes['^'], 'InlineFootnote');
    }

    #
    # Inline Footnote
    #

    protected function inlineInlineFootnote($Excerpt)
    {
        # Pandocスタイルのインライン脚注を検出: ^[text]
        # 正規表現: ^^ でエスケープされた ^ 文字
        #          ^\[ で開始
        #          [^\]]+ で ] 以外の文字（脚注内容）
        #          \] で終了
        if (preg_match('/^\^\[([^\]]+)\]/', $Excerpt['text'], $matches))
        {
            # インライン脚注のカウンターをインクリメント
            $this->inlineFootnoteCount++;
            $footnoteId = 'fn-inline:' . $this->inlineFootnoteCount;
            $footnoteRefId = 'fnref-inline:' . $this->inlineFootnoteCount;

            # 脚注の内容を保存
            $footnoteText = $matches[1];
            $this->inlineFootnotes[$footnoteId] = array(
                'id' => $footnoteId,
                'ref_id' => $footnoteRefId,
                'text' => $footnoteText,
                'number' => $this->inlineFootnoteCount,
            );

            # 脚注マーカーのHTMLを生成
            # ParsedownExtraの通常の脚注と同じスタイルを使用
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'sup',
                    'attributes' => array(
                        'id' => $footnoteRefId,
                    ),
                    'handler' => array(
                        'function' => 'element',
                        'argument' => array(
                            'name' => 'a',
                            'attributes' => array(
                                'href' => '#' . $footnoteId,
                                'class' => 'footnote-ref',
                            ),
                            'text' => $this->inlineFootnoteCount,
                        ),
                        'destination' => 'elements',
                    ),
                ),
            );
        }

        return null;
    }

    #
    # Override text() to append inline footnotes at the end
    #

    function text($text)
    {
        # 親クラスの処理を実行
        $markup = parent::text($text);

        # インライン脚注がある場合、ページ末尾に追加
        if (!empty($this->inlineFootnotes))
        {
            $footnotesMarkup = $this->buildInlineFootnotesMarkup();
            $markup .= "\n" . $footnotesMarkup;

            # 次回のために脚注をクリア
            $this->inlineFootnotes = array();
            $this->inlineFootnoteCount = 0;
        }

        return $markup;
    }

    #
    # Build HTML markup for inline footnotes section
    #

    protected function buildInlineFootnotesMarkup()
    {
        $footnotesHtml = '<div class="footnotes">' . "\n";
        $footnotesHtml .= '<hr />' . "\n";
        $footnotesHtml .= '<ol>' . "\n";

        foreach ($this->inlineFootnotes as $footnote)
        {
            $footnotesHtml .= '<li id="' . htmlspecialchars($footnote['id'], ENT_QUOTES, 'UTF-8') . '">' . "\n";

            # 脚注の内容をインライン要素としてパース
            # （リンクや強調などのMarkdown記法をサポート）
            $parsedText = $this->line($footnote['text']);

            $footnotesHtml .= '<p>' . $parsedText;

            # 戻りリンクを追加
            $footnotesHtml .= ' <a href="#' . htmlspecialchars($footnote['ref_id'], ENT_QUOTES, 'UTF-8') . '" class="footnote-backref">↩</a>';

            $footnotesHtml .= '</p>' . "\n";
            $footnotesHtml .= '</li>' . "\n";
        }

        $footnotesHtml .= '</ol>' . "\n";
        $footnotesHtml .= '</div>' . "\n";

        return $footnotesHtml;
    }
}
