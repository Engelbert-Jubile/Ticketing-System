<?php

namespace App\Domains\Project\Models;

use App\Models\Project as BaseProject;

/**
 * @property int $id
 * @property string|null $uuid
 * @property string|null $project_no
 * @property int|null $ticket_id
 * @property string|null $status_id
 * @property string $title
 * @property string|null $description
 * @property string|null $planning
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int|null $created_by
 * @property int|null $requester_id FK to users.id - requester/pengguna yang meminta proyek
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectAction> $actions
 * @property-read int|null $actions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectCost> $costs
 * @property-read int|null $costs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectDeliverable> $deliverables
 * @property-read int|null $deliverables_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectPic> $pics
 * @property-read int|null $pics_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectRiskAnalysis> $risks
 * @property-read int|null $risks_count
 * @property-read \App\Models\Ticket|null $ticket
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project wherePlanning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProjectNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereRequesterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUuid($value)
 *
 * @mixin \Eloquent
 */
class Project extends BaseProject
{
    /**
     * Tambahan hook di atas model dasar:
     * - parent::booted() sudah mengisi UUID.
     * - Di sini kita isi project_no kalau belum ada.
     */
    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (self $model) {
            if (empty($model->project_no)) {
                $model->project_no = static::nextNumber();
            }
        });
    }

    /** Format: PRJYYYYNNNN (mis. PRJ20250001) — reset tiap tahun */
    public static function nextNumber(): string
    {
        $prefix = 'PRJ'.now()->format('Y');

        $last = static::query()
            ->where('project_no', 'like', $prefix.'%')
            ->orderByDesc('project_no')
            ->value('project_no');

        $seq = $last ? ((int) substr($last, -4) + 1) : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
