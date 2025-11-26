// Shared Chart.js helpers for CCSPS
// labelsOutside plugin: draws outside labels with simple collision avoidance
(function(){
  const labelsOutside = {
    id: 'labelsOutside',
    afterDraw: function(chart) {
      try {
        // allow global opt-out via window.CCSP_DISABLE_LABELS_OUTSIDE
        if (window && window.CCSP_DISABLE_LABELS_OUTSIDE) return;
        // allow per-chart opt-out. Support either `options.plugins.labelsOutside = { display: false }`
        // or `options.plugins.labelsOutside === false` or `options.labelsOutside` at top-level.
        const opts = chart && chart.options;
        const lblOpt = opts && ((opts.plugins && opts.plugins.labelsOutside) || opts.labelsOutside);
        if (lblOpt === false) return;
        if (lblOpt && lblOpt.display === false) return;
        const ctx = chart.ctx;
        const data = chart.data;
        const meta = chart.getDatasetMeta(0);
        if (!meta || !meta.data) return;
        const total = (data.datasets && data.datasets[0] && data.datasets[0].data) ? data.datasets[0].data.reduce((s, v) => s + Number(v || 0), 0) : 0;
        ctx.save();
        const padding = 8;
        const topBound = (chart.chartArea && chart.chartArea.top != null) ? (chart.chartArea.top + padding) : padding;
        const bottomBound = (chart.chartArea && chart.chartArea.bottom != null) ? (chart.chartArea.bottom - padding) : (chart.canvas.height - padding);
        const baseOffsetDefault = 18;
        const minDistDefault = 14;
        // Build items
        const items = [];
        meta.data.forEach((arc, i) => {
          if (!arc) return;
          const start = arc.startAngle;
          const end = arc.endAngle;
          const mid = (start + end) / 2;
          const outer = arc.outerRadius || 0;
          const lineStartX = arc.x + Math.cos(mid) * outer;
          const lineStartY = arc.y + Math.sin(mid) * outer;
          const desiredX = arc.x + Math.cos(mid) * (outer + baseOffsetDefault);
          const desiredY = arc.y + Math.sin(mid) * (outer + baseOffsetDefault);
          const value = (data.datasets[0].data[i] == null) ? 0 : data.datasets[0].data[i];
          const pct = total ? Math.round((Number(value) / total) * 100) : 0;
          const text = `${data.labels[i]}: ${value} (${pct}%)`;
          items.push({arc, i, mid, lineStartX, lineStartY, desiredX, desiredY, text});
        });
        if (!items.length) { ctx.restore(); return; }
        // compute available vertical space and adapt spacing
        const available = Math.max(1, bottomBound - topBound);
        let minDist = minDistDefault;
        const needed = items.length * minDist;
        let scale = 1;
        if (needed > available) {
          scale = available / needed;
          minDist = Math.max(8, Math.floor(minDist * scale));
        }
        const baseOffset = Math.max(10, Math.round(baseOffsetDefault * Math.max(0.6, scale)));
        // sort by desiredY and resolve collisions
        items.sort((a,b) => a.desiredY - b.desiredY);
        for (let k = 0; k < items.length; k++) {
          items[k].drawY = Math.max(items[k].desiredY, topBound);
          if (k > 0) {
            const prev = items[k-1];
            if (items[k].drawY - prev.drawY < minDist) items[k].drawY = prev.drawY + minDist;
          }
        }
        for (let k = items.length - 1; k >= 0; k--) {
          if (items[k].drawY > bottomBound) items[k].drawY = bottomBound;
          if (k < items.length - 1) {
            const next = items[k+1];
            if (next.drawY - items[k].drawY < minDist) items[k].drawY = next.drawY - minDist;
          }
        }
        const shiftDown = topBound - (items[0] ? items[0].drawY : topBound);
        if (shiftDown > 0) for (let k = 0; k < items.length; k++) items[k].drawY += shiftDown;
        // choose font size based on scale
        const baseFont = Math.max(10, Math.round(12 * Math.max(0.8, scale)));
        ctx.fillStyle = '#222'; ctx.font = baseFont + 'px sans-serif';
        // draw in original index order
        items.sort((a,b) => a.i - b.i);
        items.forEach(it => {
          const labelX = it.desiredX; // we keep desiredX; could shrink if many
          const labelY = Math.max(topBound, Math.min(bottomBound, it.drawY));
          ctx.strokeStyle = 'rgba(0,0,0,0.25)'; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(it.lineStartX, it.lineStartY);
          // draw a short elbow if label is far horizontally (optional)
          ctx.lineTo(labelX, labelY); ctx.stroke();
          ctx.textAlign = (Math.cos(it.mid) >= 0) ? 'left' : 'right'; ctx.textBaseline = 'middle';
          const tx = (Math.cos(it.mid) >= 0) ? labelX + 6 : labelX - 6;
          ctx.fillText(it.text, tx, labelY);
        });
        ctx.restore();
      } catch (e) {
        // fail silently - plugin must not break page
        console.error('labelsOutside plugin error', e);
      }
    }
  };

  // register plugin when Chart is available
  function registerPlugin() {
    if (window.Chart && Chart.register) {
      try { Chart.register(labelsOutside); window.labelsOutsidePlugin = labelsOutside; } catch (e) { /* ignore */ }
    } else {
      setTimeout(registerPlugin, 50);
    }
  }
  registerPlugin();

  // helper to disable/unregister the labelsOutside plugin at runtime
  window.disableLabelsOutside = function() {
    try {
      if (window.Chart && window.labelsOutsidePlugin && Chart.unregister) {
        Chart.unregister(window.labelsOutsidePlugin);
        window.labelsOutsidePlugin = null;
      }
    } catch (e) {
      console.error('disableLabelsOutside failed', e);
    }
    window.CCSP_DISABLE_LABELS_OUTSIDE = true;
  };
  // helper to generate a pleasing HSL-based color palette
  function generateColors(n, sat=62, light=56, hueOffset=0) {
    if (!n || n <= 0) return ['hsl(200,60%,60%)'];
    return Array.from({length: n}, (_, i) => `hsl(${Math.round((i * 360 / n) + hueOffset) % 360}, ${sat}%, ${light}%)`);
  }
  window.getChartColors = generateColors;
  // set some safe defaults when Chart becomes available
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
      const defaultOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
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
