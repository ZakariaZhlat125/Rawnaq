<template>
  <div class="col-md-4 col-lg-3">
    <div id="setting-sidebar" class="setting-sidebar-inner">
      <div class="card">
        <div class="card-body">
          <div class="list-group list-group-flush" id="setting-list">
            <div class="mb-3" :class="hasPermissions('setting_general') ? 'active-menu' : ''" id="Settings.home"
                 v-if="hasPermissions('setting_general')" @click="toggle()">
              <router-link :to="{ name: 'Settings.home' }"
                           class="btn btn-border"><i class="fas fa-cube"></i>{{ $t('setting_sidebar.lbl_General') }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_misc') ? 'active-menu' : ''" id="Settings.misc"
                 v-if="hasPermissions('setting_misc')" @click="toggle()">
              <router-link :to="{ name: 'Settings.misc' }"
                           class="btn btn-border"><i class="fa-solid fa-screwdriver-wrench "></i>{{
                  $t('setting_sidebar.lbl_misc_setting')
                }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_quick_booking') ? 'active-menu' : ''"
                 id="Settings.quick-booking" v-if="hasPermissions('setting_quick_booking')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.quick-booking' }" class="btn btn-border"><i class="fa-solid fa-paper-plane"></i>{{
                  $t('setting_sidebar.lbl_quick_booking')
                }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_invoice') ? 'active-menu' : ''"
                 id="Settings.invoice-setting" v-if="hasPermissions('setting_invoice')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.invoice-setting' }" class="btn btn-border"><i class="fa-solid fa-file-invoice"
                                                                                     aria-hidden="true"></i>{{
                  $t('setting_sidebar.lbl_inv_setting')
                }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_custom_code') ? 'active-menu' : ''"
                 id="Settings.custom-code" v-if="hasPermissions('setting_custom_code')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.custom-code' }" class="btn btn-border"><i class="fa-solid fa-file-code"></i>{{
                  $t('setting_sidebar.lbl_custom_code')
                }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_customization') ? 'active-menu' : ''"
                 id="Settings.customization" v-if="hasPermissions('setting_customization')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.customization' }" class="btn btn-border"><i class="fa-solid fa-swatchbook"></i>{{
                  $t('setting_sidebar.lbl_customization')
                }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_mail') ? 'active-menu' : ''" id="Settings.mail"
                 v-if="hasPermissions('setting_mail')" @click="toggle()">
              <router-link :to="{ name: 'Settings.mail' }"
                           class="btn btn-border"><i class="fas fa-envelope"></i>{{ $t('setting_sidebar.lbl_mail') }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_notification') ? 'active-menu' : ''"
                 id="Settings.integration" v-if="hasPermissions('setting_notification')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.notificationsetting' }" class="btn btn-border"><i
                class="fa-solid fa-bullhorn"></i>{{ $t('setting_sidebar.lbl_notification') }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_intigrations') ? 'active-menu' : ''"
                 id="Settings.notificationsetting" v-if="hasPermissions('setting_intigrations')" @click="toggle()">
              <router-link :to="{ name: 'Settings.integration' }" class="btn btn-border"><i
                class="fa-solid fa-sliders"></i>{{ $t('setting_sidebar.lbl_integration') }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_custom_fields') ? 'active-menu' : ''"
                 id="Settings.custom-fields" v-if="hasPermissions('setting_custom_fields')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.custom-fields' }" class="btn btn-border"><i class="fa-solid fa-bars"></i>{{
                  $t('setting_sidebar.lbl_custom_field')
                }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_currency') ? 'active-menu' : ''"
                 id="Settings.currency-settings" v-if="hasPermissions('setting_currency')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.currency-settings' }" class="btn btn-border"><i
                class="fa fa-dollar fa-lg mr-2"></i>{{ $t('setting_sidebar.lbl_currency_setting') }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_cod_settings') ? 'active-menu' : ''"
                 id="Settings.cod-settings" v-if="hasPermissions('setting_cod_settings')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.cod-settings' }" class="btn btn-border"><i
                class="fa-solid fa-hand-holding-dollar"></i>{{
                  $t('setting_sidebar.lbl_cod_settings')
                }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_commission') ? 'active-menu' : ''"
                 id="Settings.commission" v-if="hasPermissions('setting_commission')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.commission' }" class="btn btn-border"><i class="fa fa-percent fa-lg mr-2"></i>{{
                  $t('setting_sidebar.lbl_commission')
                }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_holiday') ? 'active-menu' : ''" id="Settings.holiday"
                 v-if="hasPermissions('setting_holiday')" @click="toggle()">
              <router-link :to="{ name: 'Settings.holiday' }"
                           class="btn btn-border"><i class="fa-solid fa-calendar-check mr-2"></i>{{
                  $t('setting_sidebar.lbl_holiday')
                }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_bussiness_hours') ? 'active-menu' : ''"
                 id="Settings.shifts-bussiness" v-if="hasPermissions('setting_bussiness_hours')" @click="toggle()">
              <router-link :to="{ name: 'Settings.shifts-bussiness' }" class="btn btn-border"><i
                class="fa-solid fa-user-clock"></i>{{ $t('setting_sidebar.lbl_shifts_bussiness') }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_bussiness_hours') ? 'active-menu' : ''"
                 id="Settings.bussiness-hours" v-if="hasPermissions('setting_bussiness_hours')" @click="toggle()">
              <router-link :to="{ name: 'Settings.bussiness-hours' }" class="btn btn-border"><i
                class="fa-solid fa-clock"></i>{{ $t('setting_sidebar.lbl_bussiness') }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_theme') ? 'active-menu' : ''"
                 id="Settings.home-theme" v-if="hasPermissions('setting_theme')" @click="toggle()">
              <router-link :to="{ name: 'Settings.home-theme' }" class="btn btn-border"><i
                class="fa-solid fa-palette"></i>{{ $t('setting_sidebar.lbl_home_theme') }}
              </router-link>
            </div>

            <!-- This is setting_work_hours -->
            <div class="mb-3" :class="hasPermissions('setting_work_hours') ? 'active-menu' : ''"
                 id="Settings.work-hours" v-if="hasPermissions('setting_work_hours')" @click="toggle()">
              <router-link :to="{ name: 'Settings.work-hours' }" class="btn btn-border">
                <i class="fa-solid fa-business-time"></i>
                {{ $t('setting_sidebar.lbl_work_hours') }}
              </router-link>
            </div>

            <!-- This is setting_service_duration_settings -->
            <div class="mb-3" :class="hasPermissions('setting_duration_visibility') ? 'active-menu' : ''"
                 id="Settings.work-hours" v-if="hasPermissions('setting_duration_visibility')" @click="toggle()">
              <router-link :to="{ name: 'Settings.duration-visibility' }" class="btn btn-border">
                <i class="fa-solid fa-conf"></i>
                <i class="fa-solid fa-gear"></i>
                {{ $t('setting_sidebar.lbl_service_duration') }}
              </router-link>
            </div>

            <!-- This is setting_cart_duration -->
            <div class="mb-3" :class="hasPermissions('setting_cart_duration') ? 'active-menu' : ''"
                 id="Settings.work-hours" v-if="hasPermissions('setting_cart_duration')" @click="toggle()">
              <router-link :to="{ name: 'Settings.cart-duration' }" class="btn btn-border">
                <i class="fa-solid fa-gear"></i>
                {{ $t('setting_sidebar.lbl_cart_duration') }}
              </router-link>
            </div>

            <!-- This is points_validity_duration -->
            <div class="mb-3" :class="hasPermissions('setting_points_validity') ? 'active-menu' : ''"
                 id="Settings.points-validity" v-if="hasPermissions('setting_points_validity')" @click="toggle()">
              <router-link :to="{ name: 'Settings.points-validity' }" class="btn btn-border">
                <i class="fa-solid fa-gear"></i>
                {{ $t('setting_sidebar.lbl_points_validity') }}
              </router-link>
            </div>


            <div class="mb-3" :class="hasPermissions('setting_payment_method') ? 'active-menu' : ''"
                 id="Settings.payment-method" v-if="hasPermissions('setting_payment_method')" @click="toggle()">
              <router-link :to="{ name: 'Settings.payment-method' }" class="btn btn-border"><i
                class="fa-solid fa-coins"></i>{{ $t('setting_sidebar.lbl_payment') }}
              </router-link>
            </div>
            <div class="mb-3" :class="hasPermissions('setting_language') ? 'active-menu' : ''"
                 id="Settings.language-settings" v-if="hasPermissions('setting_language')" @click="toggle()">
              <router-link
                :to="{ name: 'Settings.language-settings' }" class="btn btn-border"><i class="fa fa-language"
                                                                                       aria-hidden="true"></i>{{
                  $t('setting_sidebar.lbl_language')
                }}
              </router-link>
            </div>



          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import {useRouter} from 'vue-router';

const toggle = () => {
  const formOffcanvas = document.getElementById('offcanvas')
  formOffcanvas.classList.add("show")
}

const hasPermissions = (name) => {
  return window.auth_permissions.includes(name)
}

</script>
<style scoped>
.btn-border {
  text-align: left;
  display: flex;
  align-items: center;
  gap: .75rem;
}
</style>
