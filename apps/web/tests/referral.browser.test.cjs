
const {test}=require('node:test'),assert=require('node:assert/strict');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE);const base=process.env.LANGUAGE_TEST_URL;
for(const mode of ['desktop','index.html','index.dynamic.html'])test('referral registration and account '+mode,{timeout:90000},async()=>{
 const b=await chromium.launch(),p=await b.newPage({viewport:{width:mode==='desktop'?1440:390,height:900}}),requests=[],errors=[];p.on('pageerror',e=>errors.push(e.message));
 await p.addInitScript(()=>{localStorage.setItem('samiSiteLang','en');localStorage.setItem('samiMobileLang','en')});
 await p.route('**/*',r=>{const q=r.request(),u=q.url();if(['fetch','xhr'].includes(q.resourceType())&&!u.includes('/src/')&&!u.includes('/node_modules/')){let data=[];if(u.endsWith('/register')){requests.push(q.postDataJSON());data={mobile:'966500000123'}}if(u.includes('verify-register-otp'))data={api_token:'test-token',referral_code:'SCNEW123',first_name:'New member'};if(u.endsWith('/profile'))data={user:{first_name:'New member',referral_code:'SCNEW123'},balances:{},stats:{}};return r.fulfill({json:{status:true,data}})}return u.startsWith(base)?r.continue():r.abort()});
 try{await p.goto(base+(mode==='desktop'?'/account':'/mobile/'+mode+'?view=account'));
 if(mode==='desktop'){
  await p.locator('.auth-tabs button').nth(1).click();await p.locator('input[autocomplete="name"]').fill('New member');await p.locator('input[autocomplete="tel"]').fill('0500000123');await p.locator('input[name="referral_code"]').fill('SCFRIEND');assert.equal(await p.locator('input[name="referral_code"]').getAttribute('placeholder'),'Referral code (optional)');await p.locator('.auth-submit').click();await p.locator('input[autocomplete="one-time-code"]').fill('1234');await p.locator('.auth-submit').click();
 }else{await p.locator('[data-auth-tab="register"]').click();await p.locator('#authUsername').fill('New member');await p.locator('#authMobile').fill('0500000123');await p.locator('#authReferral').fill('SCFRIEND');assert.equal(await p.locator('#authReferral').getAttribute('placeholder'),'Referral code (optional)');await p.locator('#authForm button[type="submit"]').click();await p.locator('#authOtp').fill('1234');await p.locator('#authOtpForm button[type="submit"]').click()}
 assert.equal(requests[0].referral_code,'SCFRIEND');await p.locator('input[readonly][value="SCNEW123"]').waitFor();assert.deepEqual(errors,[]);
 }finally{await b.close()}
});
