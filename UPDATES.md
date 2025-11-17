# pukiwiki154_md アップデート履歴

## 2025-11-18: SimpleMDEローカルファイル配置対応

### コミット
- `afc7f16` - 改善: SimpleMDEをCDNからローカルファイル配置に変更
- `e77761c` - 追加: SimpleMDEのローカルファイル配置

### 変更内容
- SimpleMDEをCDN配置からローカルファイル配置に変更
- `skin/js/simplemde.min.css` (v1.11.2) をローカル配置
- `skin/js/simplemde.min.js` (v1.11.2) をローカル配置

### メリット
- **パフォーマンス向上**: CDN遅延がない
- **オフライン動作**: インターネット接続がなくても利用可能
- **CDN依存削減**: 外部サービスへの依存を減らす
- **セキュリティ**: SRI対応から信頼できるローカルファイル配置へ

### 修正ファイル
- `lib/html.php` (334行目〜): SimpleMDE読み込みパス変更
  - CDNリンク削除
  - ローカルファイルパス（`SKIN_DIR`）への変更

---

## 過去のアップデート履歴

### Phase 5: UI/UX改善（2025-11-17, ed48be4, d6370d1）
- SimpleMDE表示問題の解決
- 新規ページの初期テキスト問題修正
- `$default_notemd`設定の追加（新規ページのデフォルトモード制御）

### Phase 4: 改良とリファクタリング（2025-11-16, ed752c1 〜 4027c63）
- SimpleMDE改善（CDN読み込み、SRI対応、リアルタイム切替）
- プラグイン互換性強化（マルチライン引数サポート）
- エラーハンドリング統一（`format_markdown_error()`関数の導入）
- convert_html()リファクタリング

### Phase 3: セキュリティ強化（2025-11-15, 1d048c7）
- URLスキーム検証機能追加（`is_safe_markdown_url()`）
- SafeModeのデフォルト有効化
- セキュリティ設定のドキュメント化

### Phase 2: 機能拡張（2025-11-14, b524415, d03a1b5）
- ParsedownExtraサポート追加（テーブル、脚注、定義リスト等）
- Parsedown 1.7.4 / ParsedownExtra 0.8.1へ更新
- エラーハンドリング強化

### Phase 1: 基本実装
- PukiWiki 1.5.4のMarkdown記法対応
- `#notemd`によるモード切替機構の実装
- 正規表現とリンク処理の修正

---

**プロジェクト**: PukiWiki 1.5.4 Markdown改造版
**最終更新**: 2025-11-18
**Parsedownバージョン**: 1.7.4 / ParsedownExtra 0.8.1
**SimpleMDEバージョン**: 1.11.2
