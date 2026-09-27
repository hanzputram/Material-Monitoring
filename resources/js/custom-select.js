/**
 * Modern Custom Dropdown Component System
 * Replaces native Windows browser select popups with elegant floating cards
 */

export function initCustomSelect(select) {
    if (!select || 
        select.dataset.customSelect === 'true' || 
        select.closest('template') || 
        select.closest('[x-for]') || 
        select.hasAttribute(':name') || 
        select.hasAttribute('x-model') || 
        select.classList.contains('no-custom')
    ) {
        return;
    }
    select.dataset.customSelect = 'true';

    // Hide native select visually while keeping it in the form for submission
    select.style.setProperty('display', 'none', 'important');

    // Create wrapper
    const wrapper = document.createElement('div');
    const isSm = select.classList.contains('select-clean-sm');
    wrapper.className = `relative inline-block w-full text-left custom-dropdown-root ${isSm ? 'custom-dropdown-sm' : ''}`;

    // Trigger Button
    const btnPadding = isSm ? 'py-1.5 pl-2.5 pr-8 text-xs' : 'py-2.5 pl-3.5 pr-10 text-xs';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `w-full flex items-center justify-between text-left bg-white border border-slate-300 rounded-xl shadow-xs hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-pointer ${btnPadding}`;
    
    // Text container
    const textSpan = document.createElement('span');
    textSpan.className = 'truncate block flex-1';
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
    menu.className = 'absolute z-[9999] mt-1.5 w-full min-w-full bg-white rounded-xl shadow-2xl border border-slate-200 py-1.5 transition-all flex-col';

    // Search input (created dynamically if options > 5)
    let searchWrap = null;
    let searchInput = null;

    // List Container
    const listWrap = document.createElement('div');
    listWrap.className = 'overflow-y-auto flex-1 max-h-56 px-1 py-0.5 space-y-0.5 custom-scrollbar';
    menu.appendChild(listWrap);

    function updateButtonLabel() {
        const curOpt = select.selectedOptions[0] || select.options[0];
        const val = select.value;
        const text = curOpt ? curOpt.textContent.trim() : (select.getAttribute('placeholder') || '-- Pilih --');
        textSpan.textContent = text;
        if (!val || text.startsWith('--')) {
            textSpan.className = 'truncate block flex-1 font-normal text-slate-400';
        } else {
            textSpan.className = 'truncate block flex-1 font-semibold text-slate-800';
        }
    }

    // Render Option items
    function renderOptions(filter = '') {
        const currentOptions = Array.from(select.options);

        // Ensure search bar exists if options > 5
        if (currentOptions.length > 5 && !searchWrap) {
            searchWrap = document.createElement('div');
            searchWrap.className = 'p-2 border-b border-slate-100 flex-shrink-0';
            searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Cari opsi...';
            searchInput.className = 'w-full px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all';
            searchWrap.appendChild(searchInput);
            menu.insertBefore(searchWrap, listWrap);

            searchInput.addEventListener('input', (e) => {
                renderOptions(e.target.value);
            });
            searchInput.addEventListener('click', (e) => e.stopPropagation());
        }

        listWrap.innerHTML = '';
        const filterLower = filter.toLowerCase();
        let matchCount = 0;

        currentOptions.forEach((opt) => {
            const label = opt.textContent.trim();
            if (filter && !label.toLowerCase().includes(filterLower)) {
                return;
            }
            matchCount++;
            const isSelected = String(opt.value) === String(select.value);
            const item = document.createElement('div');
            item.className = `px-3 py-2 text-xs rounded-lg cursor-pointer flex items-center justify-between transition-colors ${
                isSelected 
                    ? 'bg-blue-50 text-blue-700 font-bold' 
                    : 'text-slate-700 hover:bg-slate-100 font-medium'
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

            item.addEventListener('click', (e) => {
                e.stopPropagation();
                select.value = opt.value;
                updateButtonLabel();
                closeMenu();
                
                // Dispatch native events
                select.dispatchEvent(new Event('change', { bubbles: true }));
                select.dispatchEvent(new Event('input', { bubbles: true }));

                // Execute onchange attribute if present
                if (select.getAttribute('onchange')) {
                    try {
                        new Function(select.getAttribute('onchange')).call(select);
                    } catch(err) {
                        console.error('Error executing inline onchange:', err);
                    }
                }
            });

            listWrap.appendChild(item);
        });

        if (matchCount === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-4 text-xs text-center text-slate-400 italic';
            empty.textContent = 'Tidak ada pilihan yang cocok';
            listWrap.appendChild(empty);
        }
    }

    updateButtonLabel();
    renderOptions();

    function openMenu() {
        // Close other open custom menus
        document.querySelectorAll('.custom-dropdown-root .custom-menu-open').forEach(m => {
            if (m !== menu) {
                m.style.display = 'none';
                m.classList.remove('custom-menu-open');
                const btn = m.parentElement.querySelector('button svg');
                if (btn) btn.classList.remove('rotate-180');
            }
        });

        menu.style.display = 'flex';
        menu.classList.add('custom-menu-open');
        chevronSvg.classList.add('rotate-180');
        renderOptions(searchInput ? searchInput.value : '');
        if (searchInput) {
            setTimeout(() => searchInput.focus(), 50);
        }
    }

    function closeMenu() {
        menu.style.display = 'none';
        menu.classList.remove('custom-menu-open');
        chevronSvg.classList.remove('rotate-180');
        if (searchInput) {
            searchInput.value = '';
        }
    }

    button.addEventListener('click', (e) => {
        e.stopPropagation();
        if (menu.style.display === 'none') {
            openMenu();
        } else {
            closeMenu();
        }
    });

    // Close on click outside using window capture phase so @click.stop inside modals doesn't block it
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

    // Keep button label in sync if select value is modified from script
    select.addEventListener('change', () => {
        updateButtonLabel();
    });

    // Append menu and insert wrapper into DOM right after select
    wrapper.appendChild(menu);
    select.parentNode.insertBefore(wrapper, select.nextSibling);
}

export function initAllCustomSelects(container = document) {
    container.querySelectorAll('select:not(.no-custom)').forEach(select => {
        if (!select.classList.contains('select-clean')) {
            select.classList.add('select-clean');
        }
        initCustomSelect(select);
    });
}
