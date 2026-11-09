<script setup>
import { useServiceLocation } from '@/composables/useServiceLocation'

const { locations, current, pickerOpen, closePicker, confirmLocation } = useServiceLocation()
</script>

<template>
  <Teleport to="body">
    <Transition name="loc-fade">
    <div v-if="pickerOpen" class="loc-ov" @click.self="closePicker">
      <div class="loc-modal" role="dialog" aria-modal="true" aria-label="اختر مكان تنفيذ الخدمة">
        <button class="loc-close" @click="closePicker" aria-label="إغلاق">×</button>

        <div class="loc-head">
          <span class="loc-ic" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
              <path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z" /><circle cx="12" cy="10" r="3" />
            </svg>
          </span>
          <h3>اختر مكان تنفيذ الخدمة</h3>
          <p>لعرض الأسعار والمواعيد المتاحة بدقة حسب المكان</p>
        </div>

        <div class="loc-list">
          <button
            v-for="b in locations"
            :key="b.id"
            class="loc-item"
            :class="{ on: current && current.id === b.id }"
            @click="confirmLocation(b.id)"
          >
            <span class="loc-photo">
              <img :src="b.image" :alt="b.name" loading="lazy" />
              <small>{{ b.home ? 'خدمة منزلية' : 'فرع عناية سامي' }}</small>
            </span>
            <span class="loc-txt">
              <b>{{ b.name }}</b>
              <small class="loc-address">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z" /><circle cx="12" cy="10" r="3" />
                </svg>
                {{ b.address }}
              </small>
              <span class="loc-meta">
                <em>متاح الآن</em>
                <small>{{ b.home ? 'نصل إلى موقعك' : 'مواعيد يومية' }}</small>
              </span>
            </span>
            <i class="loc-go" aria-hidden="true">
              <span v-if="current && current.id === b.id">✓</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 6l-6 6 6 6" />
              </svg>
            </i>
          </button>
        </div>
      </div>
    </div>
    </Transition>
  </Teleport>
</template>

<style>
.loc-ov{position:fixed;inset:0;z-index:5000;background:rgba(18,12,5,.58);backdrop-filter:blur(4px);
  display:flex;align-items:flex-start;justify-content:center;padding:104px 20px 20px}
.loc-modal{position:relative;width:min(640px,100%);background:linear-gradient(155deg,#fff 0%,#fffdf9 62%,#faf2e5 100%);
  border:1px solid rgba(201,139,49,.22);border-radius:26px;padding:30px 28px 28px;overflow:hidden;
  box-shadow:0 38px 90px -28px rgba(30,20,6,.62);max-height:calc(100vh - 124px);overflow-y:auto}
.loc-modal::before{content:"";position:absolute;top:-90px;right:-80px;width:240px;height:190px;border-radius:50%;
  background:radial-gradient(circle,rgba(226,177,91,.2),transparent 68%);pointer-events:none}
.loc-close{position:absolute;top:14px;left:16px;width:32px;height:32px;border:0;background:#f4efe6;border-radius:50%;
  font-size:20px;line-height:1;color:#6f665c;cursor:pointer;z-index:2}
.loc-close:hover{background:#ebe3d6}

.loc-head{position:relative;text-align:center;margin-bottom:24px}
.loc-ic{display:inline-grid;place-items:center;width:62px;height:62px;border-radius:50%;
  background:linear-gradient(135deg,#b77420,#f0cf89,#be7920);color:#160f07;margin-bottom:13px;
  border:5px solid #fff7e8;box-shadow:0 12px 25px -13px rgba(156,99,23,.75)}
.loc-head h3{font-size:23px;margin:0 0 7px;color:#241f1b}
.loc-head p{font-size:12.5px;color:#8b8379;margin:0}

.loc-list{position:relative;display:grid;gap:13px}
.loc-item{direction:rtl;display:grid;grid-template-columns:122px minmax(0,1fr) 38px;align-items:center;gap:17px;
  min-height:112px;border:1.5px solid #e9e0d3;border-radius:19px;padding:11px;background:rgba(255,255,255,.9);
  cursor:pointer;text-align:right;box-shadow:0 10px 28px -24px rgba(66,42,12,.38);
  transition:transform .3s cubic-bezier(.22,1,.36,1),border-color .25s,background .25s,box-shadow .3s}
.loc-item:hover{transform:translateY(-3px);border-color:#d7a243;background:#fffaf0;
  box-shadow:0 18px 34px -22px rgba(125,78,18,.48)}
.loc-item.on{border-color:#c98b31;background:linear-gradient(110deg,#fff 0%,#fff8e9 100%);
  box-shadow:0 0 0 3px rgba(201,139,49,.13),0 18px 34px -24px rgba(125,78,18,.5)}
.loc-photo{position:relative;display:block;width:122px;height:88px;border-radius:14px;overflow:hidden;background:#1d160f}
.loc-photo::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 52%,rgba(8,6,3,.8))}
.loc-photo img{width:100%;height:100%;display:block;border-radius:14px;object-fit:cover;transition:transform .55s ease}
.loc-item:hover .loc-photo img{transform:scale(1.06)}
.loc-photo small{position:absolute;z-index:1;right:9px;bottom:7px;color:#fff;font-size:8.5px;font-weight:700}
.loc-txt{display:block;min-width:0}
.loc-txt b{display:block;font-size:16px;color:#241f1b;margin-bottom:5px}
.loc-address{display:flex!important;align-items:flex-start;gap:5px;font-size:11px!important;color:#8b8379;line-height:1.75;margin:0!important}
.loc-address svg{flex:none;margin-top:3px;color:#b77a21}
.loc-meta{display:flex;align-items:center;gap:8px;margin-top:9px}
.loc-txt em{display:flex;align-items:center;gap:5px;font-style:normal;font-size:10.5px;color:#2E8B57}
.loc-txt em::before{content:"";width:6px;height:6px;border-radius:50%;background:#2E8B57}
.loc-meta>small{margin:0;padding:3px 8px;border-radius:999px;background:#f6f0e7;color:#876f50;font-size:9px}
.loc-go{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;color:#a99d90;
  border:1px solid #eee5d9;background:#fff;transition:.25s}
.loc-go span{font-style:normal;font-size:14px;font-weight:800;color:#7c571d}
.loc-go span+svg{display:none}
.loc-item:hover .loc-go,.loc-item.on .loc-go{border-color:#d6aa62;background:#f6e3bd;color:#76501a}

.loc-fade-enter-active,.loc-fade-leave-active{transition:opacity .22s ease}
.loc-fade-enter-from,.loc-fade-leave-to{opacity:0}
.loc-fade-enter-active .loc-modal{animation:locModalIn .42s cubic-bezier(.22,1,.36,1)}
@keyframes locModalIn{from{opacity:0;transform:translateY(18px) scale(.97)}to{opacity:1;transform:none}}

@media(max-width:520px){
  .loc-modal{padding:26px 16px 18px}
  .loc-item{grid-template-columns:88px minmax(0,1fr) 30px;gap:10px;min-height:92px;padding:8px}
  .loc-photo{width:88px;height:68px}
  .loc-txt b{font-size:14px}
  .loc-meta>small{display:none}
}
</style>
