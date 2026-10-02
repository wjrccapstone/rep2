@php
    $svg = fn ($paths) => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
    $icons = [
        'jobs'  => $svg('<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>'),
        'stock' => $svg('<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>'),
        'parts' => $svg('<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>'),
    ];
    $pinIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z"/></svg>';
    $moduleLabels = ['jobs' => 'Job Orders', 'stock' => 'Inventory', 'parts' => 'Parts Out'];
@endphp

{{-- Summary strip --}}
<div class="bb-summary">
    <div class="bb-stat"><span class="bb-stat-num bb-red">{{ $summary['urgent'] }}</span><span class="bb-stat-label">Urgent items</span></div>
    <div class="bb-stat"><span class="bb-stat-num">{{ $summary['jobs'] }}</span><span class="bb-stat-label">Job order alerts</span></div>
    <div class="bb-stat"><span class="bb-stat-num">{{ $summary['stock'] }}</span><span class="bb-stat-label">Stock alerts</span></div>
    <div class="bb-stat"><span class="bb-stat-num bb-amber">{{ $summary['ack'] }}</span><span class="bb-stat-label">{{ $isAdmin ? 'Unacknowledged' : 'Need your OK' }}</span></div>
</div>

{{-- Tabs --}}
<div class="bb-tabs">
    <button type="button" data-bb-tab="all">All</button>
    <button type="button" data-bb-tab="notices">Announcements ({{ $notices->count() }})</button>
    <button type="button" data-bb-tab="alerts">System alerts ({{ $alerts->count() }})</button>
</div>

<div class="bb-body">

    {{-- ANNOUNCEMENTS --}}
    <section data-bb-section="notices">
        <h3 class="bb-h">Announcements from admin</h3>

        @forelse ($notices as $n)
            @php
                $total = $audienceTotal($n->audience);
                $done  = $n->requires_ack ? $n->ack_count : $n->seen_count;
                $pct   = $total ? min(100, (int) round($done / $total * 100)) : 0;
                $mine  = $myReads->get($n->id);
            @endphp

            <article class="bb-card bb-p-{{ $n->priority }}">
                <div class="bb-chips">
                    @if ($n->is_pinned)
                        <span class="bb-pin">{!! $pinIcon !!} Pinned</span>
                    @endif
                    <span class="bb-chip bb-chip-{{ $n->priority }}">{{ \App\Models\BulletinNotice::PRIORITIES[$n->priority] ?? 'Info' }}</span>
                    <span class="bb-chip bb-chip-muted">{{ \App\Models\BulletinNotice::CATEGORIES[$n->category] ?? 'Announcement' }}</span>
                    <span class="bb-to">To: {{ \App\Models\BulletinNotice::AUDIENCES[$n->audience] ?? 'All staff' }}</span>
                </div>

                <h4 class="bb-card-title">{{ $n->title }}</h4>
                <p class="bb-card-text">{!! nl2br(e($n->message)) !!}</p>

                <div class="bb-foot">
                    @if ($isAdmin)
                        <div class="bb-progress">
                            <div class="bb-progress-label">
                                <span>{{ $n->requires_ack ? 'Acknowledged' : 'Seen' }} by {{ min($done, $total) }} of {{ $total }} staff</span>
                                <span>{{ $pct }}%</span>
                            </div>
                            <div class="bb-bar"><div style="width: {{ $pct }}%"></div></div>
                        </div>
                    @endif

                    <div class="bb-meta">
                        <span>
                            {{ $n->poster->name ?? 'Admin' }} · {{ $n->created_at->format('M j, g:i A') }}
                            @if ($n->remove_on) · until {{ $n->remove_on->format('M j') }} @endif
                        </span>

                        @if ($isAdmin)
                            <button type="button" class="bb-link" data-bb-post="{{ route('bulletin.pin', $n) }}">
                                {{ $n->is_pinned ? 'Unpin' : 'Pin' }}
                            </button>
                            <button type="button" class="bb-link bb-link-danger"
                                    data-bb-post="{{ route('bulletin.archive', $n) }}"
                                    data-confirm="Archive &quot;{{ $n->title }}&quot;? Staff will no longer see it.">
                                Archive
                            </button>
                        @elseif ($n->requires_ack)
                            @if ($mine?->acknowledged_at)
                                <span class="bb-acked">✓ Acknowledged</span>
                            @else
                                <button type="button" class="bb-ack" data-bb-post="{{ route('bulletin.acknowledge', $n) }}">I've read this</button>
                            @endif
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p class="bb-empty">No active announcements.</p>
        @endforelse
    </section>

    {{-- SYSTEM ALERTS --}}
    <section data-bb-section="alerts">
        <div class="bb-alert-head">
            <h3 class="bb-h">Automatic system alerts</h3>
            <div class="bb-filters">
                <button type="button" data-bb-mod="all">All</button>
                @foreach ($moduleLabels as $key => $label)
                    @php $count = $alerts->where('module', $key)->count(); @endphp
                    @if ($count)
                        <button type="button" data-bb-mod="{{ $key }}">{{ $label }} ({{ $count }})</button>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="bb-list">
            @foreach ($alerts as $a)
                <a href="{{ $a['url'] }}" class="bb-alert" data-bb-module="{{ $a['module'] }}">
                    <span class="bb-alert-icon">{!! $icons[$a['module']] !!}</span>
                    <span class="bb-alert-main">
                        <span class="bb-alert-title">
                            <span class="bb-sev bb-sev-{{ $a['priority'] }}"></span>{{ $a['title'] }}
                        </span>
                        <span class="bb-alert-meta">{{ $moduleLabels[$a['module']] }} · {{ $a['meta'] }}</span>
                    </span>
                    <span class="bb-alert-action">{{ $a['action'] }} ›</span>
                </a>
            @endforeach
            <p id="bb-alerts-empty" class="bb-empty" hidden>✓ All clear. Nothing needs attention here.</p>
        </div>

        <p class="bb-note">Alerts are generated from Job Orders, Product Inventory and Parts Out, and disappear on their own once the issue is fixed.</p>
    </section>
</div>