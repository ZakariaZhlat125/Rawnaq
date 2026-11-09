<script setup>
import { useSiteContact } from '@/composables/useSiteContact'
import WheelResultDialog from '@/components/common/WheelResultDialog.vue'
import { ref, computed } from 'vue'
import { fetchWheelInfo, spinWheel } from '@/services/wheelApi'

/* ===== WhatsApp ===== */
const { whatsappUrl } = useSiteContact()

/* ===== Lucky Wheel Modal ===== */
const showModal = ref(false)
const fetchedPrizes = ref([])
const wheelEnabled = ref(true)
const canSpin = ref(true)
const errorMessage = ref('')
const wheelLoaded = ref(false)

async function openWheel() {
  showModal.value = true
  if (!wheelLoaded.value) {
    try {
      const info = await fetchWheelInfo()
      wheelEnabled.value = info.enabled !== false
      canSpin.value = info.can_spin !== false
      fetchedPrizes.value = info.prizes || []
    } catch (err) {
      console.error('FloatingActions: wheel load error', err)
    }
    wheelLoaded.value = true
  }
}

function closeWheel() {
  showModal.value = false
}

const fallbackSegments = [
  { id: null, lines: ['هدية', 'مجانية'], color: '#171310' },
  { id: null, lines: ['خصم', '20%'], color: '#caa565' },
  { id: null, lines: ['جلسة', 'مجانية'], color: '#171310' },
  { id: null, lines: ['200 نقطة', 'ولاء'], color: '#caa565' },
  { id: null, lines: ['خصم', '15%'], color: '#171310' },
  { id: null, lines: ['خصم', '10%'], color: '#caa565' },
]

const segments = computed(() => {
  const source = fetchedPrizes.value.filter(Boolean)
  if (!source.length) return fallbackSegments

  return source.map((item, index) => {
    const reward = Number(item.reward_value ?? item.value ?? item.amount ?? 0)
    const label = item.type === 'wallet_balance' ? 'رصيد محفظة' : item.type === 'points' ? 'نقاط ولاء' : (item.type || item.name || `جائزة ${index + 1}`)
    const lines = [
      label,
      reward > 0 ? `${reward} ${item.unit || ''}`.trim() : 'مكافأة',
    ]
    return {
      id: item.id ?? null,
      lines: lines.filter(Boolean),
      color: index % 2 === 0 ? '#171310' : '#caa565',
    }
  })
})

const segAngle = computed(() => 360 / segments.value.length)
const CX = 100, CY = 100, R = 92, LABEL_R = 58

function polar(radius, angleDeg) {
  const rad = ((angleDeg - 90) * Math.PI) / 180
  return { x: CX + radius * Math.cos(rad), y: CY + radius * Math.sin(rad) }
}

function wedgePath(startAngle, endAngle) {
  const p1 = polar(R, startAngle)
  const p2 = polar(R, endAngle)
  return `M ${CX} ${CY} L ${p1.x.toFixed(2)} ${p1.y.toFixed(2)} A ${R} ${R} 0 0 1 ${p2.x.toFixed(2)} ${p2.y.toFixed(2)} Z`
}

const wedges = computed(() =>
  segments.value.map((seg, i) => {
    const start = i * segAngle.value
    const end = start + segAngle.value
    const mid = start + segAngle.value / 2
    const labelPos = polar(LABEL_R, mid)
    return { ...seg, path: wedgePath(start, end), labelPos }
  })
)

const rotation = ref(0)
const spinning = ref(false)
const result = ref(null)

const spinDisabled = computed(() => spinning.value || !wheelEnabled.value || !canSpin.value)

async function spin() {
  if (spinDisabled.value) return

  spinning.value = true
  result.value = null
  errorMessage.value = ''

  let response
  try {
    response = await spinWheel()
  } catch (err) {
    spinning.value = false
    errorMessage.value = err.message || 'حدث خطأ، حاول مرة أخرى'
    return
  }

  if (response?.already_used) {
    spinning.value = false
    canSpin.value = false
    errorMessage.value = response.message || 'لقد استخدمت عجلة الحظ من قبل'
    return
  }

  if (!response?.status) {
    spinning.value = false
    errorMessage.value = response?.message || 'حدث خطأ، حاول مرة أخرى'
    return
  }

  const currentSegments = segments.value
  if (!currentSegments.length) {
    spinning.value = false
    return
  }

  const wonPrizeId = response.data?.prize?.id ?? null
  let idx = wonPrizeId !== null ? currentSegments.findIndex(s => s.id === wonPrizeId) : -1
  if (idx === -1) idx = Math.floor(Math.random() * currentSegments.length)

  const targetCenter = idx * segAngle.value + segAngle.value / 2
  const extraTurns = 6
  const currentMod = rotation.value % 360
  rotation.value += (360 - currentMod) + extraTurns * 360 + (360 - targetCenter)

  setTimeout(() => {
    spinning.value = false
    canSpin.value = false
    result.value = response.message
  }, 4200)
}
</script>

<template>
  <!-- ===== Floating Buttons ===== -->
  <div class="floating-actions">
    <!-- WhatsApp -->
    <a
      v-if="whatsappUrl" :href="whatsappUrl"
      target="_blank"
      rel="noopener noreferrer"
      class="fab fab--whatsapp"
      aria-label="تواصل معنا عبر واتساب"
    >
      <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
      </svg>
      <span class="fab__label">تواصل معنا</span>
    </a>

    <!-- Lucky Wheel -->
    <button
      class="fab fab--wheel"
      aria-label="عجلة الحظ"
      @click="openWheel"
    >
      <svg viewBox="0 0 200 200" width="36" height="36" class="fab__wheel-icon">
        <circle cx="100" cy="100" r="92" fill="#171310" stroke="#caa565" stroke-width="6"/>
        <line v-for="i in 6" :key="i"
          :x1="100 + 88 * Math.cos((i * 60 - 90) * Math.PI / 180)"
          :y1="100 + 88 * Math.sin((i * 60 - 90) * Math.PI / 180)"
          :x2="100 - 88 * Math.cos((i * 60 - 90) * Math.PI / 180)"
          :y2="100 - 88 * Math.sin((i * 60 - 90) * Math.PI / 180)"
          stroke="#caa565" stroke-width="1.5" opacity=".5"
        />
        <circle cx="100" cy="100" r="20" fill="#caa565"/>
        <text x="100" y="106" text-anchor="middle" fill="#171310" font-size="20" font-weight="bold">🎯</text>
      </svg>
      <span class="fab__label">عجلة الحظ</span>
    </button>
  </div>

  <!-- ===== Lucky Wheel Modal ===== -->
  <Teleport to="body">
    <Transition name="wheel-modal">
      <div v-if="showModal" class="wheel-overlay" @click.self="closeWheel">
        <div class="wheel-modal">
          <!-- Close -->
          <button class="wheel-modal__close" @click="closeWheel" aria-label="إغلاق">✕</button>

          <!-- Wheel -->
          <div class="wheel-modal__wheel-wrap">
            <div class="wheel-modal__pointer" aria-hidden="true"></div>
            <svg
              class="wheel-modal__wheel"
              viewBox="0 0 200 200"
              :style="{ transform: `rotate(${rotation}deg)` }"
            >
              <g v-for="(w, i) in wedges" :key="i">
                <path :d="w.path" :fill="w.color" stroke="#0c0a08" stroke-width="1" />
                <text
                  v-for="(line, li) in w.lines"
                  :key="li"
                  :x="w.labelPos.x"
                  :y="w.labelPos.y + li * 11 - (w.lines.length - 1) * 5.5"
                  text-anchor="middle"
                  dominant-baseline="middle"
                  fill="#fff"
                  font-size="8"
                  font-weight="600"
                  font-family="var(--font-d, sans-serif)"
                >{{ line }}</text>
              </g>
              <circle :cx="CX" :cy="CY" r="17" fill="url(#fabHubGrad)" stroke="#4a3814" stroke-width="2" />
              <defs>
                <radialGradient id="fabHubGrad" cx="35%" cy="30%" r="75%">
                  <stop offset="0%" stop-color="#f4d99a" />
                  <stop offset="55%" stop-color="#c9a565" />
                  <stop offset="100%" stop-color="#8a6a2e" />
                </radialGradient>
              </defs>
            </svg>
          </div>

          <!-- Content -->
          <div class="wheel-modal__content">
            <h3>احصل على هديتك</h3>
            <p>قم بلف العجلة للحصول على هديتك</p>

            <button class="wheel-modal__cta" :disabled="spinDisabled" @click="spin">
              {{ spinning ? 'جاري التدوير...' : (!wheelEnabled ? 'العجلة غير متاحة حاليًا' : (!canSpin ? 'لقد استخدمت العجلة من قبل' : 'لف العجلة')) }}
            </button>

            <WheelResultDialog :message="result || errorMessage" :error="!!errorMessage && !result" @close="result = null; errorMessage = ''" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ===== Floating Action Buttons ===== */
.floating-actions {
  position: fixed;
  left: 20px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  flex-direction: column;
  gap: 14px;
  z-index: 9000;
}

.fab {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 54px;
  height: 54px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  box-shadow: 0 6px 24px -4px rgba(0,0,0,.35);
  transition: transform .25s ease, box-shadow .25s ease;
  text-decoration: none;
}

.fab:hover {
  transform: scale(1.12);
  box-shadow: 0 8px 30px -3px rgba(0,0,0,.45);
}

.fab--whatsapp {
  background: linear-gradient(135deg, #25d366, #128c7e);
  color: #fff;
}

.fab--wheel {
  background: linear-gradient(135deg, #1c1710, #2a2015);
  border: 2px solid #caa565;
  padding: 0;
  overflow: hidden;
}

.fab__wheel-icon {
  animation: fabWheelSpin 12s linear infinite;
}

@keyframes fabWheelSpin {
  to { transform: rotate(360deg); }
}

.fab__label {
  position: absolute;
  right: calc(100% + 10px);
  top: 50%;
  transform: translateY(-50%);
  background: rgba(12, 10, 8, .88);
  color: #f0cf89;
  font-size: 12px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 8px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity .25s ease, transform .25s ease;
  transform: translateY(-50%) translateX(6px);
  backdrop-filter: blur(4px);
}

.fab:hover .fab__label {
  opacity: 1;
  transform: translateY(-50%) translateX(0);
}


/* ===== Wheel Modal ===== */
.wheel-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, .7);
  backdrop-filter: blur(6px);
}

.wheel-modal {
  position: relative;
  border-radius: 24px;
  padding: 40px 36px;
  max-width: 420px;
  width: 92%;
  text-align: center;
}

.wheel-modal__close {
  position: absolute;
  top: 14px;
  left: 14px;
  background: rgba(255,255,255,.08);
  border: none;
  color: #e8d5b5;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  font-size: 14px;
  cursor: pointer;
  transition: background .2s;
  display: grid;
  place-items: center;
}

.wheel-modal__close:hover {
  background: rgba(255,255,255,.18);
}


/* Wheel inside modal */
.wheel-modal__wheel-wrap {
  position: relative;
  width: 220px;
  height: 220px;
  margin: 0 auto 24px;
}

.wheel-modal__pointer {
  position: absolute;
  top: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 10px solid transparent;
  border-right: 10px solid transparent;
  border-top: 22px solid #caa565;
  z-index: 2;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,.5));
}

.wheel-modal__wheel {
  width: 100%;
  height: 100%;
  transition: transform 4s cubic-bezier(.17, .67, .12, .99);
  filter: drop-shadow(0 4px 20px rgba(202, 165, 101, .2));
}

.wheel-modal__content h3 {
  color: #f0cf89;
  font-size: 22px;
  margin: 0 0 8px;
  font-family: var(--font-d, sans-serif);
}

.wheel-modal__content p {
  color: #b8a88a;
  font-size: 14px;
  margin: 0 0 20px;
}

.wheel-modal__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: linear-gradient(135deg, #dda745, #a96818);
  color: #fff;
  border: none;
  padding: 12px 36px;
  border-radius: 30px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: filter .25s, transform .15s;
  font-family: var(--font-d, sans-serif);
}

.wheel-modal__cta:hover:not(:disabled) {
  filter: brightness(1.15);
  transform: scale(1.04);
}

.wheel-modal__cta:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.wheel-modal__result {
  margin-top: 16px;
  color: #4ade80;
  font-size: 15px;
  font-weight: 700;
}

.wheel-modal__error {
  margin-top: 16px;
  color: #f87171;
  font-size: 13px;
}


/* ===== Transitions ===== */
.wheel-modal-enter-active { transition: all .35s ease-out; }
.wheel-modal-leave-active { transition: all .25s ease-in; }
.wheel-modal-enter-from,
.wheel-modal-leave-to {
  opacity: 0;
}
.wheel-modal-enter-from .wheel-modal,
.wheel-modal-leave-to .wheel-modal {
  transform: scale(.9) translateY(20px);
}

.fade-enter-active { transition: opacity .4s ease, transform .4s ease; }
.fade-enter-from { opacity: 0; transform: translateY(6px); }


/* ===== Responsive ===== */
@media (max-width: 768px) {
  .floating-actions {
    left: 12px;
    gap: 10px;
  }
  .fab {
    width: 46px;
    height: 46px;
  }
  .fab--whatsapp svg {
    width: 24px;
    height: 24px;
  }
  .fab__wheel-icon {
    width: 30px;
    height: 30px;
  }
}
</style>
