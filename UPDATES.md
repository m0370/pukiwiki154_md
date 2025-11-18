# pukiwiki154_md アップデート履歴

## 2025-11-18: EasyMDE v2.20.0への移行（SimpleMDEからの完全移行）

### 概要
SimpleMDE v1.11.2（2017年開発停止）から、アクティブにメンテナンスされているEasyMDE v2.20.0に完全移行しました。

### 移行の経緯
1. **CDN配置** (Phase 4): SimpleMDEをCDN経由で利用
2. **ローカル配置** (2025-11-18 午前): SimpleMDEをローカルファイルに変更してオフライン対応
3. **EasyMDE移行** (2025-11-18 午後): SimpleMDEをEasyMDEに完全置き換え

### 変更内容
- **エディタライブラリ**: SimpleMDE v1.11.2 → EasyMDE v2.20.0
- **ファイル配置**:
  - `skin/js/easymde.min.css` (13KB) - ローカル配置
  - `skin/js/easymde.min.js` (320KB) - ローカル配置
  - SimpleMDEファイル削除
- **コード更新**:
  - `lib/html.php`: エディタ初期化コードをEasyMDEに変更
  - `pukiwiki.ini.php`: コメント更新
  - ドキュメント全体をEasyMDE基準に更新

### 移行の理由
- **メンテナンス**: SimpleMDEは2017年から開発停止、EasyMDEはアクティブ
- **セキュリティ**: 継続的な脆弱性対応とアップデート
- **互換性**: SimpleMDEとAPI互換性を維持（移行が容易）
- **パフォーマンス**: 最新のCodeMirror技術による改善

### メリット
- ✅ オフライン動作（ローカルファイル配置）
- ✅ CDN依存なし
- ✅ 継続的なセキュリティアップデート
- ✅ コミュニティによる活発な開発

### コミット
- `7c5969c` - 改善: SimpleMDEからEasyMDE v2.20.0への移行
- `afc7f16` - 改善: SimpleMDEをCDNからローカルファイル配置に変更（移行前の準備）
- `e77761c` - 追加: SimpleMDEのローカルファイル配置（移行前の準備）

---

## 過去のアップデート履歴

### Phase 5: UI/UX改善（2025-11-17, ed48be4, d6370d1）
- Markdownエディタ表示問題の解決
- 新規ページの初期テキスト問題修正
- `$default_notemd`設定の追加（新規ページのデフォルトモード制御）
- ※当時はSimpleMDEを使用（後にEasyMDEへ移行）

### Phase 4: 改良とリファクタリング（2025-11-16, ed752c1 〜 4027c63）
- Markdownエディタ改善（CDN読み込み、SRI対応、リアルタイム切替）
- プラグイン互換性強化（マルチライン引数サポート）
- エラーハンドリング統一（`format_markdown_error()`関数の導入）
- convert_html()リファクタリング
- ※当時はSimpleMDEを使用（後にEasyMDEへ移行）

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
**EasyMDEバージョン**: 2.20.0
