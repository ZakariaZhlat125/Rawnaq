<template>
  <form>
    <div :class="`offcanvas offcanvas-end`" data-bs-scroll="true" tabindex="-1" id="booking-form" aria-labelledby="offcanvasBookingForm">
      <template v-if="SINLGE_STEP == 'LOADER'">
        <div class="booking-loader">
          <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          <span>{{ $t('booking.loading_appointment_details') }}</span>
        </div>
      </template>
      <template v-else-if="SINLGE_STEP == 'MAIN' && status == 'completed' && props.bookingType !== 'CALENDER_BOOKING'">
        <InvoiceComponent :booking_id="id"></InvoiceComponent>
      </template>
      <template v-else-if="SINLGE_STEP == 'MAIN' && status != 'checkout'">
        <div class="offcanvas-header">
          <BookingHeader :booking_id="id" :status="status" :is_paid="is_paid" @statusUpdate="updateStatus"></BookingHeader>
        </div>
        <p class="ps-3" v-if="id > 0">
          <strong>{{ $t('booking.lbl_appointment_id') }} :-{{ id }} </strong>
        </p>
        <div class="px-3 pb-3" v-if="id > 0">
          <div class="booking-payment-status" :class="isPaidBooking ? 'is-paid' : 'is-unpaid'">
            <span>{{ $t('booking.lbl_payment_status') }}</span>
            <strong>{{ paymentStatusLabel }}</strong>
          </div>
        </div>
        <BookingStatus v-if="id" :status="status" :is-paid="is_paid" :booking_id="id" :status-list="statusList" :employee_id="employee_id" @statusUpdate="updateStatus"></BookingStatus>
        <div>
          <div class="d-flex text-center date-time">
            <div class="col-6 py-3">
              <i>{{ $t('booking.lbl_on') }}</i> <strong v-if="start_date_time && start_date_time !== 'Invalid date'">{{ moment(start_date_time).format('D, MMM YYYY') }}</strong>
              <strong v-else> {{ moment(current_date).format('D, MMM YYYY') }}</strong>
            </div>
            <div class="col-6 py-3">
              <i>{{ $t('booking.lbl_at') }}</i> <strong v-if="start_date_time && start_date_time !== 'Invalid date'">{{ moment(start_date_time).format('LT') }}</strong>
              <strong v-else>--:--</strong>
            </div>
          </div>
        </div>
        <div class="offcanvas-body border-top">
          <div class="form-group" v-if="canShowScheduleControls">
            <Multiselect id="branch_id" :placeholder="$t('booking.select_branch')" v-model="branch_id" :disabled="isPaidBooking || isScheduleDisabled" :value="branch_id" v-bind="singleSelectOption" :options="branch.options" @select="branchSelect" @change="removeBranch" class="form-group"></Multiselect>
          </div>
          <div class="row">
            <div class="form-group col-6" v-if="canShowScheduleControls" >
              <div class="booking-datepicker">
                <flat-pickr v-model="current_date" :disabled="isScheduleDisabled" :placeholder="$t('booking.select_date')" @change="dateChange" :config="config" class="form-control" />
              </div>
            </div>
            <div class="form-group col-6"  v-if="canShowScheduleControls">
              <div class="booking-field-wrap">
                <Multiselect id="employee_id" :placeholder="$t('booking.select_staff')" v-model="employee_id" :value="employee_id" :disabled="isScheduleDisabled || IS_EMPLOYEES_LOADING" v-bind="singleSelectOption" :options="employee.options" @select="employeeSelect" @change="removeEmployee" class="form-group"></Multiselect>
                <span v-if="IS_EMPLOYEES_LOADING" class="booking-field-loader">
                  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                  {{ $t('booking.loading_available_staff') }}
                </span>
              </div>
            </div>
          </div>
          <div class="form-group" v-if="canShowScheduleControls && employee_id">
            <div class="booking-field-wrap">
              <Multiselect id="star_time" :placeholder="$t('booking.select_time')" v-model="start_date_time" :disabled="isScheduleDisabled || IS_SLOTS_LOADING" :value="start_date_time" v-bind="singleSelectOption" :options="slots" @select="slotSelect" @change="removeSlot" class="form-group"></Multiselect>
              <span v-if="IS_SLOTS_LOADING" class="booking-field-loader">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                {{ $t('booking.loading_available_times') }}
              </span>
            </div>
          </div>
          <div class="row d-none" v-if="canShowScheduleControls && employee_id && start_date_time">
            <div class="form-group col-6">
              <label class="form-label" for="booking_end_time">وقت النهاية</label>
              <input
                id="booking_end_time"
                v-model="end_time_input"
                type="time"
                class="form-control"
                :disabled="isScheduleDisabled || !selectedService.length"
                @input="onEndTimeInput"
              />
            </div>
            <div class="form-group col-6 d-flex align-items-end">
              <div class="w-100">
                <div class="small text-muted">
                  مدة الخدمة: <strong>{{ effectiveBookingDuration }}</strong> دقيقة
                </div>
                <div v-if="end_time_error" class="small text-danger mt-1">{{ end_time_error }}</div>
                <div v-else-if="!selectedService.length" class="small text-muted mt-1">اختر خدمة أولاً لتحديد وقت النهاية.</div>
                <div v-else class="small text-muted mt-1">
                  {{ is_end_time_manual ? 'تم تحديد وقت النهاية يدويًا.' : 'تم تحديد وقت النهاية تلقائيًا حسب مدة الخدمة.' }}
                </div>
              </div>
            </div>
          </div>
          <div class="form-group border-bottom">
            <div v-if="selectedCustomer">
              <div class="d-flex align-items-start gap-3 mb-2">
                <img :src="selectedCustomer.profile_image" alt="avatar" class="img-fluid avatar avatar-60 rounded-pill" />
                <div class="flex-grow-1">
                  <div class="gap-2">
                    <strong>{{ selectedCustomer.full_name }}</strong>
                    <p class="m-0">
                      <small>{{ $t('booking.client_since') }} {{ moment(selectedCustomer.created_at).format('MMMM YYYY') }}</small>
                    </p>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <button type="button" @click="openCustomerEditModal()" class="btn btn-sm btn-outline-primary">
                    <i class="fa-regular fa-pen-to-square"></i>
                  </button>
                  <button type="button" v-if="canEditFullBooking && !id" @click="removeCustomer()" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                </div>
              </div>
              <div class="row">
                <label class="col-3"
                ><i>{{ $t('booking.lbl_phone') }}</i></label
                >
                <strong class="col">
                  <span v-if="selectedCustomer.mobile">{{ selectedCustomer.mobile }}</span>
                  <span v-else>-</span></strong
                >
              </div>
              <div class="row mb-3">
                <label class="col-3"
                ><i>{{ $t('booking.lbl_e-mail') }}</i></label
                >
                <strong class="col">
                  <span v-if="selectedCustomer.email">{{ selectedCustomer.email }}</span>
                  <span v-else>-</span>
                </strong>
              </div>
            </div>
            <Multiselect
              id="user_id"
              v-else v-model="user_id"
              :placeholder="customerSearchPlaceholder"
              :disabled="isPaidBooking || filterStatus(status).is_disabled"
              :value="user_id"
              v-bind="singleSelectOption"
              :options="customer.options"
              @select="customerSelect"
              class="form-group"
            >
              <template #option="{ option }">
                <span v-if="option.__CREATE__">
                  {{ option.label }} {{ $t('booking.add_customer_label') }}
                </span>
                <span v-else>
                  <div class="d-flex flex-column">
                    <strong>{{ option.mobile || '-' }}</strong>
                    <small>{{ option.full_name }}</small>
                  </div>
                </span>
              </template>

              <template #singlelabel="{ option }">
                <span>{{ option.mobile ? `${option.mobile} - ${option.full_name}` : option.full_name || option.label }}</span>
              </template>
            </Multiselect>
          </div>
          <ul class="form-group list-group list-group-flush">
            <li v-for="(service, index) in selectedService" :key="index" class="list-group-item py-3 px-1">
              <div class="d-flex flex-column gap-2">
                <div class="d-flex align-items-center justify-content-between">
                  <h6>{{ service.service_name }} ({{ formatCurrencyVue(service.service_price) }})</h6>
                  <div class="d-flex align-items-center gap-2">
                    <button
                      type="button"
                      v-if="canEditFullBooking && employee_id"
                      @click="toggleServiceEditor(index, service.service_id)"
                      class="btn btn-sm btn-outline-primary"
                    >
                      <i class="fa-regular fa-pen-to-square"></i>
                    </button>
                    <button type="button" v-if="canDeleteService" @click="removeService(service.service_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                  </div>
                </div>
                <div v-if="editingServiceIndex === index && canEditFullBooking" class="mt-2">
                  <Multiselect
                    :canClear="false"
                    :placeholder="$t('booking.select_service')"
                    v-model="editingServiceValue"
                    :value="editingServiceValue"
                    v-bind="singleSelectOption"
                    :options="replaceableServiceOptions(service)"
                    @select="replaceService(index, $event)"
                    class="form-group"
                  ></Multiselect>
                </div>
                <p class="m-0">
                  <label
                  ><i>{{ $t('booking.lbl_with') }}</i></label
                  >
                  <strong>{{ service.employee?.full_name || selectedEmployee?.name || '' }}</strong>
                </p>
                <div>
                  <label
                  ><i>{{ $t('booking.lbl_at') }}</i></label
                  >
                  <strong v-if="service.start_date_time !== 'Invalid date'">{{ moment(service.start_date_time).format('LT') }}</strong
                  ><strong v-else>--:--</strong> <span class="px-2">|</span> <label class="me-2"><i>{{ $t('booking.lbl_for') }} </i></label><strong>{{ service.duration_min }} {{ $t('booking.lbl_minutes') }}</strong>
                </div>
                <div v-if="canEditFullBooking" class="row g-2 align-items-end">
                  <div class="col-sm-6">
                    <label class="form-label mb-1">مدة الخدمة</label>
                    <input
                      v-model="service.manual_duration_min"
                      type="number"
                      min="1"
                      class="form-control form-control-sm"
                      :placeholder="serviceDurationPlaceholder(service)"
                      @input="applyServiceDuration(service)"
                    />
                  </div>
                  <div class="col-sm-6">
                    <small class="text-muted d-block">اتركها فارغة لاستخدام المدة الافتراضية: {{ serviceDurationHint(service) }}</small>
                  </div>
                </div>
              </div>
            </li>
          </ul>
          <div v-if="selectedService.length > 0" class="small text-muted text-end mt-2">
            إجمالي مدة الخدمات: <strong>{{ effectiveBookingDuration }}</strong> دقيقة
          </div>
          <div v-if="canEditFullBooking && selectPurchasePackages.length === 0 && services_id.length < service.options.length && selectedCustomer && employee_id" class="text-center">
            <Multiselect v-if="newService" :canClear="false" :placeholder="$t('booking.select_service')" ref="serviceInput" class="" v-model="services_id" :value="services_id" v-bind="multipleSelectOption" :options="service.options" @select="serviceSelect" id="service_ids">
              <template v-slot:multiplelabel="{ values }">
                <div class="multiselect-multiple-label">{{ $t('booking.select_service') }}</div>
              </template>
            </Multiselect>
            <template v-else>
              <a v-if="canEditFullBooking && start_date_time" href="javascript:void(0)" @click="addNewService" class="btnw-100"><i class="fa-solid fa-circle-plus"></i> {{ $t('booking.lbl_add_service') }}</a>
            </template>
          </div>
          <div v-if="canEditFullBooking && selectPurchasePackages.length == 0 && selectedCustomer && employee_id" class="text-center bg-soft-primary p-5 iq-package mt-3" @click="purchasePackageModel">
            <div class="d-flex justify-content-center mb-3">
              <div class="avatar avatar-60 rounded-pill bg-soft-secondary">
                <i class="fa-solid fa-gift"></i>
              </div>
            </div>
            <h5>{{ filteredPackages.length }} {{ $t('booking.lbl_package_available') }}</h5>
            <h6 class="text-primary">{{ $t('booking.lbl_view_all') }}</h6>
          </div>
          <div v-if="selectPurchasePackages.length > 0 && selectedCustomer && employee_id">
            <ul class="form-group list-group list-group-flush iq-package-list">
              <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-0">{{ $t('booking.lbl_packages_detail') }}</h6>
              </div>
              <li v-for="(packages, index) in selectPurchasePackages" :key="index" class="list-group-item py-3 px-3 bg-soft-primary rounded-3 m-2">
                <div class="d-flex flex-column gap-2">
                  <div class="d-flex align-items-center justify-content-between">
                    <h6>{{ packages.name }}</h6>
                    <button type="button" v-if="canEditFullBooking" @click="removePurchasePackage(packages.package_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                  </div>

                  <p class="mb-0">
                    <span class="text-primary">{{ formatCurrencyVue(packages.package_price) }}</span
                    >/{{ displayPackageDuration(packages.start_date, packages.end_date) }}
                  </p>
                  <div v-for="packageServices in packages.services" :key="packageServices.id">
                    <div class="mb-4">
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <p class="mb-0">-> {{ packageServices.service_name }} -</p>
                        <h6 class="mb-0">60 {{ $t('booking.lbl_minutes') }}</h6>
                      </div>
                      <div class="d-flex align-items-center gap-2 ms-3">
                        <p class="mb-0">{{ $t('booking.lbl_quantity') }}:</p>
                        <h6 class="mb-0">{{ packageServices.quantity ?? packageServices.qty ?? packageServices.remaining_qty }}</h6>
                      </div>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
        <div class="offcanvas-footer">
          <div v-if="userPackage && userPackage.length > 0 && selectPurchasePackages.length === 0">
            <a href="javascript:void(0)" @click="openUserPackage()" class="alert alert-success d-flex align-items-center justify-content-between mx-3" role="alert">
              <div class="d-flex gap-2 align-items-center">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p class="mb-0">{{ $t('booking.lbl_package_active') }}</p>
              </div>
              <p class="mb-0">{{ $t('booking.lbl_view_all') }}</p>
            </a>
          </div>
          <div class="form-group px-3">
            <label class="form-label">{{ $t('booking.lbl_note') }}</label>
            <textarea name="note" :disabled="isPaidBooking || filterStatus(status).is_disabled" v-model="note" cols="60" class="form-control"></textarea>
          </div>
          <div class="form-group m-0 p-3 d-flex justify-content-between border-top">
            <label for=""
            ><strong>{{ $t('booking.lbl_sub_tot') }} </strong>
            </label>
            <span>{{ formatCurrencyVue(SUB_TOTAL_SERVICE_AMOUNT) }}</span>
          </div>
          <div class="d-grid gap-3" v-if="canSaveBooking">
            <button type="button" :disabled="IS_SUBMITED || !canSubmitBooking" :class="`btn ${canSubmitBooking ? 'btn-primary' : 'disabled btn-gray'} btn-lg rounded-0 d-block`" @click="formSubmit">
              <template v-if="IS_SUBMITED">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                {{ $t('booking.loading') }}
              </template>
              <span v-else><i class="fa-solid fa-floppy-disk me-2"></i>{{ $t('messages.save_appointment') }}</span>
            </button>
          </div>
        </div>
      </template>
      <template v-else-if="SINLGE_STEP == 'CHECK_OUT' && status == 'checkout'">
        <div class="offcanvas-header">
          <div class="d-flex gap-2 align-items-center">
            <h4 class="offcanvas-title" id="form-offcanvasLabel">{{ $t("Checkout") }}</h4>
            <small class="badge bg-success" v-if="is_paid">{{ $t('booking.lbl_is_paid') }}</small>
          </div>

          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" :aria-label="$t('Close')"></button>
        </div>
        <p class="ps-3" v-if="id > 0">
          <strong>{{ $t('Appointment Id') }} :-{{ id }} </strong>
        </p>
        <BookingStatus v-if="id" :status="status" :is-paid="is_paid" :booking_id="id" :status-list="statusList" :employee_id="employee_id" @statusUpdate="updateStatus"></BookingStatus>
        <div class="offcanvas-body border-top">
          <div v-if="selectedCustomer" class="border-bottom mb-3">
            <div class="d-flex align-items-start gap-3 mb-3">
              <img :src="selectedCustomer.profile_image" alt="avatar" class="img-fluid avatar avatar-60 rounded-pill" />
              <div class="flex-grow-1">
                <div class="gap-2">
                  <strong>{{ selectedCustomer.full_name }}</strong>
                  <p class="m-0">
                    <small>{{ $t("Client since") }} {{ moment(selectedCustomer.created_at).format('MMMM YYYY') }}</small>
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div v-if="selectedService.length > 0" :class="selectedPackage.length > 0 ? 'border-bottom' : ''">
            <h5 class="mt-3">{{ $t('admin_ui.services') }}</h5>
            <ul class="form-group list-group list-group-flush">
              <li v-for="(service, index) in selectedService" :key="index" class="list-group-item py-3 px-1">
                <div class="d-flex flex-column gap-2">
                  <div class="d-flex align-items-center justify-content-between" :class="{ 'text-decoration-line-through text-danger': isPackageServiceSelected(service.service_id) }">
                    <h6>
                      {{ service.service_name }} <span :id="service.service_id">({{ formatCurrencyVue(service.service_price) }})</span><span :id="service.service_name"></span>
                    </h6>
                    <button type="button" v-if="!is_paid" @click="removeService(service.service_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                  </div>
                  <p class="m-0">
                    <label
                    ><i>{{ $t('booking.lbl_with') }}</i></label
                    >
                    <strong>{{ service.employee?.full_name || selectedEmployee?.name || '' }}</strong>
                  </p>
                  <div>
                    <label
                    ><i>{{ $t('booking.lbl_at') }}</i></label
                    >
                    <strong>{{ moment(service.start_date_time).format('LT') }}</strong> <span class="px-2">|</span> <label class="me-2"> <i>{{ $t('For:') }}</i></label
                  ><strong> {{ service.duration_min }} {{ $t("Min") }}</strong>
                  </div>
                </div>
                <div v-if="!isPackageServiceSelected(service.service_id) && isServiceInFilteredPackages(service.service_id)">
                  <div class="btn w-100 btn-primary mt-2" @click="applyUserPackage(service.service_id)" :id="service.service_id" data-bs-target="#exampleModal">{{ $t("Apply a Package") }}</div>
                </div>
                <div class="mt-2 d-none d-flex justify-content-between align-items-center" :id="service.service_id + '' + service.service_id">
                  <h6>{{ service.service_name }} {{ $t("Package") }}</h6>
                  <h6 class="text-danger" v-if="singleAppliedService(service.service_id)">- {{ formatCurrencyVue(singleAppliedServicePrice) }}</h6>
                </div>
              </li>
            </ul>
          </div>
          <div v-if="selectedPackageService && selectedPackageService.length > 0">
            <div class="mt-3 mb-3">
              <h6>{{ $t("Package Service") }}</h6>
              <div class="bg-soft-secondary p-3 rounded-3">
                <div v-for="selectedPackageService in selectedPackageService">
                  <div class="d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                      <p>-></p>
                      <p>{{ selectedPackageService.service_name }}</p>
                    </div>
                    <button type="button" v-if="!is_paid" @click="removeApplyPackageService(selectedPackageService.service_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                  </div>
                  <div class="d-flex align-items-center justify-content-between ms-3">
                    <div class="d-flex align-items-center gap-3">
                      <p class="mb-0">{{ $t('admin_ui.quantity') }}</p>
                      <h6 class="mb-0">{{ selectedPackageService.qty }}</h6>
                    </div>
                    <div>
                      <h6 class="text-danger">({{ $t('Remaining') }} {{ selectedPackageService.remaining_qty - selectedPackageService.qty }}/{{ selectedPackageService.total_qty }})</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="selectPurchasePackages.length > 0 && selectedCustomer && employee_id">
            <ul class="form-group list-group list-group-flush mt-3">
              <div class="d-flex align-items-center justify-content-between">
                <h6>{{ $t('booking.lbl_packages_detail') }}</h6>
              </div>
              <li v-for="(packages, index) in selectPurchasePackages" :key="index" class="list-group-item py-3 px-3 bg-soft-primary rounded-3 m-2">
                <div class="d-flex flex-column gap-2">
                  <div class="d-flex align-items-center justify-content-between">
                    <h6>{{ packages.name }}</h6>
                    <button type="button" v-if="canEditFullBooking" @click="removePurchasePackage(packages.package_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                  </div>
                  <p>
                    <span class="text-primary">{{ formatCurrencyVue(packages.package_price) }}</span
                    >/{{ displayPackageDuration(packages.start_date, packages.end_date) }}
                  </p>
                  <div v-for="packageServices in packages.services" :key="packageServices.id">
                    <div class="mb-4">
                      <div class="d-flex align-items-center gap-2 mb-1">
                        <p class="mb-0">-> {{ packageServices.service_name }} -</p>
                        <h6 class="mb-0">60 {{ $t('mins') }}</h6>
                      </div>
                      <div class="d-flex align-items-center gap-2 ms-3">
                        <p class="mb-0">{{ $t('admin_ui.quantity') }}</p>
                        <h6 class="mb-0">{{ packageServices.quantity ?? packageServices.qty ?? packageServices.remaining_qty }}</h6>
                      </div>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>

          <div v-if="selectedProduct.length > 0">
            <h5 class="mt-3">{{ $t('admin_ui.products') }}</h5>
            <ul class="form-group list-group list-group-flush">
              <li v-for="(product, index) in selectedProduct" :key="index" class="list-group-item py-3 px-1">
                <div class="d-flex flex-column gap-2">
                  <div class="d-flex align-items-center justify-content-between">
                    <h6>{{ product.product_name }}</h6>
                    <button type="button" v-if="!is_paid" @click="removeProduct(product.product_variation_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                  </div>
                  <div>
                    <div class="d-flex">
                      <label>{{ $t('admin_ui.price') }}</label>
                      <template v-if="product.discounted_price">
                        <h5 class="ms-2">
                          <b>{{ formatCurrencyVue(product.discounted_price) }}</b>
                        </h5>
                        <h6 class="text-secondary" v-if="product.product_price != product.discounted_price">
                          <del class="me-2">{{ formatCurrencyVue(product.product_price) }}</del>
                          <small class="text-success" v-if="product.discount_type == 'percent'">{{ product.discount_value }}% {{ $t('OFF') }}</small>
                          <small class="text-success" v-else>{{ formatCurrencyVue(product.discount_value) }} OFF</small>
                        </h6>
                      </template>
                      <template v-else>
                        <h1>{{ product.product_price }}</h1>
                        <h5 class="ms-2">
                          <b>{{ formatCurrencyVue(product.product_price) }}</b>
                        </h5>
                      </template>
                    </div>
                    <label>{{ $t('admin_ui.quantity') }} </label> <QtyButton v-model="product.product_qty" :max="product.max_qty"></QtyButton>
                  </div>
                  <p class="m-0">
                    <label><i>{{ $t("Sold By:") }} </i></label> <strong>{{ product.employee?.full_name || selectedEmployee?.name || '' }}</strong>
                  </p>
                </div>
              </li>
            </ul>
          </div>
          <div class="d-flex gap-3 justify-content-between flex-column">
            <div v-if="services_id.length < service.options.length" class="text-center">
              <Multiselect v-if="newService" :canClear="false" :placeholder="$t('admin_ui.select_service')" ref="serviceInput" class="" v-model="services_id" :value="services_id" v-bind="multipleSelectOption" :options="service.options" @select="serviceSelect" id="service_ids">
                <template v-slot:multiplelabel="{ values }">
                  <div class="multiselect-multiple-label">{{ $t("Select Service") }}</div>
                </template>
              </Multiselect>
              <template v-else>
                <a href="javascript:void(0)" v-if="selectPurchasePackages.length === 0 && !is_paid" @click="addNewService" class="btnw-100"><i class="fa-solid fa-circle-plus"></i> {{ $t('booking.lbl_add_service') }}</a>
              </template>
            </div>
            <div v-if="product_variation_id.length < products.options.length" class="text-center">
              <Multiselect v-if="newProduct" :canClear="false" :placeholder="$t('admin_ui.select_product')" ref="productInput" class="" v-model="product_variation_id" :value="product_variation_id" v-bind="multipleSelectOption" :options="products.options" @select="selectProduct" id="product_variation_ids">
                <template v-slot:multiplelabel="{ values }">
                  <div class="multiselect-multiple-label">{{ $t("Select Product") }}</div>
                </template>
              </Multiselect>
              <template v-else>
                <a href="javascript:void(0)" v-if="!is_paid" @click="addNewProduct" class="btnw-100"><i class="fa-solid fa-circle-plus"></i> {{ $t("Add Product") }}</a>
              </template>
            </div>
          </div>
        </div>

        <div class="offcanvas-footer border-top">
          <div class="form-group m-0 p-3 d-flex justify-content-between">
            <label for=""
            ><strong>{{ $t('booking.lbl_sub_tot') }} </strong>
            </label>

            <span v-if="packageApplied">{{ formatCurrencyVue(GRAND_TOTAL) }}</span>

            <span v-else>{{ formatCurrencyVue(SUB_TOTAL_SERVICE_AMOUNT - PackageServiceSelectedPrice) }}</span>
          </div>
          <div class="d-grid gap-3">
            <button type="button" :disabled="IS_SUBMITED" v-if="selectPurchasePackages.length > 0 || selectedService.length > 0" class="btn btn-primary btn-lg rounded-0 d-block" @click="formSubmitCheckout">
              <template v-if="IS_SUBMITED">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                {{ $t('admin_ui.loading') }}
              </template>
              <template v-else>
                <template v-if="is_paid"> <i class="fa-solid fa-floppy-disk mx-2"></i>{{ $t('booking.lbl_complete_now') }} </template>
                <template v-else> <i class="fa-solid fa-floppy-disk mx-2"></i>{{ $t('booking.lbl_got_to_payment') }} </template>
              </template>
            </button>
          </div>
        </div>
      </template>
      <template v-else-if="SINLGE_STEP == 'PAYMENT'">
        <div class="offcanvas-header">
          <h4 class="offcanvas-title" id="form-offcanvasLabel">{{ $t('booking.lbl_payment') }}</h4>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" :aria-label="$t('Close')"></button>
        </div>
        <p class="ps-3" v-if="id > 0">
          <strong>{{ $t('Appointment Id') }} :-{{ id }} </strong>
        </p>
        <div class="offcanvas-body border-top">
          <PaymentForm @updatePaymentData="updatePaymentData" :booking-id="id" :booking-status="status" :package-service="selectedPackageService"></PaymentForm>
        </div>
        <div class="offcanvas-footer">
          <div class="d-grid gap-3">
            <button type="button" :disabled="IS_SUBMITED" class="btn btn-primary btn-lg rounded-0 d-block" @click="formSubmitPaynow">
              <template v-if="IS_SUBMITED">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                {{ $t('admin_ui.loading') }}
              </template>
              <template v-else><i class="fa-solid fa-floppy-disk"></i> {{ $t('booking.lbl_pay_now') }}</template>
            </button>
          </div>
        </div>
      </template>
    </div>

    <div class="modal fade modal-lg" id="applyUserPackage" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            <div class="modal-header mb-3">
              <button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="$t('Close')"></button>
            </div>
            <div class="row">
              <div v-for="packages in filteredUserPackages" :key="packages.id">
                <div class="d-flex justify-content-between mb-3">
                  <h4>{{ packages.name }}</h4>
                </div>
                <div v-for="(packageServices, index) in packages.services" :key="packageServices.id" class="d-flex justify-content-between mb-3">
                  <div class="d-flex align-items-center gap-3">
                    <input type="checkbox" :id="'service-' + packageServices.id" :checked="isServiceSelected(packageServices.user_package_id, packageServices.service_id)" @change="updateSelectedServices(packageServices)" />
                    <p class="mb-0">-></p>
                    <label :for="'service-' + packageServices.id">{{ packageServices.service_name }} </label>
                    - <label :for="'service-' + packageServices.id" class="fw-semibold text-dark">{{ packageServices.duration_min }} {{ $t('mins') }}</label>
                  </div>
                  <div class="d-flex align-items-center gap-3">
                    <label :for="'qtybutton-' + packageServices.id">{{ $t('admin_ui.quantity') }} </label>
                    <QtyButton :id="'qtybutton-' + packageServices.id" v-model="packageServices.qty" :max="packageServices.remaining_qty" @input="updateServiceQty(index, packageServices, $event)"></QtyButton>
                    <p class="mb-0">
                      {{ $t('Left') }} -<span class="text-danger me-2">{{ packageServices.remaining_qty }}</span>
                    </p>
                    <p class="mb-0">
                      {{ $t('Used') }} -<span class="text-success me-2">{{ packageServices.total_qty - packageServices.remaining_qty }}</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer position-sticky fixed-bottom bg-white">
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-secondary" @click="cancelSelectedPackageService()">{{ $t('admin_ui.cancel') }}</button>
              <button type="button" class="btn btn-primary" @click="saveSelectedPackageService()">{{ $t('admin_ui.save') }}</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade modal-lg" id="userPackage" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-body">
            <div class="modal-header mb-3">
              <h4 class="mb-0">{{ $t("Your existing package") }}</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="$t('Close')"></button>
            </div>
            <div class="row">
              <div v-for="packages in userPackage" class="col-md-6">
                <div v-if="packages.payment_status === 1" class="card bg-soft-secondary">
                  <div class="card-body text-gray">
                    <div class="d-flex justify-content-between mb-3">
                      <div>
                        <small class="bg-soft-secondary rounded-pill py-2 px-4">{{ packages.branch_name }}</small>
                      </div>
                      <div class="d-flex align-items-center gap-1">
                        <h6 class="text-primary mb-0">{{ formatCurrencyVue(packages.package_price) }}</h6>
                        / <small>{{ displayPackageDuration(packages.start_date, packages.end_date) }}</small>
                      </div>
                    </div>
                    <div class="d-flex justify-content-between">
                      <h5 class="mb-3">{{ packages.name }}</h5>
                    </div>
                    <div class="iq-purchase-package">
                      <p class="border-bottom pb-3 mb-3">{{ packages.description }}</p>
                      <div>
                        <h5 class="mb-3">{{ $t("What's Included") }}</h5>
                        <div v-for="packageServices in packages.services" :key="packageServices.id">
                          <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-1">
                              <p class="mb-0">-> {{ packageServices.service_name }} -</p>
                              <h6 class="mb-0">{{ packageServices.duration_min }} {{ $t("mins") }}</h6>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-3">
                              <p class="mb-0">{{ $t('admin_ui.quantity') }}</p>
                              <h6 class="mb-0">{{ packageServices.remaining_qty }}</h6>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card-footer pt-0">
                    <div>
                      <div v-if="isPackagePurchased(packages.id)">
                        <div class="btn btn-secondary d-block" @click="removePurchasePackageId(packages.id)">{{ $t('admin_ui.purchased') }}</div>
                      </div>
                      <div v-else>
                        <div class="btn btn-outline-secondary d-block" @click="redeemPackage(packages.package_id)">{{ $t("Use") }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade modal-xl" id="purchasePackageModel" aria-labelledby="purchasePackageModelLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="mb-0">{{ $t("Packages Available") }}</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="$t('Close')"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div v-if="filteredPackages.length > 0" v-for="packages in filteredPackages" :key="service.id" class="col-md-4">
                <div class="card bg-soft-secondary" :class="{ 'iq-card-package': isPackagePurchased(packages.id) }" id="iq-modal-package">
                  <div class="card-body text-gray">
                    <div class="d-flex justify-content-between mb-3">
                      <div>
                        <small class="bg-soft-secondary rounded-pill py-2 px-4">{{ packages.branch_name }}</small>
                      </div>
                      <div class="d-flex align-items-center gap-1">
                        <h6 class="text-primary mb-0">{{ formatCurrencyVue(packages.package_price) }}</h6>
                        / <small>{{ displayPackageDuration(packages.start_date, packages.end_date) }} </small>
                      </div>
                    </div>
                    <div class="d-flex justify-content-between">
                      <h5 class="mb-3">{{ packages.name }}</h5>
                    </div>
                    <div class="iq-purchase-package">
                      <p class="border-bottom pb-3 mb-3">{{ packages.description }}</p>
                      <h5 class="mb-3">{{ $t("What's Included :") }}</h5>
                      <div v-for="packageServices in packages.services" :key="packageServices.id">
                        <div class="mb-4">
                          <div class="d-flex align-items-center gap-2 mb-1">
                            <p class="mb-0">-> {{ packageServices.service_name }} -</p>
                            <h6 class="mb-0">{{ packageServices.duration_min }} {{ $t("mins") }}</h6>
                          </div>
                          <div class="d-flex align-items-center gap-2 ms-3">
                            <p class="mb-0">{{ $t('admin_ui.quantity') }}</p>
                            <h6 class="mb-0">{{ packageServices.quantity }}</h6>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card-footer p-2">
                    <div v-if="isPackagePurchased(packages.id)">
                      <div class="btn btn-secondary d-block" @click="removePurchasePackageId(packages.id)">{{ $t('admin_ui.purchased') }}</div>
                    </div>
                    <div v-else>
                      <div class="btn btn-secondary d-block" @click="purchasePackage(packages.id)">{{ $t("Purchase Now") }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else>
                <p>{{ $t("No package is available for the current service") }}.</p>
              </div>
            </div>
          </div>
          <div class="modal-footer position-sticky fixed-bottom bg-white">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" :aria-label="$t('Close')" @click="closeModel()">{{ $t('admin_ui.cancel') }}</button>
            <button type="button" class="btn btn-primary" @click="savePurchasePackage()">{{ $t('admin_ui.save') }}</button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <CustomerCreate :data="newCustomerData" @submit="externalFormCreation"></CustomerCreate>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import FlatPickr from 'vue-flatpickr-component'
import { useBookingStore } from '../store/booking'
import {
  INDEX_URL,
  CUSTOMER_LIST,
  SERVICE_LIST,
  SLOT_LIST,
  PAYMENT_PUT_URL,
  UPDATE_PAYMENT_DATA,
  CHECKOUT_URL,
  STRIPE_PAYMENT_DATA,
  EDIT_URL,
  STORE_URL,
  UPDATE_URL,
  UPDATE_STATUS,
  PRODUCT_LIST,
  PACKAGE_LIST,
  USER_PACKAGE_LIST
} from '../constant/booking'
import { BRANCH_LIST, IS_HOLIDAY } from '@/vue/constants/branch'
import { CUSTOMER_EDIT } from '@/vue/constants/users'

import { useField, useForm } from 'vee-validate'
import * as yup from 'yup'

import { useRequest, useOnOffcanvasHide, useOnOffcanvasShow } from '@/helpers/hooks/useCrudOpration'

import CustomerCreate from '@/vue/components/Modal/CustomerCreate.vue'

import BookingHeader from './BookingFormElements/BookingHeader.vue'
import BookingStatus from './BookingFormElements/BookingStatus.vue'
import PaymentForm from './Forms/PaymentForm.vue'
import InvoiceComponent from './Forms/InvoiceComponent.vue'

import QtyButton from '@/vue/components/form-elements/QtyButton.vue'
import { useSelect } from '@/helpers/hooks/useSelect'
import moment from 'moment'

const shownServiceIds = ref([])

const { getRequest, storeRequest, updateRequest, listingRequest } = useRequest()
const store = useBookingStore()
const emit = defineEmits(['onSubmit'])
const { t } = useI18n()

const formatCurrencyVue = (value) => {
  if (window.currencyFormat !== undefined) {
    return window.currencyFormat(value)
  }
  return value
}

const props = defineProps({
  statusList: { type: Object },
  bookingType: { type: String, default: 'GLOBAL_BOOKING' },
  bookingData: {
    default: () => {
      return {
        id: 0,
        employee_id: null,
        start_date_time: null,
        branch_id: null
      }
    }
  }
})

const IS_SUBMITED = ref(false)
const IS_EMPLOYEES_LOADING = ref(false)
const IS_SLOTS_LOADING = ref(false)
const end_time_input = ref('')
const is_end_time_manual = ref(false)
const end_time_error = ref('')
const filterStatus = (value) => {
  if (props.statusList) {
    return props.statusList[value]
  }
  return { is_disabled: false }
}

const OVERRIDE_UNAVAILABLE_SLOT_PERMISSION = 'booking_override_unavailable_slots'
const authPermissions = ref(Array.isArray(window.auth_permissions) ? window.auth_permissions : [])
const BACKEND_EDIT_OVERRIDE_ROLES = ['admin', 'manager']
const SERVICE_DELETE_OVERRIDE_ROLES = ['admin', 'manager', 'receptionist']
const authUserRoles = ref(JSON.parse(document.querySelector('meta[name="auth_user_roles"]')?.getAttribute('content')) || [])
const canOverrideConfirmedEditLock = computed(() => BACKEND_EDIT_OVERRIDE_ROLES.some((role) => authUserRoles.value.includes(role)))
const canOverrideConfirmedServiceDeleteLock = computed(() => SERVICE_DELETE_OVERRIDE_ROLES.some((role) => authUserRoles.value.includes(role)))
const canOverrideUnavailableSlots = computed(() => authPermissions.value.includes(OVERRIDE_UNAVAILABLE_SLOT_PERMISSION))
const isPaidBooking = computed(() => Number(is_paid.value) === 1)
const scheduleLockedStatuses = ['check_in', 'checkout']
const fullEditLockedStatuses = computed(() => (canOverrideConfirmedEditLock.value ? ['check_in', 'checkout'] : ['check_in', 'checkout', 'confirmed']))
const serviceDeleteLockedStatuses = computed(() => (canOverrideConfirmedServiceDeleteLock.value ? ['check_in', 'checkout'] : ['check_in', 'checkout', 'confirmed']))
const canShowScheduleControls = computed(() => !scheduleLockedStatuses.includes(status.value))
const canEditFullBooking = computed(() => !isPaidBooking.value && !fullEditLockedStatuses.value.includes(status.value) && !filterStatus(status.value).is_disabled)
const canDeleteService = computed(() => !isPaidBooking.value && !serviceDeleteLockedStatuses.value.includes(status.value) && !filterStatus(status.value).is_disabled)
const isScheduleDisabled = computed(() => !isPaidBooking.value && filterStatus(status.value).is_disabled)
const canSaveBooking = computed(() => status.value !== 'check_in' && (isPaidBooking.value || !filterStatus(status.value).is_disabled))
const paymentStatusLabel = computed(() => (isPaidBooking.value ? t('booking.status_paid') : t('booking.status_unpaid')))
const customerSearchPlaceholder = computed(() => t('booking.lbl_phone'))

const holidays = ref([])
const current_date = ref(moment().format('YYYY-MM-DD'))
const config = ref({
  dateFormat: 'Y-m-d',
  defaultDate: 'today',
  minDate: 'today',
  static: true,
  disable: holidays.value
})

watch(holidays, () => {
  config.value.disable = holidays.value
})

watch(
  () => props.bookingType,
  (value) => {
  }
)

watch(
  () => props.bookingData,
  (value) => {
    status.value = 'pending'
    selectedPackageService.value = []
    store.updateStep('LOADER')

    if (value.id !== null && value.id !== undefined && value.id !== 0) {
      id.value = value.id

      getRequest({ url: EDIT_URL, id: id.value }).then((res) => {
        if (res.status) {
          store.updateStep('MAIN')
          setFormData(res.data)

          if (res.data.coupon_redeem) {
            couponRedeem.value = res.data.coupon_redeem
          }

          // ✅ Fix: Set current_date from the actual booking date (not today's date)
          if (res.data.start_date_time) {
            current_date.value = moment(res.data.start_date_time).format('YYYY-MM-DD')
          }

          branchSelect(res.data.branch_id, true)
          employeeSelect(res.data.employee_id, true)
          getUserPackages(res.data.user_id)
        }
      })
    } else {
      setFormData(defaultData())
      branch_id.value = Number(value.branch_id) > 0 ? value.branch_id : null
      employee_id.value = value.employee_id
      start_date_time.value = value.start_date_time && moment(value.start_date_time).isValid()
        ? moment(value.start_date_time).format('YYYY-MM-DD HH:mm:ss')
        : null
      if (value.start_date_time) {
        current_date.value = moment(value.start_date_time).format('YYYY-MM-DD')
      } else {
        current_date.value = moment().format('YYYY-MM-DD')
      }
      ensureCalendarPresetOptions()
      branchSelect(branch_id.value, true)
      employeeSelect(employee_id.value, true)
      window.setTimeout(() => {
        store.updateStep('MAIN')
        ensureCalendarPresetOptions()
      }, 200)
    }
  },
  { deep: true }
)

const validationSchema = yup.object({
  start_date_time: yup.string().required('Start Date Time is required'),
  branch_id: yup.string().required(window.i18n.global.t('admin_validation.branch_required')).notOneOf(['0', 0], window.i18n.global.t('admin_validation.branch_required')),
  employee_id: yup.string().required('Employee is required'),
  services_id: yup.array().required('Services is required'),
  user_id: yup.string().required('User is required')
})

const { handleSubmit, errors, resetForm } = useForm({ validationSchema })
const { value: id } = useField('id')
const { value: note } = useField('note')
const { value: start_date_time } = useField('start_date_time')
const { value: employee_id } = useField('employee_id')
const { value: branch_id } = useField('branch_id')
const { value: user_id } = useField('user_id')
const { value: status } = useField('status')
const { value: services_id } = useField('services_id')
const { value: product_variation_id } = useField('product_variation_id')
const { value: is_paid } = useField('is_paid')
const { value: package_id } = useField('package_id')

status.value = 'pending'
product_variation_id.value = []
package_id.value = []
services_id.value = []

const userPackage = ref([])
const selectedPackageService = ref([])
const tempSelectedPackageService = ref([])
const errorMessages = ref({})
const couponRedeem = ref([])

const defaultData = () => {
  errorMessages.value = {}
  selectedPackageService.value = []
  return {
    id: null,
    branch_id: Number(props.bookingData.branch_id) > 0 ? props.bookingData.branch_id : null,
    note: '',
    start_date_time: null,
    employee_id: props.bookingData.employee_id || null,
    status: 'pending',
    services_id: [],
    product_variation_id: [],
    is_paid: 0,
    package_id: []
  }
}

const setFormData = (data) => {
  IS_SUBMITED.value = false
  newService.value = false
  newSelectedServices.value = []
  packageApplied.value = false
  userPackage.value = []
  selectedBookingCustomer.value = buildBookingCustomer(data)
  ensureSelectedCustomerOption()

  if (data.status == 'checkout') {
    store.updateStep('CHECK_OUT')
  }
  if (data.services !== undefined && data.services.length > 0) {
    selectedService.value = data.services.map((serviceItem) => hydrateBookingService(serviceItem))
    getUserPackages(data.user_id)
  } else {
    resetServices()
  }

  if (data.products !== undefined && data.products.length > 0) {
    selectedProduct.value = data.products
  } else {
    resetProducts()
  }

  if (data.bookingPackages !== undefined && data.bookingPackages.length > 0) {
    selectPurchasePackages.value = data.bookingPackages
    if (data.userPackageServices) {
      selectPurchasePackages.value.forEach((packages) => {
        let userPackageService = data.userPackageServices.find((service) => service.package_id === packages.package_id)

        if (userPackageService && packages !== undefined) {
          if (Array.isArray(packages.services) && Array.isArray(data.userPackageServices)) {
            packages.services.forEach((service) => {
              data.userPackageServices.forEach((userService) => {
                if (userService.package_service.service_id === service.service_id) {
                  service.qty = userService.qty
                }
              })
            })

            packages.services = packages.services.filter((service) => {
              const matchedUserService = data.userPackageServices.find((userService) => userService.package_service.service_id === service.service_id)
              return matchedUserService && matchedUserService.qty > 0
            })
          }
        }
      })
    }
  } else {
    resetPurchasePackage()
    userPackage.value = null
  }

  if (data.packages !== undefined && data.packages.length > 0) {
    selectedPackage.value = data.packages
  } else {
    resetPackage()
  }

  resetForm({
    values: {
      id: data.id,
      branch_id: data.branch_id,
      note: data.note,
      start_date_time: data.start_date_time,
      employee_id: data.employee_id,
      user_id: data.user_id,
      status: data.status,
      services_id: data.services_id,
      is_paid: data.is_paid,
      product_variation_id: data.product_variation_id,
      package_id: data.package_id
    }
  })

  syncEndTimeState({ preserveManual: false })
}

const externalFormCreation = (e) => {
  switch (e.type) {
    case 'create_customer':
      getCustomers(() => (user_id.value = e.value))
      break
    case 'update_customer':
      getCustomers(() => (user_id.value = e.value))
      break
  }
}

const singleSelectOption = ref({
  createOption: true,
  closeOnSelect: true,
  searchable: true
})

const multipleSelectOption = ref({
  mode: 'multiple',
  closeOnSelect: false,
  searchable: true
})

const branch = ref({ options: [], list: [] })
const employee = ref({ options: [], list: [] })
const customer = ref({ options: [], list: [] })
const service = ref({ options: [], list: [] })
const selectedBookingCustomer = ref(null)
const slots = ref([])

const buildBookingCustomer = (data) => {
  if (!data?.user_id) {
    return null
  }
  return {
    id: data.user_id,
    full_name: data.user_name || '',
    profile_image: data.user_profile_image || '',
    created_at: data.user_created || null,
    mobile: data.user_mobile || '',
    email: data.user_email || '',
    first_name: data.user_first_name || '',
    last_name: data.user_last_name || ''
  }
}

const buildCustomerOption = (customerItem) => ({
  value: customerItem.id,
  label: customerItem.mobile ? `${customerItem.mobile} - ${customerItem.full_name}` : customerItem.full_name,
  full_name: customerItem.full_name,
  mobile: customerItem.mobile,
  email: customerItem.email,
  profile_image: customerItem.profile_image,
  created_at: customerItem.created_at
})

const ensureSelectedCustomerOption = () => {
  if (!selectedBookingCustomer.value?.id) {
    return
  }
  const customerId = String(selectedBookingCustomer.value.id)
  const hasCustomer = customer.value.list.some((item) => String(item.id) === customerId)
  if (!hasCustomer) {
    customer.value.list = [selectedBookingCustomer.value, ...customer.value.list]
    customer.value.options = [
      buildCustomerOption(selectedBookingCustomer.value),
      ...customer.value.options
    ]
  }
}

const ensureSelectOption = (selectRef, option) => {
  if (!option?.value) return
  if (!selectRef.value.options.some((item) => String(item.value) === String(option.value))) {
    selectRef.value.options.unshift(option)
  }
  if (!selectRef.value.list.some((item) => String(item.id) === String(option.value))) {
    selectRef.value.list.unshift({ id: option.value, name: option.label, full_name: option.label })
  }
}

const ensureCalendarPresetOptions = () => {
  if (props.bookingType !== 'CALENDER_BOOKING') return

  const selectedBranch = branch.value.list.find((item) => String(item.id) === String(branch_id.value))
  ensureSelectOption(branch, {
    value: branch_id.value,
    label: selectedBranch?.name || selectedBranch?.label || `Branch #${branch_id.value}`
  })

  const selectedEmployee = employee.value.list.find((item) => String(item.id) === String(employee_id.value))
  ensureSelectOption(employee, {
    value: employee_id.value,
    label: selectedEmployee?.name || selectedEmployee?.full_name || props.bookingData.employee_name || `Employee #${employee_id.value}`
  })

  ensureSelectedSlotOption()
}

const canKeepSelectedUnavailableSlot = () => canOverrideUnavailableSlots.value && Boolean(start_date_time.value)

const ensureSelectedSlotOption = () => {
  if (!start_date_time.value || !moment(start_date_time.value).isValid()) {
    return
  }

  if (!slots.value.some((slot) => String(slot.value) === String(start_date_time.value))) {
    slots.value.unshift({
      value: start_date_time.value,
      label: moment(start_date_time.value).format('hh:mm A'),
      disabled: false
    })
  }
}

const buildSlotCandidateForCurrentDate = (slotValue) => {
  if (!slotValue || !current_date.value || !moment(slotValue).isValid()) {
    return null
  }

  return `${moment(current_date.value).format('YYYY-MM-DD')} ${moment(slotValue).format('HH:mm:ss')}`
}

const restoreSlotIfAvailable = (previousSlotValue) => {
  const candidateSlot = buildSlotCandidateForCurrentDate(previousSlotValue)
  const previousTimeKey = moment(previousSlotValue).isValid() ? moment(previousSlotValue).format('HH:mm') : null

  if (!candidateSlot) {
    start_date_time.value = null
    resetServiceTime()
    return
  }

  const matchedSlot = slots.value.find((slot) => {
    if (String(slot.value) === String(candidateSlot)) {
      return true
    }

    if (!moment(slot.value).isValid()) {
      return false
    }

    return previousTimeKey && moment(slot.value).format('HH:mm') === previousTimeKey
  })

  if (matchedSlot) {
    start_date_time.value = matchedSlot.value
    ensureSelectedSlotOption()
    resetServiceTime()
    syncEndTimeState()
    return
  }

  start_date_time.value = null
  resetServiceTime()
  syncEndTimeState()
}

useOnOffcanvasHide('booking-form', () => setFormData(defaultData()))
useOnOffcanvasShow('booking-form', () => {
  useSelect({ url: BRANCH_LIST }, { value: 'id', label: 'name' }).then((data) => {
    branch.value = data
    ensureCalendarPresetOptions()
  })
  branch_id.value = Number(props.bookingData.branch_id) > 0 ? props.bookingData.branch_id : null
  getCustomers()
  branchSelect(branch_id.value, Boolean(props.bookingData.start_date_time))
  getProducts()
  getpackages(branch_id.value)
})

const getCustomers = (cb) =>
  useSelect({ url: CUSTOMER_LIST }, { value: 'id', label: 'full_name' }).then((data) => {
    customer.value = {
      ...data,
      options: data.list.map((item) => buildCustomerOption(item))
    }
    ensureSelectedCustomerOption()
    if (typeof cb == 'function') {
      cb()
    }
  })

const dateChange = () => {
  const previousSlotValue = start_date_time.value

  if (current_date.value && moment(current_date.value).startOf('day').isBefore(moment().startOf('day'))) {
    current_date.value = moment().format('YYYY-MM-DD')
  }

  start_date_time.value = null
  end_time_input.value = ''
  end_time_error.value = ''
  is_end_time_manual.value = false
  slots.value = []

  loadAvailableEmployees().then(() => {
    if (!employee_id.value) {
      service.value = { options: [], list: [] }
      if (!isPaidBooking.value) {
        resetServices()
      }
      return
    }

    fetchEmployeeServices(employee_id.value)
      .then((data) => {
        if (!isPaidBooking.value) {
          syncServicesForSelectedEmployee(data.list || [], employee_id.value)
        }

        return getSlots()
      })
      .then(() => restoreSlotIfAvailable(previousSlotValue))
  })
}

const getSlots = () => {
  if (!branch_id.value || !current_date.value || !employee_id.value) {
    slots.value = []
    IS_SLOTS_LOADING.value = false
    return Promise.resolve()
  }

  IS_SLOTS_LOADING.value = true
  return listingRequest({
    url: SLOT_LIST,
    data: {
      branch_id: branch_id.value,
      date: current_date.value,
      employee_id: employee_id.value,
      booking_id: id.value || null,
      service_duration: effectiveBookingDuration.value
    }
  }).then((res) => {
    if (res.status) {
      slots.value = res.data || []
      ensureCalendarPresetOptions()
    }
  }).finally(() => {
    IS_SLOTS_LOADING.value = false
  })
}

const setEmployeeOptions = (employees) => {
  employee.value = {
    options: employees.map((item) => ({ value: item.id, label: item.title || item.name })),
    list: employees.map((item) => ({
      ...item,
      name: item.title || item.name,
      full_name: item.title || item.name
    }))
  }
}

const loadAvailableEmployees = () => {
  if (!branch_id.value || !current_date.value) {
    setEmployeeOptions([])
    IS_EMPLOYEES_LOADING.value = false
    return Promise.resolve()
  }

  IS_EMPLOYEES_LOADING.value = true
  return listingRequest({
    url: INDEX_URL,
    data: {
      branch_id: branch_id.value,
      date: current_date.value,
      service_duration: effectiveBookingDuration.value,
      per_page: 1000
    }
  }).then((res) => {
   const allEmployees = res.employees || []

    setEmployeeOptions(allEmployees)

    if (employee_id.value && !allEmployees.some((item) => String(item.id) === String(employee_id.value))) {
      employee_id.value = null
      service.value = { options: [], list: [] }
      slots.value = []
      if (!isPaidBooking.value) {
        resetServices()
      }
    }

    ensureCalendarPresetOptions()
  }).finally(() => {
    IS_EMPLOYEES_LOADING.value = false
  })
}

const fetchEmployeeServices = (employeeValue) => {
  if (!employeeValue || !branch_id.value) {
    service.value = { options: [], list: [] }
    return Promise.resolve({ options: [], list: [] })
  }

  return useSelect({ url: SERVICE_LIST, data: { id: employeeValue, branch_id: branch_id.value } }, {
    value: 'service_id',
    label: 'service_name'
  }).then((data) => {
    service.value = data
    ensureCalendarPresetOptions()
    return data
  })
}

const hasSelectionValue = (value) => {
  if (Array.isArray(value)) {
    return value.length > 0
  }

  if (value && typeof value === 'object') {
    return value.value !== undefined || value.id !== undefined
  }

  return value !== null && value !== undefined && value !== ''
}

const branchSelect = (value, preserveSelection = false) => {
  branch_id.value = Number(value) > 0 ? value : null
  if (preserveSelection) {
    loadAvailableEmployees().then(() => getSlots())
  } else {
    employee_id.value = null
    start_date_time.value = null
    end_time_input.value = ''
    end_time_error.value = ''
    is_end_time_manual.value = false
    slots.value = []
    service.value = { options: [], list: [] }
    resetServices()
    loadAvailableEmployees()
  }
  if (value) {
    fetchHolidays(value)
  } else {
    holidays.value = []
  }
}

function fetchHolidays(value) {
  listingRequest({ url: IS_HOLIDAY, data: { branch_id: value } }).then((data) => {
    console.log(data)
    holidays.value = Object.values(data.isHoliday)
      .map((day) => {
        const parsedDate = new Date(day.date)
        return isNaN(parsedDate.getTime()) ? null : parsedDate
      })
      .filter(Boolean)
    console.log(holidays.value)
  })
}

const removeBranch = (value) => {
  if (hasSelectionValue(value)) {
    return
  }

  employee_id.value = null
  start_date_time.value = null
  end_time_input.value = ''
  end_time_error.value = ''
  is_end_time_manual.value = false
  slots.value = []
  service.value = { options: [], list: [] }
  setEmployeeOptions([])
  user_id.value = null
  resetServices()
}

const updateSelectedBookingItemsEmployee = (value) => {
  selectedService.value = selectedService.value.map((bookingService) => ({
    ...bookingService,
    employee_id: value,
    employee: selectedEmployee.value || bookingService.employee
  }))
  selectPurchasePackages.value = selectPurchasePackages.value.map((bookingPackage) => ({
    ...bookingPackage,
    employee_id: value
  }))
}

const employeeSelect = (value, preserveSelection = false) => {
  const previousSlotValue = start_date_time.value

  employee_id.value = value
  if (!preserveSelection) {
    start_date_time.value = null
    end_time_input.value = ''
    end_time_error.value = ''
    is_end_time_manual.value = false
    slots.value = []
  }
  if (isPaidBooking.value || id.value) {
    updateSelectedBookingItemsEmployee(value)
    if (start_date_time.value) {
      resetServiceTime()
    }
  }

  fetchEmployeeServices(value).then((data) => {
    if (!preserveSelection) {
      syncServicesForSelectedEmployee(data.list || [], value)
    }
    return getSlots()
  }).then(() => {
    if (preserveSelection) {
      ensureCalendarPresetOptions()
      return
    }

    restoreSlotIfAvailable(previousSlotValue)
  })
}

const removeEmployee = (value) => {
  if (hasSelectionValue(value)) {
    return
  }

  employee_id.value = null
  start_date_time.value = null
  end_time_input.value = ''
  end_time_error.value = ''
  is_end_time_manual.value = false
  slots.value = []
  service.value = { options: [], list: [] }
  resetServices()
}

const newCustomerData = ref(null)
const customerSelect = (value) => {
  if (_.isString(value)) {
    const trimmedValue = value.trim()
    const looksLikePhone = /^\+?[\d\s\-()]+$/.test(trimmedValue)
    newCustomerData.value = {
      id: null,
      first_name: looksLikePhone ? '' : trimmedValue.split(' ')[0] || '',
      last_name: looksLikePhone ? '' : trimmedValue.split(' ').slice(1).join(' '),
      email: '',
      mobile: looksLikePhone ? trimmedValue : '',
      gender: 'female'
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('create-customer-modal')).show()
    user_id.value = null
    return
  }

  getUserPackages(value)
}

const openCustomerEditModal = () => {
  if (!selectedCustomer.value?.id) {
    return
  }

  getRequest({ url: CUSTOMER_EDIT, id: selectedCustomer.value.id }).then((res) => {
    if (res.status && res.data) {
      newCustomerData.value = {
        id: res.data.id,
        first_name: res.data.first_name || '',
        last_name: res.data.last_name || '',
        email: res.data.email || '',
        mobile: res.data.mobile || '',
        gender: res.data.gender || 'female'
      }
      bootstrap.Modal.getOrCreateInstance(document.getElementById('create-customer-modal')).show()
      return
    }

    errorSnackbar(res.message || 'Unable to load customer data')
  })
}

const slotSelect = () => {
  resetServiceTime()
  syncEndTimeState()
}

const removeSlot = (value) => {
  if (hasSelectionValue(value)) {
    return
  }

  start_date_time.value = null
  end_time_input.value = ''
  end_time_error.value = ''
  is_end_time_manual.value = false
  resetServiceTime()
}

const selectedCustomer = computed(() => {
  const customerFromList = customer.value.list.find((customer) => customer.id == user_id.value)
  if (customerFromList) {
    return customerFromList
  }
  return String(selectedBookingCustomer.value?.id || '') === String(user_id.value || '') ? selectedBookingCustomer.value : null
})

const selectedEmployee = computed(() => employee.value.list.find((employee) => employee.id == employee_id.value) ?? null)

const removeCustomer = () => {
  user_id.value = null
  selectedBookingCustomer.value = null
  resetServices()
  userPackage.value = null
}

// ------------------- Service Module -------------------

const selectedService = ref([])
const newSelectedServices = ref([])
const editingServiceIndex = ref(null)
const editingServiceValue = ref(null)

const effectiveBookingDuration = computed(() =>
  selectedService.value.reduce((total, serviceItem) => total + Number(serviceItem.duration_min ?? 0), 0)
)

const getDefaultServiceDuration = (serviceItem) => {
  const defaultDuration = Number(serviceItem?.default_duration_min ?? serviceItem?.duration_min ?? 0)
  return defaultDuration > 0 ? defaultDuration : 30
}

const hydrateBookingService = (serviceItem) => {
  const defaultDuration = getDefaultServiceDuration(serviceItem)
  const currentDuration = Number(serviceItem?.duration_min ?? defaultDuration)

  return {
    ...serviceItem,
    duration_min: currentDuration > 0 ? currentDuration : defaultDuration,
    default_duration_min: defaultDuration,
    manual_duration_min: currentDuration > 0 && currentDuration !== defaultDuration ? String(currentDuration) : ''
  }
}

const parseCustomDuration = (value) => {
  const normalizedValue = String(value ?? '').trim()
  if (!normalizedValue) {
    return null
  }

  const parsedValue = Number.parseInt(normalizedValue, 10)
  return Number.isFinite(parsedValue) && parsedValue > 0 ? parsedValue : null
}

const serviceDurationPlaceholder = (serviceItem) => String(getDefaultServiceDuration(serviceItem))
const serviceDurationHint = (serviceItem) => `${getDefaultServiceDuration(serviceItem)} ${t('booking.lbl_minutes')}`

const replaceableServiceOptions = (currentService) => {
  const selectedIds = selectedService.value
    .map((serviceItem) => String(serviceItem.service_id))
    .filter((serviceId) => serviceId !== String(currentService.service_id))

  const options = service.value.options.filter((option) => !selectedIds.includes(String(option.value)))

  if (!options.some((option) => String(option.value) === String(currentService.service_id))) {
    options.unshift({
      value: currentService.service_id,
      label: currentService.service_name
    })
  }

  return options
}

const toggleServiceEditor = (index, currentServiceId) => {
  if (editingServiceIndex.value === index) {
    editingServiceIndex.value = null
    editingServiceValue.value = null
    return
  }

  editingServiceIndex.value = index
  editingServiceValue.value = currentServiceId
}

const syncReplacedNewService = (oldService, updatedService) => {
  const newServiceIndex = newSelectedServices.value.findIndex((serviceItem) => {
    if (oldService.id && serviceItem.id) {
      return String(serviceItem.id) === String(oldService.id)
    }

    return String(serviceItem.service_id) === String(oldService.service_id)
  })

  if (newServiceIndex !== -1) {
    newSelectedServices.value[newServiceIndex] = {
      ...newSelectedServices.value[newServiceIndex],
      ...updatedService
    }
  }
}

const replaceService = (index, value) => {
  const nextServiceId = value?.value ?? value
  const currentService = selectedService.value[index]

  if (!currentService || !nextServiceId) {
    return
  }

  const selectedServiceOption = service.value.list.find((serviceItem) => String(serviceItem.service_id) === String(nextServiceId))

  if (!selectedServiceOption) {
    return
  }

  const oldServiceId = currentService.service_id
  const updatedService = hydrateBookingService({
    ...currentService,
    service_id: selectedServiceOption.service_id,
    service_name: selectedServiceOption.service_name,
    service_price: selectedServiceOption.service_price,
    duration_min: selectedServiceOption.duration_min,
    employee_id: employee_id.value,
    branch_id: branch_id.value
  })

  selectedService.value[index] = updatedService
  services_id.value = selectedService.value.map((serviceItem) => serviceItem.service_id)
  syncReplacedNewService(currentService, updatedService)
  selectedPackageService.value = selectedPackageService.value.filter((serviceItem) => String(serviceItem.service_id) !== String(oldServiceId))
  tempSelectedPackageService.value = tempSelectedPackageService.value.filter((serviceItem) => String(serviceItem.service_id) !== String(oldServiceId))
  appliedServices.value = appliedServices.value.filter((serviceItem) => String(serviceItem.id) !== String(oldServiceId))
  editingServiceIndex.value = null
  editingServiceValue.value = null
  resetServiceTime()
  syncEndTimeState()

  getSlots().then(() => {
    if (start_date_time.value && !slots.value.some((slot) => String(slot.value) === String(start_date_time.value)) && !canKeepSelectedUnavailableSlot()) {
      start_date_time.value = null
      resetServiceTime()
    } else if (start_date_time.value) {
      ensureSelectedSlotOption()
    }
  })
}

const refreshSlotAvailabilityForServices = async () => {
  if (!branch_id.value || !current_date.value || !employee_id.value) {
    return
  }

  const currentSelectedSlot = start_date_time.value
  await getSlots()

  if (currentSelectedSlot && !slots.value.some((slot) => String(slot.value) === String(currentSelectedSlot)) && !canKeepSelectedUnavailableSlot()) {
    start_date_time.value = null
    resetServiceTime()
  } else if (currentSelectedSlot) {
    ensureSelectedSlotOption()
  }
}

const applyServiceDuration = async (serviceItem) => {
  const parsedDuration = parseCustomDuration(serviceItem.manual_duration_min)
  serviceItem.manual_duration_min = parsedDuration === null ? '' : String(parsedDuration)
  serviceItem.duration_min = parsedDuration === null ? getDefaultServiceDuration(serviceItem) : parsedDuration

  resetServiceTime()
  await refreshSlotAvailabilityForServices()
}

const buildEndMomentFromInput = () => null

const syncEndTimeState = () => {
  end_time_error.value = ''
}

const applyManualEndTimeToServices = () => {
  if (!start_date_time.value || !moment(start_date_time.value).isValid() || selectedService.value.length === 0) {
    end_time_error.value = ''
    return
  }

  const startMoment = moment(start_date_time.value)
  const endMoment = buildEndMomentFromInput()

  if (!endMoment) {
    syncEndTimeState({ preserveManual: false })
    return
  }

  const totalDuration = endMoment.diff(startMoment, 'minutes')
  const fixedDurationBeforeLast = selectedService.value.slice(0, -1).reduce((total, serviceItem) => total + Number(serviceItem.default_duration_min ?? serviceItem.duration_min ?? 0), 0)

  if (totalDuration <= 0) {
    end_time_error.value = 'وقت النهاية يجب أن يكون بعد وقت البداية.'
    return
  }

  if (selectedService.value.length > 1 && totalDuration <= fixedDurationBeforeLast) {
    end_time_error.value = 'وقت النهاية لا يكفي لتنفيذ الخدمات المحددة.'
    return
  }

  end_time_error.value = ''
  selectedService.value = selectedService.value.map((serviceItem, index) => {
    const baseDuration = Number(serviceItem.default_duration_min ?? serviceItem.duration_min ?? 0)
    if (index < selectedService.value.length - 1) {
      return {
        ...serviceItem,
        duration_min: baseDuration
      }
    }

    return {
      ...serviceItem,
      duration_min: selectedService.value.length === 1 ? totalDuration : totalDuration - fixedDurationBeforeLast
    }
  })
  resetServiceTime()
}

const onEndTimeInput = () => {
  if (!end_time_input.value) {
    is_end_time_manual.value = false
    syncEndTimeState({ preserveManual: false })
    getSlots()
    return
  }

  is_end_time_manual.value = true
  applyManualEndTimeToServices()
  if (!end_time_error.value) {
    getSlots()
  }
}

const resetServices = () => {
  selectedService.value = []
  services_id.value = []
  newSelectedServices.value = []
  editingServiceIndex.value = null
  editingServiceValue.value = null
  end_time_input.value = ''
  end_time_error.value = ''
  is_end_time_manual.value = false
}

const syncServicesForSelectedEmployee = (availableServices, nextEmployeeId) => {
  const availableServiceIds = availableServices.map((serviceItem) => String(serviceItem.service_id))

  selectedService.value = selectedService.value
    .filter((bookingService) => availableServiceIds.includes(String(bookingService.service_id)))
    .map((bookingService) => ({
      ...bookingService,
      employee_id: nextEmployeeId,
      employee: selectedEmployee.value || bookingService.employee
    }))

  newSelectedServices.value = newSelectedServices.value
    .filter((bookingService) => availableServiceIds.includes(String(bookingService.service_id)))
    .map((bookingService) => ({
      ...bookingService,
      employee_id: nextEmployeeId,
      employee: selectedEmployee.value || bookingService.employee
    }))

  services_id.value = services_id.value.filter((serviceId) => availableServiceIds.includes(String(serviceId)))

  resetServiceTime()
  syncEndTimeState()
}

const removeService = (id) => {
  const servicesIds = services_id.value
  services_id.value = servicesIds.filter((serviceid) => serviceid !== id)
  selectedService.value = selectedService.value.filter((BKservice) => BKservice.service_id !== id)
  newSelectedServices.value = newSelectedServices.value.filter((BKservice) => BKservice.service_id !== id)
  editingServiceIndex.value = null
  editingServiceValue.value = null
  resetServiceTime()
  syncEndTimeState()
  getSlots()
}

const newService = ref(false)
const serviceInput = ref(null)

const addNewService = (value) => {
  newService.value = true
  setTimeout(() => {
    serviceInput.value.open()
  }, 100)
}

const serviceSelect = (value) => {
  const filteredService = service.value.list.find((ser) => ser.service_id == value)
  const bookingService = hydrateBookingService({
    id: null,
    start_date_time: null,
    service_name: filteredService.service_name,
    employee_id: employee_id.value,
    booking_id: null,
    service_id: value,
    branch_id: branch_id.value,
    service_price: filteredService.service_price,
    duration_min: filteredService.duration_min
  })
  selectedService.value.push(bookingService)
  newSelectedServices.value.push(bookingService)
  resetServiceTime()
  syncEndTimeState()
  getSlots().then(() => {
    if (start_date_time.value && !slots.value.some((slot) => String(slot.value) === String(start_date_time.value)) && !canKeepSelectedUnavailableSlot()) {
      start_date_time.value = null
      resetServiceTime()
    } else if (start_date_time.value) {
      ensureSelectedSlotOption()
    }
  })
  newService.value = false
}

const resetServiceTime = () => {
  if (!start_date_time.value || !moment(start_date_time.value).isValid()) {
    selectedService.value = selectedService.value.map((bookingService) => ({
      ...bookingService,
      start_date_time: null
    }))
    return
  }
  let startTime = moment(start_date_time.value)
  selectedService.value.forEach((bookingService, index) => {
    if (index > 0) {
      const lastService = selectedService.value[index - 1]
      startTime = moment(lastService.start_date_time)
      startTime = startTime.add(lastService.duration_min, 'minutes')
    }
    bookingService.start_date_time = startTime.format('YYYY-MM-DD HH:mm:ss')
    selectedService.value[index] = bookingService
  })
}

// ------------------- Package Module -------------------

const packages = ref({ options: [], list: [] })
const selectedPackage = ref([])

const resetPackage = () => {
  selectedPackage.value = []
  package_id.value = []
}

const newPackage = ref(false)
const packageInput = ref(null)

const addNewPackage = (value) => {
  newPackage.value = true
  setTimeout(() => {
    packageInput.value.open()
  }, 100)
}

const getpackages = (branch_id) => {
  useSelect({ url: PACKAGE_LIST, data: { branch_id: branch_id } }, { value: 'id', label: 'name' }).then((data) => {
    packages.value = data
  })
}

const getUserPackages = (user_id) => {
  getRequest({ url: USER_PACKAGE_LIST, id: user_id }).then((data) => {
    if (branch_id.value) {
      userPackage.value = data.filter((pkg) => pkg.branch_id === branch_id.value)
    } else {
      userPackage.value = data
    }
  })
}

// ------------------- Product Module -------------------

const newProduct = ref(false)
const productInput = ref(null)

const addNewProduct = (value) => {
  newProduct.value = true
  setTimeout(() => {
    productInput.value.open()
  }, 100)
}

const resetProducts = () => {
  selectedProduct.value = []
  product_variation_id.value = []
}

const products = ref({ options: [], list: [] })

const getProducts = () => {
  useSelect({ url: PRODUCT_LIST }, { value: 'id', label: 'text' }).then((data) => {
    products.value = data
  })
}

const selectedProduct = ref([])

const selectProduct = (value) => {
  const filteredProduct = products.value.list.find((pr) => pr.id == value)
  const product_variation = JSON.parse(filteredProduct.extra_data)
  const bookingProduct = {
    id: null,
    product_name: filteredProduct.text,
    booking_id: id.value,
    employee_id: employee_id.value,
    order_id: null,
    product_id: product_variation.product_id,
    product_variation_id: value,
    product_price: product_variation.price,
    discounted_price: product_variation.discounted_price,
    discount_type: product_variation.discount_type,
    discount_value: product_variation.discount_value,
    product_qty: 1,
    variation_name: product_variation.variation_name,
    max_qty: product_variation.qty
  }
  selectedProduct.value.push(bookingProduct)
  newProduct.value = false
}

const removeProduct = (id) => {
  const productsIds = product_variation_id.value
  product_variation_id.value = productsIds.filter((productid) => productid !== id)
  selectedProduct.value = selectedProduct.value.filter((BKproduct) => BKproduct.product_variation_id !== id)
}

// ------------------- Payment & Submit -------------------

const payment_data = ref(null)
const stripe_payment_data = ref(null)
const SINLGE_STEP = computed(() => store.singleStep)

var SUB_TOTAL_SERVICE_AMOUNT = computed(() =>
  selectedService.value.reduce((total, service) => total + service.service_price, 0) +
  selectPurchasePackages.value.reduce((total, PurchasePackage) => total + PurchasePackage.package_price, 0) +
  selectedProduct.value.reduce((total, product) => total + (product.discounted_price ? product.discounted_price : product.product_price) * product.product_qty, 0) +
  selectedPackage.value.reduce((total, packages) => total + packages.package_price, 0) -
  (couponRedeem.value || 0)
)

const canSubmitBooking = computed(() => {
  const hasSelectedServices = selectedService.value.length > 0 || services_id.value.length > 0
  const hasSelectedPackages = selectPurchasePackages.value.length > 0 && status.value !== 'cancelled'
  const hasSchedule = Boolean(branch_id.value && employee_id.value && start_date_time.value)
  const isExistingPaidBooking = Boolean(id.value && isPaidBooking.value)
  return (
    canSaveBooking.value &&
    !end_time_error.value &&
    hasSchedule &&
    (isExistingPaidBooking || hasSelectedServices || hasSelectedPackages)
  )
})

const formSubmit = handleSubmit((values) => {
  if (!IS_SUBMITED.value) {
    IS_SUBMITED.value = true
    values['start_date_time'] = start_date_time.value
    values['employee_id'] = employee_id.value
    values['branch_id'] = branch_id.value
    values['user_id'] = user_id.value
    values['services_id'] = services_id.value
    values['services'] = selectedService.value
    values['products'] = selectedProduct.value
    values['packages'] = selectedPackage.value
    values['purchase_packages'] = selectPurchasePackages.value
    if (id.value > 0) {
      updateRequest({ url: UPDATE_URL, id: id.value, body: values }).then((res) => {
        submiting_booking(res)
      })
    } else {
      storeRequest({ url: STORE_URL, body: values }).then((res) => {
        submiting_booking(res)
      })
    }
  }
})

const updateStatus = (data) => {
  setFormData(data)
  emit('onSubmit', {
    employee_id: employee_id.value,
    start_date_time: start_date_time.value,
    branch_id: branch_id.value,
    booking_id: id.value
  })
}

const submiting_booking = (res) => {
  IS_SUBMITED.value = false
  const submittedBooking = {
    employee_id: res?.data?.employee_id ?? employee_id.value,
    start_date_time: res?.data?.start_date_time ?? start_date_time.value,
    branch_id: res?.data?.branch_id ?? branch_id.value,
    booking_id: res?.data?.id ?? id.value
  }

  if (res.status) {
    window.successSnackbar(res.message)
    if (props.bookingType == 'CALENDER_BOOKING') {
      setFormData(res.data)
      const elem = document.getElementById('booking-form')
      const form = bootstrap.Offcanvas.getOrCreateInstance(elem)
      form.hide()
    } else {
      setFormData(defaultData())
      const elem = document.getElementById('booking-form')
      const form = bootstrap.Offcanvas.getOrCreateInstance(elem)
      form.hide()
      if (document.getElementById('booking-datatable') != null) {
        window.renderedDataTable.ajax.reload(null, false)
      }
    }
  } else {
    window.errorSnackbar(res.message)
  }
  emit('onSubmit', submittedBooking)
}

const formSubmitCheckout = () => {
  if (!IS_SUBMITED.value) {
    const values = {
      services: selectedService.value,
      products: selectedProduct.value,
      packages: selectedPackage.value,
      packageApplied: selectedPackageService.value,
      purchase_package: selectPurchasePackages.value
    }

    IS_SUBMITED.value = true
    if (is_paid.value) {
      const data = { status: 'completed' }
      updateRequest({ url: UPDATE_STATUS, id: id.value, body: data }).then((res) => {
        if (res.status) {
          store.updateStep('MAIN')
          window.successSnackbar(res.message)
          updateStatus(res.data)
        }
      })
    } else {
      updateRequest({ url: CHECKOUT_URL, id: id.value, body: values }).then((res) => {
        if (res.status) {
          setFormData(res.data)
          submiting_booking(res)
          store.updateStep('PAYMENT')
        }
      })
    }
  }
}

const updatePaymentData = (data) => {
  payment_data.value = data
}

const formSubmitPaynow = () => {
  if (!IS_SUBMITED.value) {
    const values = { payment: payment_data.value, packageApplied: appliedServices.value }
    IS_SUBMITED.value = true
    updateRequest({ url: PAYMENT_PUT_URL, id: id.value, body: payment_data.value }).then((res) => {
      packageQtyReduce()

      switch (res.data.payment_method) {
        case 'razorpay':
          if (res.data.public_key != '') {
            openRazorpay(res.data)
          } else {
            window.errorSnackbar(window.i18n.global.t("Razorpay key does not exist"))
            errorMessages.value = window.i18n.global.t("Razorpay key does not exist")
          }
          break

        case 'stripe':
          stripe_payment_data.value = {
            booking_transaction_id: res.data.booking_transaction_id,
            currency: res.data.currency,
            payment_method: res.data.payment_method,
            total_amount: res.data.total_amount
          }

          if (res.data.public_key != '') {
            openStripe(stripe_payment_data.value)
          } else {
            window.errorSnackbar(window.i18n.global.t("Stripe Secret key does not exist"))
            errorMessages.value = window.i18n.global.t("Stripe Secret key does not exist")
          }
          break

        default:
          submiting_booking(res.data)
          setFormData(res.data.data)
          store.updateStep('MAIN')
          break
      }
    })
  }
}

const openRazorpay = (data) => {
  var options = {
    key: data.public_key,
    amount: data.total_amount * 100,
    currency: data.currency,
    name: 'Acme Corp',
    description: 'Test Transaction',
    image: 'https://example.com/your_logo',
    handler: function(response) {
      response.razorpay_payment_id = response.razorpay_payment_id
      response.total_amount = data.total_amount
      response.currency = data.currency

      updateRequest({ url: UPDATE_PAYMENT_DATA, id: data.booking_transaction_id, body: { response } }).then((res) => {
        submiting_booking(res.data)
        setFormData(res.data.booking)
        store.updateStep('MAIN')
      })
    },
    notes: { address: 'Razorpay Corporate Office' },
    theme: { color: '#3399cc' }
  }
  var rzp1 = new Razorpay(options)
  rzp1.on('payment.failed', function(response) {
    window.errorSnackbar(response.error.description)
    errorMessages.value = response.error.description
  })
  rzp1.open()
}

const openStripe = (data) => {
  storeRequest({ url: STRIPE_PAYMENT_DATA, body: { data } }).then((res) => {
    if (res.status == true) {
      var newWindow = window.open(res.data_url, '_blank')
    } else {
      window.errorSnackbar(res.data.message)
      errorMessages.value = res.data.message
    }
  })
}

// ------------------- Package Logic -------------------

const packageApplied = ref(false)
const appliedServices = ref([])

var GRAND_TOTAL = computed(() =>
  newSelectedServices.value.reduce((total, service) => total + service.service_price, 0) +
  selectedProduct.value.reduce((total, product) => total + (product.discounted_price ? product.discounted_price : product.product_price) * product.product_qty, 0) +
  selectedPackage.value.reduce((total, packages) => total + packages.package_price, 0)
)

const packageQtyReduce = () => {
  for (let singlePackage of packages.value.list) {
    for (let packageService of singlePackage.services) {
      for (let appliedService of appliedServices.value)
        if (packageService.service_id === appliedService.id && singlePackage.id === appliedService.package_id) {
          packageService.quantity = packageService.quantity - 1
        }
    }
  }
  appliedServices.value = []
}

const packageCounts = computed(() => {
  const counts = {}
  for (let singlePackage of packages.value.list) {
    for (let packageService of singlePackage.services) {
      if (packageService.quantity > 0) {
        const serviceId = packageService.service_id
        counts[serviceId] = counts[serviceId] ? counts[serviceId] + 1 : 1
      }
    }
  }
  return counts
})

const singleService = ref(null)
const openModal = (service) => {
  singleService.value = service
  $('#exampleModal').modal('show')
}

const singleAppliedServicePrice = ref(null)

function singleAppliedService(service_id) {
  for (let singleServices of appliedServices.value) {
    if (service_id === singleServices.id) {
      singleAppliedServicePrice.value = singleServices.price
      return true
    }
  }
  return false
}

const selectPurchasePackages = ref([])
const selectPurchasePackageIds = ref([])

function purchasePackageModel() {
  $('#purchasePackageModel').modal('show')
  selectPurchasePackages.value.forEach((packages) => {
    selectPurchasePackageIds.value.push(packages.package_id)
  })
}

const isPackagePurchased = (packageId) => {
  return selectPurchasePackageIds.value.some((p) => p === packageId)
}

function purchasePackage(packagesId) {
  selectPurchasePackageIds.value = [packagesId]
}

const resetPurchasePackage = () => {
  selectPurchasePackages.value = []
}

function removePurchasePackageId(package_id) {
  let removePackageId = selectPurchasePackageIds.value.some((p) => p === package_id)
  if (removePackageId) {
    removePurchasePackage(package_id)
  }
  selectPurchasePackageIds.value = selectPurchasePackageIds.value.filter((packageId) => packageId !== package_id)
}

function removePurchasePackage(package_id) {
  selectPurchasePackages.value = selectPurchasePackages.value.filter((BKservice) => BKservice.package_id !== package_id)
}

function savePurchasePackage() {
  selectPurchasePackageIds.value.forEach((packagesId) => {
    const filteredPackage = packages.value.list.find((pa) => pa.id == packagesId)
    const packageExists = selectPurchasePackages.value.some((packages) => packages.package_id === packagesId)

    if (!packageExists) {
      const bookingPackage = {
        id: null,
        name: filteredPackage.name,
        description: filteredPackage.description,
        user_id: user_id.value,
        employee_id: employee_id.value,
        booking_id: null,
        package_id: packagesId,
        package_price: filteredPackage.package_price,
        package_validity: filteredPackage.package_validity,
        services: filteredPackage.services,
        start_date: filteredPackage.start_date,
        end_date: filteredPackage.end_date,
        is_reclaim: false
      }
      selectPurchasePackages.value.push(bookingPackage)
      resetServices()
    }
  })
  $('#purchasePackageModel').modal('hide')
  selectPurchasePackageIds.value = []
}

function closeModel() {
  $('#purchasePackageModel').modal('hide')
  selectPurchasePackageIds.value = []
}

function openUserPackage() {
  $('#userPackage').modal('show')
}

const filteredUserPackages = computed(() => {
  if (userPackage.value && userPackage.value.length > 0) {
    return userPackage.value.filter(
      (packages) =>
        packages.services &&
        packages.services.some((service) =>
          selectedService.value.some((selected) => {
            if (selected.service_id === service.service_id) {
              service.employee_id = selected.employee_id
              return true
            }
            return false
          })
        )
    )
  }
  return []
})

function applyUserPackage() {
  $('#applyUserPackage').modal('show')
}

function isUserPackage(packageId) {
  if (!userPackage.value || userPackage.value.length === 0) {
    return false
  }
  return userPackage.value.some((userPackage) => userPackage.package_id === packageId)
}

const filteredPackages = computed(() => {
  const selectedServiceIds = selectedService.value.map((service) => service.service_id)
  if (selectedServiceIds.length === 0) {
    return packages.value.list.filter((pkg) => !isUserPackage(pkg.id))
  }
  const filtered = packages.value.list.filter(
    (pkg) => !isUserPackage(pkg.id) && pkg.services && pkg.services.some((service) => selectedServiceIds.includes(service.service_id))
  )
  return filtered.length > 0 ? filtered : []
})

function isServiceSelected(userPackageId, serviceId) {
  return tempSelectedPackageService.value.some((service) => service.user_package_id === userPackageId && service.service_id === serviceId)
}

function updateSelectedServices(packageServices) {
  const exactMatchIndex = tempSelectedPackageService.value.findIndex(
    (service) => service.user_package_id === packageServices.user_package_id && service.service_id === packageServices.service_id
  )
  if (exactMatchIndex !== -1) {
    tempSelectedPackageService.value.splice(exactMatchIndex, 1)
  } else {
    const serviceIdMatchIndex = tempSelectedPackageService.value.findIndex((service) => service.service_id === packageServices.service_id)
    if (serviceIdMatchIndex !== -1) {
      tempSelectedPackageService.value.splice(serviceIdMatchIndex, 1)
    }
    tempSelectedPackageService.value.push({
      ...packageServices,
      qty: packageServices.qty ?? 1
    })
  }
}

function updateServiceQty(index, packageServices, newQty) {
  const service = tempSelectedPackageService.value[index]
  if (service) {
    service.qty = newQty
  }
}

const isPackageServiceSelected = computed(() => {
  return (serviceId) => {
    return selectedPackageService.value.some((service) => service.service_id === serviceId)
  }
})

const PackageServiceSelectedPrice = computed(() => {
  return selectedPackageService.value.reduce(
    (total, packageService) =>
      total + (selectedService.value.some((service) => service.service_id === packageService.service_id) ? packageService.service_price : 0),
    0
  )
})

function removeApplyPackageService(service_id) {
  selectedPackageService.value = selectedPackageService.value.filter((service) => service.service_id !== service_id)
}

function saveSelectedPackageService() {
  tempSelectedPackageService.value.forEach((packageService) => {
    const exists = selectedPackageService.value.some((service) => service.id === packageService.id)
    if (!exists) {
      selectedPackageService.value.push(packageService)
    }
  })
  tempSelectedPackageService.value = []
  $('#applyUserPackage').modal('hide')
}

function cancelSelectedPackageService() {
  tempSelectedPackageService.value = []
  $('#applyUserPackage').modal('hide')
}

function displayPackageDuration(startDate, endDate) {
  const start = new Date(startDate)
  const end = new Date(endDate)

  const diffInMonths = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth())
  const diffInDays = Math.floor((end - start) / (1000 * 60 * 60 * 24))
  let diffInHours = Math.floor((end - start) / (1000 * 60 * 60))
  if (diffInDays === 0) {
    const endOfDay = new Date(start)
    endOfDay.setHours(23, 59, 59, 999)
    diffInHours = Math.floor((endOfDay - start) / (1000 * 60 * 60)) + 1
  }
  if (diffInMonths > 0) {
    return `${diffInMonths} Mo`
  } else if (diffInDays > 0) {
    return `${diffInDays} Days`
  } else {
    return `${diffInHours} Hours`
  }
}

function redeemPackage(package_id) {
  if (userPackage.value || userPackage.value.length > 0) {
    const userPackagedata = userPackage.value.find((pa) => pa.package_id == package_id)
    selectPurchasePackages.value = []
    const bookingPackage = {
      id: null,
      name: userPackagedata.name,
      description: userPackagedata.description,
      user_id: user_id.value,
      employee_id: employee_id.value,
      booking_id: null,
      package_id: package_id,
      package_price: 0,
      package_validity: userPackagedata.package_validity,
      services: userPackagedata.services,
      start_date: userPackagedata.start_date,
      end_date: userPackagedata.end_date,
      is_reclaim: true
    }
    selectPurchasePackages.value.push(bookingPackage)
    $('#userPackage').modal('hide')
  }
}

watch(filteredUserPackages, () => {
  shownServiceIds.value = []
})

const uniqueServiceIds = computed(() => {
  const serviceIds = new Set()
  filteredUserPackages.value.forEach((filteredPackage) => {
    if (filteredPackage.services) {
      filteredPackage.services.forEach((serviceItem) => {
        if (serviceItem.service_id) {
          serviceIds.add(serviceItem.service_id)
        }
      })
    }
  })
  return Array.from(serviceIds)
})

const isServiceInFilteredPackages = (serviceId) => {
  return uniqueServiceIds.value.includes(serviceId)
}
</script>

<style scoped>
.offcanvas {
  box-shadow: none;
}

.booking-loader {
  min-height: 220px;
  padding: 32px 24px;
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: center;
  color: var(--bs-body-color);
}

.booking-payment-status {
  align-items: center;
  border: 1px solid var(--bs-border-color);
  border-radius: 6px;
  display: flex;
  justify-content: space-between;
  padding: 10px 12px;
}

.booking-payment-status span {
  color: var(--bs-secondary-color);
  font-size: 0.84rem;
  font-weight: 700;
}

.booking-payment-status strong {
  border-radius: 999px;
  font-size: 0.82rem;
  padding: 4px 10px;
}

.booking-payment-status.is-paid strong {
  background: rgba(25, 135, 84, 0.12);
  color: #198754;
}

.booking-payment-status.is-unpaid strong {
  background: rgba(220, 53, 69, 0.12);
  color: #dc3545;
}

.booking-field-wrap {
  position: relative;
}

.booking-field-loader {
  align-items: center;
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 4px;
  bottom: -26px;
  color: var(--bs-secondary-color);
  display: inline-flex;
  font-size: 0.78rem;
  gap: 6px;
  inset-inline-end: 0;
  padding: 3px 8px;
  position: absolute;
  z-index: 2;
}

.service-duration {
  position: absolute;
  bottom: -16px;
  border-radius: 0;
  border-bottom-left-radius: 4px;
  border-bottom-right-radius: 4px;
  right: 0;
}

.border-br-radius-0 {
  border-bottom-right-radius: 0;
}

[dir='rtl'] .border-br-radius-0 {
  border-bottom-left-radius: 0;
}

.date-time {
  border-top: 1px solid var(--bs-border-color);
}

.date-time > div:not(:first-child) {
  border-left: 1px solid var(--bs-border-color);
}

.list-group-flush > .list-group-item {
  color: var(--bs-body-color);
}

.text-muted {
  text-decoration: line-through;
}

.iq-package {
  cursor: pointer;
}

.iq-card-package {
  background-color: #fcf2e3 !important;
}

.dark .iq-card-package {
  background-color: #2e2c2c !important;
}

.iq-package-list {
  .list-group-item {
    background-color: unset;
  }
}

.iq-purchase-package {
  height: 250px;
  overflow: auto;
}

.iq-purchase-package::-webkit-scrollbar {
  width: 8px;
}

.iq-purchase-package::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.iq-purchase-package::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.iq-purchase-package::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
