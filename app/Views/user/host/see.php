<!DOCTYPE html>
<html lang="<?= lang('Interface.code') ?>">

<?= view('user/head') ?>

<body>
  <?= view('user/navbar') ?>

  <div class="container">
    <div class="card">
      <div class="row">
        <div class="col-md-6 col-xl-3">
          <div class="card-body d-flex flex-column h-100">
            <h2>Webmin</h2>
            <p class="flex-grow-1">URL:<br><b>https://<?= $slave ?>.domcloud.id:8443</b><br>
              Username:<br><b><?= esc($user) ?></b><br>
              Password:<br><b><?= $pass ?></b></p>
            <p><small><a href="https://<?= $slave ?>.domcloud.id:8443/" target="_blank" rel="noreferrer">Login ke Portal Host</a></small></p>
            <p><small><a href="/user/host/login/<?= $id ?>">Panduan Login ke Portal Host</a></small></p>
          </div>
        </div>
        <div class="col-md-6 col-xl-3">
          <div class="card-body d-flex flex-column h-100">
            <h2>FTP</h2>
            <p class="flex-grow-1">Hostname:<br><b><?= $slave ?>.domcloud.id</b><br>
              Username:<br><b><?= esc($user) ?></b><br>
              Password:<br><b><?= $pass ?></b></p>
            <p><small><a href="https://panduan.domcloud.id/mengisi-file.html">Panduan Mengisi File</a></small></p>
          </div>
        </div>
        <div class="col-md-6 col-xl-3">
          <div class="card-body d-flex flex-column h-100">
            <h2>MySQL / PostgreSQL</h2>
            <p class="flex-grow-1">Hostname:<br><b><?= $slave ?>.domcloud.id</b><br>
              Username:<br><b><?= esc($user) ?></b><br>
              Password:<br><b><?= $pass ?></b></p>
            <p><small><a href="https://phpmyadmin.dom.my.id/" target="_blank" rel="noreferrer">Login ke phpMyAdmin</a></small></p>
            <p><small><a href="https://phppgadmin.dom.my.id/" target="_blank" rel="noreferrer">Login ke phpPgAdmin</a></small></p>
            <p><small><a href="https://panduan.domcloud.id/manage-db.html">Panduan Mengisi Database</a></small></p>
          </div>
        </div>
        <div class="col-md-6 col-xl-3">
          <div class="card-body d-flex flex-column h-100">
            <h2>SSH</h2>
            <p class="flex-grow-1">Username:<br><b><?= esc($user) ?>@<?= $slave ?>.domcloud.id</b><br>
              Password:<br><b><?= $pass ?></b></p>
            <p><small><a href="https://panduan.domcloud.id/ssh-toolkit.html">Panduan Menggunakan SSH</a></small></p>
          </div>
        </div>
      </div>
      <div class="card-body">
        <?php if ($shown) : ?>
          <a href="?" class="mt-3 btn btn-warning float-right"><?= lang('Host.hidePassword')?></a>
        <?php else : ?>
          <a href="?show=password" class="mt-3 btn btn-warning float-right"><?= lang('Host.showPassword')?></a>
        <?php endif ?>
        <a href="/user/host/detail/<?= $id ?>" class="mt-3 btn btn-secondary"><?= lang('Interface.back') ?></a>
      </div>
    </div>
  </div>
</body>