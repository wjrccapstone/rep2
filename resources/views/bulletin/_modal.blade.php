{{-- Bulletin Board modal. Works with BulletinController (feed, status, store, pin, archive, acknowledge). --}}
@php
    use Illuminate\Support\Facades\Route;
    use App\Models\BulletinNotice;

    $bbUrl = fn (string $name, string $fallback) => Route::has($name) ? route($name) : url($fallback);
    $bbFeedUrl   = $bbUrl('bulletin.feed', '/bulletin/feed');
    $bbStatusUrl = $bbUrl('bulletin.status', '/bulletin/status');
    $bbStoreUrl  = $bbUrl('bulletin.store', '/bulletin');
    $bbIsAdmin   = auth()->user()?->role === 'admin';
@endphp

<div id="bb-modal" class="bb-overlay" hidden aria-hidden="true"
     data-feed="{{ $bbFeedUrl }}" data-status="{{ $bbStatusUrl }}">
    <div class="bb-modal" role="dialog" aria-modal="true" aria-labelledby="bb-modal-title">
        <header class="bb-modal-head">
            <div>
                <h2 id="bb-modal-title">Bulletin Board</h2>
                <p>Updated <span id="bb-synced">just now</span> · refreshes automatically</p>
            </div>
            <div class="bb-head-actions">
                @if ($bbIsAdmin)
                    <button type="button" class="bb-btn-primary" data-bb-compose>+ New announcement</button>
                @endif
                <button type="button" class="bb-close" data-bb-close aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </header>

        @if ($bbIsAdmin)
            <form id="bb-form" class="bb-form" action="{{ $bbStoreUrl }}" hidden>
                <div class="bb-row">
                    <label class="bb-field bb-grow">
                        <span>Title</span>
                        <input name="title" maxlength="120" required>
                    </label>
                </div>
                <label class="bb-field">
                    <span>Message</span>
                    <textarea name="message" rows="3" maxlength="2000" required></textarea>
                </label>
                <div class="bb-row">
                    <label class="bb-field">
                        <span>Priority</span>
                        <select name="priority">
                            @foreach (BulletinNotice::PRIORITIES as $key => $label)
                                <option value="{{ $key }}" @selected($key === 'info')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="bb-field">
                        <span>Category</span>
                        <select name="category">
                            @foreach (BulletinNotice::CATEGORIES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="bb-field">
                        <span>Send to</span>
                        <select name="audience">
                            @foreach (BulletinNotice::AUDIENCES as $key => $label)
                                <option value="{{ $key }}" @selected($key === 'all')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="bb-field">
                        <span>Remove on (optional)</span>
                        <input type="date" name="remove_on" min="{{ now()->toDateString() }}">
                    </label>
                </div>
                <div class="bb-row bb-row-end">
                    <label class="bb-check"><input type="checkbox" name="is_pinned" value="1"> Pin to top</label>
                    <label class="bb-check"><input type="checkbox" name="requires_ack" value="1"> Staff must acknowledge</label>
                    <span class="bb-grow"></span>
                    <button type="button" class="bb-btn-ghost" data-bb-compose>Cancel</button>
                    <button type="submit" class="bb-btn-primary">Post</button>
                </div>
                <p id="bb-form-error" class="bb-form-error" hidden></p>
            </form>
        @endif

        <div id="bb-feed" class="bb-feed">
            <p class="bb-empty">Loading…</p>
        </div>
    </div>
</div>

<div id="bb-toast" class="bb-toast" hidden></div>

<style>
    .bb-overlay { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgb(15 23 42 / .42); backdrop-filter: blur(2px); }
    .bb-overlay[hidden], .bb-form[hidden], [data-bb-section][hidden], .bb-alert[hidden] { display: none; }
    .bb-modal { width: 100%; max-width: 880px; max-height: 90vh; display: flex; flex-direction: column; background: #fff; color: #1e293b; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 20px 45px rgba(15, 23, 42, .2); overflow: hidden; }
    .bb-modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid #e2e8f0; }
    .bb-modal-head h2 { font-size: 1.2rem; font-weight: 700; margin: 0; }
    .bb-modal-head p { font-size: .78rem; color: #64748b; margin: .2rem 0 0; }
    .bb-head-actions { display: flex; align-items: center; gap: .5rem; }
    .bb-close { padding: .4rem; border-radius: 8px; color: #64748b; }
    .bb-close:hover { background: #f1f5f9; color: #0f172a; }
    .bb-btn-primary { padding: .45rem .9rem; border-radius: 8px; background: var(--color-brand-600); color: #fff; font-size: .82rem; font-weight: 600; }
    .bb-btn-primary:hover { background: var(--color-brand-700); }
    .bb-btn-primary:disabled { opacity: .6; }
    .bb-btn-ghost { padding: .45rem .9rem; border-radius: 8px; color: #475569; font-size: .82rem; }
    .bb-btn-ghost:hover { background: #f1f5f9; }
    .bb-feed { overflow-y: auto; padding: 1.2rem 1.4rem 1.4rem; background: #f8fafc; }

    .bb-form { display: flex; flex-direction: column; gap: .7rem; padding: 1rem 1.4rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .bb-row { display: flex; flex-wrap: wrap; gap: .7rem; align-items: flex-end; }
    .bb-row-end { align-items: center; }
    .bb-grow { flex: 1; }
    .bb-field { display: flex; flex-direction: column; gap: .25rem; font-size: .75rem; color: #475569; }
    .bb-field input, .bb-field textarea, .bb-field select { padding: .45rem .6rem; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #1e293b; font-size: .85rem; }
    .bb-field input:focus, .bb-field textarea:focus, .bb-field select:focus { outline: none; border-color: var(--color-brand-400); }
    .bb-check { display: flex; align-items: center; gap: .35rem; font-size: .8rem; color: #334155; }
    .bb-form-error { color: var(--color-accent-rose, #ef4444); font-size: .8rem; margin: 0; }

    .bb-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: .7rem; margin-bottom: 1rem; }
    .bb-stat { display: flex; flex-direction: column; padding: .8rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    .bb-stat-num { font-size: 1.5rem; font-weight: 700; }
    .bb-stat-label { font-size: .75rem; color: #64748b; }
    .bb-red { color: var(--color-accent-rose, #ef4444); } .bb-amber { color: var(--color-accent-amber, #f59e0b); }

    .bb-tabs, .bb-filters { display: flex; flex-wrap: wrap; gap: .4rem; }
    .bb-tabs { margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: .6rem; }
    .bb-tabs button, .bb-filters button { padding: .35rem .8rem; border-radius: 8px; font-size: .8rem; color: #475569; background: #f1f5f9; }
    .bb-tabs button.is-active, .bb-filters button.is-active { background: var(--color-brand-600); color: #fff; }

    .bb-body section + section { margin-top: 1.5rem; }
    .bb-h { font-size: .95rem; font-weight: 600; margin: 0 0 .7rem; }
    .bb-alert-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .5rem; margin-bottom: .7rem; }
    .bb-alert-head .bb-h { margin: 0; }

    .bb-card { padding: 1rem; margin-bottom: .7rem; border: 1px solid #e2e8f0; border-left: 3px solid var(--color-brand-400); border-radius: 8px; background: #fff; }
    .bb-p-urgent { border-left-color: var(--color-accent-rose, #ef4444); }
    .bb-p-important { border-left-color: var(--color-accent-amber, #f59e0b); }
    .bb-chips { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-bottom: .5rem; font-size: .72rem; }
    .bb-chip, .bb-pin { display: inline-flex; align-items: center; gap: .25rem; padding: .15rem .55rem; border-radius: 6px; background: var(--color-brand-50); color: var(--color-brand-700); }
    .bb-chip-urgent { background: #fef2f2; color: #b91c1c; }
    .bb-chip-important { background: #fffbeb; color: #b45309; }
    .bb-chip-muted { background: #f1f5f9; color: #475569; }
    .bb-pin { background: #f0fdfa; color: #0f766e; }
    .bb-to { color: #64748b; }
    .bb-card-title { font-weight: 600; margin: 0 0 .3rem; }
    .bb-card-text { font-size: .875rem; color: #475569; margin: 0; }
    .bb-foot { margin-top: .8rem; }
    .bb-progress { margin-bottom: .6rem; }
    .bb-progress-label { display: flex; justify-content: space-between; font-size: .75rem; color: #64748b; margin-bottom: .25rem; }
    .bb-bar { height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
    .bb-bar > div { height: 100%; background: var(--color-accent-teal, #14b8a6); }
    .bb-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .8rem; font-size: .75rem; color: #64748b; }
    .bb-meta > span:first-child { margin-right: auto; }
    .bb-link { color: var(--color-brand-600); } .bb-link:hover { color: var(--color-brand-700); text-decoration: underline; }
    .bb-link-danger { color: var(--color-accent-rose, #ef4444); }
    .bb-ack { padding: .35rem .8rem; border-radius: 8px; background: var(--color-brand-600); color: #fff; font-weight: 600; }
    .bb-ack:hover { background: var(--color-brand-700); }
    .bb-acked { color: var(--color-accent-teal, #14b8a6); font-weight: 600; }

    .bb-list { display: flex; flex-direction: column; gap: .4rem; }
    .bb-alert { display: flex; align-items: center; gap: .8rem; padding: .7rem .9rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; transition: background .15s, border-color .15s; }
    .bb-alert:hover { background: var(--color-brand-50); border-color: var(--color-brand-200); }
    .bb-alert-icon { color: var(--color-brand-600); display: flex; }
    .bb-alert-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .bb-alert-title { display: flex; align-items: center; gap: .45rem; font-size: .875rem; font-weight: 500; }
    .bb-alert-meta { font-size: .75rem; color: #64748b; }
    .bb-alert-action { font-size: .78rem; color: var(--color-brand-600); white-space: nowrap; }
    .bb-sev { width: 8px; height: 8px; border-radius: 50%; background: var(--color-brand-400); flex-shrink: 0; }
    .bb-sev-urgent { background: var(--color-accent-rose, #ef4444); }
    .bb-sev-important { background: var(--color-accent-amber, #f59e0b); }
    .bb-empty { text-align: center; padding: 1.2rem; color: #64748b; font-size: .875rem; }
    .bb-note { margin-top: .8rem; font-size: .72rem; color: #94a3b8; }

    [data-bb-open] { position: relative; }
    .bb-badge { position: absolute; top: -6px; right: -6px; min-width: 20px; height: 20px; padding: 0 5px; border-radius: 999px; background: var(--color-brand-600); color: #fff; font-size: .7rem; font-weight: 700; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 2px #fff; }
    .bb-badge.is-urgent { background: var(--color-accent-rose, #ef4444); animation: bb-pulse 1.6s infinite; }
    @keyframes bb-pulse { 50% { box-shadow: 0 0 0 2px #fff, 0 0 0 6px rgba(239, 68, 68, .24); } }

    .bb-toast { position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%); z-index: 70; padding: .7rem 1.1rem; border-radius: 8px; background: #fff; color: #1e293b; font-size: .85rem; box-shadow: 0 10px 25px rgba(15, 23, 42, .16); border: 1px solid #e2e8f0; }
    .bb-toast.is-error { border-color: var(--color-accent-rose, #ef4444); color: #fecaca; }

    @media (max-width: 640px) { .bb-summary { grid-template-columns: repeat(2, 1fr); } }
</style>

<script>
(() => {
    const modal  = document.getElementById('bb-modal');
    const feed   = document.getElementById('bb-feed');
    const form   = document.getElementById('bb-form');
    const synced = document.getElementById('bb-synced');
    const toastEl = document.getElementById('bb-toast');
    const csrf   = document.querySelector('meta[name="csrf-token"]')?.content;
    const headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };

    let activeTab = 'all', activeMod = 'all';
    let feedTimer = null, autoOpened = false, toastTimer = null;

    function toast(msg, isError = false) {
        toastEl.textContent = msg;
        toastEl.classList.toggle('is-error', isError);
        toastEl.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.hidden = true, 3000);
    }

    /* ---------- Feed ---------- */
    async function loadFeed() {
        try {
            const res = await fetch(modal.dataset.feed + '?mark_read=1', { headers });
            if (!res.ok) throw new Error(res.status);
            const data = await res.json();
            feed.innerHTML = data.html;
            synced.textContent = data.synced_at;
            applyTab();
            applyModule();
        } catch (e) {
            if (!feed.querySelector('.bb-body')) {
                feed.innerHTML = '<p class="bb-empty">Couldn\'t load the bulletin board. Please try again.</p>';
            }
            console.error('Bulletin feed failed:', e);
        }
    }

    function applyTab() {
        feed.querySelectorAll('[data-bb-tab]').forEach(b => b.classList.toggle('is-active', b.dataset.bbTab === activeTab));
        feed.querySelectorAll('[data-bb-section]').forEach(s => s.hidden = activeTab !== 'all' && s.dataset.bbSection !== activeTab);
    }

    function applyModule() {
        feed.querySelectorAll('[data-bb-mod]').forEach(b => b.classList.toggle('is-active', b.dataset.bbMod === activeMod));
        let visible = 0;
        feed.querySelectorAll('[data-bb-module]').forEach(a => {
            a.hidden = activeMod !== 'all' && a.dataset.bbModule !== activeMod;
            if (!a.hidden) visible++;
        });
        const empty = feed.querySelector('#bb-alerts-empty');
        if (empty) empty.hidden = visible > 0;
    }

    /* ---------- Badge (status check every 30s) ---------- */
    async function checkStatus() {
        try {
            const res = await fetch(modal.dataset.status, { headers });
            if (!res.ok) return;
            const s = await res.json();
            const count = (s.unread || 0) + (s.alert_count || 0);

            document.querySelectorAll('[data-bb-open]').forEach(btn => {
                let badge = btn.querySelector('.bb-badge');
                if (!count) return badge?.remove();
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'bb-badge';
                    btn.appendChild(badge);
                }
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.toggle('is-urgent', s.urgent > 0 || s.must_ack > 0);
            });

            // Urgent notice this staff member hasn't acknowledged: open the board once per page load
            if (s.must_ack > 0 && modal.hidden && !autoOpened) {
                autoOpened = true;
                open();
            }
        } catch (e) { /* ignore network hiccups */ }
    }

    /* ---------- Open / close ---------- */
    function open() {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        loadFeed().then(checkStatus);
        clearInterval(feedTimer);
        feedTimer = setInterval(loadFeed, 15000);
    }

    function close() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        clearInterval(feedTimer);
        if (form) form.hidden = true;
        checkStatus();
    }

    window.openBulletin = open;

    /* ---------- Clicks ---------- */
    document.addEventListener('click', async (e) => {
        if (e.target.closest('[data-bb-open]')) { e.preventDefault(); return open(); }
        if (modal.hidden) return;

        if (e.target === modal || e.target.closest('[data-bb-close]')) return close();

        if (e.target.closest('[data-bb-compose]') && form) {
            form.hidden = !form.hidden;
            if (!form.hidden) form.querySelector('[name="title"]').focus();
            return;
        }

        const tab = e.target.closest('[data-bb-tab]');
        if (tab) { activeTab = tab.dataset.bbTab; return applyTab(); }

        const mod = e.target.closest('[data-bb-mod]');
        if (mod) { activeMod = mod.dataset.bbMod; return applyModule(); }

        const post = e.target.closest('[data-bb-post]');
        if (post) {
            if (post.dataset.confirm && !confirm(post.dataset.confirm)) return;
            post.disabled = true;
            try {
                const res = await fetch(post.dataset.bbPost, { method: 'POST', headers: { ...headers, 'X-CSRF-TOKEN': csrf } });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || res.status);
                toast(data.message || 'Done.');
                await loadFeed();
                checkStatus();
            } catch (err) {
                post.disabled = false;
                toast('Something went wrong. Please try again.', true);
                console.error('Bulletin action failed:', err);
            }
        }
    });

    /* ---------- New announcement form (admin) ---------- */
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const errorEl = document.getElementById('bb-form-error');
        const submit  = form.querySelector('[type="submit"]');
        errorEl.hidden = true;
        submit.disabled = true;

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { ...headers, 'X-CSRF-TOKEN': csrf },
                body: new FormData(form),
            });
            const data = await res.json().catch(() => ({}));

            if (res.status === 422) {
                errorEl.textContent = Object.values(data.errors || {}).flat().join(' ') || data.message;
                errorEl.hidden = false;
                return;
            }
            if (!res.ok) throw new Error(data.message || res.status);

            form.reset();
            form.hidden = true;
            toast(data.message || 'Announcement posted.');
            await loadFeed();
        } catch (err) {
            errorEl.textContent = 'Couldn\'t post the announcement. Please try again.';
            errorEl.hidden = false;
            console.error('Bulletin post failed:', err);
        } finally {
            submit.disabled = false;
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) close();
    });

    checkStatus();
    setInterval(checkStatus, 15000);
})();
</script>