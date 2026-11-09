/*
  بيانات صفحة الحجز — مستخرجة حرفيًا من src/legacy/booking.html
  عند ربط الباك اند تُستبدل هذه المصفوفات بنداءات API.
  راجع docs/API-INTEGRATION.md
*/

export const BRANCHES=[
 {id:'qr',name:'فرع قريش',addr:'جدة، البوادي — شارع قريش',img:'https://sami-care.sa//storage/uploads/389/4fKSZ0AIoLjJT4JuFKO81qWrxGQ6ymQ6zLbi1L3b.jpg',rate:4.7,visitors:'+980 زائر شهريًا',hours:'12:00 ص – 10:00 م',home:false},
 {id:'bg',name:'فرع البغدادية',addr:'جدة، البغدادية الغربية — شارع حائل',img:'https://sami-care.sa//storage/uploads/391/sSX2TVdlk84EEewXCDhT0w24X5WAJnPOA0GE6VHD.jpg',rate:4.8,visitors:'+1.2K زائر شهريًا',hours:'12:00 ص – 10:00 م',home:false},
 {id:'hm',name:'خدمة منزلية',addr:'حلاقة وعناية ومساجات طبيعية — في منزلك',img:'https://sami-care.sa//storage/uploads/407/7gF352Ispha9FbN9jPxmjnpOQDg5vFY41n5ZTdqK.png',rate:4.9,visitors:'+2K عميل سعيد',hours:'خدمة VIP',home:true},
]

export const CATS=[
 {id:'hair',name:'الحلاقة',color:'var(--c-hair)',hex:'#C6963F',emoji:'✂️',img:'/images/services/haircut/hero.jpg'},
 {id:'skin',name:'تنظيف البشرة',color:'var(--c-skin)',hex:'#D96A8B',emoji:'💧',img:'/images/generated/services/facial-hq.png'},
 {id:'pedi',name:'البديكير',color:'var(--c-pedi)',hex:'#B9743B',emoji:'🦶',img:'/images/services/pedicure/hero.jpg'},
 {id:'mass',name:'المساج',color:'var(--c-mass)',hex:'#8B6FC0',emoji:'🌿',img:'/images/services/massage/hero.jpg'},
 {id:'bath',name:'الحمام المغربي',color:'var(--c-bath)',hex:'#4E9E6F',emoji:'🫧',img:'/images/services/bath/hero.jpg'},
]

export const SERVICES=[
 {id:'s1',cat:'hair',name:'حلاقة عادية',desc:'حلاقة شعر عادية بقصات عصرية',dur:20,price:20},
 {id:'s2',cat:'hair',name:'حلاقة شعر + تحديد لحية',desc:'حلاقة شعر مع تحديد اللحية',dur:30,price:35},
 {id:'s3',cat:'hair',name:'حلاقة مع غسيل',desc:'حلاقة مع غسيل وتنظيف الشعر',dur:30,price:30},
 {id:'s4',cat:'hair',name:'حلاقة VIP',desc:'حلاقة فاخرة مع خدمة VIP',dur:50,price:80},
 {id:'s5',cat:'hair',name:'تنسيق اللحية',desc:'تنسيق وتجميل اللحية',dur:20,price:15},
 {id:'s6',cat:'hair',name:'تصفيف الشعر',desc:'تصفيف الشعر بالمنتجات المناسبة',dur:15,price:15},
 {id:'s7',cat:'skin',name:'تنظيف بشرة عميق',desc:'تنظيف عميق ينظف وينعش البشرة',dur:50,price:150},
 {id:'s8',cat:'skin',name:'تنظيف بشرة سريع',desc:'جلسة سريعة لإشراقة فورية',dur:30,price:80},
 {id:'s9',cat:'skin',name:'ماسك الذهب',desc:'ماسك فاخر لنضارة استثنائية',dur:25,price:90},
 {id:'s10',cat:'pedi',name:'بديكير عادي',desc:'عناية أساسية للأقدام والأظافر',dur:45,price:60},
 {id:'s11',cat:'pedi',name:'بديكير فاخر',desc:'عناية متكاملة مع تقشير وترطيب',dur:60,price:100},
 {id:'s12',cat:'pedi',name:'بديكير طبي',desc:'علاج تشققات القدم والمسامير',dur:60,price:120},
 {id:'s13',cat:'pedi',name:'إزالة جلد ميت',desc:'إزالة الجلد الميت من القدمين',dur:30,price:40},
 {id:'s14',cat:'mass',name:'مساج استرخاء للجسم',desc:'استرخاء عميق يجدد طاقتك',dur:60,price:180},
 {id:'s15',cat:'mass',name:'مساج رأس',desc:'جلسة قصيرة لتخفيف التوتر',dur:15,price:25},
 {id:'s16',cat:'mass',name:'مساج علاجي',desc:'مساج متخصص للعضلات المجهدة',dur:60,price:220},
 {id:'s17',cat:'bath',name:'حمام مغربي كلاسيكي',desc:'تقشير وتنقية بطقوس أصيلة',dur:60,price:200},
 {id:'s18',cat:'bath',name:'حمام مغربي ملكي',desc:'التجربة الكاملة مع ماسك وتدليك',dur:90,price:300},
]


/*
  أنواع الخدمات المعروضة في صفحات التفاصيل تُسجَّل هنا كخدمات حجز عادية،
  فيشتغل معها نظام الحجز كله (الأسعار، الموظفون، بناء الجلسة) بدون أي استثناء.
  hidden:true يمنع ظهورها مكرّرة داخل قائمة خطوة الخدمات في صفحة الحجز.
*/
import { serviceDetails as _details } from './serviceDetails'
for (const d of Object.values(_details)) {
  for (const v of d.variants || []) {
    if (v.svcId && !SERVICES.some(s => s.id === v.svcId)) {
      SERVICES.push({ id: v.svcId, cat: v.cat, name: v.name, desc: v.desc || '', dur: v.dur, price: v.price, hidden: true })
    }
  }
}

export const UPSELL=[
 {id:'u1',name:'كريم ترطيب متوازن',desc:'ترطيب وتغذية يدوم طويلًا',price:110,img:'https://sami-care.sa//storage/uploads/403/HyxbPuOgHBgbg0pgpMZr9bcFv2OryQtxjRMLnhxb.jpg'},
 {id:'u2',name:'غسول تنظيف البشرة',desc:'ينظف بعمق وينعش البشرة',price:95,img:'https://sami-care.sa//storage/uploads/405/L2sxjQjZgTztP0swgRTlXV0jVjCLuS5GJyypCJmT.jpg'},
 {id:'u3',name:'زيت اللحية الذهبي',desc:'ترطيب وتغذية عميقة',price:120,img:'https://sami-care.sa//storage/uploads/406/uGGoLhrbcMofuyS2kedJ44ejvBqYnz1M0mA0zTMP.jpg'},
]

export const STEPS=[
 {t:'اختيار الخدمات',ic:'<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4L8.12 15.88M14.47 14.48L20 20M8.12 8.12L12 12"/>'},
 {t:'اختيار الموظف',ic:'<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>'},
 {t:'اختيار الوقت',ic:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>'},
 {t:'تأكيد الحجز',ic:'<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>'},
 {t:'الدفع',ic:'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>'},
]

export const AR_DAYS=['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت']

export const AR_MONTHS=['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']

export const PAY_METHODS=[
 {id:'mada',n:'مدى',d:'ادفع باستخدام بطاقة مدى',logo:'مدى'},
 {id:'apple',n:'Apple Pay',d:'ادفع باستخدام Apple Pay',logo:' Pay'},
 {id:'card',n:'بطاقات الائتمان والخصم',d:'Visa / Mastercard',logo:'VISA'},
 {id:'tabby',n:'تابي',d:'ادفع الآن أو قسّمها مع تابي',logo:'tabby'},
 {id:'wallet',n:'المحفظة',d:'الرصيد الحالي 320 ر.س',logo:'👛'},
 {id:'cash',n:'الدفع عند الوصول',d:'ادفع عند وصولك للفرع',logo:'💵'},
]

export const EMPLOYEES={
 hair:[{n:'موسى تموسي',r:4.9},{n:'أحمد الحربي',r:4.9},{n:'محمد العتيبي',r:4.9},{n:'سعود القرني',r:4.8},{n:'فيصل الشهري',r:4.7},{n:'ماجد الغامدي',r:4.8},{n:'وليد الزهراني',r:4.7},{n:'حسن العمري',r:4.8}],
 skin:[{n:'جهاد سيف محمد',r:4.9},{n:'مها المطيري',r:4.7},{n:'تركي السبيعي',r:4.9},{n:'بدر الدوسري',r:4.6},{n:'خالد الشريف',r:4.8},{n:'عمر الجهني',r:4.7},{n:'ناصر الحربي',r:4.8},{n:'سلمان الثقفي',r:4.6}],
 pedi:[{n:'إبراهيم العنزي',r:4.8},{n:'نواف الحارثي',r:4.6},{n:'مروان فقيهي',r:4.7},{n:'زياد المطيري',r:4.8},{n:'رامي السلمي',r:4.7},{n:'فهد القحطاني',r:4.6},{n:'أنس الغامدي',r:4.8},{n:'بسام الشهري',r:4.7}],
 mass:[{n:'إبراهيم علي',r:4.9},{n:'علي القحطاني',r:4.9},{n:'عبدالله المالكي',r:4.8},{n:'ياسر العتيبي',r:4.8},{n:'صالح الزهراني',r:4.7},{n:'مهند الحربي',r:4.8},{n:'طارق العمري',r:4.6},{n:'أيمن الغامدي',r:4.7}],
 bath:[{n:'يوسف بن موسي',r:4.8},{n:'خالد الشمري',r:4.7},{n:'عبدالعزيز السلمي',r:4.9},{n:'حمد الدوسري',r:4.8},{n:'منصور العنزي',r:4.7},{n:'راشد المطيري',r:4.8},{n:'نايف الحربي',r:4.6},{n:'سعد القحطاني',r:4.7}],
}
