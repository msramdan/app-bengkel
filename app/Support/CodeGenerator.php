<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CodeGenerator
{
  public const PREFIX_CUSTOMER = 'PLG';

  public const PREFIX_TECHNICIAN = 'TKN';

  public const PREFIX_ITEM = 'BRG';

  public const PREFIX_STOCK_IN = 'STM';

  public const PREFIX_STOCK_OUT = 'STK';

  /** Fase 2 — penjualan barang */
  public const PREFIX_SALE = 'JBL';

  /** Fase 2 — service jasa */
  public const PREFIX_SERVICE = 'SRV';

  /** Fase 2 — transaksi gabungan barang + jasa */
  public const PREFIX_COMBINED = 'TRX';

  /** Fase 3 — pembelian barang / pengeluaran */
  public const PREFIX_PURCHASE = 'PBL';

  /**
   * Format: {PREFIX}-{YYYYMMDD}-{0001}
   * Nomor urut reset per hari per prefix.
   */
  public static function next(string $prefix, string $modelClass, string $column = 'code'): string
  {
    /** @var Model $model */
    $model = new $modelClass;
    $query = $model->newQuery();

    if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
      $query->withTrashed();
    }

    return self::nextFromQuery($prefix, $query, $column);
  }

  public static function nextFromTable(string $prefix, string $table, string $column): string
  {
    return self::nextFromQuery($prefix, DB::table($table), $column);
  }

  private static function nextFromQuery(string $prefix, $query, string $column): string
  {
    $date = now()->format('Ymd');
    $lockName = 'codegen:'.$prefix.':'.$date;
    $lock = DB::selectOne('SELECT GET_LOCK(?, 10) as acquired', [$lockName]);

    if (! $lock || (int) $lock->acquired !== 1) {
      throw new \RuntimeException('Gagal mengalokasikan nomor dokumen. Silakan coba lagi.');
    }

    try {
      $pattern = "{$prefix}-{$date}-%";

      $latest = (clone $query)
        ->where($column, 'like', $pattern)
        ->orderByDesc($column)
        ->value($column);

      $seq = 1;
      if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
        $seq = (int) $matches[1] + 1;
      }

      return sprintf('%s-%s-%04d', $prefix, $date, $seq);
    } finally {
      DB::select('SELECT RELEASE_LOCK(?)', [$lockName]);
    }
  }
}
