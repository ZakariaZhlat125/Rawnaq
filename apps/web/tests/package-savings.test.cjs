const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs'), vm = require('node:vm')

test('catalog and gift selection retain API savings when language changes', async () => {
  const language = { lang: 'ar' }
  const c = vm.createContext({
    ref: value => ({ value }), reactive: value => value,
    computed: fn => ({ get value() { return fn() } }),
    useLanguage: () => ({ state: language }),
    localizeField: (value, lang) => typeof value === 'object' ? value?.[lang] : value,
    translationSource: record => record.text_translations || record.text,
    BRANCHES: [], AR_DAYS: [], AR_MONTHS: [], paymentPolicy: {},
    localStorage: { getItem: () => null },
    fetchPackages: async () => [{
      id: 7, name: { ar: 'باقة', en: 'Package' }, services: [],
      package_price: 100, services_total_price: 145.50, savings_amount: 45.50,
    }],
  })
  const source = fs.readFileSync(__dirname + '/../src/composables/usePackages.js', 'utf8')
    .replace(/^import .*\r?\n/gm, '').replace(/^export /gm, '')
  vm.runInContext(source, c)
  await new Promise(resolve => setImmediate(resolve))
  const catalog = c.usePackages()
  assert.equal(catalog.packages.value[0].savings, 45.50)
  assert.equal(catalog.filteredPkgs.value[0].originalPrice, 145.50)
  language.lang = 'en'
  assert.equal(catalog.packages.value[0].name, 'Package')
  assert.equal(catalog.packages.value[0].savings, 45.50)
})
