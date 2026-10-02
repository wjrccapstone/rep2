{{-- Activity Log detail modal — populated by openActivityLogModal(data); see the
     script block in the page that includes this component. --}}
<div id="activity-log-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('activity-log-modal')">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <h2 id="alm-title" class="text-base font-semibold text-slate-900"></h2>
                <p id="alm-subheader" class="mt-1 text-xs text-slate-400"></p>
            </div>
            <button type="button" onclick="closeModal('activity-log-modal')" class="shrink-0 text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="max-h-[65vh] overflow-y-auto px-6 py-5">
            <dl class="grid grid-cols-[120px_1fr] gap-y-3 text-sm">
                <dt class="text-slate-400">Entry no.</dt>
                <dd id="alm-entry-no" class="font-medium text-slate-800"></dd>

                <dt class="text-slate-400">Changed by</dt>
                <dd id="alm-staff" class="font-medium text-slate-800"></dd>

                <dt class="text-slate-400">Module</dt>
                <dd id="alm-module" class="font-medium text-slate-800"></dd>

                <dt id="alm-reason-label" class="hidden text-slate-400">Reason given</dt>
                <dd id="alm-reason" class="hidden font-medium text-slate-800"></dd>
            </dl>

            <div id="alm-changes-wrap" class="mt-5 hidden overflow-hidden rounded-xl ring-1 ring-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-3.5 py-2.5">Field</th>
                            <th class="px-3.5 py-2.5">Before</th>
                            <th class="px-3.5 py-2.5">After</th>
                        </tr>
                    </thead>
                    <tbody id="alm-changes-body" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
            <p class="text-xs text-slate-400">This entry is permanent and cannot be removed.</p>
            <button type="button" onclick="closeModal('activity-log-modal')"
                class="shrink-0 rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function openActivityLogModal(data) {
        document.getElementById('alm-title').textContent = data.title;
        document.getElementById('alm-subheader').textContent = data.subheader;
        document.getElementById('alm-entry-no').textContent = data.entryNo;
        document.getElementById('alm-staff').textContent = data.staffDisplay;
        document.getElementById('alm-module').textContent = data.module;

        const reasonLabel = document.getElementById('alm-reason-label');
        const reasonEl = document.getElementById('alm-reason');
        if (data.reason) {
            reasonEl.textContent = data.reason;
            reasonLabel.classList.remove('hidden');
            reasonEl.classList.remove('hidden');
        } else {
            reasonLabel.classList.add('hidden');
            reasonEl.classList.add('hidden');
        }

        const wrap = document.getElementById('alm-changes-wrap');
        const body = document.getElementById('alm-changes-body');
        body.innerHTML = '';
        if (Array.isArray(data.changes) && data.changes.length > 0) {
            data.changes.forEach(row => {
                const tr = document.createElement('tr');
                const valueClass = row.changed ? 'font-semibold' : 'text-slate-600';
                tr.innerHTML = `
                    <td class="px-3.5 py-2.5 text-slate-600">${row.label}</td>
                    <td class="px-3.5 py-2.5 ${row.changed ? 'font-semibold text-rose-600' : valueClass}">${row.before}</td>
                    <td class="px-3.5 py-2.5 ${row.changed ? 'font-semibold text-teal-600' : valueClass}">${row.after}</td>
                `;
                body.appendChild(tr);
            });
            wrap.classList.remove('hidden');
        } else {
            wrap.classList.add('hidden');
        }

        openModal('activity-log-modal');
    }
</script>
