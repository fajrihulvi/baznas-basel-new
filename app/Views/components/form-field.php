<?php
/**
 * Field formulir: label, input, petunjuk, pesan error (aria-describedby/aria-invalid).
 * Nilai awal & error otomatis dari flash (old() dan Session::flash('errors', [...])) bila tidak diberikan.
 *
 * @var string      $name
 * @var string      $label
 * @var string|null $type         text (default) | email | tel | number | password | textarea | select | file
 * @var string|null $value
 * @var array<string, string>|null $options  untuk select: nilai => label
 * @var bool|null   $required
 * @var string|null $hint
 * @var string|null $error
 * @var string|null $placeholder
 * @var string|null $inputmode    mis. numeric
 * @var string|null $autocomplete mis. name, tel, email
 * @var string|null $accept       untuk file
 * @var string|null $id           id elemen (default "f-{name}"); wajib unik bila nama sama dipakai di beberapa form
 * @var array<string, string|int>|null $atribut atribut tambahan, mis. ['data-rupiah' => '', 'min' => 1, 'max' => 100]
 * @var string|null $nameAttr     nilai atribut name bila berbeda dari $name (mis. '' = tidak dikirim)
 */

use App\Core\Session;

$type ??= 'text';
$id ??= 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
$value ??= old($name);
$errors = Session::getFlash('errors', []);
$error ??= is_array($errors) ? ($errors[$name] ?? null) : null;
$describedBy = trim((!empty($hint) ? $id . '-hint ' : '') . (!empty($error) ? $id . '-error' : ''));

$kelasInput = 'block w-full rounded-xl border bg-white px-4 text-base text-ink placeholder:text-gray-500 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 '
    . (!empty($error) ? 'border-red-500' : 'border-gray-300');

$namaKirim = $nameAttr ?? $name;
$attr = ' id="' . e($id) . '"' . ($namaKirim !== '' ? ' name="' . e($namaKirim) . '"' : '')
    . (!empty($required) ? ' required' : '')
    . ($describedBy !== '' ? ' aria-describedby="' . e($describedBy) . '"' : '')
    . (!empty($error) ? ' aria-invalid="true"' : '')
    . (!empty($placeholder) ? ' placeholder="' . e($placeholder) . '"' : '')
    . (!empty($inputmode) ? ' inputmode="' . e($inputmode) . '"' : '')
    . (!empty($autocomplete) ? ' autocomplete="' . e($autocomplete) . '"' : '');
foreach ($atribut ?? [] as $namaAtribut => $nilaiAtribut) {
    $attr .= ' ' . e((string) $namaAtribut) . ($nilaiAtribut === '' ? '' : '="' . e((string) $nilaiAtribut) . '"');
}
?>
<div>
    <label for="<?= e($id) ?>" class="mb-1.5 block text-sm font-semibold text-ink">
        <?= e($label) ?><?php if (!empty($required)): ?> <span class="text-red-700" aria-hidden="true">*</span><?php endif ?>
    </label>

    <?php if ($type === 'textarea'): ?>
    <textarea<?= $attr ?> rows="4" class="<?= $kelasInput ?> py-3"><?= e($value) ?></textarea>
    <?php elseif ($type === 'select'): ?>
    <select<?= $attr ?> class="<?= $kelasInput ?> h-12">
        <option value="">Pilih…</option>
        <?php foreach ($options ?? [] as $nilai => $teks): ?>
        <option value="<?= e((string) $nilai) ?>"<?= (string) $nilai === (string) $value ? ' selected' : '' ?>><?= e($teks) ?></option>
        <?php endforeach ?>
    </select>
    <?php elseif ($type === 'file'): ?>
    <input type="file"<?= $attr ?><?= !empty($accept) ? ' accept="' . e($accept) . '"' : '' ?>
           class="block w-full rounded-xl border border-dashed border-gray-300 bg-surface p-3 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-primary file:px-4 file:py-2 file:font-semibold file:text-white">
    <?php else: ?>
    <input type="<?= e($type) ?>"<?= $attr ?> value="<?= e($value) ?>" class="<?= $kelasInput ?> h-12">
    <?php endif ?>

    <?php if (!empty($hint)): ?>
    <p id="<?= e($id) ?>-hint" class="mt-1.5 text-sm text-muted"><?= e($hint) ?></p>
    <?php endif ?>
    <?php if (!empty($error)): ?>
    <p id="<?= e($id) ?>-error" class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-red-700"><?= icon('alert', 'size-4 shrink-0') ?><?= e($error) ?></p>
    <?php endif ?>
</div>
