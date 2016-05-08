<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Brussels');
?>
<!DOCTYPE html>
<html lang="en" style="height:100%;">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>112 Help - Backend</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin.css">
  </head>

  <body style="height:100%;">

    <nav class="navbar navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <img src="logo.png" alt="112Help">
        </div>
      </div>
    </nav>

    <div class="container-fluid" style="height:100%;">
      <div class="row" style="height:100%;">
        <div class="col-sm-2 col-md-1 sidebar">
          <ul class="nav nav-sidebar">
            <li><a href="admin.php">Real Time</a></li>
            <li><a href="map.php">Map</a></li>
            <li class="active"><a href="twitter.php">Twitter Feed</a></li>
          </ul>
        </div>
        <div class="col-sm-10 col-sm-offset-2 col-md-11 col-md-offset-1 main">
          <a class="twitter-timeline"  href="https://twitter.com/hashtag/molenhack" data-widget-id="726702520572219392">Tweets sur #molenhack</a>
          <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
        </div>
      </div>
    </div>
  </body>
</html>