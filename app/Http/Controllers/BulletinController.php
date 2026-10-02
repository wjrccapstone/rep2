<?php

namespace App\Http\Controllers;

use App\Models\BulletinNotice;
use App\Models\BulletinRead;
use App\Models\User;
use App\Services\BulletinAlerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulletinController extends Controller
{
    public function __construct(private BulletinAlerts $alerts)
    {
    }

    /** Full board content (HTML) – loaded when the modal opens and every 15s while open. */
    public function feed(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $user->role === 'admin';

        $ids = BulletinNotice::active()->visibleTo($user)->pluck('id');

        // Opening the board marks everything as "seen" for this user
        if ($request->boolean('mark_read') && $ids->isNotEmpty()) {
            $now = now();
            BulletinRead::insertOrIgnore($ids->map(fn ($id) => [
                'bulletin_notice_id' => $id,
                'user_id'            => $user->id,
                'read_at'            => $now,
                'created_at'         => $now,
                'updated_at'         => $now,
            ])->all());
            $user->forceFill(['bulletin_seen_at' => $now])->saveQuietly();
        }

        $notices = BulletinNotice::whereIn('id', $ids)
            ->with('poster:id,name')
            ->withCount([
                'reads as seen_count' => fn ($q) => $q->whereNotNull('read_at'),
                'reads as ack_count'  => fn ($q) => $q->whereNotNull('acknowledged_at'),
            ])
            ->orderByDesc('is_pinned')
            ->orderByRaw("FIELD(priority, 'urgent', 'important', 'info')")
            ->latest()
            ->get();

        $myReads = BulletinRead::where('user_id', $user->id)
            ->whereIn('bulletin_notice_id', $ids)
            ->get()
            ->keyBy('bulletin_notice_id');

        // How many active staff each audience has (for "seen by X of Y")
        $roleTotals    = User::where('status', 'active')->selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');
        $audienceTotal = fn (string $aud) => $aud === 'all' ? (int) $roleTotals->sum() : (int) ($roleTotals[$aud] ?? 0);

        $alerts = $this->alerts->for($user);

        $pendingAck = $isAdmin
            ? $notices->where('requires_ack', true)->sum(fn ($n) => max(0, $audienceTotal($n->audience) - $n->ack_count))
            : $notices->where('requires_ack', true)->filter(fn ($n) => empty($myReads->get($n->id)?->acknowledged_at))->count();

        $summary = [
            'urgent' => $alerts->where('priority', 'urgent')->count() + $notices->where('priority', 'urgent')->count(),
            'jobs'   => $alerts->where('module', 'jobs')->count(),
            'stock'  => $alerts->where('module', 'stock')->count(),
            'ack'    => $pendingAck,
        ];

        return response()->json([
            'html'      => view('bulletin._feed', compact('notices', 'myReads', 'audienceTotal', 'alerts', 'summary', 'isAdmin'))->render(),
            'urgent'    => $alerts->where('priority', 'urgent')->count(),
            'synced_at' => now()->format('g:i:s A'),
        ]);
    }

    /** Lightweight check every 15s for the header badge and pop-up notifications. */
    public function status(Request $request)
    {
        $user    = $request->user();
        $visible = BulletinNotice::active()->visibleTo($user);

        $unread = (clone $visible)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        // Urgent notices this staff member still has to acknowledge (opens the board automatically)
        $mustAck = $user->role === 'admin' ? 0 : (clone $visible)
            ->where('requires_ack', true)
            ->where('priority', 'urgent')
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id)->whereNotNull('acknowledged_at'))
            ->count();

        $alerts = $this->alerts->for($user);

        return response()->json([
            'unread'   => $unread,
            'alert_count' => $alerts->count(),
            'urgent'   => $alerts->where('priority', 'urgent')->count(),
            'must_ack' => $mustAck,
        ]);
    }

    public function store(Request $request)
    {
        $this->adminOnly($request);

        $data = $request->validate([
            'title'        => 'required|string|max:120',
            'message'      => 'required|string|max:2000',
            'priority'     => 'required|in:' . implode(',', array_keys(BulletinNotice::PRIORITIES)),
            'category'     => 'required|in:' . implode(',', array_keys(BulletinNotice::CATEGORIES)),
            'audience'     => 'required|in:' . implode(',', array_keys(BulletinNotice::AUDIENCES)),
            'remove_on'    => 'nullable|date|after_or_equal:today',
            'is_pinned'    => 'nullable|boolean',
            'requires_ack' => 'nullable|boolean',
        ]);

        $notice = BulletinNotice::create(array_merge($data, [
            'tab'          => 'general',
            'is_important' => $data['priority'] !== 'info',
            'is_pinned'    => $request->boolean('is_pinned'),
            'requires_ack' => $request->boolean('requires_ack'),
            'posted_by'    => $request->user()->id,
        ]));

        $this->log($request, 'posted', $notice);

        return response()->json([
            'message' => 'Announcement posted to ' . BulletinNotice::AUDIENCES[$notice->audience] . '.',
        ]);
    }

    public function archive(Request $request, BulletinNotice $notice)
    {
        $this->adminOnly($request);
        $notice->update(['archived_at' => now()]);
        $this->log($request, 'archived', $notice);

        return response()->json(['message' => 'Announcement archived.']);
    }

    public function pin(Request $request, BulletinNotice $notice)
    {
        $this->adminOnly($request);
        $notice->update(['is_pinned' => ! $notice->is_pinned]);

        return response()->json(['message' => $notice->is_pinned ? 'Pinned to top.' : 'Unpinned.']);
    }

    public function acknowledge(Request $request, BulletinNotice $notice)
    {
        BulletinRead::updateOrCreate(
            ['bulletin_notice_id' => $notice->id, 'user_id' => $request->user()->id],
            ['read_at' => now(), 'acknowledged_at' => now()]
        );

        return response()->json(['message' => 'Thanks! Marked as acknowledged.']);
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }

    /** Writes to your existing activity_logs table. */
    private function log(Request $request, string $action, BulletinNotice $notice): void
    {
        DB::table('activity_logs')->insert([
            'user_id'    => $request->user()->id,
            'module'     => 'bulletin',
            'action'     => $action,
            'reference'  => 'BN-' . str_pad($notice->id, 3, '0', STR_PAD_LEFT),
            'title'      => $notice->title,
            'detail'     => 'To: ' . (BulletinNotice::AUDIENCES[$notice->audience] ?? 'All staff'),
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}