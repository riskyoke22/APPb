<?php
use App\Core\Csrf;
use App\Core\Helpers;

/** @var array $guestList */
/** @var string $currentQuery */
?>
<?php if (empty($guestList)): ?>
  <tr>
    <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
      Belum ada data tamu yang sesuai filter.
    </td>
  </tr>
<?php else: ?>
  <?php foreach ($guestList as $g): ?>
    <?php $photoUrl = '/photo?f=' . urlencode($g['foto_path']); ?>
    <tr data-guest-id="<?= (int) $g['id'] ?>">
      <td>
        <img
          src="<?= Helpers::e($photoUrl) ?>"
          alt="Foto <?= Helpers::e($g['nama_lengkap']) ?>"
          class="avatar-thumb js-open-lightbox"
          data-photo="<?= Helpers::e($photoUrl) ?>"
          data-name="<?= Helpers::e($g['nama_lengkap']) ?>"
          data-instansi="<?= Helpers::e($g['instansi']) ?>"
          data-tujuan="<?= Helpers::e($g['tujuan']) ?>"
          data-kode="<?= Helpers::e($g['kode_kunjungan']) ?>"
          data-keperluan="<?= Helpers::e($g['keperluan']) ?>"
          title="Klik untuk memperbesar foto"
        >
      </td>
      <td>
        <div style="font-weight: 700;"><?= Helpers::e($g['nama_lengkap']) ?></div>
        <div style="font-size: 0.76rem; color: var(--brand-blue); font-weight: 600;"><?= Helpers::e($g['kode_kunjungan']) ?></div>
      </td>
      <td>
        <div style="font-weight: 600;"><?= Helpers::e($g['instansi']) ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted);"><?= Helpers::e($g['no_hp']) ?></div>
      </td>
      <td style="max-width: 260px;">
        <div style="font-weight: 600; color: var(--brand-navy);"><?= Helpers::e($g['tujuan']) ?></div>
        <?php if (!empty($g['bertemu_dengan'])): ?>
          <div style="font-size: 0.78rem; color: var(--text-secondary);">PIC: <?= Helpers::e($g['bertemu_dengan']) ?></div>
        <?php endif; ?>
        <div style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= Helpers::e($g['keperluan']) ?>">
          <?= Helpers::e($g['keperluan']) ?>
        </div>
      </td>
      <td style="white-space: nowrap; font-size: 0.8rem;">
        <div><strong>Masuk:</strong> <?= Helpers::e(Helpers::formatTanggalIndo($g['waktu_masuk'])) ?></div>
        <div style="color: var(--text-muted);"><strong>Keluar:</strong> <?= Helpers::e(Helpers::formatTanggalIndo($g['waktu_keluar'])) ?></div>
      </td>
      <td>
        <?php if ($g['status'] === 'berkunjung'): ?>
          <span class="badge badge-active">Berkunjung</span>
        <?php else: ?>
          <span class="badge badge-done">Selesai</span>
        <?php endif; ?>
      </td>
      <td class="no-print">
        <div style="display: flex; gap: 0.4rem;">
          <?php if ($g['status'] === 'berkunjung'): ?>
            <form
              method="POST"
              action="/admin/guest/checkout"
              style="margin: 0;"
              class="js-confirm-action"
              data-action="checkout"
              data-name="<?= Helpers::e($g['nama_lengkap']) ?>"
              data-kode="<?= Helpers::e($g['kode_kunjungan']) ?>"
            >
              <?= Csrf::field() ?>
              <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
              <input type="hidden" name="redirect_query" value="<?= Helpers::e($currentQuery) ?>">
              <button type="submit" class="btn btn-soft btn-sm" title="Tandai selesai berkunjung">Check-Out</button>
            </form>
          <?php endif; ?>
          <form
            method="POST"
            action="/admin/guest/delete"
            style="margin: 0;"
            class="js-confirm-action"
            data-action="delete"
            data-name="<?= Helpers::e($g['nama_lengkap']) ?>"
            data-kode="<?= Helpers::e($g['kode_kunjungan']) ?>"
          >
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
            <input type="hidden" name="redirect_query" value="<?= Helpers::e($currentQuery) ?>">
            <button type="submit" class="btn btn-danger-soft btn-sm">Hapus</button>
          </form>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
<?php endif; ?>
