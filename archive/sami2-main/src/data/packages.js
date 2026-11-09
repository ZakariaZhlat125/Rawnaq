/*
  بيانات صفحة الباقات — مستخرجة حرفيًا من src/legacy/packages-gifts.html
  عند ربط الباك اند تُستبدل هذه المصفوفات بنداءات API.
  راجع docs/API-INTEGRATION.md
*/

export const PKGS=[
 {id:'relax',name:'باقة الاسترخاء',cat:'relax',dur:90,price:450,color:'var(--p-relax)',hex:'#4E9E6F',
  desc:'استرخاء عميق وتجديد للطاقة للجسم والعقل',
  inc:['مساج استرخائي للجسم','تنظيف بشرة عميق','جلسة بخار','مشروب صحي'],
  ico:'<path d="M12 3c-1.2 3-4 4.5-4 8a4 4 0 008 0c0-3.5-2.8-5-4-8z"/><path d="M5 21h14"/>',
  img:'/images/generated/services/massage-hq.png'},
 {id:'fast',name:'باقة سريعة',cat:'fast',dur:45,price:250,color:'var(--p-fast)',hex:'#D98A3B',
  desc:'عناية سريعة وفعّالة تناسب جدولك المزدحم',
  inc:['حلاقة سريعة','ترتيب لحية','غسيل شعر','تصفيف'],
  ico:'<path d="M13 2L3 14h9l-1 8 10-12h-9z"/>',
  img:'/images/generated/packages/hair-care-hq.png'},
 {id:'full',name:'باقة العناية الكاملة',cat:'full',dur:120,price:650,color:'var(--p-full)',hex:'#B8912F',hot:true,
  desc:'عناية متكاملة من الرأس حتى القدم لإطلالة مثالية',
  inc:['حلاقة كلاسيكية','تنظيف بشرة عميق','حمام مغربي','ترطيب الشعر','مشروب صحي'],
  ico:'<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  img:'/images/generated/packages/complete-care-hq.png'},
 {id:'groom',name:'باقة العريس',cat:'groom',dur:150,price:950,color:'var(--p-groom)',hex:'#8B6FC0',
  desc:'إطلالة استثنائية في يومك المميز — جاهزية تامة من الرأس للقدم',
  inc:['حلاقة فاخرة','تنظيف بشرة عميق','حمام مغربي','تنعيم وترطيب الشعر','عطر فاخر','ضيافة خاصة'],
  ico:'<path d="M6 3h12l4 6-10 12L2 9z"/><path d="M2 9h20M12 3l-4 6 4 12 4-12-4-6z"/>',
  img:'/images/gifts/package.jpg'},
 {id:'home',name:'باقة منزلية',cat:'home',dur:60,price:350,color:'var(--p-home)',hex:'#3E8E9E',
  desc:'راحة تامة في منزلك ونفس جودة العناية',
  inc:['حلاقة في المنزل','تنظيف بشرة','ترتيب لحية','منتجات عناية مجانية'],
  ico:'<path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/><path d="M9 21V12h6v9"/>',
  img:'/images/generated/branches/home-service-hq.png'},
 {id:'vip',name:'باقة VIP',cat:'vip',dur:110,price:850,color:'var(--p-vip)',hex:'#B0642A',
  desc:'غرفة خاصة وضيافة فاخرة وتجربة ملكية متكاملة',
  inc:['غرفة VIP خاصة','حلاقة فاخرة','تنظيف بشرة عميق','مساج رأس','ضيافة فاخرة'],
  ico:'<path d="M2 8l4 4 6-8 6 8 4-4v10a2 2 0 01-2 2H4a2 2 0 01-2-2z"/>',
  img:'/images/services/bath/royal.jpg'},
]

export const FILTERS=[
 {id:'all',n:'كل الباقات',ic:'<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>'},
 {id:'relax',n:'الاسترخاء',ic:'<path d="M12 3c-1.2 3-4 4.5-4 8a4 4 0 008 0c0-3.5-2.8-5-4-8z"/><path d="M5 21h14"/>'},
 {id:'full',n:'العناية الكاملة',ic:'<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>'},
 {id:'groom',n:'العريس',ic:'<path d="M6 3h12l4 6-10 12L2 9z"/>'},
 {id:'vip',n:'VIP',ic:'<path d="M2 8l4 4 6-8 6 8 4-4v10a2 2 0 01-2 2H4a2 2 0 01-2-2z"/>'},
 {id:'fast',n:'سريعة',ic:'<path d="M13 2L3 14h9l-1 8 10-12h-9z"/>'},
 {id:'home',n:'المنزلية',ic:'<path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/>'},
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

export const SEND_METHODS=[
 {id:'wa',n:'واتساب',ic:'<path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2z"/><path d="M8.5 9.5c.5 2.5 3 5 5.5 5.5l1.5-1.5 2 1c-.5 1.5-2 2-3.5 1.5-3-.9-5.6-3.5-6.5-6.5-.5-1.5 0-3 1.5-3.5l1 2z"/>'},
 {id:'sms',n:'رسالة نصية',ic:'<path d="M21 11.5a8.4 8.4 0 01-9 8.4 8.5 8.5 0 01-3.8-.9L3 21l2-5.2a8.4 8.4 0 011.5-9.8 8.5 8.5 0 0114.5 5.5z"/>'},
 {id:'mail',n:'بريد إلكتروني',ic:'<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 6L2 7"/>'},
 {id:'link',n:'رابط مشاركة',ic:'<path d="M10 13a5 5 0 007.5.5l3-3a5 5 0 00-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 00-7.5-.5l-3 3a5 5 0 007 7l1.7-1.7"/>'},
]

export const PAYS=[
 {id:'mada',n:'بطاقة مدى',d:'الدفع ببطاقة مدى',logo:'مدى mada'},
 {id:'apple',n:'Apple Pay',d:'دفع سريع وآمن',logo:' Pay'},
 {id:'card',n:'فيزا / ماستركارد',d:'بطاقات الائتمان',logo:'VISA ●●'},
 {id:'stc',n:'STC Pay',d:'المحفظة الرقمية',logo:'stc pay'},
 {id:'wallet',n:'المحفظة الرقمية',d:'رصيدك في سامي',logo:'👛'},
 {id:'points',n:'نقاط الولاء',d:'المتاح: 320 نقطة',logo:'✨'},
]

export const GTYPES=[
 {id:'pkg',n:'إهداء باقة',d:'اختر باقة جاهزة مصممة لتمنح تجربة متكاملة',btn:'اختر باقة لإهدائها',
  ic:'<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7s-2-5-5-5-3 5 0 5h5zM12 7s2-5 5-5 3 5 0 5h-5z"/>',
  img:'https://sami-care.sa//storage/uploads/422/zpncY6t7PeEmjKRKJnYepOUwN7nDJAVpsmNh6hut.png'},
 {id:'svc',n:'إهداء خدمة',d:'اختر خدمة واحدة أو أكثر من خدماتنا المتنوعة',btn:'اختر خدمة لإهدائها',
  ic:'<path d="M12 21s-7-4.4-9.3-8.5C.8 9 2.6 5.5 6 5.5c2 0 3.4 1.1 4 2.3.6-1.2 2-2.3 4-2.3 3.4 0 5.2 3.5 3.3 7-2.3 4.1-9.3 8.5-9.3 8.5z"/>',
  img:'https://sami-care.sa//storage/uploads/404/IO26IC8Qd0LCbCeHNjYPUhOmSWeZi3FkEPYFpsuB.jpg'},
]

export const GSTEPS=[
 {t:'نوع الهدية',s:'إهداء خدمة / باقة'},
 {t:'اختيار الهدية',s:'اختر الباقة المناسبة'},
 {t:'تخصيص الهدية',s:'الرسالة والتصميم وطريقة الإرسال'},
 {t:'الدفع',s:'إتمام عملية الدفع'},
 {t:'نجاح الإهداء',s:'تم إرسال الهدية بنجاح'},
]

export const BRANCHES=[
 {id:'qr',name:'فرع قريش',addr:'جدة، البوادي — شارع قريش',rate:4.7,hours:'12:00 ص – 10:00 م',vis:'+980 زائر شهريًا',home:false,
  img:'https://sami-care.sa//storage/uploads/389/4fKSZ0AIoLjJT4JuFKO81qWrxGQ6ymQ6zLbi1L3b.jpg'},
 {id:'bg',name:'فرع البغدادية',addr:'جدة، البغدادية الغربية — شارع حائل',rate:4.8,hours:'12:00 ص – 10:00 م',vis:'+1.2K زائر شهريًا',home:false,
  img:'https://sami-care.sa//storage/uploads/391/sSX2TVdlk84EEewXCDhT0w24X5WAJnPOA0GE6VHD.jpg'},
 {id:'hm',name:'خدمة منزلية',addr:'الباقة كاملة في منزلك — أينما كنت في جدة',rate:4.9,hours:'خدمة VIP',vis:'+2K عميل سعيد',home:true,
  img:'https://sami-care.sa//storage/uploads/407/7gF352Ispha9FbN9jPxmjnpOQDg5vFY41n5ZTdqK.png'},
]

export const BK_STEPS=[
 {t:'اختيار الوقت',s:'اختر الوقت المناسب لك'},
 {t:'تأكيد الحجز',s:'راجع تفاصيل الحجز'},
 {t:'الدفع',s:'ادفع بأمان وسهولة'},
 {t:'نجاح الحجز',s:'تم تأكيد حجزك'},
]

export const AR_DAYS=['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت']

export const AR_MONTHS=['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']

export const BK_PAYS=[
 {id:'mada',n:'مدى',logo:'مدى mada'},
 {id:'visa',n:'Visa',logo:'VISA'},
 {id:'mc',n:'ماستركارد',logo:'●● MC'},
 {id:'apple',n:'أبل باي',logo:' Pay'},
 {id:'cash',n:'الدفع عند الوصول',logo:'💵'},
]
