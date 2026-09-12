<?php
require_once __DIR__.'/bootstrap.php';
admin_only();
$id=(int)($_GET['id']??$_POST['id']??0);
if(!$id){$users=$pdo->query('SELECT id,nama,username,role,aktif FROM users ORDER BY role DESC,nama')->fetchAll(PDO::FETCH_ASSOC);?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Atur Izin | StorageQR</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?=url('assets/style.css')?>" rel="stylesheet"></head><body><main class="container py-4" style="max-width:900px"><div class="d-flex justify-content-between align-items-center mb-4"><div><h3 class="mb-1">Atur Izin Akses</h3><div class="text-secondary">Pilih akun untuk mengatur fitur yang boleh digunakan.</div></div><a href="<?=url('members.php')?>" class="btn btn-outline-secondary">Manajemen Akun</a></div><div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($users as $row):?><tr><td><?=e($row['nama'])?></td><td><?=e($row['username'])?></td><td><?=e(role_label($row['role']))?></td><td><?= $row['aktif']?'Aktif':'Nonaktif'?></td><td><a class="btn btn-sm btn-primary" href="<?=url('permissions.php?id='.$row['id'])?>">Atur Izin</a></td></tr><?php endforeach;?></tbody></table></div></div></main></body></html><?php exit;}
$s=$pdo->prepare('SELECT id,nama,username,role FROM users WHERE id=?');
$s->execute([$id]);
$user=$s->fetch(PDO::FETCH_ASSOC);
if(!$user){flash('danger','Akun tidak ditemukan.');header('Location: '.url('members.php'));exit;}
if(is_post()){
    verify_csrf();
    $allowed=array_keys(permission_names());
    $selected=array_intersect($allowed,$_POST['permissions']??[]);
    $pdo->prepare('DELETE FROM user_permissions WHERE user_id=?')->execute([$id]);
    $insert=$pdo->prepare('INSERT INTO user_permissions(user_id,permission) VALUES(?,?)');
    foreach($selected as $permission)$insert->execute([$id,$permission]);
    flash('success','Izin '.$user['nama'].' berhasil diperbarui.');
    header('Location: '.url('permissions.php?id='.$id));exit;
}
$s=$pdo->prepare('SELECT permission FROM user_permissions WHERE user_id=?');
$s->execute([$id]);
$selected=$s->fetchAll(PDO::FETCH_COLUMN);
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Atur Izin | StorageQR</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?=url('assets/style.css')?>" rel="stylesheet"></head><body><main class="container py-4" style="max-width:760px"><div class="d-flex justify-content-between align-items-center mb-4"><div><h3 class="mb-1">Atur Izin Member</h3><div class="text-secondary"><?=e($user['nama'])?> · <?=e($user['username'])?></div></div><a href="<?=url('members.php')?>" class="btn btn-outline-secondary">Kembali</a></div><?php show_flash();?><div class="card"><div class="card-body"><p class="text-secondary">Centang fitur yang boleh digunakan akun ini. Admin selalu memiliki semua izin.</p><?php if($user['role']==='admin'):?><div class="alert alert-info">Akun ini adalah Admin dan otomatis memiliki seluruh akses.</div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=$id?>"><div class="row g-2"><?php foreach(permission_names() as $permission=>$label):?><div class="col-md-6"><label class="border rounded p-3 w-100 d-flex gap-2 align-items-center"><input class="form-check-input" type="checkbox" name="permissions[]" value="<?=$permission?>" <?=in_array($permission,$selected,true)||$user['role']==='admin'?'checked':''?>><?=e($label)?></label></div><?php endforeach;?></div><?php if($user['role']==='admin'):?><input type="hidden" name="permissions[]" value="<?=array_key_first(permission_names())?>"><?php endif;?><button class="btn btn-primary mt-4">Simpan Izin</button></form></div></div></main></body></html>
