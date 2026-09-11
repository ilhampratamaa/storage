<?php
require_once __DIR__.'/bootstrap.php'; require_login();
$barangId=(int)($_GET['barang_id']??0);
$locations=$pdo->query('SELECT * FROM lokasi ORDER BY gedung,ruang,rak')->fetchAll(PDO::FETCH_ASSOC);
function unit_options($rows,$selected){foreach($rows as $row)echo '<option value="'.e($row['id']).'" '.($selected==$row['id']?'selected':'').'>'.e(location_name($row)).'</option>';}
if(is_post()){
	admin_only(); verify_csrf();
	$unitId=(int)($_POST['unit_id']??0); $unitBarangId=(int)($_POST['barang_id']??0);
	$s=$pdo->prepare('UPDATE barang_unit SET nama_unit=?,lokasi_id=?,spesifikasi=?,kondisi=?,catatan=? WHERE id=? AND barang_id=?');
	$s->execute([trim($_POST['nama_unit']??'')?:null,$_POST['lokasi_id']?:null,trim($_POST['spesifikasi']??'')?:null,$_POST['kondisi']??'Baik',trim($_POST['catatan']??'')?:null,$unitId,$unitBarangId]);
	flash('success','Keterangan unit berhasil diperbarui.');
	header('Location: '.url('units.php?barang_id='.$unitBarangId)); exit;
}
$s=$pdo->prepare('SELECT b.*,k.nama kategori,l.gedung,l.ruang,l.rak FROM barang b LEFT JOIN kategori k ON k.id=b.kategori_id LEFT JOIN lokasi l ON l.id=b.lokasi_id WHERE b.id=? AND b.aktif=1');
$s->execute([$barangId]); $barang=$s->fetch(PDO::FETCH_ASSOC);
$units=[];
if($barang){$s=$pdo->prepare('SELECT bu.*,l.gedung,l.ruang,l.rak FROM barang_unit bu LEFT JOIN lokasi l ON l.id=bu.lokasi_id WHERE bu.barang_id=? ORDER BY bu.id');$s->execute([$barangId]);$units=$s->fetchAll(PDO::FETCH_ASSOC);}
require __DIR__.'/partials/header.php';
if(!$barang): ?><div class="alert alert-danger">Data barang tidak ditemukan.</div><?php else: ?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><a href="<?=url('index.php?page=barang')?>" class="btn btn-outline-secondary">Kembali</a> <span class="ms-2"><b><?=e($barang['kode'])?></b> - <?=e($barang['nama'])?></span></div><button onclick="window.print()" class="btn btn-outline-primary">Cetak QR</button></div><?php show_flash(); ?>
<div class="row g-3">
<?php foreach($units as $unit): $qr=qr_image_url($unit['kode_unit'],240); ?>
<div class="col-12 col-md-6 col-xl-4"><div class="card h-100"><div class="card-body"><div class="row align-items-center"><div class="col-5 text-center"><img class="img-fluid mb-2" src="<?=e($qr)?>" alt="QR <?=e($unit['kode_unit'])?>"><h6 class="mb-1"><?=e($unit['nama_unit']?:$unit['kode_unit'])?></h6><div class="small text-secondary mb-2"><?=e($unit['kode_unit'])?></div><a class="btn btn-sm btn-outline-primary mb-2" download="QR-<?=e($unit['kode_unit'])?>.png" href="<?=e($qr)?>">Unduh QR</a><br><span class="badge text-bg-<?=$unit['status']==='tersedia'?'success':($unit['status']==='keluar'?'warning':'danger')?>"><?=e(ucfirst($unit['status']))?></span></div><div class="col-7 small"><div><b>Lokasi:</b> <?=e(location_name($unit)?:'Belum diatur')?></div><div><b>Kondisi:</b> <?=e($unit['kondisi'])?></div><div><b>Spesifikasi:</b><br><?=nl2br(e($unit['spesifikasi']?:'Belum diisi'))?></div><div><b>Catatan:</b><br><?=nl2br(e($unit['catatan']?:'Belum diisi'))?></div></div></div><?php if($_SESSION['user']['role']==='admin'): ?><hr><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="unit_id" value="<?=$unit['id']?>"><input type="hidden" name="barang_id" value="<?=$barangId?>"><label class="form-label small">Nama / label unit</label><input class="form-control form-control-sm mb-2" name="nama_unit" maxlength="150" value="<?=e($unit['nama_unit'])?>" placeholder="Contoh: Laptop Admin 1"><label class="form-label small">Lokasi unit</label><select class="form-select form-select-sm mb-2" name="lokasi_id"><option value="">- Ikuti master / belum ditentukan -</option><?php unit_options($locations,$unit['lokasi_id'])?></select><label class="form-label small">Spesifikasi unit</label><textarea class="form-control form-control-sm mb-2" name="spesifikasi" rows="2" placeholder="Contoh: RAM 16 GB, kapasitas 1 TB"><?=e($unit['spesifikasi'])?></textarea><label class="form-label small">Kondisi</label><select class="form-select form-select-sm mb-2" name="kondisi"><?php foreach(['Baik','Rusak Ringan','Rusak Berat'] as $condition): ?><option <?=$unit['kondisi']===$condition?'selected':''?>><?=$condition?></option><?php endforeach; ?></select><label class="form-label small">Catatan khusus</label><textarea class="form-control form-control-sm mb-2" name="catatan" rows="2" placeholder="Contoh: unit lebih tinggi, disimpan terpisah"><?=e($unit['catatan'])?></textarea><button class="btn btn-sm btn-primary">Simpan keterangan</button></form><?php endif; ?></div></div></div>
<?php endforeach; ?>
</div>
<?php if(!$units): ?><div class="alert alert-warning">Belum ada unit fisik untuk barang ini.</div><?php endif; ?>
<?php endif; require __DIR__.'/partials/footer.php';
