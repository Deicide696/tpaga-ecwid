<?php
/**
 * Created by PhpStorm.
 * User: cristianpinilla
 * Date: 8/21/17
 * Time: 3:24 PM
 */

function findUser($customerId)
{
    $servername = "localhost";
    $username = "uvazulco_cpmega";
    $password = "Megapiel.C0m";
    $dbname = "uvazulco_tpaga_megapiel";

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $sql = "SELECT * FROM user WHERE customer_id_ecwid = " . $customerId;

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        while ($row = mysqli_fetch_assoc($result))
        {
            return [$row["id"], $row["token_tpaga"]];
        }
    } else {
        echo false;
    }

    mysqli_close($conn);
}


function findAllCreditCards($id)
{
    $servername = "localhost";
    $username = "uvazulco_cpmega";
    $password = "Megapiel.C0m";
    $dbname = "uvazulco_tpaga_megapiel";

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $sql = "SELECT * FROM credit_card WHERE user_id = " . $id;

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        $respuesta = [];
        while ($row = mysqli_fetch_assoc($result))
        {
            //            echo "customer_id_ecwid: " . $row["customer_id_ecwid"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
            //        echo "customer_id_ecwid: " . $row["customer_id_ecwid"];
//            print_r($row['last_four']); die();
            array_push($respuesta, [$row['last_four'], $row['token']]);
        }
        return $respuesta;
    }

    else
    {
        echo false;
    }

    mysqli_close($conn);
}

?>