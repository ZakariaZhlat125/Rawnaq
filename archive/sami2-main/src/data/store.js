/*
  بيانات المتجر — مستخرجة حرفيًا من src/legacy/store.html
  عند ربط الباك اند تُستبدل هذه المصفوفات بنداءات API.
  راجع docs/API-INTEGRATION.md
*/

export const CATS = [
 {id:'all',n:'كل المنتجات',shape:'kit'},
 {id:'hair',n:'العناية بالشعر',shape:'pump'},
 {id:'body',n:'العناية بالجسم',shape:'spray'},
 {id:'beard',n:'العناية باللحية',shape:'dropper'},
 {id:'skin',n:'العناية بالبشرة',shape:'jar'},
 {id:'perf',n:'العطور',shape:'perfume'},
 {id:'sets',n:'باقات ومجموعات',shape:'kit'},
]

export const PRODUCTS = [
 {id:'p1',n:'كريم الترطيب اليومي',d:'ترطيب عميق وحماية تدوم طوال اليوم',pr:99,cat:'skin',shape:'jar',badge:'الأكثر مبيعًا',best:1,ts:16},
 {id:'p2',n:'زيت اللحية الفاخر',d:'تركيبة مغذية تمنح اللحية نعومة ولمعان',pr:89,cat:'beard',shape:'dropper',badge:'جديد',new:1,best:1,ts:15},
 {id:'p3',n:'غسول الوجه اليومي',d:'ينظف بعمق وينعش بشرتك',pr:79,cat:'skin',shape:'tube',best:1,ts:14},
 {id:'p4',n:'عطر سامي',d:'رائحة فاخرة تدوم طويلًا',pr:249,cat:'perf',shape:'perfume',badge:'مفضل العملاء',best:1,ts:13},
 {id:'p5',n:'باقة العناية المتكاملة',d:'مجموعة متكاملة للعناية اليومية',pr:299,cat:'sets',shape:'kit',ts:12},
 {id:'p6',n:'شامبو الشعر',d:'ينظف ويقوي الشعر من الجذور',pr:69,cat:'hair',shape:'pump',ts:11},
 {id:'p7',n:'مجموعة العناية باللحية',d:'فرشاة + زيت + بلسم فاخر',pr:149,cat:'beard',shape:'kit',ts:10},
 {id:'p8',n:'شمعة عطرية',d:'رائحة هادئة تضفي لمسة استرخاء',pr:89,cat:'perf',shape:'candle',ts:9},
 {id:'p9',n:'واقي الشمس للوجه',d:'حماية عالية من UVA وUVB',pr:79,cat:'skin',shape:'tube',ts:8},
 {id:'p10',n:'سيروم الوجه',d:'يمنح البشرة إشراقة ونضارة',pr:129,cat:'skin',shape:'dropper',ts:7},
 {id:'p11',n:'لوشن الجسم',d:'ترطيب عميق وامتصاص سريع',pr:89,cat:'body',shape:'pump',ts:6},
 {id:'p12',n:'زيت الشعر',d:'لمعان وكثافة وحماية من التساقط',pr:99,cat:'hair',shape:'dropper',ts:5},
 {id:'p13',n:'بلسم الشعر المغذي',d:'نعومة فائقة وترطيب متوازن',pr:59,cat:'hair',shape:'pump',ts:4},
 {id:'p14',n:'فرشاة الحلاقة الفاخرة',d:'شعيرات ناعمة ومقبض كلاسيكي',pr:119,cat:'sets',shape:'brush',ts:3},
 {id:'p15',n:'معطر الجسم',d:'انتعاش يدوم على مدار اليوم',pr:139,cat:'body',shape:'spray',ts:2},
 {id:'p16',n:'سكراب الجسم',d:'تقشير لطيف وتجديد للبشرة',pr:109,cat:'body',shape:'jar',ts:1},
]

export const STORE_PROMOS = [
 {id:'sp1',badge:'عرض محدود',title:'اشترِ 2 واحصل على الثالث مجانًا',text:'على مجموعة مختارة من منتجات العناية بالشعر واللحية',cta:'تسوق العرض'},
]

export const CK_PAYS = [
 {id:'mada',n:'مدى',d:'ادفع باستخدام بطاقات مدى',logo:'مدى mada',form:true},
 {id:'apple',n:'Apple Pay',d:'دفع سريع وآمن عبر أجهزتك',logo:' Pay',form:false},
 {id:'card',n:'فيزا / ماستركارد',d:'بطاقات ائتمانية أو مسبقة الدفع',logo:'VISA ●●',form:true},
 {id:'cod',n:'الدفع عند الاستلام',d:'ادفع عند استلام طلبك',logo:'💵',form:false},
]

export const BRANCHES = [{id:'qr',n:'فرع قريش',a:'جدة، البوادي — شارع قريش'},{id:'bg',n:'فرع البغدادية',a:'جدة، البغدادية الغربية — شارع حائل'}];
