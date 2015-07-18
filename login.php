<?php
session_start();
require_once('config.php');
require_once('functions.php');

if (!empty($_SESSION['me'])) {
    header('Location: '.SITE_URL.'admin/fgets.php');
    exit;
}

function getUser($email, $pass, $dbh) {
    $sql = "select * from user_info where status = 'active' and email = :email and pass = :pass limit 1";
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array(":email"=>$email, ":pass"=>getSha1Password($pass)));
    $user = $stmt->fetch();
    // error_log($stmt);
    return $user ? $user : false;
    //条件式(return $user = $userに戻り値があれば) ? 式1 : 式2(条件式を評価し、TRUEであれば式1、FALSEであれば式2を返します)
    // var_dump($user);
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    // CSRF対策
    setToken();
} else {

    checkToken();

    $email = $_POST['email'];
    $pass = $_POST['pass'];

    $dbh = connectDb();

    $err = array();

    // メールアドレスが登録されていない
    if (!emailExists($email, $dbh)) {
        $err['email'] = 'このメールアドレスは登録されていません';
    }

    // メールアドレスの形式が不正
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err['email'] = 'メールアドレスの形式が正しくありません';
    }

    // メールアドレスが空？
    if ($email == '') {
        $err['email'] = 'メールアドレスを入力してください';
    }

    // メールアドレスとパスワードが正しくない
    if (!$me = getUser($email, $pass, $dbh)) {
        $err['pass'] = 'パスワードとメールアドレスが一致しません';
        // echo var_dump($me);
    }

    // パスワードが空？
    if ($pass == '') {
        $err['pass'] = 'パスワードを入力してください';
    }

    if (empty($err)) {
        // セッションハイジャック対策→これやるとSESSIONが切れる
        // session_regenerate_id(true);
        $_SESSION['me'] = $me;
        header('Location: '.SITE_URL.'admin/fgets.php');
        exit;
    }

}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ログイン画面</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://yui.yahooapis.com/3.6.0/build/cssreset/cssreset-min.css">
  <link rel="stylesheet" href="mycss.css">
  <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">

  <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body>

  <!-- header -->

  <nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-header">
      <button class="navbar-toggle" data-toggle="collapse" data-target=".target">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="" style="padding-left:57px;">CPA CLUB</a>
    </div>

    <div class="collapse navbar-collapse target">
      <ul class="nav navbar-nav">

      </ul>
    </div>
  </nav>

  <!-- main -->
  <div class="container">
    <div class="row">
      <!--
      classのcol-sm-12(合計値は12)
      smはsmall
      デバイスの幅によって並び方が変わる
        col-xs-6
        col-xs-6
        →を付けるとさらにデバイスの幅によって大きさを変えることも可能
        hidden-xsは、幅が小さくなると非表示にする
      bootstrap-->
        <div class="col-sm-4 col-xs-2">
        </div>

        <div class="col-sm-4 col-xs-8" style="height:550px;">


            <form action="login.php" method="POST" style="padding-top:100px;">

              <!-- 通常 -->
              <?php if ($err['email'] == "" && $err['pass'] == "") : ?>
                <div class="form-group">
                  <label for="email"><i class="glyphicon glyphicon-user"></i>  メールアドレス</label>
                  <input type="text" style="width:100%;" name="email" class="form-control" placeholder="Enter your e-mail address" value="<?php echo h($email); ?>"> <?php echo h($err['email']); ?>
                </div>
              <!-- アラート -->
              <?php elseif (!$err['email'] == "") : ?>
                <div class="form-group has-error  has-feedback">
                  <label for="email"><i class="glyphicon glyphicon-user"></i>  メールアドレス</label>
                  <input type="text" style="width:100%;" name="email" class="form-control"  aria-describedby="inputError2Status" placeholder="Enter your e-mail address" value="<?php echo h($email); ?>">
                  <span class="glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>
                  <?php echo '<span class="MyAlert">'.h($err['email']).'</span>'; ?>
                </div>
                <!-- 他の項目がエラーの場合 -->
              <?php elseif (!$err['pass'] == "") : ?>
                <div class="form-group has-success  has-feedback">
                  <label class="control-label" for="email"><i class="glyphicon glyphicon-user"></i>  メールアドレス</label>
                  <input type="text" style="width:100%;" name="email" class="form-control" placeholder="Enter your e-mail address" value="<?php echo h($email); ?>">
                  <span class="glyphicon glyphicon-ok form-control-feedback" aria-hidden="true"></span>
                   <?php echo '<span class="MyAlert">'.h($err['email']).'</span>'; ?>
                </div>
              <?php endif; ?><!--if終了-->

              <!-- 通常 -->
              <?php if ($err['pass'] == "") : ?>
                <div class="form-group">
                  <label for="pass"><i class="glyphicon glyphicon-lock"></i>  パスワード</label>
                  <input type="password" style="width:100%;" name="pass" class="form-control" placeholder="Enter your password" value=""> <?php echo h($err['pass']); ?></p>
                </div>
                <!-- アラート -->
              <?php elseif (!$err['pass'] == "") : ?>
                <div class="form-group has-error  has-feedback">
                  <label for="pass"><i class="glyphicon glyphicon-lock"></i>  パスワード</label>
                  <input type="password" style="width:100%;" name="pass" class="form-control" placeholder="Enter your password" value="">
                  <span class="glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>
                  <?php echo '<span class="MyAlert">'.h($err['pass']).'</span>'; ?></p>
                </div>

              <?php endif; ?>


                <input type="hidden" name="token" value="<?php echo h($_SESSION['token']); ?>">

                <div class="form-group">
                <p><input type="submit" style="width:100%;" class="btn btn-primary" value="ログイン"></p>
                </div>

                <a href="signup.php">新規登録はこちら！</a>
            </form>
        </div>
        <div class="col-sm-4 col-xs-2">
        </div>

      </div>
    </div>


<!-- footer -->
<div id="footer" class="container">

  <div style="font-size:12px; border-top:1px #ccc solid; padding-top:10px; padding-bottom:30px;">
    <div style="overflow:hidden;">
      <!--CPA CLUBとは-->
      <div style="float:right">
        <span style="border-bottom:1px solid #ccc;">CPA CLUBとは</span>
        <p>公認会計士専用の転職サイトです。<br>
        あなたも転職してお祝い金（最大50万円）を受け取りませんか？</p>
      </div>

      <!--ご利用にあたって-->
      <div style="float:right;">
        <span style="border-bottom:1px solid #ccc;">ご利用にあたって</span>
        <ul>
          <li><a data-toggle="modal" href="#">このサイトについて</a></li>
          <li><a data-toggle="modal" href="#myModal">プライバシーポリシー</a></li>
          <li><a data-toggle="modal" href="#">利用規約</a></li>
          <li><a data-toggle="modal" href="#">お問い合わせ</a></li>
        </ul>
      </div>
    </div>
    <!--著作権表示 -->
    <div style="text-align:center; color:#ccc;">
      Copyright &copy; CPA CLUB. All rights reserved.
    </div>
  </div>


  <div class="modal fade" id="myModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="glyphicon glyphicon-lock"></i> プライバシーポリシー</h4>
        </div>
        <div class="modal-body">
          <p>CPA CLUBは、高度情報通信社会における個人情報保護の重要性を認識し、個人情報の保護に関する法律その他関係法令等を遵守し、以下の方針に基づき個人情報の保護に努めます。</p>

          <h5>1. 個人情報の取得について</h5>
            <ul>
              <li>CPA CLUBは、法令に基づく公認会計士等の登録に関する個人情報をはじめ、あらゆる個人情報について、適切かつ公正な手段によって取得いたします。</li>
            </ul>

          <h5>2. 個人情報の利用について</h5>
            <ol>
              <li>CPA CLUBは、個人情報を取得の際に示した利用目的の範囲内において、業務の遂行上必要な場合に限り利用します。</li>
              <li>CPA CLUBは、個人情報を第三者との間で共同利用し、又は個人情報の取扱いを第三者に委託する場合には、当該第三者について厳正な調査を行った上、個人情報の安全管理保持のため必要かつ適正な監督を行います。</li>
            </ol>

          <h5>3. 個人情報の第三者提供について</h5>
            <ul>
              <li>CPA CLUBは、次に掲げる場合を除き、第三者に提供しません。</li>
            </ul>
            <ol>
              <li>CPA CLUBは、個人情報を取得の際に示した利用目的の範囲内において、業務の遂行上必要な場合に限り利用します。</li>
              <li>CPA CLUBは、個人情報を第三者との間で共同利用し、又は個人情報の取扱いを第三者に委託する場合には、当該第三者について厳正な調査を行った上、個人情報の安全管理保持のため必要かつ適正な監督を行います。</li>
            </ol>

            <h5>4. 個人情報の管理について</h5>
              <ol>
                <li>CPA CLUBは、個人情報の正確性を保ち、これを安全に管理いたします。</li>
                <li>CPA CLUBは、個人情報の改ざん、紛失及び漏えい等を防止するため、不正アクセス、コンピュータウィルス等に対する適正な情報セキュリティ対策を講じます。</li>
                <li>CPA CLUBは、個人情報を持ち出し、外部へ送信する等による漏えいを防止するための対策を講じます。</li>
              </ol>

              <h5>5. 個人情報の開示、訂正、利用停止及び消去について</h5>
                <ul>
                  <li>CPA CLUBは、本人が自己の個人情報について、開示、訂正、利用停止、消去等を求める権利を有していることを確認し、これらの請求があった場合（ただし、公認会計士等の登録など、会則等に別途手続が定められている事項を除く。）には、速やかに対応します。</li>
                </ul>

                <h5>6. 個人情報保護に関する組織体制について</h5>
                  <ol>
                    <li>CPA CLUBは、専務理事を個人情報保護管理者として任命し、個人情報の適正な管理を実施いたします。</li>
                    <li>CPA CLUBは、職員等に対し、個人情報の保護及び適正な管理方法についての研修を実施し、日常業務における個人情報の適正な取扱いを徹底します。</li>
                  </ol>

                <h5>7. 個人情報保護に関する規定等の作成、実施、維持及び改善について</h5>
                  <ul>
                    <li>CPA CLUBは、「個人情報保護管理細則」及びその他個人情報の保護に必要な規程、事務マニュアル等を策定し、これをCPA CLUB職員その他関係者に周知徹底させて実施し、これを維持し、継続的に改善いたします。</li>
                  </ul>



        </div>
        <div class="modal-footer">
        </div>
      </div>
    </div>
  </div>
</div>



<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>

</body>
</html>
