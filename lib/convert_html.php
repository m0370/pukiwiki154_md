<?php
// PukiWiki - Yet another WikiWikiWeb clone
// convert_html.php
// Copyright
//   2002-2022 PukiWiki Development Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// function 'convert_html()', wiki text parser
// and related classes-and-functions

/**
 * Unified error message formatter for Markdown processing
 *
 * @param string $type Error type ('plugin_block', 'plugin_inline', 'parser')
 * @param string $context Context information (plugin name, etc.)
 * @param Exception $e Exception object (optional)
 * @param bool $debug_mode Debug mode flag
 * @return string Formatted error message HTML
 */
function format_markdown_error($type, $context, $e = null, $debug_mode = false)
{
	// HTML escape all user-provided content
	$safe_context = htmlspecialchars($context, ENT_QUOTES, 'UTF-8');
	$safe_message = $e ? htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') : '';

	// Determine error message and CSS class based on type
	switch ($type) {
		case 'plugin_block':
			$css_class = 'alert alert-warning';
			$tag = 'div';
			$message = 'Plugin "!' . $safe_context . '" failed';
			break;
		case 'plugin_inline':
			$css_class = 'alert alert-warning';
			$tag = 'span';
			$message = 'Plugin &amp;' . $safe_context . ' failed';
			break;
		case 'parser':
			$css_class = 'alert alert-danger';
			$tag = 'div';
			$message = 'Markdown parser error';
			break;
		default:
			$css_class = 'alert alert-danger';
			$tag = 'div';
			$message = 'Unknown error';
	}

	// Add exception details in debug mode
	if ($debug_mode && !empty($safe_message)) {
		$message .= ': ' . $safe_message;
	}

	return '<' . $tag . ' class="' . $css_class . '">' . $message . '</' . $tag . '>';
}

/**
 * Validate URL scheme for Markdown image/link URLs
 *
 * @param string $url URL to validate
 * @return bool True if URL has safe scheme (http/https only)
 */
function is_safe_markdown_url($url)
{
	// Parse URL
	$parsed = parse_url($url);

	// URLパースに失敗した場合は拒否
	if ($parsed === false || !isset($parsed['scheme'])) {
		return false;
	}

	// スキームのホワイトリストチェック（http/httpsのみ許可）
	$safe_schemes = array('http', 'https');
	$scheme = strtolower($parsed['scheme']);

	return in_array($scheme, $safe_schemes, true);
}

/**
 * Process multiline plugin content collection
 *
 * @param string $line Current line
 * @param array $lines All lines
 * @param int &$i Current line index (will be updated)
 * @param int $count Total line count
 * @return string Processed line with multiline content
 */
function process_multiline_plugin($line, $lines, &$i, $count)
{
	global $markdown_support_hash_plugin;

	// Markdown見出し記法を除外（#pluginサポート有効時のみ）
	if (!empty($markdown_support_hash_plugin)) {
		// ##以上（##, ###, ####, #####, ######）は常に見出しとして扱う
		if (preg_match('/^#{2,6}/', $line)) {
			return $line; // Not a plugin line (Markdown heading)
		}
		// 単一#の後にスペースまたは行末がある場合も見出し
		if (preg_match('/^#(\s|$)/', $line)) {
			return $line; // Not a plugin line (Markdown heading)
		}
	}

	$prefix = !empty($markdown_support_hash_plugin) ? '[#!]' : '!';
	if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
	    preg_match('/^' . $prefix . '[^{]+(\{\{+)\s*$/', $line, $m)) {
		$len = strlen($m[1]);
		$line .= "\r"; // Delimiter
		while ($i + 1 < $count) {
			$next = preg_replace('/[\r\n]*$/', '', $lines[$i + 1]);
			$i++;
			if (preg_match('/\}{' . $len . '}/', $next)) {
				$line .= $next;
				break;
			} else {
				$line .= $next . "\r";
			}
		}
	}
	return $line;
}

/**
 * Process block plugin (!plugin) in Markdown mode
 *
 * @param string $line Current line
 * @param array &$debug_info Debug information array
 * @return string Processed line or error message
 */
function process_block_plugin($line, &$debug_info)
{
	global $markdown_debug_mode, $markdown_support_hash_plugin;

	// Markdown見出し記法を除外（#pluginサポート有効時のみ）
	if (!empty($markdown_support_hash_plugin)) {
		// ##以上（##, ###, ####, #####, ######）は常に見出しとして扱う
		if (preg_match('/^#{2,6}/', $line)) {
			return null; // Not a plugin line (Markdown heading)
		}
		// 単一#の後にスペースがある場合も見出し（# 見出し）
		if (preg_match('/^#\s/', $line)) {
			return null; // Not a plugin line (Markdown heading)
		}
		// 単一#の後に何もない（行末）場合も見出し
		if (preg_match('/^#$/', $line)) {
			return null; // Not a plugin line (Markdown heading)
		}
	}

	// 接頭辞パターンを設定に応じて切り替え
	$prefix = !empty($markdown_support_hash_plugin) ? '[#!]' : '!';

	$matches = array();
	if (preg_match('/^' . $prefix . '([^\(\{]+)(?:\(([^\r]*)\))?(\{*)/', $line, $matches)) {
		$plugin = trim($matches[1]);
		if (exist_plugin_convert($plugin)) {
			$args = isset($matches[2]) ? $matches[2] : '';
			$len = strlen($matches[3]);
			if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
			    $len > 0 &&
			    preg_match('/\{{' . $len . '}\s*\r(.*)\r\}{' . $len . '}/', $line, $body)) {
				$args .= "\r" . $body[1] . "\r";
			}
			// プラグイン呼び出し（エラーハンドリング付き）
			try {
				$line = do_plugin_convert($plugin, $args);
				if (!empty($markdown_debug_mode)) {
					$debug_info['plugin_calls'][] = $plugin;
				}
			} catch (Exception $e) {
				$line = format_markdown_error('plugin_block', $plugin, $e, !empty($markdown_debug_mode));
			}
		} else {
			$error_msg = htmlspecialchars($plugin, ENT_QUOTES, 'UTF-8');
			// 接頭辞を動的に判定（元の行から取得）
			$original_line = $line;
			$prefix_char = (substr(trim($original_line), 0, 1) == '#') ? '#' : '!';
			$line = '<div class="alert alert-warning">Plugin "' . $prefix_char . $error_msg . '" not found.</div>';
			if (!empty($markdown_debug_mode)) {
				$debug_info['plugin_errors'][] = $plugin;
			}
		}
		return $line;
	}
	return null; // Not a plugin line
}

/**
 * Validate and process Markdown image URL
 *
 * @param string $line Current line
 * @param array &$debug_info Debug information array
 * @return string Processed line or error message, null if not an image
 */
function process_markdown_image($line, &$debug_info)
{
	global $markdown_debug_mode;

	$matchimg = array();
	if (preg_match('/^\!\[([^\]]*)\]\(([^\)]+)\)/u', $line, $matchimg)) {
		$img_url = trim($matchimg[2]);

		// URLスキームの安全性チェック
		if (!is_safe_markdown_url($img_url)) {
			// 危険なスキーム（javascript:等）を検出
			$line = '<div class="alert alert-warning">Unsafe image URL scheme detected. Only http/https are allowed.</div>';
			if (!empty($markdown_debug_mode)) {
				$debug_info['security_warnings'][] = 'Unsafe image URL: ' . htmlspecialchars(substr($img_url, 0, 50), ENT_QUOTES, 'UTF-8');
			}
		}
		// 安全なURLの場合はmake_linkに渡さない（Parsedownに任せる）
		return $line;
	}
	return null; // Not an image line
}

/**
 * Process Markdown links and convert to PukiWiki format
 *
 * @param string $line Current line
 * @param array &$debug_info Debug information array
 * @return string Processed line
 */
function process_markdown_links($line, &$debug_info)
{
	global $markdown_debug_mode;

	// Markdown式リンクをPukiwiki式リンクに変換（改善版：より広範なURL対応）
	// RFC 3986準拠のURLパターンに対応 + セキュリティチェック
	$line = preg_replace_callback(
		'/\[([^\]]+)\]\(([^\s\)]+)(?:\s+\"([^\"]+)\")?\)/u',
		function($matches) use (&$debug_info, $markdown_debug_mode) {
			$text = $matches[1];
			$url = $matches[2];

			// URLスキームの安全性チェック
			if (!is_safe_markdown_url($url)) {
				// 危険なスキームを検出した場合は警告を表示
				if (!empty($markdown_debug_mode)) {
					if (!isset($debug_info['security_warnings'])) {
						$debug_info['security_warnings'] = array();
					}
					$debug_info['security_warnings'][] = 'Unsafe link URL: ' . htmlspecialchars(substr($url, 0, 50), ENT_QUOTES, 'UTF-8');
				}
				return '<span class="alert alert-warning">[Invalid URL]</span>';
			}

			// タイトルは無視（PukiWikiリンクに変換時）
			return "[[${text}>${url}]]";
		},
		$line
	);

	// Pukiwiki式アンカーを非表示に
	$line = preg_replace('/\[\#[a-zA-Z0-9]{8}\]$/u', "", $line);

	// リンク処理
	$line = make_link($line);

	return $line;
}

/**
 * Initialize league/commonmark Markdown parser
 *
 * @param array &$debug_info Debug information array
 * @return \League\CommonMark\MarkdownConverter MarkdownConverter instance
 * @throws Exception If league/commonmark is not installed
 */
function init_markdown_parser(&$debug_info)
{
	global $markdown_debug_mode;

	// Check if league/commonmark is available
	if (!file_exists(dirname(PLUGIN_DIR) . '/vendor/autoload.php')) {
		throw new Exception('league/commonmark not found. Please install via composer: composer require league/commonmark');
	}

	require_once dirname(PLUGIN_DIR) . '/vendor/autoload.php';

	// Configure environment with safe HTML handling
	$environment = new \League\CommonMark\Environment\Environment([
		'html_input' => 'allow',  // Allow HTML from make_link() (PukiWiki footnotes, links, etc.)
		'allow_unsafe_links' => false,  // Block javascript:, data:, etc.
	]);

	// Add CommonMark Core Extension (required for basic rendering)
	$environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());

	// Add GitHub Flavored Markdown extension
	// Includes: strikethrough (~~text~~), tables, task lists (- [ ]), autolinks
	$environment->addExtension(new \League\CommonMark\Extension\GithubFlavoredMarkdownExtension());

	// Add Footnote extension
	// Supports: reference-style footnotes ([^1]) and Pandoc-style inline footnotes (^[text])
	$environment->addExtension(new \League\CommonMark\Extension\Footnote\FootnoteExtension());

	$parser = new \League\CommonMark\MarkdownConverter($environment);

	if (!empty($markdown_debug_mode)) {
		$debug_info['parser'] = 'league/commonmark 2.x (GFM + Footnotes)';
	}

	return $parser;
}

/**
 * Generate debug output HTML comment
 *
 * @param array $debug_info Debug information array
 * @param bool $safemode Safemode setting
 * @return string Debug output HTML comment
 */
function generate_debug_output($debug_info, $safemode)
{
	$debug_output = '<!-- Markdown Debug Info for ' . htmlspecialchars($debug_info['page'], ENT_QUOTES, 'UTF-8') . "\n";
	$debug_output .= 'Parser: ' . $debug_info['parser'] . "\n";
	$debug_output .= 'Safemode: ' . ($safemode ? 'ON' : 'OFF') . "\n";
	if (isset($debug_info['hash_plugin_support'])) {
		$debug_output .= 'Hash Plugin Support: ' . $debug_info['hash_plugin_support'] . "\n";
	}
	$debug_output .= 'Lines: ' . $debug_info['line_count'] . "\n";
	if (isset($debug_info['cache'])) {
		$debug_output .= 'Cache: ' . $debug_info['cache'];
		if (isset($debug_info['cache_file'])) {
			$debug_output .= ' (' . $debug_info['cache_file'] . ')';
		}
		$debug_output .= "\n";
	}
	if (isset($debug_info['parser_warning'])) {
		$debug_output .= 'Parser Warning: ' . $debug_info['parser_warning'] . "\n";
	}
	if (isset($debug_info['plugin_calls'])) {
		$debug_output .= 'Plugin calls: ' . implode(', ', $debug_info['plugin_calls']) . "\n";
	}
	if (isset($debug_info['plugin_errors'])) {
		$debug_output .= 'Plugin errors: ' . implode(', ', $debug_info['plugin_errors']) . "\n";
	}
	if (isset($debug_info['security_warnings'])) {
		$debug_output .= 'Security warnings: ' . implode(', ', $debug_info['security_warnings']) . "\n";
	}
	$debug_output .= 'WARNING: Debug mode is enabled. Disable in production!' . "\n";
	$debug_output .= '-->' . "\n";

	return $debug_output;
}

/**
 * Convert league/commonmark footnotes to PukiWiki note format
 *
 * Extracts Markdown footnotes from <div class="footnotes"> and adds them
 * to the global $foot_explain array for display in <div id="note">.
 * Does NOT support PukiWiki footnotes when #notemd is enabled.
 *
 * @param string $html HTML output from Markdown conversion
 * @return string HTML with footnotes section removed
 */
function convert_footnotes_to_pukiwiki_format($html)
{
	global $foot_explain, $vars;

	// Extract footnotes from <div class="footnotes">
	if (!preg_match('/<div class="footnotes"[^>]*>(.*?)<\/div>\s*$/s', $html, $matches)) {
		// No footnotes found - try without trailing \s*$
		if (!preg_match('/<div class="footnotes"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
			// Still no match - return as-is
			return $html;
		}
	}

	$footnotes_html = $matches[1];

	// Remove footnotes section from HTML
	$html = preg_replace('/<div class="footnotes"[^>]*>.*?<\/div>\s*$/s', '', $html);
	// Also try without trailing anchor
	$html = preg_replace('/<div class="footnotes"[^>]*>.*?<\/div>/s', '', $html);

	// Extract individual footnotes from <ol><li> structure
	if (!preg_match('/<ol[^>]*>(.*?)<\/ol>/s', $footnotes_html, $ol_matches)) {
		return $html;
	}

	// Parse each <li id="fn:X"> footnote
	preg_match_all('/<li[^>]*id="fn:([^"]+)"[^>]*>(.*?)<\/li>/s', $ol_matches[1], $li_matches, PREG_SET_ORDER);

	if (empty($li_matches)) {
		return $html;
	}

	// Get page URL for links
	$script = get_page_uri(isset($vars['page']) ? $vars['page'] : '');

	// Add footnotes to $foot_explain array
	$footnote_counter = count($foot_explain) + 1; // Continue numbering from existing footnotes

	foreach ($li_matches as $li_match) {
		$footnote_id = $li_match[1]; // e.g., "3" or "fn1"
		$content = $li_match[2];

		// Remove backref link (↩ symbol)
		$content = preg_replace('/<a[^>]*class="footnote-backref"[^>]*>.*?<\/a>/s', '', $content);

		// Clean up content
		$content = trim($content);
		// Remove wrapping <p> tags if present
		$content = preg_replace('/^<p>(.*?)<\/p>$/s', '$1', $content);

		// Build footnote HTML for PukiWiki format and add to $foot_explain
		$foot_explain[$footnote_counter] = '<a id="notefoot_' . $footnote_counter . '" href="' .
			$script . '#notetext_' . $footnote_counter . '" class="note_super">*' .
			$footnote_counter . '</a>' . "\n" .
			'<span class="small">' . $content . '</span><br />';

		$footnote_counter++;
	}

	// Also need to replace footnote references in HTML
	// CommonMark generates: <sup><a href="#fn:3" id="fnref:3" ...>3</a></sup>
	// We need: <a id="notetext_3" href="...#notefoot_3" class="note_super">*3</a>

	$html = preg_replace_callback(
		'/<sup[^>]*><a[^>]*href="#fn:([^"]+)"[^>]*>(\d+)<\/a><\/sup>/',
		function($matches) use ($script) {
			$display_num = $matches[2]; // Use CommonMark's original numbering
			return '<a id="notetext_' . $display_num . '" href="' . $script .
				'#notefoot_' . $display_num . '" class="note_super">*' . $display_num . '</a>';
		},
		$html
	);

	return $html;
}

/**
 * Clean up expired markdown cache files (probabilistic, 1% chance)
 *
 * @param int $lifetime Cache lifetime in seconds
 * @return void
 */
function cleanup_markdown_cache($lifetime)
{
	// 1%の確率で実行（アクセス100回に1回）
	if (mt_rand(1, 100) !== 1) {
		return;
	}

	if (empty($lifetime) || !is_dir(CACHE_DIR)) {
		return;
	}

	$files = @glob(CACHE_DIR . 'markdown_*.cache');
	if ($files === false) {
		return;
	}

	$now = time();
	$deleted = 0;

	foreach ($files as $file) {
		$mtime = @filemtime($file);
		if ($mtime !== false && ($now - $mtime) > $lifetime) {
			if (@unlink($file)) {
				$deleted++;
			}
		}
	}

	// デバッグログ（オプション）
	global $markdown_debug_mode;
	if (!empty($markdown_debug_mode) && $deleted > 0) {
		// error_log('Markdown cache cleanup: deleted ' . $deleted . ' files');
	}
}

function convert_html($lines)
{
	global $vars, $digest, $markdown_debug_mode, $use_markdown_cache, $markdown_cache_lifetime;
	static $contents_id = 0;

	// Set digest
	$digest = md5(join('', get_source($vars['page'])));

	if (! is_array($lines)) $lines = explode("\n", $lines);

	// デバッグモード用の情報収集
	$debug_info = array();
	if (!empty($markdown_debug_mode)) {
		$debug_info['page'] = isset($vars['page']) ? $vars['page'] : 'unknown';
		$debug_info['line_count'] = count($lines);
		$debug_info['has_notemd'] = preg_grep('/^\#notemd/', $lines) ? true : false;
		$debug_info['parsedown_version'] = class_exists('Parsedown') ? \Parsedown::version : 'not loaded';
		$debug_info['hash_plugin_support'] = !empty($markdown_support_hash_plugin) ? 'enabled' : 'disabled';
	}

	// PukiWiki記法とMarkdown記法の分岐
	if (! preg_grep('/^\#notemd/', $lines) ) {
		// Pukiwiki記法
		$body = new Body(++$contents_id);
		$body->parse($lines);
		return $body->toString();
	}

	// Markdown記法の処理

	// キャッシュ機能（改善版: ファイルロック + JSON/serialize両対応 + 有効期限チェック）
	$cache_hit = false;
	$cache_file = null;
	if (!empty($use_markdown_cache)) {
		// キャッシュファイル名の生成（ページ名とダイジェストから）
		$page_name = isset($vars['page']) ? $vars['page'] : 'unknown';
		$parser_mode = !empty($markdown_support_hash_plugin) ? 'commonmark-hashplugin' : 'commonmark';
		$cache_key = md5($page_name . ':' . $parser_mode . ':' . $digest);
		$cache_file = CACHE_DIR . 'markdown_' . $cache_key . '.cache';

		// キャッシュ読み込み（ファイルロック付き）
		if (file_exists($cache_file)) {
			$fp = @fopen($cache_file, 'r');
			if ($fp !== false) {
				if (flock($fp, LOCK_SH)) {  // 共有ロック（読み込み用）
					$content = stream_get_contents($fp);
					flock($fp, LOCK_UN);
					fclose($fp);

					if ($content !== false && $content !== '') {
						// JSON形式優先、失敗時serialize（後方互換）
						$cached_data = @json_decode($content, true);
						if (!is_array($cached_data)) {
							$cached_data = @unserialize($content);
						}

						// キャッシュデータ検証
						$expected_parser = !empty($markdown_support_hash_plugin) ? 'commonmark-hashplugin' : 'commonmark';
						if (is_array($cached_data) &&
						    isset($cached_data['digest']) &&
						    $cached_data['digest'] === $digest &&
						    isset($cached_data['parser']) &&
						    $cached_data['parser'] === $expected_parser) {

							// 有効期限チェック
							$is_expired = false;
							if (isset($cached_data['timestamp']) && !empty($markdown_cache_lifetime)) {
								$age = time() - $cached_data['timestamp'];
								if ($age > $markdown_cache_lifetime) {
									$is_expired = true;
									if (!empty($markdown_debug_mode)) {
										$debug_info['cache'] = 'EXPIRED (age: ' . round($age / 86400, 1) . ' days)';
									}
								}
							}

							if (!$is_expired) {
								// キャッシュヒット - 脚注処理を実行してから返す
								$cache_hit = true;
								if (!empty($markdown_debug_mode)) {
									$debug_info['cache'] = 'HIT';
									$debug_info['cache_file'] = basename($cache_file);
									if (isset($cached_data['timestamp'])) {
										$debug_info['cache_age'] = round((time() - $cached_data['timestamp']) / 3600, 1) . 'h';
									}
								}
								// Convert Markdown footnotes to PukiWiki format (必須: $foot_explainを更新)
								$html_with_footnotes = convert_footnotes_to_pukiwiki_format($cached_data['html']);

								if (!empty($markdown_debug_mode)) {
									// デバッグ情報を追加して返す
									$debug_output = generate_debug_output($debug_info, true);
									return $debug_output . $html_with_footnotes;
								}
								return $html_with_footnotes;
							}
						}
					}
				} else {
					fclose($fp);
					if (!empty($markdown_debug_mode)) {
						$debug_info['cache'] = 'MISS (lock failed)';
					}
				}
			} else {
				if (!empty($markdown_debug_mode)) {
					$debug_info['cache'] = 'MISS (open failed)';
				}
			}
		}

		if (!empty($markdown_debug_mode) && !$cache_hit) {
			if (!isset($debug_info['cache'])) {
				$debug_info['cache'] = 'MISS';
			}
			$debug_info['cache_file'] = basename($cache_file);
		}
	}

	$count = count($lines);
	$result_lines = array();

	for ($i = 0; $i < $count; $i++) {
		$line = $lines[$i];
		// #author,#notemd,#freezeはMarkdown Parserに渡さない（行頭の単独指定のみ）
		$line = preg_replace('/^(\#author\(.*\)|\#notemd|\#freeze)\s*$/', '', $line);

		// PukiWiki脚注記法 ((コメント)) を Markdownインライン脚注 ^[コメント] に変換
		$line = preg_replace('/\(\((.+?)\)\)/', '^[$1]', $line);

		// マルチラインプラグインの処理
		$line = process_multiline_plugin($line, $lines, $i, $count);

		// ブロックプラグインの処理
		$plugin_result = process_block_plugin($line, $debug_info);
		if ($plugin_result !== null) {
			$line = $plugin_result;
		} else {
			// 画像の処理
			$image_result = process_markdown_image($line, $debug_info);
			if ($image_result !== null) {
				$line = $image_result;
			} else {
				// リンクの処理
				$line = process_markdown_links($line, $debug_info);
			}
		}

		$line = str_replace(array("\r\n","\n","\r"), "", $line);
		$result_lines[] = $line;
	}

	$text = implode("\n", $result_lines);

	// league/commonmark でのMarkdown変換（エラーハンドリング付き）
	try {
		$parser = init_markdown_parser($debug_info);

		// Markdown to HTML conversion
		$raw_html = $parser->convert($text)->getContent();

		// キャッシュに保存（改善版: JSON形式 + エラーハンドリング）
		// 注意: 脚注処理前のHTML（<div class="footnotes">付き）を保存
		if (!empty($use_markdown_cache) && $cache_file !== null) {
			$parser_mode = !empty($markdown_support_hash_plugin) ? 'commonmark-hashplugin' : 'commonmark';
			$cache_data = array(
				'digest' => $digest,
				'parser' => $parser_mode,
				'html' => $raw_html,  // 生HTML（脚注未処理）をキャッシュ
				'timestamp' => time(),
				'version' => 2  // JSON形式識別子
			);

			// JSON変換
			$json = json_encode($cache_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if ($json === false) {
				// JSON変換失敗（ログのみ、処理は継続）
				if (!empty($markdown_debug_mode)) {
					$debug_info['cache_error'] = 'JSON encode failed';
				}
			} else {
				// ディレクトリ確認・作成
				if (!is_dir(CACHE_DIR)) {
					if (!@mkdir(CACHE_DIR, 0755, true)) {
						// ディレクトリ作成失敗（ログのみ）
						if (!empty($markdown_debug_mode)) {
							$debug_info['cache_error'] = 'mkdir failed: ' . CACHE_DIR;
						}
						$json = false;  // 書き込みスキップ
					}
				}

				// ファイル書き込み
				if ($json !== false) {
					$bytes = @file_put_contents($cache_file, $json, LOCK_EX);
					if ($bytes === false) {
						// 書き込み失敗（ログのみ）
						if (!empty($markdown_debug_mode)) {
							$debug_info['cache_error'] = 'write failed: ' . basename($cache_file);
						}
					} elseif (!empty($markdown_debug_mode)) {
						$debug_info['cache_write'] = 'OK (' . $bytes . ' bytes)';
					}
				}
			}
		}

		// Convert Markdown footnotes to PukiWiki format
		// キャッシュ有無に関わらず、この処理で$foot_explainを更新
		$result = convert_footnotes_to_pukiwiki_format($raw_html);

		// デバッグ情報を出力
		if (!empty($markdown_debug_mode) && isset($debug_info['page'])) {
			$debug_output = generate_debug_output($debug_info, true);
			$result = $debug_output . $result;
		}

		// キャッシュクリーンアップ（確率的実行）
		if (!empty($use_markdown_cache) && !empty($markdown_cache_lifetime)) {
			cleanup_markdown_cache($markdown_cache_lifetime);
		}

		return $result;

	} catch (Exception $e) {
		return format_markdown_error('parser', '', $e, !empty($markdown_debug_mode));
	}
}

// Block elements
class Element
{
	var $parent;
	var $elements; // References of childs
	var $last;     // Insert new one at the back of the $last

	function Element()
	{
		$this->__construct();
	}
	function __construct()
	{
		$this->elements = array();
		$this->last     = & $this;
	}

	function setParent(& $parent)
	{
		$this->parent = & $parent;
	}

	function & add(& $obj)
	{
		if ($this->canContain($obj)) {
			return $this->insert($obj);
		} else {
			return $this->parent->add($obj);
		}
	}

	function & insert(& $obj)
	{
		$obj->setParent($this);
		$this->elements[] = & $obj;

		return $this->last = & $obj->last;
	}

	function canContain(& $obj)
	{
		return TRUE;
	}

	function wrap($string, $tag, $param = '', $canomit = TRUE)
	{
		return ($canomit && $string == '') ? '' :
			'<' . $tag . $param . '>' . $string . '</' . $tag . '>';
	}

	function toString()
	{
		$ret = array();
		foreach (array_keys($this->elements) as $key)
			$ret[] = $this->elements[$key]->toString();
		return join("\n", $ret);
	}

	function dump($indent = 0)
	{
		$ret = str_repeat(' ', $indent) . get_class($this) . "\n";
		$indent += 2;
		foreach (array_keys($this->elements) as $key) {
			$ret .= is_object($this->elements[$key]) ?
				$this->elements[$key]->dump($indent) : '';
				//str_repeat(' ', $indent) . $this->elements[$key];
		}
		return $ret;
	}
}

// Returns inline-related object
function & Factory_Inline($text)
{
	// Check the first letter of the line
	if (substr($text, 0, 1) == '~') {
		return new Paragraph(' ' . substr($text, 1));
	} else {
		return new Inline($text);
	}
}

function & Factory_DList(& $root, $text)
{
	$out = explode('|', ltrim($text), 2);
	if (count($out) < 2) {
		return Factory_Inline($text);
	} else {
		return new DList($out);
	}
}

// '|'-separated table
function & Factory_Table(& $root, $text)
{
	if (! preg_match('/^\|(.+)\|([hHfFcC]?)$/', $text, $out)) {
		return Factory_Inline($text);
	} else {
		return new Table($out);
	}
}

// Comma-separated table
function & Factory_YTable(& $root, $text)
{
	if ($text == ',') {
		return Factory_Inline($text);
	} else {
		return new YTable(csv_explode(',', substr($text, 1)));
	}
}

function & Factory_Div(& $root, $text)
{
	$matches = array();

	// Seems block plugin?
	if (PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK) {
		// Usual code
		if (preg_match('/^\#([^\(]+)(?:\((.*)\))?/', $text, $matches) &&
		    exist_plugin_convert($matches[1])) {
			return new Div($matches);
		}
	} else {
		// Hack code
		if(preg_match('/^#([^\(\{]+)(?:\(([^\r]*)\))?(\{*)/', $text, $matches) &&
		   exist_plugin_convert($matches[1])) {
			$len  = strlen($matches[3]);
			$body = array();
			if ($len == 0) {
				return new Div($matches); // Seems legacy block plugin
			} else if (preg_match('/\{{' . $len . '}\s*\r(.*)\r\}{' . $len . '}/', $text, $body)) { 
				$matches[2] .= "\r" . $body[1] . "\r";
				return new Div($matches); // Seems multiline-enabled block plugin
			}
		}
	}

	return new Paragraph($text);
}

// Inline elements
class Inline extends Element
{
	function Inline($text)
	{
		$this->__construct($text);
	}
	function __construct($text)
	{
		parent::__construct();
		$this->elements[] = trim((substr($text, 0, 1) == "\n") ?
			$text : make_link($text));
	}

	function & insert(& $obj)
	{
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	function canContain(& $obj)
	{
		return is_a($obj, 'Inline');
	}

	function toString()
	{
		global $line_break;
		return join(($line_break ? '<br />' . "\n" : "\n"), $this->elements);
	}

	function & toPara($class = '')
	{
		$obj = new Paragraph('', $class);
		$obj->insert($this);
		return $obj;
	}
}

// Paragraph: blank-line-separated sentences
class Paragraph extends Element
{
	var $param;

	function Paragraph($text, $param = '')
	{
		$this->__construct($text, $param);
	}
	function __construct($text, $param = '')
	{
		parent::__construct();
		$this->param = $param;
		if ($text == '') return;

		if (substr($text, 0, 1) == '~')
			$text = ' ' . substr($text, 1);

		$this->insert(Factory_Inline($text));
	}

	function canContain(& $obj)
	{
		return is_a($obj, 'Inline');
	}

	function toString()
	{
		return $this->wrap(parent::toString(), 'p', $this->param);
	}
}

// * Heading1
// ** Heading2
// *** Heading3
class Heading extends Element
{
	var $level;
	var $id;
	var $msg_top;

	function Heading(& $root, $text)
	{
		$this->__construct($root, $text);
	}
	function __construct(& $root, $text)
	{
		parent::__construct();

		$this->level = min(3, strspn($text, '*'));
		list($text, $this->msg_top, $this->id) = $root->getAnchor($text, $this->level);
		$this->insert(Factory_Inline($text));
		$this->level++; // h2,h3,h4
	}

	function & insert(& $obj)
	{
		parent::insert($obj);
		return $this->last = & $this;
	}

	function canContain(& $obj)
	{
		return FALSE;
	}

	function toString()
	{
		return $this->msg_top .  $this->wrap(parent::toString(),
			'h' . $this->level, ' id="' . $this->id . '"');
	}
}

// ----
// Horizontal Rule
class HRule extends Element
{
	function HRule(& $root, $text)
	{
		$this->__construct($root, $text);
	}
	function __construct(& $root, $text)
	{
		parent::__construct();
	}

	function canContain(& $obj)
	{
		return FALSE;
	}

	function toString()
	{
		global $hr;
		return $hr;
	}
}

// Lists (UL, OL, DL)
class ListContainer extends Element
{
	var $tag;
	var $tag2;
	var $level;
	var $style;

	function ListContainer($tag, $tag2, $head, $text)
	{
		$this->__construct($tag, $tag2, $head, $text);
	}
	function __construct($tag, $tag2, $head, $text)
	{
		parent::__construct();

		$this->tag   = $tag;
		$this->tag2  = $tag2;
		$this->level = min(3, strspn($text, $head));
		$text = ltrim(substr($text, $this->level));

		parent::insert(new ListElement($this->level, $tag2));
		if ($text != '')
			$this->last = & $this->last->insert(Factory_Inline($text));
	}

	function canContain(& $obj)
	{
		return (! is_a($obj, 'ListContainer')
			|| ($this->tag == $obj->tag && $this->level == $obj->level));
	}

	function setParent(& $parent)
	{
		parent::setParent($parent);

		$step = $this->level;
		if (isset($parent->parent) && is_a($parent->parent, 'ListContainer'))
			$step -= $parent->parent->level;

		$this->style = sprintf(pkwk_list_attrs_template(), $this->level, $step);
	}

	function & insert(& $obj)
	{
		if (! is_a($obj, get_class($this)))
			return $this->last = & $this->last->insert($obj);

		// Break if no elements found (BugTrack/524)
		if (count($obj->elements) == 1 && empty($obj->elements[0]->elements))
			return $this->last->parent; // up to ListElement

		// Move elements
		foreach(array_keys($obj->elements) as $key)
			parent::insert($obj->elements[$key]);

		return $this->last;
	}

	function toString()
	{
		return $this->wrap(parent::toString(), $this->tag, $this->style);
	}
}

class ListElement extends Element
{
	function ListElement($level, $head)
	{
		$this->__construct($level, $head);
	}
	function __construct($level, $head)
	{
		parent::__construct();
		$this->level = $level;
		$this->head  = $head;
	}

	function canContain(& $obj)
	{
		return (! is_a($obj, 'ListContainer') || ($obj->level > $this->level));
	}

	function toString()
	{
		return $this->wrap(parent::toString(), $this->head);
	}
}

// - One
// - Two
// - Three
class UList extends ListContainer
{
	function UList(& $root, $text)
	{
		$this->__construct($root, $text);
	}
	function __construct(& $root, $text)
	{
		parent::__construct('ul', 'li', '-', $text);
	}
}

// + One
// + Two
// + Three
class OList extends ListContainer
{
	function OList(& $root, $text)
	{
		$this->__construct($root, $text);
	}
	function __construct(& $root, $text)
	{
		parent::__construct('ol', 'li', '+', $text);
	}
}

// : definition1 | description1
// : definition2 | description2
// : definition3 | description3
class DList extends ListContainer
{
	function DList($out)
	{
		$this->__construct($out);
	}
	function __construct($out)
	{
		parent::__construct('dl', 'dt', ':', $out[0]);
		$this->last = & Element::insert(new ListElement($this->level, 'dd'));
		if ($out[1] != '')
			$this->last = & $this->last->insert(Factory_Inline($out[1]));
	}
}

// > Someting cited
// > like E-mail text
class BQuote extends Element
{
	var $level;

	function BQuote(& $root, $text)
	{
		$this->__construct($root, $text);
	}
	function __construct(& $root, $text)
	{
		parent::__construct();

		$head = substr($text, 0, 1);
		$this->level = min(3, strspn($text, $head));
		$text = ltrim(substr($text, $this->level));

		if ($head == '<') { // Blockquote close
			$level       = $this->level;
			$this->level = 0;
			$this->last  = & $this->end($root, $level);
			if ($text != '')
				$this->last = & $this->last->insert(Factory_Inline($text));
		} else {
			$this->insert(Factory_Inline($text));
		}
	}

	function canContain(& $obj)
	{
		return (! is_a($obj, get_class($this)) || $obj->level >= $this->level);
	}

	function & insert(& $obj)
	{
		// BugTrack/521, BugTrack/545
		if (is_a($obj, 'inline'))
			return parent::insert($obj->toPara(' class="quotation"'));

		if (is_a($obj, 'BQuote') && $obj->level == $this->level && count($obj->elements)) {
			$obj = & $obj->elements[0];
			if (is_a($this->last, 'Paragraph') && count($obj->elements))
				$obj = & $obj->elements[0];
		}
		return parent::insert($obj);
	}

	function toString()
	{
		return $this->wrap(parent::toString(), 'blockquote');
	}

	function & end(& $root, $level)
	{
		$parent = & $root->last;

		while (is_object($parent)) {
			if (is_a($parent, 'BQuote') && $parent->level == $level)
				return $parent->parent;
			$parent = & $parent->parent;
		}
		return $this;
	}
}

class TableCell extends Element
{
	var $tag = 'td'; // {td|th}
	var $colspan = 1;
	var $rowspan = 1;
	var $style; // is array('width'=>, 'align'=>...);

	function TableCell($text, $is_template = FALSE)
	{
		$this->__construct($text, $is_template);
	}
	function __construct($text, $is_template = FALSE)
	{
		parent::__construct();
		$this->style = $matches = array();

		while (preg_match('/^(?:(LEFT|CENTER|RIGHT)|(BG)?COLOR\((#?\w{1,20})\)|SIZE\((\d{1,2})\)|(BOLD)):(.*)$/',
		    $text, $matches)) {
			if ($matches[1]) {
				$this->style['align'] = 'text-align:' . strtolower($matches[1]) . ';';
				$text = $matches[6];
			} else if ($matches[3]) {
				$name = $matches[2] ? 'background-color' : 'color';
				$this->style[$name] = $name . ':' . htmlsc($matches[3]) . ';';
				$text = $matches[6];
			} else if (is_numeric($matches[4])) {
				$this->style['size'] = 'font-size:' . htmlsc($matches[4]) . 'px;';
				$text = $matches[6];
			} else if ($matches[5]) {
				$this->style['bold'] = 'font-weight:bold;';
				$text = $matches[6];
			}
		}
		if ($is_template && is_numeric($text))
			$this->style['width'] = 'width:' . $text . 'px;';

		if ($text == '>') {
			$this->colspan = 0;
		} else if ($text == '~') {
			$this->rowspan = 0;
		} else if (substr($text, 0, 1) == '~') {
			$this->tag = 'th';
			$text      = substr($text, 1);
		}

		if ($text != '' && $text[0] == '#') {
			// Try using Div class for this $text
			$obj = & Factory_Div($this, $text);
			if (is_a($obj, 'Paragraph'))
				$obj = & $obj->elements[0];
		} else {
			$obj = & Factory_Inline($text);
		}

		$this->insert($obj);
	}

	function setStyle(& $style)
	{
		foreach ($style as $key=>$value)
			if (! isset($this->style[$key]))
				$this->style[$key] = $value;
	}

	function toString()
	{
		if ($this->rowspan == 0 || $this->colspan == 0) return '';

		$param = ' class="style_' . $this->tag . '"';
		if ($this->rowspan > 1)
			$param .= ' rowspan="' . $this->rowspan . '"';
		if ($this->colspan > 1) {
			$param .= ' colspan="' . $this->colspan . '"';
			unset($this->style['width']);
		}
		if (! empty($this->style))
			$param .= ' style="' . join(' ', $this->style) . '"';

		return $this->wrap(parent::toString(), $this->tag, $param, FALSE);
	}
}

// | title1 | title2 | title3 |
// | cell1  | cell2  | cell3  |
// | cell4  | cell5  | cell6  |
class Table extends Element
{
	var $type;
	var $types;
	var $col; // number of column

	function Table($out)
	{
		$this->__construct($out);
	}
	function __construct($out)
	{
		parent::__construct();

		$cells       = explode('|', $out[1]);
		$this->col   = count($cells);
		$this->type  = strtolower($out[2]);
		$this->types = array($this->type);
		$is_template = ($this->type == 'c');
		$row = array();
		foreach ($cells as $cell)
			$row[] = new TableCell($cell, $is_template);
		$this->elements[] = $row;
	}

	function canContain(& $obj)
	{
		return is_a($obj, 'Table') && ($obj->col == $this->col);
	}

	function & insert(& $obj)
	{
		$this->elements[] = $obj->elements[0];
		$this->types[]    = $obj->type;
		return $this;
	}

	function toString()
	{
		static $parts = array('h'=>'thead', 'f'=>'tfoot', ''=>'tbody');

		// Set rowspan (from bottom, to top)
		for ($ncol = 0; $ncol < $this->col; $ncol++) {
			$rowspan = 1;
			foreach (array_reverse(array_keys($this->elements)) as $nrow) {
				$row = & $this->elements[$nrow];
				if ($row[$ncol]->rowspan == 0) {
					++$rowspan;
					continue;
				}
				$row[$ncol]->rowspan = $rowspan;
				// Inherits row type
				while (--$rowspan)
					$this->types[$nrow + $rowspan] = $this->types[$nrow];
				$rowspan = 1;
			}
		}

		// Set colspan and style
		$stylerow = NULL;
		foreach (array_keys($this->elements) as $nrow) {
			$row = & $this->elements[$nrow];
			if ($this->types[$nrow] == 'c')
				$stylerow = & $row;
			$colspan = 1;
			foreach (array_keys($row) as $ncol) {
				if ($row[$ncol]->colspan == 0) {
					++$colspan;
					continue;
				}
				$row[$ncol]->colspan = $colspan;
				if ($stylerow !== NULL) {
					$row[$ncol]->setStyle($stylerow[$ncol]->style);
					// Inherits column style
					while (--$colspan)
						$row[$ncol - $colspan]->setStyle($stylerow[$ncol]->style);
				}
				$colspan = 1;
			}
		}

		// toString
		$string = '';
		foreach ($parts as $type => $part)
		{
			$part_string = '';
			foreach (array_keys($this->elements) as $nrow) {
				if ($this->types[$nrow] != $type)
					continue;
				$row        = & $this->elements[$nrow];
				$row_string = '';
				foreach (array_keys($row) as $ncol)
					$row_string .= $row[$ncol]->toString();
				$part_string .= $this->wrap($row_string, 'tr') . "\n";
			}
			$string .= $this->wrap($part_string, $part);
		}
		$string = $this->wrap($string, 'table', ' class="style_table" cellspacing="1" border="0"');

		return $this->wrap($string, 'div', ' class="ie5"');
	}
}

// , cell1  , cell2  ,  cell3 
// , cell4  , cell5  ,  cell6 
// , cell7  ,        right,==
// ,left          ,==,  cell8
class YTable extends Element
{
	var $col;	// Number of columns

	function YTable($row = array('cell1 ', ' cell2 ', ' cell3'))
	{
		$this->__construct($row);
	}
	// TODO: Seems unable to show literal '==' without tricks.
	//       But it will be imcompatible.
	// TODO: Why toString() or toXHTML() here
	function __construct($row = array('cell1 ', ' cell2 ', ' cell3'))
	{
		parent::__construct();

		$str = array();
		$col = count($row);

		$matches = $_value = $_align = array();
		foreach($row as $cell) {
			if (preg_match('/^(\s+)?(.+?)(\s+)?$/', $cell, $matches)) {
				if ($matches[2] == '==') {
					// Colspan
					$_value[] = FALSE;
					$_align[] = FALSE;
				} else {
					$_value[] = $matches[2];
					if ($matches[1] == '') {
						$_align[] = '';	// left
					} else if (isset($matches[3])) {
						$_align[] = 'center';
					} else {
						$_align[] = 'right';
					}
				}
			} else {
				$_value[] = $cell;
				$_align[] = '';
			}
		}

		for ($i = 0; $i < $col; $i++) {
			if ($_value[$i] === FALSE) continue;
			$colspan = 1;
			while (isset($_value[$i + $colspan]) && $_value[$i + $colspan] === FALSE) ++$colspan;
			$colspan = ($colspan > 1) ? ' colspan="' . $colspan . '"' : '';
			$align = $_align[$i] ? ' style="text-align:' . $_align[$i] . '"' : '';
			$str[] = '<td class="style_td"' . $align . $colspan . '>';
			$str[] = make_link($_value[$i]);
			$str[] = '</td>';
			unset($_value[$i], $_align[$i]);
		}

		$this->col        = $col;
		$this->elements[] = implode('', $str);
	}

	function canContain(& $obj)
	{
		return is_a($obj, 'YTable') && ($obj->col == $this->col);
	}

	function & insert(& $obj)
	{
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	function toString()
	{
		$rows = '';
		foreach ($this->elements as $str) {
			$rows .= "\n" . '<tr class="style_tr">' . $str . '</tr>' . "\n";
		}
		$rows = $this->wrap($rows, 'table', ' class="style_table" cellspacing="1" border="0"');
		return $this->wrap($rows, 'div', ' class="ie5"');
	}
}

// ' 'Space-beginning sentence
// ' 'Space-beginning sentence
// ' 'Space-beginning sentence
class Pre extends Element
{
	function Pre(& $root, $text)
	{
		$this->__construct($root, $text);
	}
	function __construct(& $root, $text)
	{
		global $preformat_ltrim;
		parent::__construct();
		$this->elements[] = htmlsc(
			(! $preformat_ltrim || $text == '' || $text[0] != ' ') ? $text : substr($text, 1));
	}

	function canContain(& $obj)
	{
		return is_a($obj, 'Pre');
	}

	function & insert(& $obj)
	{
		$this->elements[] = $obj->elements[0];
		return $this;
	}

	function toString()
	{
		return $this->wrap(join("\n", $this->elements), 'pre');
	}
}

// Block plugin: #something (started with '#')
class Div extends Element
{
	var $name;
	var $param;

	function Div($out)
	{
		$this->__construct($out);
	}
	function __construct($out)
	{
		parent::__construct();
		list(, $this->name, $this->param) = array_pad($out, 3, '');
	}

	function canContain(& $obj)
	{
		return FALSE;
	}

	function toString()
	{
		// Call #plugin
		return do_plugin_convert($this->name, $this->param);
	}
}

// LEFT:/CENTER:/RIGHT:
class Align extends Element
{
	var $align;

	function Align($align)
	{
		$this->__construct($align);
	}
	function __construct($align)
	{
		parent::__construct();
		$this->align = $align;
	}

	function canContain(& $obj)
	{
		return is_a($obj, 'Inline');
	}

	function toString()
	{
		return $this->wrap(parent::toString(), 'div', ' style="text-align:' . $this->align . '"');
	}
}

// Body
class Body extends Element
{
	var $id;
	var $count = 0;
	var $contents;
	var $contents_last;
	var $classes = array(
		'-' => 'UList',
		'+' => 'OList',
		'>' => 'BQuote',
		'<' => 'BQuote');
	var $factories = array(
		':' => 'DList',
		'|' => 'Table',
		',' => 'YTable',
		'#' => 'Div');

	function Body($id)
	{
		$this->__construct($id);
	}
	function __construct($id)
	{
		$this->id            = $id;
		$this->contents      = new Element();
		$this->contents_last = & $this->contents;
		parent::__construct();
	}

	function parse(& $lines)
	{
		$this->last = & $this;
		$matches = array();

		while (! empty($lines)) {
			$line = array_shift($lines);

			// Escape comments
			if (substr($line, 0, 2) == '//') continue;

			if (preg_match('/^(LEFT|CENTER|RIGHT):(.*)$/', $line, $matches)) {
				// <div style="text-align:...">
				$this->last = & $this->last->add(new Align(strtolower($matches[1])));
				if ($matches[2] == '') continue;
				$line = $matches[2];
			}

			$line = rtrim($line, "\r\n");

			// Empty
			if ($line == '') {
				$this->last = & $this;
				continue;
			}

			// Horizontal Rule
			if (substr($line, 0, 4) == '----') {
				$this->insert(new HRule($this, $line));
				continue;
			}

			// Multiline-enabled block plugin
			if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
			    preg_match('/^#[^{]+(\{\{+)\s*$/', $line, $matches)) {
				$len = strlen($matches[1]);
				$line .= "\r"; // Delimiter
				while (! empty($lines)) {
					$next_line = preg_replace("/[\r\n]*$/", '', array_shift($lines));
					if (preg_match('/\}{' . $len . '}/', $next_line)) {
						$line .= $next_line;
						break;
					} else {
						$line .= $next_line .= "\r"; // Delimiter
					}
				}
			}

			// The first character
			$head = $line[0];

			// Heading
			if ($head == '*') {
				$this->insert(new Heading($this, $line));
				continue;
			}

			// Pre
			if ($head == ' ' || $head == "\t") {
				$this->last = & $this->last->add(new Pre($this, $line));
				continue;
			}

			// Line Break
			if (substr($line, -1) == '~')
				$line = substr($line, 0, -1) . "\r";
			
			// Other Character
			if (isset($this->classes[$head])) {
				$classname  = $this->classes[$head];
				$this->last = & $this->last->add(new $classname($this, $line));
				continue;
			}

			// Other Character
			if (isset($this->factories[$head])) {
				$factoryname = 'Factory_' . $this->factories[$head];
				$this->last  = & $this->last->add($factoryname($this, $line));
				continue;
			}

			// Default
			$this->last = & $this->last->add(Factory_Inline($line));
		}
	}

	function getAnchor($text, $level)
	{
		global $top, $_symbol_anchor;

		// Heading id (auto-generated)
		$autoid = 'content_' . $this->id . '_' . $this->count;
		$this->count++;

		// Heading id (specified by users)
		$id = make_heading($text, FALSE); // Cut fixed-anchor from $text
		if ($id == '') {
			// Not specified
			$id     = & $autoid;
			$anchor = '';
		} else {
			$anchor = '&aname(' . $id . ',super,full,nouserselect){' . $_symbol_anchor . '};';
		}
		$text = trim($text);
		// Add 'page contents' link to its heading
		$this->contents_last = & $this->contents_last->add(new Contents_UList($text, $level, $id));
		// Add heding
		return array($text . $anchor, $this->count > 1 ? "\n" . $top : '', $autoid);
	}

	function & insert(& $obj)
	{
		if (is_a($obj, 'Inline')) $obj = & $obj->toPara();
		return parent::insert($obj);
	}

	function toString()
	{
		global $vars;

		$text = parent::toString();

		// #contents
		$text = preg_replace_callback('/<#_contents_>/',
			array(& $this, 'replace_contents'), $text);

		return $text . "\n";
	}

	function replace_contents($arr)
	{
		$contents  = '<div class="contents">' . "\n" .
				'<a id="contents_' . $this->id . '"></a>' . "\n" .
				$this->contents->toString() . "\n" .
				'</div>' . "\n";
		return $contents;
	}
}

class Contents_UList extends ListContainer
{
	function Contents_UList($text, $level, $id)
	{
		$this->__construct($text, $level, $id);
	}
	function __construct($text, $level, $id)
	{
		// Reformatting $text
		// A line started with "\n" means "preformatted" ... X(
		make_heading($text);
		$text = "\n" . '<a href="#' . $id . '">' . $text . '</a>' . "\n";
		parent::__construct('ul', 'li', '-', str_repeat('-', $level));
		$this->insert(Factory_Inline($text));
	}

	function setParent(& $parent)
	{
		parent::setParent($parent);
		$step   = $this->level;
		if (isset($parent->parent) && is_a($parent->parent, 'ListContainer')) {
			$step  -= $parent->parent->level;
		}
		$indent_level = ($step == $this->level ? 1 : $step);
		$this->style = sprintf(pkwk_list_attrs_template(), $this->level, $indent_level);
	}
}
