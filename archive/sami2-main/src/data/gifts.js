/*
  بيانات صفحة الإهداء — مستخرجة حرفيًا من src/legacy/gifts.html
  عند ربط الباك اند تُستبدل هذه المصفوفات بنداءات API.
  راجع docs/API-INTEGRATION.md
*/

export const BRANCHES=[
 {id:'qr',name:'فرع قريش',addr:'جدة، قريش — شارع قريش',rate:4.7,vis:'+980 زائر شهريًا',hours:'12:00 ص – 10:00 م',home:false,fee:0,
  img:'https://sami-care.sa//storage/uploads/389/4fKSZ0AIoLjJT4JuFKO81qWrxGQ6ymQ6zLbi1L3b.jpg'},
 {id:'bg',name:'فرع البغدادية',addr:'جدة، البغدادية الغربية — شارع حائل',rate:4.8,vis:'+1.2K زائر شهريًا',hours:'12:00 ص – 10:00 م',home:false,fee:0,
  img:'https://sami-care.sa//storage/uploads/391/sSX2TVdlk84EEewXCDhT0w24X5WAJnPOA0GE6VHD.jpg'},
 {id:'hm',name:'خدمة منزلية',addr:'الهدية تُنفَّذ في منزل المستلم — جدة',rate:4.9,vis:'+2K عميل سعيد',hours:'خدمة VIP',home:true,fee:100,
  img:'https://sami-care.sa//storage/uploads/407/7gF352Ispha9FbN9jPxmjnpOQDg5vFY41n5ZTdqK.png'},
]

export const PKGS=[
 {id:'fast',name:'باقة سريعة',dur:60,price:550,hex:'#D98A3B',hot:true,
  desc:'جلسة فعّالة وسريعة لتجديد نشاطك',
  inc:['تنظيف بشرة سريعة','مساج للظهر والرقبة','ترطيب الوجه'],
  img:'/images/generated/packages/hair-care-hq.png'},
 {id:'full',name:'باقة العناية الكاملة',dur:120,price:1150,hex:'#B8912F',
  desc:'عناية متكاملة من الرأس إلى القدم',
  inc:['مساج استرخائي للجسم','تنظيف بشرة عميق','ترطيب الوجه','جلسة بخار'],
  img:'/images/generated/packages/complete-care-hq.png'},
 {id:'groom',name:'باقة العريس',dur:150,price:950,hex:'#8B6FC0',
  desc:'تجهيز متكامل لأجمل إطلالة في يومك',
  inc:['عناية بشرة متقدمة','مساج استرخائي للجسم','تنظيف بشرة عميق','مشروب ترحيبي'],
  img:'/images/gifts/package.jpg'},
 {id:'home',name:'باقة منزلية',dur:90,price:450,hex:'#3E8E9E',
  desc:'تجربة متكاملة في منزلك بأعلى معايير العناية',
  inc:['منتجات عناية مختارة','زيوت طبيعية','شموع عطرية','توصيل مجاني'],
  img:'/images/generated/branches/home-service-hq.png'},
 {id:'relax',name:'باقة الاسترخاء',dur:90,price:650,hex:'#4E9E6F',
  desc:'استرخاء عميق وتجديد للطاقة للجسم والعقل',
  inc:['مساج استرخائي للجسم','تنظيف بشرة عميق','جلسة بخار','مشروب صحي'],
  img:'/images/generated/services/massage-hq.png'},
 {id:'royal',name:'باقة العناية الملكية',dur:180,price:1150,hex:'#B0642A',
  desc:'التجربة الأفخم — غرفة VIP وضيافة ملكية',
  inc:['غرفة VIP خاصة','حمام مغربي ملكي','مساج كامل','ضيافة فاخرة'],
  img:'/images/services/bath/royal.jpg'},
]

export const CATS=[
 {id:'hair',name:'الحلاقة',hex:'#C6963F',emoji:'✂️',img:'/images/services/haircut/hero.jpg'},
 {id:'skin',name:'تنظيف البشرة',hex:'#D96A8B',emoji:'💧',img:'/images/generated/services/facial-hq.png'},
 {id:'pedi',name:'البديكير',hex:'#B9743B',emoji:'🦶',img:'/images/services/pedicure/hero.jpg'},
 {id:'mass',name:'المساج',hex:'#8B6FC0',emoji:'🌿',img:'/images/services/massage/hero.jpg'},
 {id:'bath',name:'الحمام المغربي',hex:'#4E9E6F',emoji:'🫧',img:'/images/services/bath/hero.jpg'},
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

export const DESIGNS=[
 {id:'lux-dark',n:'ملكي أسود مذهب',bg:'radial-gradient(circle at 20% 12%,rgba(232,190,108,.24),transparent 30%),linear-gradient(150deg,#1D1810,#070604 72%)',fg:'#E8BE6C',icon:'◆'},
 {id:'gold-leaf',n:'ختم الذهب الفاخر',bg:'linear-gradient(150deg,#FFF4D8,#D4AF5E 54%,#7C5519)',fg:'#1B140D',icon:'✦'},
 {id:'emerald',n:'زمرد النخبة',bg:'radial-gradient(circle at 80% 8%,rgba(255,255,255,.18),transparent 28%),linear-gradient(150deg,#082118,#176A50 58%,#05110D)',fg:'#DDF7E8',icon:'◇'},
 {id:'birthday',n:'هدية عيد ميلاد',bg:'radial-gradient(circle at 20% 18%,rgba(255,255,255,.28),transparent 30%),linear-gradient(150deg,#4B102E,#B94069 56%,#F2B8CF)',fg:'#FFFFFF',icon:'★'},
 {id:'eid-adha',n:'هدية عيد الأضحى',bg:'radial-gradient(circle at 85% 12%,rgba(255,255,255,.22),transparent 28%),linear-gradient(150deg,#092B25,#0F6B58 52%,#D8B45B)',fg:'#FFF7DA',icon:'☾'},
 {id:'christmas',n:'هدية الكريسماس',bg:'linear-gradient(150deg,#0D2B1F,#146B3A 52%,#B41F2A)',fg:'#FFFFFF',icon:'✶'},
 {id:'ramadan',n:'هدية رمضان والعيد',bg:'radial-gradient(circle at 20% 10%,rgba(232,190,108,.26),transparent 32%),linear-gradient(150deg,#11132C,#26316B 55%,#C99B3B)',fg:'#F8E7B0',icon:'☾'},
]

export const SENDS=[
 {id:'wa',n:'واتساب',ic:'<path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2z"/><path d="M8.5 9.5c.5 2.5 3 5 5.5 5.5l1.5-1.5 2 1c-.5 1.5-2 2-3.5 1.5-3-.9-5.6-3.5-6.5-6.5-.5-1.5 0-3 1.5-3.5l1 2z"/>'},
 {id:'sms',n:'رسالة نصية',ic:'<path d="M21 11.5a8.4 8.4 0 01-9 8.4 8.5 8.5 0 01-3.8-.9L3 21l2-5.2a8.4 8.4 0 011.5-9.8 8.5 8.5 0 0114.5 5.5z"/>'},
 {id:'link',n:'رابط إلكتروني',ic:'<path d="M10 13a5 5 0 007.5.5l3-3a5 5 0 00-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 00-7.5-.5l-3 3a5 5 0 007 7l1.7-1.7"/>'},
 {id:'print',n:'طباعة البطاقة',ic:'<path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path d="M6 14h12v8H6z"/>'},
]

export const GSTEPS=[
 {t:'نوع الهدية',s:'إهداء خدمة / باقة'},
 {t:'اختيار الباقة أو الخدمة',s:'اختر ما يناسبه'},
 {t:'تخصيص الهدية',s:'خصص رسالتك'},
 {t:'الدفع',s:'إتمام عملية الدفع'},
 {t:'نجاح الإهداء',s:'تم إرسال الهدية بنجاح'},
]

export const PAY_MAIN=[
 {id:'apple',n:'Apple Pay',d:'ادفع بسهولة وأمان عبر أجهزتك',logo:' Pay',form:false},
 {id:'mada',n:'مدى',d:'ادفع باستخدام بطاقات مدى',logo:'مدى mada',form:true},
 {id:'card',n:'فيزا / ماستركارد',d:'بطاقات ائتمانية أو مدى مسبقة الدفع',logo:'VISA ●●',form:true},
 {id:'cash',n:'الدفع عند الوصول',d:'متاح عند توفر خدمة الدفع عند الاستلام',logo:'💵',form:false},
]

export const PAY_FLEX=[
 {id:'tabby',n:'تابي',d:'ادفع الآن أو قسّمها مع تابي على 4 دفعات',logo:'tabby'},
 {id:'tamara',n:'تمارا',d:'ادفع الآن أو قسّمها مع تمارا على 4 دفعات',logo:'تمارا'},
]

export const PAY_BAL=[
 {id:'wallet',n:'المحفظة',d:'الرصيد الحالي 1,150 ر.س',logo:'👛'},
 {id:'points',n:'نقاط الولاء',d:'النقاط المتاحة 2,450 نقطة',logo:'✨'},
]
