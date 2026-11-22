'use strict';

// Password controls are progressive enhancement; the original input and autocomplete stay intact.
const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
const passwordControls = [];
const passwordEye =
    '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
const passwordEyeOff =
    '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.5 5.1 12 5c6.5 0 10 7 10 7a20 20 0 0 1-3.1 4M6 6.5A23 23 0 0 0 2 12s3.5 7 10 7c1.8 0 3.4-.5 4.8-1.2M10 10a3 3 0 0 0 4 4"/></svg>';

document.querySelectorAll('input[type="password"]').forEach((input, index) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'password-control';
    input.id ||= `password-field-${index + 1}`;
    const fieldName = input.labels?.[0]?.textContent.trim() || 'password';
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-toggle';
    button.setAttribute('aria-controls', input.id);
    const setVisible = (visible) => {
        const start = input.selectionStart;
        const end = input.selectionEnd;
        input.type = visible ? 'text' : 'password';
        button.innerHTML =
            (visible ? passwordEyeOff : passwordEye) + `<span>${visible ? 'Hide' : 'Show'}</span>`;
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute(
            'aria-label',
            `${visible ? 'Hide' : 'Show'} ${fieldName.toLowerCase()}`,
        );
        button.title = visible ? 'Hide password' : 'Show password';
        if (start !== null && end !== null) input.setSelectionRange(start, end);
    };
    input.before(wrapper);
    wrapper.append(input, button);
    setVisible(false);
    button.addEventListener('click', () => setVisible(input.type === 'password'));
    input.form?.addEventListener('submit', () => setVisible(false));
    passwordControls.push(() => setVisible(false));
});
window.addEventListener('blur', () => passwordControls.forEach((hide) => hide()));
window.addEventListener('pagehide', () => passwordControls.forEach((hide) => hide()));

// Settings panels use URL fragments so refreshes and saves return to the same tab.
document.querySelectorAll('[data-settings-tabs]').forEach((workspace) => {
    const tablist = workspace.querySelector('[role="tablist"]');
    const tabs = [...workspace.querySelectorAll('[data-settings-tab]')];
    const panels = [...workspace.querySelectorAll('[data-settings-panel]')];
    const activate = (key, { focus = false, updateUrl = false } = {}) => {
        const selected = tabs.find((tab) => tab.dataset.settingsTab === key) || tabs[0];
        if (!selected) return;
        const selectedKey = selected.dataset.settingsTab;
        tabs.forEach((tab) => {
            const active = tab === selected;
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        panels.forEach((panel) => {
            const active = panel.dataset.settingsPanel === selectedKey;
            panel.hidden = !active;
            panel.classList.toggle('is-active', active);
            panel.setAttribute('role', 'tabpanel');
            panel.tabIndex = 0;
        });
        if (updateUrl && location.hash !== `#${selectedKey}`) {
            history.replaceState(null, '', `#${selectedKey}`);
        }
        if (focus) {
            selected.focus({ preventScroll: true });
            selected.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'instant' });
        }
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab.dataset.settingsTab, { updateUrl: true }));
        tab.addEventListener('keydown', (event) => {
            let target;
            if (event.key === 'ArrowRight') target = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') target = (index - 1 + tabs.length) % tabs.length;
            if (event.key === 'Home') target = 0;
            if (event.key === 'End') target = tabs.length - 1;
            if (target === undefined) return;
            event.preventDefault();
            activate(tabs[target].dataset.settingsTab, { focus: true, updateUrl: true });
        });
    });
    tablist.hidden = false;
    activate(location.hash.slice(1));
    window.addEventListener('hashchange', () => activate(location.hash.slice(1)));
});

document.querySelectorAll('[data-toggle]').forEach((button) =>
    button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.toggle);
        if (!target) return;
        const open = target.classList.toggle('open');
        button.setAttribute('aria-expanded', String(open));
        if (open && target.id === 'search-panel') target.querySelector('input')?.focus();
    }),
);
// Notices, auto-submit fields and no-JS fallbacks use delegation or a root,
// so page sections swapped in later (cart, flash) behave like the original page.
const dismissNotice = (notice) => {
    if (motionPreference.matches || !notice.animate) {
        notice.remove();
        return;
    }
    notice.querySelector('[data-dismiss]')?.setAttribute('disabled', '');
    const animation = notice.animate([{ opacity: 1 }, { opacity: 0 }], {
        duration: 120,
        easing: 'ease-out',
        fill: 'forwards',
    });
    animation.finished.then(() => notice.remove()).catch(() => notice.remove());
};
document.addEventListener('click', (event) => {
    const notice = event.target.closest('[data-dismiss]')?.closest('.flash');
    if (notice) dismissNotice(notice);
});
// Success notices leave on their own; errors stay until dismissed. Hover or focus holds them.
const fadeLater = (notice) => {
    if (notice.classList.contains('error') || !notice.querySelector('[data-dismiss]')) return;
    const leave = () => {
        if (!notice.isConnected) return;
        if (notice.matches(':hover, :focus-within')) setTimeout(leave, 2000);
        else dismissNotice(notice);
    };
    setTimeout(leave, 6000);
};
document.querySelectorAll('.flash').forEach(fadeLater);
// Number fields wait a moment so a few spinner clicks become one submit.
const autoSubmitTimers = new WeakMap();
document.addEventListener('change', (event) => {
    const field = event.target.closest?.('[data-auto-submit]');
    if (!field?.form) return;
    clearTimeout(autoSubmitTimers.get(field));
    autoSubmitTimers.set(
        field,
        setTimeout(() => field.form.requestSubmit(), field.type === 'number' ? 600 : 0),
    );
});
const hideNoJs = (root) =>
    root.querySelectorAll('[data-no-js]').forEach((element) => (element.hidden = true));
hideNoJs(document);
// Replace the store notice and bag count with fresh ones from a server response.
const showNotice = (html) => {
    document.querySelectorAll('.flash:not([data-saved-notice])').forEach((old) => old.remove());
    const holder = document.createElement('div');
    holder.innerHTML = html.trim();
    const notice = holder.querySelector('.flash');
    if (!notice) return;
    document.querySelector('#main-content')?.before(notice);
    fadeLater(notice);
};
const setBagCount = (count) => {
    document.querySelectorAll('.cart-count').forEach((badge) => (badge.textContent = count));
    document
        .querySelectorAll('.cart-link')
        .forEach((link) => link.setAttribute('aria-label', `Shopping bag, ${count} items`));
};
const fetchPage = async (url, init = {}) => {
    const response = await fetch(url, { credentials: 'same-origin', ...init });
    if (!response.ok) throw new Error(`Request failed: ${response.status}`);
    return {
        url: new URL(response.url),
        doc: new DOMParser().parseFromString(await response.text(), 'text/html'),
    };
};
const formBody = (form, submitter) => {
    try {
        return new FormData(form, submitter);
    } catch {
        const body = new FormData(form);
        if (submitter?.name) body.append(submitter.name, submitter.value);
        return body;
    }
};
// Bag: quantity, remove and promo changes refresh the bag section in place.
document.addEventListener('submit', async (event) => {
    const form = event.target;
    const page = form.closest('.cart-page');
    if (!page || event.defaultPrevented || form.method !== 'post') return;
    event.preventDefault();
    const focusedId = document.activeElement?.id;
    page.setAttribute('aria-busy', 'true');
    try {
        const { url, doc } = await fetchPage(form.action, {
            method: 'POST',
            body: formBody(form, event.submitter),
        });
        const next = doc.querySelector('.cart-page');
        if (url.pathname !== '/cart' || !next) {
            location.assign(url.href);
            return;
        }
        page.replaceWith(next);
        hideNoJs(next);
        setBagCount(doc.querySelector('.cart-count')?.textContent ?? '0');
        showNotice(doc.querySelector('.flash:not([data-saved-notice])')?.outerHTML ?? '');
        if (focusedId) document.getElementById(focusedId)?.focus();
    } catch {
        page.removeAttribute('aria-busy');
        form.submit();
    }
});
// Product: add to bag without leaving the page.
document.getElementById('add-to-cart')?.addEventListener('submit', async (event) => {
    const form = event.currentTarget;
    event.preventDefault();
    const buttons = [...document.querySelectorAll('#add-button, [data-sticky-button]')];
    const wasDisabled = buttons.map((button) => button.disabled);
    buttons.forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    });
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await response.json();
        setBagCount(data.count);
        showNotice(data.flash || '');
    } catch {
        form.submit();
    } finally {
        buttons.forEach((button, i) => {
            button.disabled = wasDisabled[i];
            button.removeAttribute('aria-busy');
        });
    }
});
// Header search: suggest up to five products while typing; Enter still opens full results.
const searchField = document.getElementById('search');
const searchResults = document.querySelector('[data-search-results]');
if (searchField && searchResults) {
    let searchTimer;
    let searchRun = 0;
    searchField.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const term = searchField.value.trim();
        if (term.length < 2) {
            searchRun++;
            searchResults.replaceChildren();
            return;
        }
        searchTimer = setTimeout(async () => {
            const run = ++searchRun;
            try {
                const { doc } = await fetchPage('/shop?q=' + encodeURIComponent(term));
                if (run !== searchRun) return;
                const cards = [...doc.querySelectorAll('.product-card')].slice(0, 5);
                const list = document.createElement('ul');
                cards.forEach((card) => {
                    const link = card.querySelector('h3 a');
                    if (!link) return;
                    const item = document.createElement('li');
                    const anchor = document.createElement('a');
                    anchor.href = link.getAttribute('href');
                    const image = document.createElement('img');
                    image.src = card.querySelector('.product-photo img')?.getAttribute('src') ?? '';
                    image.alt = '';
                    image.width = 40;
                    image.height = 46;
                    const name = document.createElement('span');
                    name.textContent = link.textContent.trim();
                    const price = document.createElement('small');
                    price.textContent = card.querySelector('.product-meta > span')?.textContent.trim() ?? '';
                    anchor.append(image, name, price);
                    item.append(anchor);
                    list.append(item);
                });
                const summary = document.createElement('p');
                summary.textContent = cards.length
                    ? `${cards.length} suggestion${cards.length === 1 ? '' : 's'} · press Enter for all results`
                    : `No products match “${term}”.`;
                searchResults.replaceChildren(summary, ...(cards.length ? [list] : []));
            } catch {
                searchResults.replaceChildren();
            }
        }, 250);
    });
}
// Checkout promo forms carry what the shopper already typed.
const checkoutForm = document.getElementById('checkout-form');
document.querySelectorAll('[data-carry-checkout]').forEach((form) =>
    form.addEventListener('submit', () => {
        if (!checkoutForm) return;
        for (const [name, value] of new FormData(checkoutForm)) {
            if (['action', 'csrf', 'checkout_nonce'].includes(name)) continue;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.append(input);
        }
    }),
);
document.querySelectorAll('[data-reveal]').forEach((input) => {
    const toggle = () => {
        const target = document.getElementById(input.dataset.reveal);
        if (target) {
            target.hidden = !input.checked;
            target
                .querySelectorAll('input,textarea')
                .forEach((field) => (field.required = input.checked));
        }
    };
    input.addEventListener('change', toggle);
    toggle();
});
document.querySelectorAll('[data-confirm]').forEach((form) =>
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    }),
);
document.querySelectorAll('form[data-loading]').forEach((form) =>
    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const button = form.querySelector('button[type=submit],button:not([type])');
        if (button) {
            button.disabled = true;
            button.textContent = form.dataset.loading;
        }
    }),
);
document
    .querySelectorAll('[data-print]')
    .forEach((button) => button.addEventListener('click', () => window.print()));
const hero = document.querySelector('.hero');
if (hero) {
    const slides = [...hero.querySelectorAll('[data-slide]')],
        dots = [...hero.querySelectorAll('[data-slide-go]')];
    let index = 0,
        paused = window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        timer;
    const render = (i) => {
        index = (i + slides.length) % slides.length;
        slides.forEach((slide, n) => {
            slide.classList.toggle('current', n === index);
            slide.setAttribute('aria-hidden', String(n !== index));
            slide.querySelectorAll('a,button').forEach((a) => (a.tabIndex = n === index ? 0 : -1));
        });
        dots.forEach((dot, n) => {
            dot.classList.toggle('active', n === index);
            dot.setAttribute('aria-pressed', String(n === index));
        });
    };
    const restart = () => {
        clearInterval(timer);
        if (!paused && slides.length > 1) timer = setInterval(() => render(index + 1), 6500);
    };
    hero.querySelector('[data-slide-next]')?.addEventListener('click', () => {
        render(index + 1);
        restart();
    });
    hero.querySelector('[data-slide-prev]')?.addEventListener('click', () => {
        render(index - 1);
        restart();
    });
    dots.forEach((dot) =>
        dot.addEventListener('click', () => {
            render(Number(dot.dataset.slideGo));
            restart();
        }),
    );
    const pauseButton = hero.querySelector('[data-slide-pause]');
    const setPause = () => {
        pauseButton.textContent = paused ? 'Play' : 'Pause';
        pauseButton.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
        restart();
    };
    pauseButton?.addEventListener('click', () => {
        paused = !paused;
        setPause();
    });
    hero.addEventListener('mouseenter', () => clearInterval(timer));
    hero.addEventListener('mouseleave', restart);
    hero.addEventListener('focusin', () => clearInterval(timer));
    hero.addEventListener('focusout', restart);
    document.addEventListener('visibilitychange', () =>
        document.hidden ? clearInterval(timer) : restart(),
    );
    render(0);
    setPause();
}
const variantForm = document.querySelector('[data-variants]');
if (variantForm) {
    const variants = JSON.parse(variantForm.dataset.variants),
        qty = document.getElementById('quantity');
    const update = () => {
        const chosen = variantForm.querySelector('input[name="variant_id"]:checked');
        const v = variants.find((item) => String(item.id) === chosen?.value);
        if (!v) return;
        document.getElementById('variant-price').textContent = v.price_label;
        const compare = document.getElementById('variant-compare');
        compare.hidden = Number(v.compare_price) <= Number(v.price);
        compare.textContent = v.compare_label;
        document.getElementById('variant-sku').textContent = v.sku;
        document.getElementById('variant-stock').textContent =
            Number(v.stock) > 0 ? 'In stock. Ready for your home.' : 'Currently sold out.';
        document.getElementById('add-button').disabled = Number(v.stock) <= 0;
        qty.max = Math.max(1, Number(v.stock));
        qty.value = Math.max(1, Math.min(Number(qty.value), Number(qty.max)));
        const stickyButton = document.querySelector('[data-sticky-button]');
        if (stickyButton) {
            stickyButton.disabled = Number(v.stock) <= 0;
            document.querySelector('[data-sticky-price]').textContent = v.price_label;
        }
    };
    variantForm.addEventListener('change', (event) => {
        if (event.target.name === 'variant_id') update();
    });
    // Mobile buy bar appears once the real button has scrolled above the screen.
    // A scroll check (once per frame) also catches fast flings that skip past the button.
    const sticky = document.querySelector('[data-sticky-buy]');
    const anchor = document.getElementById('add-button');
    if (sticky && anchor) {
        let queued = false;
        const place = () => {
            queued = false;
            sticky.hidden = anchor.getBoundingClientRect().bottom > 0;
        };
        window.addEventListener(
            'scroll',
            () => {
                if (!queued) {
                    queued = true;
                    requestAnimationFrame(place);
                }
            },
            { passive: true },
        );
        place();
    }
    document
        .querySelectorAll('[data-quantity]')
        .forEach((button) =>
            button.addEventListener(
                'click',
                () =>
                    (qty.value = Math.max(
                        1,
                        Math.min(
                            Number(qty.max),
                            Number(qty.value) + Number(button.dataset.quantity),
                        ),
                    )),
            ),
        );
    update();
}
const thumbs = [...document.querySelectorAll('[data-gallery-src]')],
    mainImage = document.getElementById('main-image'),
    lightbox = document.getElementById('image-lightbox');
let imageIndex = 0;
let displayedImageIndex = 0;
let galleryRequest = 0;
const selectImage = async (i) => {
    if (!thumbs.length || !mainImage) return;
    imageIndex = (i + thumbs.length) % thumbs.length;
    const nextIndex = imageIndex;
    const ticket = ++galleryRequest;
    const src = thumbs[nextIndex].dataset.gallerySrc;
    const preload = new Image();
    preload.src = src;
    try {
        if (preload.decode) await preload.decode();
        else if (!preload.complete)
            await new Promise((resolve, reject) => {
                preload.onload = resolve;
                preload.onerror = reject;
            });
        if (ticket !== galleryRequest) return;
        if (!preload.naturalWidth) throw new Error('Image unavailable.');
    } catch {
        if (ticket === galleryRequest) imageIndex = displayedImageIndex;
        return;
    }
    mainImage.src = src;
    displayedImageIndex = nextIndex;
    thumbs.forEach((button, n) => {
        button.classList.toggle('active', n === nextIndex);
        button.setAttribute('aria-pressed', String(n === nextIndex));
    });
    const targets = [mainImage];
    if (lightbox?.open) {
        const enlarged = document.getElementById('lightbox-image');
        enlarged.src = src;
        targets.push(enlarged);
    }
    if (!motionPreference.matches)
        targets.forEach((target) => {
            if (!target.animate) return;
            target.getAnimations?.().forEach((animation) => animation.cancel());
            target.animate([{ opacity: 0.6 }, { opacity: 1 }], {
                duration: 180,
                easing: 'ease-out',
            });
        });
};
thumbs.forEach((button, index) => button.setAttribute('aria-pressed', String(index === 0)));
thumbs.forEach((button, i) => button.addEventListener('click', () => selectImage(i)));
document.querySelector('[data-lightbox-open]')?.addEventListener('click', () => {
    document.getElementById('lightbox-image').src = mainImage.src;
    lightbox.showModal();
});
document.querySelector('[data-lightbox-close]')?.addEventListener('click', () => lightbox.close());
lightbox?.addEventListener('click', (event) => {
    if (event.target === lightbox) lightbox.close();
});
document
    .querySelectorAll('[data-gallery-step]')
    .forEach((button) =>
        button.addEventListener('click', () =>
            selectImage(imageIndex + Number(button.dataset.galleryStep)),
        ),
    );
document.addEventListener('keydown', (event) => {
    if (lightbox?.open) {
        if (event.key === 'ArrowRight') selectImage(imageIndex + 1);
        if (event.key === 'ArrowLeft') selectImage(imageIndex - 1);
    }
    if (
        event.key === '/' &&
        !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName) &&
        document.getElementById('admin-search')
    ) {
        event.preventDefault();
        document.getElementById('admin-search').focus();
    }
    if (event.key === 'Escape') {
        document
            .querySelectorAll('.search-panel.open,.store-nav.open,.admin-sidebar.open')
            .forEach((node) => {
                node.classList.remove('open');
                document
                    .querySelector(`[data-toggle="${node.id}"]`)
                    ?.setAttribute('aria-expanded', 'false');
            });
    }
});
let variantIndex = document.querySelectorAll('#variant-table tbody tr').length;
document.querySelector('[data-add-variant]')?.addEventListener('click', () => {
    const index = variantIndex++;
    const tr = document.createElement('tr');
    const fields = [
        ['sku', 'text', ''],
        ['color', 'text', ''],
        ['size', 'text', 'Standard'],
        ['price', 'number', '0'],
        ['compare_price', 'number', '0'],
        ['stock', 'number', '0'],
    ];
    fields.forEach(([name, type, value]) => {
        const td = document.createElement('td');
        const input = document.createElement('input');
        input.name = `variants[${index}][${name}]`;
        input.type = type;
        input.value = value;
        input.required = true;
        input.setAttribute('aria-label', name.replace('_', ' '));
        if (type === 'number') input.min = '0';
        td.append(input);
        tr.append(td);
    });
    const td = document.createElement('td');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'icon-button';
    button.dataset.removeVariant = '';
    button.setAttribute('aria-label', 'Remove variant');
    button.innerHTML =
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65"><path d="m6 6 12 12M6 18 18 6"/></svg>';
    td.append(button);
    tr.append(td);
    document.querySelector('#variant-table tbody').append(tr);
    tr.querySelector('input').focus();
});
document.getElementById('variant-table')?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-remove-variant]');
    if (button) {
        if (document.querySelectorAll('#variant-table tbody tr').length < 2) {
            window.alert('Keep at least one variant for this product.');
            return;
        }
        button.closest('tr').remove();
    }
});
document.querySelector('[data-image-preview]')?.addEventListener('change', (event) => {
    const preview = document.getElementById('upload-preview');
    preview.replaceChildren();
    const files = [...event.target.files];
    if (files.length > 12) {
        window.alert('Choose at most 12 images.');
        event.target.value = '';
        return;
    }
    for (const file of files) {
        if (
            file.size > 5 * 1024 * 1024 ||
            !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
        ) {
            window.alert('Use JPG, PNG, or WebP images smaller than 5 MB.');
            event.target.value = '';
            preview.replaceChildren();
            return;
        }
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        img.onload = () => URL.revokeObjectURL(img.src);
        preview.append(img);
    }
});
const savedAddress = document.querySelector('[data-addresses]');
savedAddress?.addEventListener('change', () => {
    const address = JSON.parse(savedAddress.dataset.addresses).find(
        (a) => String(a.id) === savedAddress.value,
    );
    if (address)
        for (const field of ['first_name', 'last_name', 'phone', 'address', 'city', 'postal']) {
            const input = document.getElementById(field);
            if (input) input.value = address[field];
        }
});

// The wishlist belongs to this browser; only product IDs are kept in local storage.
function initializeSavedProducts() {
    const storageKey = 'form-field:saved-products:v1';
    const buttons = [...document.querySelectorAll('[data-save-product]')];
    const savedPage = document.querySelector('[data-saved-page]');
    const read = () => {
        try {
            const value = JSON.parse(localStorage.getItem(storageKey) || '[]');
            return Array.isArray(value)
                ? [
                      ...new Set(
                          value.filter((id) => Number.isInteger(id) && id > 0 && id <= 2147483647),
                      ),
                  ].slice(0, 50)
                : [];
        } catch {
            return [];
        }
    };
    let saved = read();
    const notice = (message) => {
        let region = document.querySelector('[data-saved-notice]');
        if (!region) {
            region = document.createElement('div');
            region.className = 'flash saved-notice';
            region.dataset.savedNotice = '';
            region.setAttribute('role', 'status');
            document.querySelector('#main-content')?.before(region);
        }
        region.textContent = message;
    };
    const write = (next) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(next));
            saved = next;
            return true;
        } catch {
            notice('Your browser could not save this item. Allow site storage and try again.');
            return false;
        }
    };
    const savedUrl = () =>
        '/saved' + (saved.length ? `?${new URLSearchParams({ items: saved.join(',') })}` : '');
    const render = () => {
        buttons.forEach((button) => {
            const selected = saved.includes(Number(button.dataset.saveProduct));
            button.hidden = false;
            button.setAttribute('aria-pressed', String(selected));
            button.setAttribute(
                'aria-label',
                `${selected ? 'Remove' : 'Save'} ${button.dataset.productName || 'product'}${selected ? ' from saved items' : ' for later'}`,
            );
            const label = button.querySelector('[data-save-label]');
            if (label) label.textContent = selected ? 'Saved' : 'Save for later';
        });
        document.querySelectorAll('[data-saved-link]').forEach((link) => {
            link.href = savedUrl();
            link.setAttribute('aria-label', `Saved items, ${saved.length} products`);
        });
        document.querySelectorAll('[data-saved-indicator]').forEach((indicator) => {
            indicator.hidden = !saved.length;
            indicator.textContent = String(saved.length);
        });
        document
            .querySelectorAll('[data-clear-saved]')
            .forEach((button) => (button.hidden = !saved.length));
        if (savedPage) {
            savedPage.querySelectorAll('.product-card[data-product-id]').forEach((card) => {
                if (!saved.includes(Number(card.dataset.productId))) card.remove();
            });
            const visible = savedPage.querySelectorAll('.product-card').length;
            savedPage.querySelector('[data-saved-empty]').hidden = visible > 0;
            savedPage.querySelector('[data-saved-grid]').hidden = visible === 0;
            savedPage.querySelector('[data-saved-count]').textContent =
                `${visible} saved product${visible === 1 ? '' : 's'}`;
        }
    };
    if (savedPage) {
        const requested = JSON.parse(savedPage.dataset.requestedIds || '[]');
        const matches =
            requested.length === saved.length && requested.every((id) => saved.includes(id));
        if (!matches) {
            location.replace(savedUrl());
            return;
        }
        const activeIds = [...savedPage.querySelectorAll('.product-card[data-product-id]')].map(
            (card) => Number(card.dataset.productId),
        );
        const current = saved.filter((id) => activeIds.includes(id));
        if (current.length !== saved.length && write(current)) {
            notice(
                'Some saved products are no longer available and have been removed from this list.',
            );
            history.replaceState(null, '', savedUrl());
        }
    }
    buttons.forEach((button) =>
        button.addEventListener('click', () => {
            const id = Number(button.dataset.saveProduct);
            if (!Number.isInteger(id) || id <= 0 || id > 2147483647) return;
            const selected = saved.includes(id);
            if (!selected && saved.length >= 50) {
                notice(
                    'Your wishlist holds up to 50 products. Remove an item before saving another.',
                );
                return;
            }
            if (!write(selected ? saved.filter((item) => item !== id) : [...saved, id])) return;
            render();
            if (savedPage) history.replaceState(null, '', savedUrl());
            notice(selected ? 'Removed from saved items.' : 'Saved for later in this browser.');
        }),
    );
    document.querySelectorAll('[data-clear-saved]').forEach((button) =>
        button.addEventListener('click', () => {
            if (!write([])) return;
            render();
            if (savedPage) history.replaceState(null, '', '/saved');
            notice('Your saved list is now empty.');
        }),
    );
    window.addEventListener('storage', (event) => {
        if (event.key !== storageKey && event.key !== null) return;
        saved = read();
        if (savedPage) location.replace(savedUrl());
        else render();
    });
    render();
}
initializeSavedProducts();

// Comparison uses the same PHP session as the bag, with a native-form fallback.
function initializeComparison() {
    const tray = document.querySelector('[data-compare-tray]');
    if (!tray) return;
    const forms = [...document.querySelectorAll('[data-compare-form]')];
    const feedback = document.querySelector('[data-shopping-feedback]');
    let items = JSON.parse(tray.dataset.items || '[]');
    let busy = false;
    let feedbackTimer;
    const render = (next) => {
        const wasHidden = tray.hidden;
        items = next;
        const selected = new Set(items.map((item) => item.id));
        forms
            .filter((form) => form.dataset.compareProduct)
            .forEach((form) => {
                const active = selected.has(Number(form.dataset.compareProduct));
                form.querySelector('[name="action"]').value = active
                    ? 'compare_remove'
                    : 'compare_add';
                form.querySelector('button').setAttribute('aria-pressed', String(active));
                form.querySelector('[data-compare-label]').textContent = active
                    ? 'Comparing'
                    : 'Compare';
            });
        const thumbs = tray.querySelector('[data-compare-thumbnails]');
        thumbs.replaceChildren();
        items.forEach((item) => {
            const link = document.createElement('a');
            link.href = item.url;
            const image = document.createElement('img');
            image.src = item.image;
            image.alt = item.name;
            image.width = 42;
            image.height = 48;
            link.append(image);
            thumbs.append(link);
        });
        tray.querySelector('[data-compare-count]').textContent =
            `${items.length} of 3 products selected`;
        const allowed = tray.dataset.allowTray === 'true';
        tray.hidden = !allowed || !items.length;
        document.body.classList.toggle('compare-active', !tray.hidden);
        if (wasHidden && !tray.hidden && !motionPreference.matches && tray.animate) {
            tray.animate(
                [
                    { opacity: 0, translate: '0 12px' },
                    { opacity: 1, translate: '0 0' },
                ],
                {
                    duration: 200,
                    easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
                },
            );
        }
    };
    const announce = (message) => {
        const region = tray.hidden ? feedback : tray.querySelector('[data-compare-feedback]');
        region.textContent = message;
        region.hidden = false;
        if (region === feedback) {
            clearTimeout(feedbackTimer);
            feedbackTimer = setTimeout(() => {
                feedback.hidden = true;
            }, 4500);
        }
    };
    forms.forEach((form) =>
        form.addEventListener('submit', async (event) => {
            if (!window.fetch || !window.FormData) return;
            event.preventDefault();
            if (busy) return;
            busy = true;
            const pressed = event.submitter || form.querySelector('button');
            const controls = forms.flatMap((node) => [...node.querySelectorAll('button')]);
            controls.forEach((button) => (button.disabled = true));
            form.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(form.action || location.href, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (response.status === 419)
                    throw new Error('Your session expired. Reload the page and try again.');
                if (!response.headers.get('content-type')?.includes('application/json')) {
                    throw new Error('Comparison is temporarily unavailable. Please try again.');
                }
                const result = await response.json();
                if (!Array.isArray(result.items))
                    throw new Error('Comparison could not be updated. Try again.');
                render(result.items);
                announce(result.message);
                if (result.ok && location.pathname === '/compare') {
                    location.assign('/compare');
                    return;
                }
                if (tray.hidden && tray.contains(pressed)) {
                    document
                        .querySelector('#main-content a, #main-content button')
                        ?.focus({ preventScroll: true });
                }
            } catch (error) {
                announce(
                    error instanceof Error
                        ? error.message
                        : 'Connection interrupted. Please try again.',
                );
            } finally {
                busy = false;
                form.removeAttribute('aria-busy');
                controls.forEach((button) => (button.disabled = false));
            }
        }),
    );
    document.querySelector('[data-compare-differences]')?.addEventListener('change', (event) => {
        document
            .querySelectorAll('[data-compare-equal="true"]')
            .forEach((row) => (row.hidden = event.target.checked));
    });
    render(items);
}
initializeComparison();

// Keep the builder advisory total separate from the authoritative prices used by PHP.
function initializeSetBuilder() {
    const form = document.querySelector('[data-set-builder]');
    if (!form) return;
    const rows = [...form.querySelectorAll('[data-set-product]')];
    const budget = form.querySelector('[data-set-budget]');
    const totalLabels = [...form.querySelectorAll('[data-set-total]')];
    const countLabel = form.querySelector('[data-set-count]');
    const status = form.querySelector('[data-set-feedback]');
    const submitButtons = [...form.querySelectorAll('[data-set-submit]')];
    // Option prices arrive in minor units of the shopper's currency; the budget is typed in whole units.
    const decimals = Number(form.dataset.currencyDecimals || 0);
    const money = (amount) =>
        (form.dataset.currencySymbol || '৳') +
        amount.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    let previousTotal = null;
    const update = () => {
        const count = rows.filter((row) => row.querySelector('[data-set-select]').checked).length;
        let subtotal = 0;
        let valid = count > 0 && count <= 6;
        let units = 0;
        rows.forEach((row) => {
            const checkbox = row.querySelector('[data-set-select]');
            const select = row.querySelector('[data-set-variant]');
            const quantity = row.querySelector('[data-set-quantity]');
            const option = select.selectedOptions[0];
            select.disabled = !checkbox.checked;
            quantity.disabled = !checkbox.checked;
            checkbox.disabled = count >= 6 && !checkbox.checked;
            row.classList.toggle('is-selected', checkbox.checked);
            const max = Math.min(10, Number(option?.dataset.stock || 0));
            quantity.max = String(max);
            if (checkbox.checked) {
                const qty = Number(quantity.value);
                if (!Number.isInteger(qty) || qty < 1 || qty > max) valid = false;
                else {
                    units += qty;
                    subtotal += Number(option?.dataset.price || 0) * qty;
                }
            }
        });
        subtotal /= 10 ** decimals;
        totalLabels.forEach((label) => (label.textContent = money(subtotal)));
        form.querySelector('[data-set-mobile-count]').textContent = `${count} of 6 products`;
        countLabel.textContent = `${count} of 6 products · ${units} item${units === 1 ? '' : 's'}`;
        submitButtons.forEach((button) => (button.disabled = !valid));
        const budgetValue = Number(budget.value);
        const tracking = budget.value !== '' && Number.isFinite(budgetValue) && budgetValue > 0;
        form.querySelector('[data-budget-track]').hidden = !tracking;
        form.querySelector('[data-budget-fill]').style.transform =
            `scaleX(${tracking ? Math.min(subtotal / budgetValue, 1) : 0})`;
        if (!count) status.textContent = 'Select up to six products to begin.';
        else if (!valid)
            status.textContent =
                'Check the selected quantities. Some exceed what can be added to your bag.';
        else if (tracking)
            status.textContent =
                subtotal <= budgetValue
                    ? `${money(budgetValue - subtotal)} left in your product budget.`
                    : `${money(subtotal - budgetValue)} over your product budget. You can adjust your choices.`;
        else status.textContent = 'Your set is ready. Add a budget above to track what is left.';
        if (previousTotal !== null && previousTotal !== subtotal && !motionPreference.matches) {
            totalLabels.forEach((label) => {
                if (!label.animate) return;
                label.getAnimations?.().forEach((animation) => animation.cancel());
                label.animate([{ opacity: 0.55 }, { opacity: 1 }], {
                    duration: 160,
                    easing: 'ease-out',
                });
            });
        }
        previousTotal = subtotal;
    };
    form.addEventListener('change', update);
    form.addEventListener('input', update);
    form.addEventListener('submit', () => {
        if (!form.checkValidity()) return;
        submitButtons.forEach((button) => {
            button.disabled = true;
            button.textContent = 'Adding your set…';
        });
    });
    update();
}
initializeSetBuilder();
