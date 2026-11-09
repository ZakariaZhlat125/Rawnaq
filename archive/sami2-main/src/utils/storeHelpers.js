/*
  مساعدات المتجر — مستخرجة حرفيًا من src/legacy/store.html

  SHAPES تُنتج رسومات SVG للمنتجات وتُستخدم عبر v-html،
  للحفاظ على تطابق الماركب مع الصفحة الأصلية حرفيًا.
*/

const G1='#E8BE6C',G2='#9C6B1F',BK='#191410',BK2='#0D0B07';

const MARK=(x,y,s)=>`<g transform="translate(${x},${y}) scale(${s})" stroke="${G1}" stroke-width="1.1" fill="none"><path d="M6 1l5 3v6l-5 3-5-3V4z"/><path d="M6 1v12M1 4l5 3 5-3"/></g>`;

const SHAPES={
 jar:w=>`<svg class="prod" width="${w}" height="${w}" viewBox="0 0 90 90"><rect x="22" y="30" width="46" height="42" rx="9" fill="${BK}" stroke="${G2}" stroke-width="1"/><rect x="20" y="20" width="50" height="12" rx="5" fill="${BK2}" stroke="${G2}" stroke-width="1"/><rect x="24" y="23" width="42" height="2" rx="1" fill="${G1}" opacity=".5"/>${MARK(39,42,1.4)}<text x="45" y="66" text-anchor="middle" font-size="4.6" fill="${G1}" letter-spacing="1">SAMI CARE</text></svg>`,
 dropper:w=>`<svg class="prod" width="${w}" height="${w}" viewBox="0 0 90 90"><rect x="30" y="32" width="30" height="42" rx="7" fill="${BK}" stroke="${G2}" stroke-width="1"/><path d="M30 46h30v22a7 7 0 01-7 7H37a7 7 0 01-7-7z" fill="#B8912F" opacity=".5"/><rect x="38" y="16" width="14" height="10" rx="3" fill="${BK2}" stroke="${G2}" stroke-width="1"/><rect x="42" y="26" width="6" height="10" fill="${G2}"/>${MARK(39,44,1.15)}<text x="45" y="68" text-anchor="middle" font-size="4.2" fill="${G1}" letter-spacing=".8">SAMI</text></svg>`,
 pump:w=>`<svg class="prod" width="${w}" height="${w}" viewBox="0 0 90 90"><rect x="30" y="26" width="30" height="50" rx="7" fill="${BK}" stroke="${G2}" stroke-width="1"/><rect x="40" y="14" width="10" height="12" rx="2" fill="${BK2}" stroke="${G2}" stroke-width="1"/><path d="M45 14h12v4h-8" fill="none" stroke="${G2}" stroke-width="2.4"/>${MARK(39,40,1.15)}<text x="45" y="64" text-anchor="middle" font-size="4.2" fill="${G1}" letter-spacing=".8">SAMI</text><rect x="34" y="68" width="22" height="1.6" fill="${G1}" opacity=".4"/></svg>`,
 tube:w=>`<svg class="prod" width="${w}" height="${w}" viewBox="0 0 90 90"><path d="M32 30h26l3 40a5 5 0 01-5 5H34a5 5 0 01-5-5z" fill="${BK}" stroke="${G2}" stroke-width="1"/><rect x="36" y="20" width="18" height="10" rx="2.5" fill="${BK2}" stroke="${G2}" stroke-width="1"/>${MARK(39,42,1.15)}<text x="45" y="66" text-anchor="middle" font-size="4.2" fill="${G1}" letter-spacing=".8">SAMI</text></svg>`,
 perfume:w=>`<svg class="prod" width="${w}" height="${w}" viewBox="0 0 90 90"><rect x="26" y="34" width="38" height="40" rx="8" fill="${BK}" stroke="${G2}" stroke-width="1"/><rect x="26" y="34" width="38" height="40" rx="8" fill="url(#pfg)" opacity=".18"/><defs><linearGradient id="pfg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${G1}"/><stop offset="1" stop-color="transparent"/></linearGradient></defs><rect x="38" y="18" width="14" height="16" rx="3" fill="${BK2}" stroke="${G2}" stroke-width="1"/><rect x="35" y="30" width="20" height="4" rx="2" fill="${G2}"/>${MARK(39,47,1.3)}<text x="45" y="68" text-anchor="middle" font-size="4.2" fill="${G1}" letter-spacing=".8">SAMI</text></svg>`,
 candle:w=>`<svg class="prod" width="${w}" height="${w}" viewBox="0 0 90 90"><rect x="26" y="36" width="38" height="36" rx="7" fill="${BK}" stroke="${G2}" stroke-width="1"/><rect x="30" y="32" width="30" height="5" rx="2" fill="${G2}" opacity=".7"/><path d="M45 20c3 4 2.4 7-.1 9-2.4-2-3-5 .1-9z" fill="${G1}"/><rect x="44" y="28" width="2" height="5" fill="${G2}"/>${MARK(39,48,1.2)}<text x="45" y="66" text-anchor="middle" font-size="4.2" fill="${G1}" letter-spacing=".8">SAMI</text></svg>`,
 kit:w=>`<svg class="prod" width="${w}" height="${w}" viewBox="0 0 110 90"><rect x="12" y="34" width="22" height="40" rx="5" fill="${BK}" stroke="${G2}" stroke-width="1"/><rect x="18" y="26" width="9" height="9" rx="2" fill="${BK2}" stroke="${G2}" stroke-width="1"/><rect x="42" y="22" width="26" height="52" rx="6" fill="${BK}" stroke="${G2}" stroke-width="1"/><rect x="51" y="12" width="8" height="10" rx="2" fill="${BK2}" stroke="${G2}" stroke-width="1"/><rect x="76" y="42" width="24" height="32" rx="9" fill="${BK}" stroke="${G2}" stroke-width="1"/><rect x="74" y="34" width="28" height="9" rx="4" fill="${BK2}" stroke="${G2}" stroke-width="1"/>${MARK(49,38,1.2)}<text x="55" y="64" text-anchor="middle" font-size="4.4" fill="${G1}" letter-spacing=".8">SAMI</text></svg>`,
 brush:w=>`<svg class="prod" width="${w}" height="${w}" viewBox="0 0 90 90"><path d="M34 40c0-11 5-18 11-18s11 7 11 18z" fill="#3A3226" stroke="${G2}" stroke-width="1"/><path d="M36 40h18l-2 8H38z" fill="${G2}"/><path d="M39 48h12l1.6 22a6 6 0 01-6 6h-3.2a6 6 0 01-6-6z" fill="${BK}" stroke="${G2}" stroke-width="1"/>${MARK(39,54,1.05)}</svg>`,
 spray:w=>`<svg class="prod" width="${w}" height="${w}" viewBox="0 0 90 90"><rect x="32" y="30" width="26" height="46" rx="6" fill="${BK}" stroke="${G2}" stroke-width="1"/><rect x="36" y="18" width="12" height="12" rx="2" fill="${BK2}" stroke="${G2}" stroke-width="1"/><path d="M48 21h8M52 17l4 4-4 4" stroke="${G1}" stroke-width="1.4" fill="none" opacity=".8"/>${MARK(38,42,1.1)}<text x="45" y="66" text-anchor="middle" font-size="4.2" fill="${G1}" letter-spacing=".8">SAMI</text></svg>`,
};

const rs=n=>n.toLocaleString('ar-EG-u-nu-latn');

function svg(p,w){return`<svg width="${w||17}" height="${w||17}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">${p}</svg>`}

/*
  يفصل ناتج SHAPES إلى (viewBox + المحتوى الداخلي).

  الغرض: رسم الـ SVG كعنصر Vue حقيقي بدل حقنه داخل غلاف <span>.
  الغلاف كان سيصبح عنصر الشبكة (‎.pimg هي display:grid) بدل الـ SVG نفسه،
  وهو تغيير في بنية DOM نتجنّبه للحفاظ على تطابق التصميم.
*/
export function shapeParts(name, size) {
  const html = SHAPES[name](size)
  const m = html.match(/^<svg[^>]*viewBox="([^"]+)"[^>]*>([\s\S]*)<\/svg>$/)
  if (!m) return { viewBox: '0 0 90 90', inner: '' }
  return { viewBox: m[1], inner: m[2] }
}

export { rs, svg, SHAPES, MARK }
