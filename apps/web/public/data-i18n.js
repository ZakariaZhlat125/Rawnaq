/* API text may be plain text, a translation map, or a JSON-encoded map. */
;(function(root) {
  function field(value, lang, depth = 0) {
    if (depth > 5 || value == null) return '';
    if (typeof value === 'string') {
      const text = value.trim();
      if (text.startsWith('{') && text.endsWith('}')) {
        try { return field(JSON.parse(text), lang, depth + 1); } catch { /* Keep malformed JSON as text. */ }
      }
      return value;
    }
    if (typeof value === 'number') return String(value);
    if (typeof value !== 'object' || Array.isArray(value)) return '';
    for (const key of [...new Set([lang, 'ar', 'en'])]) {
      const text = field(value[key], lang, depth + 1);
      if (text.trim()) return text;
    }
    return '';
  }
  function source(record, key) {
    const translations = record?.[key + '_translations'];
    if (field(translations, 'ar').trim() || field(translations, 'en').trim()) return translations;
    return record?.[key];
  }
  function record(record, key, lang) { return field(source(record, key), lang); }
  root.SamiDataI18n = { field, source, record };
})(globalThis);