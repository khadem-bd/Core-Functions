<?php

//Import PHPMailer classes into the global namespace
use PHPMailer\vendor\PHPMailer\PHPMailer;
use PHPMailer\vendor\PHPMailer\SMTP;
use PHPMailer\vendor\PHPMailer\Exception;



//DATA INSERT FUNCTIONALITY WITH SUCCESS MESSAGE RETURN

function insert($tableName, $colNames, $colValues)
{
    include "connect.php";

    $sql = "INSERT INTO " . $tableName . " (";

    for ($i = 0; $i < count($colNames); $i++) {
        if ($i + 1 == count($colNames)) {
            $sql = $sql . $colNames[$i] . ")";
        } else {
            $sql = $sql . $colNames[$i] . ", ";
        }
    }

    $sql = $sql . " VALUES (";

    for ($i = 0; $i < count($colValues); $i++) {
        if ($i + 1 == count($colValues)) {
            $sql = $sql . "?)";
        } else {
            $sql = $sql . "?,";
        }
    }

    $pre_stmt = $conn->prepare($sql);

    DynamicBindVariables($pre_stmt, $colValues);

    if ($pre_stmt->execute()) {
        $pre_stmt->close();

        $conn->close();

        return true;
    } else {
        return false;
    }
}

//DATA INSERT FUNCTIONALITY WITH INSERTED ROW ID RETURN

function insert_rowid($tableName, $colNames, $colValues)
{
    include "connect.php";

    $sql = "INSERT INTO " . $tableName . " (";

    for ($i = 0; $i < count($colNames); $i++) {
        if ($i + 1 == count($colNames)) {
            $sql = $sql . $colNames[$i] . ")";
        } else {
            $sql = $sql . $colNames[$i] . ", ";
        }
    }

    $sql = $sql . " VALUES (";

    for ($i = 0; $i < count($colValues); $i++) {
        if ($i + 1 == count($colValues)) {
            $sql = $sql . "?)";
        } else {
            $sql = $sql . "?,";
        }
    }

    $pre_stmt = $conn->prepare($sql);

    DynamicBindVariables($pre_stmt, $colValues);

    if ($pre_stmt->execute()) {
        $last_id = $conn->insert_id;

        $pre_stmt->close();

        $conn->close();

        return $last_id;
    }
}

//DATA EDIT FUNCTIONALITY WITH SUCCESS MESSAGE RETURN

function update($tableName, $colNames, $colValues, $searchKeyName)
{
    include "connect.php";

    $sql = "UPDATE " . $tableName . " SET ";

    for ($i = 0; $i < count($colNames); $i++) {
        if ($i + 1 == count($colNames)) {
            $sql = $sql . $colNames[$i] . "=?";
        } else {
            $sql = $sql . $colNames[$i] . "=?, ";
        }
    }

    $sql = $sql . " WHERE " . $searchKeyName . "=?";

    $pre_stmt = $conn->prepare($sql);

    DynamicBindVariables($pre_stmt, $colValues);

    if ($pre_stmt->execute()) {
        $pre_stmt->close();

        $conn->close();

        return true;
    } else {
        return false;
    }
}

// DATA DELETE FUNCTIONALITY

function delete($tablename, $searchKeyColName, $searchKey, $deleteType, $fileColName, $folderPath) {
    if (checkDataExistance($tablename, [$searchKeyColName], [$searchKey])) {
        include "connect.php";

        if ($deleteType == "data") {
            $sql =
                "DELETE FROM " .
                $tablename .
                " WHERE " .
                $searchKeyColName .
                "=?";

            $pre_stmt = $conn->prepare($sql);

            if (is_int($searchKey)) {
                // Integer

                $pre_stmt->bind_param("i", $searchKey);
            } elseif (is_float($searchKey)) {
                // Double

                $pre_stmt->bind_param("d", $searchKey);
            } elseif (is_string($searchKey)) {
                // String

                $pre_stmt->bind_param("s", $searchKey);
            } else {
                // Blob and Unknown

                $pre_stmt->bind_param("b", $searchKey);
            }

            if ($pre_stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } elseif ($deleteType == "data_with_file") {
            $sql =
                "SELECT " .
                $fileColName .
                " FROM " .
                $tablename .
                " WHERE " .
                $searchKeyColName .
                "=?";

            $pre_stmt = $conn->prepare($sql);

            DynamicBindVariables($pre_stmt, [$searchKey]);

            $pre_stmt->execute();

            $result = $pre_stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                unlink($folderPath . $row[$fileColName]);
            }

            $sql =
                "DELETE FROM " .
                $tablename .
                " WHERE " .
                $searchKeyColName .
                "=?";

            $pre_stmt = $conn->prepare($sql);

            if (is_int($searchKey)) {
                // Integer

                $pre_stmt->bind_param("i", $searchKey);
            } elseif (is_float($searchKey)) {
                // Double

                $pre_stmt->bind_param("d", $searchKey);
            } elseif (is_string($searchKey)) {
                // String

                $pre_stmt->bind_param("s", $searchKey);
            } else {
                // Blob and Unknown

                $pre_stmt->bind_param("b", $searchKey);
            }

            if ($pre_stmt->execute()) {
                return true;
            } else {
                return false;
            }
        }
    }
}

// CHECK DATA EXISTANCE FUNCTIONALITY

function checkDataExistance($tablename, $colNames, $colValues){
    include "connect.php";

    $sql = "SELECT ";

    for ($i = 0; $i < count($colNames); $i++) {
        if ($i + 1 == count($colNames)) {
            $sql = $sql . $colNames[$i];
        } else {
            $sql = $sql . $colNames[$i] . ", ";
        }
    }

    $sql = $sql . " FROM " . $tablename . " WHERE ";

    for ($i = 0; $i < count($colNames); $i++) {
        if ($i + 1 == count($colNames)) {
            $sql = $sql . $colNames[$i] . "=?";
        } else {
            $sql = $sql . $colNames[$i] . "=? AND ";
        }
    }

    $pre_stmt = $conn->prepare($sql);

    DynamicBindVariables($pre_stmt, $colValues);

    if ($pre_stmt->execute()) {
        $result = $pre_stmt->get_result();

        if ($result->num_rows > 0) {
            return true;
        } else {
            return false;
        }

        $pre_stmt->close();

        $conn->close();
    }
}

//QUERY RETURN VALUE

function getValue($table, $returnValueColName, $SearchKeyName, $searchKey){
    include "connect.php";

    $sql =
        "SELECT " .
        $returnValueColName .
        " FROM " .
        $table .
        " WHERE " .
        $SearchKeyName .
        "=?";

    //return $sql;

    $pre_stmt = $conn->prepare($sql);

    if (is_int($searchKey)) {
        // Integer

        $pre_stmt->bind_param("i", $searchKey);
    } elseif (is_float($searchKey)) {
        // Double

        $pre_stmt->bind_param("d", $searchKey);
    } elseif (is_string($searchKey)) {
        // String

        $pre_stmt->bind_param("s", $searchKey);
    } else {
        // Blob and Unknown

        $pre_stmt->bind_param("b", $searchKey);
    }

    $pre_stmt->execute();

    $result = $pre_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        return $row[$returnValueColName];
    } else {
        return "not found";
    }
}

//POSTED VALUE STERILIZATION

function sterilizeValue($value){
    include "connect.php";
    $sterilized = stripslashes($value);
    $sterilized = mysqli_real_escape_string($conn, $sterilized);
    return $sterilized;
}

//DYNAMIC BIND PARAM FOR PREPARED STATEMENT

function DynamicBindVariables($stmt, $params){
    if ($params != null) {
        // Generate the Type String (eg: 'issisd')

        $types = "";

        foreach ($params as $param) {
            if (is_int($param)) {
                // Integer

                $types .= "i";
            } elseif (is_float($param)) {
                // Double

                $types .= "d";
            } elseif (is_string($param)) {
                // String

                $types .= "s";
            } else {
                // Blob and Unknown

                $types .= "b";
            }
        }

        // Add the Type String as the first Parameter

        $bind_names[] = $types;

        // Loop thru the given Parameters

        for ($i = 0; $i < count($params); $i++) {
            // Create a variable Name

            $bind_name = "bind" . $i;

            // Add the Parameter to the variable Variable

            $$bind_name = $params[$i];

            // Associate the Variable as an Element in the Array

            $bind_names[] = &$$bind_name;
        }

        // Call the Function bind_param with dynamic Parameters

        call_user_func_array([$stmt, "bind_param"], $bind_names);
    }

    return $stmt;
}

// SINGLE FILE UPLOAD

function singleFileUpload($file, $uploadPath){
    $imgFileName = $file["name"];

    $imgNewFileName = preg_replace("/\s+/", "", $imgFileName);

    if (move_uploaded_file($file["tmp_name"], $uploadPath . $file["name"])) {
        rename($uploadPath . $imgFileName, $uploadPath . $imgNewFileName);

        return true;
    } else {
        return false;
    }
}

function singleFileUploadWithRandomString($file, $uploadPath, $randomString){
    $imgFileName = $file["name"];

    $imgNewFileName = preg_replace("/\s+/", "", $imgFileName);

    $imgNewFileName = preg_replace("/[0-9]/", $randomString, $imgNewFileName);

    if (move_uploaded_file($file["tmp_name"], $uploadPath . $file["name"])) {
        rename($uploadPath . $imgFileName, $uploadPath . $imgNewFileName);

        return true;
    } else {
        return false;
    }
}

// MULTIPLE FILES UPLOAD

function multipleFilesUpload($files, $uploadPath){
    if (isset($files) && !empty($files)) {
        $no_files = count($files["name"]);

        for ($i = 0; $i < $no_files; $i++) {
            if ($files["error"][$i] > 0) {
                echo "Error: " . $files["error"][$i] . "<br>";
            } else {
                $imgFileName = $files["name"][$i];

                $imgNewFileName = preg_replace(
                    "/[^a-zA-Z0-9.]/",
                    "",
                    $imgFileName
                );

                if (file_exists($uploadPath . $files["name"][$i])) {
                    echo "File: " . $files["name"][$i] . " already exists";
                } else {
                    move_uploaded_file(
                        $files["tmp_name"][$i],
                        $uploadPath . $files["name"][$i]
                    );

                    rename(
                        $uploadPath . $imgFileName,
                        $uploadPath . $imgNewFileName
                    );
                }
            }
        }

        return true;
    } else {
        return false;
    }
}

//VALIDATES UPLOADED IMAGE FILE EXTENSIONS

function validateFileExtensions($fileArray){
    $allowedExtensions = ["jpg", "png", "JPG", "webp"];

    if (isset($fileArray["name"]) && is_array($fileArray["name"])) {
        foreach ($fileArray["name"] as $fileName) {
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

            if (!in_array($fileExtension, $allowedExtensions)) {
                return false; // Extension not allowed
            }
        }
    }
    return true; // All file extensions are allowed
}

//CHECK IF INPUT TYPE FILE HAS SELECTED ANY FILE

function fileSelected($file){
    if (is_uploaded_file($file["tmp_name"])) {
        return true;
    } else {
        return false;
    }
}

//FILE MOVE ACTION

function moveFile($oldroute, $newRoute){
    if (rename($oldroute, $newRoute)) {
        return true;
    } else {
        return false;
    }
}

// FILE REPLACE ACTION

function replaceFile($oldFileName, $newFile, $filePath){
    if (file_exists($filePath . $oldFileName)) {
        unlink($filePath . $oldFileName);
    }
    
    if (singleFileUpload($newFile, $filePath)) {
        return true;
    } else {
        return false;
    }
}

//ENCRYPT DATA

function encrypt($value)
{
    $val = base64_encode($value);
    $val = reverse($val);
    return $val;
}

//DECRYPT DATA

function decrypt($value){
    $val = reverse($value);
    $val = base64_decode($val);
    return $val;
}

//STRING REVERSE

function reverse($str){
    return strrev($str);
}

//CREATE FOLDER

function createFolder($route, $name){
    $foldername = preg_replace("/\s+/", "", $name);
    $foldername = strtolower($foldername);
    $dirpath = $route . $foldername;
    if (!file_exists($dirpath)) {
        mkdir($dirpath, 0777, true);
    }
}

//DELETE FILE OR DIRECTORY

function deleteDirectory($dirPath){
    if (is_dir($dirPath)) {
        $objects = scandir($dirPath);

        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (
                    filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir"
                ) {
                    deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dirPath . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        reset($objects);
        rmdir($dirPath);
    }
}

//GET FOLDER NAME

function getFolderName($name){
    $foldername = preg_replace("/\s+/", "", $name);
    $foldername = strtolower($foldername);
    return $foldername;
}

function login($auth_table, $userID_colName, $user_ID_colValue, $auth_password_colName, $password_table, $password_colName, $password_table_search_key_name, $user_inserted_pwd){
    
    $user_inserted_pwd = encrypt($user_inserted_pwd);

    if(checkDataExistance($auth_table, array($userID_colName), array($user_ID_colValue))){ // check if this user exists

        $id = getValue($auth_table, "id", $userID_colName, $user_ID_colValue); 
        $pwd_1 = getValue($auth_table, $auth_password_colName, $userID_colName, $user_ID_colValue);
        $pwd_2 = getValue($password_table, $password_colName, $password_table_search_key_name, $id);
        $password = $pwd_1 . $pwd_2;
        $password = decrypt($password);
        $password = sterilizeValue($password);
        // return $password;
        if($password === decrypt($user_inserted_pwd)){
            return true;
        }else{
            return false;
        }

    }else{
        return false;
    }
}

function dataEncrypt($value)
{
    $val = base64_encode($value);
    $val = reverse($val);
    return $val;
}

function dataDecrypt($value)
{
    $val = reverse($value);
    $val = base64_decode($val);
    return $val;
}

function string_reverse($str)
{
    return strrev($str);
}

function generateRandomString($length = 5)
{
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $charactersLength = strlen($characters);
    $randomString = "";
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

//SET NEW COOKIE

function setNewCookie($cookieName, $cookieValue, $path, $smtpUsername, $smtpPassword)
{
    setcookie($cookieName, $cookieValue, time() + 86400 * 30, $path); // 86400 = 1 day
}

function sendEmail($name, $email, $message, $setFromEmail)
{
    //Load Composer's autoloader
    require "vendor/autoload.php";

	//Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = 2; //Enable verbose debug output
        $mail->isSMTP(); //Send using SMTP
        $mail->Host = "smtp.gmail.com"; //Set the SMTP server to send through
        $mail->SMTPAuth = true; //Enable SMTP authentication
        $mail->Username = "simpliraisecanada@gmail.com"; //SMTP username
        $mail->Password = "cpcjbypzisoyikpr"; //SMTP password
        $mail->SMTPSecure = "ssl"; //Enable implicit TLS encryption
        $mail->Port = 465; //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
        $mail->setFrom($setFromEmail, "Mailer");
        $mail->addAddress($email, $name); //Add a recipient
        $mail->addAddress($email); //Name is optional
        // $mail->addReplyTo('info@example.com', 'Information');
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        //Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

        //Content
        $mail->isHTML(true); //Set email format to HTML
        $mail->Subject = "Message From SimpliRaise Website";
        $mail->Body = $message;
        $mail->AltBody = $message;

        $mail->send();
        echo "Message has been sent";
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function swapContentUp($code){
    include "connect.php";

    $nextRowValues = array();
    $currentRowValues = array();

    $code = dataDecrypt($_GET['code']);
    $code = explode('@#', $code);
    $id = (int)$code[0];
    $table = $code[1];
    $searchKeyName = $code[2];

    $colNames = getTableColNames($table);
    array_shift($colNames);

    $sql = "SELECT * FROM " . $table . " WHERE id > " . $id . " LIMIT 1";
    $result  = $conn->query($sql);

    $status = false;
    if (mysqli_num_rows($result) > 0) {
        $row = $result->fetch_assoc();

        for($n=0; $n < count($colNames); $n++){
            array_push($nextRowValues,$row[$colNames[$n]]);
        }

        $nextID = $row["id"];
        //push the id
        array_push($nextRowValues,$id);
        $status = true;
    }
    
    if($status == true){
        $sql = "SELECT * FROM " . $table . " WHERE id = " . $id;
        $result  = $conn->query($sql);
        $row = $result->fetch_assoc();

        for($n=0; $n < count($colNames); $n++){
            array_push($currentRowValues,$row[$colNames[$n]]);
        }
        //push the id
        array_push($currentRowValues,$nextID);

        if(update($table, $colNames, $currentRowValues, $searchKeyName) && update($table, $colNames, $nextRowValues, $searchKeyName)){
            return true;
        }else{
            return false;
        }
    }else{
        return false;
    }
    
}

function swapContentDown($code){
    include "connect.php";

    $prevRowValues = array();
    $currentRowValues = array();

    $code = dataDecrypt($_GET['code']);
    $code = explode('@#', $code);
    $id = (int)$code[0];
    $table = $code[1];
    $searchKeyName = $code[2];

    $colNames = getTableColNames($table);
    array_shift($colNames);

    $sql = "SELECT * FROM " . $table . " WHERE id < " . $id . " ORDER BY id DESC LIMIT 1";
    $result  = $conn->query($sql);

    $status = false;
    if (mysqli_num_rows($result) > 0) {
        $row = $result->fetch_assoc();

        for($n=0; $n < count($colNames); $n++){
            array_push($prevRowValues,$row[$colNames[$n]]);
        }

        $prevID = $row["id"];
        //push the id
        array_push($prevRowValues,$id);

        $status = true;
        
    }

    if($status == true){
        $sql = "SELECT * FROM " . $table . " WHERE id = " . $id;
        $result  = $conn->query($sql);
        $row = $result->fetch_assoc();

        for($n=0; $n < count($colNames); $n++){
            array_push($currentRowValues,$row[$colNames[$n]]);
        }
        //push the id
        array_push($currentRowValues,$prevID);

        if(update($table, $colNames, $currentRowValues, $searchKeyName) && update($table, $colNames, $prevRowValues, $searchKeyName)){
            return true;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

function getTableColNames($table) {
    include "connect.php";

    // Query to get column names
    $sql = "SHOW COLUMNS FROM $table";
    $result = $conn->query($sql);
    $columnNames = [];

    if ($result->num_rows > 0) {
        // Output data of each row
        while ($row = $result->fetch_assoc()) {
            $columnNames[] = $row["Field"];
        }
    } else {
        $columnNames[] = "null";
    }
    return $columnNames;
}

function validateDate($date) {
    // Check if the input consists only of valid characters for a date in mm/dd/yyyy format
    if (preg_match('/^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/(19|20)\d{2}$/', $date)) {
        // Attempt to create a DateTime object from the input date
        $dateTime = DateTime::createFromFormat('m/d/Y', $date);
        if ($dateTime !== false && $dateTime->format('m/d/Y') === $date) {
            return true; // Valid date in mm/dd/yyyy format
        }
    }
    return false; // Invalid date or not in mm/dd/yyyy format
}

function convertDateFormat($date, $currentDateFormat, $desiredFormat) {
    // Create DateTime object from the input date using the current date format
    $dateTime = DateTime::createFromFormat($currentDateFormat, $date);
    
    // Check if DateTime object was created successfully
    if ($dateTime !== false) {
        // Format the DateTime object with the desired format
        return $dateTime->format($desiredFormat);
    } else {
        // Return false if the input date format is invalid
        return false;
    }
}

function checkfileExtention($filename){
    $file_parts = pathinfo($filename);
    $file_parts['extension'];
    $cool_extensions = Array('jpg','png','JPG');
    if (in_array($file_parts['extension'], $cool_extensions)){
        return true;
    } else {
        return false;
    }
}

?>
