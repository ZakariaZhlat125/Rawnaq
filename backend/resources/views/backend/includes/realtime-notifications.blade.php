@auth
<script type="module">
import { mountRealtime, notificationSound } from @json(asset('js/realtime-notifications.js').'?v=20261024-1');
window.SamiRealtimeSound = notificationSound;
const label = document.createElement('li');
label.className = 'nav-item';
const button = document.createElement('button');
button.type = 'button'; button.className = 'btn btn-sm btn-link';
const updateLabel = () => {
    const off = notificationSound.muted();
    const en = document.documentElement.lang.startsWith('en');
    button.textContent = off ? '🔇' : '🔊';
    button.title = off ? (en ? 'Enable notification sound' : 'تفعيل صوت الإشعارات') : (en ? 'Mute notification sound' : 'كتم صوت الإشعارات');
    button.setAttribute('aria-label',button.title); button.setAttribute('aria-pressed',String(!off));
};
button.onclick = () => { notificationSound.toggle(); updateLabel(); };
updateLabel(); label.append(button);
document.querySelector('.notification_list')?.closest('li')?.insertAdjacentElement('afterend', label);
const live = mountRealtime({
    configUrl: @json(route('backend.realtime.config')),
    headers: () => ({ 'X-CSRF-TOKEN': @json(csrf_token()) }),
    onEvent: id => {
        if (id) notificationSound.play(id);
        const dropdown = document.querySelector('.notification_data')?.closest('.dropdown-menu');
        if (dropdown?.classList.contains('show')) window.notificationList?.();
        else window.getNotificationCounts?.();
    },
});
window.addEventListener('pagehide', () => live.destroy(), { once: true });
</script>
@endauth
