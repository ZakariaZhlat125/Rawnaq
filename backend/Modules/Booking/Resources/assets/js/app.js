// import "./calender"
import { InitApp } from '@/helpers/main'
import { createPinia } from 'pinia'

import CalendarView from './components/CalendarView.vue'
import BookingForm from './components/BookingForm.vue'
import HomeBookingForm from './components/HomeBookingForm.vue'

const pinia = createPinia()
const app = InitApp()

app.component('booking-form' , BookingForm)
app.component('home-booking-form' , HomeBookingForm)
app.component('calendar-view' , CalendarView)
app.use(pinia)
app.mount('[data-render="app"]');
