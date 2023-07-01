<?php

    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $vin = $_POST['vin'];
    $message = $_POST['message'];


    $name = htmlspecialchars($name);
    $phone = htmlspecialchars($phone);
    $vin = htmlspecialchars($vin);
    $message = htmlspecialchars($message);


    $name = urldecode($name);
    $phone = urldecode($phone);
    $vin = urldecode($vin);
    $message = urldecode($message);

    $name = trim($name);
    $phone = trim($phone);
    $vin = trim($vin);
    $message = trim($message);


    if(mail("autopartsin.ua@gmail.com",
            "Новое письмо с сайта по вопроса вин кода",
            "Имя:".$name."\n",
            "Номер телефона:".$phone."\n",
            "Вин код машины:".$vin."\n",
            "Сообщение:".$message."\n"
    )){
        echo (' Письмо успешно отправлено !');
    }

    else{
        echo('Проверьте форму на ошибки...');
    }

?>