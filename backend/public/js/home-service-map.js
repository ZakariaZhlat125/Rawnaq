(function () {
  let pending;
  function load(key) {
    if (!key || !key.trim()) return Promise.reject(new Error('أضف مفتاح Google Maps من إعدادات التكامل لتفعيل الخريطة.'));
    if (window.google?.maps?.Map) return Promise.resolve(window.google.maps);
    if (pending) return pending;
    pending = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      const timer = setTimeout(() => fail('تعذر تحميل الخريطة. تحقق من الاتصال ومفتاح Google Maps.'), 20000);
      function fail(message) { window.dispatchEvent(new CustomEvent('sami-map-error', {detail:message})); clearTimeout(timer); pending = null; script.remove(); reject(new Error(message)); }
      window.gm_authFailure = () => fail('تعذر تفعيل الخريطة. تحقق من صلاحية المفتاح وتفعيل Maps JavaScript API والسماح بنطاق الموقع.');
      window.initSamiHomeMaps = () => { clearTimeout(timer); resolve(window.google.maps); };
      script.onerror = () => fail('تعذر الاتصال بخرائط جوجل. حاول مرة أخرى.');
      script.src = 'https://maps.googleapis.com/maps/api/js?' + new URLSearchParams({key: key.trim(), callback: 'initSamiHomeMaps', loading: 'async', language: 'ar', region: 'SA', v: 'weekly'});
      script.async = true;
      document.head.appendChild(script);
    });
    return pending;
  }
  window.SamiHomeMap = {
    async create(element, options) {
      const maps = await load(options.key);
      if (!element.isConnected) return null;
      const center = {lat: Number(options.latitude), lng: Number(options.longitude)};
      const map = new maps.Map(element, {center, zoom: 10, streetViewControl: false, mapTypeControl: false, fullscreenControl: true});
      const coverage = new maps.Circle({map, center, radius: Number(options.radiusKm) * 1000, editable: !!options.editable, draggable: !!options.editable, fillColor: '#ba9653', fillOpacity: 0.18, strokeColor: '#99752e', strokeWeight: 2});
      map.fitBounds(coverage.getBounds());
      let updating = false;
      let pin;
      const listeners = [];
      if (options.editable) {
        const sync = () => { if (!updating) options.onChange?.({latitude: coverage.getCenter().lat(), longitude: coverage.getCenter().lng(), radiusKm: coverage.getRadius()/1000}); };
        listeners.push(coverage.addListener('center_changed', sync), coverage.addListener('radius_changed', sync), map.addListener('click', e => coverage.setCenter(e.latLng)));
      } else {
        pin = new maps.Circle({map, radius: 35, strokeColor: '#174a76', fillColor: '#2786ce', fillOpacity: 1, strokeWeight: 3, draggable: true});
        const select = position => { pin.setCenter(position); options.onSelect?.({latitude: position.lat(), longitude: position.lng()}); };
        listeners.push(map.addListener('click', e => select(e.latLng)), pin.addListener('dragend', () => select(pin.getCenter())));
      }
      return {
        update(values) {
          updating = true;
          coverage.setCenter({lat: Number(values.latitude), lng: Number(values.longitude)});
          coverage.setRadius(Number(values.radiusKm)*1000);
          updating = false;
        },
        select(latitude, longitude) {
          if (!pin || latitude === '' || longitude === '') return;
          const position = {lat: Number(latitude), lng: Number(longitude)};
          if (!Number.isFinite(position.lat) || !Number.isFinite(position.lng) || Math.abs(position.lat)>90 || Math.abs(position.lng)>180) return;
          pin.setCenter(position);
          map.panTo(position);
        },
        destroy() { listeners.forEach(listener => listener.remove()); coverage.setMap(null); pin?.setMap(null); }
      };
    }
  };
})();
