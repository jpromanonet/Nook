(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    if (typeof Chart === 'undefined') return;
    var node = document.getElementById('metrics-data');
    if (!node) return;
    var data;
    try {
      data = JSON.parse(node.textContent || '{}');
    } catch (e) {
      return;
    }

    var ink = getComputedStyle(document.documentElement).getPropertyValue('--ink').trim() || '#30332F';
    var muted = getComputedStyle(document.documentElement).getPropertyValue('--muted').trim() || '#7a776f';
    var moss = getComputedStyle(document.documentElement).getPropertyValue('--moss').trim() || '#71806A';
    var paper = getComputedStyle(document.documentElement).getPropertyValue('--paper').trim() || '#F3EDE2';
    var grid = 'color-mix(in srgb, ' + muted + ' 22%, transparent)';

    Chart.defaults.color = muted;
    Chart.defaults.borderColor = grid;
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

    function emptyMessage(canvas, text) {
      var c = canvas.getContext('2d');
      if (!c) return;
      var w = canvas.clientWidth || 320;
      var h = canvas.clientHeight || 220;
      canvas.width = w;
      canvas.height = h;
      c.clearRect(0, 0, w, h);
      c.fillStyle = muted;
      c.font = '14px ' + (getComputedStyle(document.body).fontFamily || 'sans-serif');
      c.textAlign = 'center';
      c.textBaseline = 'middle';
      c.fillText(text || 'No data yet', w / 2, h / 2);
    }

    function hasValues(arr) {
      return Array.isArray(arr) && arr.some(function (n) { return Number(n) > 0; });
    }

    var statusEl = document.getElementById('chart-task-status');
    if (statusEl) {
      if (!hasValues(data.tasks.status.values)) {
        emptyMessage(statusEl, 'No tasks yet');
      } else {
        new Chart(statusEl, {
          type: 'doughnut',
          data: {
            labels: data.tasks.status.labels,
            datasets: [{
              data: data.tasks.status.values,
              backgroundColor: data.tasks.status.colors,
              borderWidth: 0,
              hoverOffset: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
              legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, pointStyle: 'circle' } }
            }
          }
        });
      }
    }

    var wsEl = document.getElementById('chart-task-workspace');
    if (wsEl) {
      if (!hasValues(data.tasks.workspaces.values)) {
        emptyMessage(wsEl, 'No open tasks');
      } else {
        new Chart(wsEl, {
          type: 'bar',
          data: {
            labels: data.tasks.workspaces.labels,
            datasets: [{
              data: data.tasks.workspaces.values,
              backgroundColor: data.tasks.workspaces.colors,
              borderRadius: 8,
              maxBarThickness: 36
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } },
              y: { grid: { display: false } }
            }
          }
        });
      }
    }

    var trendEl = document.getElementById('chart-task-trend');
    if (trendEl) {
      new Chart(trendEl, {
        type: 'line',
        data: {
          labels: data.tasks.trend.labels,
          datasets: [{
            label: 'Done',
            data: data.tasks.trend.values,
            borderColor: moss,
            backgroundColor: 'rgba(113, 128, 106, 0.18)',
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: moss
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }
          }
        }
      });
    }

    var prioEl = document.getElementById('chart-task-priority');
    if (prioEl) {
      if (!hasValues(data.tasks.priority.values)) {
        emptyMessage(prioEl, 'No open tasks');
      } else {
        new Chart(prioEl, {
          type: 'bar',
          data: {
            labels: data.tasks.priority.labels,
            datasets: [{
              data: data.tasks.priority.values,
              backgroundColor: ['#C67D67', '#B8956A', '#788FA0', '#9A958C'],
              borderRadius: 8,
              maxBarThickness: 42
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              x: { grid: { display: false } },
              y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }
            }
          }
        });
      }
    }

    var typeEl = document.getElementById('chart-type');
    if (typeEl) {
      if (!hasValues(data.library.types.values)) {
        emptyMessage(typeEl, 'Library is empty');
      } else {
        new Chart(typeEl, {
          type: 'bar',
          data: {
            labels: data.library.types.labels,
            datasets: [{
              data: data.library.types.values,
              backgroundColor: moss,
              borderRadius: 8,
              maxBarThickness: 40
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              x: { grid: { display: false } },
              y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }
            }
          }
        });
      }
    }

    var allWsEl = document.getElementById('chart-all-workspace');
    if (allWsEl) {
      if (!hasValues(data.library.workspaces.values)) {
        emptyMessage(allWsEl, 'No items yet');
      } else {
        new Chart(allWsEl, {
          type: 'doughnut',
          data: {
            labels: data.library.workspaces.labels,
            datasets: [{
              data: data.library.workspaces.values,
              backgroundColor: data.library.workspaces.colors,
              borderWidth: 0,
              hoverOffset: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '58%',
            plugins: {
              legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, pointStyle: 'circle' } }
            }
          }
        });
      }
    }

    var actEl = document.getElementById('chart-activity');
    if (actEl) {
      new Chart(actEl, {
        type: 'bar',
        data: {
          labels: data.library.activity.labels,
          datasets: [{
            label: 'Created',
            data: data.library.activity.values,
            backgroundColor: 'rgba(113, 128, 106, 0.45)',
            borderRadius: 6,
            maxBarThickness: 28
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }
          }
        }
      });
    }
  });
})();
