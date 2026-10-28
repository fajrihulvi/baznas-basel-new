<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validasi input sederhana dengan pesan Bahasa Indonesia.
 *
 *   $v = Validator::make($input, [
 *       'nama'  => 'required|min:3|max:100',
 *       'email' => 'nullable|email|max:100',
 *       'no_hp' => ['required', 'regex:/^08\d{8,12}$/'],
 *       'jiwa'  => 'required|integer|between:1,50',
 *   ], ['no_hp' => 'Nomor WhatsApp']);
 *   if ($v->fails()) { $v->errors(); }  // [field => pesan pertama]
 *
 * Aturan: required, nullable, min/max (panjang teks), integer, between (angka), in, email, regex, date, before_or_equal:today
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    private function __construct(private readonly array $data)
    {
    }

    /**
     * @param array<string, string|list<string>> $rules
     * @param array<string, string>              $label
     */
    public static function make(array $data, array $rules, array $label = []): self
    {
        $v = new self($data);
        foreach ($rules as $field => $aturan) {
            $v->cek($field, is_array($aturan) ? $aturan : explode('|', $aturan), $label[$field] ?? ucfirst(str_replace('_', ' ', $field)));
        }

        return $v;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Tambah error dari pemeriksaan lain (mis. aturan bisnis) */
    public function tambah(string $field, string $pesan): void
    {
        $this->errors[$field] ??= $pesan;
    }

    /** @param list<string> $aturan */
    private function cek(string $field, array $aturan, string $label): void
    {
        $nilai = $this->data[$field] ?? null;
        $kosong = $nilai === null || (is_string($nilai) && trim($nilai) === '') || $nilai === [];

        if ($kosong) {
            if (in_array('required', $aturan, true)) {
                $this->errors[$field] = "{$label} wajib diisi.";
            }
            return;
        }
        if (!is_scalar($nilai)) {
            $this->errors[$field] = "{$label} tidak valid.";
            return;
        }
        $teks = (string) $nilai;

        foreach ($aturan as $a) {
            [$nama, $param] = array_pad(explode(':', $a, 2), 2, null);
            $pesan = match ($nama) {
                'min'     => mb_strlen($teks) < (int) $param ? "{$label} minimal {$param} karakter." : null,
                'max'     => mb_strlen($teks) > (int) $param ? "{$label} maksimal {$param} karakter." : null,
                'integer' => !preg_match('/^\d+$/', $teks) ? "{$label} harus berupa angka." : null,
                'between' => (function () use ($teks, $param, $label): ?string {
                    [$lo, $hi] = array_map('intval', explode(',', (string) $param));
                    return !preg_match('/^\d+$/', $teks) || (int) $teks < $lo || (int) $teks > $hi
                        ? "{$label} harus antara " . number_format($lo, 0, ',', '.') . ' dan ' . number_format($hi, 0, ',', '.') . '.'
                        : null;
                })(),
                'in'      => !in_array($teks, explode(',', (string) $param), true) ? "Pilihan {$label} tidak valid." : null,
                'email'   => filter_var($teks, FILTER_VALIDATE_EMAIL) === false ? "Format {$label} tidak valid." : null,
                'regex'   => !preg_match((string) $param, $teks) ? "Format {$label} tidak valid." : null,
                'date'    => !self::tanggalValid($teks) ? "{$label} bukan tanggal yang valid." : null,
                'before_or_equal' => self::tanggalValid($teks) && $teks > ($param === 'today' ? date('Y-m-d') : (string) $param)
                    ? "{$label} tidak boleh melewati hari ini." : null,
                default   => null,
            };
            if ($pesan !== null) {
                $this->errors[$field] = $pesan;
                return;
            }
        }
    }

    private static function tanggalValid(string $tgl): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $tgl);

        return $d !== false && $d->format('Y-m-d') === $tgl;
    }
}
