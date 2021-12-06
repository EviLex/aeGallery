<?php
/* Красивая функция вывода масивов */
if(!function_exists('prr')){function prr($str){echo "<pre style='overflow:auto'>";print_r($str);echo "</pre>\r\n";}}
if(!function_exists('prrc')){function prrc($str){echo "<pre>";print_r('<script>console.log('.json_encode($str).')</script>');echo "</pre>";}}

$url = $GLOBALS["url"] = '/'.explode('/',$_SERVER['REQUEST_URI'])[1]; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <?php if(isset($_POST['submit'])) echo "<meta http-equiv='refresh' content='0'>";?>
    <title><?php if(!isset($get)) { echo "aeGallery"; } else { echo "aeGallery - ".$get.""; } ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js" async></script> -->
    <script type="text/javascript" src="//cdn.jsdelivr.net/fancybox/2.1.5/jquery.fancybox.pack.js"></script>
    <script type="text/javascript" src="<?php echo $url ?>/dev/livereload.js"></script>
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/fancybox/2.1.5/jquery.fancybox.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="<?php echo $url ?>/dev/style.css" media="screen" />
    <!-- <style></style> -->
  </head>
<body>
<?php

session_start();
// session_regenerate_id(true);
$ad             = 'smart.local';
// $domain         = 'smarttechnologies-ua.com';
$get            = $_GET ? $_GET['g'] : null;
$get_no_slash   = rtrim($get,chr(47));
$GLOBALS["exclude"] = ['dev','cache','profile','ToDoSmart','.css','.php','.db','.m3u']; //You can now disable multiple folders from showing up in the list.
$allowed_types  = ['png','jpg','jpeg','gif','.txt'
                  ,'mp3' =>'audio'
                  ,'m4a' =>'audio'
                  ,'mp4' =>'video'
                  ,'pdf' =>'office'
                  ,'ppt' =>'office'
                  ,'pptx'=>'office'
                  ,'doc' =>'office'
                  ,'docx'=>'office'
                  ,'xls' =>'office'
                  ,'xlsx'=>'office'];
// $pass           = ['Administrators'          => 'Sm@rt2021!'
//                   ,'Администраторы'          => 'Sm@rt2021!'
//                   ,'Руководство'             => 'Sm@rt2021!'
//                   ,'Бухгалтерия'             => 'SmartBuh2021!'
//                   ,'Менеджера'               => 'Smart2021!'
//                   ,'Технические специалисты' => 'Smart2021!'
//                   ,'Юридический отдел'       => 'Smart2021!'
//                   ,'Уволенные'               => 'Smart2021x!'];
$audio_types    = array_intersect($allowed_types,['audio']);
$video_types    = array_intersect($allowed_types,['video']);
$office_types   = array_intersect($allowed_types,['office']);
$GLOBALS["dir"] = './'; //Z:/ ./
$show_menu      = 0;

function retrieves_users($ad='',$user='',$pas='',$del=null,$pass=null,$rights=null){
  $ldap     = ldap_connect("ldap://".$ad) or die("Couldn't connect to AD!");
  ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
  ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
  $bd       = ldap_bind($ldap,$user,$pas) or die("Couldn't bind to AD!");
  $pageSize = 100; // enable pagination with a page size of 100.
  $cookie   = '';
  do {
    ldap_control_paged_result($ldap, $pageSize, true, $cookie);
    $groups = ldap_search($ldap,'OU=SM,DC=smart,DC=local','(ou=*)', array());
    $groups_entries = ldap_get_entries($ldap, $groups);
    if(!empty($groups_entries)){
      for ($i = 0; $i < $groups_entries["count"]; $i++) {
        $users = ldap_search($ldap, $groups_entries[$i]["dn"],'(cn=*)', array());
        $users_entries = ldap_get_entries($ldap, $users);
        for ($u = 0; $u < $users_entries["count"]; $u++) {
          unset($users_entries[$u]['objectclass']);
          for ($c = 0; $c < $users_entries[$u]["count"]; $c++)
            unset($users_entries[$u][$c]);
          foreach ($users_entries[$u] as $k => $ud){
            if( $k !== 'count' && $k !== 'dn' )
              if( $ud['count'] == 1 ) $users_entries[$u][$k] = $ud[0];
              else unset( $users_entries[$u][$k]['count'] );
            if( $k == 'manager' ) $users_entries[$u][$k] = str_replace("CN=","",current(explode(",",$users_entries[$u][$k])));
            if( $k == 'directreports' || $k == 'memberof' )
              if( is_array($ud) )
                foreach ($ud as $dk => $dr)
                  if( $dk !== 'count' ) $users_entries[$u][$k][$dk] = str_replace("CN=","",current(explode(",",$ud[$dk])));
          }
          // $users_entries[$u]['samaccountname'] = [m.dovbenko] => Array | $users_entries[$u]['cn'] = [Mikhail Dovbenko] => Array
          if( $groups_entries[$i]['name'][0] !== 'Groups' && $groups_entries[$i]['name'][0] !== 'SM' ){
            $dn = explode(',',$users_entries[$u]['dn'] );
            // $users_entries[$u]['pass'] = $pass[str_replace("OU=", "",$dn[1])];
            if( $del ) {
              if( str_replace("OU=", "",$dn[1]) !== $del)
                if( $rights ) $data[ $groups_entries[$i]['name'][0] ][ $users_entries[$u]['cn'] ] = $users_entries[$u];
                else $data[ $users_entries[$u]['cn'] ] = $users_entries[$u];
            }else{
              if( $rights ) $data[ $groups_entries[$i]['name'][0] ][ $users_entries[$u]['cn'] ] = $users_entries[$u];
              else $data[ $users_entries[$u]['cn'] ] = $users_entries[$u];
            }
          }
        }
      }
    }
    ldap_control_paged_result_response($ldap, $groups, $cookie);
    ldap_unbind($ldap); // Clean up after ourselves.
  } while($cookie !== null && $cookie != '');
  return $data;
}

function strposa($haystack, $needles=array(), $offset=0) {
  $chr = array();
  foreach($needles as $needle) {
    $res = strpos($haystack, $needle, $offset);
    if ($res !== false) $chr[$needle] = $res;
  }
  if(empty($chr)) return false;
  return min($chr);
}

function dirtree($dir, $ignoreEmpty=false, $regex=''){
  if (!$dir instanceof DirectoryIterator)
    $dir = new DirectoryIterator((string)$dir);
  $dirs  = array();
  $files = array();
  // prr($dir);
  foreach ($dir as $node) {
    // prr($node->getGroup());
    if ($node->isDir() && !$node->isDot()) {
      $tree = dirtree($node->getPathname(), $regex, $ignoreEmpty);
      if (!$ignoreEmpty || count($tree))
        $dirs[$node->getFilename()] = $tree;
    } elseif ($node->isFile()) {
      $name = $node->getFilename();
      if ('' == $regex || preg_match($regex, $name)){
        $nameEx = explode(".",$name);
        if( !in_array(".".end($nameEx),$GLOBALS["exclude"]) )
          $files[] = $name;
      }
    }
  }
  asort($dirs);
  sort($files);

  return array_merge($dirs, $files);
}
$dirtree = dirtree($dir,0,'');
$dirtree = array_diff_key($dirtree,$GLOBALS["exclude"]); // Exclude folders
// $dirtree = array_diff($dirtree,$GLOBALS["exclude"]); // Exclude file types

function tree($array,$dirOnly=0,$get='',$path='',$count=0,$parent=null,$c=0){
  $i = $count;
  echo $c !== 0 ? "<ul class='sub-menu'>\n" : null; // отключаем первый ul
  // if( !in_array((string)$extn,$GLOBALS["exclude"]) )
  foreach($array as $k => $v) {
    if( !in_array((string)$k,$GLOBALS["exclude"]) ){
      if (is_array($v)) {
        $path .= $k.'/';
        $getArr = explode('/',$get);
        echo "<li class='has-children".(in_array($k,$getArr) ? ' current"' : null )."'><a href='".$GLOBALS["url"]."/index.php?g=".$GLOBALS["dir"].rtrim($path,chr(47))."'>".str_replace('_',' ',$k)."</a>\n";
        tree($v,$dirOnly,$get,$path,$count,$parent,$c);
        $path = str_replace($k.'/', '',$path);
        $count++;
        continue;
      }
      echo $dirOnly == 0 ? "<li><a href='".$path.$v."' target='_blank'>$v</a></li>\n" : null; //http://file:///".$GLOBALS["dir"].
    }$c++;
  }
  echo $c !== 0 ? "</li>\n</ul>\n" : null;

}

// function hierarchyOFexploit(array $users, $parentId = '') {
//   $branch = array();
//   foreach ($users as $user) {
//     if( $user['manager'] ){
//       prr($parentId);
//       if ((string)$user['manager'] == (string)$parentId) {
//         $children = buildTree($elements, $user['cn']);
//         if ($children) {
//           $user['children'] = $children;
//         }
//         $branch[] = $user;
//       }
//     }else
//       $branch[] = $user;
//   }
//   return $branch;
// }
// prr(hierarchyOFexploit($ad_users));

// $ldap = ldap_connect("ldap://".$ad);
// $ad = explode('.',$ad);
// $ldaprdn  = $ad[0] . "\\a.egorov";
// ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
// ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
// $bind = @ldap_bind($ldap, $ldaprdn, '2lexsmart!');
// $filter="(sAMAccountName=a.egorov)";
// $result = ldap_search($ldap,"dc={$ad[0]},dc={$ad[1]}",$filter);
// // ldap_sort($ldap,$result,"sn");
// $info = ldap_get_entries($ldap, $result);
// $infon= [];
// foreach ($info[0] as $k => $ud){
//   if( is_array($ud) )
//     if( $k !== 'count' && $k !== 'dn' )
//       if( $ud['count'] == 1 ) $infon[$k] = $ud[0];
//       else unset( $infon[$k]['count'] );
//   if( $k == 'manager' && is_array($ud) ) $infon[$k] = str_replace("CN=","",current(explode(",",$ud[0])));
//   if( $k == 'directreports' || $k == 'memberof' )
//    if( is_array($ud) )
//     foreach ($ud as $dk => $dr)
//       if( $dk !== 'count' ) $infon[$k][$dk] = str_replace("CN=","",current(explode(",",$ud[$dk])));
// }
// $infon['dn'] = $info[0]['dn'];
// prr($infon);

/* $_POST */
if( isset($_POST['logout']) ) session_destroy();
if(isset($_POST['username']) && isset($_POST['password'])){
  $ldap = ldap_connect("ldap://".$ad);
  $ad = explode('.',$ad);
  $ldaprdn  = $ad[0] . "\\" . $_POST['username'];
  ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
  $bind = @ldap_bind($ldap, $ldaprdn, $_POST['password']);

  if ($bind) {
    $filter="(sAMAccountName={$_POST['username']})";
    $result = ldap_search($ldap,"dc={$ad[0]},dc={$ad[1]}",$filter);
    // ldap_sort($ldap,$result,"sn");
    $info = ldap_get_entries($ldap, $result);
    $infon= [];
    foreach ($info[0] as $k => $ud){
      if( is_array($ud) )
        if( $k !== 'count' && $k !== 'dn' )
          if( $ud['count'] == 1 ) $infon[$k] = $ud[0];
          else unset( $infon[$k]['count'] );
      if( $k == 'manager' && is_array($ud) ) $infon[$k] = str_replace("CN=","",current(explode(",",$ud[0])));
      if( $k == 'directreports' || $k == 'memberof' )
       if( is_array($ud) )
        foreach ($ud as $dk => $dr)
          if( $dk !== 'count' ) $infon[$k][$dk] = str_replace("CN=","",current(explode(",",$ud[$dk])));
    }
    $infon['dn'] = $info[0]['dn'];
    $_SESSION["profile"] = $infon;
    $_SESSION["ad_users"] = retrieves_users($ad[0].'.'.$ad[1],'smart\a.egorov','2lexsmart!','Уволенные'); // 'ldap ad domain name','ad user for connect','ad password for connect','масив паролей по группам','спрятать уволенных, указать название группы с уволенными'

    // for ($i=0; $i<$info["count"]; $i++){
    //     if($info['count'] > 1)
    //         break;
    //     // echo "<p>You are accessing <strong> ". $info[$i]["sn"][0] .", " . $info[$i]["givenname"][0] ."</strong><br /> (" . $info[$i]["samaccountname"][0] .")</p>\n";
    //
    //     // $userDn = $info[$i]["distinguishedname"][0];
    // }
    @ldap_close($ldap);
  } else {
    echo "Invalid email address / password"; ?>
    <div class="container">
      <form action="./" method="POST" class="box">
          <input id="username" type="text" name="username" placeholder="Username" />
          <input id="password" type="password" name="password" placeholder="Password" />
          <input type="submit" name="submit" value="Log In" />
      </form>
    </div>
    <?php
  }
}elseif( empty($_SESSION) ){ ?>
  <div class="container">
    <form action="./" method="POST" class="box">
        <input id="username" type="text" name="username" placeholder="Username" />
        <input id="password" type="password" name="password" placeholder="Password" />
        <input type="submit" name="submit" value="Log In" />
    </form>
  </div>
<?php }
if( !empty($_SESSION) ){
  $info = $_SESSION["profile"];
  // ['manager'] - начальник, [directreports] - подчиненые
  $s_profile = $_SESSION['profile'];
  $s_ad_users = $_SESSION['ad_users'];
  // prr($s_profile);
  // prr($s_ad_users);

  if( is_array($s_profile['directreports']) )
    foreach ($s_profile['directreports'] as $ke => $val)
      $directreports[] = $s_ad_users[$val];

  // prr($profile);
  // структура
  $profile = [
     'Привет, ' => $s_profile['givenname'].' '.$s_profile['sn']
    ,'Начальник' => $s_ad_users[$s_profile['manager']]
    ,'Подчиненные' => $directreports
  ];
  // [Mikhail Dovbenko] => Array
  //   [cn] => Mikhail Dovbenko
  //   [sn] => Довбенко
  //   [c] => UA
  //   [l] => Город
  //   [st] => Область, край
  //   [title] => Должность
  //   [description] => Описание
  //   [postalcode] => Индекс
  //   [postofficebox] => Почтовый ящик
  //   [physicaldeliveryofficename] => Комната
  //   [telephonenumber] => +380933664314
  //   [facsimiletelephonenumber] => Факс
  //   [givenname] => Михаил
  //   [initials] => MD
  //   [distinguishedname] => CN=Mikhail Dovbenko,OU=Administrators,OU=SM,DC=smart,DC=local
  //   [instancetype] => 4
  //   [whencreated] => 20190527123609.0Z
  //   [whenchanged] => 20211125141515.0Z
  //   [displayname] => Mikhail Dovbenko
  //   [othertelephone] => Array
  //           [0] => +380931111111
  //           [1] => +380930000000
  //   [usncreated] => 8403
  //   [info] => Заметки телефоны
  //   [memberof] => Array
  //           [0] => RDS_User_1C
  //           [1] => RDS_User
  //           [2] => FS_Tech
  //           [3] => DnsAdmins
  //           [4] => Пользователи удаленного управления
  //           [5] => Администраторы Hyper-V
  //           [6] => Владельцы-создатели групповой политики
  //           [7] => Администраторы предприятия
  //           [8] => Администраторы схемы
  //           [9] => Администраторы домена
  //           [10] => Пользователи удаленного рабочего стола
  //           [11] => Администраторы
  //   [usnchanged] => 18231270
  //   [co] => Украина
  //   [department] => Отдел
  //   [company] => Организация
  //   [streetaddress] => Улица
  //   [directreports] => Array
  //           [0] => Alex Egorov
  //           [1] => Sergey Lisetskiy
  //           [2] => Sergey Milevskiy
  //           [3] => Andrey Gridin
  //           [4] => Aleksandr Voznyy
  //           [5] => Yuriy Gomel
  //   [wwwhomepage] => Веб-страница
  //   [name] => Mikhail Dovbenko
  //   [objectguid] => �M�4w�A��=�� ��
  //   [useraccountcontrol] => 66048
  //   [badpwdcount] => 0
  //   [codepage] => 0
  //   [countrycode] => 804
  //   [badpasswordtime] => 132823268895343754
  //   [lastlogon] => 132823318869699681
  //   [pwdlastset] => 132470494190013741
  //   [primarygroupid] => 513
  //   [userparameters] => m:                    d	«f«f«i
  //   [objectsid] => ǆ�N��f��RP
  //   [admincount] => 1
  //   [accountexpires] => 9223372036854775807
  //   [logoncount] => 65535
  //   [samaccountname] => m.dovbenko
  //   [samaccounttype] => 805306368
  //   [userprincipalname] => m.dovbenko@smart.local
  //   [lockouttime] => 0
  //   [ipphone] => IP телефон
  //   [objectcategory] => CN=Person,CN=Schema,CN=Configuration,DC=smart,DC=local
  //   [msnpallowdialin] => TRUE
  //   [msrassavedcallbacknumber] => 10.2.2.245
  //   [dscorepropagationdata] => Array
  //           [0] => 20200428181631.0Z
  //           [1] => 16010101000001.0Z
  //   [lastlogontimestamp] => 132822042921228895
  //   [mstsexpiredate] => 20220118115555.0Z
  //   [mstslicenseversion] => 393218
  //   [mstsmanagingls] => 00252-20000-35572-AT477
  //   [mstslicenseversion2] => 7
  //   [mstslicenseversion3] => C50-6.02-S
  //   [mail] => m.dovbenko@smarttechnologies-ua.com
  //   [manager] => Pavel Murzha
  //   [homephone] => Домашний телефон
  //   [mobile] => Мобильный телефон
  //   [pager] => Пейджер
  //   [count] => 66
  //   [dn] => CN=Mikhail Dovbenko,OU=Administrators,OU=SM,DC=smart,DC=local
  ?>
  <nav id="navegacio" class="mainmenu mshow<?php echo $show_menu ? ' open' : null?>">
    <!-- <a href="/" id="mlogo" class="mshow">Site name</a> -->
    <button class="js-mobilenav-toggle toggle mshow<?php echo $show_menu ? ' active' : null?>"><span class="xicon"></span></button>
    <div class="menu-mobile-container">
      <div id="hello">
        <?php
        foreach ($profile as $k => $data){
          if( $k == 'Начальник' ){
            echo '<b>'.$k.': </b> '
                .(!empty($data['mail'])?'<a href="mailto:'.$data['mail'].'">'.$data['givenname'].(!empty($data['sn'])?' '.$data['sn'].'</a>':'</a>'):$data['givenname'].(!empty($data['sn'])?' '.$data['sn']:null))
                .(!empty($data['ipphone'])?' | SIP:'.$data['ipphone']:null)
                .(!empty($data['telephonenumber'])?' | '.$data['telephonenumber']:null)
                .'<br/>';
          }elseif( $k == 'Подчиненные' && is_array($data) ){
            $i=0;$len=count($data);
            echo '<b>'.$k.': </b>';
            foreach ($data as $d){
              echo (!empty($d['mail'])?'<a href="mailto:'.$d['mail'].'">'.$d['givenname'].(!empty($d['sn'])?' '.$d['sn'].'</a>':'</a>'):$d['givenname'].(!empty($d['sn'])?' '.$d['sn']:null))
                  .(!empty($d['ipphone'])?' | SIP:'.$d['ipphone']:null)
                  .(!empty($d['telephonenumber'])?' | '.$d['telephonenumber']:null)
                  .($i == $len-1?null:', ');
              $i++;
            }
          }elseif($k == 'Подчиненные')
            echo '<b>'.$k.': </b> '.(!empty($data['mail'])?'<a href="mailto:'.$data['mail'].'">'.$data['givenname'].(!empty($data['sn'])?' '.$data['sn'].'</a>':'</a>'):$data['givenname'].(!empty($data['sn'])?' '.$data['sn']:null)).'<br/>';
          else
            echo "<b>".$k." </b> <a href='$url/profile'>$data</a> <form action='./' method='POST'><input type='hidden' name='logout' /><input id='logout' type='submit' name='submit' value='Log Out' /></form><br/>";
        } ?></div>
      <ul id="mobile_main_menu" class="wrapper mobile-nav<?php echo $show_menu ? ' open' : null?>">
        <li class="current"><a href="<?php echo $url ?>">Главная</a></li>
        <li class=""><a href="<?php echo $url ?>/ToDoSmart">ToDoSmart</a></li>
        <!-- <li class=""><a href="https://docs.google.com/spreadsheets/d/1AkkzGJ5iIDy9e5LeUNfzf0i2t2QFK-T3R7uc20aaY_U/edit#gid=1750051146" target="_blank">ToDoSmart</a></li> -->
        <!-- <li class="has-children"><a href="./">Папки</a></li> -->
        <?php tree($dirtree,0,$get_no_slash); ?>
      </ul>
    </div>
    </nav><?php }
// session_id($session_id_to_destroy);
// session_start();
// session_destroy();
// session_commit();
// prr($_SESSION);
// prr( session_id() );
// prr( array_keys($ad_users) );
// echo '<select id="login">';
// foreach ($ad_users as $user => $user_data) {
//   echo "<option value='$user'>{$user_data['displayname']}</option>";
// }
// echo '</select>';

/*  */
// echo tree($dirtree,$get);
// prr($dirtree);
// function in_array_multi( $needle, array $haystack ) { // поиск
// 	if ( ! is_array( $haystack ) ) return false;
//
// 	foreach ( $haystack as $key => $value ) {
// 		if ( $value == $needle ) {
// 			return $key;
// 		} else if ( is_array( $value ) ) {
// 			// multi search
// 			$key_result = in_array_multi( $needle, $value );
// 			if ( $key_result !== false ) {
// 				return $key . '_' . $key_result;
// 			}
// 		}
// 	}
// 	return false;
// }
// $key_path = in_array_multi( 'TEST', $test );
// if ( $key_path !== false ) {
//   echo $key_path;
// }
// if (strposa($file, $GLOBALS["exclude"], 1) || in_array($file, $GLOBALS["exclude"]) === true){   }
// prr($excFolders);

// namespace ldap;
// abstract class AuthStatus
// {
//     const FAIL = "Authentication failed";
//     const OK = "Authentication OK";
//     const SERVER_FAIL = "Unable to connect to LDAP server";
//     const ANONYMOUS = "Anonymous log on";
// }
//
// // The LDAP server
// class LDAP
// {
//     private $server = "127.0.0.1";
//     private $domain = "localhost";
//     private $admin = "admin";
//     private $password = "";
//
//     public function __construct($server, $domain, $admin = "", $password = "")
//     {
//         $this->server = $server;
//         $this->domain = $domain;
//         $this->admin = $admin;
//         $this->password = $password;
//     }
//
//     // Authenticate the against server the domain\username and password combination.
//     public function authenticate($user)
//     {
//         $user->auth_status = AuthStatus::FAIL;
//
//         $ldap = ldap_connect($this->server) or $user->auth_status = AuthStatus::SERVER_FAIL;
//         ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
//         ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
//         $ldapbind = ldap_bind($ldap, $user->username.$this->domain, $user->password);
//
//         if($ldapbind)
//         {
//             if(empty($user->password))
//             {
//                 $user->auth_status = AuthStatus::ANONYMOUS;
//             }
//             else
//             {
//                 $result = $user->auth_status = AuthStatus::OK;
//
//                 $this->_get_user_info($ldap, $user);
//             }
//         }
//         else
//         {
//             $result = $user->auth_status = AuthStatus::FAIL;
//         }
//
//         ldap_close($ldap);
//     }
//
//     // Get an array of users or return false on error
//     public function get_users()
//     {
//         if(!($ldap = ldap_connect($this->server))) return false;
//
//         ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
//         ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
//         $ldapbind = ldap_bind($ldap, $this->admin.$this->domain, $this->password);
//
//         $dc = explode(".", $this->domain);
//         $base_dn = "";
//         foreach($dc as $_dc) $base_dn .= "dc=".$_dc.",";
//         $base_dn = substr($base_dn, 0, -1);
//         $sr=ldap_search($ldap, $base_dn, "(&(objectClass=user)(objectCategory=person)(|(mail=*)(telephonenumber=*))(!(userAccountControl:1.2.840.113556.1.4.803:=2)))", array("cn", "dn", "memberof", "mail", "telephonenumber", "othertelephone", "mobile", "ipphone", "department", "title"));
//         $info = ldap_get_entries($ldap, $sr);
//
//         for($i = 0; $i < $info["count"]; $i++)
//         {
//             $users[$i]["name"] = $info[$i]["cn"][0];
//             $users[$i]["mail"] = $info[$i]["mail"][0];
//             $users[$i]["mobile"] = $info[$i]["mobile"][0];
//             $users[$i]["skype"] = $info[$i]["ipphone"][0];
//             $users[$i]["telephone"] = $info[$i]["telephonenumber"][0];
//             $users[$i]["department"] = $info[$i]["department"][0];
//             $users[$i]["title"] = $info[$i]["title"][0];
//
//             for($t = 0; $t < $info[$i]["othertelephone"]["count"]; $t++)
//                 $users[$i]["othertelephone"][$t] = $info[$i]["othertelephone"][$t];
//
//             // set to empty array
//             if(!is_array($users[$i]["othertelephone"])) $users[$i]["othertelephone"] = Array();
//         }
//
//         return $users;
//     }
//
//     private function _get_user_info($ldap, $user)
//     {
//         $dc = explode(".", $this->domain);
//
//         $base_dn = "";
//         foreach($dc as $_dc) $base_dn .= "dc=".$_dc.",";
//
//         $base_dn = substr($base_dn, 0, -1);
//
//         $sr=ldap_search($ldap, $base_dn, "(&(objectClass=user)(objectCategory=person)(samaccountname=".$user->username."))", array("cn", "dn", "memberof", "mail", "telephonenumber", "othertelephone", "mobile", "ipphone", "department", "title"));
//         $info = ldap_get_entries($ldap, $sr);
//
//         $user->groups = Array();
//         for($i = 0; $i < $info[0]["memberof"]["count"]; $i++)
//             array_push($user->groups, $info[0]["memberof"][$i]);
//
//         $user->name = $info[0]["cn"][0];
//         $user->dn = $info[0]["dn"];
//         $user->mail = $info[0]["mail"][0];
//         $user->telephone = $info[0]["telephonenumber"][0];
//         $user->mobile = $info[0]["mobile"][0];
//         $user->skype = $info[0]["ipphone"][0];
//         $user->department = $info[0]["department"][0];
//         $user->title = $info[0]["title"][0];
//
//         for($t = 0; $t < $info[$i]["othertelephone"]["count"]; $t++)
//                 $user->other_telephone[$t] = $info[$i]["othertelephone"][$t];
//
//         if(!is_array($user->other_telephone[$t])) $user->other_telephone[$t] = Array();
//     }
// }
//
// class User
// {
//     var $auth_status = AuthStatus::FAIL;
//     var $username = "Anonymous";
//     var $password = "";
//
//     var $groups = Array();
//     var $dn = "";
//     var $name = "";
//     var $mail = "";
//     var $telephone = "";
//     var $other_telephone = Array();
//     var $mobile = "";
//     var $skype = "";
//     var $department = "";
//     var $title = "";
//
//     public function __construct($username, $password)
//     {
//         $this->auth_status = AuthStatus::FAIL;
//         $this->username = $username;
//         $this->password = $password;
//     }
//
//     public function get_auth_status()
//     {
//         return $this->auth_status;
//     }
//  }
// $ldap = new LDAP("smart.local", "", "smart\a.egorov", "2lexsmart!"); //ST-AD-01.smart.local @smarttechnologies-ua.com
// $users = $ldap->get_users();

/**
 * Get a list of users from Active Directory.
 */
// $ldap_password = '2lexsmart!';
// $ldap_username = 'a.egorov@smarttechnologies-ua.com';
// $ldap_connection = ldap_connect('10.2.2.250');
// if (FALSE === $ldap_connection){
//     // Uh-oh, something is wrong...
// }
// ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
// ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.
// if (TRUE === ldap_bind($ldap_connection, $ldap_username, $ldap_password)){
//     $ldap_base_dn = 'DC=XXXX,DC=XXXX';
//     $search_filter = '(&(objectCategory=person)(samaccountname=*))';
//     $attributes = array();
//     $attributes[] = 'givenname';
//     $attributes[] = 'mail';
//     $attributes[] = 'samaccountname';
//     $attributes[] = 'sn';
//     $result = ldap_search($ldap_connection, $ldap_base_dn, $search_filter, $attributes);
//     if (FALSE !== $result){
//         $entries = ldap_get_entries($ldap_connection, $result);
//         for ($x=0; $x<$entries['count']; $x++){
//             if (!empty($entries[$x]['givenname'][0]) &&
//                  !empty($entries[$x]['mail'][0]) &&
//                  !empty($entries[$x]['samaccountname'][0]) &&
//                  !empty($entries[$x]['sn'][0]) &&
//                  'Shop' !== $entries[$x]['sn'][0] &&
//                  'Account' !== $entries[$x]['sn'][0]){
//                 $ad_users[strtoupper(trim($entries[$x]['samaccountname'][0]))] = array('email' => strtolower(trim($entries[$x]['mail'][0])),'first_name' => trim($entries[$x]['givenname'][0]),'last_name' => trim($entries[$x]['sn'][0]));
//             }
//         }
//     }
//     ldap_unbind($ldap_connection); // Clean up after ourselves.
// }
// $message .= "Retrieved ". count($ad_users) ." Active Directory users\n";

function readAudioData() {
  // Open the file.
  $fileHandle = fopen($this->file, "rb");

  // Skip header.
  $offset = $this->headerOffset($fileHandle);
  fseek($fileHandle, $offset, SEEK_SET);

  while (!feof($fileHandle)) {
    // We nibble away at the file, 10 bytes at a time.
    $block = fread($fileHandle, 8);
    if (strlen($block) < 8)
      break;
    //looking for 1111 1111 111 (frame synchronization bits)
    else if ($block[0] == "\xff" && (ord($block[1]) & 0xe0)) {
      $fourbytes = substr($block, 0, 4);
      // The first block of bytes will always be 0xff in the framesync
      // so we ignore $fourbytes[0] but need to process $fourbytes[1] for
      // the version information.
      $b1 = ord($fourbytes[1]);
      $b2 = ord($fourbytes[2]);
      $b3 = ord($fourbytes[3]);

      // Extract the version and create a simple version for lookup.
      $version = $this->versions[($b1 & 0x18) >> 3];
      $simpleVersion = ($version == '2.5' ? 2 : $version);

      // Extract layer.
      $layer = $this->layers[($b1 & 0x06) >> 1];

      // Extract protection bit.
      $protectionBit = ($b1 & 0x01);

      // Extract bitrate.
      $bitrateKey = sprintf('V%dL%d', $simpleVersion, $layer);
      $bitrateId = ($b2 & 0xf0) >> 4;
      $bitrate = isset($this->bitrates[$bitrateKey][$bitrateId]) ? $this->bitrates[$bitrateKey][$bitrateId] : 0;

      // Extract the sample rate.
      $sampleRateId = ($b2 & 0x0c) >> 2;
      $sampleRate = isset($this->samplerates[$version][$sampleRateId]) ? $this->samplerates[$version][$sampleRateId] : 0;

      // Extract padding bit.
      $paddingBit = ($b2 & 0x02) >> 1;

      // Extract framesize.
      if ($layer == 1)
        $framesize = intval(((12 * $bitrate * 1000 / $sampleRate) + $paddingBit) * 4);
      else // Later 2 and 3.
        $framesize = intval(((144 * $bitrate * 1000) / $sampleRate) + $paddingBit);

      // Extract samples.
      $frameSamples = $this->samples[$simpleVersion][$layer];

      // Extract other bits.
      $channelModeBits = ($b3 & 0xc0) >> 6;
      $modeExtensionBits = ($b3 & 0x30) >> 4;
      $copyrightBit = ($b3 & 0x08) >> 3;
      $originalBit = ($b3 & 0x04) >> 2;
      $emphasis = ($b3 & 0x03);

      // Calculate the duration and add this to the running total.
      $this->duration += ($frameSamples / $sampleRate);

      // Read the frame data into memory.
      $frameData = fread($fileHandle, $framesize - 8);

      // do something with the frame data.
    }
    else if (substr($block, 0, 3) == 'TAG')// If this is a tag then jump over it.
      fseek($fileHandle, 128 - 10, SEEK_CUR);
    else
      fseek($fileHandle, -9, SEEK_CUR);
  }
}
