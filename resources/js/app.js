import './bootstrap';
import L from 'leaflet';
import Chart from 'chart.js/auto';
import Alpine from 'alpinejs';

window.L = L;
window.Chart = Chart;
window.Alpine = Alpine;

await import('leaflet.markercluster');
Alpine.start();
window.dispatchEvent(new Event('monitoring-vendors-ready'));