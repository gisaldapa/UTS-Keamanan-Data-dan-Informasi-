<?php
// Setup kini otomatis. File ini hanya memicu penyiapan lalu mengarahkan ke login.
require 'config.php'; db(); header('Location: login.php');
