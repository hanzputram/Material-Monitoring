import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import { initAllCustomSelects, initCustomSelect } from './custom-select.js';

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.initAllCustomSelects = initAllCustomSelects;
window.initCustomSelect = initCustomSelect;

document.addEventListener('DOMContentLoaded', () => {
    initAllCustomSelects();

    // Listen for modal openings or dynamic elements
    const observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
            if (m.addedNodes.length > 0) {
                for (const node of m.addedNodes) {
                    if (node.nodeType === 1 && !node.classList.contains('custom-dropdown-root') && !node.closest?.('.custom-dropdown-root')) {
                        if (node.matches && node.matches('select.select-clean')) {
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

Alpine.start();
