<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\Feedback;
use App\Models\GobikerMessage;
use App\Models\ReplicationRequest;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Models\VolunteerApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * What the admin panel keeps current without a page reload (resources/js/admin/live-updates.js polls it):
 * the sidebar counts, and what came in since the page last looked (new sign-ups, applications, messages…).
 *
 * "Since" is a cursor of the newest id seen per kind, so nothing depends on the browser's or server's clock.
 * Emergencies and SOS requests are not here: resources/js/admin/emergency-alert.js handles those, with the alarm.
 */
class AdminLiveFeed
{
    /** At most this many items per kind are sent; the rest are only counted. */
    public const ITEMS_PER_KIND = 3;

    /** Sidebar counts, keyed by the badge name in admin/partials/sidebar.blade.php. */
    public static function counts(): array
    {
        return [
            'users' => User::pendingApproval()->count(),
            'volunteers' => VolunteerApplication::where('application_status', 'pending')->count(),
            'appointments' => Appointment::where('status', 'pending')->count(),
            'messages' => ContactMessage::where('is_read', false)->count(),
            'gobiker-messages' => GobikerMessage::where('is_read', false)->count(),
            'training' => TrainingRequest::where('status', 'pending')->count(),
            'chapters' => ReplicationRequest::where('status', 'pending')->count(),
        ];
    }

    /**
     * Counts, the newest id per kind, and (for each kind in $after) what is newer than the id given there.
     *
     * @param  array<string, mixed>  $after  kind => last id the page has seen
     */
    public static function snapshot(array $after = []): array
    {
        $cursor = [];
        $new = [];

        foreach (self::kinds() as $kind => $feed) {
            $latest = (int) $feed['query']()->max('id');
            $cursor[$kind] = $latest;

            if (! array_key_exists($kind, $after) || ! is_numeric($after[$kind])) {
                continue;
            }
            $since = (int) $after[$kind];
            if ($latest <= $since) {
                continue;
            }

            $newer = $feed['query']()->where('id', '>', $since)->where('id', '<=', $latest);
            $total = (clone $newer)->count();
            $items = $newer->with($feed['with'] ?? [])
                ->orderByDesc('id')
                ->limit(self::ITEMS_PER_KIND)
                ->get()
                ->map(fn (Model $model) => ['id' => $model->getKey()] + $feed['item']($model))
                ->all();

            $new[$kind] = [
                'total' => $total,
                'title' => $total === 1 ? $feed['one'] : str_replace(':count', number_format($total), $feed['many']),
                'index_url' => route($feed['index']),
                'items' => $items,
            ];
        }

        return [
            'counts' => self::counts(),
            'cursor' => $cursor,
            'new' => (object) $new,
        ];
    }

    /** The list page for each kind, so the page can tell the admin "2 new — show them". */
    public static function indexRoutes(): array
    {
        return array_map(fn (array $feed) => $feed['index'], self::kinds());
    }

    private static function kinds(): array
    {
        return [
            'users' => [
                'query' => fn (): Builder => User::query()->where('role', '!=', 'Admin')->where('is_admin', false),
                'item' => fn (User $user) => [
                    'name' => $user->name,
                    'detail' => $user->role === 'GoBiker' && $user->status === 'Inactive'
                        ? 'GoBiker, waiting for your approval'
                        : User::roleLabelFor($user->role),
                    'url' => route('admin.operations.users.show', $user),
                ],
                'one' => 'New account',
                'many' => ':count new accounts',
                'index' => 'admin.operations.users.index',
            ],
            'volunteers' => [
                'query' => fn (): Builder => VolunteerApplication::query(),
                'item' => fn (VolunteerApplication $application) => [
                    'name' => trim("{$application->first_name} {$application->last_name}"),
                    'detail' => collect([$application->barangay, $application->municipality])->filter()->join(', '),
                    'url' => route('admin.operations.volunteers.show', $application),
                ],
                'one' => 'New volunteer application',
                'many' => ':count new volunteer applications',
                'index' => 'admin.operations.volunteers.index',
            ],
            'messages' => [
                'query' => fn (): Builder => ContactMessage::query(),
                'item' => fn (ContactMessage $message) => [
                    'name' => $message->name,
                    'detail' => Str::limit(
                        in_array(strtolower($message->subject ?? ''), ['others', 'other'], true) && $message->other_concern
                            ? $message->other_concern
                            : ucfirst((string) $message->subject),
                        60
                    ),
                    'url' => route('admin.operations.messages.index'),
                ],
                'one' => 'New message',
                'many' => ':count new messages',
                'index' => 'admin.operations.messages.index',
            ],
            'gobiker-messages' => [
                'query' => fn (): Builder => GobikerMessage::query(),
                'with' => ['sender:id,name'],
                'item' => fn (GobikerMessage $message) => [
                    'name' => $message->sender?->name ?? 'A GoBiker',
                    'detail' => Str::limit((string) $message->message, 60),
                    'url' => route('admin.operations.gobiker-messages.index'),
                ],
                'one' => 'New GoBiker message',
                'many' => ':count new GoBiker messages',
                'index' => 'admin.operations.gobiker-messages.index',
            ],
            'appointments' => [
                'query' => fn (): Builder => Appointment::query(),
                'with' => ['resident:id,name'],
                'item' => fn (Appointment $appointment) => [
                    'name' => $appointment->resident?->name ?? 'A resident',
                    'detail' => collect(['Home visit', $appointment->barangay])->filter()->join(', '),
                    'url' => route('admin.operations.appointments.show', $appointment),
                ],
                'one' => 'New home-visit request',
                'many' => ':count new home-visit requests',
                'index' => 'admin.operations.appointments.index',
            ],
            'training' => [
                'query' => fn (): Builder => TrainingRequest::query(),
                'item' => fn (TrainingRequest $request) => [
                    'name' => $request->name,
                    'detail' => 'Training request',
                    'url' => route('admin.operations.training-requests.index'),
                ],
                'one' => 'New training request',
                'many' => ':count new training requests',
                'index' => 'admin.operations.training-requests.index',
            ],
            'chapters' => [
                'query' => fn (): Builder => ReplicationRequest::query(),
                'item' => fn (ReplicationRequest $inquiry) => [
                    'name' => $inquiry->organization ?: $inquiry->contact_name,
                    'detail' => collect([$inquiry->municipality, $inquiry->province])->filter()->join(', '),
                    'url' => route('admin.operations.chapter-inquiries.show', $inquiry),
                ],
                'one' => 'New chapter inquiry',
                'many' => ':count new chapter inquiries',
                'index' => 'admin.operations.chapter-inquiries.index',
            ],
            // Only once the payment has gone through: a donation row is made before the donor pays.
            'donations' => [
                'query' => fn (): Builder => Donation::query()->where('status', 'paid'),
                'item' => fn (Donation $donation) => [
                    'name' => $donation->donor_type === 'organization' && $donation->org_name
                        ? $donation->org_name
                        : (trim("{$donation->first_name} {$donation->last_name}") ?: 'A donor'),
                    'detail' => $donation->formatted_total,
                    'url' => route('admin.operations.donations.index'),
                ],
                'one' => 'New donation',
                'many' => ':count new donations',
                'index' => 'admin.operations.donations.index',
            ],
            'feedback' => [
                'query' => fn (): Builder => Feedback::query()->whereNotNull('rating'),
                'with' => ['resident:id,name'],
                'item' => fn (Feedback $feedback) => [
                    'name' => $feedback->resident?->name ?? 'A resident',
                    'detail' => $feedback->rating.' of 5 stars',
                    'url' => $feedback->gobiker_id
                        ? route('admin.operations.feedback.show', $feedback->gobiker_id)
                        : route('admin.operations.feedback.index'),
                ],
                'one' => 'New feedback',
                'many' => ':count new feedback ratings',
                'index' => 'admin.operations.feedback.index',
            ],
        ];
    }
}
