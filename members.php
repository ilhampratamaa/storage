<?php
require_once __DIR__.'/bootstrap.php';
admin_only();

function save_user_permissions($userId, $role, $selected) {
    global $pdo;
    $pdo->prepare('DELETE FROM user_permissions WHERE user_id=?')->execute([$userId]);
    if ($role !== 'user') return;
    $selected = array_intersect(array_keys(permission_names()), $selected);
    $insert = $pdo->prepare('INSERT INTO user_permissions(user_id,permission) VALUES(?,?)');
    foreach ($selected as $permission) $insert->execute([$userId, $permission]);
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($action === 'create') {
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            if (strlen($nama) < 3 || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username) || strlen($password) < 6) throw new Exception('Lengkapi data dengan benar. Password minimal 6 karakter.');
            if (!in_array($role, ['admin', 'user'], true)) throw new Exception('Role tidak valid.');
            $pdo->prepare('INSERT INTO users(nama,username,password,role) VALUES(?,?,?,?)')->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
            $id = (int)$pdo->lastInsertId();
            save_user_permissions($id, $role, $_POST['permissions'] ?? []);
            flash('success', 'Akun baru berhasil dibuat.');
        }
        if ($action === 'update') {
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $aktif = isset($_POST['aktif']) ? 1 : 0;
            if (!$id || strlen($nama) < 3 || !preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username) || !in_array($role, ['admin', 'user'], true)) throw new Exception('Data akun tidak valid.');
            if ($id === $_SESSION['user']['id'] && ($role !== 'admin' || !$aktif)) throw new Exception('Admin tidak dapat menurunkan role atau menonaktifkan akun sendiri.');
            $pdo->prepare('UPDATE users SET nama=?,username=?,role=?,aktif=? WHERE id=?')->execute([$nama, $username, $role, $aktif, $id]);
            save_user_permissions($id, $role, $_POST['permissions'] ?? []);
            if ($id === $_SESSION['user']['id']) $_SESSION['user']['nama'] = $nama;
            flash('success', 'Akun dan izin berhasil diperbarui.');
        }
        if ($action === 'reset_password') {
            $password = $_POST['password'] ?? '';
            if (!$id || strlen($password) < 6) throw new Exception('Password minimal 6 karakter.');
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            flash('success', 'Password berhasil direset.');
        }
    } catch (Throwable $e) { flash('danger', $e->getMessage()); }
    header('Location: '.url('members.php')); exit;
}

$users = $pdo->query('SELECT id,nama,username,role,aktif,created_at FROM users ORDER BY role DESC,nama')->fetchAll(PDO::FETCH_ASSOC);
$edit = null;
$editPermissions = [];
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT id,nama,username,role,aktif FROM users WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch(PDO::FETCH_ASSOC);
    if ($edit) {
        $s = $pdo->prepare('SELECT permission FROM user_permissions WHERE user_id=?');
        $s->execute([$edit['id']]);
        $editPermissions = $s->fetchAll(PDO::FETCH_COLUMN);
    }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kelola Member & Admin | StorageQR</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?=url('assets/style.css')?>" rel="stylesheet"></head><body><main class="container py-4 py-md-5" style="max-width:1180px"><div class="d-flex justify-content-between align-items-center mb-4"><div><h3 class="mb-0">Kelola Member & Admin</h3><small class="text-secondary">Atur akun, role, dan izin fitur setiap Member.</small></div><a href="<?=url('index.php')?>" class="btn btn-outline-secondary">Dashboard</a></div><?php show_flash();?><div class="row g-4"><div class="col-lg-5"><div class="card"><div class="card-body"><h5><?= $edit ? 'Edit akun dan izin' : 'Tambah akun' ?></h5><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="<?=$edit?'update':'create'?>"><input type="hidden" name="id" value="<?=e($edit['id']??'')?>"><div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="nama" required value="<?=e($edit['nama']??'')?>"></div><div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required value="<?=e($edit['username']??'')?>"></div><?php if(!$edit):?><div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" minlength="6" required></div><?php endif?><div class="mb-3"><label class="form-label">Role</label><select class="form-select" name="role"><option value="user" <?=($edit['role']??'user')==='user'?'selected':''?>>Member</option><option value="admin" <?=($edit['role']??'')==='admin'?'selected':''?>>Admin</option></select></div><div class="mb-3"><label class="form-label">Izin Member</label><small class="d-block text-secondary mb-2">Admin otomatis memiliki semua akses.</small><div class="border rounded p-2"><?php foreach(permission_names() as $permission=>$label):?><div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="<?=$permission?>" id="permission-<?=$permission?>" <?=in_array($permission,$editPermissions,true)||(!$edit&&in_array($permission,['dashboard_view','barang_view','units_view'],true))?'checked':''?>><label class="form-check-label" for="permission-<?=$permission?>"><?=e($label)?></label></div><?php endforeach;?></div></div><?php if($edit):?><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="aktif" id="aktif" <?=$edit['aktif']?'checked':''?>><label class="form-check-label" for="aktif">Akun aktif</label></div><?php endif?><button class="btn btn-primary w-100">Simpan Akun & Izin</button><?php if($edit):?><a class="btn btn-light w-100 mt-2" href="<?=url('members.php')?>">Batal</a><?php endif?></form></div></div><?php if($edit):?><div class="card mt-3"><div class="card-body"><h6>Reset password</h6><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?=$edit['id']?>"><input class="form-control mb-2" type="password" name="password" minlength="6" placeholder="Password baru" required><button class="btn btn-outline-warning w-100">Reset Password</button></form></div></div><?php endif?></div><div class="col-lg-7"><div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Terdaftar</th><th></th></tr></thead><tbody><?php foreach($users as $u):?><tr><td><?=e($u['nama'])?></td><td><?=e($u['username'])?></td><td><span class="badge text-bg-<?=$u['role']==='admin'?'primary':'secondary'?>"><?=$u['role']==='admin'?'Admin':'Member'?></span></td><td><span class="badge text-bg-<?=$u['aktif']?'success':'danger'?>"><?=$u['aktif']?'Aktif':'Nonaktif'?></span></td><td><?=e(date('d-m-Y',strtotime($u['created_at'])))?></td><td><a class="btn btn-sm btn-outline-primary" href="<?=url('members.php?edit='.$u['id'])?>">Kelola</a></td></tr><?php endforeach?></tbody></table></div></div></div></div></main></body></html>
