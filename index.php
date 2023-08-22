<?php
    include "vk_api.php";
    const VK_KEY = "...";
    const ACCESS_KEY = "...";
    const VERSION = "5.81";
    
    const RASSILKA_KEY = "...";
    const RASSILKA_ID = "...";
    
    const TELEGRAM_KEY = "...";
    const TELEGRAM_ID = "@...";
    
    $arrayOfIds = array(
        "47263205" // Неграш Андрей
    );

    $vk = new vk_api(VK_KEY, VERSION);
    $data = json_decode(file_get_contents('php://input'));
    if ($data->type == 'confirmation')
        exit(ACCESS_KEY);
    $vk->sendOK();
    
    if (isset($data->type) and $data->type == 'message_new') {
        $idUser = $data->object->from_id;
        $message = $data->object->text;
        if (isset($data->object->peer_id))
            $peer_id = $data->object->peer_id;
        else
            $peer_id = $idUser;
        
        if (in_array($idUser, $arrayOfIds)) {
            
            if (strpos($message, '/post') === 0) {
                $message = trim(explode('/post', $message)[1]);
                // здесь отправка месседжа в вк рассылку
                $urlVk = "https://broadcast.vkforms.ru/api/v2/broadcast?token=".RASSILKA_KEY;
                $fieldsVk = [
                    'message' => [
                        'message' => "".$message // здесь в будущем нужно вставлять фотку или документы
                    ],
                    'list_ids' => RASSILKA_ID,
                    'run_now' => 1 // если ставить 0, то нужно добавить поле run_at с временем для отложенной отправки рассылки
                ];
                
                $fields_string = http_build_query($fieldsVk);
                $ch = curl_init();
                
                curl_setopt($ch, CURLOPT_URL, $urlVk);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = json_decode(curl_exec($ch));
                if ((int)$result->response->status == 1)
                    $statusVk = "Успешно";
                else
                    $statusVk = "Ошибка";
                $vk->sendMessage($peer_id, "Результат рассылки в вк: ".$statusVk);
                
                // здесь отправка месседжа в тг-канал
                $urlTelegram = "https://api.telegram.org/bot".TELEGRAM_KEY."/sendMessage";
                $fieldsTg = [
                    'chat_id' => TELEGRAM_ID,
                    'text' => "".$message
                ];
                $fields_string_tg = http_build_query($fieldsTg);
                $ch = curl_init();
                
                curl_setopt($ch, CURLOPT_URL, $urlTelegram);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string_tg);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                $result = json_decode($result)->ok;
                
                $vk->sendMessage($peer_id, "Результат рассылки в тг: ".((bool)$result ? "Успешно" : "Ошибка"));
            }
        } else 
            if (strpos(mb_strtolower($message), 'access') !== false)
                $vk->sendMessage($peer_id, "Твой id: ".$id);
    }
?>
