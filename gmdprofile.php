<?php
error_reporting(E_ALL);
ini_set("display_errors", "on");
use function RedBeanPHP\with;
use wkhtmltox\PDF\Object;
require "inc/db.php";
?>


<?php
R::exec('DELETE FROM uads WHERE time < DATE_SUB(NOW(), INTERVAL 7 DAY)');
R::exec('DELETE FROM adsauction WHERE time < DATE_SUB(NOW(), INTERVAL 14 DAY)');
$user = R::findOne('users','id = ?', array($_SESSION['logged_user']->id));
$ads = R::findOne('uads', 'users_id = ? ORDER BY id DESC', [$user->id]);
$sql = R::getAll('SELECT * FROM uads WHERE (users_id = ?) ORDER BY id DESC', [$user->id]);
$sql2 = R::getAll('SELECT * FROM adsauction WHERE (users_id = ?) ORDER BY id DESC', [$user->id]);
$position_uac = R::getCol('SELECT auc_position FROM adsauction');

function LoadAvatar($img){
    $type = $img['type'];
    $name = md5(microtime()).'.'.substr($type, strlen("image/"));
    $dir = 'uploads/avatars/';
    $uploadfile = $dir.$name;

    if(move_uploaded_file($img['tmp_name'], $uploadfile)){
        $user = R::findOne('users','id = ?', array($_SESSION['logged_user']->id));
        $user->avatar = $name;
        R::store($user);

    }else{
        return false;
    }
    return true;
}
if($_SESSION == NULL) header('Location: https://gmdhub.com/gmdlogin.php');
    if(!$user) header ('Location: https://gmdhub.com/gmdlogin.php');
if(isset($_POST['set-avatar'])){
    $img = $_FILES['avatar'];

    if(imgSecurity($img)) LoadAvatar($img);
    echo "<meta http-equiv='refresh' content='0'>";
}


function Upload($up){
    $type1 = $up['type'];
    $name1 = md5(microtime()).'.'.substr($type1, strlen("image/"));
    $dir1 = 'uploads/photos/';
    $uploadfile1 = $dir1.$name1;

    if(move_uploaded_file($up['tmp_name'], $uploadfile1)){
        $user = R::findOne('users','id = ?', array($_SESSION['logged_user']->id));
        $data = $_POST;
        $ads = R::dispense('uads');
        $ads->ads = $name1;
        $ads->title = $data['title'];
        $ads->descript = $data['descript'];
        $ads->price = $data['price'];
        $ads->category = $data['category'];
        $ads->regions = $data['regions'];
        $ads->confirm = $data['confirm'] = '0';
        if($user->status === 'hHSDFAoKM98hDNCJIA7cC9C4J3NGN45G97bbytSDF9P'){
            $ads->confirm = $data['confirm'] = '1';
        }
        $ads->time = R::isoDateTime();
        $ads->avatar = $user->avatar;
        $ads->login = $user->login;
        $user->ownAdsList[] = $ads;
        R::store($user);

    }else{
        return false;
    }
    return true;
}
    
if(isset($_POST['submit-ads'])){
    $up = $_FILES['ads'];
    if(uploadSecurity($up)) Upload($up);
    $user = R::findOne('users','id = ?', array($_SESSION['logged_user']->id));
    $ads = R::findOne('uads', 'users_id = ? ORDER BY id DESC', [$user->id]);
    // $regions = R::findOne('regions', 'id= ? ORDER BY id DESC', [$regions->id]);
        $_SESSION['logged_user']->lastImage = $ads->ads;
        $_SESSION['logged_user']->lastTitle = $ads->title;
        $_SESSION['logged_user']->lastDescript = $ads->descript;
        $_SESSION['logged_user']->lastPrice = $ads->price;
        $_SESSION['logged_user']->lastCategory = $ads->category;
        $_SESSION['logged_user']->region = $ads->region;
        $_SESSION['logged_user']->time = $ads->time;
        $_SESSION['logged_user']->confirm = $ads->confirm;
        echo "<meta http-equiv='refresh' content='0'>";
}else{
    $defaultImage = "defaultimage.png";
}

$radio = $_POST;
$checkbox = $_POST;
$radio_onetwo = $_POST;
$id_delete = $_POST; // пересмотреть
if(isset($radio['submit-auc'])){
    if(isset($radio_onetwo['onetwo'])){
    class customException extends Exception {
        function errorMessage(){
            $errorMsg = 'Ошибка, попробуйте снова.';
            return $errorMsg;
        }
    }
    try {
        $user = R::findOne('users','id = ?', array($_SESSION['logged_user']->id));
        $auc = R::dispense('adsauction');
        $auc->auc_position = $radio['choosen'];
        $auc->auc_ads = $radio['onetwo'];
        $auc->price_auc = $radio['price-auc'];
        foreach($sql as $k => $elements){
            if($_POST['onetwo'] == $k){
                $auc->image = $elements['ads'];
                $auc->title = $elements['title'];
                $auc->description = $elements['descript'];
                $auc->region= $elements['regions'];
                $auc->category = $elements['category'];
                $auc->price = $elements['price'];
                $auc->avatar = $elements['avatar'];
                $auc->login = $elements['login'];
            }
        }
        $auc->time = R::isoDateTime();
        $auc->confirm = $radio['confirm-auc'] = '0';
        if($user->status === 'hHSDFAoKM98hDNCJIA7cC9C4J3NGN45G97bbytSDF9P'){
            $auc->confirm = $radio['confirm-auc'] = '1';
        }
        $user->ownAucList[] = $auc;
        
        $ads = R::load('uads', $id_delete['delete']); // пересмотреть
        R::exec("DELETE FROM uads WHERE id=".$id_delete['delete'].""); // пересмотреть

        R::store($user);
        echo "<meta http-equiv='refresh' content='0'>";
        if($auc->price < $user->balance){
            R::exec('UPDATE users SET `balance` = `balance` - '.$auc->price.' WHERE id = '.$_SESSION['logged_user']->id.'');
        }else{
            $time = R::isoDateTime();
            $find = R::findOne('adsauction', 'time = ?',[$time]);
            $delete = R::load('adsauction', $find->id);
            R::trash($delete);
            echo '<div class="up-balance">Недостаточно средств!</div>';
        }
        if(!isset($radio_onetwo['onetwo']) && $auc->price < $user->balance){
            R::exec('UPDATE users SET `balance` = `balance` + '.$auc->price.' WHERE id = '.$_SESSION['logged_user']->id.'');
            $time = R::isoDateTime();
            $find = R::findOne('adsauction', 'time = ?',[$time]);
            $delete = R::load('adsauction', $find->id);
            R::trash($delete);
            echo '<div class="info-auc">Вы не выбрали объявление!</div>';
        }
    }
    catch(customException $e){
        echo $e->errorMessage();
    }
    }
}
?>
<?php   
if(isset($_SESSION['logged_user'])): 
?>

<!DOCTYPE html>
<html lang="ru, en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer-when-downgrade">
    <title>GMD Hub</title>
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/profile.css?v001">
    <link rel="stylesheet" href="css/g-stats.css?v001">
    <link rel="stylesheet" href="media/profile.css?v001">
    <link rel="icon" href="img/favicon.png" type="image/png" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body>
    <header>
        <div class="header-top">
            <div class="width-nav">
                <a href="index.php">
                    <span class="material-icons unselectable">
                    cottage
                </span>
                </a>
                <a class="back-link" href="index.php">На главную</a>
            </div>
            <p class="GMD">Личный кабинет</p>
            <div class="width-nav">
                <a href="./logout.php" class="logout">Выход</a>
                <a href="./logout.php">
                    <span class="material-icons unselectable">
                    logout
                    </span></a>
            </div>
        </div>
    </header>
    <div class="slideshow-container">
        <div class="mySlides fade">
            <section id="slide-0" class="content">
                <div class="container">
                    <div class="top-block">
                    <?php if ($user->status === 'hHSDFAoKM98hDNCJIA7cC9C4J3NGN45G97bbytSDF9P'){
                        echo '<a href="./admin/admin.php">Админ панель</a>';
                    }else{
                        echo '<p class="info" style="z-index: 100; font-size: 20px; color: #FCF570" data-tooltip="Приветствуем Вас в своем личном кабинете. Здесь Вы можете загружать неограниченное количество объявлений, одако учтите, что на главной странице помещается всего 32 объявления от разных пользователей. Объявления, ушедшие за рамки, Вы можете найти по категориям, или в поиске сайта. Помните, что, чем виднее Ваше объявление, тем больше шансов на успех сделки с другим пользователем. Бесплатные объявления &#171;живут&#187; 7 дней, платные 2 недели. Платные объявления размещаются по системе аукциона и гарантированно помещаются на главной странице, пока не закончится время.">🛈</p>';
                        echo '<p class="info2" style="z-index: 99; font-size: 18px; color: #cd1616" data-tooltip="Некоторые функции сайта, могут работать некорректно. Мы знаем и работаем над этим. Наказать админа или оставить обратную связь о Вашем пользовательском опыте можно по e-mail: gmdhubsupp@yandex.ru.">
                        <span class="material-icons unselectable">
                        warning
                        </span></p>';
                    };?>
                    </div>
                    <div class="top">
                        <div class="btn-container">
                            <div id="btn-2" class="lk next" onclick="plusSlides(2)">
                                <span style="font-size: 3em;" class="material-icons unselectable">
                                person_pin
                                </span>
                            </div>
                            <a style="color: #000; font-weight:bold; font-size: 1.1em;" class="limk-text" href="#">Личные данные</a>
                        </div>
                        <div class="btn-container">
                            <div id="btn-1" class="partner next" onclick="plusSlides(1)">
                                <span style="font-size: 3em;" class="material-icons unselectable">
                                volunteer_activism
                                </span>
                            </div>
                            <a style="color: #000; font-weight:bold; font-size: 1.1em;" class="limk-text" href="#">Партнерская программа</a>
                        </div>
                    </div>

                    <div class="middle">
                        <div class="btn-container">
                            <div id="btn-3" class="ads next" onclick="plusSlides(3)">
                                <span style="font-size: 3em;" class="material-icons unselectable">
                                dashboard
                                </span>
                            </div>
                            <a style="color: #000; font-weight:bold; font-size: 1.1em;" class="limk-text" href="#">Мои объявления</a>
                        </div>
                        <div class="line"></div>
                        <div class="line2"></div>
                        <div class="line3"></div>
                        <div class="btn-container">
                            <div id="btn-0" class="center-circle">
                                <img src="uploads/avatars/<?php echo $user->avatar; ?>" class="avatar">
                            </div>
                            <?php echo $_SESSION['logged_user']->login;?>
                            <form action="./gmdprofile.php" method="post" enctype="multipart/form-data">
                            <label for="input-file" class="custom-file-upload">
                                <input id="input-file" class="custom-file-input" type="file" name="avatar">
                                <button class="button-avatar" type="submit" name="set-avatar">Ok</button>
                            </label>
                                
                            </form>
                        </div>

                        <div class="btn-container">
                            <div id="btn-4" class="balance next" onclick="plusSlides(4)">
                                <span style="font-size: 3em;" class="material-icons unselectable">
                                account_balance_wallet
                                </span>
                            </div>
                            <a style="color: #000; font-weight:bold; font-size: 1.1em;" class="limk-text" href="#">Баланс</a>
                        </div>
                    </div>

                    <div class="bottom">
                        <div class="btn-container">
                            <div id="btn-5" class="stats-1 next" onclick="plusSlides(5)">
                                <span style="font-size: 3em;" class="material-icons unselectable">
                                query_stats
                                </span>
                            </div>
                            <a style="color: #000; font-weight:bold; font-size: 1.1em;" class="limk-text" href="#">Статистика показов</a>
                        </div>
                        <div class="btn-container">
                            <div id="btn-6" class="stats-2 next" onclick="plusSlides(6)">
                                <span style="font-size: 3em;" class="material-icons unselectable">
                                insert_chart_outlined
                                </span>
                            </div>
                            <a style="color: #000; font-weight:bold; font-size: 1.1em;" class="limk-text" href="#">Статистика профиля</a>
                        </div>

                        <div class="bottom-block">
                            <span class="material-icons unselectable">
                                share
                                </span> 
                                <p class="share-text">Поделиться в социальных сетях</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="mySlides fade">
        <section id="slide-1" class="lk-page" style="display:none"> 
            <div class="prev" onclick="plusSlides(7)">
            <span class="material-icons unselectable">
                keyboard_backspace
                </span></div>
            <div class="container">
                <h1>Партнерская программа</h1>
                <p>Моя партнерская ссылка:</p>
                <div class="partner-table">
                    <p>Переходов по ссылке:</p>
                    <p>Новые зарегистрированные пользователи:</p>
                    <p>Активные пользователи:</p>
                    <p>Общий доход:</p>
                </div>
            </div>
        </section>
    </div>

    <div class="mySlides fade">
        <section id="slide-2" class="ads-page" style="display:none"> 
            <div class="prev" onclick="plusSlides(7)"><span class="material-icons unselectable">
                keyboard_backspace
                </span></div>
            <div class="container">
                <h1>Личные данные</h1>
                <div class="personal-container">
                    <div class="personal-left-block">
                        <ul class="personal-list">
                            <li class="personal-item">Фамилия <input class="personal-data" type="text" name="" placeholder="Введите Вашу фамилию"></li>
                            <li class="personal-item">Имя <input class="personal-data" type="text" name="" placeholder="Введите Ваше имя"></li>
                            <li class="personal-item">Номер телефона <input class="personal-data" type="tel" name="" pattern="^\d{3}-\d{3}-\d{4}$" required="required" placeholder="+7"></li>
                            <li class="personal-item">Дата рождения <input class="personal-data" type="text" name="" placeholder="дд.мм.гггг"></li>
                            <li class="personal-item">Страна <input class="personal-data" type="text" name="" placeholder="Выбрать страну"></li>
                            <li class="personal-item">Город <input class="personal-data" type="text" name="" placeholder="Выбрать город"></li>
                            <li class="personal-item">Физический адрес <input class="personal-data" type="text" name="" placeholder="Адрес"></li>
                            <li class="personal-item">Статус верификации <input class="personal-data" type="text" name=""></li>
                        </ul>
                        <button class="save-data">Сохранить</button>
                    </div>
                    <div class="personal-right-block">
                        <div></div>
                        <button class="send-verification">Верифицировать профиль</button>
                    </div>
                </div>
            </div>
        </section>
    </div>
<!-- Загрузка объявлний -->
    <div class="mySlides fade">
        <section id="slide-3" class="center-page" style="display:none"> 
            <div class="prev" onclick="plusSlides(7)"><span class="material-icons unselectable">
                keyboard_backspace
                </span></div>
            <div class="container">
                <h1>Мои объявления</h1>
                <div class="centered">
                    <div class="scroll">
                        <ul>
                        <?php foreach($sql as $elements):
                            ?>
                            <li class="li-ads-section">
                                <div class="ads-section">
                                    <img class="photos unselectable" src='uploads/photos/<?=$elements['ads']?>' width='100%' height='100%'/>
                                        <p class="text-title"><?php echo $elements['title'];?></p>
                                        <p class="text-descript"><?php echo $elements['descript'];?></p>
                                        <p class="text-region"><?php echo $elements['regions'];?></p>
                                        <p class="text-price"><?php echo $elements['price'];?></p>
                                        <p class="text-category"><?php echo $elements['category'];?></p>
                                        <?php if($elements['confirm'] === '1'){
                                            echo "Опубликовано";
                                            } else{
                                                echo "На проверке";
                                            }
                                        ?>
                                </div>
                                <div class="second-child">
                                    <button id="change-1" class="change">
                                        <span class="svg material-icons unselectable">
                                            create
                                            </span>
                                        </button>
                                    <div class="add-time">
                                        <span class="svg material-icons unselectable">
                                            alarm
                                            </span>
                                    </div>
                                </div>
                                <div id="change-modal-1" class="change-modal-1">
                                    <div class="change-modal-content-1">
                                    <span class="close-change-1 unselectable">&times;</span>
                                    <h2 class="ch2">Что хотите изменить?</h2>
                                        <form class="change-modal-form" action="./gmdprofile.php" enctype="multipart/form-data" method="post">
                                            <h3>Название<h3>
                                                <input type="text" name="change-title" class="items-settings-ads" placeholder="Изменить название" id="title">
                                            <h3>Описание<h3>
                                                <input type="text" name="change-descript" class="items-settings-ads" placeholder="Изменить описание" id="description">
                                            <h3>Цена<h3>
                                                <input type="text" name="change-price" class="items-settings-ads" placeholder="Изменить цену" id="price">
                                            <h3>Категория<h3>
                                                <input type="text" name="change-category" class="items-settings-ads" placeholder="Изменить категорию" id="category">
                                            <h3>Регион<h3>
                                                <input type="text" name="change-region" class="items-settings-ads" placeholder="Изменить регион" id="region">
                                            <button type="submit-change" class="save-change-modal-content-1">Сохранить изменения
                                                <span class="material-icons unselectable">
                                                ios_share
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="time-section">
                                </div>
                            </li>
                            <?php
                        endforeach;
                            ?>
                        </ul>
                        <ul>
                        <?php foreach($sql2 as $elements):
                            ?>
                            <li class="li-ads-section">
                                <div class="ads-section">
                                    <img class="photos unselectable" src='uploads/photos/<?=$elements['image']?>' width='100%' height='100%'/>
                                        <p class="text-title"><?php echo $elements['title'];?></p>
                                        <p class="text-descript"><?php echo $elements['description'];?></p>
                                        <p class="text-region"><?php echo $elements['region'];?></p>
                                        <p class="text-price"><?php echo $elements['price'];?></p>
                                        <p class="text-category"><?php echo $elements['category'];?></p>
                                        <?php if($elements['confirm'] === '1'){
                                            echo '<p class="text-on-auction">На аукционе</p>';
                                            } else{
                                                echo "Отправлено на аукцион. На проверке";
                                            }
                                        ?>
                                </div>
                                <div class="second-child">
                                    <button id="change-1" class="change">
                                        <span class="svg material-icons unselectable">
                                            create
                                            </span>
                                        </button>
                                    <div class="add-time">
                                        <span class="svg material-icons unselectable">
                                            alarm
                                            </span>
                                    </div>
                                </div>
                                <div id="change-modal-1" class="change-modal-1">
                                    <div class="change-modal-content-1">
                                    <span class="close-change-1 unselectable">&times;</span>
                                    <h2 class="ch2">Что хотите изменить?</h2>
                                        <form class="change-modal-form" action="./gmdprofile.php" enctype="multipart/form-data" method="post">
                                            <h3>Название<h3>
                                                <input type="text" name="change-title" class="items-settings-ads" placeholder="Изменить название" id="title">
                                            <h3>Описание<h3>
                                                <input type="text" name="change-descript" class="items-settings-ads" placeholder="Изменить описание" id="description">
                                            <h3>Цена<h3>
                                                <input type="text" name="change-price" class="items-settings-ads" placeholder="Изменить цену" id="price">
                                            <h3>Категория<h3>
                                                <input type="text" name="change-category" class="items-settings-ads" placeholder="Изменить категорию" id="category">
                                            <h3>Регион<h3>
                                                <input type="text" name="change-region" class="items-settings-ads" placeholder="Изменить регион" id="region">
                                            <button type="submit-change" class="save-change-modal-content-1">Сохранить изменения
                                                <span class="material-icons unselectable">
                                                ios_share
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="time-section">
                                </div>
                            </li>
                            <?php
                        endforeach;
                            ?>
                        </ul>
                    </div>
                    <div class="container-other">
                        <button id="myBtn-1" class="auction">
                            <span style="font-size: 3em; color: white;" class="material-icons unselectable">
                                add_circle
                                </span>
                        </button>
                        <p class="text-other"> Аукцион объявлений</p>
                        <div id="myModal-1" class="modal-1">
                                <div class="modal-content-1">
                                <span class="close-1 unselectable">&times;</span>
                                    <div class="h2-popup">
                                    <h2>Выставить объявление на аукцион</h2><p class="info" data-tooltip="1. Чтобы выставить объявление на аукцион, Ваше объявление должно быть опубликовано и проверено. &#173;2. Цена, предложенная вами, должна быть выше предложенной в данный момент. &#173;3. Если выполнены п.1 и п.2, Ваше объявление попадает в топ показов на главной странице сайта на определенное время.">&#128712;</p>
                                    </div>
                                        <form id="modal-form" class="modal-form" action="./gmdprofile.php" method="post" enctype="multipart/form-data">
                                            <div class="grid-auc">
                                                <div class="col-auc-one">
                                                    <!-- <div class="element1-div-2">
                                                        <input id="radio-1" type="radio" name="choosen" value="1" class="element1" checked></input>
                                                        <label for="radio-1"></label>
                                                    </div>
                                                    <div class="element1-div-2">
                                                        <input id="radio-2" type="radio" name="choosen" value="2" class="element1"></input>
                                                        <label for="radio-2"></label>
                                                    </div> -->
                                                </div>
                                                <div class="col-auc-two">
                                                    <div class="top-group-elements">
                                                    <?php
                                                        echo '<div class="element1-div">
                                                            <input id="radio-3" type="radio" name="choosen" value="1" class="element1"></input>
                                                            <label for="radio-3"></label>
                                                        </div>
                                                        <div class="element1-div">
                                                            <input id="radio-4" type="radio" name="choosen" value="2" class="element1"></input>
                                                            <label for="radio-4"></label>
                                                        </div>
                                                        <div class="element1-div">
                                                            <input id="radio-5" type="radio" name="choosen" value="3" class="element1"></input>
                                                            <label for="radio-5"></label>
                                                        </div>
                                                        <div class="element1-div">
                                                            <input id="radio-6" type="radio" name="choosen" value="4" class="element1"></input>
                                                            <label for="radio-6"></label>
                                                        </div>';
                                                        ?>
                                                    </div>
                                                    <div class="bot-group-elements">
                                                    <?php
                                                        echo '<div class="element1-div">
                                                            <input id="radio-7" type="radio" name="choosen" value="5" class="element1"></input>
                                                            <label for="radio-7"></label>
                                                        </div>
                                                        <div class="element1-div">
                                                            <input id="radio-8" type="radio" name="choosen" value="6" class="element1"></input>
                                                            <label for="radio-8"></label>
                                                        </div>
                                                        <div class="element1-div">
                                                            <input id="radio-9" type="radio" name="choosen" value="7" class="element1"></input>
                                                            <label for="radio-9"></label>
                                                        </div>
                                                        <div class="element1-div">
                                                            <input id="radio-10" type="radio" name="choosen" value="8" class="element1"></input>
                                                            <label for="radio-10"></label>
                                                        </div>';
                                                        ?>
                                                    </div>
                                                </div>'
                                                <div class="col-auc-three">
                                                <!-- <div class="element1-div-2">
                                                    <input id="radio-11" type="radio" name="choosen" value="11" class="element1"></input>
                                                    <label for="radio-11"></label>
                                                    </div>
                                                    <div class="element1-div-2">
                                                    <input id="radio-12" type="radio" name="choosen" value="12" class="element1"></input>
                                                    <label for="radio-12"></label>
                                                    </div> -->
                                                </div>
                                            </div>
                                            <div id="selected-ads" class="selected-ads">
                                                <div class="modal-win-choose-ads">
                                                    <span class="close-choose">&times;</span>
                                                    <p>Выберите объявление</p>
                                                        <?php
                                                        for ($i = 0; $i < count($sql); $i++):?>
                                                        <?php $elements = $sql[$i];
                                                        if($elements['confirm'] === '1'){?>
                                                            <div id="ad-<?=$i?>" class="choosen-content">
                                                                <div class="ads-section-choose-auc">
                                                                    <input type="image" class="photos unselectable" name="auc-image" src="uploads/photos/<?=$elements['ads']?>" value="<?=$elements['ads']?>" width="100%" height="100%" readonly/>
                                                                </div>
                                                                <div class="ads-text-auc">
                                                                    <p>№ <input type="text" class="text-auc" name="delete" value="<?=$elements['id']?>" readonly/></p>
                                                                    <input type="text" class="text-auc" name="auc-title" value="<?=$elements['title']?>" readonly/>
                                                                    <input type="text" class="text-auc" name="auc-descript" value="<?=$elements['descript']?>" readonly/>
                                                                    <input type="text" class="text-auc" name="auc-regions" value="<?=$elements['regions']?>" readonly/>
                                                                    <p class="text-auc"><?php echo $elements['price'];?></p>
                                                                    <input type="text" class="text-auc" name="auc-category" value="<?=$elements['category']?>" readonly/>
                                                                </div>
                                                                <div class="auc-radio">
                                                                    <input id="radio-13-<?=$i?>" type="radio" name="onetwo" value="<?=$i?>" class="element1"></input>
                                                                    <label for="radio-13-<?=$i?>"><span class="material-icons unselectable" style="font-size: 96px; color: white;" width="100%" height="100%">
                                                                    check_circle
                                                                    </span></label>
                                                                </div>
                                                            </div>
                                                            <?php }
                                                        endfor;?>
                                                        <div name="choose-ads" class="close-choose div-close-choose">Ok</div>
                                                </div>
                                            </div>
                                            <h3>Выберите позицию<h3>
                                            <div class="grid-btns">
                                                <div class="btns">
                                                    <a id="choose-ads" class="choose-ads">Выбрать</a>
                                                    <p class="choose-ads-text">Выберите объявление</p>
                                                </div>
                                            </div>
                                            <div>
                                                <input type="number" name="price-auc" class="items-settings-ads" placeholder="Указать цену" min="100" maxlength="9" value="100" oninput="maxLengthCheckk(this)">
                                            </div>
                                            <input type="datetime-local" name="time" readonly>
                                            <button type="submit" name="submit-auc" class="save-modal-content-1">Опубликовать>
                                            <span class="material-icons unselectable">
                                            ios_share
                                            </span>
                                            </button>
                                            </div>
                                            </div>
                                            
                                        </form>
                        <button id="myBtn" class="add-ads">
                            <span style="font-size: 3em; color: white;" class="material-icons unselectable">
                                add_circle
                                </span>
                        </button>
                        <p class="text-other"> Добавить объявление</p>
                            <div id="myModal" class="modal">
                                <div class="modal-content">
                                    <span class="close unselectable">&times;</span>
                                    <div class="h2-popup">
                                    <h2>Добавить новое объявление</h2><p class="info" data-tooltip="Добавьте фото, выберите параметры. После того как, Ваше объявление пройдет проверку, оно будет опубликовано. Доступные форматы: png, jpg, jpeg, gif.">&#128712;</p>
                                    </div>
                                        <form action="./gmdprofile.php" enctype="multipart/form-data" method="post" id="form">
                                            <div class="content-ads">
                                                <div id="file" class="content-ads-one upload">
                                                    <input type="file" id="uploadbtn" multiple name="ads" class="modal-content-image" value="Добавить фото" style="display:none;" onchange="loadFile(event)">
                                                    <label for="uploadbtn" class="modal-content-image">
                                                        <span style="font-size: 3em; color: white;" class="material-icons unselectable">
                                                        add_circle
                                                        </span>
                                                    </label>
                                                    <p>Добавить объявление</p>
                                                </div>
                                                <div class="content-ads-two">
                                                    <div id="preview" class="preview">
                                                    <div id="data" class="data" width="100%" height="100%"></div>
                                                    <img class="preimage" id="preimage" style="display: none"/>
                                                    <div id="myVideo" class="myVideo" width="100%" height="100%" style="display: none"></div>
                                                    </div>
                                                        <h4 id="output1" class="title-output"><span class="unselectable" id="title-output"></span></h4>
                                                        <h4 id="output2" class="descript-output"><span class="unselectable" id="descript-output"></span></h4>
                                                        <h4 id="output3" class="region-output"><span class="unselectable" id="region-output"></span></h4>
                                                        <h4 id="output4" class="price-output"><span class="unselectable" id="price-output"></span></h4>
                                                        <h4 id="output5" class="category-output"><span class="unselectable" id="category-output"></span></h4>
                                                        <p>Предпросмотр</p>
                                                </div>
                                                <div class="content-ads-three">
                                                </div>
                                            </div>
                                            <div class="settings-ads">
                                                <input type="text" name="title" class="items-settings-ads" placeholder="Добавить название" maxlength="14" id="title">
                                                <?php
                                                    $goods = R::getAssoc('SELECT id, goods_name FROM goods ORDER BY id DESC LIMIT 8');
                                                    $services = R::getAssoc('SELECT id, services_name FROM services ORDER BY id DESC LIMIT 2');
                                                    $job = R::getAssoc('SELECT id, job_name FROM job ORDER BY id DESC LIMIT 2');
                                                    $art = R::getAssoc('SELECT id, art_name FROM art ORDER BY id DESC LIMIT 8');
                                                    $profiles = R::getAssoc('SELECT id, profiles_name FROM profiles ORDER BY id DESC LIMIT 8');
                                                    ?>
                                                    <select class="items-settings-ads" name="category" required="required" id="category">
                                                        <?php
                                                    echo '<option selected="selected">Категория</option>';
                                                    echo '<optgroup label="Товары">';
                                                    foreach( $goods as $good ) {
                                                        echo "<option value='$good'>$good</option>";
                                                    }
                                                    echo '</optgroup>';
                                                    echo '<optgroup label="Услуги">';
                                                    foreach( $services as $service ) {
                                                        echo "<option value='$service'>$service</option>";
                                                    }
                                                    echo '</optgroup>';
                                                    echo '<optgroup label="Работа">';
                                                    foreach( $job as $jo ) {
                                                        echo "<option value='$jo'>$jo</option>";
                                                    }
                                                    echo '</optgroup>';
                                                    echo '<optgroup label="Творчество">';
                                                    foreach( $art as $arts ) {
                                                        echo "<option value='$arts'>$arts</option>";
                                                    }
                                                    echo '</optgroup>';
                                                    echo '<optgroup label="Профили">';
                                                    foreach( $profiles as $profile ) {
                                                        echo "<option value='$profile'>$profile</option>";
                                                    }
                                                    echo '</optgroup>';
                                                    echo '</select>';
                                                ?>
                                                <input type="text" name="descript" class="items-settings-ads" placeholder="Добавить описание" maxlength="20" id="description">
                                                <?php 
                                                $regions = R::getAssoc('SELECT id, city FROM regions');
                                                ?>
                                                <select class="items-settings-ads" name="regions" required="required" id="regions">
                                                <?php
                                                echo '<option selected="selected">Выбрать город</option>';

                                                foreach( $regions as $region ) {
                                                    echo "<option value='$region'>$region</option>";
                                                }

                                                echo '</select>';
                                                ?>
                                                    <span class="textbox">
                                                    <input type="number" name="price" class="items-settings-ads" placeholder="Указать цену" id="price" min="1" maxlength="9" oninput="maxLengthCheck(this)">
                                                    &#8381; </span>
                                                <!-- <button data-jscolor="{closeButton:true, closeText:'Закрыть', backgroundColor:'#333', buttonColor:'#FFF'}">Цвет фона</button> -->
                                            </div>
                                            <input type="datetime-local" name="time" readonly>
                                            <button type="submit" id="submit-ads" name="submit-ads" class="save-modal-content">Опубликовать
                                                <span class="material-icons unselectable">
                                                ios_share
                                                </span>
                                            </button>
                                        </form>
                                        </div>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="mySlides fade">
        <section id="slide-4" class="balance-page" style="display:none"> 
            <div class="prev" onclick="plusSlides(7)"><span class="material-icons unselectable">
                keyboard_backspace
                </span>
            </div>
            <div class="container">
                <h1>Мой баланс</h1>
                <div class="balance-container">
                    <div class="left-block">
                        <div class="payments-text">
                            Пополнить баланс
                        </div>
                        <div class="payments">
                            100 RUB 200 RUB 500 RUB 1000 RUB
                        </div>
                    </div>
                    <div class="balance-circle">
                        <p style="color: #000; font-size: 30px;">
                        <?php echo '<span class="material-icons unselectable">account_balance_wallet</span> ', $user->balance, ' RUB'?></p>
                    </div>
                    <div class="right-block">
                        Вывести средства
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="mySlides fade">
        <section id="slide-5" class="stats-1-page" style="display:none"> 
            <div class="prev" onclick="plusSlides(7)"><span class="material-icons unselectable">
                keyboard_backspace
                </span></div>
            <div class="container">
                <h1>Статистика показов</h1>
                <div id="regions_div" style="width: 900px; height: 500px;"></div>
                
            </div>
        </section>
    </div>

    <div class="mySlides fade">
        <section id="slide-6" class="stats-2-page" style="display:none"> 
            <div class="prev" onclick="plusSlides(7)"><span class="material-icons unselectable">
                keyboard_backspace
                </span></div>
            <div class="container">
                <h1>Статистика профиля</h1>
                <figure>
                    <div class="figure-content">
                        <svg width="100%" height="100%" viewBox="0 0 42 42" class="donut" aria-labelledby="beers-title beers-desc" role="img">
                            <title id="beers-title">Beers in My Cellar</title>
                            <desc id="beers-desc">Donut chart showing 10 total beers. Two beers are Imperial India Pale Ales, four beers are Belgian Quadrupels, and three are Russian Imperial Stouts. The last remaining beer is unlabeled.</desc>
                            <circle class="donut-hole" cx="21" cy="21" r="15.91549430918954" fill="#fff" role="presentation"></circle>
                            <circle class="donut-ring" cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#d2d3d4" stroke-width="3" role="presentation"></circle>
                            <circle class="donut-segment" cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#F686B6" stroke-width="3" stroke-dasharray="40 60" stroke-dashoffset="25" aria-labelledby="donut-segment-1-title donut-segment-1-desc">
                              <title id="donut-segment-1-title">Размещенные объявления</title>
                              <desc id="donut-segment-1-desc">Pink chart segment spanning 40% of the whole, which is 4 Belgian Quadrupels out of 10 total.</desc>
                            </circle>
                            <circle class="donut-segment" cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#FCF570" stroke-width="3" stroke-dasharray="20 80" stroke-dashoffset="85">
                              <title id="donut-segment-2-title">Партнерская программа</title>
                              <desc id="donut-segment-2-desc">Green chart segment spanning 20% of the whole, which is 2 Imperial India Pale Ales out of 10 total.</desc>
                            </circle>
                            <circle class="donut-segment" cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#4BA6F9" stroke-width="3" stroke-dasharray="30 70" stroke-dashoffset="65">
                              <title id="donut-segment-3-title">Активность</title>
                              <desc id="donut-segment-3-desc">Blue chart segment spanning 3% of the whole, which is 3 Russian Imperial Stouts out of 10 total.</desc>
                            </circle>
                            <!-- unused 10% -->
                            <g class="chart-text">
                              <text x="50%" y="50%" class="chart-number">
                                %
                              </text>
                              <text x="50%" y="50%" class="chart-label">
                                Показатели
                              </text>
                            </g>
                          </svg>
                    </div>
                    <figcaption class="figure-key">
                        <p class="sr-only">Donut chart showing 10 total beers. Two beers are Imperial India Pale Ales, four beers are Belgian Quadrupels, and three are Russian Imperial Stouts. The last remaining beer is unlabeled.</p>
                        <ul class="figure-key-list" aria-hidden="true" role="presentation">
                            <li>
                                <span class="shape-circle shape-fuschia unselectable"></span> Размещенные объявления
                            </li>
                            <li>
                                <span class="shape-circle shape-lemon-lime unselectable"></span> Активность
                            </li>
                            <li>
                                <span class="shape-circle shape-blue unselectable"></span> Партнерская программа
                            </li>
                        </ul>
                    </figcaption>
                </figure>
            </div>
        </section>
    </div>
</div>
    <footer>
        <div class="footer">
        
        </div>
    </footer>
    <?php 
    echo "<pre>",print_r($obj2), "</pre>";
    // echo "<pre>",print_r($k), "</pre>";
    ?>
</body>
</html>
<?php endif; ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="./gmdprofile.js"></script>
<script src="./pluggins/jscolor.js"></script>
<!-- slides -->
<script>
$('#btn-2').on('click', function() {
    $('#slide-2').show('slow');
});
$('#btn-1').on('click', function() {
    $('#slide-1').show('slow');
});
$('#btn-3').on('click', function() {
    $('#slide-3').show('slow');
});
$('#btn-4').on('click', function() {
    $('#slide-4').show('slow');
});
$('#btn-5').on('click', function() {
    $('#slide-5').show('slow');
});
$('#btn-6').on('click', function() {
    $('#slide-6').show('slow');
});
</script>
<!-- preview -->
<script type="text/javascript">
    function LoaadFile (event){
        var output = document.getElementById('preimage');
        output.src = URL.createObjectURL(event.target.files[0]);
    };
</script>
<script>
    if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
    }
</script>
<script>
    var loadFile = function(event) {
    var preimage = document.getElementById('preimage');
    preimage.src = URL.createObjectURL(event.target.files[0]);
    preimage.onload = function() {
        URL.revokeObjectURL(preimage.src)
    }
    for (let i = 0; i < 10; i++) {
    document.getElementById("preimage").style.display = "block";
    document.getElementById("myVideo").style.display = "none";
}
};
</script>
<script>
    $(document).ready(function(){
        $('#title').on('keyup',function(){
            var title = $(this).val();
            $('#title-output').text(title);
        });
        });
        $(document).ready(function(){
        $('#descript').on('keyup',function(){
            var description = $(this).val();
            $('#description-output').text(description);
        });
        });
        $(document).ready(function(){
        $('#regions').on('keyup',function(){
            var regions = $(this).val();
            $('#region-output').text(regions);
        });
        });
        $(document).ready(function(){
        $('#price').on('keyup',function(){
            var price = $(this).val();
            $('#price-output').text(price);
        });
        });
        $(document).ready(function(){
        $('#category').on('keyup',function(){
            var category = $(this).val();
            $('#category-output').text(category);
        });
        });
</script>
<!-- timer -->
<!-- input-price -->
<script>
    function maxLengthCheckk(object)
    {
        if (object.value.length > object.maxLength)
        object.value = object.value.slice(0, object.maxLength)
    }
    $(function(){
    $('input[type="number"]').on('change keyup input click mouseup', function() {
        this.value = this.value.replace(/^0|[^\d]/g, '');
            })
    });
</script>
<script>
    function maxLengthCheck(object)
    {
        if (object.value.length > object.maxLength)
        object.value = object.value.slice(0, object.maxLength)
    }

    $(function(){
    $('input[type="number"]').on('change keyup input click mouseup', function() {
        this.value = this.value.replace(/^0|[^\d]/g, '');
            })
    });

</script>
<!-- modalWins -->
<script>
    var change_modal_1 = document.getElementById("change-modal-1");
    var change_btn_1 = document.getElementById("change-1");
    var change_span_1 = document.getElementsByClassName("close-change-1")[0];

    change_btn_1.onclick = function() {
        change_modal_1.style.display = "block";
    }

    change_span_1.onclick = function() {
        change_modal_1.style.display = "none";
    }
</script>
<script>
var modal = document.getElementById("myModal");
var btn = document.getElementById("myBtn");
var span = document.getElementsByClassName("close")[0];

btn.onclick = function() {
    modal.style.display = "block";
}

span.onclick = function() {
    modal.style.display = "none";
}
</script>
<script>
    var modal_1 = document.getElementById("myModal-1");
    var btn_1 = document.getElementById("myBtn-1");
    var span_1 = document.getElementsByClassName("close-1")[0];

    btn_1.onclick = function() {
        modal_1.style.display = "block";
    }

    span_1.onclick = function() {
        modal_1.style.display = "none";
    }
</script>
<script>
    $(document).ready(function(){
    $(".close-choose").click(function(){
        $(".selected-ads").hide();
    });
    $(".choose-ads").click(function(){
        $(".selected-ads").show();
    });
});
</script>
<!-- google -->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {
        'packages': ['geochart'],
        // Note: you will need to get a mapsApiKey for your project.
        // See: https://developers.google.com/chart/interactive/docs/basic_load_libs#load-settings
        'mapsApiKey': 'AIzaSyD-9tSrke72PouQMnMX-a7eZSW0jkFMBWY'
    });
    google.charts.setOnLoadCallback(drawRegionsMap);

    function drawRegionsMap() {
        var data = google.visualization.arrayToDataTable([
            ['Country', 'Popularity'],
            ['Germany', 200],
            ['United States', 300],
            ['Brazil', 400],
            ['Canada', 500],
            ['France', 600],
            ['RU', 700]
        ]);

        var options = {};

        var chart = new google.visualization.GeoChart(document.getElementById('regions_div'));

        chart.draw(data, options);
    }
</script>
