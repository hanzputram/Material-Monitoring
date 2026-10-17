import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import { initAllCustomSelects, initCustomSelect } from './custom-select.js';

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.initAllCustomSelects = initAllCustomSelects;
window.initCustomSelect = initCustomSelect;

window.tableColumnHandler = function(storageKey, defaultCols) {
    return {
        cols: (defaultCols || []).map(c => typeof c === 'string' ? { key: c, visible: true } : { key: c.key, visible: c.visible !== false }),
        init() {
            try {
                const saved = localStorage.getItem('table_cols_' + storageKey);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    this.cols.forEach(c => {
                        if (parsed[c.key] !== undefined) c.visible = parsed[c.key];
                    });
                }
            } catch(e) {}
        },
        toggleCol(key) {
            const col = this.cols.find(c => c.key === key);
            if (col) {
                col.visible = !col.visible;
                this.persist();
            }
        },
        isColVisible(key) {
            const col = this.cols.find(c => c.key === key);
            return col ? col.visible : true;
        },
        setAllCols(val) {
            this.cols.forEach(c => c.visible = val);
            this.persist();
        },
        resetCols() {
            this.cols = defaultCols.map(c => ({ ...c }));
            this.persist();
        },
        persist() {
            try {
                const state = {};
                this.cols.forEach(c => state[c.key] = c.visible);
                localStorage.setItem('table_cols_' + storageKey, JSON.stringify(state));
            } catch(e) {}
        }
    };
};

document.addEventListener('DOMContentLoaded', () => {
    initAllCustomSelects();

    // Listen for modal openings or dynamic elements
    const observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
            if (m.addedNodes.length > 0) {
                for (const node of m.addedNodes) {
                    if (node.nodeType === 1 && !node.classList.contains('custom-dropdown-root') && !node.closest?.('.custom-dropdown-root')) {
                        if (node.matches && node.matches('select')) {
                            initCustomSelect(node);
                        } else if (node.querySelectorAll) {
                            initAllCustomSelects(node);
                        }
                    }
                }
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

document.addEventListener('alpine:initialized', () => {
    initAllCustomSelects();
});

Alpine.start();
