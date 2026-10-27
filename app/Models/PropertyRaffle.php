<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PropertyRaffle extends Model
{
    use HasFactory;

    protected $fillable = [
        'advertisement_id', 'slug', 'title', 'subtitle', 'description', 'terms',
        'start_at', 'end_at', 'draw_at', 'drawn_at', 'capacity', 'ticket_price',
        'max_tickets_per_user', 'status', 'hero_image', 'panorama_image',
        'virtual_tour_url', 'live_enabled', 'live_url', 'live_start_at',
        'highlights', 'ticket_design_id', 'winner_ticket_id', 'draw_operator_id', 'winner_announcement'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'draw_at' => 'datetime',
        'drawn_at' => 'datetime',
        'live_start_at' => 'datetime',
        'live_enabled' => 'boolean',
        'highlights' => 'array',
        'capacity' => 'integer',
        'ticket_price' => 'integer',
        'max_tickets_per_user' => 'integer',
    ];

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function orders()
    {
        return $this->hasMany(RaffleOrder::class, 'raffle_id');
    }

    public function tickets()
    {
        return $this->hasMany(RaffleTicket::class, 'raffle_id');
    }

    public function ticketDesign()
    {
        return $this->belongsTo(RaffleTicketDesign::class, 'ticket_design_id');
    }

    public function winnerTicket()
    {
        return $this->belongsTo(RaffleTicket::class, 'winner_ticket_id');
    }

    public function drawOperator()
    {
        return $this->belongsTo(Admin::class, 'draw_operator_id');
    }

    public function soldTicketsCount()
    {
        return (int) $this->tickets()->whereIn('status', ['active', 'winner', 'not_winner'])->count();
    }

    public function reservedTicketsCount()
    {
        return (int) $this->orders()
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('ticket_count');
    }

    public function remainingCapacity($includeReservations = true)
    {
        $used = $this->soldTicketsCount();
        if ($includeReservations) {
            $used += $this->reservedTicketsCount();
        }
        return max(0, (int) $this->capacity - $used);
    }

    public function computedStatus()
    {
        if ($this->status === 'cancelled') return 'cancelled';
        if ($this->status === 'draft') return 'draft';
        if ($this->winner_ticket_id || $this->status === 'completed') return 'completed';

        $now = Carbon::now();
        if ($now->lt($this->start_at)) return 'upcoming';
        if ($now->gt($this->end_at)) return 'ended';
        if ($this->remainingCapacity(false) <= 0) return 'sold_out';
        return 'active';
    }
}
