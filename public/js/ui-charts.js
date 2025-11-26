// Shared Chart.js helpers for CCSPS
(function(){
  // generate a pleasing HSL-based color palette
  function generateColors(n, sat=62, light=56, hueOffset=0) {
    if (!n || n <= 0) return ['hsl(200,60%,60%)'];
    return Array.from({length: n}, (_, i) => `hsl(${Math.round((i * 360 / n) + hueOffset) % 360}, ${sat}%, ${light}%)`);
  }
  window.getChartColors = generateColors;

  // set some safe Chart.defaults when Chart is available
  (function setDefaultsWhenReady(){
    if (window.Chart && Chart.defaults) {
      try {
        if (!Chart.defaults.color) Chart.defaults.color = '#222';
        if (!Chart.defaults.font) Chart.defaults.font = Chart.defaults.font || {};
        Chart.defaults.font.family = Chart.defaults.font.family || 'sans-serif';
      } catch(e){}
    } else {
      setTimeout(setDefaultsWhenReady, 50);
    }
  })();

  // helper to create a donut, removes zero-value slices and returns Chart instance
  window.createDonut = function(ctxOrSelector, labels, data, extraOptions = {}) {
    try {
      const ctxEl = (typeof ctxOrSelector === 'string') ? document.querySelector(ctxOrSelector) : ctxOrSelector;
      if (!ctxEl) return null;
      const canvas = (ctxEl instanceof HTMLCanvasElement) ? ctxEl : ctxEl.querySelector('canvas') || ctxEl;

      // filter zeros and empty labels unless caller requested forceRenderZeros
      const filteredLabels = [];
      const filteredData = [];
      const force = extraOptions && extraOptions.forceRenderZeros;
      for (let i = 0; i < (data || []).length; i++) {
        const v = Number(data[i] || 0);
        const lab = labels && labels[i] ? String(labels[i]) : '';
        if (!force) {
          if (v === 0 || lab === '') continue;
        } else {
          if (lab === '') continue;
        }
        filteredLabels.push(lab);
        filteredData.push(v);
      }

      // if nothing remains after filtering, bail out
      if (!filteredLabels.length) return null;

      // default to show legend; callers can override via extraOptions
      const defaultOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'right' } } };
      const options = Object.assign({}, defaultOptions, extraOptions);
      const colors = (extraOptions && extraOptions.colors) ? extraOptions.colors : generateColors(filteredLabels.length);

      // destroy any existing Chart instance on this canvas to allow re-render
      try {
        if (window.Chart && typeof Chart.getChart === 'function') {
          const prev = Chart.getChart(canvas);
          if (prev && typeof prev.destroy === 'function') prev.destroy();
        }
      } catch (e) { /* ignore */ }

      const chart = new Chart(canvas, {
        type: 'doughnut',
        data: { labels: filteredLabels, datasets: [{ data: filteredData, backgroundColor: colors, borderColor: '#ffffff', borderWidth: 1 }] },
        options
      });
      return chart;
    } catch (e) {
      console.error('createDonut error', e);
      return null;
    }
  };
})();
