<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Class Debt
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property float $amount
 * @property float $amount_paid
 * @property float $amount_remaining
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $maturity_date
 * @property string $status
 * @property string|null $note
 * @property float|null $interest_rate
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Debt active()
 * @method static \Illuminate\Database\Eloquent\Builder|Debt paid()
 * @method static \Illuminate\Database\Eloquent\Builder|Debt overdue()
 */
class Debt extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status tetap untuk tabel debt
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAID = 'paid';
    public const STATUS_DEFAULTED = 'defaulted';
    public const STATUS_RENEGOTIATED = 'renegotiated';

    /**
     * Atribut yang dapat diisi (mass assignable)
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'amount',
        'amount_paid',
        'start_date',
        'maturity_date',
        'status',
        'note',
        'interest_rate',
    ];

    /**
     * Atribut yang harus dikonversi
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer', // FIXED: Use integer for financial calculations
        'amount_paid' => 'integer',
        'amount_remaining' => 'integer',
        'interest_rate' => 'decimal:2',
        'start_date' => 'date',
        'maturity_date' => 'date',
    ];

    /**
     * Validation rules for debts
     */
    public static function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'amount_paid' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'maturity_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:active,paid,defaulted,renegotiated'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Atribut yang harus dimutasi menjadi tanggal
     *
     * @var array<int, string>
     */
    protected $dates = [
        'start_date',
        'maturity_date',
    ];

    /**
     * Accessor untuk menghitung persentase pembayaran
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function paymentPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount > 0 ? round(($this->amount_paid / $this->amount) * 100, 2) : 0,
        );
    }

    /**
     * Accessor untuk mengecek apakah hutang sudah jatuh tempo
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === self::STATUS_ACTIVE && $this->maturity_date < now(),
        );
    }

    /**
     * Scope query untuk hutang yang masih aktif
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope query untuk hutang yang sudah dibayar
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope query untuk hutang yang sudah jatuh tempo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('maturity_date', '<', now());
    }
}
