/**
 * Enterprise Custom Dropdown Component System
 * Replaces native browser select popups with elegant, searchable, floating dropdown cards.
 * Fully compatible with Alpine.js (x-model), reactive bindings, dynamic options, and form validation.
 */

export function initCustomSelect(select) {
    if (!select || 
        select.dataset.customSelect === 'true' || 
        select.closest('template')
    ) {
        return;
    }
    select.dataset.customSelect = 'true';

    // Visually hide native select while keeping it focusable/validatable by browser forms
    select.style.position = 'absolute';
    select.style.width = '1px';
    select.style.height = '1px';
    select.style.padding = '0';
    select.style.margin = '-1px';
    select.style.overflow = 'hidden';
    select.style.clip = 'rect(0, 0, 0, 0)';
    select.style.whiteSpace = 'nowrap';
    select.style.border = '0';
    select.style.opacity = '0';
    select.style.pointerEvents = 'none';
    select.tabIndex = -1;

    // Detect size variant
    const isSm = select.classList.contains('select-clean-sm') || 
                 select.classList.contains('text-xs') || 
                 select.closest('table') !== null;

    // Create custom wrapper
    const wrapper = document.createElement('div');
    wrapper.className = `relative inline-block w-full text-left custom-dropdown-root ${isSm ? 'custom-dropdown-sm' : ''}`;

    // Trigger Button
    const btnPadding = isSm ? 'py-1.5 pl-2.5 pr-8 text-xs' : 'py-2.5 pl-3.5 pr-10 text-xs';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `w-full flex items-center justify-between text-left bg-white border border-slate-300 rounded-xl shadow-xs hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-pointer ${btnPadding}`;
    
    // Text container
    const textSpan = document.createElement('span');
    textSpan.className = 'truncate block flex-1 font-semibold text-slate-800';
    button.appendChild(textSpan);

    // Chevron SVG
    const chevronSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    chevronSvg.setAttribute('class', 'w-4 h-4 text-slate-400 flex-shrink-0 ml-2 transition-transform duration-200');
    chevronSvg.setAttribute('fill', 'none');
    chevronSvg.setAttribute('viewBox', '0 0 24 24');
    chevronSvg.setAttribute('stroke', 'currentColor');
    chevronSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7"/>';
    button.appendChild(chevronSvg);

    wrapper.appendChild(button);

    // Floating Menu Panel
    const menu = document.createElement('div');
    menu.style.display = 'none';
    menu.className = 'absolute z-[9999] min-w-full w-full bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 transition-all flex flex-col';

    // Search input
    let searchWrap = null;
    let searchInput = null;

    // List Container
    const listWrap = document.createElement('div');
    listWrap.className = 'overflow-y-auto flex-1 max-h-60 px-1 py-0.5 space-y-0.5 custom-scrollbar';
    menu.appendChild(listWrap);

    let activeHoverIndex = -1;
    let visibleItems = [];

    function updateButtonLabel() {
        const curOpt = select.selectedOptions[0] || select.options[0];
        const val = select.value;
        const text = curOpt ? curOpt.textContent.trim() : (select.getAttribute('placeholder') || '-- Pilih --');
        textSpan.textContent = text;
        
        if (!val || text.startsWith('--') || text.toLowerCase().includes('pilih')) {
            textSpan.className = 'truncate block flex-1 font-normal text-slate-400';
        } else {
            textSpan.className = 'truncate block flex-1 font-semibold text-slate-800';
        }

        // Disabled sync
        if (select.disabled) {
            button.classList.add('bg-slate-100', 'cursor-not-allowed', 'opacity-60');
            button.disabled = true;
        } else {
            button.classList.remove('bg-slate-100', 'cursor-not-allowed', 'opacity-60');
            button.disabled = false;
        }
    }

    // Render Option items
    function renderOptions(filter = '') {
        const currentOptions = Array.from(select.options);
        visibleItems = [];
        activeHoverIndex = -1;

        // Ensure search bar exists if options > 5
        if (currentOptions.length > 5) {
            if (!searchWrap) {
                searchWrap = document.createElement('div');
                searchWrap.className = 'p-2 border-b border-slate-100 flex-shrink-0';
                searchInput = document.createElement('input');
                searchInput.type = 'text';
                searchInput.placeholder = 'Cari pilihan...';
                searchInput.className = 'w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all font-medium';
                searchWrap.appendChild(searchInput);
                menu.insertBefore(searchWrap, listWrap);

                searchInput.addEventListener('input', (e) => {
                    renderOptions(e.target.value);
                });
                searchInput.addEventListener('click', (e) => e.stopPropagation());
                searchInput.addEventListener('keydown', (e) => {
                    handleKeyNavigation(e);
                });
            }
        } else if (searchWrap) {
            searchWrap.remove();
            searchWrap = null;
            searchInput = null;
        }

        listWrap.innerHTML = '';
        const filterLower = filter.toLowerCase().trim();
        let matchCount = 0;

        currentOptions.forEach((opt) => {
            const label = opt.textContent.trim();
            if (filterLower && !label.toLowerCase().includes(filterLower)) {
                return;
            }
            matchCount++;
            const isSelected = String(opt.value) === String(select.value);
            const isDisabled = opt.disabled;

            const item = document.createElement('div');
            item.dataset.value = opt.value;
            item.className = `px-3 py-2 text-xs rounded-lg flex items-center justify-between transition-colors ${
                isDisabled
                    ? 'text-slate-400 cursor-not-allowed italic opacity-60'
                    : isSelected 
                        ? 'bg-blue-50 text-blue-700 font-bold cursor-pointer' 
                        : 'text-slate-700 hover:bg-slate-100 font-medium cursor-pointer'
            }`;
            
            const itemText = document.createElement('span');
            itemText.className = 'truncate flex-1';
            itemText.textContent = label;
            item.appendChild(itemText);

            if (isSelected) {
                const checkSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                checkSvg.setAttribute('class', 'w-4 h-4 text-blue-600 flex-shrink-0 ml-2');
                checkSvg.setAttribute('fill', 'none');
                checkSvg.setAttribute('viewBox', '0 0 24 24');
                checkSvg.setAttribute('stroke', 'currentColor');
                checkSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>';
                item.appendChild(checkSvg);
            }

            if (!isDisabled) {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectOption(opt.value);
                });
                visibleItems.push(item);
            }

            listWrap.appendChild(item);
        });

        if (matchCount === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-4 text-xs text-center text-slate-400 italic';
            empty.textContent = 'Tidak ada pilihan yang cocok';
            listWrap.appendChild(empty);
        }
    }

    function selectOption(val) {
        select.value = val;
        updateButtonLabel();
        closeMenu();
        
        // Clear any validation error styling
        button.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200');

        // Dispatch events for Alpine.js x-model and native listeners
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));

        // Execute inline onchange attribute if present
        if (select.getAttribute('onchange')) {
            try {
                new Function(select.getAttribute('onchange')).call(select);
            } catch(err) {
                console.error('Error executing inline onchange:', err);
            }
        }
    }

    function handleKeyNavigation(e) {
        if (!visibleItems.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeHoverIndex = (activeHoverIndex + 1) % visibleItems.length;
            highlightItem(activeHoverIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeHoverIndex = (activeHoverIndex - 1 + visibleItems.length) % visibleItems.length;
            highlightItem(activeHoverIndex);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeHoverIndex >= 0 && visibleItems[activeHoverIndex]) {
                selectOption(visibleItems[activeHoverIndex].dataset.value);
            }
        } else if (e.key === 'Escape') {
            closeMenu();
            button.focus();
        }
    }

    function highlightItem(index) {
        visibleItems.forEach((it, i) => {
            if (i === index) {
                it.classList.add('bg-slate-100', 'text-slate-900');
                it.scrollIntoView({ block: 'nearest' });
            } else if (!it.classList.contains('bg-blue-50')) {
                it.classList.remove('bg-slate-100', 'text-slate-900');
            }
        });
    }

    function openMenu() {
        if (select.disabled) return;

        // Close any other open dropdowns across the page
        document.querySelectorAll('.custom-dropdown-root .custom-menu-open').forEach(m => {
            if (m !== menu) {
                m.style.display = 'none';
                m.classList.remove('custom-menu-open');
                const btnSvg = m.parentElement.querySelector('button svg');
                if (btnSvg) btnSvg.classList.remove('rotate-180');
            }
        });

        // Smart Positioning: open upward if near bottom of viewport
        const rect = button.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;

        if (spaceBelow < 230 && spaceAbove > spaceBelow) {
            menu.style.bottom = 'calc(100% + 4px)';
            menu.style.top = 'auto';
        } else {
            menu.style.top = 'calc(100% + 4px)';
            menu.style.bottom = 'auto';
        }

        menu.style.display = 'flex';
        menu.classList.add('custom-menu-open');
        chevronSvg.classList.add('rotate-180');
        
        updateButtonLabel();
        renderOptions(searchInput ? searchInput.value : '');

        if (searchInput) {
            setTimeout(() => searchInput.focus(), 60);
        }
    }

    function closeMenu() {
        menu.style.display = 'none';
        menu.classList.remove('custom-menu-open');
        chevronSvg.classList.remove('rotate-180');
        if (searchInput) {
            searchInput.value = '';
        }
        activeHoverIndex = -1;
    }

    button.addEventListener('click', (e) => {
        e.stopPropagation();
        if (menu.style.display === 'none') {
            openMenu();
        } else {
            closeMenu();
        }
    });

    button.addEventListener('keydown', (e) => {
        if (menu.style.display === 'none') {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openMenu();
            }
        } else {
            handleKeyNavigation(e);
        }
    });

    // Close on click outside (capture phase so @click.stop inside modals doesn't block it)
    window.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            closeMenu();
        }
    }, true);

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.style.display !== 'none') {
            closeMenu();
        }
    });

    // Handle HTML5 validation states
    select.addEventListener('invalid', () => {
        button.classList.add('border-rose-500', 'ring-2', 'ring-rose-200');
        button.focus();
    });
    select.addEventListener('input', () => {
        button.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200');
    });

    // Keep button label in sync when select value is modified
    select.addEventListener('change', () => {
        updateButtonLabel();
    });

    // Intercept property descriptor of `value` & `selectedIndex` so Alpine.js x-model updates reflect immediately
    const origValueDescriptor = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    if (origValueDescriptor) {
        Object.defineProperty(select, 'value', {
            get() {
                return origValueDescriptor.get.call(this);
            },
            set(val) {
                origValueDescriptor.set.call(this, val);
                updateButtonLabel();
            }
        });
    }

    const origIndexDescriptor = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');
    if (origIndexDescriptor) {
        Object.defineProperty(select, 'selectedIndex', {
            get() {
                return origIndexDescriptor.get.call(this);
            },
            set(idx) {
                origIndexDescriptor.set.call(this, idx);
                updateButtonLabel();
            }
        });
    }

    // Observer for dynamic options changes (<option> added/removed/selected)
    const optObserver = new MutationObserver(() => {
        updateButtonLabel();
        if (menu.style.display !== 'none') {
            renderOptions(searchInput ? searchInput.value : '');
        }
    });
    optObserver.observe(select, { 
        childList: true, 
        subtree: true, 
        attributes: true, 
        attributeFilter: ['selected', 'disabled'] 
    });

    // Synchronize form reset
    if (select.form) {
        select.form.addEventListener('reset', () => {
            setTimeout(updateButtonLabel, 20);
        });
    }

    updateButtonLabel();
    renderOptions();

    // Append menu to wrapper and insert wrapper into DOM right after select
    wrapper.appendChild(menu);
    select.parentNode.insertBefore(wrapper, select.nextSibling);
}

export function initAllCustomSelects(container = document) {
    if (!container || !container.querySelectorAll) return;
    container.querySelectorAll('select').forEach(select => {
        if (!select.classList.contains('select-clean')) {
            select.classList.add('select-clean');
        }
        initCustomSelect(select);
    });
}
