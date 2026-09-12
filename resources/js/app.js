/**
 * HAU Quality Assurance Portal
 * Client-Side Local Draft Auto-Save & Recovery System
 * 
 * Comprehensive Form & Modal Auto-Save:
 * - Captures textareas, text inputs, dates, links, single & multi-selects, 
 *   dependent dropdowns (Accrediting Body -> Areas), and dynamic repeater lists 
 *   (Schools, Programs, Units, Recommendations, Areas).
 * - Restores complete form states including dynamic rows and dependent cascades.
 * - Auto-cleans drafts on form submit or explicit discard.
 * - Auto-prunes expired drafts (> 48h TTL) on startup.
 */

(function () {
    'use strict';

    const STORAGE_PREFIX = 'hau_draft_v2_';
    const TTL_MS = 48 * 60 * 60 * 1000; // 48 Hours

    function timeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        if (seconds < 60) return 'just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        return `${Math.floor(hours / 24)}d ago`;
    }

    /**
     * Determine if a form should have draft auto-save enabled.
     * Excludes:
     * - Auth & Security forms (login, forgot-password, reset-password, verify-otp, change password)
     * - Any form containing password inputs, OTP fields, or security tokens
     * - Destructive & transition action forms (logout, bulk-destroy, quick approve/reject, delete, toggle)
     * - Search and filter toolbar forms
     * - Forms explicitly marked with data-no-draft="true" or class .no-draft / .no-autosave
     * - Forms without fillable content fields (text, textarea, select)
     */
    function shouldAutoSaveForm(form) {
        if (!form || !(form instanceof HTMLFormElement)) return false;

        // 1. Explicit ignore markers on form or ancestor
        if (form.hasAttribute('data-no-draft') || 
            form.dataset.noDraft === 'true' || 
            form.classList.contains('no-draft') || 
            form.classList.contains('no-autosave') ||
            form.closest('[data-no-draft="true"], .no-draft, .no-autosave')) {
            return false;
        }

        // 2. Auth URL / page checks
        const pathname = window.location.pathname.toLowerCase();
        const authRoutes = ['/login', '/forgot-password', '/reset-password', '/verify-otp', '/otp', '/password'];
        if (authRoutes.some(route => pathname.includes(route))) {
            return false;
        }

        // 3. Form action checks (auth, logout, quick approvals, bulk actions)
        const action = (form.getAttribute('action') || '').toLowerCase();
        const ignoreActions = ['/login', '/logout', 'bulk-destroy', '/approve', '/reject', '/toggle', '/toggle-accreditable', 'password'];
        if (ignoreActions.some(act => action.includes(act))) {
            return false;
        }

        // 4. Form ID / name checks
        const formId = (form.id || '').toLowerCase();
        const formName = (form.getAttribute('name') || '').toLowerCase();
        const ignoreFormIdentifiers = ['login', 'logout', 'reject-form', 'reject-item-form', 'auth', 'search', 'filter'];
        if (ignoreFormIdentifiers.some(ident => formId.includes(ident) || formName.includes(ident))) {
            return false;
        }

        // 5. Password & security field checks (NEVER save drafts for credentials)
        if (form.querySelector('input[type="password"]')) {
            return false;
        }

        // 6. OTP & Token field checks
        if (form.querySelector('input[name="otp"], input[name*="otp"], input[name="token"], input[name="email_otp"]')) {
            return false;
        }

        // 7. GET / Search filter forms without textareas
        if (form.method.toUpperCase() === 'GET' && !form.querySelector('textarea')) {
            return false;
        }

        // 8. Single-button action forms or forms with no meaningful inputs
        const fillableInputs = form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]), textarea, select');
        if (fillableInputs.length === 0) {
            return false;
        }

        return true;
    }

    /**
     * Purge drafts older than 48 hours or belonging to excluded forms (auth, login, etc.)
     */
    function purgeExpiredDrafts() {
        try {
            const now = Date.now();
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && (key.startsWith(STORAGE_PREFIX) || key.startsWith('hau_draft_v1_'))) {
                    const lowerKey = key.toLowerCase();
                    // Instantly clean up any legacy draft keys related to login, passwords, logout, auth, or OTP
                    if (lowerKey.includes('login') || lowerKey.includes('password') || lowerKey.includes('logout') || lowerKey.includes('auth') || lowerKey.includes('otp')) {
                        keysToRemove.push(key);
                        continue;
                    }
                    try {
                        const item = JSON.parse(localStorage.getItem(key));
                        if (!item || !item.timestamp || (now - item.timestamp > TTL_MS)) {
                            keysToRemove.push(key);
                        }
                    } catch (e) {
                        keysToRemove.push(key);
                    }
                }
            }
            keysToRemove.forEach(k => localStorage.removeItem(k));
        } catch (e) {
            console.warn('HAU AutoSave: Error accessing localStorage', e);
        }
    }

    /**
     * Unique key for a form
     */
    function getStorageKey(form) {
        const path = window.location.pathname.replace(/[^a-zA-Z0-9_-]/g, '_');
        const formIdentifier = form.id || form.getAttribute('action') || form.getAttribute('name') || 'main_form';
        return `${STORAGE_PREFIX}${path}_${formIdentifier}`;
    }

    /**
     * Serialize full form data (standard fields + repeater arrays)
     */
    function serializeForm(form) {
        if (!shouldAutoSaveForm(form)) {
            return { fields: {}, arrayFields: {}, hasContent: false };
        }

        const fields = {};
        const arrayFields = {};
        let hasContent = false;

        // 1. Single and Array Elements
        const elements = form.querySelectorAll('input, textarea, select');
        elements.forEach(el => {
            const name = el.name || el.id;
            if (!name || name === '_token' || name === '_method' || el.type === 'password' || el.type === 'submit' || el.type === 'button' || el.dataset.noDraft) {
                return;
            }

            if (name.endsWith('[]')) {
                if (!arrayFields[name]) arrayFields[name] = [];
                const val = el.value !== undefined ? el.value.trim() : '';
                if (val !== '') {
                    arrayFields[name].push(el.value);
                    hasContent = true;
                }
            } else {
                const val = el.value !== undefined ? el.value.trim() : '';
                // Check if meaningful (non-empty)
                if (val !== '') {
                    fields[name] = el.value;
                    hasContent = true;
                }
            }
        });

        return { fields, arrayFields, hasContent };
    }

    /**
     * Save draft to localStorage
     */
    function saveFormDraft(form) {
        if (!shouldAutoSaveForm(form)) return;

        try {
            const { fields, arrayFields, hasContent } = serializeForm(form);
            const storageKey = getStorageKey(form);

            if (hasContent) {
                localStorage.setItem(storageKey, JSON.stringify({
                    timestamp: Date.now(),
                    fields,
                    arrayFields
                }));
                showSaveStatus(form);
            } else {
                localStorage.removeItem(storageKey);
                hideSaveStatus(form);
            }
        } catch (e) {
            console.warn('HAU AutoSave: Save failed', e);
        }
    }

    /**
     * Subtle save status pill
     */
    function showSaveStatus(form) {
        let statusPill = form.querySelector('.hau-draft-status-pill');
        if (!statusPill) {
            statusPill = document.createElement('div');
            statusPill.className = 'hau-draft-status-pill text-[11px] text-amber-700 font-medium flex items-center gap-1.5 py-1 px-2.5 mt-2 bg-amber-50/90 border border-amber-200/70 rounded-md w-fit transition-opacity duration-300 select-none';
            const footer = form.querySelector('.bg-gray-50, .modal-footer, .flex.justify-end') || form.lastElementChild;
            if (footer && footer.parentNode) {
                footer.parentNode.insertBefore(statusPill, footer);
            } else {
                form.appendChild(statusPill);
            }
        }
        statusPill.innerHTML = `
            <svg class="w-3.5 h-3.5 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
            </svg>
            <span>Draft saved locally (${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })})</span>
        `;
        statusPill.style.display = 'flex';
        statusPill.style.opacity = '1';
    }

    function hideSaveStatus(form) {
        const statusPill = form.querySelector('.hau-draft-status-pill');
        if (statusPill) {
            statusPill.style.display = 'none';
        }
    }

    /**
     * Restore all form fields, dropdowns, dependent cascades, and dynamic repeater lists
     */
    function restoreFormDraft(form, draft) {
        if (!draft || !shouldAutoSaveForm(form)) return;
        const fields = draft.fields || {};
        const arrayFields = draft.arrayFields || {};

        const isEdit = form.id === 'edit-form' || form.closest('#edit-modal') !== null;
        const prefix = isEdit ? 'edit' : 'add';

        // ── 1. Priority: Accrediting Body (triggers area options & un-disables them) ──
        const bodyValue = fields.accrediting_body || fields[`${prefix}-accrediting_body`];
        if (bodyValue) {
            const bodySelect = form.querySelector(`select[name="accrediting_body"], #${prefix}-accrediting_body`);
            if (bodySelect) {
                bodySelect.value = bodyValue;
                bodySelect.dispatchEvent(new Event('change', { bubbles: true }));
                if (typeof window.updateAreasForModal === 'function') {
                    window.updateAreasForModal(prefix);
                }
            }
        }

        // ── 2. Dynamic Array / Repeater Fields ──

        // (a) Schools Repeater
        if (arrayFields['schools[]'] && arrayFields['schools[]'].length > 0) {
            const schoolsContainer = form.querySelector(`#${prefix}-schools-list`) || form.querySelector('[id$="-schools-list"]');
            if (schoolsContainer) {
                const schoolValues = arrayFields['schools[]'];
                const existingRows = schoolsContainer.querySelectorAll('.school-dropdown-row, .flex');
                // Remove extra rows beyond first
                for (let i = 1; i < existingRows.length; i++) existingRows[i].remove();

                const firstSelect = schoolsContainer.querySelector('select');
                if (firstSelect && schoolValues[0]) {
                    firstSelect.value = schoolValues[0];
                    firstSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                // Add remaining rows
                for (let i = 1; i < schoolValues.length; i++) {
                    if (typeof window.addSchoolDropdownRow === 'function') {
                        window.addSchoolDropdownRow(schoolsContainer.id, schoolValues[i]);
                    }
                }
                if (typeof window.filterProgramsBySchool === 'function') {
                    window.filterProgramsBySchool(prefix);
                }
            }
        }

        // (b) Programs Repeater
        if (arrayFields['program_ids[]'] && arrayFields['program_ids[]'].length > 0) {
            const progContainer = form.querySelector(`#${prefix}-programs-list`) || form.querySelector('[id$="-programs-list"]');
            if (progContainer) {
                const progValues = arrayFields['program_ids[]'];
                const existingRows = progContainer.querySelectorAll('.program-dropdown-row, .flex');
                for (let i = 1; i < existingRows.length; i++) existingRows[i].remove();

                const firstSelect = progContainer.querySelector('select');
                if (firstSelect && progValues[0]) {
                    firstSelect.value = progValues[0];
                    firstSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                for (let i = 1; i < progValues.length; i++) {
                    if (typeof window.addProgramDropdownRow === 'function') {
                        window.addProgramDropdownRow(progContainer.id, progValues[i]);
                    }
                }
            }
        }

        // (c) Responsible Units Repeater
        if (arrayFields['responsible_unit_ids[]'] && arrayFields['responsible_unit_ids[]'].length > 0) {
            const unitsContainer = form.querySelector(`#${prefix}-units-list`) || form.querySelector('[id$="-units-list"]');
            if (unitsContainer) {
                const unitValues = arrayFields['responsible_unit_ids[]'];
                const existingRows = unitsContainer.querySelectorAll('.unit-dropdown-row, .flex');
                for (let i = 1; i < existingRows.length; i++) existingRows[i].remove();

                const firstSelect = unitsContainer.querySelector('select');
                if (firstSelect && unitValues[0]) {
                    firstSelect.value = unitValues[0];
                    firstSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                for (let i = 1; i < unitValues.length; i++) {
                    if (typeof window.addUnitDropdownRow === 'function') {
                        window.addUnitDropdownRow(unitsContainer.id, unitValues[i]);
                    }
                }
            }
        }

        // (d) Areas Repeater (populated after Accrediting Body is updated)
        if (arrayFields['areas[]'] && arrayFields['areas[]'].length > 0) {
            const areasContainer = form.querySelector(`#${prefix}-areas-list`) || form.querySelector('[id$="-areas-list"]');
            if (areasContainer) {
                const areaValues = arrayFields['areas[]'];
                const existingRows = areasContainer.querySelectorAll('.flex');
                for (let i = 1; i < existingRows.length; i++) existingRows[i].remove();

                const firstSelect = areasContainer.querySelector('select');
                if (firstSelect && areaValues[0]) {
                    firstSelect.value = areaValues[0];
                    firstSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                for (let i = 1; i < areaValues.length; i++) {
                    if (typeof window.addAreaRow === 'function') {
                        window.addAreaRow(areasContainer.id, areaValues[i]);
                    }
                }
            }
        }

        // (e) Recommendations Repeater
        if (arrayFields['recommendations[]'] && arrayFields['recommendations[]'].length > 0) {
            const recoContainer = form.querySelector(`#${prefix}-recommendations-list`) || form.querySelector('[id$="-recommendations-list"]');
            if (recoContainer) {
                const recoValues = arrayFields['recommendations[]'];
                const existingRows = recoContainer.querySelectorAll('.flex, .reco-row-animate');
                for (let i = 1; i < existingRows.length; i++) existingRows[i].remove();

                const firstInput = recoContainer.querySelector('input');
                if (firstInput && recoValues[0]) {
                    firstInput.value = recoValues[0];
                    firstInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                for (let i = 1; i < recoValues.length; i++) {
                    if (typeof window.addRecoRow === 'function') {
                        window.addRecoRow(recoContainer.id);
                        const inputs = recoContainer.querySelectorAll('input');
                        if (inputs.length > i) {
                            inputs[i].value = recoValues[i];
                            inputs[i].dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                }
            }
        }

        // (f) Categories Repeater or Single Select
        if (arrayFields['categories[]'] && arrayFields['categories[]'].length > 0) {
            const catSelect = form.querySelector(`select[name="categories[]"], #${prefix}-category`);
            if (catSelect) {
                catSelect.value = arrayFields['categories[]'][0];
                catSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // ── 3. Standard Single Inputs & Textareas ──
        for (const [key, val] of Object.entries(fields)) {
            // Already handled accrediting body in step 1
            if (key === 'accrediting_body' || key === `${prefix}-accrediting_body`) continue;

            const el = form.querySelector(`[name="${key}"], #${key}`);
            if (el) {
                el.value = val;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    /**
     * Check if saved draft exists and prompt user with Restore Banner
     */
    function checkAndPromptDraft(form) {
        if (!shouldAutoSaveForm(form)) return;

        try {
            const storageKey = getStorageKey(form);
            const savedRaw = localStorage.getItem(storageKey);
            if (!savedRaw) return;

            const saved = JSON.parse(savedRaw);
            if (!saved || (!saved.fields && !saved.arrayFields)) return;

            const fields = saved.fields || {};
            const arrayFields = saved.arrayFields || {};
            const hasData = Object.keys(fields).length > 0 || Object.keys(arrayFields).length > 0;
            if (!hasData) return;

            // Avoid duplicate banners
            if (form.querySelector('.hau-draft-restore-banner')) return;

            const banner = document.createElement('div');
            banner.className = 'hau-draft-restore-banner flex flex-wrap items-center justify-between gap-2.5 p-3 mb-3 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300/80 rounded-xl text-xs text-amber-900 shadow-sm';
            banner.innerHTML = `
                <div class="flex items-center gap-2">
                    <div class="p-1 rounded-md bg-amber-200/70 text-amber-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <strong class="font-bold text-amber-950">Unsaved draft found</strong>
                        <span class="text-amber-800/90 ml-1">(${timeAgo(new Date(saved.timestamp))})</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="hau-restore-btn inline-flex items-center gap-1.5 px-3 py-1.5 bg-hau-gold-dark hover:bg-amber-700 text-white font-bold rounded-lg shadow-xs transition cursor-pointer" style="background-color: #d97706;">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Restore Draft
                    </button>
                    <button type="button" class="hau-discard-btn px-2.5 py-1.5 text-gray-500 hover:text-gray-800 font-semibold hover:bg-black/5 rounded-lg transition cursor-pointer">
                        Discard
                    </button>
                </div>
            `;

            // Restore action handler
            banner.querySelector('.hau-restore-btn').addEventListener('click', () => {
                restoreFormDraft(form, saved);
                banner.className = 'hau-draft-restore-banner flex items-center gap-2 p-2.5 mb-3 bg-emerald-50 border border-emerald-300 rounded-xl text-xs text-emerald-800 shadow-xs';
                banner.innerHTML = `
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-bold">Draft restored successfully with all fields and selections!</span>
                `;
                setTimeout(() => banner.remove(), 2500);
            });

            // Discard action handler
            banner.querySelector('.hau-discard-btn').addEventListener('click', () => {
                localStorage.removeItem(storageKey);
                hideSaveStatus(form);
                banner.remove();
            });

            const scrollableArea = form.querySelector('.overflow-y-auto') || form;
            scrollableArea.insertBefore(banner, scrollableArea.firstChild);
        } catch (e) {
            console.warn('HAU AutoSave: Prompt failed', e);
        }
    }

    /**
     * Initialize Draft Engine
     */
    function initAutoSave() {
        purgeExpiredDrafts();

        const allForms = Array.from(document.querySelectorAll('form'));
        const forms = allForms.filter(shouldAutoSaveForm);

        forms.forEach(form => {
            checkAndPromptDraft(form);

            // Debounced auto-save on input or select change
            let timeout = null;
            const handleChange = () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => saveFormDraft(form), 400);
            };

            form.addEventListener('input', handleChange);
            form.addEventListener('change', handleChange);

            // Wipe draft when form is successfully submitted
            form.addEventListener('submit', () => {
                try {
                    const storageKey = getStorageKey(form);
                    localStorage.removeItem(storageKey);
                    hideSaveStatus(form);
                } catch (e) {}
            });
        });

        // Watch for dynamic modal opens (removing 'hidden' class or changing display)
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                if (mutation.type === 'attributes' && (mutation.attributeName === 'class' || mutation.attributeName === 'style')) {
                    const target = mutation.target;
                    if (target.classList && !target.classList.contains('hidden') && target.style.display !== 'none') {
                        const modalForms = target.querySelectorAll ? Array.from(target.querySelectorAll('form')).filter(shouldAutoSaveForm) : [];
                        modalForms.forEach(f => checkAndPromptDraft(f));
                    }
                }
            });
        });

        observer.observe(document.body, {
            attributes: true,
            subtree: true,
            attributeFilter: ['class', 'style']
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutoSave);
    } else {
        initAutoSave();
    }
})();
