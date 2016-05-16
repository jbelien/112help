<?php
$ini = parse_ini_file('../settings.ini', TRUE);

if (isset($_POST['action'], $_POST['login'], $_POST['passwd']) && $_POST['action'] == 'login') {
  $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
  $mysqli->set_charset('utf8');

  $q = $mysqli->query("SELECT * FROM `users` WHERE `login` = '".$mysqli->real_escape_string($_POST['login'])."' LIMIT 1") or trigger_error($mysqli->error);
  if ($q->num_rows == 1) {
    $r = $q->fetch_row();
    if (password_verify($_POST['passwd'], $r[2]) === TRUE) {
      if (password_needs_rehash($r[2], PASSWORD_DEFAULT) === TRUE) {
        $new_hash = password_hash($_POST['passwd'], PASSWORD_DEFAULT);
        $mysqli->query("UPDATE `users` SET `password` = '".$mysqli->real_escape_string($new_hash)."' WHERE `id` = ".$r[0]." LIMIT 1") or trigger_error($mysqli->error);
      }

      session_start();

      $_SESSION['user'] = $r[0];
      header('Location: index.php');
      exit();
    }
  }
  $q->free();

  $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>112 Help - Backend</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <script src="https://use.fontawesome.com/b168f02b1e.js"></script>
  </head>
  <body>
    <div class="container">
      <div class="text-center"><img src="logo.png" alt="112 Help" style="margin:15px 0;"></div>
      <div class="row">
        <div class="col-sm-4 col-md-offset-4">
          <form method="post" action="login.php" class="well" autocomplete="off">
            <div class="form-group control-group">
              <label class="control-label" for="inputLogin">Identifiant</label>
              <input class="form-control" type="text" id="inputLogin" name="login" required="required">
            </div>
            <div class="form-group control-group">
              <label class="control-label" for="inputPassword">Mot de passe</label>
              <input class="form-control" type="password" id="inputPassword" name="passwd" required="required">
            </div>
<!--
            <div class="form-group control-group">
              <label class="control-label" for="inputLanguage">Langue</label>
              <select class="form-control" name="lang" id="inputLanguage">
                <option value="fr">Fran&ccedil;ais</option>
                <option value="nl">Nederlands</option>
                <option value="en">English</option>
              </select>
            </div>
-->
<div class="text-center">
  <button type="submit" class="btn btn-default" name="action" value="login"><i class="fa fa-sign-in"></i> Connexion</button><br>
  <!--<button type="button" class="btn btn-link btn-sm" data-target="#password" data-toggle="modal">Mot de passe oubli&eacute; ?</button>-->
</div>
</fieldset>
</form>
<!--
<div id="password" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Mot de passe oubli&eacute; ?</h4>
      </div>
      <div class="modal-body">
        <p>Vous allez recevoir un email contenant un lien vous permettant de g&eacute;n&eacute;rer un nouveau mot de passe.</p>
        <form method="post" action="/password.php">
          <p>Veuillez entrer l'adresse e-mail li&eacute;e &agrave; votre compte :</p>
          <div class="input-group">
            <input type="email" name="email" class="form-control" required="required">
            <span class="input-group-btn">
              <button class="btn btn-default" type="submit"><i class="fa fa-paper-plane-o"></i> Envoyer</button>
            </span>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
-->
<!--
          <div class="text-muted clearfix small">
            <div class="pull-right">&copy; 2016</div>
            <div class="pull-left">
              <ul class="list-inline">
                                <li><a href="?L=nl" class="text-muted">Nederlands</a></li>                <li><a href="?L=en" class="text-muted">English</a></li>              </ul>
            </div>
          </div>
-->
        </div>
      </div>
    </div>
    <script src="/cdn/js/jquery/jquery-1.12.2.min.js"></script>
    <script src="/cdn/js/bootstrap/3.3/bootstrap.min.js"></script>
  </body>
</html>
<!--
<?php
echo password_hash("!en5utf8", PASSWORD_DEFAULT).PHP_EOL;
echo password_hash("feYap8"  , PASSWORD_DEFAULT).PHP_EOL;
echo password_hash("pA5pat"  , PASSWORD_DEFAULT).PHP_EOL;
?>
-->