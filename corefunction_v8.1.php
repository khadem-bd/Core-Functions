<?php

//Import PHPMailer classes into the global namespace
use PHPMailer\vendor\PHPMailer\PHPMailer;
use PHPMailer\vendor\PHPMailer\SMTP;
use PHPMailer\vendor\PHPMailer\Exception;



//DATA INSERT FUNCTIONALITY WITH SUCCESS MESSAGE RETURN

function insert($tableName, $colNames, $colValues) {
    include "connect.php";

    $sql = "INSERT INTO " . $tableName . " (";

    $sql .= implode(", ", $colNames) . ") VALUES (" . str_repeat('?,', count($colNames) - 1) . "?)";

    $pre_stmt = $conn->prepare($sql);

    DynamicBindVariables($pre_stmt, $colValues);

    if ($pre_stmt->execute()) {
        $pre_stmt->close();
        $conn->close();
        return true;
    } else {
        $pre_stmt->close();
        $conn->close();
        return false;
    }
}


//DATA INSERT FUNCTIONALITY WITH INSERTED ROW ID RETURN

function insert_rowid($tableName, $colNames, $colValues) {
    include "connect.php";

    $sql = "INSERT INTO " . $tableName . " (";

    $sql .= implode(", ", $colNames) . ") VALUES (" . str_repeat('?,', count($colNames) - 1) . "?)";

    $pre_stmt = $conn->prepare($sql);

    DynamicBindVariables($pre_stmt, $colValues);

    if ($pre_stmt->execute()) {
        $last_id = $conn->insert_id;
        $pre_stmt->close();
        $conn->close();
        return $last_id;
    } else {
        $pre_stmt->close();
        $conn->close();
        return false;
    }
}


//DATA EDIT FUNCTIONALITY WITH SUCCESS MESSAGE RETURN

function update($tableName, $colNames, $colValues, $searchKeyName) {
    include "connect.php";

    $sql = "UPDATE " . $tableName . " SET ";

    $sql .= implode("=?, ", $colNames) . "=? WHERE " . $searchKeyName . "=?";

    $pre_stmt = $conn->prepare($sql);

    $colValues[] = end($colValues); // Add search key to colValues array

    DynamicBindVariables($pre_stmt, $colValues);

    if ($pre_stmt->execute()) {
        $pre_stmt->close();
        $conn->close();
        return true;
    } else {
        $pre_stmt->close();
        $conn->close();
        return false;
    }
}


// DATA DELETE FUNCTIONALITY

function delete($tablename, $searchKeyColName, $searchKey, $deleteType, $fileColName = null, $folderPath = null) {
    include "connect.php";

    if (checkDataExistance($tablename, [$searchKeyColName], [$searchKey])) {
        if ($deleteType == "data") {
            $sql = "DELETE FROM " . $tablename . " WHERE " . $searchKeyColName . "=?";
            $pre_stmt = $conn->prepare($sql);

            $type = (is_int($searchKey)) ? "i" : ((is_float($searchKey)) ? "d" : "s");

            $pre_stmt->bind_param($type, $searchKey);

            if ($pre_stmt->execute()) {
                $pre_stmt->close();
                $conn->close();
                return true;
            } else {
                $pre_stmt->close();
                $conn->close();
                return false;
            }
        } elseif ($deleteType == "data_with_file" && $fileColName && $folderPath) {
            $sql = "SELECT " . $fileColName . " FROM " . $tablename . " WHERE " . $searchKeyColName . "=?";
            $pre_stmt = $conn->prepare($sql);

            DynamicBindVariables($pre_stmt, [$searchKey]);

            $pre_stmt->execute();
            $result = $pre_stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                unlink($folderPath . $row[$fileColName]);
            }

            $sql = "DELETE FROM " . $tablename . " WHERE " . $searchKeyColName . "=?";
            $pre_stmt = $conn->prepare($sql);

            $type = (is_int($searchKey)) ? "i" : ((is_float($searchKey)) ? "d" : "s");

            $pre_stmt->bind_param($type, $searchKey);

            if ($pre_stmt->execute()) {
                $pre_stmt->close();
                $conn->close();
                return true;
            } else {
                $pre_stmt->close();
                $conn->close();
                return false;
            }
        }
    }
    return false;
}


// CHECK DATA EXISTANCE FUNCTIONALITY

function checkDataExistance($tablename, $colNames, $colValues) {
    include "connect.php";

    $sql = "SELECT " . implode(", ", $colNames) . " FROM " . $tablename . " WHERE " . implode("=? AND ", $colNames) . "=?";

    $pre_stmt = $conn->prepare($sql);

    DynamicBindVariables($pre_stmt, $colValues);

    if ($pre_stmt->execute()) {
        $result = $pre_stmt->get_result();
        $exists = $result->num_rows > 0;
        $pre_stmt->close();
        $conn->close();
        return $exists;
    } else {
        $pre_stmt->close();
        $conn->close();
        return false;
    }
}


//QUERY RETURN VALUE

function getValue($table, $returnValueColName, $SearchKeyName, $searchKey) {
    include "connect.php";

    $sql = "SELECT " . $returnValueColName . " FROM " . $table . " WHERE " . $SearchKeyName . "=?";

    $pre_stmt = $conn->prepare($sql);

    $type = (is_int($searchKey)) ? "i" : ((is_float($searchKey)) ? "d" : "s");

    $pre_stmt->bind_param($type, $searchKey);

    $pre_stmt->execute();

    $result = $pre_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $pre_stmt->close();
        $conn->close();
        return $row[$returnValueColName];
    } else {
        $pre_stmt->close();
        $conn->close();
        return "not found";
    }
}


//POSTED VALUE STERILIZATION

function sterilizeValue($value) {
    include "connect.php";
    $sterilized = stripslashes($value);
    $sterilized = mysqli_real_escape_string($conn, $sterilized);
    return $sterilized;
}


//DYNAMIC BIND PARAM FOR PREPARED STATEMENT

function DynamicBindVariables($stmt, $params) {
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

        // Loop through the given Parameters
        for ($i = 0; $i < count($params); $i++) {
            // Create a variable Name
            $bind_name = "bind" . $i;

            // Add the Parameter to the variable Variable
            ${$bind_name} = $params[$i];

            // Associate the Variable as an Element in the Array
            $bind_names[] = &${$bind_name};
        }

        // Call the Function bind_param with dynamic Parameters
        call_user_func_array([$stmt, "bind_param"], $bind_names);
    }

    return $stmt;
}


// FILE UPLOAD

function fileUpload($files, $uploadPath, $uploadType, $isEncrypted) {
    $errorMsg = "null";
    $status = false;

    if ($uploadType == "single") {
        $imgFileName = $files["name"];
        $imgNewFileName = $isEncrypted ? encrypt(preg_replace("/\s+/", "", $imgFileName)) : preg_replace("/\s+/", "", $imgFileName);

        if (move_uploaded_file($files["tmp_name"], $uploadPath . $imgFileName)) {
            if (rename($uploadPath . $imgFileName, $uploadPath . $imgNewFileName)) {
                $status = true;
            } else {
                $errorMsg = "Failed to rename file: " . $imgFileName;
            }
        } else {
            $errorMsg = "Failed to upload file: " . $imgFileName;
        }
    } elseif ($uploadType == "multiple") {
        if (isset($files) && !empty($files)) {
            $no_files = count($files["name"]);

            for ($i = 0; $i < $no_files; $i++) {
                if ($files["error"][$i] > 0) {
                    $errorMsg = "Error: " . $files["error"][$i];
                    $status = false;
                    break;
                } else {
                    $imgFileName = $files["name"][$i];
                    $imgNewFileName = preg_replace("/[^a-zA-Z0-9.]/", "", $imgFileName);

                    if ($isEncrypted) {
                        $imgNewFileName = encrypt($imgNewFileName);
                    }

                    if (file_exists($uploadPath . $imgFileName)) {
                        $errorMsg = "File: " . $imgFileName . " already exists";
                        $status = false;
                        break;
                    } else {
                        if (move_uploaded_file($files["tmp_name"][$i], $uploadPath . $imgFileName)) {
                            if (!rename($uploadPath . $imgFileName, $uploadPath . $imgNewFileName)) {
                                $errorMsg = "Failed to rename file: " . $imgFileName;
                                $status = false;
                                break;
                            }
                        } else {
                            $errorMsg = "Failed to upload file: " . $imgFileName;
                            $status = false;
                            break;
                        }
                    }
                }
            }

            if ($status !== false) {
                $status = true;
            }
        } else {
            $errorMsg = "No files have been selected";
            $status = false;
        }
    } else {
        $errorMsg = "Invalid upload type";
        $status = false;
    }

    return json_encode([$status, $errorMsg]);
}

// VALIDATES UPLOADED IMAGE FILE EXTENSIONS
function validateFileExtensions(array $fileArray): bool {
    $allowedExtensions = ["jpg", "png", "JPG", "webp"];

    if (isset($fileArray["name"]) && is_array($fileArray["name"])) {
        foreach ($fileArray["name"] as $fileName) {
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

            if (!in_array($fileExtension, $allowedExtensions, true)) {
                return false; // Extension not allowed
            }
        }
    }
    return true; // All file extensions are allowed
}

// CHECK IF INPUT TYPE FILE HAS SELECTED ANY FILE
function fileSelected(array $file): bool {
    return is_uploaded_file($file["tmp_name"]);
}

// FILE MOVE ACTION
function moveFile(string $oldRoute, string $newRoute): bool {
    return rename($oldRoute, $newRoute);
}

// FILE REPLACE ACTION
function replaceFile(string $oldFileName, array $newFile, string $filePath): bool {
    if (file_exists($filePath . $oldFileName)) {
        unlink($filePath . $oldFileName);
    }

    return singleFileUpload($newFile, $filePath);
}

// ENCRYPT DATA
function encrypt(string $value): string {
    return strrev(base64_encode($value));
}

// DECRYPT DATA
function decrypt(string $value): string {
    return base64_decode(strrev($value));
}

// STRING REVERSE
function reverse(string $str): string {
    return strrev($str);
}

// CREATE FOLDER
function createFolder(string $route, string $name): void {
    $foldername = strtolower(preg_replace("/\s+/", "", $name));
    $dirpath = $route . $foldername;
    if (!file_exists($dirpath)) {
        mkdir($dirpath, 0777, true);
    }
}

// DELETE FILE OR DIRECTORY
function deleteDirectory(string $dirPath): void {
    if (is_dir($dirPath)) {
        $objects = scandir($dirPath);

        foreach ($objects as $object) {
            if ($object !== "." && $object !== "..") {
                if (is_dir($dirPath . DIRECTORY_SEPARATOR . $object)) {
                    deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dirPath . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        rmdir($dirPath);
    }
}

// GET FOLDER NAME
function getFolderName(string $name): string {
    return strtolower(preg_replace("/\s+/", "", $name));
}

// LOGIN
function login(string $auth_table, string $userID_colName, string $user_ID_colValue, string $auth_password_colName, string $password_table, string $password_colName, string $password_table_search_key_name, string $user_inserted_pwd): bool {
    $user_inserted_pwd = encrypt($user_inserted_pwd);

    if (checkDataExistance($auth_table, [$userID_colName], [$user_ID_colValue])) { // check if this user exists
        $id = getValue($auth_table, "id", $userID_colName, $user_ID_colValue);
        $pwd_1 = getValue($auth_table, $auth_password_colName, $userID_colName, $user_ID_colValue);
        $pwd_2 = getValue($password_table, $password_colName, $password_table_search_key_name, $id);
        $password = decrypt($pwd_1 . $pwd_2);
        $password = sterilizeValue($password);

        return $password === decrypt($user_inserted_pwd);
    }
    
    return false;
}

// GENERATE RANDOM STRING
function generateRandomString(int $length = 5): string {
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $charactersLength = strlen($characters);
    $randomString = "";

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

// SET NEW COOKIE
function setNewCookie(string $cookieName, string $cookieValue, string $path): void {
    setcookie($cookieName, $cookieValue, [
        'expires' => time() + 86400 * 30,
        'path' => $path,
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// SEND EMAIL
function sendEmail(string $name, string $email, string $message, string $setFromEmail): void {
    // Load Composer's autoloader
    require "vendor/autoload.php";

    // Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
        $mail->isSMTP(); // Send using SMTP
        $mail->Host = "smtp.gmail.com"; // Set the SMTP server to send through
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = "simpliraisecanada@gmail.com"; // SMTP username
        $mail->Password = "cpcjbypzisoyikpr"; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
        $mail->Port = 465; // TCP port to connect to

        // Recipients
        $mail->setFrom($setFromEmail, "Mailer");
        $mail->addAddress($email, $name); // Add a recipient
        $mail->addAddress($email); // Name is optional

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = "Message From SimpliRaise Website";
        $mail->Body = $message;
        $mail->AltBody = $message;

        $mail->send();
        echo "Message has been sent";
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// SWAP CONTENT UP
function swapContentUp(string $code): bool {
    include "connect.php";

    $nextRowValues = [];
    $currentRowValues = [];

    $code = dataDecrypt($_GET['code']);
    $code = explode('@#', $code);
    $id = (int)$code[0];
    $table = $code[1];
    $searchKeyName = $code[2];

    $colNames = getTableColNames($table);
    array_shift($colNames);

    $sql = "SELECT * FROM " . $table . " WHERE id > " . $id . " LIMIT 1";
    $result = $conn->query($sql);

    $status = false;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        foreach ($colNames as $colName) {
            $nextRowValues[] = $row[$colName];
        }

        $nextID = $row["id"];
        $nextRowValues[] = $id;
        $status = true;
    }

    if ($status) {
        $sql = "SELECT * FROM " . $table . " WHERE id = " . $id;
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

        foreach ($colNames as $colName) {
            $currentRowValues[] = $row[$colName];
        }
        $currentRowValues[] = $nextID;

        return update($table, $colNames, $currentRowValues, $searchKeyName) && update($table, $colNames, $nextRowValues, $searchKeyName);
    }
    
    return false;
}

// SWAP CONTENT DOWN
function swapContentDown(string $code): bool {
    include "connect.php";

    $prevRowValues = [];
    $currentRowValues = [];

    $code = dataDecrypt($_GET['code']);
    $code = explode('@#', $code);
    $id = (int)$code[0];
    $table = $code[1];
    $searchKeyName = $code[2];

    $colNames = getTableColNames($table);
    array_shift($colNames);

    $sql = "SELECT * FROM " . $table . " WHERE id < " . $id . " ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    $status = false;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        foreach ($colNames as $colName) {
            $prevRowValues[] = $row[$colName];
        }

        $prevID = $row["id"];
        $prevRowValues[] = $id;
        $status = true;
    }

    if ($status) {
        $sql = "SELECT * FROM " . $table . " WHERE id = " . $id;
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

        foreach ($colNames as $colName) {
            $currentRowValues[] = $row[$colName];
        }
        $currentRowValues[] = $prevID;

        return update($table, $colNames, $currentRowValues, $searchKeyName) && update($table, $colNames, $prevRowValues, $searchKeyName);
    }
    
    return false;
}

// GET TABLE COLUMN NAMES
function getTableColNames(string $table): array {
    include "connect.php";

    $sql = "SHOW COLUMNS FROM $table";
    $result = $conn->query($sql);
    $columnNames = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $columnNames[] = $row["Field"];
        }
    } else {
        $columnNames[] = "null";
    }
    
    return $columnNames;
}

// VALIDATE DATE
function validateDate(string $date): bool {
    return preg_match('/^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/(19|20)\d{2}$/', $date) &&
           DateTime::createFromFormat('m/d/Y', $date) !== false &&
           DateTime::createFromFormat('m/d/Y', $date)->format('m/d/Y') === $date;
}

// CONVERT DATE FORMAT
function convertDateFormat(string $date, string $currentDateFormat, string $desiredFormat): string|false {
    $dateTime = DateTime::createFromFormat($currentDateFormat, $date);
    return $dateTime !== false ? $dateTime->format($desiredFormat) : false;
}

// CHECK FILE EXTENSION
function checkfileExtension(string $filename): bool {
    $file_parts = pathinfo($filename);
    $cool_extensions = ['jpg', 'png', 'JPG'];
    return in_array($file_parts['extension'], $cool_extensions, true);
}
?>
