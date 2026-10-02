{{-- Bulletin Board trigger button. The modal itself is in bulletin/_modal.blade.php (loaded by app-layout). --}}
<button type="button" class="bb-trigger" data-bb-open aria-haspopup="dialog" aria-controls="bb-modal">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="8" y="2" width="8" height="4" rx="1"/>
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
        <path d="M9 12h6M9 16h4"/>
    </svg>
    <span>Bulletin Board</span>
</button>

<style>
    .bb-actions { display: flex; align-items: center; gap: 12px; }

    /* Same look as the "All Time" dropdown */
    .bb-trigger {
        position: relative; display: inline-flex; align-items: center; gap: 8px;
        height: 38px; padding: 0 14px; border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, .20); background: rgba(255, 255, 255, .10); color: #fff;
        font: inherit; font-size: 14px; font-weight: 500; cursor: pointer; transition: background .15s;
    }
    .bb-trigger:hover { background: rgba(255, 255, 255, .16); }
    .bb-trigger:focus-visible { outline: 2px solid #5b9bff; outline-offset: 2px; }
    .bb-trigger svg { width: 18px; height: 18px; flex-shrink: 0; }
</style>