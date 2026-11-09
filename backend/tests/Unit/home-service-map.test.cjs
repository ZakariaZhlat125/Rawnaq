const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const assert = require('node:assert/strict');
const source = readFileSync(require('node:path').join(__dirname, '../../public/js/home-service-map.js'), 'utf8');
const circles = [];
class MapStub {
  fitBounds() {}
  panTo() {}
  addListener() { return { remove() {} }; }
}
class CircleStub {
  constructor(options) { Object.assign(this, options); this.events = {}; circles.push(this); }
  getCenter() { return { lat: () => this.center.lat, lng: () => this.center.lng }; }
  getRadius() { return this.radius; }
  getBounds() { return {}; }
  setCenter(center) { this.center = center; this.events.center_changed?.(); }
  setRadius(radius) { this.radius = radius; this.events.radius_changed?.(); }
  setMap(map) { this.map = map; }
  addListener(name, callback) { this.events[name] = callback; return { remove: () => delete this.events[name] }; }
}
const window = { google: { maps: { Map: MapStub, Circle: CircleStub } } };
runInNewContext(source, { window });
(async () => {
  await assert.rejects(window.SamiHomeMap.create({}, { key: '' }));
  let changes = 0;
  const widget = await window.SamiHomeMap.create({ isConnected: true }, {
    key: 'test', latitude: 21, longitude: 39, radiusKm: 50, editable: true, onChange: () => changes++,
  });
  widget.update({ latitude: 22, longitude: 40, radiusKm: 70 });
  assert.equal(circles[0].getRadius(), 70000);
  assert.equal(changes, 0, 'Updating fields must not overwrite other pending field values');
  circles[0].setRadius(80000);
  assert.equal(changes, 1, 'Dragging coverage must update form fields');
  widget.destroy();
  assert.equal(circles[0].map, null);
  assert.equal(Object.keys(circles[0].events).length, 0);
  console.log('Home map checks passed.');
})().catch(error => { console.error(error); process.exitCode = 1; });
